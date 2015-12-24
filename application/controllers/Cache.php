<?php

class Cache extends CI_Controller{

    protected $authenticator;
    protected $auth_response;
    protected $user;

    public function __construct(){

        parent::__construct();

        $ioc = $this->config->item('ioc');

        $this->load->model('Cache_model');

        $this->request = $ioc['Request'];

        $this->authenticator = $ioc['PolyAuth\Authenticator'];
        $this->authenticator->start();

        $this->auth_response = $this->authenticator->get_response();
        $this->user = $this->authenticator->get_user();

    }

    /**
     * Collection of snapshots. However it doesn't actually acquire the snapshot data, but data about the snapshots.
     */
    public function index(){

        $user_id = $this->input->get('user', true);
        $offset = $this->input->get('offset', true);
        $limit = $this->input->get('limit', true);
        $transform = $this->input->get('transform', true);

        $authorized = false;

        if(!$user_id){

            if($this->user->authorized(['roles' => 'admin'])){

                $authorized = true;

            }else{

                $this->auth_response->setStatusCode(401);
                $content = 'Not authorized to view this information.';
                $code = 'error';

            }

        }else{

            if($this->user->authorized([
                'roles' => 'admin'
            ], [
                'users' => $user_id
            ])){
                
                $authorized = true;

            }else{

                $this->auth_response->setStatusCode(401);
                $content = 'Not authorized to view this information.';
                $code = 'error';

            }

        }

        if($authorized){

            if($transform == 'count'){

                $query = $this->Cache_model->count($user_id);

            }else{

                if(empty($limit)) $limit = 100;
                if(empty($offset)) $offset = 0;

                $query = $this->Cache_model->read_all($offset, $limit, $user_id);

            }

            // $query may return 0 as count contents
            // we really should be using exceptions, not booleans here
            if($query !== false){

                $content = $query;
                $code = 'success';

            }else{

                $this->auth_response->setStatusCode(404);
                $content = current($this->Cache_model->get_errors());
                $code = key($this->Cache_model->get_errors());

            }

        }

        $this->auth_response->sendHeaders();
        
        $output = array(
            'content'   => $content,
            'code'      => $code,
        );
        
        Template::compose(false, $output, 'json');

    }

    /**
     * Shows one snapshot data, in this case, it actually acquires the snapshot itself, not the snapshot metadata. This is not a good architecture. In the future, this data should be embedded in the snapshot metadata. But oh well, Codeigniter sucks balls.
     */
    public function show($id){

        if(!$this->user->authorized()){

            $this->auth_response->setStatusCode(401);
            $content = 'Not authorized to use SnapSearch.';
            $code = 'error';

        }else{

            if($this->user->authorized([
                'roles' => 'admin'
            ])){

                $query = $this->Cache_model->read($id);

            }else{

                $query = $this->Cache_model->read($id, $this->user['id']);
            
            }

            if($query){

                $content = $query;
                $code = 'success';

            }else{

                $this->auth_response->setStatusCode(404);
                $content = current($this->Cache_model->get_errors());
                $code = key($this->Cache_model->get_errors());

            }

        }

        $this->auth_response->sendHeaders();
        
        $output = array(
            'content'   => $content,
            'code'      => $code,
        );
        
        Template::compose(false, $output, 'json');

    }

    public function delete($id){

        if(!$this->user->authorized()){

            $this->auth_response->setStatusCode(401);
            $content = 'Not authorized to use SnapSearch.';
            $code = 'error';

        }else{

            if($this->user->authorized([
                'roles' => 'admin'
            ])){

                $query = $this->Cache_model->delete($id);

            }else{

                $query = $this->Cache_model->delete($id, $this->user['id']);
            
            }

            if($query){

                $content = $id;
                $code = 'success';

            }else{

                $this->auth_response->setStatusCode(404);
                $content = current($this->Cache_model->get_errors());
                $code = key($this->Cache_model->get_errors());

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