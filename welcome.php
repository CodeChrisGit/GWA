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
$student_name = $user['student_name'];
$year_level = $user['year_level'];

$course_result = $conn->query("SELECT course FROM grades WHERE username='$username' LIMIT 1");
$course_row = $course_result->fetch_assoc();
$course = $course_row['course'];

$grade_result = $conn->query("SELECT * FROM grades WHERE username='$username' ORDER BY semester, subject_code");

$semesters = [];
$yearly_grades_data = ['1st Year' => [], '2nd Year' => [], '3rd Year' => [], '4th Year' => []];
$yearly_gwas = [];
$has_blank_grade = false;

while ($row = $grade_result->fetch_assoc()) {
    if ($row['grade'] === "" || $row['grade'] === null) {
        $has_blank_grade = true;
    }
    $semesters[$row['semester']][] = $row;

    if (strpos($row['semester'], '1st Year') !== false) {
        $yearly_grades_data['1st Year'][] = $row;
    } elseif (strpos($row['semester'], '2nd Year') !== false) {
        $yearly_grades_data['2nd Year'][] = $row;
    } elseif (strpos($row['semester'], '3rd Year') !== false) {
        $yearly_grades_data['3rd Year'][] = $row;
    } elseif (strpos($row['semester'], '4th Year') !== false) {
        $yearly_grades_data['4th Year'][] = $row;
    }
}

function calculateGWA($grades) {
    $excluded_subjects = ['NSTP1', 'NSTP2'];
    $weighted_total = 0;
    $total_units = 0;
    $hasLowerGrade = false;

    foreach ($grades as $grade_info) {
        if (in_array($grade_info['subject_code'], $excluded_subjects)) continue;
        $weighted_total += $grade_info['grade'] * $grade_info['Units'];
        $total_units += $grade_info['Units'];
        if ($grade_info['grade'] >= 2.4) {
            $hasLowerGrade = true;
        }
    }
    $gwa = $total_units > 0 ? round($weighted_total / $total_units, 4) : 0;
    return ['gwa' => $gwa, 'hasLowerGrade' => $hasLowerGrade];
}

$final_weighted_total = 0;
$final_total_units = 0;
$overall_hasLowerGrade = false;

foreach ($yearly_grades_data as $year => $grades) {
    $yearly_gwa_result = calculateGWA($grades);
    $yearly_gwas[$year] = $yearly_gwa_result;
    foreach ($grades as $grade_info) {
        if (!in_array($grade_info['subject_code'], ['NSTP1', 'NSTP2'])) {
            $final_weighted_total += $grade_info['grade'] * $grade_info['Units'];
            $final_total_units += $grade_info['Units'];
        }
        if ($grade_info['grade'] >= 2.4) {
            $overall_hasLowerGrade = true;
        }
    }
}

$final_gwa = $final_total_units > 0 ? round($final_weighted_total / $final_total_units, 4) : 0;

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
        h2, h3 {
            margin-bottom: 5px;
        }
        .gwa, .message {
            margin-top: 10px;
        }
        .highlight {
            font-weight: bold;
            color: yellow;
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
            margin-bottom: 30px;
        }
        .semester-toggle-wrapper {
            margin-top: 20px;
        }
        .toggle-label {
            cursor: pointer;
            background-color: #007acc;
            padding: 5px 10px;
            display: inline-block;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .toggle-label:hover {
            background-color: #005999;
        }
        #show-all {
            display: none;
        }
        .hidden-sem {
            display: none;
        }
        #show-all:checked ~ .semesters .hidden-sem {
            display: block;
        }
        .logout-btn {
            color: white;
            text-decoration: none;
            background-color: #c0392b;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .logout-btn:hover {
            background-color: #a93226;
        }

    </style>
