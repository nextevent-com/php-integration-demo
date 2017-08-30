<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Util\Env;
use NextEvent\Demo\Config;

$client = Bootstrap::getClient();


Util::html_header('booking');

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

echo '<div class="context-nextevent">';
// embed widget into page
if (!$event_id) {
  Util::debug('No event_id! use ?event_id=00000');
}

// user language selection
if (isset($_GET['locale'])) {
  if (!isset($_SESSION)) {
    session_start();
  }
  $_SESSION['locale'] = $_GET['locale'];
  // and change Env locale for this session
  Env::setVar('locale', $_GET['locale']);
}

$locale = Env::getVar('locale');
$locale = $locale ? $locale : '';

$locales = [
  '' => 'Automatisch',
  'de' => 'Deutsch',
  'en' => 'Englisch',
  'fr' => 'Französisch'
]
?>
<div class="pull-right">
    <form>
        <select name="locale" class="form-control" onchange="this.parentNode.submit()">
          <?php
          foreach ($locales as $key=>$value) {
            echo '<option value="' . $key . '"' . ($locale === $key ? ' selected' : '') . '>' . $value . '</option>';
          }
          ?>
        </select>
        <br>
    </form>
</div>
<?php

echo $client->getWidget(Config::get('widgetHash'))->generateEmbedCode($event_id);
echo '</div>';


// page menu
?>
  <div class="">
    <div class="pull-right">
      <a href="checkout.php" class="btn btn-primary">Zur Bezahlung</a>
    </div>
  </div>
  <br>
<?php


Util::html_footer();
