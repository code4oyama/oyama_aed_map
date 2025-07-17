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
        $db = new SQLite3($config['database']['path']);
        
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

// CSVファイルの確認
$csvFilePath = __DIR__ . '/AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv';
$csvExists = file_exists($csvFilePath);
$csvInfo = null;
if ($csvExists) {
    $csvInfo = [
        'size' => filesize($csvFilePath),
        'modified' => date('Y-m-d H:i:s', filemtime($csvFilePath))
    ];
}

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
    
    echo "<form method='POST' style='margin: 20px;'>";
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
    
    // CSVインポートオプション
    if ($csvExists) {
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
        echo "<p style='margin: 0; color: #495057;'><strong>📄 CSVファイル情報:</strong></p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>ファイル: AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv</p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>サイズ: " . number_format($csvInfo['size']) . " bytes</p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>更新日時: " . $csvInfo['modified'] . "</p>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; opacity: 0.6;'>";
        echo "<label style='display: block; cursor: not-allowed;'>";
        echo "<input type='radio' name='init_type' value='csv_import' disabled style='margin-right: 10px;'>";
        echo "<strong>全削除してCSVからインポート</strong> <span style='color: #dc3545; font-size: 0.9em;'>(CSVファイルが見つかりません)</span>";
        echo "</label>";
        echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
        echo "CSVファイル「AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv」をルートディレクトリに配置してください";
        echo "</p>";
        echo "</div>";
    }
    
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
    
    echo "<form method='POST' style='margin: 20px;'>";
    
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
    
    // CSVインポートオプション
    if ($csvExists) {
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
        echo "<p style='margin: 0; color: #495057;'><strong>📄 CSVファイル情報:</strong></p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>ファイル: AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv</p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>サイズ: " . number_format($csvInfo['size']) . " bytes</p>";
        echo "<p style='margin: 2px 0; color: #6c757d;'>更新日時: " . $csvInfo['modified'] . "</p>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; opacity: 0.6;'>";
        echo "<label style='display: block; cursor: not-allowed;'>";
        echo "<input type='radio' name='init_type' value='csv_import' disabled style='margin-right: 10px;'>";
        echo "<strong>CSVファイルからインポート</strong> <span style='color: #dc3545; font-size: 0.9em;'>(CSVファイルが見つかりません)</span>";
        echo "</label>";
        echo "<p style='margin: 10px 0 0 25px; color: #666; font-size: 0.9em;'>";
        echo "CSVファイル「AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv」をルートディレクトリに配置してください";
        echo "</p>";
        echo "</div>";
    }
    
    echo "<input type='hidden' name='csrf_token' value='" . generateCSRFToken() . "'>";
    echo "<button type='submit' style='background: #0d6efd; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; margin-right: 10px;'>初期化実行</button>";
    echo "<button type='button' onclick='history.back()' style='background: gray; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px;'>キャンセル</button>";
    echo "</form>";
}

