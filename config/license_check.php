<?php
function checkLicense($user_id, $cards_count) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT l.* FROM licenses l
        WHERE l.user_id = ? 
        AND l.used_cards < l.total_cards 
        AND l.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $license = $stmt->fetch();

    if (!$license) {
        return ['status' => false, 'message' => 'لا يوجد ترخيص نشط'];
    }

    if (($license['used_cards'] + $cards_count) > $license['total_cards']) {
        return ['status' => false, 'message' => 'عدد الكروت المتبقي غير كافٍ'];
    }

    return ['status' => true, 'license_id' => $license['id']];
}

function updateLicenseUsage($license_id, $cards_used) {
    global $pdo;
    
    $pdo->beginTransaction();
    try {
        // تحديث عدد الكروت المستخدمة
        $stmt = $pdo->prepare("
            UPDATE licenses 
            SET used_cards = used_cards + ? 
            WHERE id = ?
        ");
        $stmt->execute([$cards_used, $license_id]);

        // تسجيل العملية
        $stmt = $pdo->prepare("
            INSERT INTO usage_log (license_id, cards_used) 
            VALUES (?, ?)
        ");
        $stmt->execute([$license_id, $cards_used]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}