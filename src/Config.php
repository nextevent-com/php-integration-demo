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

      // set default DateTime format for date and time
      if (!isset(self::$configuration['dateTimeFormat'])) {
        self::$configuration['dateTimeFormat'] = 'D, d M Y H:i';
      }

      // set default DateTime format for date only
      if (!isset(self::$configuration['dateFormat'])) {
        self::$configuration['dateFormat'] = 'D, d M Y';
      }

      // provide configured user language as Env variable
      if (isset($_SESSION['locale'])) {
        Env::setVar('locale', $_SESSION['locale']);
      }
    }
  }

  static function get($key)
  {
    self::init();
    return isset(self::$configuration[$key]) ? self::$configuration[$key] : null;
  }
}
