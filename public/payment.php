<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\APIResponseException;

$client = Bootstrap::getClient();


Util::html_header('payment');

if (!isset($_SESSION)) {
  session_start();
}

$orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
$totalPrice = '--.--';
$totalCurrency = 'CHF';
$orderItems = [];

try {
  if (isset($_SESSION['nexteventPaymentAuthorization'])) {
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);
  } else {
    $payment = $client->authorizeOrder($orderId);
  }

  $totalPrice = $payment->getAmount();
  $totalCurrency = $payment->getCurrency();
  $_SESSION['nexteventPaymentAuthorization'] = serialize($payment);

  // fetch the final order items for listing
  foreach ($client->getOrder($orderId)->getOrderItems() as $item) {
    $price = $item->getPrice();
    $key = $price->getId();
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
} catch (APIResponseException $ex) {
  if ($ex->getCode() === 404) {
    // this is the case, when the payment step is visited without any item in basket
    Util::info('Keine Items im Warenkorb');
    Util::html_footer();
    return;
  }
  throw $ex;
} catch (Exception $ex) {
  Util::logException($ex);
  Util::error($ex->getCode().' could not authorize order: '.$ex->getMessage());
  Util::html_footer();
  return;
}


?>

  <h3>Ihre Rechnung</h3>

  <table class="table">
  <thead>
    <tr>
      <th>Event</th>
      <th>Ticket</th>
      <th>Anzahl</th>
      <th class="text-right" width="15%">Total <?= $totalCurrency ?></th>
    </tr>
  </thead>
  <tbody>
<?php foreach ($orderItems as $orderItem): ?>
    <tr>
      <td><?= $orderItem->event ?></td>
      <td><?= $orderItem->description ?></td>
      <td><?= $orderItem->items ?></td>
      <td class="text-right"><?= sprintf('%0.02f', $orderItem->price * $orderItem->items) ?></td>
    </tr>
<?php endforeach; ?>
  </tbody>
  </table>

  <div class="well" style="display:block;">Total <?= sprintf('%s %0.02f', $totalCurrency, $totalPrice) ?></div>


  <form action="server.php?settle_payment" method="post" class="form-horizontal">
    <div class="form-group">
      <label for="customerName" class="col-sm-2 control-label">Name</label>
      <div class="col-sm-10">
        <input type="text" class="form-control" id="customerName" name="name">
      </div>
    </div>
    <div class="form-group">
      <label for="customerName" class="col-sm-2 control-label">Email</label>
      <div class="col-sm-10">
        <input type="email" class="form-control" id="customerEmail"
               name="email">
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-12 text-center">
        <a href="server.php?abort_payment" class="btn btn-danger">Abbrechen</a>
        <button type="submit" class="btn btn-primary">Zahlung bestätigen</button>
      </div>
    </div>
  </form>




<?php

Util::html_footer();
