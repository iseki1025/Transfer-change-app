<?php
/**
 * 送迎管理システム - トップページ（今日以降の予定 + 未対応の連絡事項）
 * v9: 連絡事項対応、入院一覧リンク追加、ソート修正
 */

$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
    die('DB Error');
}

// 設定取得
$pdo->exec("CREATE TABLE IF NOT EXISTS app_config (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    hospital_name TEXT DEFAULT '',
    hospital_tel TEXT DEFAULT '',
    pharmacy_name TEXT DEFAULT '',
    transfer_schedule_url TEXT DEFAULT ''
)");
$stmt = $pdo->query("SELECT * FROM app_config WHERE id = 1");
$config = $stmt->fetch();
if (!$config) {
    $pdo->exec("INSERT INTO app_config (id, hospital_name, hospital_tel, pharmacy_name, transfer_schedule_url) VALUES (1, '○○透析クリニック', '00-0000-0000', '○○薬局', '')");
    $config = [
        'hospital_name' => '○○透析クリニック',
        'hospital_tel' => '00-0000-0000',
        'pharmacy_name' => '○○薬局',
        'transfer_schedule_url' => ''
    ];
}
$scheduleUrl = $config['transfer_schedule_url'] ?? '#';
if (empty($scheduleUrl))
    $scheduleUrl = '#'; // 未設定時は#

// テーブル作成
$pdo->exec("CREATE TABLE IF NOT EXISTS records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    p_name TEXT NOT NULL,
    event_type TEXT NOT NULL,
    orig_date TEXT,
    orig_weekday TEXT,
    orig_schedule TEXT,
    target_date TEXT,
    target_weekday TEXT,
    new_schedule TEXT,
    reason TEXT,
    needs_pickup INTEGER DEFAULT 1,
    needs_dropoff INTEGER DEFAULT 1,
    bed_change TEXT DEFAULT 'なし',
    bed_no TEXT,
    bxp_change TEXT DEFAULT 'なし',
    exam_change TEXT DEFAULT 'なし',
    pharmacy_req TEXT DEFAULT '不要',
    pharmacy_date TEXT,
    drv_name TEXT,
    pickup_time TEXT,
    chk_drv1 INTEGER DEFAULT 0,
    chk_drv2 INTEGER DEFAULT 0,
    fax_sent INTEGER DEFAULT 0,
    created_by TEXT,
    technician TEXT,
    office_staff TEXT,
    is_handled INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// マイグレーション
$migCols = [
    "orig_date TEXT",
    "orig_weekday TEXT",
    "needs_pickup INTEGER DEFAULT 1",
    "needs_dropoff INTEGER DEFAULT 1",
    "created_by TEXT",
    "technician TEXT",
    "office_staff TEXT",
    "bxp_change TEXT DEFAULT 'なし'",
    "exam_change TEXT DEFAULT 'なし'",
    "is_handled INTEGER DEFAULT 0"
];
foreach ($migCols as $col) {
    try {
        $pdo->exec("ALTER TABLE records ADD COLUMN $col");
    } catch (Exception $e) {
    }
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM records WHERE id=?")->execute([$_POST['id']]);
    } elseif ($action === 'check') {
        $field = $_POST['field'];
        $value = $_POST['value'];
        $id = $_POST['id'];
        if (in_array($field, ['chk_drv1', 'chk_drv2'])) {
            $pdo->prepare("UPDATE records SET $field = ? WHERE id = ?")->execute([$value, $id]);
        }
    } elseif ($action === 'handle') {
        // 連絡事項の対応済み処理
        $pdo->prepare("UPDATE records SET is_handled = 1 WHERE id = ?")->execute([$_POST['id']]);
    }
}

