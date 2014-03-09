<?php

use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;

class Demo extends CI_Controller {

    protected $request;
    protected $authenticator;
    protected $auth_response;
    protected $user;
    protected $client;

    public function __construct(){

        parent::__construct();

        $this->load->model('v1/Robot_model');

        $ioc = $this->config->item('ioc');

        $this->request = $ioc['Request'];

        $this->authenticator = $ioc['PolyAuth\Authenticator'];
        $this->authenticator->start();

        $this->auth_response = $this->authenticator->get_response();
        $this->user = $this->authenticator->get_user();

        $this->client = new Client;
        $this->client->setUserAgent('Snapsearch', true);

    }

    /**
     * Get the Demo result based of a single URL.
     *
     * TODO: Leaky Bucket API throttling.
     */
    public function show(){

        $parameters = $this->request->query->all();

        if(!isset($parameters['url'])){

            $this->auth_response->setStatusCode(400);
            $content['url'] = 'No URL query parameter.';
            $code = 'validation_error';

        }else{

            //we only care about the url
            $url = $parameters['url'];

            //demo cache will be saved as administrator, usages are not tracked
            $user_id = 1;

            $curl_result = false;
            $curl_errors = false;
            try{
                $curl_query = $this->client->get($parameters['url'], [
                    'Accept-Encoding'   => 'gzip, deflate, identity',
                ], [
                    'allow_redirects'   => false,
                    'exceptions'        => false
                ]);
                $curl_response = $curl_query->send();
                $curl_result = $curl_response->getBody(true);
            }catch(CurlException $e){
                $curl_errors = [
                    'system_error'  => 'Curl failed. Try again later.',
                ];
            }

            //send query to robot
            $robot_query = $this->Robot_model->read_site($user_id, [
                'url'   => $url
            ]);

            $robot_result = false;
            $robot_errors = false;
            if($robot_query){
                //only get the html
                $robot_result = $query['html'];
            }else{
                $robot_errors = $this->Robot_model->get_errors();
            }

            if($curl_result AND $robot_result){

                $content = [
                    'withoutSnapSearch' => $curl_result,
                    'withSnapSearch'    => $robot_result,
                ];
                $code = 'success';

            }else{

                $this->auth_response->setStatusCode(500);

                //even though there are 2 error states here, basically it's a system error if the demo didn't work
                $code = 'system_error';

                if(is_array($curl_errors)){
                    $content['curlErrors'] = current($curl_errors);
                }

                if(is_array($robot_errors)){
                    $content['robotErrors'] = current($robot_errors);
                }

            }

        }

        $this->auth_response->sendHeaders();
        
        $output = array(
            'content'   => $content,
            'code'      => $code,
        );

        Template::compose(false, $output, 'json');

    }

}