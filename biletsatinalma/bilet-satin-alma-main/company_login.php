<?php
session_start();
$db = new PDO("sqlite:bilet_sistemi.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM User WHERE email = ? AND role = 'company'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['role'] = 'company';
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        header("Location: company_panel.php");
        exit;
    } else {
        $message = "<p class='error'>E-posta veya şifre hatalı ❌</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Admin Girişi</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f1fdfb; text-align: center; padding: 60px; }
    .card { background: white; padding: 30px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 400px; }
    h2 { color: #2a4d4a; margin-bottom: 20px; }
    input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 6px; }
    button { padding: 10px 15px; background: #2a9d8f; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
    button:hover { background: #1d6f65; }
    .error { color: red; font-weight: bold; margin-top: 10px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Firma Admin Girişi</h2>
    <form method="post">
      <input type="email" name="email" placeholder="E-posta" required>
      <input type="password" name="password" placeholder="Şifre" required>
      <button type="submit">Giriş Yap</button>
    </form>
    <?= $message ?>
  </div>
</body>
</html>