// データ取得
// 1. 今日以降の予定（入院除く）
// 2. 変更の場合は元の日付or先の日付が今日以降なら表示
// 3. 連絡事項は未対応なら日付問わず（あるいは今日以降）表示 -> UI的には「未対応ならずっと」が要望なので日付条件外す
// 4. 入院は今日以降のみ（過去の入院は入院一覧へ）
$today = date('Y-m-d');
$sql = "SELECT * FROM records WHERE 
    (event_type = '連絡事項' AND (chk_drv1 = 0 OR chk_drv2 = 0)) OR
    (event_type != '連絡事項' AND event_type != '入院' AND target_date >= '$today') OR
    (event_type = '入院' AND target_date >= '$today') OR
    (event_type = '変更' AND (orig_date >= '$today' OR target_date >= '$today'))
    ORDER BY 
    CASE WHEN (chk_drv1 = 1 AND chk_drv2 = 1) THEN 1 ELSE 0 END ASC,
    target_date ASC";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送迎管理</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --danger: #ef4444;
            --warning: #f97316;
            --success: #22c55e;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-100: #f1f5f9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans JP', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e6e9ef 100%);
            color: var(--slate-800);
            min-height: 100vh;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        header h1 {
            font-size: 1.2rem;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 16px;
        }

        .nav-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .add-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
            margin-bottom: 24px;
        }

        .add-btn svg {
            width: 26px;
            height: 26px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 10px 10px;
            text-align: left;
            border-bottom: 2px solid var(--slate-300);
            /* 太めのライン */
            font-size: 0.8rem;
        }

        th {
            background: var(--slate-100);
            font-weight: 700;
            color: var(--slate-600);
            border-bottom: 2px solid var(--slate-300);
            font-size: 0.75rem;
            white-space: nowrap;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        .date-cell {
            font-weight: 700;
            white-space: nowrap;
        }

        .date-main {
            font-size: 0.95rem;
        }

        .date-sub {
            font-size: 0.7rem;
            color: var(--slate-400);
            margin-top: 2px;
        }

        .type-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 700;
            color: #fff;
        }

        .name-cell {
            font-weight: 700;
        }

        .flag {
            display: inline-block;
            padding: 4px 8px;
            /* パディング増やす */
            border-radius: 6px;
            font-size: 0.9rem;
            /* フォント大きく */
            font-weight: 700;
            margin-right: 2px;
            line-height: 1;
        }

        .flag-pickup {
            background: #dbeafe;
            color: #1e40af;
        }

        .flag-dropoff {
            background: #fef3c7;
            color: #92400e;
        }

        .flag-off {
            background: #f1f5f9;
            color: #9ca3af;
        }

        .needs-driver {
            background: #fef2f2 !important;
        }

        .driver-alert {
            display: inline-block;
            background: #fee2e2;
            color: #dc2626;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
        }

        .time-display {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.3rem;
            /* 時間を大きく */
            letter-spacing: -0.05em;
        }

        .check-cell {
            text-align: center;
            width: 1%;
            /* 限界まで縮める (gyuu-gyuu) */
            padding: 5px 2px !important;
            /* 隙間を詰める */
            white-space: nowrap;
            vertical-align: middle;
        }

        .check-stack {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
            justify-content: center;
        }

        .check-box {
            width: 32px;
            /* 少し大きく */
            height: 32px;
            border-radius: 6px;
            border: 2px solid var(--slate-400);
            background: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .action-btns {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: var(--primary);
            color: #fff;
        }

        .btn-fax {
            background: var(--success);
            color: #fff;
        }

        .btn-fax-done {
            background: #e5e7eb;
            color: #6b7280;
        }

        .btn-notice {
            background: #8b5cf6;
            color: #fff;
        }

        .btn-delete {
            background: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-handle {
            background: #f59e0b;
            color: #fff;
        }

        .arrow {
            font-weight: 700;
            color: var(--slate-400);
            line-height: 1.2;
            margin: 2px 0;
            text-align: center;
        }

        /* 文字サイズ調整（老眼対応） */
        .name-cell {
            font-weight: 700;
            font-size: 1.05rem;
            /* 名前を大きく */
        }

        .date-main {
            font-size: 1.0rem;
            /* 日付も見やすく */
        }

        /* 狭い列のヘッダー */
        th.check-cell {
            font-size: 0.7rem;
            padding: 5px 2px;
            vertical-align: middle;
        }

        /* 明日（黄色系で強調） */
        .tomorrow-row {
            background: #fffbeb !important;
            border-left: 6px solid var(--warning);
        }

        /* 当日（青系で強調） */
        .today-row {
            background: #e0f2fe !important;
            border-left: 6px solid var(--primary);
        }

        .notice-row {
            background: #fdf4ff !important;
            /* 薄い紫 */
            border-left: 6px solid #a855f7;
        }

        /* 未チェック（サイン不足） */
        .unchecked-row {
            background: #fff1f2 !important;
            /* 薄い赤 */
            border-left: 6px solid #f43f5e;
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--slate-400);
            background: #fff;
            border-radius: 12px;
        }

        .staff-info {
            font-size: 0.7rem;
            color: var(--slate-600);
            line-height: 1.4;
        }

        @media (max-width: 900px) {

            th,
            td {
                padding: 12px 4px;
                /* Padding reduced to prevent overflow */
                font-size: 0.9rem;
                white-space: normal;
            }

            /* Fix date cell overflow */
            .date-cell {
                max-width: 140px;
                overflow: hidden;
            }

            .date-main {
                display: block;
                /* Ensure separate lines */
            }

            .arrow {
                margin: 4px 0;
                /* Add margin to prevent overlap */
            }

            /* Fix time display font size on mobile if too large */
            .time-display {
                font-size: 1.1rem;
            }

            /* スマホで名前等のスペースを確保 */
            .name-cell {
                font-size: 1.1rem;
            }

            .hide-mobile {
                display: none;
            }

            .type-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }

        /* Header Navigation Styles */
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-header {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-schedule {
            background: #0ea5e9;
            /* Light Blue */
            color: white;
        }

        .btn-notice-list {
            background: #d97706;
            /* Notice Color */
            color: white;
        }

        /* Hamburger Menu */
        .menu-container {
            position: relative;
        }

        .menu-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--slate-600);
        }

        .menu-btn svg {
            width: 32px;
            height: 32px;
            stroke-width: 2.5;
        }

        .menu-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            overflow: hidden;
            z-index: 100;
            border: 1px solid var(--slate-100);
        }

        .menu-dropdown.show {
            display: block;
        }

        .menu-item {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--slate-800);
            font-weight: 700;
            border-bottom: 1px solid var(--slate-100);
            transition: background 0.2s;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover {
            background: #f8fafc;
        }

        .menu-item.settings {
            color: var(--slate-600);
        }
    </style>
