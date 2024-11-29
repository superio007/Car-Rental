<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $rawData = file_get_contents('php://input');
  $requestData = json_decode($rawData, true);

  if (json_last_error() === JSON_ERROR_NONE) {
    $carCategory = $requestData['carCategory'];
    $vendorId = $requestData['vendorID'];
    $pickupLocation = $requestData['pickup'];
    $dropoffLocation = $requestData['dropOff'];
    $pickupDateTime = $requestData['pickUpDateTime'];
    $dropoffDateTime = $requestData['dropOffDateTime'];

    $xmlRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <OTA_VehAvailRateRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.008">
            <POS>
                <Source ISOCountry="AU" AgentDutyCode="T17R16L5D11">
                    <RequestorID Type="4" ID="X975">
                        <CompanyName Code="CP" CodeContext="4PH5"/>
                    </RequestorID>
                </Source>
                <Source>
                    <RequestorID Type="8" ID="$vendorId"/>
                </Source>
            </POS>
            <VehAvailRQCore Status="Available">
                <VehRentalCore PickUpDateTime="$pickupDateTime" ReturnDateTime="$dropoffDateTime">
                    <PickUpLocation LocationCode="$pickupLocation" CodeContext="IATA"/>
                    <ReturnLocation LocationCode="$dropoffLocation" CodeContext="IATA"/>
                </VehRentalCore>
            </VehAvailRQCore>
        </OTA_VehAvailRateRQ>
        XML;

    $apiUrl = 'https://vv.xnet.hertz.com/DirectLinkWEB/handlers/DirectLinkHandler?id=ota2007a';
    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/xml',
      'Content-Length: ' . strlen($xmlRequest)
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);

    $response = curl_exec($ch);
    curl_close($ch);

    // Filter 

    $xml = new SimpleXMLElement($response);

    // Initialize an array to hold filtered results
    $filteredResults = [];

    // Loop through the VehAvail elements
    foreach ($xml->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehAvail) {
      // Check if the VehMakeModel code matches the specified carCategory
      if ((string) $vehAvail->VehAvailCore->Vehicle->VehMakeModel['Code'] === $carCategory) {
        // Add the matching item to the results array
        $filteredResults[] = [
          'Name' => (string) $vehAvail->VehAvailCore->Vehicle->VehMakeModel['Name'],
          'Code' => (string) $vehAvail->VehAvailCore->Vehicle->VehMakeModel['Code'],
          'RateTotalAmount' => (string) $vehAvail->VehAvailCore->TotalCharge['RateTotalAmount'],
          'CurrencyCode' => (string) $vehAvail->VehAvailCore->TotalCharge['CurrencyCode'],
        ];
      }
    }

    // Output the filtered results
    if (!empty($filteredResults)) {
      echo json_encode(['results' => $filteredResults]);
    } else {
      echo "No vehicles found matching the specified category: $carCategory";
    }
  } else {
    echo json_encode(['error' => 'Invalid JSON payload']);
  }
} else {
  echo json_encode(['error' => 'Invalid request method']);
}
