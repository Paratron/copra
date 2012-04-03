<?php
/**
 * CopraAuth
 * ==========
 * The Copra Auth class handles user logins with username and password.
 * If a login happens, a token will be generated which can be used for restricted actions.
 * Please modify this class to your needs to communicate with your database for login operations.
 *
 * @author: Christian Engel <hello@wearekiss.com> 
 * @version: 1 11.03.12
 */

class CopraAuth {
    var $copra = NULL;

    /**
     * A reference to copra is passed in to enable you to use $copra->error().
     * @param $copra_reference
     */
    function __construct($copra_reference){
        $this->copra = $copra_reference;
    }

    /**
     * The login function takes the login credentials and returns the access token for restricted actions, or false.
     * @param string $user
     * @param string $password
     * @return string|FALSE
     */
    function login($user, $password){
        return array(
            'token' => md5(uniqid('')) //Dummy, whoot!
        );
    }

    /**
     * Checks if a token is valid and returns an array with permissions (strings), or false.
     * @param string $token
     * @return array|false
     */
    function validate_token($token){
        return array('generic'); //Dummy permission
    }
}
