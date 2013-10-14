<?php

if (!defined('WEBROOT')) {
    define('WEBROOT', __DIR__);
}

// Init autoloader
require '../system/application.php';
// Prepare app
$app = new Slim\Slim(array(
    'mode' => SERVER_MODE,
    'debug' => (SERVER_MODE == 'development') ? true : false,
    'log.enabled' => false,
    'cookies.path' => $config['base_url'],
    'cookies.domain' => isset($config['domain']) ? $config['domain'] : null,
    'cookies.secure' => (bool) $config['ssl'],
    'cookies.httponly' => true,
    'base_url' => $config['api_url']
  ));

// Add HTTP Authentication middleware
if(SERVER_MODE == 'test'){
    $app->add(new Middleware\HttpAuth("API Authentication", array('admin'=>'password')));
}elseif ($config['secure']) {
    $app->add(new Middleware\HttpAuth("API Authentication", $config['logins']));
}

// Include Routes
require_once(SYSTEM.DIRECTORY_SEPARATOR."routes.php");

// Run app
$app->run();