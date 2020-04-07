<?php

class Router {

    private static $routes = [];

    public static function registerRoute($entry) {
        // register route in routes object
        self::$routes[trim($entry->uri, '/')] = $entry;
    }

    public static function handleRequest($request, $siteVarName = 'SITE') {

        global $$siteVarName;

        if ($request == '') {

            // homepage
            require 'templates/index.php';

        } else if (array_key_exists($request, self::$routes)) {

            // template
            $entry = self::$routes[$request];
            require 'templates/' . $entry->template;

        } else {

            // 404
            if (file_exists('views/404.php')) {
                require 'templates/404.php';
            } else {
                echo '404 not found';
            }
        }
    }

}
