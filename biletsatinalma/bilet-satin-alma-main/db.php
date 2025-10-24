<?php
try {
//Artık $db üzerinden sorgular yapabilirsin. UNUTMAA
    $db = new PDO('sqlite:bilet_sistemi.db');

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Bağlantı başarılı!";
} catch (PDOException $a) {

    echo "Bağlantı hatası: " . $a->getMessage();
}
?>
