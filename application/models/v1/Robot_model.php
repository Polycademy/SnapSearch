<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\File;

class Robot_model extends CI_Model{

    protected $robot_uri;
    protected $client;
    protected $lockpath;
    protected $filesystem;
    protected $errors;

    public function __construct(){

        parent::__construct();

        $this->robot_uri = 'http://127.0.0.1:8499';
        
        $this->client = new Client;
        $this->client->setUserAgent('Snapsearch', true);

        $this->lockpath = FCPATH . 'snapshots/lockfiles';
        $this->filesystem = new Filesystem (new LocalAdapter (FCPATH . 'snapshots/cache', true, 755));

        $this->load->library('form_validation', false, 'validator');

    }

    public function read_site($user_id, $input_parameters){

        $parameters = elements(array(
            'url',
            'useragent',
            'width',
            'height',
            'imgformat',
            'screenshot',
            'navigate',
            'loadimages',
            'javascriptenabled',
            'totaltimeout',
            'maxtimeout',
            'initialwait',
            'callback',
            'meta',
            'cache',
            'cachetime',
            'refresh',
            'test'
        ), $input_parameters, null, true);

        // validate our input data
        if (!empty($validation_errors = $this->validate_robot_request($parameters))) {

            $this->errors = array(
                'validation_error'  => $validation_errors
            );
            return false;

        }

        // check if the request is a test request
        if ($this->check_test_mode($parameters)) {

            return [
                'id'                 => null, 
                'cache'              => null,
                'callbackResult'     => '',
                'date'               => time(),
                'generationDatetime' => date('Y-m-d H:i:s'), 
                'headers'            => [],
                'html'               => '',
                'message'            => 'You are in test mode. Your request was received!',
                'pageErrors'         => [],
                'screensot'          => '',
                'status'             => 200
            ];

        }

        // check if the request will use cache
        $using_cache = $this->check_cache_request($parameters);

        // check if the request is a refresh request
        $refresh = $this->check_refresh_request($parameters);

        // filter the parameters to defaults
        $parameters = $this->default_parameters_filtering($parameters);

        // canonicalise parameter order for checksum generation
        ksort($parameters); 

        // serialise our parameters
        $encoded_parameters = json_encode($parameters);

        // checksum to compare snapshots
        $parameters_checksum = hash('sha256', $encoded_parameters);

        // generation datetime as the event ordinal
        $snapshot_generation_datetime = date('Y-m-d H:i:s');

        // only try to read the cache if:
        // 1. the cache has to be considered and 
        // 2. this isn't a refresh request
        $cache = false;
        if ($using_cache AND !$refresh) {

            // we may get a fresh or expired snapshot
            // save this cache for later
            $cache = $this->read_cache($parameters_checksum, $parameters['cachetime']);

            list($status, $snapshot) = $cache;

            if ($status == 'fresh') {
                return $this->return_cached_response($snapshot, $snapshot_generation_datetime);
            }

        }

        // only try to regenerate the cache if the cache has to be considered
        // we will be using a lockfile to synchronise regeneration requests
        // each snapshot will have its own lockfile
        $lock = false;
        if ($using_cache) {

            // setup the snapshot lock
            $lock = $this->setup_lock($parameters_checksum);

            if (!$lock) {

                log_message('error', "Snapsearch PHP application could not open a lockfile (possible permission error on the filesystem) to regenerate $parameters_checksum.");

                $this->errors = array(
                    'system_error'  => 'Robot service could not open a lockfile to regenerate the cache. Try again later.',
                );

                return false;

            }

            // if this thread acquires the write lock, it becomes the primary thread
            // if this thread waits for a read lock, it becomes a secondary thread

            // if refresh request, then just get the write lock with timeout
            
            // if normal request, try to get a write lock with no timeout
            // if unsuccessful, return the expired snapshot
            // if no expired snapshot, get into a queue and wait for the snapshot 
            // to be generated by the primary thread

            if ($refresh) {

                list ($type, $snapshot) = $this->handle_refresh_cache_stampede ($lock);

            } else {

                list ($type, $snapshot) = $this->handle_cache_stampede (
                    $lock, 
                    $cache, 
                    $parameters_checksum, 
                    $parameters['cachetime'], 
                    2
                );
            
            }

            switch ($type) {

                case 'write': // got the write lock, proceed to regeneration
                    // pass
                break;

                case 'read': // got the cached response, returning the cache

                    $this->release_and_close_lock ($lock);

                    return $this->return_cached_response($snapshot, $snapshot_generation_datetime);

                break;

                case 'timeout': // could not acquire write lock or did not receive an event firing

                    $this->release_and_close_lock ($lock);

                    log_message('error', "Snapsearch PHP application timed out in acquiring a lock to regenerate $parameters_checksum.");

                    $this->errors = array(
                        'system_error'  => 'Robot service timed out in acquiring a lock to regenerate the cache. Try again later.',
                    );

                    return false;

                break;

                case 'limit': // exhausted cycle limit when trying to regenerate the cache

                    $this->release_and_close_lock ($lock);

                    log_message('error', "Snapsearch PHP application reached the cycle limit in regenerating $parameters_checksum.");

                    $this->errors = array(
                        'system_error'  => 'Robot service reached the cycle limit in cache regeneration attempts. Try again later.',
                    );

                    return false;

                break;

            }

        }

        // we can only get here through the write path
        // we shall regenerate the cache now
        // we will release_and_close the lock only after saving the response to the database
        // or if we hit an error during regeneration
        // or if this script exits

        try{

            $request = $this->client->post(
                $this->robot_uri, 
                array(
                    'Content-Type'  => 'application/json'
                ),
                $encoded_parameters
            );

            // decode the returned json into an array
            $response = $request->send();
            $response_array = $response->json();

        }catch(BadResponseException $e){

            // a bad response exception can come from 400 or 500 errors, this should not happen
            log_message('error', 'Snapsearch PHP application received a 400/500 from Robot\'s load balancer or robot itself.');
            $this->errors = array(
                'system_error'  => 'Robot service is a bit broken. Try again later.',
            );

            $this->release_and_close_lock ($lock);

            return false;

        }catch(CurlException $e){

            log_message('error', 'Snapsearch PHP application received a curl error when contacting the robot load balancer. See: ' . $e->getMessage());
            $this->errors = array(
                'system_error'  => 'Curl failed. Try again later.'
            );

            $this->release_and_close_lock ($lock);

            return false;

        }

        if($response_array['message'] == 'Failed'){

            $this->errors = [
                'validation_error'  => [ 'url'  => 'Robot could not open url: ' . $parameters['url'] ],
            ];

            $this->release_and_close_lock ($lock);

            return false;

        }

        // if we need to handle a redirect, and our handling fails, then we return false
        if (!$response_array = $this->handle_redirect_shim($parameters['url'], $response_array)) {

            $this->errors = [
                'system_error'  => 'Curl failed. Try again later.'
            ];

            $this->release_and_close_lock ($lock);

            return false;

        }

        // recalculating the content-length header based on content-type character set if they exist
        $response_array = $this->recount_content_length($response_array);
        

        // only cache the result if the cache option was true
        if ($using_cache) {

            $response_string = json_encode($response_array);

            // upsert will use parameters checksum as the unique key
            $id = $this->upsert_cache(
                $user_id, 
                $parameters['url'], 
                $snapshot_generation_datetime, 
                $parameters_checksum, 
                $response_string
            );

            // finished regeneration!
            $this->release_and_close_lock ($lock);

            // pass the id into it!
            // if the upsert failed, we make the id = null
            // this is actually a hint to whether the cache is working or not
            if ($id) {
                $response_array['id'] = $id;    
            } else {
                $response_array['id'] = null;
            }

        } else {

            // not saved into the cache, means there's no snapshot id
            // this cannot be done prior to the condition because 
            // it would change the saved snapshot contents
            $response_array['id'] = null;

        }

        // this is not a cached response
        $response_array['cache'] = false;
        $response_array['generationDatetime'] = $snapshot_generation_datetime;

        return $response_array;

    }

