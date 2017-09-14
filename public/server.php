<?php
require_once '../vendor/autoload.php';

use NextEvent\Demo\Bootstrap;
use NextEvent\Demo\Util;
use NextEvent\PHPSDK\Exception\APIResponseException;
use NextEvent\PHPSDK\Util\Env;

$client = Bootstrap::getClient();

function response($data)
{
  header('Content-Type: application/json');
  echo json_encode($data);
}

if (!isset($_SESSION)) {
  session_start();
}

// set order_id
if (isset($_POST['set_order_id'])) {
  $_SESSION['nexteventOrderId'] = $_POST['set_order_id'];
  unset($_SESSION['nexteventPaymentAuthorization']);
  response(['order_id' => $_SESSION['nexteventOrderId']]);
}

// tickets as json
if (isset($_GET['tickets'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    $documents = $client->getTicketDocuments($orderId, 15);
    $urls = array_map(function($document) { /* @var \NextEvent\PHPSDK\Model\TicketDocument $document */
      return $document->getDownloadUrl();
    },
      $documents
    );
    response(
      [
        'ready' => true,
        'urls' => $urls
      ]
    );
  } catch (Exception $exception) {
    Util::logException($exception);
    response(['ready' => false, 'message' => $exception->getMessage()]);
  }
}

// settle payment process
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
    } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    }

//    // example customer data
//    $customer = [
//      'email' => 'max.muster@example.com',
//      'name' => 'Max Muster',
//      'company' => 'Musterfirma',
//      'address' => [
//        'street' => 'Musterstr. 1',
//        'pobox' => '',
//        'zip' => '3001',
//        'city' => 'Bern',
//        'country' => 'CH'
//      ],
//      'language' => 'de-CH'
//    ];

    $customer = [
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'language' => $language,
      'booking_ref' => md5(time()), // TODO replace by reference for booking
      'address' => []
    ];

    // TODO get transaction id
    $transactionId = 'demo-' . time();

    $success = $client->settlePayment($payment, $customer, $transactionId);

    if ($success) {
      unset($_SESSION['nexteventPaymentAuthorization']);
      header('Location: documents.php');
      exit;
    } else {
      Util::html_header('payment');
      echo 'There went something wrong with the settlement';
      Util::html_footer();
    }

  } catch (APIResponseException $exception) {
    Util::html_header('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::html_footer();
  } catch (Exception $exception) {
    Util::logException($exception);
    Util::html_header('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::html_footer();
  }
}


// cancel payment process
if (isset($_GET['abort_payment'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    $payment = unserialize($_SESSION['nexteventPaymentAuthorization']);

    $success = $client->abortPayment(
      $payment,
      'Kunde hat die Bezahlung abgebrochen'
    );

    unset($_SESSION['nexteventPaymentAuthorization']);
    if ($success) {
      sleep(2);  // wait for basket to be restored before redirecting
      header('Location: checkout.php');
      exit;
    } else {
      Util::html_header('payment');
      echo 'There went something wrong with the settlement';
      Util::html_footer();
    }
  } catch (APIResponseException $ex) {
    Util::html_header('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($ex->getMessage());
    Util::html_footer();
  } catch (Exception $exception) {
    Util::logException($exception);
    Util::html_header('payment');
    echo 'There went something wrong with the settlement<br>';
    Util::error($exception->getMessage());
    Util::html_footer();
  }
}

// basket view als json
if (isset($_GET['basket'])) {
  try {
    $orderId = isset($_SESSION['nexteventOrderId']) ? $_SESSION['nexteventOrderId'] : 0;
    response([
      'html' => Util::renderBasket($client, $orderId)
    ]);
  } catch (Exception $ex) {
    Util::logException($ex);
    response([
      'error' => $ex->getMessage()
    ]);
  }
}
