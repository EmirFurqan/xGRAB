<?php
function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "movie";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function myQuery($qry) {
    $conn = getConnection();
    $result = mysqli_query($conn, $qry);
    mysqli_close($conn);
    return $result;
}

function escapeString($str) {
    $conn = getConnection();
    $escaped = mysqli_real_escape_string($conn, $str);
    mysqli_close($conn);
    return $escaped;
}

//$servername = "localhost";
//$username = "root";
//$password = "j1_vtk4I|Ohj.a%g1vW0O&xb3T";
//$dbname = "movie";
?>


    