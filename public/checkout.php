<?php
/*
 * Page controller showing an example checkout page with the contents of the NextEvent basket
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
use NextEvent\PHPSDK\Exception\BasketEmptyException;
use NextEvent\PHPSDK\Exception\OrderItemNotFoundException;
use NextEvent\PHPSDK\Exception\OrderNotFoundException;

// get an instance of the NextEvent API client
$client = Bootstrap::getClient();
$cache = $client->getCache();

/**
 * Helper function to get the event title for the given basket item
 *
 * Uses the previously fetched and caches event list for lookup
 *
 * @param int $id The Event ID
 * @param array $items List of basket items for this event
 * @return string
 */
function getEventTitle($id, $items)
{
  // get cached events
  $cache = Bootstrap::getClient()->getCache();
  $eventList = $cache->get('event_titles');
  if (!is_array($eventList)) {
    $eventList = array();
  }
  if (isset($eventList[$id])) {
    return $eventList[$id];
  } else if (count($items)) {
    $item = $items[0];
    return $item->getCategory()->getEventTitle();
  }
  return null;
}


Util::htmlHeader('checkout');


$orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
$delete_order_item_id = isset($_GET['delete_order_item_id']) ? (int)$_GET['delete_order_item_id'] : 0;

/*
 * Delete a given order item from the basket
 * 
 * @see http://docs.nextevent.com/sdk/#remove-items-from-basket
 * @see http://docs.nextevent.com/sdk/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_deleteBasketItem
 */
if ($orderId && $delete_order_item_id) {
  try {
    $success = $client->deleteBasketItem($orderId, $delete_order_item_id);
    if ($success) {
      Util::success('Successfully deleted order_item_id ' . $delete_order_item_id);
    } else {
      Util::error('Failed to delete order_item_id ' . $delete_order_item_id);
    }
  } catch (APIResponseException $ex) {
    Util::error('Failed to delete order_item_id ' . $delete_order_item_id);
    Util::debug($ex->getMessage());
  }
}

/*
 * Clear the entire basket
 *
 * @see http://docs.nextevent.com/sdk/#delete-the-entire-basket
 * @see http://docs.nextevent.com/sdk/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_deleteBasket
 */
if (isset($_GET['delete_order'])) {
  try {
    // delete each basket separately
    $success = $client->deleteBasket($orderId);
    if ($success) {
      Util::success('Successfully deleted order ' . $orderId);
    } else {
      Util::error('Failed to delete order ' . $orderId);
    }
  } catch (APIResponseException $ex) {
    Util::error('Failed to delete order ' . $orderId);
  }

  // remove basket reference from our session
  unset($_SESSION['nexteventOrderId']);
}

/**
 * Extend basket expiration to N minutes from now
 *
 * @see http://docs.nextevent.com/sdk/#extend-basket-expiration
 * @see http://docs.nextevent.com/sdk/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_updateBasketExpiration
 */
if (!empty($_GET['extend']) && $orderId) {
  $client->updateBasketExpiration($orderId, intval($_GET['extend']));

  // redirect back to payment page
  header('Location: checkout.php');
  return;
}

// cancel payment authorization if order is already in payment process
if (isset($_SESSION['nexteventPaymentAuthorization'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    /* @var \NextEvent\PHPSDK\Model\Payment $payment */
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);
    unset($_SESSION['nexteventPaymentAuthorization']);

    $success = $client->abortPayment(
      $payment,
      'Customer has aborted payment'
    );

    if (!$success) {
      Util::error('There went something wrong with aborting the settlement');
    }

    sleep(2);  // wait for basket to be restored
  } catch (APIResponseException $exception) {
    Util::error('Failed to abort payment process: ' . $exception->getDescription());
  } catch (Exception $exception) {
    Util::error('There went something wrong with aborting the settlement');
    Util::debug($exception->getMessage());
  }
}

// fetch and aggregate basket data
$totalPrice = 0;
$totalCurrency = 'CHF';
$basketExpires = null;
$basket = null;

Util::debug('Current order id ' . $orderId);

