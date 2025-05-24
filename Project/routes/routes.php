<?php

$router->get('/', 'HomeController@index');

$router->get('/login', 'LoginController@showForm');
$router->post('/login', 'LoginController@handleLogin');

$router->get('/register', 'RegisterController@showForm');
$router->post('/register', 'RegisterController@handleRegistration');

// $router->get('/emotion', 'EmotionController@showInterface');
// $router->post('/send-command', 'EmotionController@sendCommand');

// // Add route for admin panel
// $router->get('/admin', 'AdminController@showPanel');

// // Add route for sending reaction commands
// $router->post('/reaction/send', 'ReactionController@sendCommand');
// // Add route for simulating reactions
// $router->post('/reaction/simulate', 'ReactionController@simulateReaction');
// // Add route for event creation
// $router->post('/event/create', 'EventController@createEvent');
// // Add route for generating quick-join link
// $router->get('/event/join-link/{eventId}', 'EventController@generateJoinLink');
