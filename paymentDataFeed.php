
text/x-generic paymentDataFeed.php ( PHP script, UTF-8 Unicode text, with CRLF line terminators )
<?php
include('SHAPaydollarSecure.php');

// Retrieve HTTP POST parameters
$src = $_POST['src'];
$prc = $_POST['prc'];
$successcode = $_POST['successcode'];
$ref = $_POST['Ref'];
$payRef = $_POST['PayRef'];
$amt = $_POST['Amt'];
$cur = $_POST['Cur'];
$payerAuth = $_POST['payerAuth'];
$ord = $_POST['Ord'];
$holder = $_POST['Holder'];
$remark = $_POST['remark'];
$authId = $_POST['AuthId'];
$eci = $_POST['eci'];
$sourceIp = $_POST['sourceIp'];
$ipCountry = $_POST['ipCountry'];
$mpsAmt = $_POST['mpsAmt'];
$mpsCur = $_POST['mpsCur'];
$mpsForeignAmt = $_POST['mpsForeignAmt'];
$mpsForeignCur = $_POST['mpsForeignCur'];
$mpsRate = $_POST['mpsRate'];
$cardIssuingCountry = $_POST['cardIssuingCountry'];
$payMethod = $_POST['payMethod'];
$secureHash = $_POST['secureHash'];

$secureHashSecret = "rp6RIf6VpNbT4vMTskQ9qu0Gusyp2yJB"; // offered by PayDollar

$isSecureHash = true;

if ($isSecureHash) {
    $secureHashs = explode(',', $secureHash);
    $paydollarSecure = new SHAPaydollarSecure();
    $verifyResult = false;

    foreach ($secureHashs as $value) {
        $verifyResult = $paydollarSecure->verifyPaymentDatafeed(
            $src,
            $prc,
            $successcode,
            $ref,
            $payRef,
            $cur,
            $amt,
            $payerAuth,
            $secureHashSecret,
            $value
        );

        if ($verifyResult) {
            break;
        }
    }

    if (!$verifyResult) {
        // Handle verification failure
        error_log('Secure Hash verification failed.');
        exit;
    }
}

// Backend processing based on the success code
if ($successcode == '0') {
    // Payment Success Logic
    // Update your database for Transaction Accepted
    // Add security control to check the currency, amount with the merchant’s order reference from your database
    $orderExists = true; // Implement logic to check order existence

    if ($orderExists) {
        // Update transaction status in the database
        // Notify the customer via email or other means
        error_log('Payment Successful for Order Ref: ' . $ref);
    } else {
        error_log('Order not found for Ref: ' . $ref);
        // Handle the scenario where the order does not exist
    }
} else {
    // Payment Fail Logic
    // Update your database for Transaction Rejected
    error_log('Payment Failed for Order Ref: ' . $ref);
}

// Respond with 'OK' to indicate successful receipt of data feed
echo 'OK';

// Log the POST data for debugging purposes
foreach ($_POST as $key => $value) {
    error_log('[' . $key . ']=[' . $value . ']');
}
?>