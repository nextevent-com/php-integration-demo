<?php
/*
 * Page controller showing an example confirm page after completing a NextEvent order
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
$orderId = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] :
          (isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0);

// start rebooking of the given order
if ($orderId && isset($_GET['rebook'])) {
  try {
    $basket = $client->rebookOrder($orderId);
    // set rebooking basket as current basket
    $_SESSION['nexteventOrderId'] = $basket->getId();
    header('Location: checkout.php');
    exit;
  } catch (APIResponseException $ex) {
    Util::logException($ex);
    Util::error($ex->getCode() . ' could not rebook order: ' . $ex->getMessage());
  }
}

Util::htmlHeader('documents');


// get details about the order
if ($orderId && ($order = $client->getOrder($orderId))) {
  $orderData = $order->toArray();
  $customerNames = ['--'];
  $customerEmail = null;

  // NOTE: order completion is an asynchronous process in NextEvent and therefore
  // the Order instance fetched with Client::getOrder() may not yet have the completed flag set.
  // It's recommended to pull the order details in a background process until it has been completed.
  if ($order->isComplete() && isset($orderData['payment']['customer'])) {
    $customerNames = array_filter([$orderData['payment']['customer']['firstname'], $orderData['payment']['customer']['lastname']]);
    $customerEmail = $orderData['payment']['customer']['email'];
  }
?>
  <h3>Order #<?= htmlspecialchars($orderId) ?> (<?= htmlspecialchars($order ? $order->getState() : 'in process') ?>)</h3>

  <div class="load-msg">
    <div class="panel-body">
      <i class="fa fa-spinner fa-spin"></i>
      Tickets are being issued...
    </div>
  </div>

  <div class="ticket-list" style="display: none"></div>

<?php if ($order && $order->isComplete()): ?>
  <p>Order date: <?= date('d.m.Y H:i', strtotime($orderData['orderdate'])) ?>

  <p>Customer: <?= htmlspecialchars(join(' ', $customerNames)) ?> <?= htmlspecialchars($customerEmail) ?></p>

  <div class="actionbuttons">
    <a href="admin.php?order_id=<?= $orderId ?>" class="btn btn-primary">Further actions...</a>
  </div>
<?php endif; ?>

  <script>
    // start polling for tickets to become available for download
    window.pollTickets = true;
    // tell client to reload the page once tickets become available
    window.refreshView = <?= ($order && $order->isComplete()) ? 'false' : 'true' ?>;
  </script>

<?php
} else {
  Util::info('No order reference found in your session');
}

Util::htmlFooter();
