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

// Retrieve the username from the session
$username = $_SESSION['username'];

// Include your database connection
include "./php/connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removeProductID'])) {
    $productID = $_POST['removeProductID']; // Fix: use the correct variable name
    $removeQuery = "DELETE FROM checkout WHERE username = '$username' AND productID = '$productID'";
    
    if ($conn->query($removeQuery)) {
        // Removal successful
        $removeResponse = ['success' => true];
    } else {
        // Removal failed
        $removeResponse = ['success' => false];
    }
}
// Fetch products from the checkoutpage view for the logged-in user
$checkoutQuery = "SELECT * FROM checkoutpage WHERE username = '$username'";
$checkoutResult = $conn->query($checkoutQuery);

// Check if there are rows in the result
if ($checkoutResult->num_rows > 0) {
    $checkoutRows = $checkoutResult->fetch_all(MYSQLI_ASSOC);
} else {
    $checkoutRows = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Get the productID and quantity from the form
    $productIDs = $_POST['productID'];
    $quantities = $_POST['quantity'];

    // Loop through each product and perform the checkout
    foreach (array_combine($productIDs, $quantities) as $productID => $quantity) {
        // Call the stored procedure with the three parameters
        $procedureCall = "CALL checkout('$username', '$productID', '$quantity')";

        if ($conn->query($procedureCall)) {
            // Checkout successful
            $checkoutResponse = ['success' => true];
        } else {
            // Checkout failed
            $checkoutResponse = ['success' => false];
        }
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
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="./css/checkout.css">
</head>
<body>

<header>
        <nav>
            <div class="logo">Botika</div>
            <div class="burger-menu" onclick="toggleMenu()">☰</div>
            <div class="navigation">
                <ul>
                    <li><a href="pharmacist.php"><i class="fa-solid fa-shop"></i>Products</a></li>
                    <li><a href="#"><i class="fa-solid fa-basket-shopping" style="color: #0be407;"></i>Basket</a></li>
                    <li><a href="?logout=1"><i class="fa-solid fa-right-from-bracket" style="color: #FF0000"></i>Logout <?php echo $username; ?></a></li>
                </ul>
            </div>
        </nav>
    </header>



<!-- Display checkout details -->
<section class="checkout-section">
    <div class="checkout-header">
        <h1>Your Checkout</h1>
        <form id="checkoutForm" method="post">
            <?php foreach ($checkoutRows as $checkoutRow): ?>
                <input type="hidden" name="productID[]" value="<?php echo $checkoutRow['productID']; ?>" />
                <input type="hidden" name="quantity[]" value="<?php echo $checkoutRow['quantity']; ?>" />
            <?php endforeach; ?>
            <label for="cash">Enter Cash: </label>
            <input type="text" name="cash" id="cashInput" oninput="calculateChange()" required /> <br>
            <br>
            <br>
            <button type="submit" class="checkout-button" name="checkout">Checkout</button>
            <button type="button" onclick="printReceipt()" class="print-receipt-button">Print Receipt</button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Product Image</th>
                <th>Brand Name</th>
                <th>Generic Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Actions</th> <!-- New column for Actions -->
            </tr>
        </thead>
        <tbody>
            <?php 
                $totalSum = 0; // Initialize total sum variable
                foreach ($checkoutRows as $checkoutRow): 
                    $total = $checkoutRow['price'] * $checkoutRow['quantity'];
                    $totalSum += $total; // Add to total sum
            ?>
                <tr>
                    <td><img src="./productuploads/<?php echo $checkoutRow['productImg']; ?>" alt="Product Image"></td>
                    <td><?php echo $checkoutRow['brandName']; ?></td>
                    <td><?php echo $checkoutRow['genericName']; ?></td>
                    <td><?php echo $checkoutRow['price']; ?></td>
                    <td><?php echo $checkoutRow['quantity']; ?></td>
                    <td><?php echo $total; ?></td>
                    <td>
    <form method="post" onsubmit="return confirm('Are you sure you want to remove this item?');">
        <input type="hidden" name="removeProductID" value="<?php echo $checkoutRow['productID']; ?>">
        <button type="submit">Remove</button>
    </form>
</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"><strong>Total Sum</strong></td>
                <td><strong><?php echo $totalSum; ?></strong></td> <!-- Display Total Sum -->
            </tr>
            <tr>
                <td colspan="5"><strong>Cash</strong></td>
                <td><strong id="cashDisplay">0</strong></td> <!-- Display Cash -->
            </tr>
            <tr>
                <td colspan="5"><strong>Change</strong></td>
                <td><strong id="changeDisplay">0</strong></td> <!-- Display Change -->
            </tr>
        </tfoot>
    </table>
</section>

<script>
    function toggleMenu() {
        const navigation = document.querySelector('.navigation');
        navigation.classList.toggle('show');
    }

      // Add this script block
      <?php if (isset($checkoutResponse) && $checkoutResponse['success']): ?>
        alert('Transaction Successful');
        window.location.href = 'pharmacist.php';
    <?php endif; ?>

    function calculateChange() {
    const cashInput = document.getElementById('cashInput');
    const cashDisplay = document.getElementById('cashDisplay');
    const changeDisplay = document.getElementById('changeDisplay');
    const checkoutButton = document.querySelector('.checkout-button');
    const printReceiptButton = document.querySelector('.print-receipt-button');

    // Parse input values as floats
    const cash = parseFloat(cashInput.value) || 0;
    const totalSum = <?php echo $totalSum; ?>; // Retrieve totalSum from PHP

    // Display the cash
    cashDisplay.textContent = cash.toFixed(2);

    // Calculate and display the change
    const change = cash - totalSum;
    changeDisplay.textContent = change.toFixed(2);

    // Disable buttons if conditions are met
    checkoutButton.disabled = cash === 0 || change < 0 || cash === "";
    printReceiptButton.disabled = cash === 0 || change < 0 || cash === "";
}

    function printReceipt() {
    const cashInput = document.getElementById('cashInput');
    const totalSum = <?php echo $totalSum; ?>;
    const cash = parseFloat(cashInput.value) || 0;
    const change = cash - totalSum;

    const receiptDetails = {
        products: <?php echo json_encode($checkoutRows); ?>,
        totalSum: totalSum.toFixed(2),
        cash: cash.toFixed(2),
        change: change.toFixed(2)
    };

    // Build the receipt HTML
    let receiptHTML = '<div style="max-width: 300px; margin: auto; font-family: Arial, sans-serif;">';
    receiptHTML += '<h1 style="text-align: center; font-size: 18px;">Your Receipt</h1>';
    receiptHTML += '<hr>';

    // Display product details in the receipt
    receiptDetails.products.forEach(product => {
        const total = product.price * product.quantity;
        receiptHTML += `<p style="font-size: 12px; margin: 5px 0;">${product.brandName} - ${product.genericName} - PHP${product.price} x ${product.quantity} = $${total.toFixed(2)}</p>`;
    });

    receiptHTML += '<hr>';
    receiptHTML += `<p style="font-size: 14px; margin: 5px 0;">Total Sum: PHP${receiptDetails.totalSum}</p>`;
    receiptHTML += `<p style="font-size: 14px; margin: 5px 0;">Cash: PHP${receiptDetails.cash}</p>`;
    receiptHTML += `<p style="font-size: 14px; margin: 5px 0;">Change: PHP${receiptDetails.change}</p>`;
    receiptHTML += '</div>';

    // Open a new window with the receipt details and print it
    const receiptWindow = window.open('', '_blank');
    receiptWindow.document.write('<html><head><title>Receipt</title></head><body>');
    receiptWindow.document.write(receiptHTML);
    receiptWindow.document.write('</body></html>');

    // Close the document for printing
    receiptWindow.document.close();

    // Print the receipt
    receiptWindow.print();
}



</script>
    
</script>
</body>
</html>