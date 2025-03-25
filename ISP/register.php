<?php 

include 'connect.php';

if(isset($_POST['signUp'])){
    $firstName=$_POST['fName'];
    $lastName=$_POST['lName'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    $password=md5($password);

     $checkEmail="SELECT * From users where email='$email'";
     $result=$conn->query($checkEmail);
     if($result->num_rows>0){
        echo "Email Address Already Exists !";
     }
     else{
        $insertQuery="INSERT INTO users(firstName,lastName,email,password)
                       VALUES ('$firstName','$lastName','$email','$password')";
            if($conn->query($insertQuery)==TRUE){
                header("location: login.html");
            }
            else{
                echo "Error:".$conn->error;
            }
     }
   

}

if(isset($_POST['signIn'])){
   $email=$_POST['email'];
   $password=$_POST['password'];
   $password=md5($password) ;
   
   $sql="SELECT * FROM users WHERE email='$email' and password='$password'";
   $result=$conn->query($sql);
   if($result->num_rows>0){
    session_start();
    $row=$result->fetch_assoc();
    $_SESSION['email']=$row['email'];
    header("Location: home.html");
    exit();
   }
   else{
    echo "Not Found, Incorrect Email or Password";
   }

}
?>

<?php
include 'connect.php'; // Ensure database connection is included

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bookRide'])) {
    // Get and sanitize input values
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $pickup = mysqli_real_escape_string($conn, trim($_POST['pickup']));
    $drop = mysqli_real_escape_string($conn, trim($_POST['drop']));

    // Check if values are empty
    if (empty($name) || empty($pickup) || empty($drop)) {
        die("Error: All fields are required.");
    }

    // Insert data into the bookings table
    $sql = "INSERT INTO bookings (name, pickup, dropoff) VALUES ('$name', '$pickup', '$drop')";

    if ($conn->query($sql) === TRUE) {
        echo "Booking successful!";
    } else {
        echo "Database Error: " . $conn->error;
    }
}

$conn->close(); // Close database connection
?>




    