try {
  $basket = $client->getBasket($orderId);
  $basketExpires = date_diff(new DateTime(), $basket->getExpires())->i;

  // if this is a rebooking order, store it in session for referencing it in embed.php
  if ($basket->isRebookOrder()) {
    $_SESSION['nexteventRebookingOrder'] = serialize($basket);
  } else {
    unset($_SESSION['nexteventRebookingOrder']);
  }

  $basketItems = $basket->getBasketItems();

  if (count($basketItems)) {
    // group basket items by event_id
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

      <h3><a href="embed.php?event_id=<?= $eventId ?>">
        <i class="fa fa-calendar"></i>
        <?= htmlspecialchars(getEventTitle($eventId, $items)) ?>
      </a></h3>
      <table class="table">
        <thead>
        <tr>
          <th width="10%">#</th>
          <th>Title</th>
          <th width="15%" class="currency">Price</th>
          <th width="15%">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $basketItem) {
          /** @var \NextEvent\PHPSDK\Model\BasketItem $basketItem */
          $category = $basketItem->getCategory();
          $price = $basketItem->getPrice();
          $totalCurrency = $price->getCurrency();
          if (!$basketItem->isDeleted()) {
            $totalPrice += $price->getPrice();
          }
          $eventName = $category->getEventTitle();
          $description = $basketItem->getDescription();
          $priceFormatted = sprintf('%s %0.02f', $price->getCurrency(), $price->getPrice());
          $itemAdditions = [];
          // append ticket code to listing (in rebooking case)
          if ($basketItem->hasTicketCode()) {
            $itemAdditions[] = ' <em class="ticket-code">(T' . $basketItem->getTicketCode() . ')</em>';
          }
          // add seat information
          if ($basketItem->hasSeat()) {
            $itemAdditions[] = '<div class="item-addition seat">' . $basketItem->getSeat()->getDisplayname() . '</div>';
          }
          // list all child items if available
          foreach ($basketItem->getChildren() as $child) {
            $additionPrice = $child->getPrice()->getPrice() ? sprintf(' (%0.2f)', $child->getPrice()->getPrice()) : '';
            $itemAdditions[] = '<div class="item-addition">' . $child->getDescription() . $additionPrice . '</div>';
          }
        ?>
          <tr class="<?= $basketItem->isDeleted() ? 'deleted' : '' ?>">
            <td><?= $basketItem->getId() ?></td>
            <td><?= htmlspecialchars($description) . join('', $itemAdditions) ?></td>
            <td class="currency"><?= $priceFormatted ?></td>
            <td class="actions">
              <a href="checkout.php?delete_order_item_id=<?= $basketItem->getId() ?>">Remove</a>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>

      <?php
    }

  }
} catch (OrderNotFoundException $ex) {
  Util::info('Your basket is empty');
} catch (BasketEmptyException $ex) {
  Util::info('Your basket is empty');
} catch (Exception $ex) {
  Util::error($ex->getMessage());
  Util::logException($ex);
}

// display rebooking/basket total
if ($basket && $basket->isRebookOrder()) {
  printf('<div class="well"><strong>Rebooking total: %s %0.02f</strong>', $totalCurrency, $totalPrice - $basket->getCancellationTotal());
} else {
  printf('<div class="well"><strong>Total %s %0.02f</strong>', $totalCurrency, $totalPrice);
}

print '<p class="pull-right">';

// display basket expiration time
if ($basketExpires > 0) {
  printf('Expires in %d minutes', $basketExpires);
} else if ($basketExpires !== null) {
  print('Is expired!');
}

if ($basketExpires !== null && $basketExpires < 20) {
  print('&nbsp; <a href="./checkout.php?extend=20" class="btn btn-default btn-xs" title="Increase to 20 minutes"><i class="fa fa-plus"></i></a>');
}

echo '</p></div>';

// checkout menu
?>

  <div class="">
    <div class="pull-right">
      <a href="checkout.php?delete_order" class="btn btn-danger">Clear basket</a>
      <a href="payment.php" class="btn btn-primary">Proceed to payment</a>
    </div>
  </div>
  <br>
<?php

Util::htmlFooter();
