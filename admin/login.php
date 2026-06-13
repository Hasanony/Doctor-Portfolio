<?php
session_start();
// Assumes 'db.php' is one folder up
require_once '../db.php'; 

// Fetch the admin/user image from the database
// Fetch image AND name from the 'user' table
$user_stmt = $pdo->query("SELECT image, name FROM user LIMIT 1");
$user = $user_stmt->fetch();

$profile_img = ($user && !empty($user['image'])) ? $user['image'] : 'img/default-profile.jpg';
$user_name = ($user && !empty($user['name'])) ? $user['name'] : 'Administrator';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && $password === $admin['password']) {
        $_SESSION['loggedin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
    <meta name="description" content="Secure login for Dr. MD. Golam Mortuza, the best skin doctor in Jessore. Specialist Dermatologist & Dermato Surgeon providing advanced skin care solutions.">
    <meta name="keywords" content="Dr. MD. Golam Mortuza, best skin doctor in Jessore, Dermatologist in Jessore, Dermato Surgeon, Skin specialist Jessore">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Login | Dr. MD. Golam Mortuza - Best Skin Doctor in Jessore</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, sans-serif; }
        body { display: flex; min-height: 100vh; background: #f5f5f7; }

        /* Left Side: Dynamic Image */
        .left-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .left-side img {
            max-width: 80%;
            opacity: 0.5; /* Transparency preserved */
            filter: drop-shadow(0 20px 50px rgba(0,0,0,0.1));
        }

        /* Right Side: Glass Login Form */
        .right-side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 40px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            text-align: center;
        }

        .input-box input {
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.5);
        }

        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: #ff3b30;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .admin-name {
    font-size: 24px;
    font-weight: 800; /* Extra Bold */
    color: #1d1d1f;
    margin-bottom: 5px;
    letter-spacing: -0.5px;
}

.admin-title {
    font-size: 14px;
    font-weight: 600;
    color: #86868b;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

        button:hover { background: #d63026; }
        .error { color: #ff3b30; background: rgba(255, 59, 48, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="left-side">
        <img src="<?php echo htmlspecialchars($profile_img); ?>" alt="Admin Profile">
    </div>

    <div class="right-side">
        <div class="login-box">
            <h2 class="admin-name"><?php echo htmlspecialchars($user_name); ?></h2>
            <form method="POST">
                <div class="input-box"><input type="email" name="email" placeholder="Email" required></div>
                <div class="input-box"><input type="password" name="password" placeholder="Password" required></div>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>
    </div>

</body>
</html>