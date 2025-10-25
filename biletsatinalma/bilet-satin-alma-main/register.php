<?php
session_start();
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    // Şifreler aynı mı kontrol et
    if ($password !== $confirm) {
        $message = "<p class='error'>Şifreler eşleşmiyor!</p>";
    } else {
        // Email zaten kayıtlı mı kontrol et
        $stmt = $db->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $message = "<p class='error'>Bu email zaten kayıtlı!</p>";
        } else {
            // Şifreyi hashle
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Yeni kullanıcı ekle (rol: user, balance: 800)
            $stmt = $db->prepare("INSERT INTO User (full_name, email, password, role, balance, created_at) 
                                  VALUES (?, ?, ?, 'user', 800, datetime('now'))");
            $stmt->execute([$full_name, $email, $hashed]);

            $message = "<p class='success'>Kayıt başarılı! <a href='login.php'>Giriş yap</a></p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kayıt Ol</title>
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
      width: 320px;
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
    .success {
      color: green;
      font-weight: bold;
      text-align: center;
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
    <h2>Kayıt Ol</h2>
    <?= $message ?>
    <form action="" method="post">
      <label>Ad Soyad:</label>
      <input type="text" name="full_name" required>

      <label>Email:</label>
      <input type="email" name="email" required>

      <label>Şifre:</label>
      <input type="password" name="password" required>

      <label>Şifre Tekrar:</label>
      <input type="password" name="confirm_password" required>

      <button type="submit">Kayıt Ol</button>
    </form>
    <p style="text-align:center; margin-top:10px;">
      Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
    </p>
  </div>
</body>
</html>