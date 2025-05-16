<?php
declare(strict_types=1);
namespace em_rest_api_cpt\init_plugin\Classes;


if (!class_exists('Make_Endpoint;')) {
    class Make_Endpoint
    {


    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data , $args );


       add_action( 'rest_api_init', [ $this, 'add_callback_url_endpoint']  );
    }


    public function add_callback_url_endpoint(){
        register_rest_route(
            'esmond-api/v1/', // Namespace
            'receive-callback', // Endpoint
            array(
                'methods'  => 'POST',
                'callback' => 'receive_callback'
            )
        );
    }


    public function receive_callback( $request_data ) {
        $data = array();
        
        $parameters = $request_data->get_params();
        
        $name     = $parameters['name'];
        $password = $parameters['password'];
        
        if ( isset($name) && isset($password) ) {
            
            $data['status'] = 'OK';
        
            $data['received_data'] = array(
                'name'     => $name,
                'password' => $password,
            );
            
            $data['message'] = 'You have reached the server';
            
        } else {
            
            $data['status'] = 'Failed';
            $data['message'] = 'Parameters Missing!';
            
        }
        
        return $data;
    }
        
    
} // Closing bracket for class

}