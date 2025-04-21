<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "student_info");
$username = $_SESSION['username'];

$user_result = $conn->query("SELECT student_name, year_level FROM users WHERE username='$username'");
$user = $user_result->fetch_assoc();
$student_name = $user['student_name'];
$year_level = $user['year_level'];

$grade_result = $conn->query("SELECT * FROM grades WHERE username='$username' ORDER BY semester, subject_code");

$semesters = [];
while ($row = $grade_result->fetch_assoc()) {
    $semesters[$row['semester']][] = $row;
}

function calculateGWA($grades) {
    $total = 0;
    foreach ($grades as $g) {
        $total += $g['grade'];
    }
    return round($total / count($grades), 2);
}

$final_total = 0;
$final_count = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Grades</title>
    <style>
        body {
            background-color: #111;
            color: white;
            font-family: Arial;
            padding: 20px;
        }
        .box {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            max-width: 800px;
            margin: auto;
        }
        table {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #444;
            text-align: left;
        }
        th {
            background-color: #333;
        }
        .gwa {
            background-color: #333;
            padding: 10px;
            text-align: right;
            font-weight: bold;
            margin-top: 5px;
            border-radius: 5px;
        }
        .message {
            background-color: #333;
            padding: 15px;
            margin-top: 20px;
            color: #eee;
            border-left: 5px solid yellow;
        }
        .highlight {
            color: yellow;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="box">
    <h2><?= $student_name ?></h2>
    <p><?= $year_level ?><br>BSCS - Bachelor of Science in Computer Science<br>2028-2029</p>

    <?php foreach ($semesters as $sem => $subjects) { ?>
        <h3><?= $sem ?></h3>
        <table>
            <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Grade</th>
            </tr>
            <?php foreach ($subjects as $sub) { ?>
                <tr>
                    <td><?= $sub['subject_code'] ?></td>
                    <td><?= $sub['subject_title'] ?></td>
                    <td><?= $sub['grade'] ?></td>
                </tr>
                <?php
                $final_total += $sub['grade'];
                $final_count++;
            } ?>
        </table>
        <?php $gwa = calculateGWA($subjects); ?>
        <div class="gwa">GWA: <?= $gwa ?></div>

        <?php if ($gwa <= 1.75) { ?>
            <div class="message">
                "Congratulations on making it to the <span class='highlight'>President's/Dean's List!</span><br>
                Your hard work and dedication have truly paid off.<br>
                Keep striving for excellence!"
            </div>
        <?php } ?>
    <?php } ?>

    <?php 
    $final_gwa = round($final_total / $final_count, 2); 
    ?>
    <div class="gwa">Final GWA: <?= $final_gwa ?></div>

    <?php if ($year_level == 4) { 
        if ($final_gwa <= 1.20) {
            $honor = "Summa Cum Laude";
        } elseif ($final_gwa <= 1.45) {
            $honor = "Magna Cum Laude";
        } elseif ($final_gwa <= 1.75) {
            $honor = "Cum Laude";
        } else {
            $honor = "";
        }

        if ($honor) {
            echo "<div class='message'>You graduated with <span class='highlight'>$honor</span> honors. Congratulations!</div>";
        }
    } ?>
</div>
</body>
</html>

<?php $conn->close(); ?>