<?php
function myQuery($qry) {
    $servername = "localhost";
    $username = "midtermka2_usr";
    $password = "12345PhP";
    $dbname = "midtermka2";
    
    // Fixed: added '=' assignment
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Fixed: added '=' assignment
    $result = mysqli_query($conn, $qry);
    mysqli_close($conn);
    return $result;
}
?>