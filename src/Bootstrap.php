<?php

namespace NextEvent\Demo;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NextEvent\PHPSDK\Client;

/**
 * Class Bootstrap
 *
 * Can be used on each page for initializing
 *
 * @package NextEvent\Demo
 */
class Bootstrap
{
  /**
   * Initialize Client with parameters from configuration
   *
   * @return Client
   */
  public static function getClient()
  {
    // provide logger instance to SDK
    $log = new Logger('sdk');
    if (!realpath(Config::get('logFile'))) {
      Util::error('Log file access permission denied');
    } else {
      // log to file
      $streamHandler = new StreamHandler(Config::get('logFile'), Config::get('debug') ? Logger::DEBUG : Logger::INFO);
      $log->pushHandler($streamHandler);
    }

    $options = [
      'appId' => Config::get('appId'),
      'appUrl' => Config::get('appUrl'),
      'authUsername' => Config::get('authUsername'),
      'authPassword' => Config::get('authPassword'),
      'env' => Config::get('env'),
      'logger' => $log
    ];

    return new Client($options);
  }


  /**
   * Initialize Logger for the Demoapp
   *
   * @return Logger
   */
  public static function getLogger()
  {
    $log = new Logger('demoapp');
    if (!realpath(Config::get('logFile'))) {
      Util::error('Log file access permission denied');
    } else {
      // log to file
      $streamHandler = new StreamHandler(Config::get('logFile'), Config::get('debug') ? Logger::DEBUG : Logger::INFO);
      $log->pushHandler($streamHandler);
    }
    return $log;
  }
}

// this demo app requires session support so start it in case session.auto_start=Off
if (!session_id()) {
  session_start();
}

// enable error reporting on the screen if debug mode is set
if (Config::get('debug')) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

