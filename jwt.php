<?php
require 'vendor/autoload.php'; // Include Composer autoload

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key; // Key class is needed in v6.x for decode method

// Function to encode JWT
function encodeJWT($data) {
    $key = "ddf5df25gd5fg1d5fg1ds5f1g"; // Use a secure and secret key
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // jwt valid for 1 hour
    $payload = [
        'iss' => "http://example.org", // Issuer
        'aud' => "http://example.com", // Audience
        'iat' => $issuedAt, // Issued at: time when the token was generated
        'nbf' => $issuedAt, // Not before
        'exp' => $expirationTime, // Expiration time
        'data' => $data // Custom data you want to include in the token
    ];

    // Generate the JWT
    return JWT::encode($payload, $key, 'HS256'); // Return the encoded token
}

// Function to decode JWT and check expiration
function decodeJWT($jwt) {
    $key = "ddf5df25gd5fg1d5fg1ds5f1g"; // Use the same secret key

    try {
        // Decode the JWT using the Key class and HS256 algorithm
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Convert stdClass object to an array
        $decodedArray = json_decode(json_encode($decoded), true);

        // Check if the token is expired
        if (isset($decodedArray['exp']) && time() > $decodedArray['exp']) {
            // Token has expired
            echo "<script>window.location.href='login.php';</script>"; // Redirect to login.php
            exit(); // Make sure the script stops executing after redirect
        }

        return $decodedArray; // Return the decoded array if the token is valid
    } catch (Exception $e) {
        // If the JWT verification fails, redirect to login page
        echo "<script>window.location.href='login.php';</script>";
        exit();
    }
}
?>
