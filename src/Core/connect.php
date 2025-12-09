<?php
function myQuery($qry) {
$servername = "localhost";
$username   = "xgrab_usr";   
$password   = "12345PhP"; 
$dbname     = "xgrab"; 

$conn = new mysqli($servername, $username, $password, $dbname);
echo "<script>console.log('Connected successfully');</script>";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = mysqli_query($conn, $qry);
mysqli_close($conn);
return $result;
}
?>
