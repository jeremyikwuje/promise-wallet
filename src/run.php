<?php declare(strict_types=1);

require '../vendor/autoload.php';

use \Curl\Curl;

error_reporting(E_ALL);

// Load enviroment variables
$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

require 'functions.php'; // Load app functions

// allow cors
_allowCors();

// Register the error handler
$whoops = new \Whoops\Run;
if ( getenv('ENV') == 'local') {
    $whoops->pushHandler( new \Whoops\Handler\PrettyPageHandler);
} else {
    $whoops->pushHandler(function($e) {
        Controller::errorResponse('Internal Server Error', 500);
    });
}
$whoops->Register();

spl_autoload_register( function($className) {
	$controllers = 'controllers/' . $className . '.php';
    $models = 'models/' . $className . '.php';
    $helpers = 'helpers/' . $className . '.php';

	if ( file_exists( __DIR__ . '/../src/' . $controllers) ) {
		include_once $controllers;
	}
    if ( file_exists( __DIR__ . '/../src/' . $models) ) {
		include_once $models;
	}
    if ( file_exists( __DIR__ . '/../src/' . $helpers) ) {
		include_once $helpers;
	}
});

require_once 'routes.php'; // Load app functions