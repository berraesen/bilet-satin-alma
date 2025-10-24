<?php
session_start();
session_unset();   // tüm session değişkenlerini temizle
session_destroy(); // oturumu sonlandır

header("Location: index.php"); // anasayfaya yönlendir
exit;