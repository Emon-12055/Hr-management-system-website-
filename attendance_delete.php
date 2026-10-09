<?php
include 'db_connection.php';
include 'template.php';

// Check if ID is passed
if(!isset($_GET['id'])){
    echo "<p style='color:red; text-align:center;'>No attendance ID provided.</p>";
    exit;
}

$attendance_id = intval($_GET['id']);

// Delete record
$sql = "DELETE FROM attendance WHERE attendance_id = $attendance_id";
if($conn->query($sql) === TRUE){
    echo "<p style='color:green; text-align:center;'>Attendance record deleted successfully.</p>";
} else {
    echo "<p style='color:red; text-align:center;'>Error deleting record: ".$conn->error."</p>";
}

// Redirect back to attendance list after 1.5 seconds
echo "<script>
        setTimeout(function(){
            window.location.href='attendance_view.php';
        }, 1500);
      </script>";
?>
