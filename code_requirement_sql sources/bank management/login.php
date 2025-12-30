<?php
session_start();
require("connect.php");
$error = "";

if (isset($_POST['submit'])) {
    // Escape inputs manually since myQuery doesn't support prepared statements directly
    $login_input = $_POST['login'];
    $password = md5($_POST['password']); // Requirement: MD5 conversion

    // 1. Check if user is a Branch Manager (Login using Branch Name)
    $sql_branch = "SELECT * FROM tblbranch WHERE branchName = '$login_input' AND password = '$password'";
    $res_branch = myQuery($sql_branch);

    if (mysqli_num_rows($res_branch) == 1) {
        $row = mysqli_fetch_assoc($res_branch);
        $_SESSION['user_type'] = 'manager';
        $_SESSION['global_id'] = $row['branchID']; // Global ID as BranchID
        $_SESSION['name'] = $row['branchName'];
        header("Location: midterm02/menu.php");
        exit();
    }

    // 2. If not Manager, check if Account Owner (Login using IBAN)
    $sql_account = "SELECT * FROM tblaccount WHERE accountIBAN = '$login_input' AND password = '$password'";
    $res_account = myQuery($sql_account);

    if (mysqli_num_rows($res_account) == 1) {
        $row = mysqli_fetch_assoc($res_account);
        $_SESSION['user_type'] = 'owner';
        $_SESSION['global_id'] = $row['accountID']; // Global ID as AccountID
        $_SESSION['name'] = $row['accountName'];
        $_SESSION['iban'] = $row['accountIBAN'];
        header("Location: midterm02/menu.php");
        exit();
    }
    $error = "Invalid Login (Branch Name or IBAN) or Password";
}
?>
<!DOCTYPE html>
<html>
<head><title>Bank Login</title></head>
<body style="font-family: sans-serif; padding: 20px;">
    <h2>Bank Management System Login</h2>
    <?php if($error) echo "<p style='color: red'>$error</p>"; ?>
    <form method="post">
        <label>Login (Branch Name OR IBAN): </label><br>
        <input type="text" name="login" required><br><br>
        <label>Password: </label><br>
        <input type="password" name="password" required><br><br>
        <input type="submit" name="submit" value="Login">
    </form>
</body>
</html>