<?php
// データベース初期化＆サンプルデータ投入

// 設定ファイル読み込み
require_once 'auth_check.php';

// 管理者認証チェック
checkAuth();

$config = getConfig();

// シンプルな初期化チェック関数
function getFacilityCount($config) {
    try {
        $db = getDatabase();
        
        // テーブルの存在確認
        $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='facilities'");
        if ($tableCheck && $tableCheck->fetchArray()) {
            // データ件数確認
            $result = $db->query("SELECT COUNT(*) as count FROM facilities");
            $row = $result->fetchArray();
            return $row['count'];
        }
        
        $db->close();
    } catch (Exception $e) {
        // DB接続エラーの場合は0を返す
    }
    
    return 0;
}

// 施設データの件数を取得
$facilityCount = getFacilityCount($config);
$hasData = ($facilityCount > 0);

// CSVアップロード対応（事前配置ファイルは不要）

// 処理実行部分（POST送信時）
if (isset($_POST['init_type'])) {
    // CSRF対策
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        echo "<div style='color: red; margin: 20px; padding: 20px; border: 2px solid red;'>";
        echo "<h3>❌ セキュリティエラー</h3>";
        echo "<p>CSRFトークンが無効です。再度お試しください。</p>";
        echo "</div>";
        exit;
    }
    
    // 選択されたタイプに応じて処理を実行
    $initType = $_POST['init_type'];
    $success = false;
    
    if ($initType === 'schema_only') {
        $success = updateDatabaseSchema($config);
    } elseif ($initType === 'full_reset') {
        $success = resetDatabaseWithSampleData($config);
    } elseif ($initType === 'csv_import') {
        $success = resetDatabaseWithCSVData($config);
    }
    
    // 処理結果に応じた完了メッセージ（この後に選択画面は表示されない）
    $currentTime = date('Y-m-d H:i:s');
    $newFacilityCount = getFacilityCount($config);
    
    if ($success) {
        echo "<div style='color: green; font-size: 1.2em; margin: 20px; padding: 20px; border: 2px solid green;'>";
        
        if ($initType === 'schema_only') {
            echo "<h3>✅ データベース構成更新完了</h3>";
            echo "<p>処理日時: " . htmlspecialchars($currentTime) . "</p>";
            echo "<p>処理内容: テーブル構造の更新（データ保持）</p>";
            echo "<p>施設データ: {$newFacilityCount} 件（保持）</p>";
            echo "<p>既存データを保持したまま、データベース構成を更新しました。</p>";
        } elseif ($initType === 'full_reset') {
            echo "<h3>✅ データベース初期化＆サンプルデータ投入完了</h3>";
            echo "<p>初期化日時: " . htmlspecialchars($currentTime) . "</p>";
            echo "<p>処理内容: 全データ削除 + サンプルデータ投入</p>";
            echo "<p>施設データ: {$newFacilityCount} 件（新規）</p>";
            echo "<p>データベースを完全にリセットし、サンプルデータで初期化しました。</p>";
        } elseif ($initType === 'csv_import') {
            echo "<h3>✅ データベース初期化＆CSVインポート完了</h3>";
            echo "<p>初期化日時: " . htmlspecialchars($currentTime) . "</p>";
            echo "<p>処理内容: 全データ削除 + CSVファイルからインポート</p>";
            echo "<p>施設データ: {$newFacilityCount} 件（新規）</p>";
            echo "<p>データベースを完全にリセットし、CSVファイルからデータをインポートしました。</p>";
            
            // CSVインポート結果の詳細表示
            if (isset($_SESSION['csv_import_results'])) {
                $results = $_SESSION['csv_import_results'];
                echo "<div style='margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
                echo "<p><strong>📊 インポート結果詳細:</strong></p>";
                echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
                foreach ($results as $category => $count) {
                    echo "<li>" . htmlspecialchars($category) . ": " . $count . " 件</li>";
                }
                echo "</ul>";
                echo "</div>";
                unset($_SESSION['csv_import_results']);
            }
        }
        
        echo "<p>管理者パスワード: <strong>" . htmlspecialchars($config['admin']['password']) . "</strong></p>";
        
        // テーブル構造の表示
        if (isset($_SESSION['table_structure'])) {
            echo "<div style='margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
            echo "<p><strong>📋 facilitiesテーブル構造:</strong></p>";
            echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
            foreach ($_SESSION['table_structure'] as $column) {
                echo "<li>" . htmlspecialchars($column) . "</li>";
            }
            echo "</ul>";
            echo "</div>";
            unset($_SESSION['table_structure']); // 表示後に削除
        }
        
        echo "<div style='margin-top: 15px; color: #d63384;'>";
        echo "<p><strong>⚠️ 重要な注意事項:</strong></p>";
        echo "<ul>";
        echo "<li>パスワードは config.php ファイルで管理されています</li>";
        echo "<li>管理画面からパスワード変更が可能です</li>";
        echo "<li>セキュリティのため、このファイルを本番環境から削除することを推奨します</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='margin-top: 15px;'>";
        echo "<a href='admin.php' style='background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>管理画面へ</a>";
        echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>地図へ</a>";
        echo "</div>";
        echo "</div>";
        
    } else {
        echo "<div style='color: red; font-size: 1.2em; margin: 20px; padding: 20px; border: 2px solid red;'>";
        echo "<h3>❌ 処理に失敗しました</h3>";
        echo "<p>データベースの初期化処理中にエラーが発生しました。</p>";
        echo "<p>ログを確認して問題を解決してください。</p>";
        echo "<div style='margin-top: 15px;'>";
        echo "<button onclick='history.back()' style='background: gray; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px;'>戻る</button>";
        echo "</div>";
        echo "</div>";
    }
    
    // 完了メッセージ表示後は処理終了（選択画面は表示しない）
    exit;
}

