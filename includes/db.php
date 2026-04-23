<?php
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=xspectec_db;charset=utf8mb4",
        "xspectec_ropprov",
        "Kontteroul123@"
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
