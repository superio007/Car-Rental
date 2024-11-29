<?php
    session_start();
    if(!isset($_SESSION['jwtToken'])){
        echo "<script>window.location.href='login.php';</script>";
    }
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
    <?php include_once 'header.php';?>
    <div id="hero" class="d-grid container-fluid-md justify-content-center" style="background: url(./images/39D0DD07-0B14-7EA4-AB3D00DD78758912_fullwidth.jpg); padding: 4rem 1rem; background-repeat: no-repeat;background-attachment: local;background-size: 100% 100%;">
        <div class="text-center text-white">
            <h1>SEARCH CAR HIRE, EURO-LEASING & MOTORHOMES</h1>
            <h5><i class="fa-solid fa-check " style="color: #ffffff;"></i> Flexibility <i class="fa-solid fa-check " style="color: #ffffff;"></i> 24,000 Locations <i class="fa-solid fa-check " style="color: #ffffff;"></i> Peace of mind <i class="fa-solid fa-check " style="color: #ffffff;"></i> Best in Price</h5>
        </div>
        <div>
            <?php include_once 'searchWidget.php';?>
        </div>
    </div>
    <!-- desktop version -->
    <div class="d-none d-md-block" style="background-color: #f4f7fa; width:100%">
        <div class="container d-flex justify-content-between">
            <div class="p-3 d-flex gap-5" style="height:max-content;width: 60rem;">
                <img src="./images/dollar.png" alt="">
                <img src="./images/hertz.png" alt="">
                <img src="./images/thrifty.png" alt="">
            </div>
            <div style="position: relative;height: 7rem;">
                <img style="position: absolute; right:0; bottom:0;" src="./images/QASHQAI_home_resized.png" alt="">
            </div>
        </div>
    </div>
    <!-- mobile version -->
    <div class="d-md-none" style="background-color: #f4f7fa; width:100%">
        <div>
            <div class="d-flex justify-content-center">
                <img src="./images/QASHQAI_home_resized.png" alt="">
            </div>
            <div class="p-3 d-flex justify-content-between gap-5" style="height:max-content;">
                <img src="./images/dollar.png" alt="">
                <img src="./images/hertz.png" alt="">
            </div>
            <div class="d-flex justify-content-center">
                <img src="./images/thrifty.png" alt="">
            </div>
        </div>
    </div>
    <!-- desktop version -->
    <div class="d-none d-md-block container my-5">
        <h3>The DriveAway Difference</h3>
        <div class="row text-center">
            <div class="col-2">
                <img style="width: 10rem;" src="./images/flexibility.png" alt="">
                <strong>Flexibility</strong>
                <p style="font-size: 1rem;">Free Cancellation for most bookings before pick up</p>
            </div>
            <div class="col-2">
                <img style="width: 10rem;" src="./images/24k-locations.png" alt="">
                <strong>24,000 Locations</strong>
                <p style="font-size: 1rem;">24,000 Locations in 140 countries across the globe</p>
            </div>
            <div class="col-2">
                <img style="width: 10rem;" src="./images/peace-of-mind.png" alt="">
                <strong>Peace of Mind</strong>
                <p style="font-size: 1rem;">24/7 Local Customer Support</p>
            </div>
            <div class="col-2">
                <img style="width: 10rem;" src="./images/best-in-price.png" alt="">
                <strong>Best in Price</strong>
                <p style="font-size: 1rem;">DriveAway won’t be beaten in price</p>
            </div>
            <div class="col-2">
                <img style="width: 10rem;" src="./images/trusted-suppliers.png" alt="">
                <strong>Trusted Suppliers</strong>
                <p style="font-size: 1rem;">We work with the best in business worldwide</p>
            </div>
            <div class="col-2">
                <img style="width: 10rem;" src="./images/expertise.png" alt="">
                <strong>Expertise</strong>
                <p style="font-size: 1rem;">Outstanding Service Since 1988</p>
            </div>
        </div>
    </div>
    <!-- mobile version -->
    <div class="d-md-none container-md my-5">
        <h3>The DriveAway Difference</h3>
        <div class="text-center">
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem;" src="./images/flexibility.png" alt="">
                </div>
                <strong>Flexibility</strong>
                <p style="font-size: 1rem;">Free Cancellation for most bookings before pick up</p>
            </div>
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem; display:flex; justify-content:center;" src="./images/24k-locations.png" alt="">
                </div>
                <strong>24,000 Locations</strong>
                <p style="font-size: 1rem;">24,000 Locations in 140 countries across the globe</p>
            </div>
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem; display:flex; justify-content:center;" src="./images/peace-of-mind.png" alt="">
                </div>
                <strong>Peace of Mind</strong>
                <p style="font-size: 1rem;">24/7 Local Customer Support</p>
            </div>
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem; display:flex; justify-content:center;" src="./images/best-in-price.png" alt="">
                </div>
                <strong>Best in Price</strong>
                <p style="font-size: 1rem;">DriveAway won’t be beaten in price</p>
            </div>
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem; display:flex; justify-content:center;" src="./images/trusted-suppliers.png" alt="">
                </div>
                <strong>Trusted Suppliers</strong>
                <p style="font-size: 1rem;">We work with the best in business worldwide</p>
            </div>
            <div class="d-grid justify-content-center">
                <div class="d-flex justify-content-center">
                    <img style="width: 10rem; display:flex; justify-content:center;" src="./images/expertise.png" alt="">
                </div>
                <strong>Expertise</strong>
                <p style="font-size: 1rem;">Outstanding Service Since 1988</p>
            </div>
        </div>
    </div>
    <?php include 'footer.php';?>
</body>
</html>