</head>

<body>

    <header>
        <h1>送迎管理</h1>
        <div class="nav-actions">
            <!-- 直接表示するボタン -->
            <a href="notice_list.php" class="btn-header btn-notice-list">連絡事項</a>
            <a href="<?php echo h($scheduleUrl); ?>" target="_blank" class="btn-header btn-schedule">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <line x1="16" y1="2" x2="16" y2="6" />
                    <line x1="8" y1="2" x2="8" y2="6" />
                    <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                送迎表
            </a>

            <!-- ハンバーガーメニュー -->
            <div class="menu-container">
                <button class="menu-btn" onclick="document.querySelector('.menu-dropdown').classList.toggle('show')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <div class="menu-dropdown">
                    <a href="hospitalization.php" class="menu-item">入院一覧</a>
                    <a href="archive.php" class="menu-item">過去一覧</a>
                    <a href="settings.php" class="menu-item settings">⚙ 設定</a>
                </div>
            </div>
        </div>
    </header>

    <!-- メニュー外クリックで閉じる処理 -->
    <script>
        document.addEventListener('click', function (e) {
            const container = document.querySelector('.menu-container');
            if (!container.contains(e.target)) {
                document.querySelector('.menu-dropdown').classList.remove('show');
            }
        });
    </script>

    <div class="container">

        <a href="edit.php" class="add-btn">
            <svg viewBox="0 0 24 24">
                <path d="M12 5v14" />
                <path d="M5 12h14" />
            </svg>
            新規入力
        </a>

        <?php if (empty($records)): ?>
            <div class="empty">今日以降の予定はありません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>対象日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th>送迎</th>
                        <th>迎え時間</th>
                        <th class="hide-mobile">備考／連絡内容</th>
                        <th class="hide-mobile">担当</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $isToday = ($row['target_date'] === $today);
                        $isNotice = ($row['event_type'] === '連絡事項');

                        $isTomorrow = ($row['target_date'] === date('Y-m-d', strtotime('+1 day')));

                        $needsPickup = $row['needs_pickup'] ?? 1;
                        $needsDropoff = $row['needs_dropoff'] ?? 1;
                        $chkDrv1 = $row['chk_drv1'] ?? 0;
                        $chkDrv2 = $row['chk_drv2'] ?? 0;
                        $isUnchecked = ($chkDrv1 == 0 || $chkDrv2 == 0);

                        // 迎えが必要(連絡事項以外)かつ時間未入力
                        $needsDriverInput = (!$isNotice && $needsPickup && empty($row['pickup_time']));

                        $rowClass = '';
                        if ($isNotice) {
                            $rowClass = 'notice-row';
                        } elseif ($isUnchecked) {
                            $rowClass = 'unchecked-row';
                        } elseif ($needsDriverInput) {
                            $rowClass = 'needs-driver';
                        } elseif ($isToday) {
                            $rowClass = 'today-row';
                        } elseif ($isTomorrow) {
                            $rowClass = 'tomorrow-row';
                        }

                        $origDateStr = $row['orig_date'] ? date('n/j', strtotime($row['orig_date'])) : '';
                        $targetDateStr = $row['target_date'] ? date('n/j', strtotime($row['target_date'])) . '(' . h($row['target_weekday']) . ')' : '-';

                        $badgeColor = '#3b82f6';
                        if (in_array($row['event_type'], ['入院', '永眠']))
                            $badgeColor = '#ef4444';
                        if ($row['event_type'] === '臨時')
                            $badgeColor = '#f97316';
                        if ($row['event_type'] === '退院')
                            $badgeColor = '#22c55e';
                        if ($row['event_type'] === '連絡事項')
                            $badgeColor = '#d97706';

                        $chkDrv1 = $row['chk_drv1'] ?? 0;
                        $chkDrv2 = $row['chk_drv2'] ?? 0;
                        $createdBy = $row['created_by'] ?? '';
                        $technician = $row['technician'] ?? '';
                        $officeStaff = $row['office_staff'] ?? '';
                        $pickupTime = $row['pickup_time'] ?? '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td class="date-cell">
                                <?php
                                $origWd = $row['orig_weekday'] ? '(' . h($row['orig_weekday']) . ')' : '';
                                $origD = $row['orig_date'] ? date('n/j', strtotime($row['orig_date'])) : '';
                                $origSch = h($row['orig_schedule'] ?? '');

                                $targetWd = $row['target_weekday'] ? '(' . h($row['target_weekday']) . ')' : '';
                                $targetD = $row['target_date'] ? date('n/j', strtotime($row['target_date'])) : '';

                                // スケジュール決定ロジック
                                // 変更イベントの場合、target側はnew_scheduleを使う。
                                // 通常イベントの場合、new_scheduleがあればそれ、なければorig_scheduleを使う。
                                $targetSchVal = $row['new_schedule'] ? $row['new_schedule'] : ($row['orig_schedule'] ?? '');
                                $targetSch = h($targetSchVal);

                                if ($row['event_type'] === '変更'):
                                    // 変更前の表示: 日付があれば表示
                                    if ($origD) {
                                        echo "<div class='date-main'>{$origD}{$origWd}{$origSch}</div>";
                                    } else {
                                        // 日付変更なしでスケジュールのみ変更等の場合でもorigを表示すべきか？
                                        // User request: "1/1(日)AM -> 1/2(月)PM".
                                        // 日付がない場合(スケジュールのみ変更)は日付はtargetと同じだが...
                                        // とりあえず元データがあれば表示
                                        echo "<div class='date-main'>{$origSch}</div>";
                                    }
                                    echo "<div class='arrow'>↓</div>";
                                    echo "<div class='date-main'>{$targetD}{$targetWd}{$targetSch}</div>";
                                else:
                                    echo "<div class='date-main'>{$targetD}{$targetWd}{$targetSch}</div>";
                                endif;
                                ?>
                            </td>
                            <td><span class="type-badge"
                                    style="background:<?php echo $badgeColor; ?>;"><?php echo h($row['event_type']); ?></span>
                            </td>
                            <td class="name-cell"><?php echo h($row['p_name']); ?></td>
                            <td>
                                <?php if (!$isNotice): ?>
                                    <span class="flag <?php echo $needsPickup ? 'flag-pickup' : 'flag-off'; ?>">迎</span>
                                    <span class="flag <?php echo $needsDropoff ? 'flag-dropoff' : 'flag-off'; ?>">送</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isNotice): ?>
                                    -
                                <?php elseif ($pickupTime): ?>
                                    <span class="time-display"><?php echo h($pickupTime); ?></span>
                                <?php elseif (!$needsPickup): ?>
                                    <span style="font-size:0.8rem; color:#9ca3af;">迎えなし</span>
                                <?php else: ?>
                                    <span class="driver-alert" style="font-size:0.8rem;">要入力</span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-mobile" style="font-size:0.75rem; color:var(--slate-600); max-width:100px;">
                                <?php echo h($row['reason'] ?? ''); ?>
                            </td>
                            <td class="hide-mobile staff-info">
                                <?php if ($createdBy): ?>入力:<?php echo h($createdBy); ?><br><?php endif; ?>
                                <?php if ($technician): ?>技士:<?php echo h($technician); ?><br><?php endif; ?>
                                <?php if ($officeStaff): ?>医事:<?php echo h($officeStaff); ?><?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($isNotice): ?>
                                        <!-- 自動移動のためボタンなし -->
                                        <span style="font-size:0.75rem; color:var(--slate-400);">サイン後移動</span>
                                    <?php else: ?>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">編集</a>
                                        <?php if (in_array($row['event_type'], ['入院', '退院', '永眠']) || ($row['pharmacy_req'] ?? '') === '必要'): ?>
                                            <a href="fax.php?id=<?php echo $row['id']; ?>" target="_blank"
                                                class="btn <?php echo ($row['fax_sent'] ?? 0) ? 'btn-fax-done' : 'btn-fax'; ?>">FAX</a>
                                        <?php endif; ?>
                                        <?php if ($pickupTime): ?>
                                            <a href="notice.php?id=<?php echo $row['id']; ?>" target="_blank"
                                                class="btn btn-notice">案内</a>
                                        <?php endif; ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('削除しますか？');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-delete">×</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <script>
        function toggleCheck(el, id, field, value) {
            var form = new FormData();
            form.append('action', 'check');
            form.append('id', id);
            form.append('field', field);
            form.append('value', value);
            fetch('', { method: 'POST', body: form }).then(function () {
                el.classList.toggle('checked');
            });
        }
    </script>

</body>

</html>