<?php
/**
 * Main entry point of the demo application
 */

require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;

Util::htmlHeader('overview');

?>

  <div class="container">
    <h4><a href="events.php">Event listing</a></h4>
    <p>
      Displays a list of events available for booking.
    </p>
  </div>
  <div class="container context-nextevent">
    <h4><a href="embed.php">Booking widget</a></h4>
    <p>
      Embeds the NextEvent widget for booking tickets for a selected event.
    </p>
  </div>
  <div class="container">
    <h4><a href="checkout.php">Checkout</a></h4>
    <p>
      Review and complete the NextEvent ticket order.
    </p>
  </div>
  <div class="container">
    <h4><a href="index.php?clear">Clear cache</a></h4>
    <p>
      Resets session data and server-side cache.
    </p>
    <?php
    if (isset($_GET['clear'])) {
      // get an instance of the NextEvent API client.
      // this also calls session_start() if necessary
      $client = Bootstrap::getClient();
      $client->getCache()->clear();
      session_destroy();
      Util::info('Cache cleared successfully');
    }
    ?>
  </div>

<?php
Util::htmlFooter();
