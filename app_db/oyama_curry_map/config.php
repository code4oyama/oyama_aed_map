<?php
// 施設マップ設定ファイル
// このファイルはWeb外に配置されているため直接アクセス不可

// 直接アクセス防止
if (!defined('CONFIG_ACCESS_ALLOWED')) {
    die('Direct access to this file is not allowed.');
}

return [
    // データベース設定
    'database' => [
        'path' => __DIR__ . '/facilities.db',
        'tables' => [
            'facilities' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'csv_no' => 'TEXT',  // CSVの識別番号
                    'name' => 'TEXT NOT NULL',  // 名称
                    'name_kana' => 'TEXT',  // 名称_カナ
                    'lat' => 'REAL NOT NULL',  // 緯度
                    'lng' => 'REAL NOT NULL',  // 経度
                    'address' => 'TEXT',  // 住所
                    'address_detail' => 'TEXT',  // 方書
                    'installation_position' => 'TEXT',  // 設置位置
                    'phone' => 'TEXT',  // 電話番号
                    'phone_extension' => 'TEXT',  // 内線番号
                    'corporate_number' => 'TEXT',  // 法人番号
                    'organization_name' => 'TEXT',  // 団体名
                    'available_days' => 'TEXT',  // 利用可能曜日
                    'start_time' => 'TEXT',  // 開始時間
                    'end_time' => 'TEXT',  // 終了時間
                    'available_hours_note' => 'TEXT',  // 利用可能日時特記事項
                    'pediatric_support' => 'TEXT',  // 小児対応設備の有無
                    'website' => 'TEXT',  // URL
                    'note' => 'TEXT',  // 備考
                    'category' => 'TEXT',  // カテゴリ（公共施設、学校、コンビニ等）
                    'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'indexes' => [
                    'idx_facilities_location' => ['lat', 'lng'],
                    'idx_facilities_updated_at' => ['updated_at'],
                    'idx_facilities_category' => ['category'],
                    'idx_facilities_csv_no' => ['csv_no']
                ]
            ],
            'facility_images' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'facility_id' => 'INTEGER NOT NULL',
                    'filename' => 'TEXT NOT NULL',
                    'original_name' => 'TEXT NOT NULL',
                    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'foreign_keys' => [
                    'facility_id' => [
                        'references' => 'facilities(id)',
                        'on_delete' => 'CASCADE'
                    ]
                ],
                'indexes' => [
                    'idx_facility_images_facility_id' => ['facility_id'],
                    'idx_facility_images_created_at' => ['created_at']
                ]
            ],
            'admin_settings' => [
                'columns' => [
                    'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'setting_key' => 'TEXT UNIQUE NOT NULL',
                    'setting_value' => 'TEXT NOT NULL',
                    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                    'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                ],
                'indexes' => [
                    'idx_admin_settings_key' => ['setting_key'],
                    'idx_admin_settings_updated_at' => ['updated_at']
                ]
            ]
        ],
        'drop_order' => ['facility_images', 'facilities', 'admin_settings']
    ],
    
    // 管理者設定
    'admin' => [
        'password' => 'admin123',  // 初期パスワード（初回設定後に変更推奨）
        'session_timeout' => 1800  // 30分（秒）
    ],
    
    // アプリケーション設定
    'app' => [
        'name' => 'おやまAEDマップ',
        'version' => '1.0.0',
        'timezone' => 'Asia/Tokyo',
        'facility_name' => 'AED設置場所',  // 施設の呼称
        'categories' => [
            '公共施設',
            '学校・教育機関',
            'コンビニエンスストア',
            '医療機関',
            'その他'
        ],
        'field_labels' => [
            'name' => '施設名',
            'name_kana' => '施設名（カナ）',
            'category' => 'カテゴリー',
            'address' => '住所',
            'address_detail' => '住所詳細',
            'installation_position' => '設置位置',
            'phone' => '電話番号',
            'phone_extension' => '内線番号',
            'corporate_number' => '法人番号',
            'organization_name' => '団体名',
            'available_days' => '利用可能曜日',
            'start_time' => '開始時間',
            'end_time' => '終了時間',
            'available_hours_note' => '利用可能時間備考',
            'pediatric_support' => '小児対応設備',
            'website' => 'ウェブサイト',
            'note' => '備考',
            'images' => '画像',
            'location' => '位置情報'
        ]
    ],
    
    // 地図設定
    'map' => [
        'initial_latitude' => 36.3141,   // 初期表示緯度（小山市中心）
        'initial_longitude' => 139.8006, // 初期表示経度（小山市中心）
        'initial_zoom' => 14             // 初期ズームレベル
    ],
    
    // セキュリティ設定
    'security' => [
        'max_image_size' => 5 * 1024 * 1024,  // 5MB
        'max_images_per_facility' => 10,
        'max_review_length' => 2000
    ],
    
    // ストレージ設定
    'storage' => [
        'images_dir' => 'facility_images',
        'database_file' => 'facilities.db'
    ],
    
    // サンプルデータ設定
    'sample_data' => [
        [
            'csv_no' => '0111000001',
            'name' => '小山市役所',
            'name_kana' => 'オヤマシヤクショ',
            'lat' => 36.314502,
            'lng' => 139.800732,
            'address' => '栃木県小山市中央町1-1-1',
            'address_detail' => '',
            'installation_position' => '1階',
            'phone' => '(0285)23-1111',
            'phone_extension' => '',
            'corporate_number' => '4000020092088',
            'organization_name' => '小山市',
            'available_days' => '月火水木金',
            'start_time' => '8:30',
            'end_time' => '17:15',
            'available_hours_note' => '祝日、年末年始を除く。',
            'pediatric_support' => '有',
            'website' => '',
            'note' => '',
            'category' => '公共施設'
        ],
        [
            'csv_no' => '0111000002',
            'name' => '道の駅思川',
            'name_kana' => 'ミチノエキオモイガワ',
            'lat' => 36.308174,
            'lng' => 139.760202,
            'address' => '栃木県小山市下国府塚25-1',
            'address_detail' => '',
            'installation_position' => '事務室',
            'phone' => '(0285)38-0201',
            'phone_extension' => '',
            'corporate_number' => '4000020092088',
            'organization_name' => '小山市',
            'available_days' => '月火水木金土日',
            'start_time' => '9:00',
            'end_time' => '18:00',
            'available_hours_note' => '',
            'pediatric_support' => '有',
            'website' => '',
            'note' => '',
            'category' => '公共施設'
        ],
        [
            'csv_no' => '0111000036',
            'name' => 'ファミリーマート小山出井北店',
            'name_kana' => 'ファミリーマートオヤマイデイキタテン',
            'lat' => 36.345314,
            'lng' => 139.851986,
            'address' => '栃木県小山市出井1252-10',
            'address_detail' => '',
            'installation_position' => 'カウンター裏バックヤード',
            'phone' => '(0285)30-3113',
            'phone_extension' => '',
            'corporate_number' => '4000020092088',
            'organization_name' => '小山市',
            'available_days' => '月火水木金土日',
            'start_time' => '',
            'end_time' => '',
            'available_hours_note' => '店舗の営業時間に従う',
            'pediatric_support' => '有',
            'website' => '',
            'note' => '',
            'category' => 'コンビニエンスストア'
        ]
    ]
];
?>