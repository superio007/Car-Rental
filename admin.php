<?php
session_start();
$jwtToken = $_SESSION['jwtToken'];
include 'jwt.php';
$decodedArray = decodeJWT($jwtToken);
// var_dump($decodedArray);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <script
        src="https://kit.fontawesome.com/74e6741759.js"
        crossorigin="anonymous"></script>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous" />
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="admin.css" />
</head>
<body class="flex items-center justify-center w-screen h-screen p-10 space-x-6 bg-gray-300">
    <?php
    include 'dbconn.php';
    if (isset($_GET['accessId'])) {
        $accessId = $_GET['accessId'];
    }
    if (isset($_GET['booking'])) {
        if ($decodedArray['data']['Access'] == "Admin") {
            $sql = "SELECT * FROM `bookings`";
            $result = $conn->query($sql);
        } else {
            $sql = "SELECT * FROM `bookings` WHERE AccessId= '$accessId'";
            $result = $conn->query($sql);
        }
    }
    if (isset($_GET['about'])) {
        $sql = "SELECT * FROM `user_info` WHERE AccessId = '$accessId'";
        $result = $conn->query($sql);
    }
    if (isset($_GET['price'])) {
        $sql = "SELECT * FROM `markup_price`";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            // output data of each row
            while ($row = $result->fetch_assoc()) {
                $MarkUpVal = $row['MarkupPrice'];
            }
        } else {
            echo "0 results";
        }
    }
    if (isset($_POST['Marup'])) {
        $Marup = $_POST['Marup'];
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['mark_sub'])) {
        $sql = "UPDATE `markup_price` SET `Id`='1',`MarkupPrice`='$Marup' WHERE 1";
        $result = $conn->query($sql);
        if ($conn->query($sql) === TRUE) {
            echo "<script></script>";
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['Log-off'])) {
        unset($jwtToken);
        echo "<script>window.location.href='login.php';</script>";
        session_destroy();
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['Home'])) {
        echo "<script>window.location.href='index.php';</script>";
    }
    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['Submit_user_info'])) {
        $FullName = $_POST['FullName'];
        $BirthDate = $_POST['BirthDate'];
        $PhoneNumber = $_POST['PhoneNumber'];
        $Email = $_POST['Email'];
        $Designation = $_POST['Designation'];
        $Company = $_POST['Company'];

        $sql = "INSERT INTO `user_info`(`Id`, `Name`, `BirthDate`, `PhoneNumber`, `Email`, `Designation`, `Company`, `AccessId`) VALUES ('','$FullName','$BirthDate','$PhoneNumber','$Email','$Designation','$Company','$accessId')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
    $conn->close();
    ?>
    <div class="wrapper">
        <div class="main_body">
            <div class="sidebar_menu">
                <div class="inner__sidebar_menu">
                    <ul>
                        <li>
                            <a href="?dashboard&accessId=<?php echo $accessId; ?>" class="anchor">
                                <span class="icon"> <i class="fas fa-border-all"></i></span>
                                <span class="list">Dashboard</span>
                            </a>
                        </li>
                        <?php if ($decodedArray['data']['Access'] == "Admin"): ?>
                            <li>
                                <a href="?price&accessId=<?php echo $accessId; ?>" class="anchor">
                                    <span class="icon"><i class="fas fa-chart-pie"></i></span>
                                    <span class="list">Price</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="?booking&accessId=<?php echo $accessId; ?>" class="anchor">
                                <span class="icon"><i class="fas fa-address-book"></i></span>
                                <span class="list">Bookings</span>
                            </a>
                        </li>
                        <li>
                            <a href="?about&accessId=<?php echo $accessId; ?>" class="anchor">
                                <span class="icon"><i class="fas fa-address-card"></i></span>
                                <span class="list">About</span>
                            </a>
                        </li>
                    </ul>

                    <div class="hamburger">
                        <div class="inner_hamburger">
                            <span class="arrow">
                                <i class="fas fa-long-arrow-alt-left"></i>
                                <i class="fas fa-long-arrow-alt-right"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <?php if (isset($_GET['dashboard'])): ?>
                    <h1 class="text-center">DASHBOARD</h1>
                    <div>
                        <div class="d-flex justify-content-center align-items-center" style="background-color:white;border-radius:0.5rem;width:100%;height:80vh;">
                            <div class="d-grid">
                                <form action="" method="post">
                                    <h3 class="text-center"><Strong>Welcome To Dashboard</Strong></h3>
                                    <p class="text-center">Here you can monitor your stat</p>
                                    <div class="d-flex justify-content-center gap-3">
                                        <button type="submit" class="btn btn-success" name="Home">Home</button>
                                        <button type="submit" class="btn btn-danger" name="Log-off">Log Out</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['price'])): ?>
                    <h1 class="text-center">MANAGE PRICE</h1>
                    <div class="mt-3">
                        <div class="d-flex justify-content-center p-5" style="background-color:white;border-radius:0.5rem;width:100%;height:80vh;">
                            <div>
                                <p class="text-center">State your markup</p>
                                <form class="form form_markup d-flex" method="post">
                                    <div class="form-group mb-2">
                                        <input type="text" readonly class="form-control-plaintext" id="staticEmail2" value="Add your Markup">
                                    </div>
                                    <div class="form-group mx-sm-3 mb-2">
                                        <label for="inputPassword2" class="sr-only">Password</label>
                                        <input type="number" name="Marup" class="form-control" value="<?php echo $MarkUpVal ?>" style="width: 13rem;" id="inputPassword2" min="10" max="100" placeholder="Enter Markup Price %">
                                    </div>
                                    <button type="submit" name="mark_sub" class="btn btn-primary mb-2">Add Markup</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['booking'])): ?>
                    <h1 class="text-center">BOOKINGS</h1>
                    <table class="table table-success table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Id</th>
                                <th scope="col">Name</th>
                                <th scope="col">Confirm Id</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // output data of each row
                                $num = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "
                                            <tr>
                                                <th scope=\"row\">$num</th>
                                                <td>{$row['FirstName']} {$row['LastName']}</td>
                                                <td>{$row['ConfirmedId']}</td>
                                                <td class=\"d-none\"><input type=\"text\" value=\"{$row['ConfirmedId']}\" hidden></td>
                                                <td class=\"d-none\"><input type=\"text\" value=\"{$row['LastName']}\" hidden></td>
                                                <td><button type=\"submit\" class=\"btn btn-delete btn-danger\">Delete</button></td>
                                            </tr>
                                        ";
                                    $num++;
                                }
                            } else {
                                echo "
                                    <tr>
                                        <td colspan=\"6\" style=\"text-align:center;\">No bookings available</td>
                                    </tr>
                                ";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <?php if (isset($_GET['about'])): ?>
                    <h1 class="text-center">ABOUT INFO</h1>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="d-flex justify-content-evenly mt-4">
                                <div style="background-color:white;border-radius:0.5rem;width:25rem;height:10rem;" class="p-2">
                                    <h3 class="text-center">Personal Info</h3>
                                    <div class="px-5">
                                        <div class="d-flex gap-2">
                                            <strong>Name : </strong>
                                            <p><?php echo $row['Name']; ?></p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <strong>Birth Date : </strong>
                                            <p><?php echo $row['BirthDate']; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div style="background-color:white;border-radius:0.5rem;width:25rem;height:10rem;" class="p-2">
                                    <h3 class="text-center">Contact Info</h3>
                                    <div class="px-5">
                                        <div class="d-flex gap-2">
                                            <strong>Phone Number : </strong>
                                            <p><?php echo $row['PhoneNumber']; ?></p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <strong>Email : </strong>
                                            <p><?php echo $row['Email']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-5">
                                <div style="background-color:white;border-radius:0.5rem;width:58rem;height:10rem;" class="p-2">
                                    <h3 class="text-center">Professional Summary</h3>
                                    <div class="px-5">
                                        <div class="d-flex gap-2">
                                            <strong>Designation : </strong>
                                            <p><?php echo $row['Designation']; ?></p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <strong>Company : </strong>
                                            <p><?php echo $row['Company']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="d-flex justify-content-center align-items-center">
                            <div style="width:20rem;">
                                <form class="g-3" method="post">
                                    <div class="mt-2">
                                        <label for="FullName" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="FullName" id="FullName" placeholder="Enter Full Name" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="BirthDate" class="form-label">Birth Date</label>
                                        <input type="date" class="form-control" name="BirthDate" id="BirthDate" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="PhoneNumber" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="PhoneNumber" id="PhoneNumber" placeholder="Enter Phone number" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="Email" class="form-label">Email</label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" name="Email" id="Email" aria-describedby="inputGroupPrepend2" required placeholder="Enter Email">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="Designation" class="form-label">Designation</label>
                                        <input type="text" class="form-control" name="Designation" id="Designation" placeholder="Enter Designation" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="Company" class="form-label">Company</label>
                                        <input type="text" class="form-control" name="Company" id="Company" placeholder="Enter Company" required>
                                    </div>
                                    <div class="mt-2">
                                        <button class="btn btn-primary" name="Submit_user_info" type="submit">Submit form</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script>
        $(".hamburger").click(function() {
            $(".wrapper").toggleClass("active");
        });
        // When the document is ready
        $(document).ready(function() {
            // Attach a click event listener to all delete buttons
            $(".btn-delete").click(function(event) {
                // Prevent form submission if needed
                event.preventDefault();

                // Find the closest table row to the button clicked
                var row = $(this).closest('tr');

                // Get the values from the input fields in the row
                var confirmId = row.find('input[type="text"]').eq(0).val(); // First input (Confirm ID)
                var name = row.find('input[type="text"]').eq(1).val(); // Second input (Name)

                // Show confirmation alert using SweetAlert
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete the record of ${name} with Confirm ID: ${confirmId}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // If the user confirms, send the data to admin.php via POST
                        $.ajax({
                            url: 'delete.php', // URL of the PHP file to handle the request
                            type: 'POST', // HTTP method
                            data: {
                                confirmId: confirmId, // Send confirmId
                                name: name // Send name
                            },
                            success: function(response) {
                                // Handle success response (e.g., show success message, remove row)
                                Swal.fire(
                                    'Deleted!',
                                    `${name}'s record has been deleted.`,
                                    'success'
                                );

                                // Optionally, you can remove the row after deletion
                                row.remove();
                                location.reload();
                            },
                            error: function(xhr, status, error) {
                                // Handle error response
                                Swal.fire(
                                    'Error!',
                                    'There was an error processing your request.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            $(".form_markup").on("submit", function(event) {
                event.preventDefault(); // Prevent form submission
                var markup = $("input[name='Marup']").val(); // Get the markup value

                $.ajax({
                    url: '', // Your PHP file (can be left blank if it's the same file)
                    type: 'POST',
                    data: {
                        Marup: markup,
                        mark_sub: true // Make sure the form data matches the POST check in PHP
                    },
                    success: function(response) {
                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: 'Markup price has been updated successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while updating the markup price.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>