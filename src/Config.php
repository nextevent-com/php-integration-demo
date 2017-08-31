<?php

namespace NextEvent\Demo;

use NextEvent\PHPSDK\Util\Env;

define('CONFIG_FILE', __DIR__ . '/../config/config.php');

// make sure the server uses the correct time zone
date_default_timezone_set('Europe/Zurich');

class Config
{
  private static $configuration = null;

  private static function init()
  {
    if (self::$configuration === null) {
      self::$configuration = include CONFIG_FILE;
      if (!self::$configuration || !is_array(self::$configuration)) {
        die('configs are required! rename example.config.ph or test.config.php to /config/config.php');
      }

      // use INT environment by default
      if (empty(self::$configuration['env'])) {
        self::$configuration['env'] = 'INT';
      }
      Env::setEnv(self::$configuration['env']);

      // set default DateTime fromat
      if (!isset(self::$configuration['dateTimeFormat'])) {
        self::$configuration['dateTimeFormat'] = 'D, d M Y H:i';
      }
    }
  }

  static function get($key)
  {
    self::init();
    return isset(self::$configuration[$key]) ? self::$configuration[$key] : null;
  }
}

if (Config::get('debug')) {
  // error reporting
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

// provide configured user language as Env variable
if (!isset($_SESSION)) {
  session_start();
}
if (isset($_SESSION['locale'])) {
  Env::setVar('locale', $_SESSION['locale']);
}
