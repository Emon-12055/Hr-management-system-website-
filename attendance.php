<?php
include 'db_connection.php';
include 'template.php';

$today = date("Y-m-d");

// Form submit handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['attendance'])){
        foreach ($_POST['attendance'] as $emp_id => $status) {
            $status = ($status == "Present") ? "Present" : "Absent";
            $remark = isset($_POST['remark'][$emp_id]) ? $_POST['remark'][$emp_id] : '';

            $sql = "INSERT INTO attendance (employee_id, attendance_date, status, remarks) 
                    VALUES ('$emp_id', '$today', '$status', '$remark')
                    ON DUPLICATE KEY UPDATE status='$status', remarks='$remark'";

            $conn->query($sql);
        }
        echo "<p style='color:green; text-align:center;'>Attendance saved for $today</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Attendance</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: Arial;
            display: flex;
            flex-direction: column;
            background: #f4f6f9;
        }

        .container {
            width: 95%;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            flex: 1;
        }

        h2 { text-align: center; color: #007BFF; margin-bottom: 20px; }

        .date-badge {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }
        th { background: #007BFF; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        select { padding: 5px; border-radius: 5px; border: 1px solid #ccc; }
        input[type="submit"] { margin-top: 15px; background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; display: block; margin-left: auto; margin-right: auto; }
        input[type="submit"]:hover { background: #218838; }

        #downloadPdf { background:#007BFF; margin-bottom:15px; padding:10px 20px; color:white; border:none; border-radius:5px; cursor:pointer; }

        .footer {
            background: #007BFF;
            color: white;
            text-align: center;
            padding: 15px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="date-badge"><?php echo "Date: $today"; ?></div>

    <h2>Employee Attendance</h2>

    <button id="downloadPdf">Download PDF</button>

    <form method="POST">
        <table id="attendanceTable">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Job Title</th>
                <th>Department</th>
                <th>Attendance</th>
                <th>Remark</th>
            </tr>
            <?php
            $sql = "SELECT e.employee_id, CONCAT(e.first_name,' ',e.last_name) AS full_name,
                           j.job_title, d.department_name
                    FROM employees e
                    LEFT JOIN jobs j ON e.job_id=j.job_id
                    LEFT JOIN departments d ON e.department_id=d.department_id";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $emp_id = $row['employee_id'];

                    // Fetch existing attendance for today
                    $att_res = $conn->query("SELECT status, remarks FROM attendance WHERE employee_id='$emp_id' AND attendance_date='$today'");
                    $status_checked = $remark_selected = '';
                    if($att_res && $att_res->num_rows > 0){
                        $att_row = $att_res->fetch_assoc();
                        $status_checked = $att_row['status'];
                        $remark_selected = $att_row['remarks'];
                    }

                    echo "<tr>
                            <td>{$emp_id}</td>
                            <td>{$row['full_name']}</td>
                            <td>{$row['job_title']}</td>
                            <td>{$row['department_name']}</td>
                            <td>
                                <label><input type='radio' name='attendance[$emp_id]' value='Present' ".($status_checked=='Present'?'checked':'')." required onchange='toggleRemark($emp_id, \"Present\")'> Present</label>
                                <label><input type='radio' name='attendance[$emp_id]' value='Absent' ".($status_checked=='Absent'?'checked':'')." onchange='toggleRemark($emp_id, \"Absent\")'> Absent</label>
                            </td>
                            <td>
                                <select name='remark[$emp_id]' id='remark_$emp_id'></select>
                                <script>
                                    const savedRemark_$emp_id = '".($remark_selected ?? '')."';
                                    toggleRemark($emp_id, '".($status_checked ?: '')."', savedRemark_$emp_id);
                                </script>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No employees found</td></tr>";
            }
            ?>
        </table>
        <input type="submit" value="Save Attendance">
    </form>
</div>

<footer class="footer">
    &copy; <?php echo date("Y"); ?> HR Management System. All rights reserved.
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function toggleRemark(empId, status, savedRemark = '') {
    const remarkSelect = document.getElementById('remark_' + empId);
    remarkSelect.innerHTML = "<option value=''>-- Select --</option>";
    let options = [];
    if(status === "Present") options = ["On time","Late"];
    else if(status === "Absent") options = ["Sick","Personal","Vacation"];
    options.forEach(opt => {
        const option = document.createElement('option');
        option.value = opt;
        option.text = opt;
        if(opt === savedRemark) option.selected = true;
        remarkSelect.appendChild(option);
    });
}

// PDF Download
document.getElementById('downloadPdf').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(16);
    doc.text("Employee Attendance", 105, 15, null, null, "center");
    doc.autoTable({ 
        html: '#attendanceTable',
        startY: 25,
        styles: { fontSize: 10 },
        headStyles: { fillColor: [0, 123, 255] },
        alternateRowStyles: { fillColor: [240,240,240] }
    });
    doc.save('Employee_Attendance_<?php echo $today; ?>.pdf');
});
</script>
</body>
</html>
