<?php
/**
 * Database Connection Utility
 * Provides functions for establishing database connections and executing queries.
 * This file centralizes database access for the application.
 */

/**
 * Establishes a new MySQL database connection using mysqli.
 * Creates a connection to the local MySQL server with the movie database.
 * 
 * @return mysqli Database connection object
 */
function getConnection()
{
    // Database connection parameters for local XAMPP environment
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "movie";

    // Create new mysqli connection object
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if connection failed and terminate script with error message
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Executes a SQL query and returns the result.
 * Opens a connection, executes the query, closes the connection, and returns the result.
 * This function uses a connection-per-query pattern which is simple but not optimal for multiple queries.
 * 
 * @param string $qry SQL query string to execute
 * @return mysqli_result|bool Query result object on success, false on failure
 */
function myQuery($qry)
{
    // Get a fresh database connection
    $conn = getConnection();

    // Execute the query using the connection
    $result = mysqli_query($conn, $qry);

    // Close the connection immediately after query execution
    mysqli_close($conn);

    return $result;
}

/**
 * Escapes special characters in a string for use in SQL queries.
 * Prevents SQL injection by escaping characters that have special meaning in SQL.
 * Note: This function creates a new connection just for escaping, which is inefficient for multiple escapes.
 * 
 * @param string $str String to escape
 * @return string Escaped string safe for use in SQL queries
 */
function escapeString($str)
{
    // Get a database connection to access the escape function
    $conn = getConnection();

    // Escape special characters using the connection's character set
    $escaped = mysqli_real_escape_string($conn, $str);

    // Close the connection
    mysqli_close($conn);

    return $escaped;
}

//$servername = "localhost";
//$username = "root";
//$password = "j1_vtk4I|Ohj.a%g1vW0O&xb3T";
//$dbname = "movie";
?>