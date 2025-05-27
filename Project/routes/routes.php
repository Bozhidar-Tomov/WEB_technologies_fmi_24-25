<?php

$router->get('/', 'HomeController@index');

$router->get('/login', 'LoginController@showForm');
$router->post('/login', 'LoginController@handleLogin');

$router->get('/register', 'RegisterController@showForm');
$router->post('/register', 'RegisterController@handleRegistration');

$router->get('/logout', 'LogoutController@logout');

// Admin dashboard and command routes
$router->get('/admin', 'AdminController@index');
$router->post('/admin/send-command', 'AdminController@sendCommand');

// Reaction routes
$router->post('/reaction/send', 'ReactionController@sendCommand');
$router->post('/reaction/simulate', 'ReactionController@simulateReaction');

// Event routes
$router->post('/event/create', 'EventController@createEvent');
$router->get('/event/join-link/{eventId}', 'EventController@generateJoinLink');

// Room view
$router->get('/room', 'RoomController@showRoom');