// 初期化タイプ選択画面
if ($hasData) {
    // データが存在する場合：2つのオプションを提供
    echo "<div style='font-size: 1.2em; margin: 20px; padding: 20px; border: 2px solid #ffc107; background: #fff9c4;'>";
    echo "<h3>⚠️ データベースに既存データがあります</h3>";
    echo "<p>現在 <strong>{$facilityCount} 件</strong> の施設データが登録されています。</p>";
    echo "<p>以下のどちらかを選択してください：</p>";
    echo "</div>";
    
    echo "<form method='POST' enctype='multipart/form-data' style='margin: 20px;'>";
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<label style='display: block; cursor: pointer;'>";
    echo "<input type='radio' name='init_type' value='schema_only' required style='margin-right: 10px;'>";
    echo "<strong>構成のみ更新（データ保持）</strong>";
    echo "</label>";
    echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
    echo "既存データを保持したまま、テーブル構造のみ更新<br>";
    echo "新機能対応やバージョンアップ時に使用";
    echo "</p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<label style='display: block; cursor: pointer;'>";
    echo "<input type='radio' name='init_type' value='full_reset' required style='margin-right: 10px;'>";
    echo "<strong>全削除して初期化（サンプルデータのみ）</strong>";
    echo "</label>";
    echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
    echo "全データを削除してサンプルデータで初期化<br>";
    echo "開発・テスト用や完全リセット時に使用";
    echo "</p>";
    echo "</div>";
    
    // CSVインポートオプション（アップロード方式）
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<label style='display: block; cursor: pointer;'>";
    echo "<input type='radio' name='init_type' value='csv_import' required style='margin-right: 10px;'>";
    echo "<strong>全削除してCSVからインポート</strong>";
    echo "</label>";
    echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
    echo "全データを削除してCSVファイルからAED設置場所データをインポート<br>";
    echo "本番データ投入時に使用";
    echo "</p>";
    echo "<div style='margin: 10px 0 0 25px; padding: 8px; background: #f8f9fa; border-radius: 3px; font-size: 0.8em;'>";
    echo "<p style='margin: 0; color: #495057;'><strong>📋 CSVファイル要件:</strong></p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• ファイル形式: CSV (UTF-8エンコーディング)</p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• 最大ファイルサイズ: " . number_format($config['csv_import']['max_file_size'] / 1024 / 1024) . "MB</p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• 列数: " . $config['csv_import']['validation']['expected_columns'] . "列 (ヘッダー行含む)</p>";
    echo "</div>";
    echo "<div style='margin: 10px 0 0 25px;' id='csv_upload_section' style='display: none;'>";
    echo "<label for='csv_file' style='display: block; margin: 5px 0; font-weight: bold;'>CSVファイルを選択:</label>";
    echo "<input type='file' id='csv_file' name='csv_file' accept='.csv' style='margin: 5px 0; padding: 5px; border: 1px solid #ccc; border-radius: 3px;'>";
    echo "<p style='margin: 5px 0; color: #666; font-size: 0.8em;'>※ ファイル選択後に「実行」ボタンを押してください</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<input type='hidden' name='csrf_token' value='" . generateCSRFToken() . "'>";
    echo "<button type='submit' style='background: #0d6efd; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; margin-right: 10px;'>実行</button>";
    echo "<button type='button' onclick='history.back()' style='background: gray; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px;'>キャンセル</button>";
    echo "</form>";
    
} else {
    // データが存在しない場合：サンプルデータ投入またはCSVインポート
    echo "<div style='color: blue; font-size: 1.2em; margin: 20px; padding: 20px; border: 2px solid blue;'>";
    echo "<h3>🚀 データベースの初期化を実行します</h3>";
    echo "<p>初期化方法を選択してください：</p>";
    echo "</div>";
    
    echo "<form method='POST' enctype='multipart/form-data' style='margin: 20px;'>";
    
    // サンプルデータ初期化オプション
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<label style='display: block; cursor: pointer;'>";
    echo "<input type='radio' name='init_type' value='full_reset' required style='margin-right: 10px;'>";
    echo "<strong>サンプルデータで初期化</strong>";
    echo "</label>";
    echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
    echo "テーブル作成 + サンプルデータ投入（3件のAED設置場所データ）<br>";
    echo "開発・テスト用に最適";
    echo "</p>";
    echo "</div>";
    
    // CSVインポートオプション（アップロード方式）
    echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<label style='display: block; cursor: pointer;'>";
    echo "<input type='radio' name='init_type' value='csv_import' required style='margin-right: 10px;'>";
    echo "<strong>CSVファイルからインポート</strong>";
    echo "</label>";
    echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
    echo "テーブル作成 + CSVファイルからAED設置場所データをインポート<br>";
    echo "本番データ投入に最適";
    echo "</p>";
    echo "<div style='margin: 10px 0 0 25px; padding: 8px; background: #f8f9fa; border-radius: 3px; font-size: 0.8em;'>";
    echo "<p style='margin: 0; color: #495057;'><strong>📋 CSVファイル要件:</strong></p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• ファイル形式: CSV (UTF-8エンコーディング)</p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• 最大ファイルサイズ: " . number_format($config['csv_import']['max_file_size'] / 1024 / 1024) . "MB</p>";
    echo "<p style='margin: 2px 0; color: #6c757d;'>• 列数: " . $config['csv_import']['validation']['expected_columns'] . "列 (ヘッダー行含む)</p>";
    echo "</div>";
    echo "<div style='margin: 10px 0 0 25px;' id='csv_upload_section2' style='display: none;'>";
    echo "<label for='csv_file2' style='display: block; margin: 5px 0; font-weight: bold;'>CSVファイルを選択:</label>";
    echo "<input type='file' id='csv_file2' name='csv_file' accept='.csv' style='margin: 5px 0; padding: 5px; border: 1px solid #ccc; border-radius: 3px;'>";
    echo "<p style='margin: 5px 0; color: #666; font-size: 0.8em;'>※ ファイル選択後に「初期化実行」ボタンを押してください</p>";
    echo "</div>";
    echo "</div>";
    
    echo "<input type='hidden' name='csrf_token' value='" . generateCSRFToken() . "'>";
    echo "<button type='submit' style='background: #0d6efd; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; margin-right: 10px;'>初期化実行</button>";
    echo "<button type='button' onclick='history.back()' style='background: gray; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px;'>キャンセル</button>";
    echo "</form>";
}

