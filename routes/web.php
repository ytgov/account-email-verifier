<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/start', [
  'as'         => 'default',
  'uses'       => 'DefaultController@start',
  'middleware' => ['jwt']
]);
$router->get('/', [
  'as'         => 'default',
  'uses'       => 'DefaultController@show',
  'middleware' => ['jwt']
]);
$router->post('/', [
  'uses'       => 'DefaultController@resend',
  'middleware' => ['jwt']
]);
$router->get('/about', [
  'as'         => 'missing_info',  
  'uses'       => 'DefaultController@missing_info',
]);
$router->get('/verification/submitted', [
  'uses'       => 'VerificationController@submitted',
]);
