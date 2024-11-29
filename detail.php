<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Reservation Details</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .section {
            margin-bottom: 20px;
        }
        .section p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php
    // API endpoint URL
    $apiUrl = 'https://vv.xqual.hertz.com/DirectLinkWEB/handlers/DirectLinkHandler?id=ota2007a'; // Replace with your actual API endpoint
    $confId = $_GET['cnfNo'];
    $surname = $_GET['lName'];
    // XML request data
    $xmlRequest = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <OTA_VehRetResRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="2.007">
        <POS>
            <Source ISOCountry="IN" AgentDutyCode="T17R16L5D11">
                <RequestorID Type="4" ID="X975">
                    <CompanyName Code="CP" CodeContext="4PH5"/>
                </RequestorID>
            </Source>
        </POS>
        <VehRetResRQCore>
            <UniqueID Type="14" ID="$confId"/> <!-- Confirmation ID -->
            <PersonName>
                <Surname>$surname</Surname> <!-- Customer's Last Name -->
            </PersonName>
        </VehRetResRQCore>
    </OTA_VehRetResRQ>
    XML;
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/xml',  // Set the request content type to XML
        'Content-Length: ' . strlen($xmlRequest)
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
    
    // Execute the cURL request and capture the response
    $response = curl_exec($ch);
    
    $xml = simplexml_load_string($response);
    curl_close($ch);
    function separateAndConvertDateTime($dateTime) {
        // Split the string at "T" to separate date and time
        list($date, $timeWithOffset) = explode("T", $dateTime);
        
        // Split the time to remove the timezone offset
        $time24Hour = explode("-", $timeWithOffset)[0]; // Removes the timezone part
        
        // Convert the time to 12-hour format
        $dateTimeObj = DateTime::createFromFormat('H:i:s', $time24Hour);
        $time12Hour = $dateTimeObj->format('h:i:s A');
        
        // Return date and converted time
        return [
            'date' => $date,
            'time' => $time12Hour
        ];
    }
    $pickDateTime = $xml->VehRetResRSCore->VehReservation->VehSegmentCore->VehRentalCore['PickUpDateTime'];
    $dropDateTime = $xml->VehRetResRSCore->VehReservation->VehSegmentCore->VehRentalCore['ReturnDateTime'];

    $pickDate = separateAndConvertDateTime($pickDateTime);
    $dropDate = separateAndConvertDateTime($dropDateTime);
    ?>
    <div class="container">
        <h1 class="text-center m-4">Vehicle Reservation Details</h1>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Reservation Information</h2>
            <p><strong>Vendor Code:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vendor['Code']; ?></p>
            <p><strong>Pick-Up Date</strong> <?php echo $pickDate['date'];?></p>
            <p><strong>Pick-Up Time</strong> <?php echo $pickDate['time'];?></p>
            <p><strong>Return Date</strong> <?php echo $dropDate['date'];?></p>
            <p><strong>Return Time</strong> <?php echo $dropDate['time'];?></p>
            <p><strong>Pick-Up Location:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->VehRentalCore->PickUpLocation['LocationCode'];?></p>
            <p><strong>Return Location:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->VehRentalCore->ReturnLocation['LocationCode'];?></p>
        </div>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Customer Information</h2>
            <p><strong>Customer Name:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->Customer->Primary->PersonName->Surname;?></p>
        </div>

        <div class="section">
            <h2 class="bg-warning text-primary p-2">Vehicle Details</h2>
            <img src="<?php echo 'https://images.hertz.com/vehicles/220x128/' . $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle->PictureURL;?>" alt="Vehicle Image" class="vehicle-img">
            <p><strong>Vehicle Code:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle->VehMakeModel['Code'] ;?></p>
            <p><strong>Passenger Quantity:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['PassengerQuantity'] ;?></p>  
            <p><strong>Baggage Quantity:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['BaggageQuantity'] ;?></p>
            <p><strong>Air Conditioning:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['AirConditionInd'] ;?></p>
            <p><strong>Transmission Type:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['TransmissionType'] ;?></p>
            <p><strong>Fuel Type:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['FuelType'] ;?></p>
            <p><strong>Drive Type:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle['DriveType'] ;?></p>
            <p><strong>Make and Model:</strong> <?php echo $xml->VehRetResRSCore->VehReservation->VehSegmentCore->Vehicle->VehMakeModel['Name'] ;?></p>
        </div>

        <div class="section">
            <h2 class="bg-warning text-primary p-2 mb-3">Terms & Conditions</h2>
            <?php foreach ($xml->Warnings->Warning as $warning): ?>
                <div class="alert alert-danger">
                    <ul class="m-0">
                        <li> <?php echo htmlspecialchars((string)$warning['ShortText']); ?></li>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-center gap-5 mt-3">
            <button onclick="window.location.href='index.php';" class="btn bg-warning text-primary">
                Go Back To Homepage
            </button>
            <button onclick="window.print()" class="btn bg-primary text-warning">
                Download Now
            </button>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded',function(){
        setTimeout(function(){
            window.location.href = "index.php";
        },3000);
    });
</script>
</html>
