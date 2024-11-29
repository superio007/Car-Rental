<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    <link rel="stylesheet"
        href="https://code.jquery.com/ui/1.14.0/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.0/jquery-ui.js"></script>
    <link rel="stylesheet"
        href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
    require 'dbconn.php';
    $sql = "SELECT 
        MIN(Id) AS Id, -- This ensures one entry per group (the one with the smallest Id)
        Vendor_Id, 
        citycode, 
        country, 
        city, 
        cityaddress, 
        groupName
    FROM 
        filter_locations_hertz
    WHERE 
        groupName IS NOT NULL AND groupName != '0'
    GROUP BY 
        groupName;
    ";
    // $sql = "SELECT * FROM airport_list";
    $result = $conn->query($sql);

    $res = $result->fetch_assoc();

    function makeApiCall($vendorID, $pickup, $dropOff, $pickUpDateTime, $dropOffDateTime)
    {
        $xmlRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8" ?>
        <OTA_VehAvailRateRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.008">
            <POS>
                <Source ISOCountry="AU" AgentDutyCode="T17R16L5D11">
                    <RequestorID Type="4" ID="X975">
                        <CompanyName Code="CP" CodeContext="4PH5"></CompanyName>
                    </RequestorID>
                </Source>
                <Source>
                    <RequestorID Type="8" ID="$vendorID"/>
                </Source>
            </POS>
            <VehAvailRQCore Status="Available">
                <VehRentalCore PickUpDateTime="$pickUpDateTime" ReturnDateTime="$dropOffDateTime">
                    <PickUpLocation LocationCode="$pickup" CodeContext="IATA"/>
                    <ReturnLocation LocationCode="$dropOff" CodeContext="IATA"/>
                </VehRentalCore>
            </VehAvailRQCore>
        </OTA_VehAvailRateRQ>
        XML;

        $apiUrl = 'https://vv.xqual.hertz.com/DirectLinkWEB/handlers/DirectLinkHandler?id=ota2007a';
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/xml',
            'Content-Length: ' . strlen($xmlRequest)
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);

        // Execute the cURL request and capture the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }

        curl_close($ch);

        return $response;
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['search_btn'])) {
        $pickup = $_POST['pickCityCode'];
        $dropOff = $_POST['dropCityCode'];
        $pickDate = $_POST['pickDate'];
        $pickTime = $_POST['pickTime'];
        $dropDate = $_POST['dropDate'];
        $dropTime = $_POST['dropTime'];

        // Function to convert 12-hour time to 24-hour format
        function convertTo24HourFormat($time12Hour)
        {
            $date = DateTime::createFromFormat('h:i A', $time12Hour);
            return $date->format('H:i:s');
        }

        // Function to convert date format
        function convertDateFormat($date, $fromFormat, $toFormat)
        {
            $dateTime = DateTime::createFromFormat($fromFormat, $date);
            return $dateTime->format($toFormat);
        }

        // Convert the date formats
        $fromFormat = 'm/d/Y'; // Original format
        $toFormat = 'Y-m-d';   // Desired format

        $convertedPickDate = convertDateFormat($pickDate, $fromFormat, $toFormat);
        $convertedDropDate = convertDateFormat($dropDate, $fromFormat, $toFormat);

        // Convert the time formats
        $formatPickTime = convertTo24HourFormat($pickTime);
        $formatDropTime = convertTo24HourFormat($dropTime);

        // Combine date and time for XML
        $pickUpDateTime = $convertedPickDate . 'T' . $formatPickTime;
        $dropOffDateTime = $convertedDropDate . 'T' . $formatDropTime;

        // Make the API calls for ZR and ZT
        $responseZE = makeApiCall('ZE', $pickup, $dropOff, $pickUpDateTime, $dropOffDateTime);
        $responseZR = makeApiCall('ZR', $pickup, $dropOff, $pickUpDateTime, $dropOffDateTime);
        $responseZT = makeApiCall('ZT', $pickup, $dropOff, $pickUpDateTime, $dropOffDateTime);

        // Combine the responses
        if (strlen($responseZE) > 500 || strlen($responseZR) > 500 || strlen($responseZT) > 500) {
            $_SESSION['responseZE'] = $responseZE;
            $_SESSION['responseZR'] = $responseZR;
            $_SESSION['responseZT'] = $responseZT;
            $dataarray = [
                'pickUpDateTime' => $pickUpDateTime,
                'dropOffDateTime' => $dropOffDateTime,
                'pickLocation' => $pickup,
                'dropLocation' => $dropOff,
            ];
            $_SESSION['dataarray'] = $dataarray;
            echo "<script>window.location.href='results.php'</script>";
        } else {
            echo "<script>alert('Vehicles are not available')</script>";
        }
    }
    ?>
    <!-- for desktop -->
    <div id="searchbox" class="d-none d-md-block bg-white p-3 rounded-3 mt-3">
        <form action="" method="post">
            <div class="d-flex justify-content-start">
                <h2>SEARCH FOR CAR PRICES</h2>
            </div>
            <div class="row">
                <div class="col-6 d-grid position-relative">
                    <label for="pick">PICK UP LOCATION:</label>
                    <input type="text" name="pick" id="pickInput" class="form-control" placeholder="Enter city or airport code">
                    <input type="hidden" name="pickCityCode" id="pickCityCode"> <!-- Hidden input to store pick-up city code -->

                    <!-- Suggestion box -->
                    <div id="suggestionBox" class="suggestion-box bg-white border rounded position-absolute">

                    </div>
                </div>
                <div class="col-6 d-grid position-relative">
                    <label for="drop">DROP OFF LOCATION:</label>
                    <input type="text" name="drop" id="dropInput" class="form-control" placeholder="Enter city or airport code">
                    <input type="hidden" name="dropCityCode" id="dropCityCode"> <!-- Hidden input to store drop-off city code -->

                    <!-- Suggestion box for drop-off locations -->
                    <div id="dropSuggestionBox" class="suggestion-box bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-6 d-flex justify-content-between gap-3 mt-3">
                    <div class="w-50 d-grid outer_div">
                        <label for="pickDate">PICK UP DATE:</label>
                        <input type="text" class="input_div" name="pickDate" id="pickDate">
                    </div>
                    <div class="w-50 d-grid outer_div">
                        <label for="pickTime">PICK UP TIME:</label>
                        <input type="text" class="input_div" name="pickTime" id="pickTime">
                    </div>
                </div>
                <div class="col-6 d-flex justify-content-between gap-3 mt-3">
                    <div class="w-50 d-grid outer_div">
                        <label for="dropDate">DROP OFF DATE:</label>
                        <input type="text" class="input_div" name="dropDate" id="dropDate">
                    </div>
                    <div class="w-50 d-grid outer_div">
                        <label for="dropTime">DROP OFF TIME:</label>
                        <input type="text" class="input_div" name="dropTime" id="dropTime">
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="d-flex justify-content-end">
                    <button type="submit" name="search_btn" class="btn btn-primary rounded-2">SEARCH <i class="fa-solid fa-arrow-right fa-sm" style="color: #ffffff;"></i></button>
                </div>
            </div>
        </form>
    </div>
    <!-- for mobile -->
    <div class="d-md-none bg-white p-3 rounded-3 mt-3">
        <form action="" method="post">
            <div class="d-flex justify-content-start mb-3">
                <h2>SEARCH FOR CAR PRICES</h2>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_pick">Pick Up Location:</label>
                    <input type="text" name="pick" id="mobile_pick" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off" class="form-control">
                    <input type="hidden" name="pickCityCode" id="mobile_pickCityCode">
                    <div id="suggestionBox-mobile" class="suggestionBox-mobile bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_drop">Drop Off Location:</label>
                    <input type="text" name="drop" id="mobile_drop" list="airport_name" placeholder="CITY OR AIRPORT CODE" autocomplete="off" class="form-control">
                    <input type="hidden" name="dropCityCode" id="mobile_dropCityCode">
                    <div id="drop-suggestionBox-mobile" class="drop-suggestionBox-mobile bg-white border rounded position-absolute" style="display:none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_pickDate">Pick Up Date:</label>
                    <input type="text" name="pickDate" id="mobile_pickDate" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_pickTime">Pick Up Time:</label>
                    <input type="text" name="pickTime" id="mobile_pickTime" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="mobile_dropDate">Drop Off Date:</label>
                    <input type="text" name="dropDate" id="mobile_dropDate" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mobile_dropTime">Drop Off Time:</label>
                    <input type="text" name="dropTime" id="mobile_dropTime" class="form-control">
                </div>
            </div>
            <div>
                <button type="submit" name="search_btn" class="btn btn-primary rounded-2">SEARCH <i class="fa-solid fa-arrow-right fa-sm" style="color: #ffffff;"></i></button>
            </div>
        </form>
    </div>
    <script>
        // Function to handle the autocomplete logic
        $(document).ready(function() {
            $('#pickInput').on('focus input', function() {
                var inputValue = $(this).val().trim();

                if (inputValue.length >= 3 || inputValue.length === 0) {
                    $.ajax({
                        url: 'getStations.php',
                        method: 'POST',
                        data: {
                            searchTerm: inputValue
                        },
                        success: function(response) {
                            var stations = JSON.parse(response);
                            $('#suggestionBox').empty().show();

                            if (stations.message) {
                                $('#suggestionBox').append(`
                                    <div class="no-data-message" style="padding: 10px; color: red;">
                                        ${stations.message}
                                    </div>
                                `);
                            } else {
                                var groupedStations = stations.reduce(function(grouped, station) {
                                    var city = station.group;
                                    if (!grouped[city]) {
                                        grouped[city] = [];
                                    }
                                    grouped[city].push(station);
                                    return grouped;
                                }, {});

                                Object.keys(groupedStations).forEach(function(city) {
                                    var cityStations = groupedStations[city];
                                    cityStations.forEach(function(station) {
                                        $('#suggestionBox').append(`
                                            <div class="suggestion-item" data-code="${station.stationCode}">
                                                ${station.stationName} ALL LOCATIONS
                                            </div>
                                        `);
                                    });
                                });

                                // Handle click on suggestion items
                                $('.suggestion-item').on('click', function() {
                                    var stationName = $(this).text().trim();
                                    var stationCode = $(this).data('code');

                                    $('#pickInput').val(stationName);
                                    $('#pickCityCode').val(stationCode);
                                    $('#dropInput').val(stationName);
                                    $('#dropCityCode').val(stationCode);

                                    $('#suggestionBox').hide();
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error during AJAX request:', textStatus, errorThrown);
                        },
                    });
                } else {
                    $('#suggestionBox').hide();
                }
            });

            $('#pickInput').on('click', function() {
                if ($('#suggestionBox').children().length > 0) {
                    $('#suggestionBox').show();
                }
            });

            $('#mobile_pick').on('focus input', function() {
                var inputValue = $(this).val().trim();

                if (inputValue.length >= 3 || inputValue.length === 0) {
                    $.ajax({
                        url: 'getStations.php',
                        method: 'POST',
                        data: {
                            searchTerm: inputValue
                        },
                        success: function(response) {
                            var stations = JSON.parse(response);
                            $('#suggestionBox-mobile').empty().show();

                            if (stations.message) {
                                $('#suggestionBox-mobile').append(`
                        <div class="no-data-message" style="padding: 10px; color: red;">
                            ${stations.message}
                        </div>
                    `);
                            } else {
                                var groupedStations = stations.reduce(function(grouped, station) {
                                    var city = station.group;
                                    if (!grouped[city]) {
                                        grouped[city] = [];
                                    }
                                    grouped[city].push(station);
                                    return grouped;
                                }, {});

                                Object.keys(groupedStations).forEach(function(city) {
                                    var cityStations = groupedStations[city];
                                    cityStations.forEach(function(station) {
                                        $('#suggestionBox-mobile').append(`
                                <div class="suggestion-item-mobile" data-code="${station.stationCode}">
                                    ${station.stationName} ALL LOCATIONS
                                </div>
                            `);
                                    });
                                });

                                $('.suggestion-item-mobile').on('click', function() {
                                    var stationName = $(this).text().trim();
                                    var stationCode = $(this).data('code');

                                    $('#mobile_pick').val(stationName);
                                    $('#mobile_pickCityCode').val(stationCode);
                                    $('#mobile_drop').val(stationName);
                                    $('#mobile_dropCityCode').val(stationCode);

                                    $('#suggestionBox-mobile').hide();
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error during AJAX request:', textStatus, errorThrown);
                        },
                    });
                } else {
                    $('#suggestionBox-mobile').hide();
                }
            });

            $('#mobile_pick').on('click', function() {
                if ($('#suggestionBox-mobile').children().length > 0) {
                    $('#suggestionBox-mobile').show();
                }
            });

            $('#dropInput').on('focus input', function() {
                var inputValue = $(this).val().trim();

                if (inputValue.length >= 3 || inputValue.length === 0) {
                    $.ajax({
                        url: 'getStations.php',
                        method: 'POST',
                        data: {
                            searchTerm: inputValue
                        },
                        success: function(response) {
                            var stations = JSON.parse(response);
                            $('#dropSuggestionBox').empty().show();

                            if (stations.message) {
                                $('#dropSuggestionBox').append(`
                        <div class="no-data-message" style="padding: 10px; color: red;">
                            ${stations.message}
                        </div>
                    `);
                            } else {
                                var groupedStations = stations.reduce(function(grouped, station) {
                                    var city = station.group;
                                    if (!grouped[city]) {
                                        grouped[city] = [];
                                    }
                                    grouped[city].push(station);
                                    return grouped;
                                }, {});

                                Object.keys(groupedStations).forEach(function(city) {
                                    var cityStations = groupedStations[city];
                                    cityStations.forEach(function(station) {
                                        $('#dropSuggestionBox').append(`
                                <div class="suggestion-item" data-code="${station.stationCode}">
                                    ${station.stationName}
                                </div>
                            `);
                                    });
                                });

                                $('.suggestion-item').on('click', function() {
                                    var stationName = $(this).text().trim();
                                    var stationCode = $(this).data('code');

                                    $('#dropInput').val(stationName);
                                    $('#dropCityCode').val(stationCode);

                                    $('#dropSuggestionBox').hide();
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error during AJAX request:', textStatus, errorThrown);
                        },
                    });
                } else {
                    $('#dropSuggestionBox').hide();
                }
            });

            $('#dropInput').on('click', function() {
                if ($('#dropSuggestionBox').children().length > 0) {
                    $('#dropSuggestionBox').show();
                }
            });
            $('#mobile_drop').on('focus input', function() {
                var inputValue = $(this).val().trim();

                if (inputValue.length >= 3 || inputValue.length === 0) {
                    $.ajax({
                        url: 'getStations.php',
                        method: 'POST',
                        data: {
                            searchTerm: inputValue
                        },
                        success: function(response) {
                            var stations = JSON.parse(response);
                            $('#drop-suggestionBox-mobile').empty().show();

                            if (stations.message) {
                                $('#drop-suggestionBox-mobile').append(`
                        <div class="no-data-message" style="padding: 10px; color: red;">
                            ${stations.message}
                        </div>
                    `);
                            } else {
                                var groupedStations = stations.reduce(function(grouped, station) {
                                    var city = station.group;
                                    if (!grouped[city]) {
                                        grouped[city] = [];
                                    }
                                    grouped[city].push(station);
                                    return grouped;
                                }, {});

                                Object.keys(groupedStations).forEach(function(city) {
                                    var cityStations = groupedStations[city];
                                    cityStations.forEach(function(station) {
                                        $('#drop-suggestionBox-mobile').append(`
                                <div class="drop-suggestion-item-mobile" data-code="${station.stationCode}">
                                    ${station.stationName}
                                </div>
                            `);
                                    });
                                });

                                $('.drop-suggestion-item-mobile').on('click', function() {
                                    var stationName = $(this).text().trim();
                                    var stationCode = $(this).data('code');

                                    $('#mobile_drop').val(stationName);
                                    $('#mobile_dropCityCode').val(stationCode);

                                    $('#drop-suggestionBox-mobile').hide();
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error during AJAX request:', textStatus, errorThrown);
                        },
                    });
                } else {
                    $('#drop-suggestionBox-mobile').hide();
                }
            });

            $('#mobile_drop').on('click', function() {
                if ($('#drop-suggestionBox-mobile').children().length > 0) {
                    $('#drop-suggestionBox-mobile').show();
                }
            });


        });

        $(function() {
            $("#pickDate").datepicker();
        });
        $(document).ready(function() {
            $('#pickTime').timepicker({});
        });
        $(function() {
            $("#dropDate").datepicker();
        });
        $(document).ready(function() {
            $('#dropTime').timepicker({});
        });
        // mobile
        $(function() {
            $("#mobile_pickDate").datepicker();
        });
        $(document).ready(function() {
            $('#mobile_pickTime').timepicker({});
        });
        $(function() {
            $("#mobile_dropDate").datepicker();
        });
        $(document).ready(function() {
            $('#mobile_dropTime').timepicker({});
        });
    </script>
</body>

</html>