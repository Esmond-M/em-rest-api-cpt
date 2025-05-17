<?php
declare(strict_types=1);
namespace em_rest_api_cpt\init_plugin\Classes;

if (!class_exists('Make_Endpoint;')) {
    class Make_Endpoint
    {

        public function __construct()
        {

        add_action( 'rest_api_init', [ $this, 'add_callback_url_endpoint']  );
        add_action( 'init',  [ $this, 'add_callback_url_endpoint']   );

        }

        public function add_callback_url_endpoint(){
            register_rest_route(
                'esmond-api/v1', // Namespace
                '/receive', // Endpoint
                array(
                    'methods'  => 'POST',
                    'callback' => [$this, 'receive_callback']
                )
            );
        }


        public function receive_callback( $request_data ) {
            $data = array();
                
            $parameters = $request_data->get_params();
            
            $name     = $parameters['name'];
            $password = $parameters['password'];
            $title    = $parameters['title'];
            $body     = $parameters['body'];
                
            if ( isset($name) && isset($password) ) {
                
                $userdata = get_user_by( 'login', $name );
                
                if ( $userdata ) {
                    
                    $wp_check_password_result = wp_check_password( $password, $userdata->user_pass, $userdata->ID );
                    
                    if ( $wp_check_password_result ) {
                        $data['status'] = 'OK';
                    
                        $data['received_data'] = array(
                            'name'     => $name,
                            'password' => $password,
                            //'data'     => $userdata
                        );
                        
                        $post_args = array(
                            'post_title'   => wp_strip_all_tags( $title ),
                            'post_content' => $body,
                            'post_status'  => 'publish',
                            'post_type'    => 'apidata'
                        );
                        
                        $post_var = wp_insert_post( $post_args );
                        
                        if( $post_var ) {
                            $data['message'] = 'Post was successful';
                        }
                        
                    } else {
                        $data['status'] = 'OK';
                        $data['message'] = 'You are not authenticated to login!';
                    }
                    
                    
                } else {
                    
                    $data['status'] = 'OK';
                    $data['message'] = 'The current user does not exist!';
                }
                
                
            } else {
                
                $data['status'] = 'Failed';
                $data['message'] = 'Parameters Missing!';
                
            }
            
            return $data;
        }
            
    } // Closing bracket for class

}
  new Make_Endpoint;
  