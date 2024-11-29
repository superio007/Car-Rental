<?php
session_start();
$jwtToken = $_SESSION['jwtToken'];
include_once 'jwt.php';
$decodedArray = decodeJWT($jwtToken);
// var_dump($decodedArray);
$AccessId = $decodedArray['data']['AccessId'];
$_SESSION['AccessId'] = $AccessId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <nav class="d-flex container-md justify-content-between align-content-center">
        <div>
            <a href="index.php"><img src="./images/carRent Logo.png" style="width: 7.5rem;" alt="car Rental Logo"></a>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-4">
            <div>
                <i class="fa-solid fa-phone fa-xl" style="color: #000000;"></i>
                <a class="text-dark text-decoration-none" style="font-weight: bolder; font-size: 1.5rem;" href="#">1300 363 500</a>
            </div>
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-user fa-xl" style="color: #000000;"></i>
                <a href="admin.php?dashboard&accessId=<?php echo $decodedArray['data']['AccessId']; ?>" style="text-decoration: none;color:black;font-weight:400;">
                    <p class="m-0"><?php echo $decodedArray['data']['Name']; ?></p>
                </a>
            </div>
        </div>
    </nav>
</body>

</html>