<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fiyat = $_POST['fiyat'];

    $stmt = $db->prepare("INSERT INTO Tickets (total_price,trip_id) VALUES (:fiyat)");
    $stmt->bindParam(':fiyat', $fiyat);
    $stmt->execute();

    echo "Bilet başarıyla eklendi!";
}
?>

<form method="POST">
    <label>Bilet Fiyatı:</label>
    <input type="text" name="fiyat" required>
    <button type="submit">Ekle</button>
</form>