// 自動カテゴリ分類関数
function categorize_facility($facility_name) {
    // 学校・教育機関
    if (preg_match('/小学校|中学校|高等学校|義務教育学校|大学/', $facility_name)) {
        return '学校・教育機関';
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
    if (preg_match('/市役所|出張所|センター|図書館|博物館|保育所|児童センター/', $facility_name)) {
        return '公共施設';
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
    validateConfig($config);
    
    $db = new SQLite3($config['database']['path']);
    
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
    validateConfig($config);
    
    $db = new SQLite3($config['database']['path']);
    
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
    
    foreach ($facilities as $facility) {
        $stmt = $db->prepare('INSERT INTO facilities (
            csv_no, name, name_kana, lat, lng, address, address_detail, 
            installation_position, phone, phone_extension, corporate_number, 
            organization_name, available_days, start_time, end_time, 
            available_hours_note, pediatric_support, website, note, category
        ) VALUES (
            :csv_no, :name, :name_kana, :lat, :lng, :address, :address_detail,
            :installation_position, :phone, :phone_extension, :corporate_number,
            :organization_name, :available_days, :start_time, :end_time,
            :available_hours_note, :pediatric_support, :website, :note, :category
        )');
        
        $stmt->bindValue(':csv_no', $facility['csv_no'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':name', $facility['name'], SQLITE3_TEXT);
        $stmt->bindValue(':name_kana', $facility['name_kana'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lat', $facility['lat'], SQLITE3_FLOAT);
        $stmt->bindValue(':lng', $facility['lng'], SQLITE3_FLOAT);
        $stmt->bindValue(':address', $facility['address'], SQLITE3_TEXT);
        $stmt->bindValue(':address_detail', $facility['address_detail'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':installation_position', $facility['installation_position'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':phone', $facility['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':phone_extension', $facility['phone_extension'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':corporate_number', $facility['corporate_number'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':organization_name', $facility['organization_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':available_days', $facility['available_days'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':start_time', $facility['start_time'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':end_time', $facility['end_time'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':available_hours_note', $facility['available_hours_note'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':pediatric_support', $facility['pediatric_support'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':website', $facility['website'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':note', $facility['note'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':category', $facility['category'], SQLITE3_TEXT);
        $stmt->execute();
    }
    
    $db->close();
    return true;
}

// 全削除初期化関数（CSVインポート）
function resetDatabaseWithCSVData($config) {
    try {
        // 設定ファイルの検証
        validateConfig($config);
        
        // CSVファイルの確認
        $csvFilePath = __DIR__ . '/AED設置場所_小山市オープンデータ_UTF-8_BOM無.csv';
        if (!file_exists($csvFilePath)) {
            throw new Exception("CSVファイルが見つかりません: " . $csvFilePath);
        }
        
        // ファイルが読み取り可能かチェック
        if (!is_readable($csvFilePath)) {
            throw new Exception("CSVファイルが読み取り不可能です: " . $csvFilePath);
        }
    } catch (Exception $e) {
        error_log("CSV Import Error: " . $e->getMessage());
        return false;
    }
    
    $db = new SQLite3($config['database']['path']);
    
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
    
    // CSVファイルを開く
    if (($handle = fopen($csvFilePath, "r")) !== FALSE) {
        // 最初の行（ヘッダー）をスキップ
        if (($header = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lineNumber++;
        }
        
        // データ行を読み込み
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $lineNumber++;
            
            // データが22項目未満の場合はスキップ（備考まで含む）
            if (count($data) < 22) {
                error_log("CSV Import Warning: Insufficient data columns at line " . $lineNumber . " (expected 22, got " . count($data) . ")");
                continue;
            }
            
            // CSVデータのマッピング
            $csvNo = trim($data[1]);           // NO
            $name = trim($data[4]);            // 名称
            $nameKana = trim($data[5]);        // 名称_カナ
            $address = trim($data[6]);         // 住所
            $addressDetail = trim($data[7]);   // 方書
            $lat = floatval($data[8]);         // 緯度
            $lng = floatval($data[9]);         // 経度
            $installationPosition = trim($data[10]); // 設置位置
            $phone = trim($data[11]);          // 電話番号
            $phoneExtension = trim($data[12]); // 内線番号
            $corporateNumber = trim($data[13]); // 法人番号
            $organizationName = trim($data[14]); // 団体名
            $availableDays = trim($data[15]);  // 利用可能曜日
            $startTime = trim($data[16]);      // 開始時間
            $endTime = trim($data[17]);        // 終了時間
            $availableHoursNote = trim($data[18]); // 利用可能日時特記事項
            $pediatricSupport = trim($data[19]); // 小児対応設備の有無
            $website = trim($data[20]);        // URL
            $note = isset($data[21]) ? trim($data[21]) : '';           // 備考
            
            // 基本データの検証
            if (empty($name) || $lat == 0 || $lng == 0) {
                error_log("CSV Import Warning: Invalid data at line " . $lineNumber . " - name: '$name', lat: $lat, lng: $lng");
                continue;
            }
            
            // 緯度・経度の範囲チェック（日本の範囲内）
            if ($lat < 24 || $lat > 46 || $lng < 123 || $lng > 146) {
                error_log("CSV Import Warning: Invalid coordinates at line " . $lineNumber . " - lat: $lat, lng: $lng");
                continue;
            }
            
            // 自動カテゴリ分類
            $category = categorize_facility($name);
            
            // カテゴリ別件数をカウント
            if (!isset($categoryCount[$category])) {
                $categoryCount[$category] = 0;
            }
            $categoryCount[$category]++;
            
            // データベースに挿入
            $stmt = $db->prepare('INSERT INTO facilities (
                csv_no, name, name_kana, lat, lng, address, address_detail, 
                installation_position, phone, phone_extension, corporate_number, 
                organization_name, available_days, start_time, end_time, 
                available_hours_note, pediatric_support, website, note, category
            ) VALUES (
                :csv_no, :name, :name_kana, :lat, :lng, :address, :address_detail,
                :installation_position, :phone, :phone_extension, :corporate_number,
                :organization_name, :available_days, :start_time, :end_time,
                :available_hours_note, :pediatric_support, :website, :note, :category
            )');
            
            $stmt->bindValue(':csv_no', $csvNo, SQLITE3_TEXT);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':name_kana', $nameKana, SQLITE3_TEXT);
            $stmt->bindValue(':lat', $lat, SQLITE3_FLOAT);
            $stmt->bindValue(':lng', $lng, SQLITE3_FLOAT);
            $stmt->bindValue(':address', $address, SQLITE3_TEXT);
            $stmt->bindValue(':address_detail', $addressDetail, SQLITE3_TEXT);
            $stmt->bindValue(':installation_position', $installationPosition, SQLITE3_TEXT);
            $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
            $stmt->bindValue(':phone_extension', $phoneExtension, SQLITE3_TEXT);
            $stmt->bindValue(':corporate_number', $corporateNumber, SQLITE3_TEXT);
            $stmt->bindValue(':organization_name', $organizationName, SQLITE3_TEXT);
            $stmt->bindValue(':available_days', $availableDays, SQLITE3_TEXT);
            $stmt->bindValue(':start_time', $startTime, SQLITE3_TEXT);
            $stmt->bindValue(':end_time', $endTime, SQLITE3_TEXT);
            $stmt->bindValue(':available_hours_note', $availableHoursNote, SQLITE3_TEXT);
            $stmt->bindValue(':pediatric_support', $pediatricSupport, SQLITE3_TEXT);
            $stmt->bindValue(':website', $website, SQLITE3_TEXT);
            $stmt->bindValue(':note', $note, SQLITE3_TEXT);
            $stmt->bindValue(':category', $category, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $importedCount++;
            } else {
                error_log("Failed to insert facility: " . $name . " (Line: " . $lineNumber . ") - " . $db->lastErrorMsg());
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

