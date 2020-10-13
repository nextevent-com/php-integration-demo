<?php
/*
 * Page controller showing how to embed a NextEvent booking widget in your application
 *
 * This file is part of the NextEvent integration demo site and only serves as an example.
 * Please do not use in production.
 *
 * @ 2018 NextEvent AG - nextevent.com
 */

require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\Demo\Config;

// get an instance of the NextEvent API client
$client = Bootstrap::getClient();


Util::htmlHeader('booking');

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$embed_link = isset($_GET['url']) ? $_GET['url'] : null;

echo '<div class="context-nextevent">';

// fallback if no event ID is provided
if (!$event_id && !$embed_link) {
  Util::warn('No event_id provided! Use <code>?event_id=&lt;id&gt;</code> or <a href="events.php">select an event from the listing</a>');
  echo '</div>';
  return Util::htmlFooter();
}

// if this is a rebooking order, set $changeorder variable
$changeorder = null;
if (isset($_SESSION['nexteventRebookingOrder']) && isset($_SESSION['nexteventOrderId'])) {
  $basket = unserialize($_SESSION['nexteventRebookingOrder']);
  if ($basket->getId() === $_SESSION['nexteventOrderId']) {
    $changeorder = $basket;
  }
}

// user language selection
if (isset($_GET['locale'])) {
  // change Env locale for this session
  $_SESSION['locale'] = $_GET['locale'];
  Env::setVar('locale', $_GET['locale']);
}

$locale = Env::getVar('locale');
$locale = $locale ?: '';

// list of languees to select from
$locales = [
  '' => 'Automatic',
  'de' => 'Deutsch',
  'en' => 'English',
  'fr' => 'Français'
];

// print '<pre>';
// print json_encode($client->getPrices(['event_id' => $event_id])->map(function($c){ return $c->toArray(); }), JSON_PRETTY_PRINT);
// print '</pre>';
?>
  <div class="row">
    <div class="col-xs-6">
      <?php if ($changeorder): ?>
      <h3>Rebooking order #<?= $changeorder->getReplacedOrderId() ?></h3>
      <?php endif; ?>
    </div>
    <div class="col-xs-6">
      <div class="pull-right">
        <form>
          <input type="hidden" name="event_id" value="<?= htmlentities($event_id) ?>">
          <label for="ne-demo-lang-select">Language:</label>
          <select id="ne-demo-lang-select" name="locale" class="form-control" onchange="this.parentNode.submit()">
            <?php
            foreach ($locales as $key => $value) {
              echo '<option value="' . $key . '"' . ($locale === $key ? ' selected' : '') . '>' . $value . '</option>';
            }
            ?>
          </select>
          <br>
        </form>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="content-box" style="min-height:400px">
      <?php
        /*
         * Embed the booking widget with the hash stored in config and the submitted event ID
         * @see https://developer.nextevent.com/#embed-the-booking-widget
         */
        if ($embed_link)
          $embedOptions = ['link' => $embed_link];
        else
          $embedOptions = ['eventId' => $event_id];

        // if this is a rebooking order, pass a reference to the widget
        if ($changeorder) {
          $embedOptions['basket'] = $changeorder;
        }

        echo $client->getWidget(Config::get('widgetHash'))->generateEmbedCode($embedOptions);
      ?>
      </div>
    </div>
    <div class="col-md-4 content-box">
      <div id="basket">
        <?php
        try {
          $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
          if ($orderId) {
            echo Util::renderBasket($client, $orderId);
          } else {
            echo '<h3>Basket</h3>';
            Util::info('Your basket is empty');
          }
        } catch (Exception $ex) {
          Util::error($ex->getMessage());
          Util::logException($ex);
        }
        ?>
      </div>
      <div>
        <p class="pull-right">
          <a href="checkout.php" class="btn btn-primary">Proceed to checkout</a>
        </p>
      </div>
    </div>
  </div>
<?php


Util::htmlFooter();
