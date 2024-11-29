<?php
function generateAlphaNumericCode($length = 6) {
  // Define the characters to use (both letters and digits)
  $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $charactersLength = strlen($characters);
  $randomString = '';

  // Loop to create a string of specified length
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
}

// Example usage:
$alphaNumericCode = generateAlphaNumericCode(); // This will generate a 6-character alphanumeric code
echo $alphaNumericCode;