// JavaScriptコード（ファイルアップロード欄の表示制御）
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSVインポートオプションが選択された時の処理
    const radioButtons = document.querySelectorAll('input[name="init_type"]');
    const csvUploadSection = document.getElementById('csv_upload_section');
    const csvUploadSection2 = document.getElementById('csv_upload_section2');
    
    radioButtons.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'csv_import') {
                if (csvUploadSection) csvUploadSection.style.display = 'block';
                if (csvUploadSection2) csvUploadSection2.style.display = 'block';
            } else {
                if (csvUploadSection) csvUploadSection.style.display = 'none';
                if (csvUploadSection2) csvUploadSection2.style.display = 'none';
            }
        });
    });
    
    // ファイル選択時の検証
    const fileInputs = document.querySelectorAll('input[type="file"][name="csv_file"]');
    fileInputs.forEach(function(fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // ファイルサイズチェック
                const maxSize = <?= $config['csv_import']['max_file_size'] ?>;
                if (file.size > maxSize) {
                    alert('ファイルサイズが上限(' + Math.round(maxSize/1024/1024) + 'MB)を超えています。');
                    this.value = '';
                    return;
                }
                
                // ファイル拡張子チェック
                const allowedExtensions = <?= json_encode($config['csv_import']['allowed_extensions']) ?>;
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('CSVファイル(.csv)を選択してください。');
                    this.value = '';
                    return;
                }
                
                console.log('Selected file:', file.name, 'Size:', Math.round(file.size/1024) + 'KB');
            }
        });
    });
});
</script>
<?php

