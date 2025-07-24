<?php
// 施設一覧をJSONで返すAPI
require_once 'auth_check.php';

header('Content-Type: application/json; charset=UTF-8');
$config = getConfig();
$db = getDatabase();
// セキュリティ: 公開する必要のないフィールドを除外
$res = $db->query('SELECT id, csv_no, name, name_kana, lat, lng, address, address_detail, installation_position, phone, phone_extension, corporate_number, organization_name, available_days, start_time, end_time, available_hours_note, pediatric_support, website, note, category FROM facilities');
$facilities = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    // 各施設の画像を取得（original_nameは除外）
    $imageStmt = $db->prepare('SELECT filename FROM facility_images WHERE facility_id = :facility_id ORDER BY id');
    $imageStmt->bindValue(':facility_id', $row['id'], SQLITE3_INTEGER);
    $imageRes = $imageStmt->execute();
    
    $images = [];
    while ($imageRow = $imageRes->fetchArray(SQLITE3_ASSOC)) {
        $images[] = [
            'filename' => $imageRow['filename'],
            'url' => $config['storage']['images_dir'] . '/' . $imageRow['filename']
        ];
    }
    
    $row['images'] = $images;
    $facilities[] = $row;
}
echo json_encode($facilities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
