<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;

Util::html_header('overview');

?>

  <div class="container">
    <h4><a href="event_list.php">Event Auflistung</a></h4>
    <p>
      Eine Liste der verfügbaren Events wird bei NextEvent abgefragt und dargestellt.
    </p>
  </div>
  <div class="container context-nextevent">
    <h4><a href="widget_embed.php">Widget Buchung</a></h4>
    <p>
      Zum Buchen von Ticket eines Events wird das NextEvent Widget eingebunden.
    </p>
  </div>
  <div class="container">
    <h4><a href="checkout.php">Warenkorb</a></h4>
    <p>
      Zur Bestätigung hat der Kunde eine übersicht der im Warenkorb erhaltenen Tickets.
    </p>
  </div>
  <div class="container">
    <h4><a href="index.php?clear">Cache löschen</a></h4>
    <?php
    if(isset($_GET['clear'])) {
        $client = \NextEvent\Demo\Bootstrap::getClient();
        $cache = $client->getCache();
        $cache->clear();
        Util::info('Cache wurde gelöscht');
        if(!isset($_SESSION)) {
            session_start();
            unset($_SESSION['nexteventOrderId']);
            unset($_SESSION['nexteventPaymentAuthorization']);
        }
    }
    ?>
    <p>
        Demo zurück setzen und Cache löschen.
    </p>
  </div>

<?php
Util::html_footer();