// 動的SQL生成ヘルパー関数
function generateInsertSQL($config, $tableName) {
    if (!isset($config['database']['tables'][$tableName])) {
        throw new Exception("Table '{$tableName}' not found in configuration");
    }
    
    $columns = $config['database']['tables'][$tableName]['columns'];
    
    // idカラムは除外（AUTO_INCREMENT）
    $insertColumns = [];
    $placeholders = [];
    
    foreach ($columns as $columnName => $columnType) {
        if ($columnName !== 'id' && $columnName !== 'created_at' && 
            strpos($columnType, 'DEFAULT CURRENT_TIMESTAMP') === false) {
            $insertColumns[] = $columnName;
            $placeholders[] = ":{$columnName}";
        }
    }
    
    $columnList = implode(', ', $insertColumns);
    $placeholderList = implode(', ', $placeholders);
    
    return "INSERT INTO {$tableName} ({$columnList}) VALUES ({$placeholderList})";
}

// 動的データバインディングヘルパー関数
function bindDataFromConfig($stmt, $data, $config, $tableName) {
    if (!isset($config['database']['tables'][$tableName])) {
        throw new Exception("Table '{$tableName}' not found in configuration");
    }
    
    $columns = $config['database']['tables'][$tableName]['columns'];
    
    foreach ($columns as $columnName => $columnType) {
        // idカラムとDEFAULT CURRENT_TIMESTAMPカラムはスキップ
        if ($columnName === 'id' || $columnName === 'created_at' || 
            strpos($columnType, 'DEFAULT CURRENT_TIMESTAMP') !== false) {
            continue;
        }
        
        // データの値を取得（存在しない場合は空文字）
        $value = $data[$columnName] ?? '';
        
        // デフォルト値の適用（設定ベース）
        if (isset($config['csv_import']['default_values'][$columnName]) && empty(trim($value))) {
            $value = $config['csv_import']['default_values'][$columnName];
        }
        
        // データ型を自動判定してバインド
        if (strpos($columnType, 'REAL') !== false || strpos($columnType, 'FLOAT') !== false) {
            $stmt->bindValue(":{$columnName}", floatval($value), SQLITE3_FLOAT);
        } elseif (strpos($columnType, 'INTEGER') !== false) {
            $stmt->bindValue(":{$columnName}", intval($value), SQLITE3_INTEGER);
        } else {
            $stmt->bindValue(":{$columnName}", (string)$value, SQLITE3_TEXT);
        }
    }
}

