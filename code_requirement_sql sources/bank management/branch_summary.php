<?php
session_start();
require("connect.php");

// Access: Manager Only
if ($_SESSION['user_type'] != 'manager') die("Access Denied");
$branchID = $_SESSION['global_id'];

// We join Account with Deposits and Withdrawals using subqueries
$sql = "SELECT
            a.accountName,
            a.accountIBAN,
            (SELECT COALESCE(SUM(amount), 0) FROM tbldeposit WHERE accountID = a.accountID) as total_dep,
            (SELECT COALESCE(SUM(amount), 0) FROM tblwithdrawal WHERE accountID = a.accountID) as total_wit
        FROM tblaccount a
        WHERE a.branchID = $branchID";
        
$result = myQuery($sql);
$grand_dep = 0;
$grand_wit = 0;
?>
<!DOCTYPE html>
<html>
<body>
    <a href="menu.php">Back to Menu</a>
    <h2>Branch Summary: <?php echo $_SESSION['name']; ?></h2>
    <table border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Account Name</th>
                <th>IBAN</th>
                <th>Total Deposits</th>
                <th>Total Withdrawals</th>
                <th>Net Balance</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = mysqli_fetch_assoc($result)):
            $net = $row['total_dep'] - $row['total_wit'];
            $grand_dep += $row['total_dep'];
            $grand_wit += $row['total_wit'];
        ?>
            <tr>
                <td><?php echo $row['accountName']; ?></td>
                <td><?php echo $row['accountIBAN']; ?></td>
                <td><?php echo $row['total_dep']; ?></td>
                <td><?php echo $row['total_wit']; ?></td>
                <td><?php echo $net; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #eee; font-weight:bold;">
                <td colspan="2">BRANCH TOTALS</td>
                <td><?php echo $grand_dep; ?></td>
                <td><?php echo $grand_wit; ?></td>
                <td><?php echo $grand_dep - $grand_wit; ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>