    public function purge_cache($allowed_length = false, $user_id = false){

        $cutoff_date = false;
        try{
            if($allowed_length){
                $current_date = new DateTime;
                $allowed_length = new DateInterval($allowed_length);
                $cutoff_date = $current_date->sub($allowed_length)->format('Y-m-d H:i:s');
            }
        }catch(Exception $e){
            return $e->getMessage();
        }

        if($cutoff_date){
            $this->db->where('date <', $cutoff_date);
        }

        if(is_int($user_id)){
            $this->db->where('userId', $user_id);
        }

        $query = $this->db->get('snapshots');

        if($query->num_rows() > 0){
            
            foreach($query->result() as $row){

                $this->delete_cache($row->parametersChecksum);

            }
            
        }

        return true;

    }

    public function update_api_requests ($user_id) {

        $sql = "UPDATE user_accounts SET apiRequests = apiRequests + 1 WHERE id = ?";
        $this->db->query($sql, array($user_id));

        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function update_api_usages ($user_id) {

        $sql = "UPDATE user_accounts SET apiUsage = apiUsage + 1 WHERE id = ?";
        $query = $this->db->query($sql, array($user_id));

        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function get_errors(){

        return $this->errors;

    }

    protected function validate_robot_request ($parameters) {

        $this->validator->set_data($parameters);

        $this->validator->set_rules([
            [
                'field' => 'url',
                'label' => 'Url (url)',
                'rules' => 'required',
            ],
            [
                'field' => 'useragent',
                'label' => 'User Agent (useragent)',
                'rules' => 'max_length[120]',
            ],
            [
                'field' => 'width',
                'label' => 'Width (width)',
                'rules' => 'greater_than_equal_to[200]|less_than_equal_to[4000]',
            ],
            [
                'field' => 'height',
                'label' => 'Height (height)',
                'rules' => 'greater_than_equal_to[200]|less_than_equal_to[4000]',
            ],
            [
                'field' => 'imgformat',
                'label' => 'Image format (imgformat)',
                'rules' => 'image_format',
            ],
            [
                'field' => 'screenshot',
                'label' => 'Screenshot (screenshot)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'navigate',
                'label' => 'Navigate (navigate)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'loadimages',
                'label' => 'Load images (loadimages)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'javascriptenabled',
                'label' => 'Javascript enabled (javascriptenabled)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'totaltimeout',
                'label' => 'Total timeout (totaltimeout)',
                'rules' => 'greater_than_equal_to[10000]|less_than_equal_to[30000]',
            ],
            [
                'field' => 'maxtimeout',
                'label' => 'Max timeout (maxtimeout)',
                'rules' => 'greater_than_equal_to[1000]|less_than_equal_to[15000]',
            ],
            [
                'field' => 'initialwait',
                'label' => 'Initial wait (initialwait)',
                'rules' => 'greater_than_equal_to[1000]|less_than_equal_to[15000]',
            ],
            [
                'field' => 'callback',
                'label' => 'Callback (callback)',
                'rules' => 'min_length[1]|max_length[8000]',
            ],
            [
                'field' => 'meta',
                'label' => 'Meta (meta)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'cache',
                'label' => 'Cache (cache)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'cachetime',
                'label' => 'Cache time (cachetime)',
                'rules' => 'greater_than_equal_to[1]|less_than_equal_to[720]',
            ],
            [
                'field' => 'refresh',
                'label' => 'Refresh (refresh)',
                'rules' => 'boolean_style',
            ],
            [
                'field' => 'test',
                'label' => 'Test Mode (test)',
                'rules' => 'boolean_style',
            ]
        ]);

        $validation_errors = [];

        //parameters that must exist
        if(!isset($parameters['url'])){
            $validation_errors['url'] = 'Url (url) is necessary.';
        }else{
            if (!$this->validate_url($parameters['url'])) {
                $validation_errors['url'] = 'Url (url) must be a valid url containing http or https as the host and a proper host domain.';
            }
        }

        // if there is a custom user agent, we need to make sure it has SnapSearch in it
        if (isset($parameters['useragent']) AND strpos($parameters['useragent'], 'SnapSearch') === false) {
            $validation_errors['useragent'] = 'User Agent (useragent) must contain the substring \'SnapSearch\'.';
        }

        if(isset($parameters['maxtimeout']) AND isset($parameters['initialwait'])){
            //initialwait has to be lower than maxtimeout
            if($parameters['initialwait'] >= $parameters['maxtimeout']){
                $validation_errors['initialwait'] = 'Initial wait (initialwait) needs to be lower than Max timeout (maxtimeout).';
            }
        }

        if($this->validator->run() ==  false){
            //overwrite the validation with form validation
            $validation_errors = array_merge($validation_errors, $this->validator->error_array());
        }

        return $validation_errors;

    }

    protected function validate_url ($url) {

        // empty url!?
        if (empty($url)) return false;

        // prefix the url with http if necessary
        if (!preg_match("~^(?:ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        $url_parts = parse_url($url);

        // malformed url
        if (!$url_parts OR !isset($url_parts['host'])) return false;

        // remove any whitespace
        $url_parts['host'] = trim($url_parts['host']);

        // remove any kind of `[]` for ipv6 because urls may be "[1080:0:0:0:8:800:200C:417A]""
        $url_parts['host'] = trim($url_parts['host'], "[]");

        // check if this an ip address
        // the regex checks for a top-level domain like (.com)
        // it is false if you pass things like "127.0.0.1" or "localhost" as these don't have top-level domains
        // the top-level domain needs at least one non-digit character so it does allow (.4a)
        if (!preg_match("~^[^\s/]+\.[^.\s/]*?[^.0-9\s/]~i", $url_parts['host'])) {

            // ok so it might be an ip address

            // we need to check for loopback addresses, these aren't well supported by filter_var
            // if they are loopbacks, we need to return false
            if (preg_match(
                "~^localhost$|^127(?:\.[0-9]+){0,2}\.[0-9]+$|^(?:0*\:)*?:?0*1$~i", 
                $url_parts['host'])
            ) return false;

            // we're going to prevent local, private and reserved addresses
            // this also fails for "localhost" as it's not an ip address
            // return true if it's an ip address that works out
            return !!(filter_var(
                $url_parts['host'], 
                FILTER_VALIDATE_IP, 
                FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ));
        
        }

        // at this point we're pretty sure it's an actual domain, so we can return true

        // we still need to firewall the process using this url from accessing local, private and reserved addresses
        // because DNS resolution can still result in resolving to disallowed addresses 

        return true;

    }

    protected function check_test_mode ($parameters) {

        if(isset($parameters['test'])){
            return filter_var($parameters['test'], FILTER_VALIDATE_BOOLEAN);
        } else {
            return false;
        }

    }

    protected function check_cache_request ($parameters) {

        //default cache parameter of true
        if(isset($parameters['cache'])){
            return filter_var($parameters['cache'], FILTER_VALIDATE_BOOLEAN);
        }else{
            return true;
        }

    }

    protected function check_refresh_request ($parameters) {

        if(isset($parameters['refresh'])) {
            return filter_var($parameters['refresh'], FILTER_VALIDATE_BOOLEAN);
        } else {
            return false;
        }

    }

    /**
     * Canonicalisation the parameters that have multiple true/false variants. This ensures an equivalence 
     * of requests between requests having different syntactical payloads but the same semantics.
     * The parameters will also be key sorted in another function afterwards.
     */
    protected function default_parameters_filtering ($parameters) {

        // remove unnecessary parameters not relevant to snapshot uniqueness
        unset($parameters['test']);
        unset($parameters['refresh']);
        unset($parameters['cache']);

        // default javascriptenabled of true
        if(isset($parameters['javascriptenabled'])){
            $parameters['javascriptenabled'] = filter_var($parameters['javascriptenabled'], FILTER_VALIDATE_BOOLEAN);
        }else{
            $parameters['javascriptenabled'] = true;
        }

        // default loadimages parameter of false
        if(isset($parameters['loadimages'])){
            $parameters['loadimages'] = filter_var($parameters['loadimages'], FILTER_VALIDATE_BOOLEAN);
        }else{
            $parameters['loadimages'] = false;
        }

        // default imgformat of png
        if(!isset($parameters['imgformat'])) $parameters['imgformat'] = 'png';

        // default screenshot parameter of false
        if(isset($parameters['screenshot'])){
            $parameters['screenshot'] = filter_var($parameters['screenshot'], FILTER_VALIDATE_BOOLEAN);
        }else{
            $parameters['screenshot'] = false;
        }

        //default navigate parameter of false, meaning we don't follow redirects
        if(isset($parameters['navigate'])){
            $parameters['navigate'] = filter_var($parameters['navigate'], FILTER_VALIDATE_BOOLEAN);
        }else{
            $parameters['navigate'] = false;
        }

        //default meta parameter of true, so we do use meta parameters if they exist
        if(isset($parameters['meta'])){
            $parameters['meta'] = filter_var($parameters['meta'], FILTER_VALIDATE_BOOLEAN);
        }else{
            $parameters['meta'] = true;
        }

        //default cachetime parameter of 24 hours
        if(!isset($parameters['cachetime'])) $parameters['cachetime'] = 24;

        return $parameters;

    }

    /**
     * Looks up for a cached snapshot indexed by a unique checksum.
     * 
     * If it finds a cache record in the database (regardless of freshness), 
     * it will return the decompressed and decoded contents as part of the 
     * returned array.
     *
     * Returns either an array: [$status, $snapshot] where:
     * $status = 'fresh' or 'expired'
     * $snapshot = null or 
     * [
     *     'id'                 => ...
     *     'userId'             => ...
     *     'date'               => ...
     *     'parametersChecksum' => ...
     *     'snapshotData'       => ...
     * ]
     * 
     * @param  string $parameters_checksum
     * @param  int    $parameters_cachetime
     * 
     * @return array
     */
    protected function read_cache($parameters_checksum, $parameters_cachetime){

        $this->db->where('parametersChecksum', $parameters_checksum);

        $query = $this->db->get('snapshots');

        if($query->num_rows() > 0){ // available snapshot in the cache
            
            $data = [
                'id'                    => $query->row()->id,
                'userId'                => $query->row()->userId,
                'date'                  => $query->row()->date,
                'parametersChecksum'    => $query->row()->parametersChecksum
            ];

            $snapshot_file = new File($parameters_checksum, $this->filesystem);

            // if the generation datetime is >= (current time - cache time period)
            // then we have a recent snapshot
            // otherwise we have an old snapshot
            $valid_timestamp = (new DateTime())
                ->sub(new DateInterval("PT${parameters_cachetime}H"))
                ->getTimestamp();

            if (strtotime($data['date']) >= $valid_timestamp) {
                $status = 'fresh';
            } else {
                $status = 'expired';
            }

            if ($snapshot_file->exists()) {

                $data['snapshotData'] = json_decode(
                    bzdecompress($snapshot_file->getContent()), 
                    true
                );

                return [$status, $data];

            }
            
            // if it doesn't exist, delete it
            $this->delete_cache($parameters_checksum);      
            
        }

        // non-existent
        return ['null', null];

    }

    /**
     * Takes a cached snapshot array, returns a response array.
     * The response array is the cache array with 2 extra properties:
     * 1. ['cache'] = true
     * 2. ['id'] = <snapshot id>
     * 
     * @param  array  $snapshot
     * @param  string $generation_datetime
     * @return array
     */
    protected function return_cached_response ($snapshot, $generation_datetime) {

        $response_array = $snapshot['snapshotData'];
        $response_array['cache'] = true;
        $response_array['id'] = $snapshot['id'];
        $response_array['generationDatetime'] = $generation_datetime;

        return $response_array;

    }

    /**
     * Setup the lockfile given a checksum as the lock file name.
     * Returns a file pointer to the lock file.
     * 
     * @param  string $checksum
     * @return pointer
     */
    protected function setup_lock ($checksum) {

        $lock = fopen ("{$this->lockpath}/{$checksum}.lock", 'w+');
        return $lock;

    }

    protected function acquire_lock ($lock, $type, $timeout) {

        if ($timeout < 1) {

            $got_lock = flock ($lock, $type | LOCK_NB);

        } else {

            $count = 0;
            $got_lock = true;

            // this optimistic method is flawed because continuous retries can 
            // result in resource starvation
            // it would be better for all threads to enter into a queue
            // but it is not possible under shared-nothing PHP
            
            // the trick is in the combination of LOCK_NB and $blocking
            // the $blocking variable is assigned by reference
            // it returns 1 when the flock is blocked from acquiring a lock
            // with LOCK_NB, the flock returns immediately instead of waiting indefinitely
            while (!flock($lock, $type | LOCK_NB, $would_block)) {
                if ($would_block AND $count++ < $timeout) {
                    sleep(1);
                } else {
                    $got_lock = false;
                    break;
                }
            }

        }

        return $got_lock;

    }

    protected function release_lock ($lock) {

        if ($lock) {
            return flock($lock, LOCK_UN);
        } else {
            return true;
        }

    }

    protected function release_and_close_lock ($lock) {

        if ($lock) {
            flock ($lock, LOCK_UN);
            fclose ($lock);
        }
        return true;

    }

    protected function handle_refresh_cache_stampede ($lock) {

        if ($this->acquire_lock($lock, LOCK_EX, 25)) {

            return ['write', null];

        } else {

            return ['timeout', null];

        }

    }

    protected function handle_cache_stampede (
        $lock, 
        $cache, 
        $parameters_checksum, 
        $parameters_cachetime, 
        $cycle_limit
    ) {

        // if the cycle reached 0, then, we do an early return of cycle limit
        if ($cycle_limit <= 0) {
            return ['limit', null];
        }

        // attempt to lock the mutex in order to regenerate the cache
        // if we succeed acquiring the lock, we just return 
        // if we fail to acquire the lock, we will try to return a valid most recent cache even though it is stale
        if ($this->acquire_lock($lock, LOCK_EX, 0)) {

            return ['write', null];

        } else {

            list ($status, $snapshot) = $cache;

            if ($status == 'fresh' OR $status == 'expired') {
                return ['read', $snapshot];                
            }

            // there was no cache available, so we have to 
            // wait for the primary thread to complete regeneration
            // time limit of 25 seconds
            if ($this->acquire_lock ($lock, LOCK_SH, 25)) {

                // if we have acquire the shared lock, this means another thread has regenerated, we can immediately early release our shared lock, in order to allow at least one thread to acquire another write lock
                $this->release_and_close_lock ($lock);

                list ($status, $snapshot) = $this->read_cache(
                    $parameters_checksum, 
                    $parameters_cachetime
                );

                if ($status == 'fresh') {

                    return ['read', $snapshot];

                } else {

                    log_message('error', "Snapsearch PHP application had to cycle once for regenerating $parameters_checksum. This can only happen where the primary thread failed to regenerate a snapshot.");

                    // if the cache was false or was not fresh
                    // then the regenerating thread failed to regenerate the cache
                    // at this point, we must cycle back to regenerating with a cycle limit of 1
                    // cache will be false and the cycle will be decremented
                    return $this->handle_cache_stampede(
                        $lock, 
                        ['null', null], 
                        $parameters_checksum, 
                        $parameters_cachetime, 
                        --$cycle_limit
                    );

                }

            } else {

                // timed out waiting for the event to fire
                // we must return an error
                // this should never happen
                return ['timeout', null];

            }

        }

    }

    protected function recount_content_length ($response_array) {

        // content-length from the headers can be different from the javascript generated content
        if(isset($response_array['headers'])){

            foreach($response_array['headers'] as &$header){

                if (strtolower($header['name']) == 'content-length') {
                    $header['value'] = strlen($response_array['html']);
                    break;
                }

            }

        }

        return $response_array;

    }

    protected function handle_redirect_shim ($url, $response_array) {

        //SHIM: this is a shim for supporting the scraping of redirected pages, this is because slimerjs currently does not support acquiring the headers or body of a redirection request
        if($this->is_redirect($response_array['status'])){

            try{

                //we don't want to follow redirects in this case
                $request = $this->client->get($url, [
                    'Accept-Encoding'   => 'gzip, deflate, identity',
                ], [
                    'allow_redirects'   => false,
                    'exceptions'        => false
                ]);

                $response = $request->send();

                //shim the headers as [['name' => 'Header Name', 'value' => 'Header Value']]
                $response_array['headers'] = [];
                foreach($response->getHeaders() as $header_key => $header_value){
                    $response_array['headers'][] = [
                        'name'  => (string) $header_key,
                        'value' => (string) $header_value,
                    ];
                }

                //shim the body
                $response_array['html'] = $response->getBody(true);

            }catch(CurlException $e){

                return false;

            }

        }

        return $response_array;

    }

    protected function is_redirect($status){

        //shim for null status https://github.com/laurentj/slimerjs/issues/167
        if(is_null($status)){
            return true;
        }

        return in_array((string) $status, [
            '301',
            '302',
            '303',
            '305',
            '306',
            '307',
            '308'
        ]);

    }

    protected function upsert_cache($user_id, $url, $generation_datetime, $parameters_checksum, $snapshot_data) {

        $snapshot_file = new File($parameters_checksum, $this->filesystem);
        $file_status = $snapshot_file->setContent(bzcompress($snapshot_data, 9));

        if($file_status !== false){

            // Updating id = LAST_INSERT_ID (id) in the update clause fixes last_insert_id to be 
            // meaningful for both inserts and updates
            $upsert_query =
                "INSERT INTO 
                 snapshots (
                    userId, 
                    url, 
                    date, 
                    parametersChecksum
                 ) 
                 VALUES (?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                    id = LAST_INSERT_ID(id), 
                    userId = VALUES(userId), 
                    url = VALUES(url), 
                    date = VALUES(date), 
                    parametersChecksum = VALUES(parametersChecksum)
                 ";

            $this->db->query($upsert_query, [
                $user_id, 
                $url, 
                $generation_datetime, 
                $parameters_checksum
            ]);

            // returns the last insert/update id
            return $this->db->insert_id();

        }

        return false;

    }

    protected function delete_cache($parameter_checksum){

        $query = $this->db->delete('snapshots', ['parametersChecksum' => $parameter_checksum]);
        $snapshot_file = new File($parameter_checksum, $this->filesystem);
        if ($snapshot_file->exists()) $snapshot_file->delete();
        return true;

    }

}