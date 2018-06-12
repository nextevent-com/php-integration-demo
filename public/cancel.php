<?php
/*
 * Page controller cancelling a completed a NextEvent order
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

$client = Bootstrap::getClient();
$orderId = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] :
          (isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0);

Util::htmlHeader('admin');

// Cancellation is a two-step process. We store the result of the first step
// in session for executing the final settlement after the user has confirmed cancellation.

$cancellationRequest = null;
if (isset($_SESSION['nexteventCancellationRequest'])) {
  $cancellationRequest = unserialize($_SESSION['nexteventCancellationRequest']);
}

// read orderId from stored cancellation request
if ($cancellationRequest && !$orderId) {
  $orderId = $cancellationRequest->getOrderId();
}

// request cancellation for the given orderId
if (!$cancellationRequest || $cancellationRequest->getOrderId() != $orderId) {
  try {
    $cancellationRequest = $client->requestCancellation($orderId);
    $_SESSION['nexteventCancellationRequest'] = serialize($cancellationRequest);
  } catch (APIResponseException $ex) {
    Util::logException($ex);
    Util::error($ex->getCode() . ' cancellation rejected: ' . $ex->getMessage());
    return Util::htmlFooter();  // terminate script
  }
}

// confirm cancellation by submitting the previously obtained cancellation request
if ($cancellationRequest && isset($_POST['submit'])) {
  try {
    $client->settleCancellation($cancellationRequest, $_POST['reason']);
    unset($_SESSION['nexteventCancellationRequest']);
    Util::success('Successfully cancelled order #' . $orderId);
    return Util::htmlFooter();
  } catch (APIResponseException $ex) {
    Util::logException($ex);
    Util::error($ex->getCode() . ' could not cancel order: ' . $ex->getMessage());
  }
}

?>

<h3>Cancel order #<?= htmlspecialchars($orderId) ?></h3>

<p>Do you really want to cancel this order?</p>

<?php if ($cancellationRequest): ?>
<p>This requires refunding <?= sprintf('%s %0.2f', $cancellationRequest->getCurrency(), $cancellationRequest->getRefundAmount()) ?> for NextEvent tickets.</p>
<?php endif; ?>

<form method="post">
  <div class="form-group">
    <label for="cancellationReason" class="control-label">Remarks</label>
    <input type="text" class="form-control" id="cancellationReason" name="reason" placeholder="Reason for cancellation">
  </div>
  <button type="submit" class="btn btn-danger" name="submit">Confirm cancellation</button>
</form>

<?php

Util::htmlFooter();
