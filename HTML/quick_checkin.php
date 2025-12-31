<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include __DIR__ . "/../config/db_connect.php";

// Fetch all students for dropdown
$students = $conn->query("SELECT id, student_id, firstname, middlename, lastname, section, grade_level FROM students ORDER BY lastname, firstname");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quick Check-in</title>
    <link rel="stylesheet" href="../CSS/student_table.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        h2 {
            color: #1e293b;
            margin: 0 0 25px 0;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        select,
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        select:focus,
        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-checkin {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
            transition: transform 0.2s;
        }

        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-checkin:hover,
        .btn-checkout:hover {
            transform: translateY(-2px);
        }

        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            display: none;
        }

        .result.success {
            background: #d4edda;
            color: #155724;
            display: block;
        }

        .result.error {
            background: #f8d7da;
            color: #721c24;
            display: block;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
        }

        .student-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .student-info.visible {
            display: block;
        }

        .student-info p {
            margin: 5px 0;
            color: #475569;
        }

        .student-info strong {
            color: #1e293b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <h2>📋 Quick Check-in / Check-out</h2>

            <div class="form-group">
                <label>Select Student</label>
                <select id="student-select">
                    <option value="">-- Choose a student --</option>
                    <?php while ($s = $students->fetch_assoc()):
                        $fullName = $s['firstname'] . ' ' . ($s['middlename'] ? $s['middlename'] . ' ' : '') . $s['lastname'];
                        ?>
                        <option value="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars($fullName); ?>"
                            data-studentid="<?php echo htmlspecialchars($s['student_id']); ?>"
                            data-section="<?php echo htmlspecialchars($s['section']); ?>"
                            data-grade="<?php echo htmlspecialchars($s['grade_level']); ?>">
                            <?php echo htmlspecialchars($fullName); ?> (<?php echo $s['student_id']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="student-info" id="student-info">
                <p><strong>Student ID:</strong> <span id="info-studentid"></span></p>
                <p><strong>Grade & Section:</strong> <span id="info-grade"></span> - <span id="info-section"></span></p>
            </div>

            <button class="btn-checkin" id="btn-checkin" disabled>✅ TIME IN</button>
            <button class="btn-checkout" id="btn-checkout" disabled>🚪 TIME OUT</button>

            <div class="result" id="result"></div>
        </div>

        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        const studentSelect = document.getElementById('student-select');
        const studentInfo = document.getElementById('student-info');
        const btnCheckin = document.getElementById('btn-checkin');
        const btnCheckout = document.getElementById('btn-checkout');
        const resultDiv = document.getElementById('result');

        studentSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];

            if (this.value) {
                document.getElementById('info-studentid').textContent = selected.dataset.studentid;
                document.getElementById('info-grade').textContent = selected.dataset.grade;
                document.getElementById('info-section').textContent = selected.dataset.section;
                studentInfo.classList.add('visible');
                btnCheckin.disabled = false;
                btnCheckout.disabled = false;
            } else {
                studentInfo.classList.remove('visible');
                btnCheckin.disabled = true;
                btnCheckout.disabled = true;
            }

            resultDiv.className = 'result';
        });

        btnCheckin.addEventListener('click', function () {
            recordAttendance('TIME IN');
        });

        btnCheckout.addEventListener('click', function () {
            recordAttendance('TIME OUT');
        });

        function recordAttendance(status) {
            const studentId = studentSelect.value;
            if (!studentId) {
                alert('Please select a student');
                return;
            }

            btnCheckin.disabled = true;
            btnCheckout.disabled = true;

            fetch('quick_attendance_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'student_id=' + encodeURIComponent(studentId) + '&status=' + encodeURIComponent(status)
            })
                .then(response => response.json())
                .then(data => {
                    resultDiv.textContent = data.message;
                    resultDiv.className = 'result ' + (data.success ? 'success' : 'error');

                    btnCheckin.disabled = false;
                    btnCheckout.disabled = false;

                    // Reset after 3 seconds
                    if (data.success) {
                        setTimeout(() => {
                            studentSelect.value = '';
                            studentInfo.classList.remove('visible');
                            btnCheckin.disabled = true;
                            btnCheckout.disabled = true;
                            resultDiv.className = 'result';
                        }, 3000);
                    }
                })
                .catch(error => {
                    resultDiv.textContent = 'Error: ' + error.message;
                    resultDiv.className = 'result error';
                    btnCheckin.disabled = false;
                    btnCheckout.disabled = false;
                });
        }
    </script>
</body>

</html>