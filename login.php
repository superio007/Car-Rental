<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="login.css">
  <script src="https://kit.fontawesome.com/74e6741759.js" crossorigin="anonymous"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <title>Document</title>
</head>

<body>
  <?php
  include 'dbconn.php';
  include 'jwt.php'; 
  if (isset($_POST['signname'])) {
    $signName = $_POST['signname'];
  }
  if (isset($_POST['signemail'])) {
    $signEmail = $_POST['signemail'];
  }
  if (isset($_POST['signpass'])) {
    $signPass = $_POST['signpass'];
  }
  if (isset($_POST['logpass'])) {
    $logpass = $_POST['logpass'];
  }
  if (isset($_POST['logemail'])) {
    $logemail = $_POST['logemail'];
  }
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
  if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['log-submit'])) {
    $sql = "SELECT * FROM `users`";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      // output data of each row
      while ($row = $result->fetch_assoc()) {
        // Compare email and password (make sure to hash passwords if not done already)
        if ($row['Email'] == $logemail && $row['Password'] == $logpass) { // Ideally, use password_verify() if using hashed passwords
            echo "
            <script>
            Swal.fire({
                title: 'Good job!',
                text: 'Login successful!',
                icon: 'success'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
            </script>
            ";
    
            // Prepare JWT payload and encode it
            $stringarr = [
                'Name' => $row['Name'],
                'Email' => $row['Email'],
                'Access' => $row['Access'],
                'AccessId' => $row['AccessId']
            ];
    
            $jwt = encodeJWT($stringarr); // Ensure encodeJWT is implemented properly
            $_SESSION['jwtToken'] = $jwt; // Store JWT in session
    
            // Exit after successful login
            exit;
        }
    }
    
    // If no matching row was found, show error
    echo "
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Email or password is incorrect!'
    });
    </script>
    ";
    
    } else {
      
    }
  }
  if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['sign-submit'])) {
    $alphaNumericCode = generateAlphaNumericCode();
    $sql = "INSERT INTO `users`(`Id`, `Name`, `Email`, `Password`,`AccessId`) VALUES ('','$signName','$signEmail','$signPass','$alphaNumericCode')";
    if ($conn->query($sql) === TRUE) {
      echo "New record created successfully";
    } else {
      echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $conn->close();
  }
  ?>
  <div>
    <form action="" method="post">
      <div class="section">
        <div class="container">
          <div class="row full-height justify-content-center">
            <div class="col-12 text-center align-self-center py-5">
              <div class="section pb-5 pt-5 pt-sm-2 text-center">
                <input
                  class="checkbox"
                  type="checkbox"
                  id="reg-log"
                  name="reg-log" />
                <label for="reg-log"></label>
                <div class="card-3d-wrap mx-auto">
                  <div class="card-3d-wrapper">
                    <div class="card-front">
                      <div class="center-wrap">
                        <div class="section text-center">
                          <h4 class="mb-4 pb-3 text-white">Log In</h4>
                          <div class="form-group">
                            <input
                              type="text"
                              name="logemail"
                              class="form-style"
                              placeholder="Your Email"
                              id="logemail"
                              autocomplete="off" />
                            <i class="fa-solid fa-at fa-lg" style="color: #FFD43B;
                              position: absolute;
                              top: 1.5rem;
                              left: 0.8rem;"></i>
                          </div>
                          <div class="form-group mt-2">
                            <input
                              type="password"
                              name="logpass"
                              class="form-style"
                              placeholder="Your Password"
                              id="logpass"
                              autocomplete="off" />
                            <i class="fa-solid fa-lock fa-lg" style="color: #FFD43B;
                              position: absolute;
                              top: 1.5rem;
                              left: 0.8rem;"></i>
                          </div>
                          <button type="submit" class="btn mt-4" name="log-submit">submit</button>
                          <p class="mb-0 mt-4 text-center">
                            <a href="#" class="link">Forgot your password?</a>
                          </p>
                        </div>
                      </div>
                    </div>
                    <div class="card-back">
                      <div class="center-wrap">
                        <div class="section text-center">
                          <h4 class="mb-4 pb-3 text-white">Sign Up</h4>
                          <div class="form-group">
                            <input
                              type="text"
                              name="signname"
                              class="form-style"
                              placeholder="Your Full Name"
                              id="signname"
                              autocomplete="off" />
                            <i class="fa-solid fa-user fa-lg" style="color: #FFD43B;
                              position: absolute;
                              top: 1.5rem;
                              left: 0.8rem;"></i>
                          </div>
                          <div class="form-group mt-2">
                            <input
                              type="email"
                              name="signemail"
                              class="form-style"
                              placeholder="Your Email"
                              id="signemail"
                              autocomplete="off" />
                            <i class="fa-solid fa-at fa-lg" style="color: #FFD43B;
                              position: absolute;
                              top: 1.5rem;
                              left: 0.8rem;"></i>
                          </div>
                          <div class="form-group mt-2">
                            <input
                              type="password"
                              name="signpass"
                              class="form-style"
                              placeholder="Your Password"
                              id="signpass"
                              autocomplete="off" />
                            <i class="fa-solid fa-lock fa-lg" style="color: #FFD43B;
                              position: absolute;
                              top: 1.5rem;
                              left: 0.8rem;"></i>
                          </div>
                          <button type="submit" class="btn mt-4" name="sign-submit">submit</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</body>

</html>