// CSVデータマッピングヘルパー関数
function mapCSVDataToFields($csvRow, $config) {
    $mapping = $config['csv_import']['field_mapping'];
    $mappedData = [];
    
    foreach ($mapping as $fieldName => $csvColumn) {
        if (isset($csvRow[$csvColumn])) {
            $mappedData[$fieldName] = trim($csvRow[$csvColumn]);
        } else {
            $mappedData[$fieldName] = '';
        }
    }
    
    return $mappedData;
}

// 設定整合性検証ヘルパー関数
function validateSampleDataAgainstConfig($config) {
    $tableColumns = array_keys($config['database']['tables']['facilities']['columns']);
    $sampleData = $config['sample_data'];
    
    foreach ($sampleData as $index => $facility) {
        foreach ($facility as $fieldName => $value) {
            if (!in_array($fieldName, $tableColumns)) {
                throw new Exception("Sample data field '{$fieldName}' at index {$index} not found in table configuration");
            }
        }
    }
    
    return true;
}

// CSVマッピング整合性検証ヘルパー関数
function validateCSVMappingAgainstConfig($config) {
    if (!isset($config['csv_import']['field_mapping'])) {
        throw new Exception("CSV field mapping not found in configuration");
    }
    
    $tableColumns = array_keys($config['database']['tables']['facilities']['columns']);
    $csvMapping = $config['csv_import']['field_mapping'];
    
    foreach ($csvMapping as $fieldName => $csvColumn) {
        if (!in_array($fieldName, $tableColumns)) {
            throw new Exception("CSV mapping field '{$fieldName}' not found in table configuration");
        }
        
        if (!is_numeric($csvColumn) || $csvColumn < 0) {
            throw new Exception("CSV mapping column for field '{$fieldName}' must be a non-negative integer");
        }
    }
    
    // 必須フィールドがマッピングに含まれているかチェック
    if (isset($config['csv_import']['required_fields'])) {
        $requiredFields = $config['csv_import']['required_fields'];
        foreach ($requiredFields as $field) {
            if (!isset($csvMapping[$field])) {
                throw new Exception("Required field '{$field}' not found in CSV mapping");
            }
        }
    }
    
    return true;
}

// 統合設定検証関数
function validateFullConfig($config) {
    validateConfig($config);
    validateSampleDataAgainstConfig($config);
    validateCSVMappingAgainstConfig($config);
    return true;
}

// アップロードファイル検証ヘルパー関数
function validateUploadedCSVFile($config) {
    // アップロードファイルの存在確認
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("CSVファイルがアップロードされていません。");
    }
    
    $uploadedFile = $_FILES['csv_file'];
    
    // ファイルサイズチェック
    if ($uploadedFile['size'] > $config['csv_import']['max_file_size']) {
        $maxSizeMB = round($config['csv_import']['max_file_size'] / 1024 / 1024);
        throw new Exception("ファイルサイズが上限({$maxSizeMB}MB)を超えています。");
    }
    
    // ファイル拡張子チェック
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $config['csv_import']['allowed_extensions'])) {
        throw new Exception("CSVファイル以外はアップロードできません。");
    }
    
    // MIMEタイプチェック
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $config['csv_import']['allowed_mime_types'])) {
        throw new Exception("不正なファイル形式です。");
    }
    
    // ファイルが読み取り可能かチェック
    if (!is_readable($uploadedFile['tmp_name'])) {
        throw new Exception("アップロードされたファイルが読み取り不可能です。");
    }
    
    return $uploadedFile['tmp_name'];
}

// 自動カテゴリ分類関数
function categorize_facility($facility_name) {
    // 民間企業
    if (preg_match('/会社|㈱/', $facility_name)) {
        return 'その他';
    }
    
    // コンビニエンスストア
    if (preg_match('/ファミリーマート|セブンイレブン|ミニストップ/', $facility_name)) {
        return 'コンビニエンスストア';
    }
    
    // 医療機関
    if (preg_match('/歯科|医院|病院|クリニック/', $facility_name)) {
        return '医療機関';
    }
    
    // 公共施設
    if (preg_match('/市役所|小山市|庁舎|出張所|図書館|博物館|資料館|公園|保育所|センター/', $facility_name)) {
        return '公共施設';
    }
    
    // 学校・教育機関
    if (preg_match('/小学校|中学校|高等学校|義務教育学校|大学/', $facility_name)) {
        return '学校・教育機関';
    }
    
    return 'その他';
}

