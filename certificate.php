<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];

$user_result = $conn->query("SELECT student_name, year_level FROM users WHERE username='$username'");
$user = $user_result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$student_name = $user['student_name'];
$year_level = $user['year_level'];

$grade_result = $conn->query("SELECT * FROM grades WHERE username='$username' ORDER BY semester");
$course_result = $conn->query("SELECT course FROM grades WHERE username='$username' LIMIT 1");
$course_row = $course_result->fetch_assoc();
$course = $course_row['course'];

$semesters = [];
while ($row = $grade_result->fetch_assoc()) {
    $semesters[$row['semester']][] = $row;
}

$achieved_dl = false; // We'll only check for Dean's List based on semesters

function calculateSemesterGWA($grades) {
    $excluded_subjects = ['NSTP1', 'NSTP2'];
    $weighted_total = 0;
    $total_units = 0;

    foreach ($grades as $grade_info) {
        if (in_array($grade_info['subject_code'], $excluded_subjects)) continue;
        if ($grade_info['grade'] !== null && $grade_info['grade'] !== '') {
            $weighted_total += $grade_info['grade'] * $grade_info['Units'];
            $total_units += $grade_info['Units'];
        }
    }
    return $total_units > 0 ? round($weighted_total / $total_units, 4) : 0;
}

foreach ($semesters as $semester_grades) {
    $semester_gwa = calculateSemesterGWA($semester_grades);
    if ($semester_gwa > 1.50 && $semester_gwa <= 1.75) {
        $achieved_dl = true;
    }
}

$final_grade_result = $conn->query("SELECT grade, Units, subject_code FROM grades WHERE username='$username'");
$final_weighted_total = 0;
$final_total_units = 0;
$overall_hasLowerGrade = false;
$achieved_pl = false; // Initialize here

while ($row = $final_grade_result->fetch_assoc()) {
    if (in_array($row['subject_code'], ['NSTP1', 'NSTP2'])) continue;
    if ($row['grade'] === null || $row['grade'] === '') continue;

    $final_weighted_total += $row['grade'] * $row['Units'];
    $final_total_units += $row['Units'];

    if ($row['grade'] >= 2.4) {
        $overall_hasLowerGrade = true;
    }
}

$final_gwa = $final_total_units > 0 ? round($final_weighted_total / $final_total_units, 4) : 0;

function getHonor($gwa, $hasLowerGrade) {
    if ($hasLowerGrade) return "No Latin Honors";
    if ($gwa <= 1.20) return "Summa Cum Laude";
    if ($gwa <= 1.45) return "Magna Cum Laude";
    if ($gwa <= 1.75) return "Cum Laude";
    return "No Latin Honors";
}

$honor = getHonor($final_gwa, $overall_hasLowerGrade);

if ($final_gwa <= 1.50 && $final_gwa > 0 && !$overall_hasLowerGrade) {
    $achieved_pl = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Certificate of Academic Achievement</title>
    <style>
        @media print {
            @page {
                size: landscape;
            }
        }
        body {
            background-color: #fdf6e3;
            font-family: 'Georgia', serif;
            padding: 40px;
            text-align: center;
        }
        .certificate {
            border: 10px solid #8b4513;
            padding: 50px;
            width: 90%;
            margin: auto;
            background-color: #fff8dc;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 40px;
            color: #8b4513;
        }
        h2 {
            font-size: 24px;
            font-style: italic;
            margin-top: 20px;
        }
        p {
            font-size: 18px;
            margin: 10px 0;
        }
        .highlight {
            font-weight: bold;
            color: #b8860b;
        }
        .footer {
            margin-top: 40px;
            font-size: 16px;
            color: #555;
        }
        .btn-back {
            margin-top: 40px;
            display: inline-block;
            background-color: #8b4513;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-back:hover {
            background-color: #a0522d;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <h1>Certificate of Academic Achievement</h1>
        <p>This is to certify that</p>
        <h2><?= htmlspecialchars($student_name) ?></h2>
        <p>is currently a <span class="highlight"><?= htmlspecialchars($year_level) ?></span> student</p>
        <p>enrolled in the program</p>
        <p class="highlight"><?= htmlspecialchars($course) ?></p>
        <?php if ($year_level !== '4th Year'): ?>
        <p>
            <?php
            $achievements = [];
            if ($achieved_pl) {
                $achievements[] = "Achieved President's List based on overall GWA.";
            }
            if ($achieved_dl) {
                $achievements[] = "Achieved Dean's List at some point.";
            }

            if (!empty($achievements)) {
                echo implode("<br>", $achievements);
            } else {
                echo "Academic performance is in good standing.";
            }
            ?>
        </p>
        <?php else: ?>
        <p>Final General Weighted Average (GWA): <span class="highlight"><?= $final_gwa ?></span></p>
        <p>
            <?php if ($honor !== "No Latin Honors"): ?>
                With the distinction of <span class="highlight"><?= $honor ?></span>, awarded for academic excellence.
            <?php else: ?>
                Has successfully completed the program requirements.
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <p>Issued this day, <?= date("F j, Y") ?>.</p>
        <div class="footer">
            <p><em>Registrar's Office</em></p>
        </div>
        <a href="welcome.php" class="btn-back">Back to Grades</a>
    </div>
</body>
</html>


<?php $conn->close(); ?>
