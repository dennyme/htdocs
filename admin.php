<?php
require_once 'config.php';
session_start();

// Function to safely get POST data
function getPostData($key) {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : '';
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function resetSession() {
    session_unset();
    session_destroy();
    session_start();
    generateCSRFToken();
}

if (isset($_POST['reset_session'])) {
    resetSession();
    header("Location: admin.php");
    exit();
}

function verifyCSRFToken() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!isset($_POST['csrf_token'])) {
            error_log("CSRF token not present in POST data");
            die('CSRF token not found in form submission. Please refresh the page and try again.');
        }
        if (!isset($_SESSION['csrf_token'])) {
            error_log("CSRF token not present in session");
            die('CSRF token not found in session. Please refresh the page and try again.');
        }
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("CSRF token mismatch. POST: " . $_POST['csrf_token'] . ", Session: " . $_SESSION['csrf_token']);
            die('CSRF token mismatch. Please refresh the page and try again.');
        }
    }
}

// Database connection
function getDbConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }
    return $conn;
}

// Login functionality
function loginUser($username, $password) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            return true;
        }
    }
    return false;
}

// Get current prices
function getCurrentPrices() {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM prices ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $prices = $result->fetch_assoc();
    $prices['currency'] = $prices['currency'] ?? 'USD'; // Default to USD if not set
    return $prices;
}

// Update prices functionality
// Modify the updatePrices function to remove currency parameter and adjust query
function updatePrices($ramPrice, $cpuPrice) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO prices (ram_price, cpu_price) VALUES (?, ?)");
    $stmt->bind_param("dd", $ramPrice, $cpuPrice);
    return $stmt->execute();
}

// New function to get current exchange rates (Example static value)
function getExchangeRates() {
    return ['THB' => 35.5]; // 1 USD = 35.5 THB (example rate)
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verifyCSRFToken();

    if (isset($_POST['login'])) {
        $username = getPostData('username');
        $password = getPostData('password');
        if (loginUser($username, $password)) {
            header("Location: admin.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } elseif (isset($_POST['update_prices']) && isset($_SESSION['loggedin'])) {
        $ramPrice = floatval(getPostData('ram_price'));
        $cpuPrice = floatval(getPostData('cpu_price'));
        $currency = getPostData('currency');
        if (updatePrices($ramPrice, $cpuPrice, $currency)) {
            $success = "Prices updated successfully";
        } else {
            $error = "Failed to update prices";
        }
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header("Location: admin.php");
        exit();
    }
}

// Get current prices and exchange rates
$currentPrices = getCurrentPrices();
$exchangeRates = getExchangeRates();

// Convert prices to THB
$currentPrices['ram_price_thb'] = $currentPrices['ram_price'] * $exchangeRates['THB'];
$currentPrices['cpu_price_thb'] = $currentPrices['cpu_price'] * $exchangeRates['THB'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 600px; margin: auto; }
        h1 { text-align: center; }
        form { background: #f4f4f4; padding: 20px; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin-bottom: 10px; }
        input[type="submit"] { background: #333; color: #fff; padding: 10px; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
        .slider { -webkit-appearance: none; width: 100%; height: 15px; border-radius: 5px; background: #d3d3d3; outline: none; opacity: 0.7; transition: opacity .2s; }
        .slider:hover { opacity: 1; }
        .slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 25px; height: 25px; border-radius: 50%; background: #4CAF50; cursor: pointer; }
        .slider::-moz-range-thumb { width: 25px; height: 25px; border-radius: 50%; background: #4CAF50; cursor: pointer; }
        select { width: 100%; padding: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Admin Panel</h1>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <?php if (!isset($_SESSION['loggedin'])): ?>
        <form method="post" action="">
            <h2>Login</h2>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="submit" name="login" value="Login">
        </form>
    <?php else: ?>
        <form method="post" action="">
            <h2>Update Prices</h2>
            <label for="currency">Currency:</label>
            <select id="currency" name="currency">
                <option value="USD" <?php echo $currentPrices['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                <option value="THB" <?php echo $currentPrices['currency'] === 'THB' ? 'selected' : ''; ?>>THB</option>
            </select>

            <label for="ram_price">RAM Price per GB: <span id="ramPriceValue"><?php echo $currentPrices['ram_price']; ?></span></label>
            <input type="range" id="ram_price" name="ram_price" class="slider" min="0" max="20" step="0.1" value="<?php echo $currentPrices['ram_price']; ?>">

            <label for="cpu_price">CPU Price per Core: <span id="cpuPriceValue"><?php echo $currentPrices['cpu_price']; ?></span></label>
            <input type="range" id="cpu_price" name="cpu_price" class="slider" min="0" max="40" step="0.1" value="<?php echo $currentPrices['cpu_price']; ?>">

            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="submit" name="update_prices" value="Update Prices">
        </form>
    <?php endif; ?>
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="submit" name="reset_session" value="Reset Session">
    </form>
</div>

<script>
    const exchangeRates = <?php echo json_encode($exchangeRates); ?>;
    let currentCurrency = '<?php echo $currentPrices['currency']; ?>';

    const prices = {
        USD: {
            ram: <?php echo $currentPrices['ram_price']; ?>,
            cpu: <?php echo $currentPrices['cpu_price']; ?>
        },
        THB: {
            ram: <?php echo $currentPrices['ram_price_thb']; ?>,
            cpu: <?php echo $currentPrices['cpu_price_thb']; ?>
        }
    };

    function updatePriceDisplay(elementId, priceType) {
        const element = document.getElementById(elementId);
        const slider = document.getElementById(elementId.replace('Value', ''));
        const currency = document.getElementById('currency').value;

        let displayValue = prices[currency][priceType];
        element.innerHTML = displayValue.toFixed(2);

        // Update slider value and max based on currency
        if (currency === 'USD') {
            slider.value = prices.USD[priceType];
            slider.max = priceType === 'ram' ? 20 : 40;
        } else {
            slider.value = prices.THB[priceType] / exchangeRates.THB;
            slider.max = (priceType === 'ram' ? 20 : 40) * exchangeRates.THB;
        }
    }

    function updateAllPrices() {
        updatePriceDisplay('ramPriceValue', 'ram');
        updatePriceDisplay('cpuPriceValue', 'cpu');
    }

    function updatePrice(priceType, value) {
        const currency = document.getElementById('currency').value;
        if (currency === 'USD') {
            prices.USD[priceType] = parseFloat(value);
            prices.THB[priceType] = prices.USD[priceType] * exchangeRates.THB;
        } else {
            prices.THB[priceType] = parseFloat(value) * exchangeRates.THB;
            prices.USD[priceType] = prices.THB[priceType] / exchangeRates.THB;
        }
        updatePriceDisplay(priceType + 'PriceValue', priceType);
    }

    document.getElementById('ram_price').oninput = function() {
        updatePrice('ram', this.value);
    }

    document.getElementById('cpu_price').oninput = function() {
        updatePrice('cpu', this.value);
    }

    document.getElementById('currency').onchange = function() {
        currentCurrency = this.value;
        updateAllPrices();
    }

    // Initialize displays
    updateAllPrices();
</script>
</body>
</html>