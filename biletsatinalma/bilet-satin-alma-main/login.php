<?php
session_start();
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Kullanıcıyı email ile bul
    $stmt = $db->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Session güvenliği için ID yenile
        session_regenerate_id(true);

        // Session bilgilerini ata
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['balance']   = $user['balance'];

        // Rolüne göre yönlendirme
        if ($user['role'] === 'admin') {
            header("Location: admin_panel.php");
        } elseif ($user['role'] === 'company') {
            header("Location: company_panel.php");
            $_SESSION['company_id'] = $user['company_id'];
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $message = "<p class='error'>Email veya şifre hatalı!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Giriş Yap</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #a8dadc;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background: #f1fdfb;
      padding: 25px;
      border-radius: 12px;
      width: 300px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    h2 {
      text-align: center;
      margin-bottom: 15px;
      color: #2a4d4a;
    }
    label {
      display: block;
      margin: 8px 0 4px;
      font-weight: bold;
      font-size: 14px;
      color: #2a4d4a;
    }
    input {
      width: 100%;
      padding: 8px;
      margin-bottom: 12px;
      border: 1px solid #bcd9d7;
      border-radius: 6px;
      background-color: #ffffffcc;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #2a9d8f;
      border: none;
      border-radius: 6px;
      font-size: 15px;
      font-weight: bold;
      color: #fff;
      cursor: pointer;
    }
    button:hover {
      background: #1d6f65;
    }
    .error {
      color: red;
      font-weight: bold;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Giriş Yap</h2>
    <?= $message ?>
    <form action="" method="post">
      <label>Email:</label>
      <input type="email" name="email" required>

      <label>Şifre:</label>
      <input type="password" name="password" required>

      <button type="submit">Giriş Yap</button>
    </form>
    <p style="text-align:center; margin-top:10px;">
      Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
    </p>
  </div>
</body>
</html>