<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/74e6741759.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
    if (!isset($_SESSION['jwtToken'])) {
        echo "<script>window.location.href='login.php';</script>";
    }
    $responseZE = $_SESSION['responseZE'];
    // var_dump($responseZE);
    $responseZT = $_SESSION['responseZT'];
    // var_dump($responseZT);
    $responseZR = $_SESSION['responseZR'];
    // var_dump($responseZR);
    $dataArray = $_SESSION['dataarray'];
    var_dump($dataArray);
    require "dbconn.php";
    $pickUp = $dataArray['pickLocation'] ?? '';
    $drop = $dataArray['dropLocation'] ?? '';
    $sql = $conn->prepare("SELECT * FROM `airport_list` WHERE citycode IN ( ? , ?)");
    $sql->bind_param("ss", $pickUp, $drop);
    $sql->execute();
    $result = $sql->get_result();
    $pickupDetails = '';
    $dropoffDetails = '';

    // Loop through the results to assign pickup and drop-off airport names and cities
    while ($row = $result->fetch_assoc()) {
        if ($row['citycode'] === $pickUp) {
            $pickupDetails = $row['city'] . ' ' . $row['airpotname'];
        }
        if ($row['citycode'] === $drop) {
            $dropoffDetails = $row['city'] . ' ' . $row['airpotname'];
        }
    }
    // $conn->close();

    // Date formatting function
    function formatDate($dateString)
    {
        $date = new DateTime($dateString);
        return $date->format('D, d M, Y \a\t H:i');
    }

    $pickDate = formatDate($dataArray['pickUpDateTime'] ?? '');
    $dropDate = formatDate($dataArray['dropOffDateTime'] ?? '');

    $xmlresZE = new SimpleXMLElement($responseZE);
    $xmlresZR = new SimpleXMLElement($responseZR);
    $xmlresZT = new SimpleXMLElement($responseZT);
    $categories = [
        'Economy' => [3],
        'Compact' => [4],
        'Midsize' => [6],
        // 'LargeSize' => ['FDAR'],
        'LuxurySportsCar' => [37],
        'SUV' => [4],
        'StationWagon' => [8], // Not in the XML
        'VanPeopleCarrier' => [2], // Not in the XML
        '7-12PassengerVans' => [2] // Not in the XML
    ];
    function extractVehicleDetails($xmlResponse, $categories)
    {
        $vehicleDetails = [];

        if (isset($xmlResponse->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
            foreach ($xmlResponse->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size']; // Fetch the vehicle class size
                $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                $code = (string)$vehicle['Code'];


                // Loop through the categories and match based on size
                foreach ($categories as $category => $sizes) {
                    if (in_array($size, $sizes)) {
                        $vehicleDetails[$category][] = [
                            'name' => $name,
                            'size' => $size,
                            'rate' => $rate,
                            'currency' => $currency,
                        ];
                    }
                }
            }
        }

        return $vehicleDetails;
    }
    $sql = "SELECT MarkupPrice FROM `markup_price`";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            $markUp = $row['MarkupPrice'];
        }
    } else {
        echo "0 results";
    }
    // echo $markUp;

    $vehicleDetailsZE = extractVehicleDetails($xmlresZE, $categories);
    $vehicleDetailsZT = extractVehicleDetails($xmlresZT, $categories);
    $vehicleDetailsZR = extractVehicleDetails($xmlresZR, $categories);
    // echo "<pre>";
    // print_r($xmlresZE);
    // echo "</pre>";
    function calculatePercentage($part, $total)
    {
        $og = $total;
        if ($total == 0) {
            return "Total cannot be zero"; // To avoid division by zero error
        }
        $percentage = ($total * $part) / 100;
        return $percentage + $og;
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
        // Retrieve form data
        $transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
        $fuelTypes = isset($_GET['fuelTypes']) ? $_GET['fuelTypes'] : [];
        $mileage = isset($_GET['mileage']) ? $_GET['mileage'] : '';
        $doors = isset($_GET['doors']) ? $_GET['doors'] : '';

        if (isset($transmission)) {
            // Function to filter XML based on the specified criteria
            function filter($filterContent, $transmission = '', $doors = '', $mileage = '', $fuelTypes = [])
            {
                $vehicleDetails = [];
                if (isset($filterContent->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($filterContent->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $VehAvail) {
                        $Vehicle = $VehAvail->VehAvailCore->Vehicle;

                        // Check transmission if specified
                        $transmissionMatch = $transmission ? ((string)$Vehicle['TransmissionType'] === $transmission) : true;

                        // Check doors if specified
                        $doorsMatch = $doors ? ((string)$Vehicle['DoorCount'] === $doors) : true;

                        // Check mileage if specified
                        $mileageMatch = $mileage ? ((string)$VehAvail->VehAvailCore->RentalRate['DistanceIncluded'] === $mileage) : true;

                        // Check fuel type if specified
                        $fuelTypeMatch = !empty($fuelTypes) ? in_array((string)$Vehicle['FuelType'], $fuelTypes) : true;

                        // If all conditions match, add the vehicle size to the result
                        if ($transmissionMatch && $doorsMatch && $mileageMatch && $fuelTypeMatch) {
                            $vehicleDetails[] = (string)$Vehicle->VehClass['Size']; // Now filtering by size
                        }
                    }
                }
                return $vehicleDetails;
            }

            // Apply the filtering function to each XML response
            $filteredVehiclesZE = filter($xmlresZE, $transmission); // Filter ZE vehicles
            $filteredVehiclesZT = filter($xmlresZT, $transmission); // Filter ZT vehicles
            $filteredVehiclesZR = filter($xmlresZR, $transmission); // Filter ZR vehicles

            // Function to update vehicle details array based on filtered sizes
            function filterVehicleDetailsBySize($vehicleDetails, $filteredSizes)
            {
                $filteredDetails = [];
                foreach ($vehicleDetails as $category => $vehicles) {
                    foreach ($vehicles as $vehicle) {
                        if (in_array($vehicle['size'], $filteredSizes)) {
                            $filteredDetails[$category][] = $vehicle;
                        }
                    }
                }
                return $filteredDetails;
            }

            // Apply the filtering to each vehicle detail array using size
            $vehicleDetailsZE = filterVehicleDetailsBySize($vehicleDetailsZE, $filteredVehiclesZE);
            $vehicleDetailsZT = filterVehicleDetailsBySize($vehicleDetailsZT, $filteredVehiclesZT);
            $vehicleDetailsZR = filterVehicleDetailsBySize($vehicleDetailsZR, $filteredVehiclesZR);

            // Uncomment below lines if you want to see the filtered results for debugging
            // echo "<pre>";
            // print_r($vehicleDetailsZE);
            // echo "</pre>";
        }
    }
    $cityName = $dataArray['groupName'];
    echo $cityName;
    $sql = "SELECT * FROM `filter_locations_Hertz` WHERE groupName Like '$cityName%'";
    $result = $conn->query($sql);

    // Fetch all rows into an array
    $locations = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    ?>
    <style>
        .showQuote_Hertz,
        .showQuote_Thrifty,
        .showQuote_doller {
            background-color: #d4d4d4;
            height: 27rem;
        }

        .dropoffLocationName_div_Hertz,
        .pickupLocationName_div_Hertz,
        .dropoffLocationName_div_Thrifty,
        .pickupLocationName_div_Thrifty,
        .dropoffLocationName_div_doller,
        .pickupLocationName_div_doller {
            cursor: pointer;
            overflow-y: scroll;
            height: 22rem;
        }

        .locationName_Hertz,
        .locationName_Thrifty,
        .locationName_doller {
            padding: 10px;
            border: 1px solid #d4d4d4;
        }

        .selected {
            background-color: #bfe6e6;
            color: white;
            padding: 1rem;
        }
    </style>
    <?php include 'header.php'; ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" id="popUp" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Your Next Rental</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php include 'searchWidget.php'; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="background: url('./images/res_back.jpg');background-repeat: no-repeat;
    background-attachment: local;
    background-size: 100% 100%;
    height: 14rem;">
        <div class="container align-content-center">
            <h1 class="text-white text-center">
                Explore, Discover & Save, 24,000 <br>
                Locations & Local Support
            </h1>
        </div>
    </div>
    <!-- location details -->
    <div class="row py-3 p-md-0" style="background-color: rgba(35,31,32,.5)!important">
        <div class="container row">
            <div class="col-md-10">
                <?php //foreach($results as $res):
                ?>
                <p class="text-center my-3 text-white">
                    <?php echo $pickupDetails; ?>, <?php echo $pickDate; ?> -> <?php echo $dropoffDetails; ?>, <?php echo $dropDate; ?>
                </p>
                <?php //endforeach;
                ?>
            </div>
            <div class="col-md-2 d-flex align-content-center justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target=".bd-example-modal-lg">MODIFY</button>
            </div>
        </div>
    </div>
    <!-- search dropdown desktop-->
    <div style="background-color: #ced1d4; height: auto;" class="p-3 d-none d-md-block">
        <div>
            <form class="container d-flex flex-wrap align-items-baseline" action="" method="get">
                <details>
                    <summary class="dropdown">Transmission Types <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="checkbox" name="transmission" id="automatic" value="Automatic">
                        <label for="automatic" style="font-size: 1rem;">Automatic Only</label><br>
                        <input type="checkbox" name="transmission" id="manual" value="Manual">
                        <label for="manual" style="font-size: 1rem;">Manual Only</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Fuel Type or Electric <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="checkbox" id="diesel" name="fuelTypes[]" value="Diesel">
                        <label for="diesel" style="font-size: 1rem;">Diesel</label><br>
                        <input type="checkbox" id="electric" name="fuelTypes[]" value="Electric">
                        <label for="electric" style="font-size: 1rem;">Electric</label><br>
                        <input type="checkbox" id="hybrid" name="fuelTypes[]" value="Hybrid">
                        <label for="hybrid" style="font-size: 1rem;">Hybrid</label><br>
                        <input type="checkbox" id="unspecifiedFuel" name="fuelTypes[]" value="Unspecified">
                        <label for="unspecifiedFuel" style="font-size: 1rem;">Unspecified fuel/power</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Mileage <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="radio" id="unlimited" name="mileage" value="Unlimited">
                        <label for="unlimited" style="font-size: 1rem;">Unlimited</label><br>
                        <input type="radio" id="limited" name="mileage" value="Limited">
                        <label for="limited" style="font-size: 1rem;">Limited</label>
                    </div>
                </details>
                <details>
                    <summary class="dropdown">Doors <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                    <div class="shown">
                        <input type="radio" id="doors2" name="doors" value="2">
                        <label for="doors2" style="font-size: 1rem;">2</label><br>
                        <input type="radio" id="doors4" name="doors" value="4+">
                        <label for="doors4" style="font-size: 1rem;">4+</label>
                    </div>
                </details>
                <div class="d-flex gap-3">
                    <button name="search" type="submit" class="btn btn-primary">Search</button>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <!-- search dropdown mobile-->
    <div style="background-color: #ced1d4; height: auto;padding:1rem 0.5rem" class="d-md-none">
        <div>
            <form action="" method="get">
                <div class="d-flex">
                    <div>
                        <details>
                            <summary class="dropdown">Transmission Types <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="checkbox" name="transmission" id="automatic-mobile" value="Automatic">
                                <label for="automatic-mobile" style="font-size: 1rem;">Automatic Only</label><br>
                                <input type="checkbox" name="transmission" id="manual-mobile" value="Manual">
                                <label for="manual-mobile" style="font-size: 1rem;">Manual Only</label>
                            </div>
                        </details>
                        <details>
                            <summary class="dropdown">Fuel Type or Electric <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="checkbox" id="diesel-mobile" name="fuelTypes[]" value="Diesel">
                                <label for="diesel-mobile" style="font-size: 1rem;">Diesel</label><br>
                                <input type="checkbox" id="electric-mobile" name="fuelTypes[]" value="Electric">
                                <label for="electric-mobile" style="font-size: 1rem;">Electric</label><br>
                                <input type="checkbox" id="hybrid-mobile" name="fuelTypes[]" value="Hybrid">
                                <label for="hybrid-mobile" style="font-size: 1rem;">Hybrid</label><br>
                                <input type="checkbox" id="unspecifiedFuel-mobile" name="fuelTypes[]" value="Unspecified">
                                <label for="unspecifiedFuel-mobile" style="font-size: 1rem;">Unspecified fuel/power</label>
                            </div>
                        </details>
                    </div>
                    <div>
                        <details>
                            <summary class="dropdown">Mileage <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="radio" id="unlimited-mobile" name="mileage" value="Unlimited">
                                <label for="unlimited-mobile" style="font-size: 1rem;">Unlimited</label><br>
                                <input type="radio" id="limited-mobile" name="mileage" value="Limited">
                                <label for="limited-mobile" style="font-size: 1rem;">Limited</label>
                            </div>
                        </details>
                        <details>
                            <summary class="dropdown">Doors <i class="fa-solid fa-angle-down fa-lg" style="color: #000000;"></i></summary>
                            <div class="shown">
                                <input type="radio" id="doors2-mobile" name="doors" value="2">
                                <label for="doors2-mobile" style="font-size: 1rem;">2</label><br>
                                <input type="radio" id="doors4-mobile" name="doors" value="4+">
                                <label for="doors4-mobile" style="font-size: 1rem;">4+</label>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button name="search" type="submit" class="btn btn-primary">Search</button>
                    <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-danger">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <!-- price table desktop-->
    <div id="price_table" class="container d-none d-md-block">
        <table class="table table-bordered my-4">
            <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">
                        <img src="./images/economy.jpg" alt="">
                        <p class="text-center">Economy</p>
                    </th>
                    <th scope="col">
                        <img src="./images/compact.jpg" alt="">
                        <p class="text-center">Compact </p>
                    </th>
                    <th scope="col">
                        <img src="./images/midsize.jpg" alt="">
                        <p class="text-center">Midsize</p>
                    </th>
                    <!-- <th scope="col">
                        <img src="./images/LargeSize.jpg" alt="">
                        <p class="text-center">Large Size</p>
                    </th> -->
                    <th scope="col">
                        <img src="./images/LuxurySportsCar.jpg" alt="">
                        <p class="text-center">Luxury/Sports Car </p>
                    </th>
                    <th scope="col">
                        <img src="./images/suv.jpg" alt="">
                        <p class="text-center">SUV</p>
                    </th>
                    <th scope="col">
                        <img src="./images/stationwagon.jpg" alt="">
                        <p class="text-center">Station Wagon</p>
                    </th>
                    <th scope="col">
                        <img src="./images/VanPeopleCarrier.jpg" alt="">
                        <p class="text-center">Van/People Carrier</p>
                    </th>
                    <th scope="col">
                        <img src="./images/7-12PassengerVans.jpg" alt="">
                        <p class="text-center">7-12 Passenger Vans</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr id="hertz">
                    <th id="hertz_image"><img src="./images/hertz.png" alt=""></th>
                    <?php foreach ($categories as $category => $sizes): ?>
                        <td class="text-center" data-size="<?php echo implode(',', $sizes); ?>">
                            <?php if (isset($vehicleDetailsZE[$category])): ?>
                                <?php foreach ($vehicleDetailsZE[$category] as $details): ?>
                                    <?php
                                    // Output the rate and the code
                                    echo 'AUD ' . number_format(calculatePercentage($markUp, $details['rate']), 2);
                                    ?>
                                    <br>
                                    <!-- <input type="text" value="<?php echo $details['code']; ?>" hidden> -->
                                    <?php break; // Break to only show the first vehicle of each category 
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Not Available
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr id="doller">
                    <th id="doller_image"><img src="./images/DOLLARRet.png" alt=""></th>

                    <?php foreach ($categories as $category => $sizes): ?>
                        <td class="text-center" data-size="<?php echo implode(',', $sizes); ?>">
                            <?php if (isset($vehicleDetailsZR[$category])): ?>
                                <?php foreach ($vehicleDetailsZR[$category] as $details): ?>
                                    <?php
                                    // Output the rate and the code
                                    echo 'AUD ' . number_format(calculatePercentage($markUp, $details['rate']), 2);
                                    ?>
                                    <br>
                                    <!-- <input type="text" value="<?php echo $details['code']; ?>" hidden> -->
                                    <?php break; // Break to only show the first vehicle of each category 
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Not Available
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <tr id="thrifty">
                    <th id="thrifty_image"><img src="./images/thrifty.png" alt=""></th>

                    <?php foreach ($categories as $category => $sizes): ?>
                        <td class="text-center" data-size="<?php echo implode(',', $sizes); ?>">
                            <?php if (isset($vehicleDetailsZT[$category])): ?>
                                <?php foreach ($vehicleDetailsZT[$category] as $details): ?>
                                    <?php
                                    // Output the rate and the code
                                    echo 'AUD ' . number_format(calculatePercentage($markUp, $details['rate']), 2);
                                    ?>
                                    <br>
                                    <!-- <input type="text" value="<?php echo $details['code']; ?>" hidden> -->
                                    <?php break; // Break to only show the first vehicle of each category 
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                Not Available
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

            </tbody>
        </table>
    </div>
    <!-- price table mobile-->
    <div id="price_table_mobile" class="container d-md-none">
        <table class="table table-bordered my-4">
            <thead>
                <tr>
                    <th></th>
                    <th><img src="./images/hertz.png" alt=""></th>
                    <th><img src="./images/DOLLARRet.png" alt=""></th>
                    <th><img src="./images/thrifty.png" alt=""></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category => $sizes): ?>
                    <tr>
                        <td scope="col" class="text-center align-middle">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <img src="./images/<?php echo strtolower($category); ?>.jpg" alt="" style="max-width: 100px; height: auto;">
                                <p style="text-align: center; width: 100%; word-wrap: break-word; margin-top: 5px;">
                                    <?php echo ucfirst($category); ?>
                                </p>
                            </div>
                        </td>

                        <?php foreach (['hertz', 'dollar', 'thrifty'] as $company): ?>
                            <td class="text-center mobile" data-size="<?php echo implode(',', $sizes); ?>">
                                <?php
                                // Determine which array to use based on the company
                                $vehicleDetails = null;
                                if ($company == 'hertz') {
                                    $vehicleDetails = $vehicleDetailsZE;
                                } elseif ($company == 'dollar') {
                                    $vehicleDetails = $vehicleDetailsZR;
                                } elseif ($company == 'thrifty') {
                                    $vehicleDetails = $vehicleDetailsZT;
                                }

                                // Check if the category exists in the selected array
                                if (isset($vehicleDetails[$category])):
                                    $details = $vehicleDetails[$category];
                                    // Check if the 'rate' key exists and match with size
                                    foreach ($details as $detail) {
                                        if (isset($detail['rate']) && in_array($detail['size'], $sizes)) {
                                            $val = calculatePercentage($markUp, $detail['rate']);
                                            echo 'AUD ' . number_format($val, 2);
                                            break;
                                        }
                                    }
                                ?>
                                    <br>
                                    <?php
                                    // Check if the 'size' key exists before displaying it
                                    if (isset($detail['size'])): ?>
                                        <input type="text" value="<?php echo $detail['size']; ?>" hidden>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Not Available
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- result line desktop-->
    <div class="d-none d-md-block">
        <div style="background-color: #ced1d4; height: auto; display: none;" class="p-3" id="results-count-container">
            <div class="container d-flex justify-content-start text-white">
                <span id="results-count">SHOWING 0 RESULTS</span>
            </div>
        </div>
    </div>
    <!-- result line mobile-->
    <div class="d-md-none">
        <div style="background-color: #ced1d4; height: auto; display: none;" class="p-3" id="results-count-container-mobile">
            <div class="container d-flex justify-content-start text-white">
                <span id="results-count-mobile">SHOWING 0 RESULTS</span>
            </div>
        </div>
    </div>
    <!-- results cards ZE desktop-->
    <div class="d-none d-md-block">
        <div class="container">
            <div id="vehicle-list-hertz" class="vehicle-list">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZE->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($xmlresZE->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $vendorId = (string)$xmlresZE->VehAvailRSCore->VehVendorAvails->VehVendorAvail->Vendor['Code'];
                        $Code = (string)$vehicle->VehAvailCore->Vehicle['Code'];
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/hertz.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card res_hertz vehicle-item" data-size="' . $size . '" style="display: none;">
                            <div class="row">
                                <div class="col-4 d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div class="col-4">
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a style="height:2.5rem;" class="btn btn-primary showLocation_Hertz" id="showLocation_Hertz' . $Code . '" data-VendorId="' . $vendorId . '">Show Locations</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        echo '
                                            <div class="row show_Hertz d-none" id="showQuote_Hertz_' . $Code . '">
                                                <!-- Pick Up Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Pick Up Location</p>
                                                    </div>
                                                    <div class="pickupLocationName_div_Hertz" id="pickupLocationName_div_Hertz_' . $Code . '">';
                        foreach ($locations as $row) {
                            echo '
                                                        <p class="locationName_Hertz" id="locationName_Hertz_' . $Code . '" data-euro="' . htmlspecialchars($row['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>
                                                
                                                <!-- Drop Off Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Drop Off Location</p>
                                                    </div>
                                                    <div class="dropoffLocationName_div_Hertz" id="dropoffLocationName_div_Hertz_' . $Code . '">';
                        foreach ($locations as $row1) {
                            echo '
                                                        <p class="locationName_Hertz"  id="locationName_Hertz_' . $Code . '" data-euro="' . htmlspecialchars($row1['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row1['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>

                                                <!-- Payment Information -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Payment Information</p>
                                                    </div>
                                                    <div class="res_pay" id="Pay_div">
                                                        <div>
                                                            <p>Insurances Package</p>
                                                            <p>Rates starting at ...</p>
                                                        </div>
                                                    </div>
                                                    <div class="res_pay">
                                                        <div class="d-flex">
                                                            <a href="book.php?reference=' . $reference . '&vdNo=ZE"; class="btn btn-primary">BOOK NOW</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZE mobile-->
    <div class="d-md-none">
        <div>
            <div id="vehicle-list-hertz-mobile" class="vehicle-list-mobile container">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZE->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($xmlresZE->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/hertz.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card vehicle-item-mobile" data-size="' . $size . '" style="display: none;">
                            <div>
                                <div class="d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div>
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div>
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a href="book.php?reference=' . $reference . '&vdNo=ZE"; class="btn btn-primary">BOOK NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZT desktop-->
    <div class="d-none d-md-block">
        <div class="container">
            <div id="vehicle-list-thrifty" class="vehicle-list" style="display:none;">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZT->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($xmlresZT->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $vendorId = (string)$xmlresZT->VehAvailRSCore->VehVendorAvails->VehVendorAvail->Vendor['Code'];
                        $Code = (string)$vehicle->VehAvailCore->Vehicle['Code'];
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/thrifty.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card res_thrifty vehicle-item" data-size="' . $size . '" style="display: none;">
                            <div class="row">
                                <div class="col-4 d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div class="col-4">
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a style="height:2.5rem;" class="btn btn-primary showLocation_Thrifty" id="showLocation_Thrifty' . $Code . '" data-Thrifty="' . $vendorId . '">Show Locations</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        echo '
                                            <div class="row show_Thrifty d-none" id="showQuote_Thrifty_' . $Code . '">
                                                <!-- Pick Up Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Pick Up Location</p>
                                                    </div>
                                                    <div class="pickupLocationName_div_Thrifty" id="pickupLocationName_div_Thrifty_' . $Code . '">';
                        foreach ($locations as $row_thrifty) {
                            echo '
                                                        <p class="locationName_Thrifty" id="locationName_Thrifty_' . $Code . '" data-euro="' . htmlspecialchars($row_thrifty['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row_thrifty['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>
                                                
                                                <!-- Drop Off Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Drop Off Location</p>
                                                    </div>
                                                    <div class="dropoffLocationName_div_Thrifty" id="dropoffLocationName_div_Thrifty_' . $Code . '">';
                        foreach ($locations as $row_thrifty1) {
                            echo '
                                                        <p class="locationName_Thrifty"  id="locationName_Thrifty_' . $Code . '" data-euro="' . htmlspecialchars($row_thrifty1['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row_thrifty1['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>

                                                <!-- Payment Information -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Payment Information</p>
                                                    </div>
                                                    <div class="res_pay" id="Pay_div">
                                                        <div>
                                                            <p>Insurances Package</p>
                                                            <p>Rates starting at ...</p>
                                                        </div>
                                                    </div>
                                                    <div class="res_pay">
                                                        <div class="d-flex">
                                                            <a href="book.php?reference=' . $reference . '&vdNo=ZT"; class="btn btn-primary">BOOK NOW</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZT mobile-->
    <div class="d-md-none">
        <div>
            <div id="vehicle-list-thrifty-mobile" class="vehicle-list-mobile container">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZT->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($xmlresZT->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/thrifty.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card vehicle-item-mobile" data-size="' . $size . '" style="display: none;">
                            <div>
                                <div class="d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div>
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div>
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a href="book.php?reference=' . $reference . '&vdNo=ZT"; class="btn btn-primary">BOOK NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZR desktop-->
    <div class="d-none d-md-block">
        <div class="container">
            <div id="vehicle-list-doller" class="vehicle-list" style="display:none;">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZR->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    // Function to calculate the percentage

                    foreach ($xmlresZR->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $vendorId = (string)$xmlresZR->VehAvailRSCore->VehVendorAvails->VehVendorAvail->Vendor['Code'];
                        $Code = (string)$vehicle->VehAvailCore->Vehicle['Code'];
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/DOLLARRet.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card res_doller vehicle-item" data-size="' . $size . '" style="display: none;">
                            <div class="row">
                                <div class="col-4 d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div class="col-4">
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a style="height:2.5rem;" class="btn btn-primary showLocation_doller" id="showLocation_doller' . $Code . '" data-doller="' . $vendorId . '">Show Locations</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        echo '
                                            <div class="row show_doller d-none" id="showQuote_doller_' . $Code . '">
                                                <!-- Pick Up Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Pick Up Location</p>
                                                    </div>
                                                    <div class="pickupLocationName_div_doller" id="pickupLocationName_div_doller_' . $Code . '">';
                        foreach ($locations as $row_doller) {
                            echo '
                                                        <p class="locationName_doller" id="locationName_doller_' . $Code . '" data-euro="' . htmlspecialchars($row_doller['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row_doller['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>
                                                
                                                <!-- Drop Off Location -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Drop Off Location</p>
                                                    </div>
                                                    <div class="dropoffLocationName_div_doller" id="dropoffLocationName_div_doller_' . $Code . '">';
                        foreach ($locations as $row_doller1) {
                            echo '
                                                        <p class="locationName_doller"  id="locationName_doller_' . $Code . '" data-euro="' . htmlspecialchars($row_doller1['citycode'], ENT_QUOTES) . '">' . htmlspecialchars($row_doller1['cityaddress'], ENT_QUOTES) . '</p>';
                        }
                        echo '
                                                    </div>
                                                </div>

                                                <!-- Payment Information -->
                                                <div class="col-4">
                                                    <div>
                                                        <p class="text-center mt-3">Payment Information</p>
                                                    </div>
                                                    <div class="res_pay" id="Pay_div">
                                                        <div>
                                                            <p>Insurances Package</p>
                                                            <p>Rates starting at ...</p>
                                                        </div>
                                                    </div>
                                                    <div class="res_pay">
                                                        <div class="d-flex">
                                                            <a href="book.php?reference=' . $reference . '&vdNo=ZR"; class="btn btn-primary">BOOK NOW</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                var_dump($final);
                ?>
            </div>
        </div>
    </div>
    <!-- results cards ZR mobile-->
    <div class="d-md-none">
        <div>
            <div id="vehicle-list-dollar-mobile" class="vehicle-list-mobile container">
                <?php
                // Loop through each vehicle in the XML
                if (isset($xmlresZR->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail)) {
                    foreach ($xmlresZR->VehAvailRSCore->VehVendorAvails->VehVendorAvail->VehAvails->VehAvail as $vehicle) {
                        // Get the vehicle details
                        $size = (int)$vehicle->VehAvailCore->Vehicle->VehClass['Size'];
                        $name = (string)$vehicle->VehAvailCore->Vehicle->VehMakeModel['Name'];
                        $transmission = (string)$vehicle->VehAvailCore->Vehicle['TransmissionType'];
                        $passengers = (string)$vehicle->VehAvailCore->Vehicle['PassengerQuantity'];
                        $luggage = (string)$vehicle->VehAvailCore->Vehicle['BaggageQuantity'];
                        $rate = (float)$vehicle->VehAvailCore->TotalCharge['RateTotalAmount'];
                        $final = calculatePercentage($markUp, $rate);
                        $currency = (string)$vehicle->VehAvailCore->TotalCharge['CurrencyCode'];
                        $vendor = (string)$vehicle->VehAvailCore->Vehicle['VendorName'] ?? 'Vendor';
                        $image = "https://images.hertz.com/vehicles/220x128/" . (string)$vehicle->VehAvailCore->Vehicle->PictureURL;
                        $vendorLogo = "./images/DOLLARRet.png"; // Use dynamic logos if needed
                        $reference = (string)$vehicle->VehAvailCore->Reference['ID'];

                        // Output the HTML for each vehicle, hide them initially
                        echo '
                        <div class="res_card vehicle-item-mobile" data-size="' . $size . '" style="display: none;">
                            <div>
                                <div class="d-grid">
                                    <img style="width:20rem;" src="' . $image . '" alt="' . $name . '">
                                    <img src="' . $vendorLogo . '" alt="' . $vendor . '">
                                </div>
                                <div>
                                    <strong>' . $name . '</strong>
                                    <p>OR SIMILAR | ' . strtoupper($transmission) . ' CLASS</p>
                                    <div class="d-flex gap-2 my-3">
                                        <div class="car_spec">' . ucfirst($transmission) . '</div>
                                        <div class="car_spec">
                                            <img src="./images/door-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/person-icon.png" alt="">' . $passengers . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/S-luggage-icon.png" alt="">' . $luggage . '
                                        </div>
                                        <div class="car_spec">
                                            <img src="./images/snow-icon.png" alt="">
                                        </div>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/plane-icon.png" alt="">
                                        <label for=""> On Airport</label>
                                    </div>
                                    <div class="car_info mb-1">
                                        <img src="./images/km-icon.png" alt="">
                                        <label for=""> Unlimited Kilometres</label>
                                    </div>
                                    <div class="text-primary" style="">
                                        + Terms and Conditions
                                    </div>
                                </div>
                                <div>
                                    <div class="res_pay">
                                        <div>
                                            <p>Insurances Package</p>
                                            <p>Rates starting at ...</p>
                                        </div>
                                        <div>
                                            <p>' . $currency . ' ' . $final . '</p>
                                        </div>
                                    </div>
                                    <div class="res_pay">
                                        <div class="d-flex">
                                            <a href="book.php?reference=' . $reference . '&vdNo=ZR"; class="btn btn-primary">BOOK NOW</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>No vehicles available</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        //for hertz
        let carCategory_Hertz = null; // Declare globally to access across functions
        let pickupSelected_Hertz = false;
        let vendorId_Hertz = null;
        let dropoffSelected_Hertz = false;
        let infoObject_Hertz = {};
        let pickupData_Hertz = {};
        let dropoffData_Hertz = {};

        // Handle the "Show Locations" button click
        document.querySelectorAll('.showLocation_Hertz').forEach(function(e) {
            e.addEventListener('click', function(event) {
                carCategory_Hertz = event.target.id.replace("showLocation_Hertz", ""); // Assign the car category
                vendorId_Hertz = event.target.dataset.vendorid;
                console.log("Selected car category:", carCategory_Hertz);
                console.log("Selected vendor ID:", vendorId_Hertz);
                infoObject_Hertz = {
                    pickUpDateTime: <?php echo json_encode($dataArray['pickUpDateTime']); ?>,
                    dropOffDateTime: <?php echo json_encode($dataArray['dropOffDateTime']); ?>,
                    vendorId: vendorId_Hertz,
                    carCategory: carCategory_Hertz
                };
                console.log(infoObject_Hertz);
                // Show the correct "Show Quotes" section and hide others
                document.querySelectorAll('.show_Hertz').forEach(function(el) {
                    if (el.id === `showQuote_Hertz_${carCategory_Hertz}`) {
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                });

                // Reset pickup and dropoff selections when switching categories
                pickupSelected_Hertz = false;
                dropoffSelected_Hertz = false;
                pickupData_Hertz = {};
                dropoffData_Hertz = {};
            });
        });

        // Handle the selection of pickup and dropoff locations
        function handleSelection(event, type) {
            const targetDiv =
                type === "pickup" ?
                `#pickupLocationName_div_Hertz_${carCategory_Hertz}` :
                `#dropoffLocationName_div_Hertz_${carCategory_Hertz}`;

            const selectedData = {
                hertz: event.target.getAttribute("data-hertz"),
                euro: event.target.getAttribute("data-euro"),
            };

            console.log(
                `${type.charAt(0).toUpperCase() + type.slice(1)} location: Hertz - ${
            selectedData.hertz
        }, Euro - ${selectedData.euro}`
            );

            if (selectedData.hertz || selectedData.euro) {
                // Clear any previously selected location
                const prevSelected = document.querySelector(`${targetDiv} .selected`);
                if (prevSelected) prevSelected.classList.remove("selected");

                // Mark the new location as selected
                event.target.classList.add("selected");

                if (type === "pickup") {
                    pickupData_Hertz = selectedData;
                    pickupSelected_Hertz = true;
                } else {
                    dropoffData_Hertz = selectedData;
                    dropoffSelected_Hertz = true;
                }

                console.log("Pickup Data:", pickupData_Hertz);
                console.log("Dropoff Data:", dropoffData_Hertz);
            }

            // Trigger quote retrieval when both locations are selected
            if (pickupSelected_Hertz && dropoffSelected_Hertz) {
                callGetQuoteHertz();
            }
        }

        // Add click listeners for pickup and dropoff locations
        document.addEventListener("click", function(event) {
            // Match pickup location clicks
            if (
                event.target &&
                event.target.matches(`.pickupLocationName_div_Hertz p`)
            ) {
                handleSelection(event, "pickup");
            }

            // Match dropoff location clicks
            if (
                event.target &&
                event.target.matches(`.dropoffLocationName_div_Hertz p`)
            ) {
                handleSelection(event, "dropoff");
            }
        });

        // console.log("infoObject_Hertz:", infoObject_Hertz);
        // Function to call the server for a quote
        function callGetQuoteHertz() {
            console.log("Calling getQuote with:");
            console.log("Car Category:", carCategory_Hertz);
            console.log("Pickup Data:", pickupData_Hertz);
            console.log("Dropoff Data:", dropoffData_Hertz);

            if (!carCategory_Hertz) {
                console.error("Car category is not selected!");
                return;
            }

            const data = {
                carCategory: infoObject_Hertz.carCategory,
                vendorId: vendorId_Hertz,
                pickup: pickupData_Hertz['euro'],
                dropOff: dropoffData_Hertz['euro'],
                pickUpDateTime: infoObject_Hertz.pickUpDateTime,
                dropOffDateTime: infoObject_Hertz.dropOffDateTime
            };

            console.log("getQuote Data:", data);

            fetch("getQuote.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                })
                .then((response) => response.text()) // Parse as text to debug issues
                .then((text) => {
                    try {
                        const json = JSON.parse(text); // Try parsing as JSON
                        console.log("Quote Response:", json);

                        // Check if the results array exists
                        if (Array.isArray(json.results) && json.results.length > 0) {
                            // Iterate over the results
                            json.results.forEach((item) => {
                                if (item.Name && item.Code && item.RateTotalAmount && item.CurrencyCode) {
                                    const {
                                        Name,
                                        Code,
                                        RateTotalAmount,
                                        CurrencyCode
                                    } = item;

                                    // Select the payment info div for the corresponding car category
                                    const paymentInfoDiv = document.querySelector(
                                        `#showQuote_Hertz_${Code} #Pay_div`
                                    );

                                    // Update the HTML if the div exists
                                    if (paymentInfoDiv) {
                                        paymentInfoDiv.innerHTML += `
                                            <div>
                                                <p>Rental Rate:${CurrencyCode} ${RateTotalAmount}</p>
                                            </div>
                                        `;
                                    }
                                } else {
                                    console.error("Missing data in one of the results:", item);
                                }
                            });
                        } else {
                            console.error("No results found in the response", json);
                        }
                    } catch (error) {
                        console.error("Failed to parse JSON response:", text);
                    }
                })
                .catch((error) => console.error("Fetch error:", error));

        }
        // for thrifty

        let carCategory_Thrifty = null; // Declare globally to access across functions
        let pickupSelected_Thrifty = false;
        let vendorIdThrifty = null;
        let dropoffSelected_Thrifty = false;
        let infoObject_Thrifty = {};
        let pickupData_Thrifty = {};
        let dropoffData_Thrifty = {};

        // Handle the "Show Locations" button click
        document.querySelectorAll('.showLocation_Thrifty').forEach(function(e) {
            e.addEventListener('click', function(event) {
                console.log(event);
                carCategory_Thrifty = event.target.id.replace("showLocation_Thrifty", ""); // Assign the car category
                vendorIdThrifty = event.target.dataset.thrifty;
                console.log("Selected car category:", carCategory_Thrifty);
                console.log("Selected vendor ID:", vendorIdThrifty);
                infoObject_Thrifty = {
                    pickUpDateTime: <?php echo json_encode($dataArray['pickUpDateTime']); ?>,
                    dropOffDateTime: <?php echo json_encode($dataArray['dropOffDateTime']); ?>,
                    vendorId: vendorIdThrifty,
                    carCategory: carCategory_Thrifty
                };
                console.log(infoObject_Thrifty);
                // Show the correct "Show Quotes" section and hide others
                document.querySelectorAll('.show_Thrifty').forEach(function(el) {
                    if (el.id === `showQuote_Thrifty_${carCategory_Thrifty}`) {
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                });

                // Reset pickup and dropoff selections when switching categories
                pickupSelected_Thrifty = false;
                dropoffSelected_Thrifty = false;
                pickupData_Thrifty = {};
                dropoffData_Thrifty = {};
            });
        });

        // Handle the selection of pickup and dropoff locations
        function handleSelection(event, type) {
            const targetDiv =
                type === "pickup" ?
                `#pickupLocationName_div_Thrifty_${carCategory_Thrifty}` :
                `#dropoffLocationName_div_Thrifty_${carCategory_Thrifty}`;

            const selectedData = {
                hertz: event.target.getAttribute("data-hertz"),
                euro: event.target.getAttribute("data-euro"),
            };

            console.log(
                `${type.charAt(0).toUpperCase() + type.slice(1)} location: Hertz - ${
            selectedData.hertz
        }, Euro - ${selectedData.euro}`
            );

            if (selectedData.hertz || selectedData.euro) {
                // Clear any previously selected location
                const prevSelected = document.querySelector(`${targetDiv} .selected`);
                if (prevSelected) prevSelected.classList.remove("selected");

                // Mark the new location as selected
                event.target.classList.add("selected");

                if (type === "pickup") {
                    pickupData_Thrifty = selectedData;
                    pickupSelected_Thrifty = true;
                } else {
                    dropoffData_Thrifty = selectedData;
                    dropoffSelected_Thrifty = true;
                }

                console.log("Pickup Data:", pickupData_Thrifty);
                console.log("Dropoff Data:", dropoffData_Thrifty);
            }

            // Trigger quote retrieval when both locations are selected
            if (pickupSelected_Thrifty && dropoffSelected_Thrifty) {
                callGetQuoteThrifty();
            }
        }

        // Add click listeners for pickup and dropoff locations
        document.addEventListener("click", function(event) {
            // Match pickup location clicks
            if (
                event.target &&
                event.target.matches(`.pickupLocationName_div_Thrifty p`)
            ) {
                handleSelection(event, "pickup");
            }

            // Match dropoff location clicks
            if (
                event.target &&
                event.target.matches(`.dropoffLocationName_div_Thrifty p`)
            ) {
                handleSelection(event, "dropoff");
            }
        });

        // console.log("infoObject_Thrifty:", infoObject_Thrifty);
        // Function to call the server for a quote
        function callGetQuoteThrifty() {
            console.log("Calling getQuote with:");
            console.log("Car Category:", carCategory_Thrifty);
            console.log("Pickup Data:", pickupData_Thrifty);
            console.log("Dropoff Data:", dropoffData_Thrifty);

            if (!carCategory_Thrifty) {
                console.error("Car category is not selected!");
                return;
            }

            const data = {
                carCategory: infoObject_Thrifty.carCategory,
                vendorId: vendorIdThrifty,
                pickup: pickupData_Thrifty['euro'],
                dropOff: dropoffData_Thrifty['euro'],
                pickUpDateTime: infoObject_Thrifty.pickUpDateTime,
                dropOffDateTime: infoObject_Thrifty.dropOffDateTime
            };

            console.log("getQuote Data:", data);

            fetch("getQuote.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                })
                .then((response) => response.text()) // Parse as text to debug issues
                .then((text) => {
                    try {
                        const json = JSON.parse(text); // Try parsing as JSON
                        console.log("Quote Response:", json);

                        // Check if the results array exists
                        if (Array.isArray(json.results) && json.results.length > 0) {
                            // Iterate over the results
                            json.results.forEach((item) => {
                                if (item.Name && item.Code && item.RateTotalAmount && item.CurrencyCode) {
                                    const {
                                        Name,
                                        Code,
                                        RateTotalAmount,
                                        CurrencyCode
                                    } = item;

                                    // Select the payment info div for the corresponding car category
                                    const paymentInfoDiv = document.querySelector(
                                        `#showQuote_Thrifty_${Code} #Pay_div`
                                    );

                                    // Update the HTML if the div exists
                                    if (paymentInfoDiv) {
                                        paymentInfoDiv.innerHTML += `
                                        <div>
                                            <p>Rental Rate:${CurrencyCode} ${RateTotalAmount}</p>
                                        </div>
                                        `;
                                    }
                                } else {
                                    console.error("Missing data in one of the results:", item);
                                }
                            });
                        } else {
                            console.error("No results found in the response", json);
                        }
                    } catch (error) {
                        console.error("Failed to parse JSON response:", text);
                    }
                })
                .catch((error) => console.error("Fetch error:", error));
        }

        // for doller

        let carCategory_doller = null; // Declare globally to access across functions
        let pickupSelected_doller = false;
        let vendorIddoller = null;
        let dropoffSelected_doller = false;
        let infoObject_doller = {};
        let pickupData_doller = {};
        let dropoffData_doller = {};

        // Handle the "Show Locations" button click
        document.querySelectorAll('.showLocation_doller').forEach(function(e) {
            e.addEventListener('click', function(event) {
                console.log(event);

                // Extract car category and vendor ID
                carCategory_doller = event.target.id.replace("showLocation_doller", ""); // Assign the car category
                vendorIddoller = event.target.dataset.doller;
                console.log("Selected car category:", carCategory_doller);
                console.log("Selected vendor ID:", vendorIddoller);

                // Info object for the selected car category
                infoObject_doller = {
                    pickUpDateTime: <?php echo json_encode($dataArray['pickUpDateTime']); ?>,
                    dropOffDateTime: <?php echo json_encode($dataArray['dropOffDateTime']); ?>,
                    vendorId: vendorIddoller,
                    carCategory: carCategory_doller
                };
                console.log("infoObject_doller:", infoObject_doller);

                // Show the correct "Show Quotes" section and hide others
                document.querySelectorAll('.show_doller').forEach(function(el) {
                    console.log(`Checking element with id: ${el.id}`);
                    if (el.id === `showQuote_doller_${carCategory_doller}`) {
                        console.log(`Matching element found: ${el.id}`);
                        el.classList.remove('d-none');
                    } else {
                        el.classList.add('d-none');
                    }
                });

                // Reset pickup and dropoff selections
                pickupSelected_doller = false;
                dropoffSelected_doller = false;
                pickupData_doller = {};
                dropoffData_doller = {};
            });
        });



        // Handle the selection of pickup and dropoff locations
        function handleSelection(event, type) {
            const targetDiv =
                type === "pickup" ?
                `#pickupLocationName_div_doller_${carCategory_doller}` :
                `#dropoffLocationName_div_doller_${carCategory_doller}`;

            const selectedData = {
                hertz: event.target.getAttribute("data-hertz"),
                euro: event.target.getAttribute("data-euro"),
            };

            console.log(
                `${type.charAt(0).toUpperCase() + type.slice(1)} location: Hertz - ${
            selectedData.hertz
        }, Euro - ${selectedData.euro}`
            );

            if (selectedData.hertz || selectedData.euro) {
                // Clear any previously selected location
                const prevSelected = document.querySelector(`${targetDiv} .selected`);
                if (prevSelected) prevSelected.classList.remove("selected");

                // Mark the new location as selected
                event.target.classList.add("selected");

                if (type === "pickup") {
                    pickupData_doller = selectedData;
                    pickupSelected_doller = true;
                } else {
                    dropoffData_doller = selectedData;
                    dropoffSelected_doller = true;
                }

                console.log("Pickup Data:", pickupData_doller);
                console.log("Dropoff Data:", dropoffData_doller);
            }

            // Trigger quote retrieval when both locations are selected
            if (pickupSelected_doller && dropoffSelected_doller) {
                callGetQuoteDoller();
            }
        }

        // Add click listeners for pickup and dropoff locations
        document.addEventListener("click", function(event) {
            // Match pickup location clicks
            if (
                event.target &&
                event.target.matches(`.pickupLocationName_div_doller p`)
            ) {
                handleSelection(event, "pickup");
            }

            // Match dropoff location clicks
            if (
                event.target &&
                event.target.matches(`.dropoffLocationName_div_doller p`)
            ) {
                handleSelection(event, "dropoff");
            }
        });

        // console.log("infoObject_doller:", infoObject_doller);
        // Function to call the server for a quote
        function callGetQuoteDoller() {
            console.log("Calling getQuote with:");
            console.log("Car Category:", carCategory_doller);
            console.log("Pickup Data:", pickupData_doller);
            console.log("Dropoff Data:", dropoffData_doller);

            if (!carCategory_doller) {
                console.error("Car category is not selected!");
                return;
            }

            const data = {
                carCategory: infoObject_doller.carCategory,
                vendorId: vendorIddoller,
                pickup: pickupData_doller['euro'],
                dropOff: dropoffData_doller['euro'],
                pickUpDateTime: infoObject_doller.pickUpDateTime,
                dropOffDateTime: infoObject_doller.dropOffDateTime
            };

            console.log("getQuote Data:", data);

            fetch("getQuote.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                })
                .then((response) => response.text()) // Parse as text to debug issues
                .then((text) => {
                    try {
                        const json = JSON.parse(text); // Try parsing as JSON
                        console.log("Quote Response:", json);

                        // Check if the results array exists
                        if (Array.isArray(json.results) && json.results.length > 0) {
                            // Iterate over the results
                            json.results.forEach((item) => {
                                if (item.Name && item.Code && item.RateTotalAmount && item.CurrencyCode) {
                                    const {
                                        Name,
                                        Code,
                                        RateTotalAmount,
                                        CurrencyCode
                                    } = item;

                                    // Select the payment info div for the corresponding car category
                                    const paymentInfoDiv = document.querySelector(
                                        `#showQuote_doller_${Code} #Pay_div`
                                    );

                                    // Update the HTML if the div exists
                                    if (paymentInfoDiv) {
                                        paymentInfoDiv.innerHTML += `
                                        <div>
                                            <p>Rental Rate:${CurrencyCode} ${RateTotalAmount}</p>
                                        </div>
                                        `;
                                    }
                                } else {
                                    console.error("Missing data in one of the results:", item);
                                }
                            });
                        } else {
                            console.error("No results found in the response", json);
                        }
                    } catch (error) {
                        console.error("Failed to parse JSON response:", text);
                    }
                })
                .catch((error) => console.error("Fetch error:", error));
        }

        document.querySelectorAll('td.text-center').forEach(function(td) {
            td.addEventListener('click', function() {
                var selectedSizes = td.getAttribute('data-size').split(','); // Get the list of sizes for the selected category
                var vendorRow = td.closest('tr').id; // Get the id of the closest row to identify the vendor (e.g., hertz, dollar, thrifty)

                // Hide all vehicle lists first
                document.querySelectorAll('.vehicle-list').forEach(function(list) {
                    list.style.display = 'none'; // Hide all lists
                });

                // Show the specific vendor's vehicle list
                var vendorList = document.getElementById('vehicle-list-' + vendorRow);
                vendorList.style.display = 'block';

                // Hide all vehicles in this list first
                vendorList.querySelectorAll('.vehicle-item').forEach(function(vehicle) {
                    vehicle.style.display = 'none';
                });

                // Show the vehicles that match the selected sizes
                var vehiclesShown = false; // Track whether we display any vehicles
                var vehicleCount = 0; // Track how many vehicles are displayed

                selectedSizes.forEach(function(size) {
                    var matchingVehicles = vendorList.querySelectorAll('.vehicle-item[data-size="' + size + '"]');
                    matchingVehicles.forEach(function(vehicle) {
                        vehicle.style.display = 'block';
                        vehiclesShown = true;
                        vehicleCount++; // Increment the count for each shown vehicle
                    });
                });

                // If no vehicles are shown, handle the empty case
                if (!vehiclesShown) {
                    alert('No matching vehicles found.');
                }

                // Update the results count dynamically
                if (vehicleCount > 0) {
                    document.getElementById('results-count').innerText = 'SHOWING ' + vehicleCount + ' RESULTS';
                    document.getElementById('results-count-container').style.display = 'block'; // Show the results count section
                } else {
                    document.getElementById('results-count-container').style.display = 'none'; // Hide the results count if no vehicles are found
                }
            });
        });


        document.querySelectorAll('td.mobile').forEach(function(td) {
            td.addEventListener('click', function() {
                var selectedSizes = td.getAttribute('data-size').split(','); // Get the list of sizes for the selected category

                // Convert the NodeList to an array and then find the index of the clicked td
                var allTds = Array.prototype.slice.call(td.closest('tr').querySelectorAll('td.mobile'));
                var companyIndex = allTds.indexOf(td); // Find the index of the clicked td within the row

                // Define the list of companies in the same order as in the HTML
                var companies = ['hertz', 'dollar', 'thrifty'];

                // Get the company based on the index
                var vendorRow = companies[companyIndex];

                console.log('vendorRow:', vendorRow); // Debugging line

                if (!vendorRow) {
                    console.error('Could not find a valid vendor row ID.');
                    return; // Exit if vendorRow is not valid
                }

                // Hide all vehicle lists first
                document.querySelectorAll('.vehicle-list-mobile').forEach(function(list) {
                    list.style.display = 'none'; // Hide all lists
                });

                // Construct the ID for the specific vendor's vehicle list
                var vendorList = document.getElementById('vehicle-list-' + vendorRow + '-mobile');

                // Check if the vendorList exists
                if (vendorList) {
                    vendorList.style.display = 'block';

                    // Hide all vehicles in this list first
                    vendorList.querySelectorAll('.vehicle-item-mobile').forEach(function(vehicle) {
                        vehicle.style.display = 'none';
                    });

                    // Show the vehicles that match the selected sizes
                    var vehiclesShown = false; // Track whether we display any vehicles
                    var vehicleCount = 0; // Track how many vehicles are displayed

                    selectedSizes.forEach(function(size) {
                        var matchingVehicles = vendorList.querySelectorAll('.vehicle-item-mobile[data-size="' + size + '"]');
                        matchingVehicles.forEach(function(vehicle) {
                            vehicle.style.display = 'block';
                            vehiclesShown = true;
                            vehicleCount++; // Increment the count for each shown vehicle
                        });
                    });

                    // If no vehicles are shown, handle the empty case
                    if (!vehiclesShown) {
                        alert('No matching vehicles found.');
                    }

                    // Update the results count dynamically
                    if (vehicleCount > 0) {
                        document.getElementById('results-count-mobile').innerText = 'SHOWING ' + vehicleCount + ' RESULTS';
                        document.getElementById('results-count-container-mobile').style.display = 'block'; // Show the results count section
                    } else {
                        document.getElementById('results-count-container-mobile').style.display = 'none'; // Hide the results count if no vehicles are found
                    }
                } else {
                    console.error('Vehicle list with ID "vehicle-list-' + vendorRow + '-mobile" not found.');
                }
            });
        });
    })
</script>

</html>