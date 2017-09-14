<?php

return array(
  // enter the base URL where this app runs at
  'baseUrl' => '/',

  // the hostname of the NextEvent app to interacto with
  'appUrl' => 'https://demo-app.int.nextevent.com/',

  // the identifier of your NextEvent app to connect with
  'appId' => 'nextevent_demo_app',

  // username used to authorize access the API
  'authUsername' => 'my_new_demo_app',

  // passwort for the API authentication
  'authPassword' => '123456',

  // the configuration ID for the booking widget
  'widgetHash' => '1502ad0250',

  // which NextEvent environment to use: 'PROD', 'INT' or 'TEST'
  'env' => 'INT',

  // format to render date/time values. See http://www.php.net/manual/en/function.date.php
  'dateTimeFormat' => 'd.m.Y H:i',

  // format to render date values. See http://www.php.net/manual/en/function.date.php
  'dateFormat' => 'd.m.Y',

  // path to the log file (needs to be writeable for the webserver)
  'logFile' => __DIR__ . '/../log/demo.log',

  // enable debug mode. this will print errors and warnings to the screen
  'debug' => true
);
