<?php

$router->get('/', 'HomeController@index');

$router->get('/login', 'LoginController@showForm');
$router->post('/login', 'LoginController@handleLogin');

$router->get('/register', 'RegisterController@showForm');
$router->post('/register', 'RegisterController@handleRegistration');

$router->get('/logout', 'LogoutController@logout');

// Admin dashboard and command routes
$router->get('/admin', 'AdminController@index');
$router->post('/admin/broadcast',  'AdminController@broadcast' );

// Room view
$router->get('/room', 'RoomController@showRoom');

// SSE endpoint
$router->get('/sse', function() {
    require_once __DIR__ . '/../public/sse.php';
});
