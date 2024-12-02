<?php
session_start();
$dataarray = isset($_SESSION['dataarray']) ? $_SESSION['dataarray'] : []; // Ensure session is initialized
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
  $pickup = $input['pickup']; // Match JavaScript key
  $dropOff = $input['dropOff'];
  $pickTime = $input['pickUpTime'];
  $dropTime = $input['dropOffTime'];
  $dataarray['pickLocation'] = $pickup;
  $dataarray['dropLocation'] = $dropOff;
  $dataarray['pickUpDateTime'] = $pickTime;
  $dataarray['dropOffDateTime'] = $dropTime;

  // Save back to session
  $_SESSION['dataarray'] = $dataarray;

  // Respond with updated session data
  echo json_encode([
    "status" => "success",
    "message" => "Session updated",
    "data" => $_SESSION['dataarray']
  ]);
} else {
  echo json_encode([
    "status" => "error",
    "message" => "Invalid input"
  ]);
}
