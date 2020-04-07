<?php

// YAML and .md parsing
include 'app/lib/includes.php';

// main app
include 'app/Router.php';
include 'app/Entry.php';
include 'app/Site.php';

// build the site object
$SITE = new Site('content');

// handle request
$request = trim($_SERVER['REQUEST_URI'], '/');
Router::handleRequest($request);