// 設定ファイル構造検証機能
function validateConfig($config) {
    $requiredKeys = ['database', 'app', 'admin'];
    foreach ($requiredKeys as $key) {
        if (!isset($config[$key])) {
            throw new Exception("Missing required config section: {$key}");
        }
    }
    
    // データベース設定の検証
    if (!isset($config['database']['tables'])) {
        throw new Exception("Missing database.tables configuration");
    }
    
    validateTableConfig($config);
}

function validateTableConfig($config) {
    $required = ['columns'];
    $tables = $config['database']['tables'];
    
    foreach ($tables as $tableName => $tableConfig) {
        foreach ($required as $key) {
            if (!isset($tableConfig[$key])) {
                throw new Exception("Missing {$key} in table {$tableName}");
            }
        }
        
        // カラム定義の検証
        if (empty($tableConfig['columns'])) {
            throw new Exception("Table {$tableName} has no columns defined");
        }
        
        // 外部キー制約の検証
        if (isset($tableConfig['foreign_keys'])) {
            foreach ($tableConfig['foreign_keys'] as $fkName => $fkConfig) {
                if (!isset($fkConfig['references'])) {
                    throw new Exception("Foreign key {$fkName} in table {$tableName} missing references");
                }
            }
        }
    }
}

// テーブル構造読み込み用ヘルパー関数
function getTableSchema($config, $tableName) {
    if (!isset($config['database']['tables'][$tableName])) {
        $available = implode(', ', array_keys($config['database']['tables']));
        throw new Exception("Table '{$tableName}' not found in configuration. Available tables: {$available}");
    }
    
    $table = $config['database']['tables'][$tableName];
    $columns = $table['columns'];
    
    // CREATE TABLE文の生成
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (\n";
    $columnDefinitions = [];
    
    foreach ($columns as $columnName => $columnType) {
        $columnDefinitions[] = "        {$columnName} {$columnType}";
    }
    
    $sql .= implode(",\n", $columnDefinitions);
    
    // 外部キー制約の追加
    if (isset($table['foreign_keys'])) {
        foreach ($table['foreign_keys'] as $keyName => $keyDef) {
            $sql .= ",\n        FOREIGN KEY ({$keyName}) REFERENCES {$keyDef['references']}";
            if (isset($keyDef['on_delete'])) {
                $sql .= " ON DELETE {$keyDef['on_delete']}";
            }
        }
    }
    
    $sql .= "\n    )";
    
    return $sql;
}

// インデックス作成用ヘルパー関数
function createTableIndexes($config, $tableName, $db) {
    if (!isset($config['database']['tables'][$tableName]['indexes'])) {
        return; // インデックス定義がない場合は何もしない
    }
    
    $indexes = $config['database']['tables'][$tableName]['indexes'];
    
    foreach ($indexes as $indexName => $columns) {
        $columnList = implode(', ', $columns);
        $sql = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName} ({$columnList})";
        
        try {
            $db->exec($sql);
        } catch (Exception $e) {
            error_log("Failed to create index {$indexName}: " . $e->getMessage());
        }
    }
}

// テーブル削除用ヘルパー関数
function dropAllTables($config, $db) {
    // 設定ファイルから削除順序を取得
    $dropOrder = $config['database']['drop_order'] ?? array_keys($config['database']['tables']);
    
    foreach ($dropOrder as $tableName) {
        try {
            $db->exec("DROP TABLE IF EXISTS {$tableName}");
        } catch (Exception $e) {
            error_log("Failed to drop table {$tableName}: " . $e->getMessage());
        }
    }
}

