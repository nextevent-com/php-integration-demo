<?php
/*
 * Page controller demonstrating the payment process of a NextEvent order
 *
 * This file is part of the NextEvent integration demo site and only serves as an example.
 * Please do not use in production.
 *
 * @ 2018 NextEvent AG - nextevent.com
 */

require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\APIResponseException;

$client = Bootstrap::getClient();

Util::htmlHeader('payment');

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
  $order = $client->getOrder($orderId);
  foreach ($order->getOrderItems() as $item) {
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

  // read stored customer data
  $customer = $order->getPayment()['customer'] + ['firstname' => '', 'lastname' => '', 'email' => ''];
} catch (APIResponseException $ex) {
  if ($ex->getCode() === 404) {
    // this is the case, when the payment step is visited without any item in basket
    Util::info('Your basket is empty');
  } else {
    Util::logException($ex);
    Util::error($ex->getCode().' could not authorize order: '.$ex->getMessage());
  }
  return Util::htmlFooter();  // terminate script
} catch (Exception $ex) {
  Util::logException($ex);
  Util::error($ex->getCode().' could not authorize order: '.$ex->getMessage());
  return Util::htmlFooter();  // terminate script
}

?>

  <h3>Your order</h3>

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

  <div class="well">
    Total <?= sprintf('%s %0.02f', $totalCurrency, $totalPrice) ?>
  </div>

  <!-- submit form to server.php for processing -->
  <form action="server.php?settle_payment" method="post" class="form-horizontal">
    <div class="form-group">
      <label for="customerFirstName" class="col-sm-2 control-label">First name</label>
      <div class="col-sm-10">
        <input type="text" class="form-control" id="customerFirstName" name="firstname" value="<?= htmlentities($customer['firstname']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="customerLastName" class="col-sm-2 control-label">Last name</label>
      <div class="col-sm-10">
        <input type="text" class="form-control" id="customerLastName" name="lastname" value="<?= htmlentities($customer['lastname']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="customerEmail" class="col-sm-2 control-label">E-mail address</label>
      <div class="col-sm-10">
        <input type="email" class="form-control" id="customerEmail" name="email" value="<?= htmlentities($customer['email']) ?>">
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-12 text-center">
        <a href="server.php?abort_payment" class="btn btn-danger">Cancel</a>
        <button type="submit" class="btn btn-primary">Confirm payment</button>
      </div>
    </div>
  </form>

<?php

Util::htmlFooter();
