<?php

namespace NextEvent\Demo;

/**
 * provide some utility functions
 */
class Util
{
  /**
   * place base_url in html
   * @return string
   */
  static function base_url()
  {
    return rtrim(Config::get('baseUrl'),'/') . '/';
  }

  /**
   * place debug notes in html
   *
   * @param $log string
   */
  static function debug($log)
  {
    if (Config::get('debug')) {
      echo '<div class="alert alert-info"><strong>DEBUG</strong> ' . $log . "</div>\n";
    }
  }

  static function error($log)
  {
    echo '<div class="alert alert-danger"><strong>ERROR</strong> ' . $log . "</div>\n";
  }

  static function info($log)
  {
    echo '<div class="alert alert-info"><strong>INFO</strong> ' . $log . "</div>\n";
  }

  static function warn($log)
  {
    echo '<div class="alert alert-warning"><strong>WARNING</strong> ' . $log . "</div>\n";
  }

  static function success($log)
  {
    echo '<div class="alert alert-success"><strong>SUCCESS</strong> ' . $log . "</div>\n";
  }


  /**
   * html header template
   *
   * @param $title string page title
   */
  static function html_header($page)
  {
    $baseUrl = self::base_url();
    ?>
      <!DOCTYPE html>
      <html>
      <head>
          <meta charset="UTF-8">
          <title>Demo Interkonnektion - NextEvent</title>
          <base href="<?= $baseUrl ?>" target="_self">
          <link rel="stylesheet" href="assets/css/font-awesome.min.css">
          <link rel="stylesheet" href="assets/css/bootstrap.min.css">
          <link rel="stylesheet" href="assets/css/bootstrap-nav-wizard.css">
          <link rel="stylesheet" href="assets/css/style.css">
      </head>
      <body>

      <div class="container">
      <h1>Demo Interkonnektion - NextEvent</h1>
      <ul class="nav nav-wizard">
        <li class="<?php if ($page == 'overview') echo 'active' ?>">
          <a href="index.php" target="_self"><i class="fa fa-home"></i> &Uuml;bersicht</a>
        </li>
        <li class="<?php if ($page == 'events') echo 'active' ?>">
          <a href="event_list.php" target="_self"><i class="fa fa-bullhorn"></i> Event Auflistung</a>
        </li>
        <li class="<?php if ($page == 'booking') echo 'active' ?> context-nextevent">
          <a href="widget_embed.php" target="_self"><i class="fa fa-ticket"></i> Widget Buchung</a>
        </li>
        <li class="<?php if ($page == 'checkout') echo 'active' ?>">
          <a href="checkout.php" target="_self"><i class="fa fa-shopping-basket"></i> Warenkorb</a>
        </li>
        <li class="<?php if ($page == 'payment') echo 'active' ?>">
          <a href="payment.php" target="_self"><i class="fa fa-money"></i> Bezahlung</a>
        </li>
        <li class="<?php if ($page == 'documents') echo 'active' ?>">
          <a href="documents.php" target="_self"><i class="fa fa-ticket"></i> Tickets</a>
        </li>
      </ul>

      <div class="content">
    <?php
  }

  /**
   * html footer template
   */
  static function html_footer()
  {
    ?>
      </div>
      </div>

      <script src="assets/js/widgetapi.js"></script>
      <script src="assets/js/script.js"></script>

      </body>
      </html><?php
  }
}
