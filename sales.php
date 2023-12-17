<?php
// Start the session
session_start();

// Check if the user is logged in, otherwise redirect to index.html
if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

// Logout logic
if (isset($_GET['logout'])) {
    // Unset all of the session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header('Location: index.html');
    exit();
}

// Retrieve the username from the session
$username = $_SESSION['username'];

// Include your database connection
include "./php/connection.php";

// Fetch sales data based on the date range
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['displaySale'])) {
    $fromDate = $_POST['fromDate'];
    $toDate = $_POST['toDate'];

    // Fetch sales data from the orders table for the specified date range
    $salesQuery = "SELECT orders.orderID, products.productID, products.brandName, products.genericName, products.price, orders.quantity, orders.ordered_at 
                   FROM orders
                   JOIN products ON orders.productID = products.productID
                   WHERE orders.ordered_at BETWEEN '$fromDate' AND '$toDate'
                   ORDER BY orders.ordered_at";

    $salesResult = $conn->query($salesQuery);

    // Check for errors
    if (!$salesResult) {
        die("Query failed: " . $conn->error);
    }

    // Check if there are rows in the result
    if ($salesResult->num_rows > 0) {
        $salesRows = $salesResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $salesRows = [];
    }

    // Calculate total sales
    $totalSales = 0;
    foreach ($salesRows as $salesRow) {
        $totalSales += $salesRow['price'] * $salesRow['quantity'];
    }
} else {
    // Fetch all sales data
    $salesQuery = "SELECT orders.orderID, products.productID, products.brandName, products.genericName, products.price, orders.quantity, orders.ordered_at 
                   FROM orders
                   JOIN products ON orders.productID = products.productID
                   ORDER BY orders.ordered_at";

    $salesResult = $conn->query($salesQuery);

    // Check for errors
    if (!$salesResult) {
        die("Query failed: " . $conn->error);
    }

    // Check if there are rows in the result
    if ($salesResult->num_rows > 0) {
        $salesRows = $salesResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $salesRows = [];
    }

    // Calculate total sales
    $totalSales = 0;
    foreach ($salesRows as $salesRow) {
        $totalSales += $salesRow['price'] * $salesRow['quantity'];
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="./css/sales.css">
</head>
<body>
<header>
    <nav>
        <div class="logo">Botika</div>
        <div class="burger-menu" onclick="toggleMenu()">☰</div>
        <div class="navigation">
            <ul>
                <li><a href="inventory.php"><i class="fa-solid fa-box"></i>Inventory</a></li>
                <li>
                    <a href="add.php" class="dropbtn"><i class="fa-solid fa-pen-to-square"></i>Add Product</a>
                </li>
                <li><a href="sales.php"><i class="fa-solid fa-dollar-sign"></i>Sales</a></li>
                <li><a href="notif.php"><i class="fa-solid fa-envelope"></i>Notification</a></li>
                <li><a href="?logout=1"><i class="fa-solid fa-right-from-bracket" style="color: #FF0000"></i>Logout <?php echo $username; ?></a></li>
            </ul>
        </div>
    </nav>
</header>

<!-- Display date range filter and sales details -->
<section class="sales-section">
    <div class="sales-header">
        <h1>Sales Report</h1>
        <form action="sales.php" method="post">
            <label for="fromDate">From:</label>
            <input type="date" id="fromDate" name="fromDate" required>
            <label for="toDate">To:</label>
            <input type="date" id="toDate" name="toDate" required>
            <button type="submit" name="displaySale">Display Sale</button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Brand Name</th>
                <th>Generic Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Ordered At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salesRows as $salesRow): ?>
                <tr>
                    <td><?php echo $salesRow['orderID']; ?></td>
                    <td><?php echo $salesRow['brandName']; ?></td>
                    <td><?php echo $salesRow['genericName']; ?></td>
                    <td><?php echo $salesRow['price']; ?></td>
                    <td><?php echo $salesRow['quantity']; ?></td>
                    <td><?php echo $salesRow['price'] * $salesRow['quantity']; ?></td>
                    <td><?php echo $salesRow['ordered_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Fixed bar for total sales -->
    <div class="total-bar">
        <p>Total Sales: <?php echo $totalSales; ?></p>
    </div>
</section>

<script>
    function toggleMenu() {
        const navigation = document.querySelector('.navigation');
        navigation.classList.toggle('show');
    }
</script>
</body>
</html>
