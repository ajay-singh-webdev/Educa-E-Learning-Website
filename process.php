<?php
// Start the session if needed
session_start();

// Database connection setup
$db_name = 'mysql:host=localhost:3307;dbname=users';
$user_name = 'root';
$user_password = '';

try {
    $conn = new PDO($db_name, $user_name, $user_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Generate a unique ID for new users
function unique_id() {
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $rand = array();
    $length = strlen($str) - 1;
    for ($i = 0; $i < 20; $i++) {
        $n = mt_rand(0, $length);
        $rand[] = $str[$n];
    }
    return implode($rand);
}

// Check if form is submitted correctly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Handle Login
    if ($action === 'login') {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Prepared statement to avoid SQL injection
            $query = $conn->prepare("SELECT * FROM users WHERE email = :username");
            $query->bindParam(':username', $username);
            $query->execute();

            // Check if user exists
            if ($query->rowCount() > 0) {
                $user = $query->fetch(PDO::FETCH_ASSOC);
                // Verify password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $username;
                    header("Location: index.html"); // Redirect to dashboard
                    exit;
                } else {
                    echo "<script>alert('Invalid username or password.');</script>";
                }
            } else {
                echo "<script>alert('Invalid username or password.');</script>";
            }
        } else {
            echo "<script>alert('Please enter both username and password.');</script>";
        }
    }
    // Handle Registration
    elseif ($action === 'register') {
        if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Check if passwords match
            if ($password !== $confirm_password) {
                echo "<script>alert('Passwords do not match.');</script>";
            } else {
                // Check if the email already exists
                $query = $conn->prepare("SELECT * FROM users WHERE email = :email");
                $query->bindParam(':email', $email);
                $query->execute();
                if ($query->rowCount() > 0) {
                    echo "<script>alert('Email is already registered.');</script>";
                } else {
                    // Hash the password before storing
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $unique_id = unique_id(); // Generate unique ID

                    // Insert new user into the database
                    $query = $conn->prepare("INSERT INTO users (id, name, email, password) VALUES (:id, :name, :email, :password)");
                    $query->bindParam(':id', $unique_id);
                    $query->bindParam(':name', $name);
                    $query->bindParam(':email', $email);
                    $query->bindParam(':password', $hashed_password);

                    if ($query->execute()) {
                        echo "<script>alert('Registration successful!');</script>";
                        header("Location: login.html"); // Redirect to login page after registration
                        exit;
                    } else {
                        echo "<script>alert('Registration failed. Please try again.');</script>";
                    }
                }
            }
        } else {
            echo "<script>alert('Please fill all required fields.');</script>";
        }
    }
} else {
    echo "<script>alert('Invalid form submission.');</script>";
}
?>
