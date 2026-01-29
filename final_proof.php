<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=pln_monitoring;port=3306", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Let's find the latest applicant identity
    $stmt = $pdo->query("SELECT id, nik, no_kk, npwp, nama_lengkap FROM applicant_identities ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "SQL_RESULT_START\n";
    echo json_encode($row, JSON_PRETTY_PRINT);
    echo "\nSQL_RESULT_END\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
