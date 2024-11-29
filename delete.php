<?php
include 'dbconn.php';

// Validate and sanitize inputs
if (isset($_POST['confirmId']) && isset($_POST['name'])) {
    $Can_confirmNo = htmlspecialchars(trim($_POST['confirmId']));
    $Can_Lname = htmlspecialchars(trim($_POST['name']));

    // Create XML request string
    $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <OTA_VehCancelRQ xmlns=\"http://www.opentravel.org/OTA/2003/05\" Version=\"1.008\">
        <POS>
            <Source PseudoCityCode=\"XXXX\" ISOCountry=\"US\" AgentDutyCode=\"T17R16L5D11\">
                <RequestorID Type=\"4\" ID=\"X975\">
                    <CompanyName Code=\"CP\" CodeContext=\"4PH5\"/>
                </RequestorID>
            </Source>
            <Source>
                <RequestorID Type=\"5\" ID=\"ota2007a\"/>
            </Source>
        </POS>
        <VehCancelRQCore CancelType=\"Book\">
            <UniqueID Type=\"14\" ID=\"$Can_confirmNo\"/>
            <PersonName>
                <Surname>$Can_Lname</Surname>
            </PersonName>
        </VehCancelRQCore>
    </OTA_VehCancelRQ>";

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, "https://vv.xqual.hertz.com/DirectLinkWEB/handlers/DirectLinkHandler?id=ota2007a");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/xml',
      'Content-Length: ' . strlen($xmlString)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // Execute cURL request and get the response
    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $error]);
        exit;
    }else{
        $sql = "DELETE FROM `bookings` WHERE ConfirmedId = '$Can_confirmNo'";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => "Cancellation for $Can_Lname with Confirm ID $Can_confirmNo was successful and the record was deleted."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cancellation successful, but error deleting record: ' . $conn->error]);
        }

        $conn->close();
    }

    // Close cURL session
    curl_close($ch);

    // Load the XML response
    $xml = simplexml_load_string($response);

    // Register the namespace for the XML
    $xml->registerXPathNamespace('ns', 'http://www.opentravel.org/OTA/2003/05');

    // Check if the CancelStatus is "Cancelled"
    $result = $xml->xpath("//ns:VehCancelRSCore[@CancelStatus='Cancelled']");

    // If the cancellation was successful, delete from database and return a success message
    if ($result) {
        // SQL to delete a record
        
    } else {
        echo json_encode(['status' => 'error', 'message' => "Cancellation for $Can_Lname with Confirm ID $Can_confirmNo failed."]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request. Confirm ID or Name is missing.']);
}
?>
