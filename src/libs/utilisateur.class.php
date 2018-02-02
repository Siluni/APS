<?php

/**
 * Autorisation de connexion (API, ACS, ...)
 * @TODO Construction d'une BDD users a faire
 * */
class Utilisateur{

    /**
     * Basic auth
     * @param String              $username                Login
     * @param String              $password                Password
     * @return                                             Boolean
     * */
    public static function authenticate($username, $password){
        if(!self::check_user($username, $password)){
            header('WWW-Authenticate: Basic realm="aps2.wibox.fr"');
            header('HTTP/1.1 401 Unauthorized');
            exit();
        }
    }

    public static function authenticate_digest($username, $password){
        if(!self::check_user($username, $password)){
            header('WWW-Authenticate: Basic realm="aps2.wibox.fr"');
            header('HTTP/1.1 401 Unauthorized');
            exit();
        }
    }

    /**
     * Validation des utilisateurs autorises
     * @param String              $username                Login
     * @param String              $password                Password
     * @return                                             Boolean
     * */
    private static function check_user($username, $password){
        return true; // TODO
        return (isset($_login[$username]) && $_login[$username] == $password);
    }

}