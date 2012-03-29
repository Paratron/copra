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
    /**
     * The login function takes the login credentials and returns the access token for restricted actions, or false.
     * @param string $user
     * @param string $password
     * @return string|FALSE
     */
    function login($user, $password){
        return md5(uniqid('')); //Dummy, whoot!
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
