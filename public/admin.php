<?php
/*
 * Page controller showing an example admin panel for NextEvent orders
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
use NextEvent\PHPSDK\Exception\OrderNotFoundException;

$client = Bootstrap::getClient();
$orderId = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : 0;
$rebookError = null;

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
    $rebookError = $ex->getCode() . ' could not rebook order: ' . $ex->getMessage();
  }
}

Util::htmlHeader('admin');

// print rebooking error message
if ($rebookError) {
  Util::error($rebookError);
}

?>

<h3>Recent Orders</h3>

<?php
  // fetch the 10 most recent orders
  $orders = $client->getOrders([
    'page_size' => 10,
    'order' => 'desc',
  ]);
  if (count($orders)) {
    // only iterate the current page
    $orders->setAutofetch(false);
?>
  <table class="table">
    <thead>
    <tr>
      <th width="15%">#</th>
      <th>Order Date</th>
      <th width="15%">State</th>
      <th width="15%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $ordr): ?>
    <tr class="<?= $ordr->getId() == $orderId ? 'active' : '' ?>">
      <td><a href="admin.php?order_id=<?= $ordr->getId() ?>">B<?= $ordr->getId() ?></a></td>
      <td><?= htmlspecialchars($ordr->getOrderDate() ? $ordr->getOrderDate()->format('Y/m/d H:i') : '--') ?></td>
      <td><?= htmlspecialchars($ordr->getState()) ?></td>
      <td class="actions">
        <a href="admin.php?order_id=<?= $ordr->getId() ?>">Details</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php

} else {
  Util::info('No recent orders returned');
}

// ********************************

// get details about the selected order (from order_id query parameter)
$order = null;
if ($orderId) {
  try {
    $order = $client->getOrder($orderId, ['tickets', 'document', 'items', 'user']);
  } catch (OrderNotFoundException $ex) {
    Util::warn("Order with ID $orderId does not exist");
  } catch (APIResponseException $ex) {
    Util::error($ex->getMessage());
    Util::logException($ex);
  }
}

if ($order && $order->getState() !== 'basket') {
  $orderData = $order->toArray();
  $customerNames = ['--'];
  $customerEmail = null;
  $sellerName = '--';

  // check if customer data is available
  if (isset($orderData['payment']['customer'])) {
    $customerNames = array_filter([$orderData['payment']['customer']['firstname'], $orderData['payment']['customer']['lastname']]);
    $customerEmail = $orderData['payment']['customer']['email'];
  }
  if (isset($orderData['user']['name'])) {
    $sellerName = $orderData['user']['name'];
  }
?>
  <hr>
  <h3>Order <?= htmlspecialchars(sprintf('#%s (%s)', $order->getId(), $order->getState())) ?></h3>

  <p>Order date: <?= date('Y/m/d H:i:s', strtotime($orderData['orderdate'])) ?>

  <p>Customer: <?= htmlspecialchars(join(' ', $customerNames)) ?> <?= htmlspecialchars($customerEmail) ?></p>

  <p>Seller: <?= htmlspecialchars($sellerName) ?></p>

  <!-- order items listing (ungrouped) -->
  <table class="table">
    <thead>
    <tr>
      <th width="35%">Event</th>
      <th width="50%">Title</th>
      <th width="15%" class="currency">Price</th>
    </tr>
    </thead>
    <tbody>
      <?php foreach ($order->getOrderItems() as $orderItem) {
        /** @var \NextEvent\PHPSDK\Model\OrderItem $orderItem */
        $category = $orderItem->getCategory();
        $price = $orderItem->getPrice();
        $itemAdditions = [];
        // add additional information
        if ($orderItem->hasInfo()) {
          $itemAdditions[] = '<div class="item-addition info">' . $orderItem->getInfo() . '</div>';
        }
        // list all child items if available
        foreach ($orderItem->getChildren() as $child) {
          $additionPrice = $child->getPrice()->getPrice() ? sprintf(' (%0.2f)', $child->getPrice()->getPrice()) : '';
          $itemAdditions[] = '<div class="item-addition">' . $child->getDescription() . $additionPrice . '</div>';
        }
      ?>
      <tr class="<?= $orderItem->getItems() < 0 ? 'deleted' : '' ?>">
        <td><?= htmlspecialchars($category->getEventTitle()) ?></td>
        <td><?= htmlspecialchars($orderItem->getDescription()) . join('', $itemAdditions) ?></td>
        <td class="currency"><?= sprintf('%s %0.02f', $price->getCurrency(), $price->getPrice()) ?></td>
      </tr>
      <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <td colspan="2"><strong>Total</strong></td>
      <td class="currency"><strong><?= sprintf('%s %0.02f', $order->getCurrency(), $order->getTotal()) ?></strong></td>
    </tr>
  </table>

  <?php if ($order->hasTickets()): ?>
  <!-- list all tickets if available -->
  <h4>Ticket Codes</h4>
  <ul>
  <?php
    foreach ($order->getTickets(true) as $ticket) {
      // check if ticket documents are available
      if ($ticket->hasDocument()) {
        printf('<li><a href="%s">T%s</a></li>', $ticket->getDocument()->getDownloadUrl(), $ticket->getCode());
      } else {
        // print the state of the ticket, too (e.g. revoked)
        printf('<li>T%s (%s)</li>', $ticket->getCode(), $ticket->getState());
      }
    }
  ?>
  </ul>
  <?php endif; ?>

  <?php if (count($order->getDocuments()) > 0): ?>
  <!-- list all downloadable documents -->
  <h4>Documents</h4>
  <ul>
  <?php
    foreach ($order->getDocuments() as $doc) {
      printf('<li><a href="%s">%s (%s)</a></li>', $doc->getDownloadUrl(), $doc->getTitle(), strtoupper($doc->get('filetype')));
    }
  ?>
  </ul>
  <?php endif; ?>

  <div class="actionbuttons">
    <a href="admin.php?rebook=1&amp;order_id=<?= $orderId ?>" class="btn btn-primary">Start rebooking</a>
    <a href="cancel.php?order_id=<?= $orderId ?>" class="btn btn-danger">Cancel order</a>
  </div>
  <br>
<?php }  // end if

Util::htmlFooter();
