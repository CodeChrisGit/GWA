<?php
session_start();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password'");

    if ($result->num_rows > 0) {
        $_SESSION["username"] = $username;
        header("Location: welcome.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SorSu Bulan Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container">
        <div class="left">
            <h1>Welcome to SorSu Bulan Campus - GWA</h1>
            <p>Make sure You Have All The Grades <br>To See Your GWA</p>
            <p>NSTP Grades are not included when computing grades.</p>
        </div>
        <div class="right">
            <div class="card">
                <h2>Sign In</h2>
                <form method="post" action="login.php">
                    <?php if (!empty($error)): ?>
                        <div class="error"><?= $error ?></div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <input type="submit" value="Sign In">
                </form>
            </div>
        </div>
    </div>
</body>
</html>

