<?php
session_start();
$conn = new mysqli("localhost", "root", "", "student_info");

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $_SESSION['username'] = $username;
    header("Location: welcome.php");
    exit(); 
} else {
    echo"Invalid Input";
}
$conn->close();
?>
