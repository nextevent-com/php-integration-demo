<?php

namespace NextEvent\Demo;
use NextEvent\PHPSDK\Client;
use NextEvent\PHPSDK\Exception\BasketEmptyException;

/**
 * provide some utility functions
 */
class Util
{
  /**
   * Getter for the baseUrl from config
   *
   * @return string
   */
  static function baseUrl()
  {
    return rtrim(Config::get('baseUrl'),'/') . '/';
  }

  /**
   * Place debug notes in html
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
   * Print HTML page header template
   *
   * @param string $page The active page
   */
  static function htmlHeader($page)
  {
    ?>
      <!DOCTYPE html>
      <html>
      <head>
          <meta charset="UTF-8">
          <title><?= ucfirst($page) ?> - NextEvent Integration Demo</title>
          <base href="<?= self::baseUrl() ?>" target="_self">
          <link rel="stylesheet" href="assets/css/font-awesome.min.css">
          <link rel="stylesheet" href="assets/css/bootstrap.min.css">
          <link rel="stylesheet" href="assets/css/bootstrap-nav-wizard.css">
          <link rel="stylesheet" href="assets/css/style.css">
      </head>
      <body>

      <div class="container">
      <h1>NextEvent Integration Demo</h1>
      <ul class="nav nav-wizard pull-left">
        <li class="<?php if ($page == 'overview') echo 'active' ?>">
          <a href="index.php" target="_self"><i class="fa fa-home"></i> Overview</a>
        </li>
        <li class="<?php if ($page == 'events') echo 'active' ?>">
          <a href="events.php" target="_self"><i class="fa fa-bullhorn"></i> Event listing</a>
        </li>
        <li class="<?php if ($page == 'booking') echo 'active' ?> context-nextevent">
          <a href="embed.php" target="_self"><i class="fa fa-ticket"></i> Booking widget</a>
        </li>
        <li class="<?php if ($page == 'checkout') echo 'active' ?>">
          <a href="checkout.php" target="_self"><i class="fa fa-shopping-basket"></i> Checkout</a>
        </li>
        <li class="<?php if ($page == 'payment') echo 'active' ?>">
          <a href="payment.php" target="_self"><i class="fa fa-money"></i> Payment</a>
        </li>
        <li class="<?php if ($page == 'documents') echo 'active' ?>">
          <a href="documents.php" target="_self"><i class="fa fa-ticket"></i> Tickets</a>
        </li>
      </ul>
      <ul class="nav nav-wizard pull-right">
        <li class="<?php if ($page == 'admin') echo 'active' ?>">
          <a href="admin.php" target="_self"><i class="fa fa-list"></i> Admin</a>
        </li>
      </ul>

      <br style="clear:both">

      <div class="content">
    <?php
  }

  /**
   * Print the HTML page footer template
   */
  static function htmlFooter()
  {
    ?>
      </div>
      </div>

      <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
      <script src="assets/js/widgetapi.js"></script>
      <script src="assets/js/script.js"></script>

      </body>
      </html><?php
  }


  /**
   * Fetch and Render the basket as HTML
   *
   * @param Client $client
   * @param int $orderId
   * @return string
   */
  static function renderBasket($client, $orderId)
  {
    ob_start();

    echo '<h3>Basket</h3>';

    $orderItems = [];
    $totalCurrency = 'CHF';
    try {
      // fetch the basket items for listing
      foreach ($client->getBasket($orderId)->getBasketItems() as $item) {
        if ($item->isDeleted()) {
          continue;
        }
        $price = $item->getPrice();
        $key = $price->getId();

        // group items by price ID
        if (!isset($orderItems[$key])) {
          $orderItems[$key] = (object)[
            'event' => $item->getEventTitle(),
            'description' => $item->getDescription(),
            'price' => $price->getPrice(),
            'items' => 0,
          ];
        }
        $orderItems[$key]->items++;
      }
    } catch (BasketEmptyException $ex) {
      // Util::info('Your basket is empty');
    } catch (\Exception $ex) {
      Util::error($ex->getMessage());
      Util::logException($ex);
    }

    ?>
    <?php if (count($orderItems)) { ?>
    <table class="table">
      <thead>
        <tr>
          <th>Event</th>
          <th>Ticket</th>
          <th>Items</th>
          <th class="text-right" width="15%">Total <?= $totalCurrency ?></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($orderItems as $orderItem): ?>
        <tr>
          <td><?= htmlspecialchars($orderItem->event) ?></td>
          <td><?= htmlspecialchars($orderItem->description) ?></td>
          <td><?= htmlspecialchars($orderItem->items) ?></td>
          <td class="text-right"><?= sprintf('%0.02f', $orderItem->price * $orderItem->items) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php } else {
      Util::info('Your basket is empty');
    } ?>

    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }


  /**
   * Forwards the given exception to the logger
   *
   * @param \Exception $ex
   */
  static function logException(\Exception $ex)
  {
    Bootstrap::getLogger()->error('Exception occurred: ' . $ex->getMessage());
  }


  /**
   * Renders the given data as application/json response to the client
   * 
   * @param mixed $data The response data to send (unually an object or array)
   * @param boolean $terminate Terminates the PHP script if set to true
   */
  static function jsonResponse($data, $terminate = true)
  {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);

    if ($terminate) {
      exit;
    }
  }
}
