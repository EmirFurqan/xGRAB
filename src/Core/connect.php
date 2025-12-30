<?php
/**
 * Database Connection Utility for src/App directory
 * Provides database query execution function for the alternative application structure.
 * Uses different database credentials than the root connect.php file.
 */

/**
 * Executes a SQL query against the xgrab database.
 * Establishes a connection, executes the query, closes the connection, and returns the result.
 * This function uses a connection-per-query pattern.
 * 
 * Note: This version uses different database credentials (xgrab database) than the root connect.php.
 * 
 * @param string $qry SQL query string to execute
 * @return mysqli_result|bool Query result object on success, false on failure
 */
function myQuery($qry) {
    // Database connection parameters for xgrab database
    $servername = "localhost";
    $username   = "xgrab_usr";   
    $password   = "12345PhP"; 
    $dbname     = "xgrab"; 

    // Create new mysqli connection object
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Debug output to browser console (should be removed in production)
    echo "<script>console.log('Connected successfully');</script>";
    
    // Check if connection failed and terminate script with error message
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Execute the query using the connection
    $result = mysqli_query($conn, $qry);
    
    // Close the connection immediately after query execution
    mysqli_close($conn);
    
    return $result;
}
?>
