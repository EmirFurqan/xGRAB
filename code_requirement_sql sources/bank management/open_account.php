<?php
session_start();
require("connect.php");
if ($_SESSION['user_type'] != 'manager') die("Access Denied");
$msg = "";

if (isset($_POST['save'])) {
    $iban = $_POST['iban'];
    $name = $_POST['name'];
    $pass = md5($_POST['password']);
    $branchID = $_SESSION['global_id']; // Auto-filled from session

    // Insert Query
    $sql = "INSERT INTO tblaccount (accountIBAN, accountName, password, branchID) 
            VALUES ('$iban', '$name', '$pass', $branchID)";
    
    if (myQuery($sql)) {
        $msg = "Account created successfully!";
    } else {
        $msg = "Error creating account.";
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h3>Open New Account</h3>
    <a href="menu.php">Back to Menu</a>
    <?php if($msg) echo "<p style='color:green'>$msg</p>"; ?>
    <form method="post">
        IBAN: <input type="text" name="iban" required><br><br>
        Account Name: <input type="text" name="name" required><br><br>
        Password: <input type="text" name="password" required><br>
        <small>(Write this on a post-it for the client)</small><br><br>
        <input type="submit" name="save" value="Create Account" >
    </form>
</body>
</html>