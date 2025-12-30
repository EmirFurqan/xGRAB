<?php
session_start();
require("connect.php");

if ($_SESSION['user_type'] != 'manager') die("Access Denied");
$branchID = $_SESSION['global_id'];
$days = 90; // Default

if (isset($_POST['filter'])) {
    $days = (int)$_POST['days'];
}

$sql = "SELECT
            a.accountName,
            a.accountIBAN,
            GREATEST(
                COALESCE((SELECT MAX(processDate) FROM tbldeposit WHERE accountID = a.accountID), '1900-01-01'),
                COALESCE((SELECT MAX(processDate) FROM tblwithdrawal WHERE accountID = a.accountID), '1900-01-01')
            ) as last_activity
        FROM tblaccount a
        WHERE a.branchID = $branchID
        HAVING DATEDIFF(NOW(), last_activity) > $days";
        
$result = myQuery($sql);
?>
<!DOCTYPE html>
<html>
<body>
    <a href="menu.php">Back to Menu</a>
    <h2>Inactive Accounts Report</h2>
    <form method="post">
        Show accounts inactive for more than
        <input type="number" name="days" value="<?php echo $days; ?>"> days.
        <input type="submit" name="filter" value="Filter">
    </form>
    <br>
    <table border="1">
        <tr>
            <th>Account Name</th>
            <th>IBAN</th>
            <th>Last Activity Date</th>
            <th>Days Inactive</th>
        </tr>
        <?php
        $currentDate = new DateTime();
        while($row = mysqli_fetch_assoc($result)):
            $lastDate = new DateTime($row['last_activity']);
            $diff = $currentDate->diff($lastDate)->days;
            
            // If date is 1900-01-01 it means no transaction ever
            $displayDate = ($row['last_activity'] == '1900-01-01') ? 'Never' : $row['last_activity'];
        ?>
        <tr>
            <td><?php echo $row['accountName']; ?></td>
            <td><?php echo $row['accountIBAN']; ?></td>
            <td><?php echo $displayDate; ?></td>
            <td><?php echo $diff; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>