</head>
<body>
<div class="box">
    <h2><?= $student_name ?></h2>
    <p><?= $year_level ?><br><?= $course ?><br>2028–2029</p>

    <div class="semester-toggle-wrapper">
        <input type="checkbox" id="show-all">
        <label for="show-all" class="toggle-label">Show All Semesters</label>

        <div class="semesters">
            <?php
            $i = 0;
            foreach ($semesters as $sem => $subjects):
                $is_hidden = $i < count($semesters) - 1 ? "hidden-sem" : "";
                $semester_gwa_data = calculateGWA($subjects);
                $semester_gwa = $semester_gwa_data['gwa'];
                $semester_hasLowerGrade = $semester_gwa_data['hasLowerGrade'];
                $blank_Grade_semester = false;
                ?>
                <div class="<?= $is_hidden ?>">
                    <h3><?= $sem ?></h3>
                    <table>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Grade</th>
                            <th>Units</th>
                        </tr>
                        <?php foreach ($subjects as $sub):
                            if ($sub['grade'] === "" || $sub['grade'] === null) {
                                $blank_Grade_semester = true;
                            }
                            ?>
                            <tr>
                                <td><?= $sub['subject_code'] ?></td>
                                <td><?= $sub['subject_title'] ?></td>
                                <td><?= $sub['grade'] ?></td>
                                <td><?= $sub['Units'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <div class="gwa">GWA: <?= $semester_gwa ?></div>

                    <?php if ($blank_Grade_semester): ?>
                        <div class="message">
                            Notice: Some subject grades are still pending. <span class='highlight'>The displayed GWA is based only on completed subjects</span>. Once all grades are available, your final GWA may improve. Stay focused and finish strong—you’re doing great!
                        </div>
                    <?php elseif ($semester_gwa <= 1.50 && !$semester_hasLowerGrade): ?>
                        <div class="message">
                            Congratulations on making it to the <span class='highlight'>President's List!</span><br>
                            Your hard work and dedication have truly paid off.<br>
                            Keep striving for excellence!
                        </div>
                    <?php elseif ($semester_gwa > 1.50 && $semester_gwa <= 1.75 && !$semester_hasLowerGrade): ?>
                        <div class="message">
                            Congratulations on making it to the <span class='highlight'>Dean's List!</span><br>
                            Your hard work and dedication have truly paid off.<br>
                            Keep striving for excellence.
                        </div>
                    <?php else: ?>
                        <div class="message">
                            Keep working hard! Your GWA is improving,<br>
                            and you're on the right track to making it to the honor lists.<br>
                            Stay consistent and aim higher next semester!
                        </div>
                    <?php endif; ?>
                </div>
            <?php $i++; endforeach; ?>
        </div>
    </div>

    <h3>Yearly GWA Summary</h3>
    <table>
        <tr>
            <th>Year Level</th>
            <th>GWA</th>
            <th>Honor</th>
        </tr>
        <?php foreach ($yearly_gwas as $year => $gwa_data):
            $year_gwa = $gwa_data['gwa'];
            $year_hasLowerGrade = $gwa_data['hasLowerGrade'];
            $honor = "";
            if ($year_gwa > 0) {
                if ($year_gwa <= 1.50 && !$year_hasLowerGrade) {
                    $honor = "President's List";
                } elseif ($year_gwa > 1.50 && $year_gwa <= 1.75 && !$year_hasLowerGrade) {
                    $honor = "Dean's List";
                }
            }
            echo "<tr><td>$year</td><td>$year_gwa</td><td>$honor</td></tr>";
        endforeach; ?>
    </table>

    <div class="gwa">Final GWA: <?= $final_gwa ?></div><br>
    <?php

    if ($year_level == "4th Year"):
        if ($final_gwa <= 1.20 && !$overall_hasLowerGrade): ?>
            <div class='message'>
                Congratulations to <span class='highlight'><?= $student_name ?></span> for graduating with flying colors! With an impressive final GWA of <span class='highlight'><?= $final_gwa ?></span>, you have earned the distinguished honor of <span class='highlight'>Summa Cum Laude</span>. Your hard work and dedication have truly paid off. Well done and best wishes for your future!
            </div>
        <?php elseif ($final_gwa <= 1.45 && !$overall_hasLowerGrade): ?>
            <div class='message'>
                Congratulations to <span class='highlight'><?= $student_name ?></span> for graduating with flying colors! With an impressive final GWA of <span class='highlight'><?= $final_gwa ?></span>, you have earned the distinguished honor of <span class='highlight'>Magna Cum Laude</span>. Your hard work and dedication have truly paid off. Well done and best wishes for your future!
            </div>
        <?php elseif ($final_gwa <= 1.75 && !$overall_hasLowerGrade): ?>
            <div class='message'>
                Congratulations to <span class='highlight'><?= $student_name ?></span> for graduating with flying colors! With an impressive final GWA of <span class='highlight'><?= $final_gwa ?></span>, you have earned the distinguished honor of <span class='highlight'>Cum Laude</span>. Your hard work and dedication have truly paid off. Well done and best wishes for your future!
            </div>
        <?php else: ?>
            <div class='message'>
                Congratulations to <span class='highlight'><?= $student_name ?></span> for successfully completing your degree program with a final GWA of <span class='highlight'><?= $final_gwa ?></span>! While you may not have qualified for Latin honors, your hard work and perseverance have led you to this milestone achievement. Best wishes for a bright and successful future ahead!
            </div>
        <?php endif;
    endif;
    ?>
    <?php if (!$has_blank_grade): ?>
        <a href="certificate.php" class="logout-btn" style="background-color: #2980b9; margin-left: 10px;">View Certificate</a>
    <?php endif; ?>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>
</body>
</html>

<?php
$conn->close();

?>
