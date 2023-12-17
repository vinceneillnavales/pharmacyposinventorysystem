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

    // Redirect to the login page
    header('Location: index.html');
    exit();
}

// Include your database connection
include "./php/connection.php";

// Retrieve the username from the session
$username = $_SESSION['username'];

// Check if the addToBasket parameter is set
if (isset($_GET['addToBasket'])) {
    $productID = $_GET['addToBasket'];

    // Insert into the basket table
    $insertQuery = "CALL addbasket('$productID', '$username')";
    $conn->query($insertQuery);

    // Redirect to prevent duplicate submissions
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Check if a search query is submitted
if (isset($_POST['search'])) {
    $searchKeyword = $_POST['searchKeyword'];

    // Fetch data from the products table based on the search keyword
    $query = "SELECT * FROM products WHERE genericName LIKE '%$searchKeyword%' OR brandName LIKE '%$searchKeyword%'";
    $result = $conn->query($query);

    // Check if there are rows in the result
    if ($result->num_rows > 0) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $rows = [];
    }
} else {
    // Fetch all data from the products table
    $query = "SELECT * FROM products";
    $result = $conn->query($query);

    // Check if there are rows in the result
    if ($result->num_rows > 0) {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $rows = [];
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
    <title>Pharmacist</title>
    <link rel="stylesheet" href="./css/pharmacy.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Botika</div>
            <div class="burger-menu" onclick="toggleMenu()">☰</div>
            <div class="navigation">
                <ul>
                    <li><a href="pharmacist.php"><i class="fa-solid fa-shop"></i>Products</a></li>
                    <li><a href="basket.php"><i class="fa-solid fa-basket-shopping" style="color: #0be407;"></i>Basket</a></li>
                    <li><a href="?logout=1"><i class="fa-solid fa-right-from-bracket" style="color: #FF0000"></i>Logout <?php echo $username; ?></a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <section class="product-section">
        <h1>Products</h1>

        <!-- Search Form -->
        <form method="post">
            <label for="searchKeyword">Search:</label>
            <input type="text" id="searchKeyword" name="searchKeyword">
            <button type="submit" name="search">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Image</th>
                    <th>Brand Name</th>
                    <th>Generic Name</th>
                    <th>Stocks</th>
                    <th>Price</th>
                    <th>Manufacture Date</th>
                    <th>Expiry Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo $row['productID']; ?></td>
                        <td><img src="productuploads/<?php echo $row['productImg']; ?>" alt="Product Image" style="width: 50px; height: 50px;"></td>
                        <td><?php echo $row['brandName']; ?></td>
                        <td><?php echo $row['genericName']; ?></td>
                        <td><?php echo $row['stocks']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td><?php echo $row['manufactureDate']; ?></td>
                        <td><?php echo $row['expiryDate']; ?></td>
                        <td><button onclick="addToBasket('<?php echo $row['productID']; ?>')">Add to Basket</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <script>
        function toggleMenu() {
            const navigation = document.querySelector('.navigation');
            navigation.classList.toggle('show');
        }

        function addToBasket(productID) {
            // Redirect to the same page with the addToBasket parameter
            window.location.href = '?addToBasket=' + productID;
        }
    </script>
</body>
</html>
