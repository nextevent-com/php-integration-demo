<?php

return array(
  // enter the base URL where this app runs at
  'BASE_URL' => '/',

  // the hostname of the NextEvent app to interacto with
  'APP_URL' => 'https://demo-app.int.nextevent.com/',

  // the identifier of your NextEvent app to connect with
  'APP_ID' => 'nextevent_demo_app',

  // username used to authorize access the API
  'AUTH_USER' => 'my_new_demo_app',

  // passwort for the API authentication
  'AUTH_PASSWORD' => '123456',

  // the configuration ID for the booking widget
  'WIDGET_HASH' => '1502ad0250',

  // path to the log file (needs to be writeable for the webserver)
  'LOG_FILE' => __DIR__ . '/../log/demo.log',

  // enable debug mode. this will print errors and warnings to the screen
  'DEBUG' => true
);
