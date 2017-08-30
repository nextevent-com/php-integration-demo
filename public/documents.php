<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;

$client = Bootstrap::getClient();


Util::html_header('documents');

?>


  <div class="load-msg">
    <i class="fa fa-spinner fa-rotate"></i> Tickets werden ausgestellt
  </div>


  <div class="ticket-list" style="display: none">

  </div>


  <script>
    window.pollTickets = true;
  </script>

<?php

Util::html_footer();
