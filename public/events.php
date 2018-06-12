<?php
/*
 * Page rendering a listing of events fetched from the NextEvent API
 *
 * This file is part of the NextEvent integration demo site and only serves as an example.
 * Please do not use in production.
 *
 * @ 2018 NextEvent AG - nextevent.com
 */

require_once '../vendor/autoload.php';

use NextEvent\Demo\Config;
use NextEvent\Demo\Util;
use NextEvent\Demo\Bootstrap;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Exception\NotAuthenticatedException;

$client = Bootstrap::getClient();
$cache = $client->getCache();
$events = [];

Util::htmlHeader('events');

// fetch all events
// @see http://docs.nextevent.com/sdk/#listing-events-for-booking
try {
  $events = $client->getEvents();
} catch (APIResponseException $ex) {
  Util::error('Events not loaded. Code: '.$ex->getCode());
} catch (NotAuthenticatedException $ex) {
  Util::error('Could not authenticate SDK Client: '.$ex->getMessage());
} catch (Exception $ex) {
  Util::logException($ex);
}

// to array event ID => event title
$event_titles = array();
foreach ($events as $event) {
  $event_titles[$event->getId()] = $event->getTitle();
}

// cache event titles for later access
$cache->set('event_titles', $event_titles);

// show list of all events
if (count($events)) {
  echo '<ul class="event-list">';
  foreach ($events as $event) {
    ?>
    <li class="event-row">
      <?php if ($event->hasImage()): ?>
      <div class="event-image"><img src="<?= htmlspecialchars($event->getImage()) ?>" alt="Image"></div>
      <?php endif; ?>
      <a href="embed.php?event_id=<?= $event->getId()?>" target="_self" class="event-title">
        <i class="fa fa-calendar"></i> <?= htmlspecialchars($event->getTitle()) . ($event->getTitle() ? '' : 'NO NAME') ?>
      </a>
      <ul class="event-info">
      <?php
        $startDate = $event->getStartDate();
        if ($startDate) {?>
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
  Util::info('No events found');
}

Util::htmlFooter();
