<?php
/*
 * Backend controller executing different actions based on GET/POST parameters
 *
 * This file is part of the NextEvent integration demo site and only serves as an example.
 * Please do not use in production.
 *
 * @ 2018 NextEvent AG - nextevent.com
 */

require_once '../vendor/autoload.php';

use NextEvent\Demo\Bootstrap;
use NextEvent\Demo\Util;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Util\Env;

// get an instance of the NextEvent API client.
// this also calls session_start() if necessary
$client = Bootstrap::getClient();

/*
 * POST / - save the submitted order_id in this user's session
 *
 * This stores the NexEvent basket reference for later server-to-server
 * interactions via the API.
 *
 * It also renders a mini basket view and returns it as a JSON response.
 *
 * @see https://developer.nextevent.com/#proceed-to-checkout
 */
if (isset($_POST['set_order_id'])) {
  $_SESSION['nexteventOrderId'] = $_POST['set_order_id'];
  unset($_SESSION['nexteventPaymentAuthorization']);

  // respond with the rendered basket preview
  $response = ['order_id' => $_SESSION['nexteventOrderId']];
  try {
    $response['html'] = Util::renderBasket($client, $_SESSION['nexteventOrderId']);
  } catch (Exception $ex) {
    Util::logException($ex);
    $response['error'] = $ex->getMessage();
  }
  Util::jsonResponse($response);
}


/*
 * GET /?settle_payment - settle the payment process of the current order
 *
 * Completes the payment process which was previously initiated with
 * Client::authorizeOrder(). This action uses the payment authorization stored
 * in session and submits a final settlemet to the NextEvent API
 *
 * @see https://developer.nextevent.com/#settle-payment
 * @see https://developer.nextevent.com/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_settlePayment
 */
if (isset($_GET['settle_payment'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    /* @var \NextEvent\PHPSDK\Model\Payment $payment */
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);
    if ($payment->isExpired()) {
      throw new Exception('Payment is expired');
    }

    $language = '';
    if (Env::getVar('locale')) {
      $language = Env::getVar('locale');
    } else if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $language = reset(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
    }

    // example customer data
    // $customer = [
    //   'name' => 'Mad Max',
    //   'firstname' => 'Max',
    //   'lastname' => 'Mad',
    //   'company' => 'Example Inc.',
    //   'email' => 'max.muster@example.com',
    //   'sex' => 'm', // m:male f:female
    //   'address' => [
    //     'street' => 'Musterstr. 1',
    //     'pobox' => null,
    //     'zip' => '3001',
    //     'city' => 'Bern',
    //     'country' => 'CH',
    //     'countryname' => 'Schweiz'
    //   ],
    //   'language' => 'de-CH'
    // ];

    $customer = [
      'name' => $_POST['firstname'] . ' ' . $_POST['lastname'],
      'firstname' => $_POST['firstname'],
      'lastname' => $_POST['lastname'],
      'email' => $_POST['email'],
      'language' => $language,
      'address' => []
    ];

    // unique payment transaction identifier for later reference
    $transactionId = 'demo-' . time();

    $success = $client->settlePayment($payment, $customer, $transactionId);

    // payment settlement succeeded, redirect to ticket download page
    if ($success) {
      unset($_SESSION['nexteventPaymentAuthorization']);
      header('Location: documents.php');
      exit;
    } else {
      Util::htmlHeader('payment');
      echo 'There went something wrong with the settlement';
      Util::htmlFooter();
    }

  } catch (APIResponseException $exception) {
    Util::htmlHeader('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::htmlFooter();
  } catch (Exception $exception) {
    Util::logException($exception);
    Util::htmlHeader('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::htmlFooter();
  }
}


/*
 * GET /?abort_payment - cancel the order payment process
 *
 * Cancels the payment process which was previously initiated with Client::authorizeOrder().
 *
 * @see https://developer.nextevent.com/#abort-the-payment-process
 * @see https://developer.nextevent.com/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_abortPayment
 */
if (isset($_GET['abort_payment'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);

    $success = $client->abortPayment(
      $payment,
      'Customer has aborted payment'
    );

    if ($success) {
      unset($_SESSION['nexteventPaymentAuthorization']);
      header('Location: checkout.php');
      sleep(2);  // wait for basket to be restored before redirecting
      exit;
    } else {
      Util::htmlHeader('payment');
      echo 'There went something wrong with the settlement';
      Util::htmlFooter();
    }
  } catch (APIResponseException $ex) {
    Util::htmlHeader('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($ex->getMessage());
    Util::htmlFooter();
  } catch (Exception $exception) {
    Util::logException($exception);
    Util::htmlHeader('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::htmlFooter();
  }
}


/*
 * GET /?tickets - Get ticket documents for the current order
 *
 * Fetches the ticket documents for the order stored in session and return them
 * as download urls to the client. The action has set a wait time of 5 seconds
 *
 * @see https://developer.nextevent.com/#retrieve-tickets
 * @see https://developer.nextevent.com/phpdoc/classes/NextEvent.PHPSDK.Client.html#method_getOrderDocuments
 */
if (isset($_GET['tickets'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    $documents = $client->getOrderDocuments($orderId, 5);
    $results = array_map(
      function($document) {
        /* @var \NextEvent\PHPSDK\Model\OrderDocument $document */
        return $document->toArray();
      },
      $documents
    );
    Util::jsonResponse([
      'ready' => true,
      'results' => $results
    ]);
  } catch (Exception $exception) {
    Util::logException($exception);
    Util::jsonResponse([
      'ready' => false,
      'message' => $exception->getMessage()
    ]);
  }
}
