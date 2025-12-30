<?php
session_start();
require("connect.php");

// Access Control: Only Owners
if ($_SESSION['user_type'] != 'owner') die("Access Denied");

$accountID = $_SESSION['global_id'];
$msg = "";

// Handle Form Submission
if (isset($_POST['process'])) {
    $amount = $_POST['amount'];
    $explanation = $_POST['explanation'];
    $type = $_POST['type']; // 'deposit' or 'withdrawal'
    $date = date('Y-m-d'); // System Date

    if ($type == 'deposit') {
        $sql = "INSERT INTO tbldeposit (accountID, processDate, explanation, amount) 
                VALUES ($accountID, '$date', '$explanation', $amount)";
    } else {
        // Corrected table name 'thlwithdrawal' to 'tblwithdrawal' based on standard conventions
        $sql = "INSERT INTO tblwithdrawal (accountID, processDate, explanation, amount) 
                VALUES ($accountID, '$date', '$explanation', $amount)";
    }
    myQuery($sql);
    $msg = "Transaction Successful!";
}

// 1. Get Deposits
$q_dep = "SELECT SUM(amount) as total_dep FROM tbldeposit WHERE accountID = $accountID";
$r_dep = mysqli_fetch_assoc(myQuery($q_dep));
$total_dep = $r_dep['total_dep'] ?? 0;

// 2. Get Withdrawals
$q_wit = "SELECT SUM(amount) as total_wit FROM tblwithdrawal WHERE accountID = $accountID";
$r_wit = mysqli_fetch_assoc(myQuery($q_wit));
$total_wit = $r_wit['total_wit'] ?? 0;

// Calculated Net Balance
$net_balance = $total_dep - $total_wit;
?>
<!DOCTYPE html>
<html>
<body>
    <h3>Make Transaction</h3>
    <a href="menu.php">Back to Menu</a>
    <div style="border: 1px solid #000; padding: 10px; margin: 10px 0;">
        <strong>Account Name:</strong> <?php echo $_SESSION['name']; ?><br>
        <strong>Total Deposited:</strong> <?php echo $total_dep; ?><br>
        <strong>Total Withdrawn:</strong> <?php echo $total_wit; ?><br>
        <strong>Net Balance:</strong> <?php echo $net_balance; ?><br>
    </div>
    <?php if($msg) echo "<h4 style='color:green'>$msg</h4>"; ?>
    <form method="post">
        Amount: <input type="number" step="0.01" name="amount" required><br><br>
        Explanation: <input type="text" name="explanation" required><br><br>
        Type:
        <input type="radio" name="type" value="deposit" checked> Deposit
        <input type="radio" name="type" value="withdrawal"> Withdrawal<br><br>
        <input type="submit" name="process" value="Submit Transaction">
    </form>
</body>
</html>