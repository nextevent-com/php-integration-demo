<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Config;
use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\NotAuthenticatedException;

$client = Bootstrap::getClient();
$cache = $client->getCache();
$events = [];

Util::html_header('events');

// fetching all event
try {
  $events = $client->getEvents();
} catch (APIResponseException $ex) {
  Util::error('Events not loaded. Code: '.$ex->getCode());
} catch (NotAuthenticatedException $ex) {
  Util::error('Could not authenticate SDK Client: '.$ex->getMessage());
} catch (Exception $ex) {
  Util::logException($ex);
}

// to indexed array
$indexed_list = array();
foreach ($events as $event) {
  $indexed_list[$event->getId()] = $event->getTitle();
}

// cache events for better performance
$cache->set('event_list', $indexed_list);

// show list of all events
if (count($events)) {
  echo '<ul class="event-list">';
  foreach ($events as $event) {
    ?>
    <li class="event-row">
      <a href="widget_embed.php?event_id=<?= $event->getId()?>" target="_self">
        <i class="fa fa-calendar"></i> <?= htmlspecialchars($event->getTitle()) . ($event->getTitle() ? '' : 'NO NAME') ?>
      </a><br>
      <ul>
      <?php
        $startDate = $event->getStartDate();
        if($startDate) {?>
        <li><i class="fa fa-clock-o"></i>
        Start: <?= $startDate->format($startDate->isDateOnly() ? Config::get('dateFormat') : Config::get('dateTimeFormat')) ?></li>
      <?php } ?>
      <?php
        $endDate = $event->getEndDate();
        if($endDate) { ?>
        <li><i class="fa fa-clock-o"></i>
        Ende: <?= $endDate->format($startDate->isDateOnly() ? Config::get('dateFormat') : Config::get('dateTimeFormat')) ?></li>
      <?php } ?>
      <?php if($event->getLocation()) { ?>
        <li><i class="fa fa-map-pin"></i> Ort: <?= htmlspecialchars($event->getLocation()->getTitle()) ?></li>
      <?php } ?>
      </ul>
    </li>
<?php
  }
  echo '</ul>';
} else {
  Util::info('NO events<br>');
}

Util::html_footer();