// 構成のみ更新関数（データ保持）
function updateDatabaseSchema($config) {
    // 設定ファイルの検証
    validateFullConfig($config);
    
    $db = getDatabase();
    
    // 設定からテーブル構造を取得してテーブル作成
    $facilitiesTableSQL = getTableSchema($config, 'facilities');
    $db->exec($facilitiesTableSQL);
    
    // 既存テーブルに新しいカラムを追加（設定ファイルベース）
    $facilityColumns = $config['database']['tables']['facilities']['columns'];
    
    foreach ($facilityColumns as $columnName => $columnType) {
        // idカラムはスキップ（既存のPRIMARY KEYのため）
        if ($columnName === 'id') {
            continue;
        }
        
        // カラム存在チェック
        $checkResult = $db->query("PRAGMA table_info(facilities)");
        $columnExists = false;
        while ($row = $checkResult->fetchArray()) {
            if ($row['name'] === $columnName) {
                $columnExists = true;
                break;
            }
        }
        
        // カラムが存在しない場合のみ追加
        if (!$columnExists) {
            try {
                $result = $db->exec("ALTER TABLE facilities ADD COLUMN {$columnName} {$columnType}");
                if ($result === false) {
                    error_log("Failed to add column {$columnName}: " . $db->lastErrorMsg());
                } else {
                    // updated_atカラムを追加した場合、既存レコードに日本時間を設定
                    if ($columnName === 'updated_at') {
                        $japanTime = date('Y-m-d H:i:s', time());
                        $db->exec("UPDATE facilities SET updated_at = '{$japanTime}' WHERE updated_at IS NULL");
                    }
                }
            } catch (Exception $e) {
                error_log("Exception adding column {$columnName}: " . $e->getMessage());
            }
        }
    }
    
    // 他のテーブルも設定から作成
    $facilityImagesTableSQL = getTableSchema($config, 'facility_images');
    $db->exec($facilityImagesTableSQL);
    
    $adminSettingsTableSQL = getTableSchema($config, 'admin_settings');
    $db->exec($adminSettingsTableSQL);
    
    // 全テーブルのインデックスを作成
    createTableIndexes($config, 'facilities', $db);
    createTableIndexes($config, 'facility_images', $db);
    createTableIndexes($config, 'admin_settings', $db);
    
    // テーブル構造の確認結果を取得
    $tableInfo = [];
    $result = $db->query("PRAGMA table_info(facilities)");
    while ($row = $result->fetchArray()) {
        $tableInfo[] = $row['name'] . ' (' . $row['type'] . ')';
    }
    
    $db->close();
    
    // テーブル構造をセッションに保存（完了画面で表示するため）
    $_SESSION['table_structure'] = $tableInfo;
    
    return true;
}

