<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Config;
use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\APIResponseException;

$client = Bootstrap::getClient();
$cache = $client->getCache();

// fetching all event
try {
  $events = $client->getEvents();
} catch (APIResponseException $ex) {
  Util::error('Events not loaded. Code: '.$ex->getCode());
  $events = [];
}

// to indexed array
$indexed_list = array();
foreach ($events as $event) {
  $indexed_list[$event->getId()] = $event->getTitle();
}

// cache events for better performance
$cache->set('event_list', $indexed_list);


Util::html_header('events');

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
      <?php if($event->getStartDate()) { ?>
        <li><i class="fa fa-clock-o"></i> Start: <?= $event->getStartDate()->format(Config::get('dateTimeFormat')) ?></li>
      <?php } ?>
      <?php if($event->getEndDate()) { ?>
        <li><i class="fa fa-clock-o"></i> Ende: <?= $event->getEndDate()->format(Config::get('dateTimeFormat')) ?></li>
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
