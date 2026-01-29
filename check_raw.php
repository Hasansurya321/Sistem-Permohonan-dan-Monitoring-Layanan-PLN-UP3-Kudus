<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=pln_monitoring;port=3306", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("DESCRIBE applicant_identities");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $names = array_column($cols, 'Field');
    file_put_contents('db_cols_raw.json', json_encode($names));
    echo "SUCCESS";
} catch (Exception $e) {
    file_put_contents('db_error.txt', $e->getMessage());
    echo "ERROR";
}