// 全削除初期化関数（サンプルデータのみ）
function resetDatabaseWithSampleData($config) {
    // 設定ファイルの検証
    validateFullConfig($config);
    
    $db = getDatabase();
    
    // テーブルを削除（設定ファイルベース）
    dropAllTables($config, $db);
    
    // 既存の画像ファイルも削除
    $imageDir = __DIR__ . '/' . $config['storage']['images_dir'] . '/';
    if (is_dir($imageDir)) {
        $files = glob($imageDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    // テーブル再作成（設定ファイルから）
    $tables = array_keys($config['database']['tables']);
    foreach ($tables as $tableName) {
        $tableSQL = getTableSchema($config, $tableName);
        $db->exec($tableSQL);
        
        // インデックスも作成
        createTableIndexes($config, $tableName, $db);
    }
    
    // サンプルデータ（設定ファイルから取得）
    $facilities = $config['sample_data'];
    
    // 動的SQL生成
    $insertSQL = generateInsertSQL($config, 'facilities');
    
    foreach ($facilities as $facility) {
        $stmt = $db->prepare($insertSQL);
        
        // 動的データバインディング
        bindDataFromConfig($stmt, $facility, $config, 'facilities');
        
        $stmt->execute();
    }
    
    $db->close();
    return true;
}

// 全削除初期化関数（CSVインポート）
function resetDatabaseWithCSVData($config) {
    try {
        // 設定ファイルの検証
        validateFullConfig($config);
        
        // アップロードされたCSVファイルの検証
        $csvFilePath = validateUploadedCSVFile($config);
        
    } catch (Exception $e) {
        error_log("CSV Import Error: " . $e->getMessage());
        return false;
    }
    
    $db = getDatabase();
    
    // テーブルを削除（設定ファイルベース）
    dropAllTables($config, $db);
    
    // 既存の画像ファイルも削除
    $imageDir = __DIR__ . '/' . $config['storage']['images_dir'] . '/';
    if (is_dir($imageDir)) {
        $files = glob($imageDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    // テーブル再作成（設定ファイルから）
    $tables = array_keys($config['database']['tables']);
    foreach ($tables as $tableName) {
        $tableSQL = getTableSchema($config, $tableName);
        $db->exec($tableSQL);
        
        // インデックスも作成
        createTableIndexes($config, $tableName, $db);
    }
    
    // CSVファイルの読み込みとデータインポート
    $csvData = [];
    $categoryCount = [];
    $lineNumber = 0;
    $importedCount = 0;
    
    // 動的SQL生成
    $insertSQL = generateInsertSQL($config, 'facilities');
    
    // 設定ファイルから検証パラメータを取得
    $expectedColumns = $config['csv_import']['validation']['expected_columns'];
    $latMin = $config['csv_import']['validation']['lat_min'];
    $latMax = $config['csv_import']['validation']['lat_max'];
    $lngMin = $config['csv_import']['validation']['lng_min'];
    $lngMax = $config['csv_import']['validation']['lng_max'];
    $requiredFields = $config['csv_import']['required_fields'];
    
    // CSVファイルを開く
    if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
        // 最初の行（ヘッダー）をスキップ（設定により）
        if ($config['csv_import']['has_header'] && ($header = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lineNumber++;
        }
        
        // データ行を読み込み
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lineNumber++;
            
            // データ列数の検証（設定ベース）
            if (count($data) < $expectedColumns) {
                error_log("CSV Import Warning: Insufficient data columns at line " . $lineNumber . " (expected {$expectedColumns}, got " . count($data) . ")");
                continue;
            }
            
            // CSVデータのマッピング（設定ベース）
            $mappedData = mapCSVDataToFields($data, $config);
            
            // 必須フィールドの検証（設定ベース）
            $hasRequiredData = true;
            foreach ($requiredFields as $field) {
                if (empty($mappedData[$field]) || ($field === 'lat' && floatval($mappedData[$field]) == 0) || 
                    ($field === 'lng' && floatval($mappedData[$field]) == 0)) {
                    $hasRequiredData = false;
                    break;
                }
            }
            
            if (!$hasRequiredData) {
                error_log("CSV Import Warning: Missing required data at line " . $lineNumber);
                continue;
            }
            
            // 緯度・経度の範囲チェック（設定ベース）
            $lat = floatval($mappedData['lat']);
            $lng = floatval($mappedData['lng']);
            if ($lat < $latMin || $lat > $latMax || $lng < $lngMin || $lng > $lngMax) {
                error_log("CSV Import Warning: Invalid coordinates at line " . $lineNumber . " - lat: $lat, lng: $lng");
                continue;
            }
            
            // 自動カテゴリ分類
            $mappedData['category'] = categorize_facility($mappedData['name']);
            
            // カテゴリ別件数をカウント
            $category = $mappedData['category'];
            if (!isset($categoryCount[$category])) {
                $categoryCount[$category] = 0;
            }
            $categoryCount[$category]++;
            
            // データベースに挿入（動的バインディング）
            $stmt = $db->prepare($insertSQL);
            bindDataFromConfig($stmt, $mappedData, $config, 'facilities');
            
            if ($stmt->execute()) {
                $importedCount++;
            } else {
                error_log("Failed to insert facility: " . $mappedData['name'] . " (Line: " . $lineNumber . ") - " . $db->lastErrorMsg());
            }
        }
        
        fclose($handle);
    } else {
        error_log("Cannot open CSV file: " . $csvFilePath);
        $db->close();
        return false;
    }
    
    $db->close();
    
    // インポート結果をセッションに保存
    $_SESSION['csv_import_results'] = $categoryCount;
    
    // 最低限のデータがインポートされたかチェック
    if ($importedCount < 1) {
        error_log("CSV Import Error: No valid data imported");
        return false;
    }
    
    return true;
}

