<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
</head>
<style>
    #spinner {
        width: 100%;
        height: 100%;
        color: #154782;
        margin-top: 12px;
    }
</style>
<body>
    <?php
    session_start();
    // unset($_SESSION['jwtToken']);
    unset($_SESSION['AccessId']);
    unset($_SESSION['dataarray']);
    unset($_SESSION['responseZR']);
    unset($_SESSION['responseZT']);
    unset($_SESSION['responseZE']);
    unset($_SESSION['userInfo']);
    // session_destroy();
    if(isset($_GET['rate'])){
        $rate = $_GET['rate'];
    }
    if(isset($_GET['cnfNo'])){
        $cnfNo = $_GET['cnfNo'];
    }
    if(isset($_GET['lName'])){
        $lName = $_GET['lName'];
    }
    require_once('SHAPaydollarSecure.php');
    //Required Parameter ( with UTF-8 Encoding ) for connect to our payment page
    $merchantId='16000806';
    $orderRef=date('YmdHis');
    $currCode='036';
    $amount=$rate;
    $paymentType='N';
    $mpsMode="NIL";
    $payMethod="ALL";
    $lang="E";
    $sucess = "https://dev.yourbestwayhome.com.au/Hertz-main/detail.php?cnfNo=$cnfNo&lName=$lName";
    $successUrl=$sucess;
    $failUrl="http://www.yourdomain.com/fail.html";
    $cancelUrl="https://dev.yourbestwayhome.com.au/Hertz-main/index.php";
    //Optional Parameter for connect to our payment page
    $remark="";
    $redirect="";
    $oriCountry="";
    $destCountry="";



    $secureHashSecret='rp6RIf6VpNbT4vMTskQ9qu0Gusyp2yJB';//offered by paydollar
    //Secure hash is used to authenticate the integrity of the transaction information and the identity of the merchant. It is calculated by hashing the combination of various transaction parameters and the Secure Hash Secret.
    $paydollarSecure=new SHAPaydollarSecure(); 
    $secureHash=$paydollarSecure->generatePaymentSecureHash($merchantId, $orderRef, $currCode, $amount, $paymentType, $secureHashSecret);
    ?>
    <div class="d-flex justify-content-center align-items-center" style="height:100vh">
        <div class="text-center">
            <h1>Please Wait!</h1>
            <p>Payment is in the process</p>
            <div id="spinner">
                <div class="spinner-border" role="status" >
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
        <form name="payFormCcard" method="post" action="https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp">
            <input type="hidden" name="merchantId" value="<?php echo $merchantId ?>">
            <input type="hidden" name="amount" value="<?php echo $amount ?>">
            <input type="hidden" name="orderRef" value="<?php echo $orderRef ?>">
            <input type="hidden" name="currCode" value="<?php echo $currCode ?>">
            <input type="hidden" name="successUrl" value="<?php echo $successUrl ?>">
            <input type="hidden" name="failUrl" value="<?php echo $failUrl ?>">
            <input type="hidden" name="cancelUrl" value="<?php echo $cancelUrl ?>">
            <input type="hidden" name="payType" value="<?php echo $paymentType ?>">
            <input type="hidden" name="lang" value="<?php echo $lang ?>">
            <input type="hidden" name="mpsMode" value="<?php echo $mpsMode ?>">
            <input type="hidden" name="payMethod" value="<?php echo $payMethod ?>">
            <input type="hidden" name="secureHash" value="<?php echo $secureHash ?>">
            <input type="hidden" name="remark" value="<?php echo $remark ?>">
            <input type="hidden" name="redirect" value="<?php echo $redirect ?>">
            <input type="hidden" name="oriCountry" value="<?php echo $oriCountry ?>">
            <input type="hidden" name="destCountry" value="<?php echo $destCountry ?>">
        </form>
    </div>
</body>
<script>
    window.onload = function() {
        document.forms['payFormCcard'].submit();
    };
    // function redirect(){
    //     window.location.href = `detail.php?cnfNo=${cnfNo}&lName=${lName}`;
    // }
    // setTimeout(redirect,2000);
</script>
</html>