<?php
require_once 'config/db.php';
function checkLicense($license_key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ? AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE)");
    $stmt->execute([$license_key]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($license) {
        return [
            'cards' => $license['total_cards'],
            'used' => $license['used_cards']
        ];
    }
    return false;
}
function updateLicenseUsage($license_key, $cards_count) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE licenses SET used_cards = used_cards + ? WHERE license_key = ?");
        $stmt->execute([$cards_count, $license_key]);       
        $stmt = $pdo->prepare("INSERT INTO usage_log (license_id, cards_used) SELECT id, ? FROM licenses WHERE license_key = ?");
        $stmt->execute([$cards_count, $license_key]);
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
?>