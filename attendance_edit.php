<?php
include 'db_connection.php';
include 'template.php';

if(!isset($_GET['id'])){
    echo "<p style='color:red; text-align:center;'>No attendance ID provided.</p>";
    exit;
}

$attendance_id = intval($_GET['id']);

// Fetch record with employee info
$result = $conn->query("SELECT a.*, e.first_name, e.last_name 
                        FROM attendance a
                        JOIN employees e ON a.employee_id = e.employee_id
                        WHERE a.attendance_id = $attendance_id");

if($result->num_rows == 0){
    echo "<p style='color:red; text-align:center;'>Attendance record not found.</p>";
    exit;
}

$row = $result->fetch_assoc();

// Update logic
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];

    $conn->query("UPDATE attendance 
                  SET status='$status', remarks='$remarks' 
                  WHERE attendance_id = $attendance_id");

    echo "<p style='color:green; text-align:center;'>Attendance updated successfully.</p>";

    // Refresh row
    $result = $conn->query("SELECT a.*, e.first_name, e.last_name 
                            FROM attendance a
                            JOIN employees e ON a.employee_id = e.employee_id
                            WHERE a.attendance_id = $attendance_id");
    $row = $result->fetch_assoc();
}
?>

<h2 style="text-align:center;">Edit Attendance (ID: <?php echo $attendance_id; ?>)</h2>

<form method="POST" style="width:400px; margin:30px auto; padding:20px; background:white; border-radius:10px;">
    <p><strong>Employee:</strong> <?php echo $row['first_name'].' '.$row['last_name']; ?> (ID: <?php echo $row['employee_id']; ?>)</p>

    <label>Status:</label><br>
    <select name="status" id="statusSelect" required style="width:100%; padding:10px; margin:10px 0;">
        <option value="Present" <?php if($row['status']=="Present") echo "selected"; ?>>Present</option>
        <option value="Absent" <?php if($row['status']=="Absent") echo "selected"; ?>>Absent</option>
    </select>

    <label>Remarks:</label><br>
    <select name="remarks" id="remarksSelect" required style="width:100%; padding:10px; margin:10px 0;">
        <option value="">-- Select Remark --</option>
    </select>

    <input type="submit" value="Update Attendance" style="background:#28a745; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">
</form>

<script>
function populateRemarks() {
    const status = document.getElementById('statusSelect').value;
    const remarksSelect = document.getElementById('remarksSelect');

    remarksSelect.innerHTML = ""; // reset

    if(status === "Present") {
        const options = ["On time","Late"];
        options.forEach(opt => {
            let selected = ("<?php echo $row['remarks']; ?>" === opt) ? "selected" : "";
            remarksSelect.innerHTML += `<option value="${opt}" ${selected}>${opt}</option>`;
        });
    } else { // Absent
        const options = ["Sick","Personal","Vacation"];
        options.forEach(opt => {
            let selected = ("<?php echo $row['remarks']; ?>" === opt) ? "selected" : "";
            remarksSelect.innerHTML += `<option value="${opt}" ${selected}>${opt}</option>`;
        });
    }
}

// Initial population
populateRemarks();

// Update remarks when status changes
document.getElementById('statusSelect').addEventListener('change', populateRemarks);
</script>
