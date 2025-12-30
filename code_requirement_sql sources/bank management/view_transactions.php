<?php
session_start();
require("connect.php");

$targetAccountID = 0;
$accountInfo = null;

// Logic to determine which account to view
if ($_SESSION['user_type'] == 'owner') {
    // If Owner, view own account
    $targetAccountID = $_SESSION['global_id'];
} elseif ($_SESSION['user_type'] == 'manager' && isset($_POST['view_acc'])) {
    // If Manager selected an account
    $targetAccountID = $_POST['account_select'];
}

// If Manager and no account selected, show dropdown list
if ($_SESSION['user_type'] == 'manager' && $targetAccountID == 0) {
    $branchID = $_SESSION['global_id'];
    $sql_list = "SELECT accountID, accountName, accountIBAN FROM tblaccount WHERE branchID = $branchID";
    $res_list = myQuery($sql_list);
?>
<!DOCTYPE html>
<html>
<body>
    <h3>Select Account to View</h3>
    <a href="menu.php">Back</a>
    <form method="post">
        <select name="account_select">
            <?php while($row = mysqli_fetch_assoc($res_list)): ?>
                <option value="<?php echo $row['accountID']; ?>">
                    <?php echo $row['accountName']." (".$row['accountIBAN'].")"; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="submit" name="view_acc" value="View Transactions">
    </form>
</body>
</html>
<?php
    exit(); // Stop here if selecting
}

// Get Account Header Info
$sql_info = "SELECT a.accountName, a.accountIBAN, b.branchName
             FROM tblaccount a
             JOIN tblbranch b ON a.branchID = b.branchID
             WHERE a.accountID = $targetAccountID";
$res_info = myQuery($sql_info);
$accInfo = mysqli_fetch_assoc($res_info);

// Get Deposits
$sql_dep = "SELECT * FROM tbldeposit WHERE accountID = $targetAccountID ORDER BY processDate";
$res_dep = myQuery($sql_dep);

// Get Withdrawals
$sql_wit = "SELECT * FROM tblwithdrawal WHERE accountID = $targetAccountID ORDER BY processDate";
$res_wit = myQuery($sql_wit);

// Get Monthly Aggregates
$sql_monthly = "
    SELECT y, m,
    SUM(dep_total) as total_dep,
    SUM(wit_total) as total_wit,
    (SUM(dep_total) - SUM(wit_total)) as net_amount
    FROM (
        -- Get monthly sums from Deposits
        SELECT YEAR(processDate) as y, MONTH(processDate) as m, SUM(amount) as dep_total, 0 as wit_total
        FROM tbldeposit WHERE accountID = $targetAccountID GROUP BY y, m
        UNION ALL
        -- Get monthly sums from Withdrawals
        SELECT YEAR(processDate) as y, MONTH(processDate) as m, 0 as dep_total, SUM(amount) as wit_total
        FROM tblwithdrawal WHERE accountID = $targetAccountID GROUP BY y, m
    ) as combined_table
    GROUP BY y, m
    ORDER BY y DESC, m DESC";
    
$res_monthly = myQuery($sql_monthly);
$sum_dep = 0;
$sum_wit = 0;
?>
<!DOCTYPE html>
<html>
<body>
    <a href="menu.php">Back to Menu</a>
    <h2>Account Statement</h2>
    <p>
        <strong>Branch:</strong> <?php echo $accInfo['branchName']; ?> |
        <strong>Name:</strong> <?php echo $accInfo['accountName']; ?> |
        <strong>IBAN:</strong> <?php echo $accInfo['accountIBAN']; ?>
    </p>
    <hr>
    <h3>Detailed Transactions</h3>
    
    <table border="1" width="100%">
        <tr><td colspan="3"><strong>Deposits</strong></td></tr>
        <tr><th>Date</th><th>Explanation</th><th>Amount</th></tr>
        <?php while($r = mysqli_fetch_assoc($res_dep)):
            $sum_dep += $r['amount'];
        ?>
        <tr>
            <td><?php echo $r['processDate']; ?></td>
            <td><?php echo $r['explanation']; ?></td>
            <td><?php echo number_format($r['amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <br>
    
    <table border="1" width="100%">
        <tr><td colspan="3"><strong>Withdrawals</strong></td></tr>
        <tr><th>Date</th><th>Explanation</th><th>Amount</th></tr>
        <?php while($r = mysqli_fetch_assoc($res_wit)):
            $sum_wit += $r['amount'];
        ?>
        <tr>
            <td><?php echo $r['processDate']; ?></td>
            <td><?php echo $r['explanation']; ?></td>
            <td><?php echo number_format($r['amount'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <h4>Summary</h4>
    <p>Total Deposits: <?php echo number_format($sum_dep, 2); ?></p>
    <p>Total Withdrawals: <?php echo number_format($sum_wit, 2); ?></p>
    <p><strong>Net Balance: <?php echo number_format($sum_dep - $sum_wit, 2); ?></strong></p>
    <hr>
    
    <h3>Monthly Aggregates</h3>
    <table border="1" width="100%" cellpadding="5">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>Year</th>
                <th>Month</th>
                <th>Total Deposits</th>
                <th>Total Withdrawals</th>
                <th>Net Balance</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($res_monthly) == 0): ?>
            <tr><td colspan="5" align="center">No transactions found.</td></tr>
        <?php else:
            while($row = mysqli_fetch_assoc($res_monthly)): ?>
            <tr>
                <td><?php echo $row['y']; ?></td>
                <td><?php echo $row['m']; ?></td>
                <td><?php echo number_format($row['total_dep'], 2); ?></td>
                <td><?php echo number_format($row['total_wit'], 2); ?></td>
                <td style="color: <?php echo ($row['net_amount'] < 0 ? 'red' : 'green'); ?>;">
                    <?php echo number_format($row['net_amount'], 2); ?>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #ddd; font-weight: bold;">
                <td colspan="2" align="right">GRAND TOTALS:</td>
                <td><?php echo number_format($sum_dep, 2); ?></td>
                <td><?php echo number_format($sum_wit, 2); ?></td>
                <td><?php echo number_format($sum_dep - $sum_wit, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>