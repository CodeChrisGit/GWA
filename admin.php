<?php
$conn = new mysqli("localhost", "root", "", "student_info");

$rows = isset($_POST["rows"]) ? (int)$_POST["rows"] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_grades"])) {
    $username = $_POST["username"];
    $semester = $_POST["semester"];

    $codes = $_POST["subject_code"];
    $titles = $_POST["subject_title"];
    $grades = $_POST["grade"];
    $units = $_POST["units"];

    for ($i = 0; $i < count($codes); $i++) {
        $code = $codes[$i];
        $title = $titles[$i];
        $grade = $grades[$i];
        $unit = $units[$i];

        $stmt = $conn->prepare("INSERT INTO grades (username, semester, subject_code, subject_title, grade, units) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdi", $username, $semester, $code, $title, $grade, $unit);
        $stmt->execute();
    }

    echo "<div class='message'>Grades inserted successfully!</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Grade Entry</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f4f9;
            padding: 40px;
        }
        form {
            background: #fff;
            padding: 25px;
            max-width: 800px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin: 6px 0 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
        }
        button:hover {
            background: #0056b3;
        }
        .message {
            max-width: 800px;
            margin: 20px auto;
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-left: 5px solid #28a745;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<?php if ($rows <= 0): ?>
<!-- Ask for number of rows -->
<form method="post">
    <h2>How many subjects do you want to insert?</h2>
    <label>Number of rows:</label>
    <input type="number" name="rows" min="1" max="20" required>
    <button type="submit">Proceed</button>
</form>

<?php else: ?>
<!-- Grade entry form -->
<form method="post">
    <h2>Admin Grade Entry</h2>

    <input type="hidden" name="rows" value="<?= $rows ?>">
    <label>Student Username:</label>
    <input type="text" name="username" required>

    <label>Semester:</label>
    <input type="text" name="semester" placeholder="e.g., 2nd Year - 1st Sem" required>

    <table>
        <tr>
            <th>Subject Code</th>
            <th>Subject Title</th>
            <th>Units</th>
            <th>Grade</th>
        </tr>
        <?php for ($i = 0; $i < $rows; $i++): ?>
        <tr>
            <td><input type="text" name="subject_code[]"></td>
            <td><input type="text" name="subject_title[]"></td>
            <td><input type="number" name="units[]" step="0.1"></td>
            <td><input type="number" name="grade[]" step="0.01"></td>
        </tr>
        <?php endfor; ?>
    </table>

    <button type="submit" name="submit_grades">Insert Grades</button>
</form>
<?php endif; ?>

</body>
</html>
