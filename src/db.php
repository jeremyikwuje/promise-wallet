<?php 
 
// Using Medoo namespace
use Medoo\Medoo;
 
$db = new Medoo([
	// required
	'database_type' => 'mysql',
	'database_name' => getenv('DB_NAME'),
	'server' => getenv('DB_HOST'),
	'username' => getenv('DB_USER'),
	'password' => getenv('DB_PASS'),
]);

return $db;
