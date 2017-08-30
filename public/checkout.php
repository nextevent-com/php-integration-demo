<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\OrderItemNotFoundException;
use NextEvent\PHPSDK\Exception\OrderNotFoundException;

$client = Bootstrap::getClient();
$cache = $client->getCache();

if (!isset($_SESSION)) {
  session_start();
}

function get_event_title($id)
{
  $cache = Bootstrap::getClient()->getCache();
  // get cached events
  $eventList = $cache->get('event_list');
  if (!is_array($eventList)) {
    $eventList = array();
  }
  if (isset($eventList[(int)$id])) {
    return $eventList[(int)$id];
  } else {
    return null;
  }
}


Util::html_header('checkout');


$orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
$delete_order_item_id = isset($_GET['delete_order_item_id']) ? (int)$_GET['delete_order_item_id'] : 0;

// delete order item
if ($orderId && $delete_order_item_id) {
  try {
    $success = $client->deleteBasketItem($orderId, $delete_order_item_id);
    if ($success) {
      Util::success('successfully deleted order_item_id ' . $delete_order_item_id);
    } else {
      Util::error('couldn\'t delete order_item_id ' . $delete_order_item_id);
    }
  } catch (OrderItemNotFoundException $ex) {
    Util::error('couldn\'t delete order_item_id ' . $delete_order_item_id);
    Util::debug($ex->getMessage());
  }
}

// empty baskets
if (isset($_GET['delete_orders'])) {
  try {
    // delete each basket separately
    $success = $client->deleteBasket($orderId);
    if ($success) {
      Util::success('successfully deleted order ' . $orderId);
    } else {
      Util::error('couldn\'t delete order ' . $orderId);
    }
  } catch (OrderNotFoundException $ex) {
    Util::error('couldn\'t delete order ' . $orderId);
  }

  unset($_SESSION['nexteventOrderId']);
}

// abort payment if one is occurring
if (isset($_SESSION['nexteventPaymentAuthorization'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    /* @var \NextEvent\PHPSDK\Model\Payment $payment */
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);
    unset($_SESSION['nexteventPaymentAuthorization']);

    $success = $client->abortPayment(
      $payment,
      'Kunde hat die Bezahlung abgebrochen'
    );

    if (!$success) {
      Util::error('There went something wrong with aborting the settlement');
    }

    sleep(2);  // wait for basket to be restored
  } catch (Exception $exception) {
    Util::error('There went something wrong with aborting the settlement');
    Util::debug($exception->getMessage());
  }
}

// load orders
$totalPrice = 0;
$totalCurrency = 'CHF';

Util::debug('current order id ' . $orderId);

try {
  $basket = $client->getBasket($orderId);
  $basketItems = $basket->getBasketItems();

  $interval = date_diff(new DateTime(), $basket->getExpires());
  echo 'Expires in ' . $interval->i . ' minutes';

  $cache->set('basket:' . $orderId, $basket->toArray());

  if (count($basketItems)) {

    // group by event_id
    $basketItemsByEvent = array();
    foreach ($basketItems as $basketItem) {
      $key = $basketItem->getEventId();
      if (!isset($basketItemsByEvent[$key])) {
        $basketItemsByEvent[$key] = array();
      }
      $basketItemsByEvent[$key][] = $basketItem;
    }

    foreach ($basketItemsByEvent as $eventId => $items) {
      ?>

      <h3><a href="widget_embed.php?event_id=<?php echo $eventId ?>"><i
              class="fa fa-calendar"></i> <?php echo htmlspecialchars(
            get_event_title($eventId)
          ) ?></a></h3>
      <table class="table">
        <thead>
        <tr>
          <th width="10%">#</th>
          <th>Titel</th>
          <th width="15%">Preis</th>
          <th width="15%">&nbsp;</th>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($items as $basketItem) {
          $category = $basketItem->getCategory();
          $price = $basketItem->getPrice();
          $totalPrice += $price->getPrice();
          $totalCurrency = $price->getCurrency();

          $eventName = $category->getEventTitle();
          $description = $basketItem->getDescription();
          $priceFormatted = sprintf('%s %0.02f', $price->getCurrency(), $price->getPrice());
            ?>
          <tr>
            <td><?php echo $basketItem->getId() ?></td>
            <td><?php echo htmlspecialchars($description) ?></td>
            <td><?php echo $priceFormatted ?></td>
            <td><a
                  href="checkout.php?delete_order_item_id=<?php echo $basketItem->getId() ?>">Löschen</a>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>

      <?php
    }

  }
} catch (\NextEvent\PHPSDK\Exception\OrderNotFoundException $ex) {
  Util::info('Keine Items im Warenkorb ' . $orderId);
} catch (Exception $ex) {
  Util::error($ex->getMessage());
}


echo '<div class="well">Total ' . sprintf('%s %0.02f', $totalCurrency, $totalPrice) . '</div>';

// checkout menu
?>

  <div class="">
    <div class="pull-right">
      <a href="checkout.php?delete_orders=true" class="btn btn-danger">Warenkorb
        leeren</a>
      <a href="payment.php" class="btn btn-primary">Jetzt bezahlen</a>
    </div>
  </div>
  <br>
<?php

Util::html_footer();
