<?php
/**
 * 透析・送迎管理システム - 新規入力/編集ページ
 * v7: 医事担当者追加、BX-P変更・検査変更オプション追加
 */

$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
}

// マイグレーション
$columns = [
    "orig_date TEXT",
    "orig_weekday TEXT",
    "needs_pickup INTEGER DEFAULT 1",
    "needs_dropoff INTEGER DEFAULT 1",
    "chk_office INTEGER DEFAULT 0",
    "chk_office_name TEXT",
    "created_by TEXT",
    "technician TEXT",
    "office_staff TEXT",
    "bxp_change TEXT DEFAULT 'なし'",
    "exam_change TEXT DEFAULT 'なし'",
    "is_handled INTEGER DEFAULT 0",
    "transport_method TEXT DEFAULT '送迎'",
    "self_transport_type TEXT",
    "parking_number TEXT",
    "self_needs_dropoff INTEGER DEFAULT 1",
    "taxi_needs_dropoff INTEGER DEFAULT 0",
    "event_needs_dropoff INTEGER DEFAULT 0",
    "exam_date TEXT"
];
foreach ($columns as $col) {
    try {
        $pdo->exec("ALTER TABLE records ADD COLUMN $col");
    } catch (Exception $e) {
    }
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getWeekday($dateStr)
{
    if (!$dateStr)
        return '';
    $d = new DateTime($dateStr);
    $w = ['日', '月', '火', '水', '木', '金', '土'];
    return $w[$d->format('w')];
}

$id = $_GET['id'] ?? 0;
$isNew = empty($id);
$pageTitle = $isNew ? '新規入力' : '予定編集';

// デフォルト値
$row = [
    'p_name' => '',
    'event_type' => '',
    'orig_date' => '',
    'orig_weekday' => '',
    'orig_schedule' => '',
    'target_date' => '',
    'target_weekday' => '',
    'new_schedule' => '',
    'reason' => '',
    'needs_pickup' => 1,
    'needs_dropoff' => 1,
    'pickup_time' => '',
    'bed_change' => 'なし',
    'bed_no' => '',
    'bxp_change' => 'なし',
    'exam_change' => 'なし',
    'pharmacy_req' => '不要',
    'pharmacy_date' => '',
    'created_by' => '',
    'technician' => '',
    'office_staff' => '',
    'transport_method' => '送迎',
    'self_transport_type' => '',
    'parking_number' => '',
    'self_needs_dropoff' => 1,
    'taxi_needs_dropoff' => 0,
    'event_needs_dropoff' => 0,
    'exam_date' => ''
];

if (!$isNew) {
    $stmt = $pdo->prepare("SELECT * FROM records WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if (!$fetched)
        die('Data not found');
    $row = array_merge($row, $fetched);
}

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 削除処理（論理削除）
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare("UPDATE records SET is_deleted = 1 WHERE id = ?")->execute([$_POST['id']]);
        header('Location: trash.php');
        exit;
    }

    $origWd = getWeekday($_POST['orig_date'] ?? '');
    $targetWd = getWeekday($_POST['target_date'] ?? '');

    if ($isNew) {
        $stmt = $pdo->prepare("INSERT INTO records 
            (p_name, event_type, orig_date, orig_weekday, orig_schedule, target_date, target_weekday, new_schedule, reason, needs_pickup, needs_dropoff, pickup_time, bed_change, bed_no, bxp_change, exam_change, pharmacy_req, pharmacy_date, created_by, technician, office_staff, chk_drv1, chk_drv2, transport_method, self_transport_type, parking_number, self_needs_dropoff, taxi_needs_dropoff, event_needs_dropoff, exam_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['p_name'],
            $_POST['event_type'],
            $_POST['orig_date'] ?? '',
            $origWd,
            $_POST['orig_schedule'] ?? '',
            $_POST['target_date'] ?? '',
            $targetWd,
            $_POST['new_schedule'] ?? '',
            $_POST['reason'] ?? '',
            isset($_POST['needs_pickup']) ? 1 : 0,
            isset($_POST['needs_dropoff']) ? 1 : 0,
            $_POST['pickup_time'] ?? '',
            $_POST['bed_change'] ?? 'なし',
            $_POST['bed_no'] ?? '',
            $_POST['bxp_change'] ?? 'なし',
            $_POST['exam_change'] ?? 'なし',
            $_POST['pharmacy_req'] ?? '不要',
            $_POST['pharmacy_date'] ?? '',
            $_POST['created_by'] ?? '',
            $_POST['technician'] ?? '',
            $_POST['office_staff'] ?? '',
            isset($_POST['chk_drv1']) ? 1 : 0,
            isset($_POST['chk_drv2']) ? 1 : 0,
            $_POST['transport_method'] ?? '送迎',
            $_POST['self_transport_type'] ?? '',
            $_POST['parking_number'] ?? '',
            isset($_POST['self_needs_dropoff']) ? 1 : 0,
            isset($_POST['taxi_needs_dropoff']) ? 1 : 0,
            isset($_POST['event_needs_dropoff']) ? 1 : 0,
            $_POST['exam_date'] ?? ''
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE records SET 
            p_name=?, event_type=?, orig_date=?, orig_weekday=?, orig_schedule=?, 
            target_date=?, target_weekday=?, new_schedule=?, reason=?, 
            needs_pickup=?, needs_dropoff=?, pickup_time=?,
            bed_change=?, bed_no=?, bxp_change=?, exam_change=?,
            pharmacy_req=?, pharmacy_date=?,
            created_by=?, technician=?, office_staff=?,
            chk_drv1=?, chk_drv2=?,
            transport_method=?, self_transport_type=?, parking_number=?, self_needs_dropoff=?,
            taxi_needs_dropoff=?, event_needs_dropoff=?, exam_date=?
            WHERE id=?");
        $stmt->execute([
            $_POST['p_name'],
            $_POST['event_type'],
            $_POST['orig_date'] ?? '',
            $origWd,
            $_POST['orig_schedule'] ?? '',
            $_POST['target_date'] ?? '',
            $targetWd,
            $_POST['new_schedule'] ?? '',
            $_POST['reason'] ?? '',
            isset($_POST['needs_pickup']) ? 1 : 0,
            isset($_POST['needs_dropoff']) ? 1 : 0,
            $_POST['pickup_time'] ?? '',
            $_POST['bed_change'] ?? 'なし',
            $_POST['bed_no'] ?? '',
            $_POST['bxp_change'] ?? 'なし',
            $_POST['exam_change'] ?? 'なし',
            $_POST['pharmacy_req'] ?? '不要',
            $_POST['pharmacy_date'] ?? '',
            $_POST['created_by'] ?? '',
            $_POST['technician'] ?? '',
            $_POST['office_staff'] ?? '',
            isset($_POST['chk_drv1']) ? 1 : 0,
            isset($_POST['chk_drv2']) ? 1 : 0,
            $_POST['transport_method'] ?? '送迎',
            $_POST['self_transport_type'] ?? '',
            $_POST['parking_number'] ?? '',
            isset($_POST['self_needs_dropoff']) ? 1 : 0,
            isset($_POST['taxi_needs_dropoff']) ? 1 : 0,
            isset($_POST['event_needs_dropoff']) ? 1 : 0,
            $_POST['exam_date'] ?? '',
            $_POST['id']
        ]);
    }
    header('Location: index.php');
    exit;
}

$needsPickup = $row['needs_pickup'] ?? 1;
$needsDropoff = $row['needs_dropoff'] ?? 1;
$pickupTime = $row['pickup_time'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --success: #22c55e;
            --warning: #f97316;
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
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        header h1 {
            font-size: 1.1rem;
            margin: 0;
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            font-weight: 700;
            color: var(--slate-600);
            margin: 14px 0 5px;
            font-size: 0.85rem;
        }

        label:first-child {
            margin-top: 0;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--slate-400);
            border-radius: 10px;
            font-size: 1rem;
        }

        textarea {
            height: 70px;
            resize: vertical;
        }

        select {
            background: #fff;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            border-radius: 12px;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            min-height: 48px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
        }

        .btn-ghost {
            background: transparent;
            color: var(--slate-600);
            border: 1px solid var(--slate-400);
        }

        .btn-block {
            width: 100%;
        }

        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .type-btns {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
        }

        .type-btn {
            padding: 12px 4px;
            text-align: center;
            border-radius: 10px;
            background: #fff;
            border: 2px solid var(--slate-400);
            font-weight: 700;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .type-btn.active {
            border-color: var(--primary);
            background: #eff6ff;
            color: var(--primary);
        }

        /* 種別ごとの色分け */
        .type-btn.active[data-type="変更"] {
            border-color: #3b82f6;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .type-btn.active[data-type="臨時"] {
            border-color: #f97316;
            background: #ffedd5;
            color: #c2410c;
        }
        .type-btn.active[data-type="入院"] {
            border-color: #ef4444;
            background: #fee2e2;
            color: #b91c1c;
        }
        .type-btn.active[data-type="退院"] {
            border-color: #22c55e;
            background: #dcfce7;
            color: #15803d;
        }
        .type-btn.active[data-type="連絡事項"] {
            border-color: #d97706;
            background: #fef3c7;
            color: #92400e;
        }
        .type-btn.active[data-type="永眠"] {
            border-color: #6b7280;
            background: #f3f4f6;
            color: #374151;
        }

        /* カード背景の種別色（全体に色が分かるグラデーション） */
        .card.type-変更 { background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 50%, #fff 100%); }
        .card.type-臨時 { background: linear-gradient(135deg, #fed7aa 0%, #fff7ed 50%, #fff 100%); }
        .card.type-入院 { background: linear-gradient(135deg, #fecaca 0%, #fef2f2 50%, #fff 100%); }
        .card.type-退院 { background: linear-gradient(135deg, #bbf7d0 0%, #f0fdf4 50%, #fff 100%); }
        .card.type-連絡事項 { background: linear-gradient(135deg, #fde68a 0%, #fffbeb 50%, #fff 100%); }
        .card.type-永眠 { background: linear-gradient(135deg, #e5e7eb 0%, #f9fafb 50%, #fff 100%); }

        .date-row {
            display: grid;
            grid-template-columns: 1fr 30px 1fr;
            gap: 8px;
            align-items: end;
        }

        .date-row .arrow {
            text-align: center;
            font-size: 1.2rem;
            color: var(--slate-400);
            padding-bottom: 14px;
        }

        .ampm-btns {
            display: flex;
            gap: 8px;
            margin-top: 5px;
        }

        .ampm-btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            background: #fff;
            border: 2px solid var(--slate-400);
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
        }

        .ampm-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: #fff;
        }

        .checkbox-row {
            display: flex;
            gap: 10px;
        }

        .checkbox-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border-radius: 10px;
            background: var(--slate-100);
            cursor: pointer;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .checkbox-item input {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        .checkbox-item.checked {
            background: #dcfce7;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .hidden {
            display: none !important;
        }

        .section-title {
            font-size: 0.8rem;
            color: var(--slate-400);
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid var(--slate-100);
        }

        .pickup-time-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 14px;
            margin-top: 14px;
        }

        .pickup-time-box label {
            margin-top: 0;
            color: #92400e;
        }

        .pickup-time-box input[type="tel"] {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .pickup-time-hint {
            font-size: 0.75rem;
            color: #92400e;
            margin-top: 5px;
        }

        .staff-section {
            background: #eff6ff;
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 18px;
        }

        .staff-section label {
            color: #1e40af;
            margin-top: 0;
        }

        .staff-section .grid-3 label {
            margin-top: 10px;
        }

        .staff-section .grid-3 label:first-child {
            margin-top: 0;
        }

        @media (max-width: 600px) {
            .container {
                padding: 12px;
            }
            
            .card {
                padding: 16px;
            }

            /* Make date row more compact */
            .date-row {
                grid-template-columns: 1fr 20px 1fr; /* Reduce arrow space */
                gap: 4px; /* Reduce gap */
            }
            
            .date-row .arrow {
                font-size: 1rem;
                padding-bottom: 10px;
            }

            /* Compact inputs on mobile only in date-row */
            .date-row input[type="date"],
            .date-row input[type="time"] {
                padding: 10px 0px; /* Reduce horizontal padding significantly for date/time row */
                border-radius: 8px;
                text-align: center;
            }

            /* Compact AM/PM buttons */
            .ampm-btns {
                gap: 4px;
            }

            .pickup-time-box input[type="tel"] {
                width: auto !important;
                max-width: 100%;
                display: inline-block;
            }
            
            .ampm-btn {
                padding: 10px 2px;
                border-radius: 8px;
            }
        }
    </style>
</head>

<body>

    <header>
        <a href="index.php" class="back-link">← 戻る</a>
        <h1><?php echo $pageTitle; ?></h1>
        <div style="width:50px; text-align:right;">
            <?php if (!$isNew): ?>
                <form method="post" onsubmit="return confirm('削除してゴミ箱に移動しますか？');" style="margin:0;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <button type="submit" style="background:none; border:none; color:#dc2626; cursor:pointer; padding:4px;">
                        <!-- ゴミ箱アイコン -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <form method="post" class="card">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <?php endif; ?>

            <!-- 担当者入力 -->
            <div class="staff-section">
                <div class="grid-3">
                    <div>
                        <label>👤 入力者</label>
                        <input type="text" name="created_by" value="<?php echo h($row['created_by']); ?>"
                            placeholder="名前">
                    </div>
                    <div>
                        <label>🔧 技士</label>
                        <input type="text" name="technician" value="<?php echo h($row['technician']); ?>"
                            placeholder="名前">
                    </div>
                    <div>
                        <label>📋 医事</label>
                        <input type="text" name="office_staff" value="<?php echo h($row['office_staff'] ?? ''); ?>"
                            placeholder="名前">
                    </div>
                </div>
                
                <div style="margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                    <label style="color:#1e40af;">運転手確認</label>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <div class="checkbox-row" style="flex-grow:1;">
                            <label class="checkbox-item <?php echo ($row['chk_drv1'] ?? 0) ? 'checked' : ''; ?>">
                                <input type="checkbox" name="chk_drv1" <?php echo ($row['chk_drv1'] ?? 0) ? 'checked' : ''; ?>
                                    onchange="this.parentNode.classList.toggle('checked', this.checked)"> 西
                            </label>
                            <label class="checkbox-item <?php echo ($row['chk_drv2'] ?? 0) ? 'checked' : ''; ?>">
                                <input type="checkbox" name="chk_drv2" <?php echo ($row['chk_drv2'] ?? 0) ? 'checked' : ''; ?>
                                    onchange="this.parentNode.classList.toggle('checked', this.checked)"> 佐藤
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-left:10px; padding:8px 16px; font-size:0.9rem; min-height:40px;">保存</button>
                    </div>
                </div>
            </div>

            <label>患者氏名</label>
            <input type="text" name="p_name" value="<?php echo h($row['p_name']); ?>" required placeholder="氏名を入力">

            <label>種別</label>
            <div class="type-btns">
                <?php foreach (['変更', '臨時', '入院', '退院', '連絡事項'] as $t): ?>
                    <div class="type-btn <?php echo $row['event_type'] === $t ? 'active' : ''; ?>"
                        data-type="<?php echo $t; ?>" onclick="setType(this)"><?php echo $t; ?></div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="event_type" id="event_type" value="<?php echo h($row['event_type']); ?>">

            <!-- 変更/臨時用 -->
            <div id="date_change" class="<?php echo in_array($row['event_type'], ['変更', '臨時']) ? '' : 'hidden'; ?>">
                <label>日程変更</label>
                <div class="date-row" id="date_row_date">
                    <div id="orig_date_wrapper">
                        <small style="color:var(--slate-400);">元の日付</small>
                        <input type="date" name="orig_date" id="orig_date" value="<?php echo h($row['orig_date']); ?>">
                    </div>
                    <div class="arrow" id="date_arrow">→</div>
                    <div>
                        <small style="color:var(--slate-400);">新しい日付</small>
                        <input type="date" name="target_date" id="target_date"
                            value="<?php echo h($row['target_date']); ?>">
                    </div>
                </div>

                <label>時間帯</label>
                <div class="date-row" id="date_row_time">
                    <div id="orig_time_wrapper">
                        <div class="ampm-btns">
                            <div class="ampm-btn <?php echo $row['orig_schedule'] === 'AM' ? 'active' : ''; ?>"
                                onclick="setAmPm(this, 'orig')">AM</div>
                            <div class="ampm-btn <?php echo $row['orig_schedule'] === 'PM' ? 'active' : ''; ?>"
                                onclick="setAmPm(this, 'orig')">PM</div>
                        </div>
                        <input type="hidden" name="orig_schedule" id="orig_schedule"
                            value="<?php echo h($row['orig_schedule']); ?>">
                    </div>
                    <div class="arrow" id="time_arrow">→</div>
                    <div>
                        <div class="ampm-btns">
                            <div class="ampm-btn <?php echo $row['new_schedule'] === 'AM' ? 'active' : ''; ?>"
                                onclick="setAmPm(this, 'new')">AM</div>
                            <div class="ampm-btn <?php echo $row['new_schedule'] === 'PM' ? 'active' : ''; ?>"
                                onclick="setAmPm(this, 'new')">PM</div>
                        </div>
                        <input type="hidden" name="new_schedule" id="new_schedule"
                            value="<?php echo h($row['new_schedule']); ?>">
                    </div>
                </div>

                <div id="btn_tomorrow" class="<?php echo $row['event_type'] === '臨時' ? '' : 'hidden'; ?>"
                    style="margin-top:10px;">
                    <button type="button" class="btn btn-block" onclick="setTomorrow()"
                        style="background:linear-gradient(135deg,#f97316,#ea580c); color:#fff;">明日に設定</button>
                </div>
            </div>

            <!-- 入院等・連絡事項用 -->
            <div id="date_single"
                class="<?php echo in_array($row['event_type'], ['入院', '退院', '連絡事項']) ? '' : 'hidden'; ?>">
                <label id="single_label"><?php
                if ($row['event_type'] === '入院')
                    echo '入院日';
                elseif ($row['event_type'] === '退院')
                    echo '退院日';
                else
                    echo '対象日';
                ?></label>
                <input type="date" name="target_date_single" id="target_date_single"
                    value="<?php echo h($row['target_date']); ?>">
            </div>

            <!-- 来院方法 -->
            <div id="transport_method_section"
                class="<?php echo in_array($row['event_type'], ['変更', '臨時', '退院', '']) ? '' : 'hidden'; ?>">
                <label>来院方法</label>
                <div class="ampm-btns" style="margin-bottom: 10px;">
                    <?php $tm = $row['transport_method'] ?? '送迎'; ?>
                    <div class="ampm-btn <?php echo $tm === '送迎' ? 'active' : ''; ?>"
                        onclick="setTransportMethod(this, '送迎')">送迎</div>
                    <div class="ampm-btn <?php echo $tm === '自走' ? 'active' : ''; ?>"
                        onclick="setTransportMethod(this, '自走')">自走</div>
                    <div class="ampm-btn <?php echo $tm === 'タクシー' ? 'active' : ''; ?>"
                        onclick="setTransportMethod(this, 'タクシー')">タクシー</div>
                </div>
                <input type="hidden" name="transport_method" id="transport_method"
                    value="<?php echo h($row['transport_method'] ?? '送迎'); ?>">

                <!-- 自走時の詳細選択 -->
                <div id="self_transport_section" class="<?php echo $tm === '自走' ? '' : 'hidden'; ?>" style="margin-top: 10px;">
                    <label>自走の詳細</label>
                    <div class="ampm-btns">
                        <?php $stt = $row['self_transport_type'] ?? ''; ?>
                        <div class="ampm-btn <?php echo $stt === '自動車' ? 'active' : ''; ?>"
                            onclick="setSelfTransportType(this, '自動車')">自動車</div>
                        <div class="ampm-btn <?php echo $stt === '徒歩' ? 'active' : ''; ?>"
                            onclick="setSelfTransportType(this, '徒歩')">徒歩</div>
                        <div class="ampm-btn <?php echo $stt === '自転車' ? 'active' : ''; ?>"
                            onclick="setSelfTransportType(this, '自転車')">自転車</div>
                        <div class="ampm-btn <?php echo $stt === '家族送迎' ? 'active' : ''; ?>"
                            onclick="setSelfTransportType(this, '家族送迎')">家族送迎</div>
                    </div>
                    <input type="hidden" name="self_transport_type" id="self_transport_type"
                        value="<?php echo h($row['self_transport_type'] ?? ''); ?>">

                    <!-- 駐車場番号（自動車/家族送迎時のみ） -->
                    <div id="parking_section" class="<?php echo in_array($stt, ['自動車', '家族送迎']) ? '' : 'hidden'; ?>" style="margin-top: 10px;">
                        <label>🅿️ 駐車場番号</label>
                        <input type="text" name="parking_number" id="parking_number" 
                            value="<?php echo h($row['parking_number'] ?? ''); ?>" placeholder="例: A-12">
                    </div>

                    <!-- 送りチェック（徒歩/家族送迎時のみ - 自動車は除外） -->
                    <?php $selfNeedsDropoff = $row['self_needs_dropoff'] ?? 1; ?>
                    <div id="self_dropoff_section" class="<?php echo in_array($stt, ['徒歩', '家族送迎']) ? '' : 'hidden'; ?>" style="margin-top: 10px;">
                        <div class="checkbox-row">
                            <label class="checkbox-item <?php echo $selfNeedsDropoff ? 'checked' : ''; ?>">
                                <input type="checkbox" name="self_needs_dropoff" <?php echo $selfNeedsDropoff ? 'checked' : ''; ?>
                                    onchange="this.parentNode.classList.toggle('checked', this.checked)">
                                送りあり
                            </label>
                        </div>
                    </div>
                </div>

                <!-- タクシー送りあり（タクシー選択時のみ・普通のチェック） -->
                <?php $taxiNeedsDropoff = $row['taxi_needs_dropoff'] ?? 0; ?>
                <div id="taxi_dropoff_section" class="<?php echo $tm === 'タクシー' ? '' : 'hidden'; ?>" style="margin-top: 10px;">
                    <div class="checkbox-row">
                        <label class="checkbox-item <?php echo $taxiNeedsDropoff ? 'checked' : ''; ?>">
                            <input type="checkbox" name="taxi_needs_dropoff" <?php echo $taxiNeedsDropoff ? 'checked' : ''; ?>
                                onchange="this.parentNode.classList.toggle('checked', this.checked)">
                            送りあり
                        </label>
                    </div>
                </div>
            </div>

            <!-- 入院時の送り選択（送りあり or 送りあり（タクシー）） -->
            <?php 
            $eventNeedsDropoff = $row['event_needs_dropoff'] ?? 0;
            $taxiEventDropoff = $row['taxi_needs_dropoff'] ?? 0;
            ?>
            <div id="hospital_dropoff_section" class="<?php echo $row['event_type'] === '入院' ? '' : 'hidden'; ?>" style="margin-top: 10px;">
                <label>送り方法</label>
                <div class="checkbox-row">
                    <label class="checkbox-item <?php echo $eventNeedsDropoff ? 'checked' : ''; ?>">
                        <input type="checkbox" name="event_needs_dropoff" id="event_needs_dropoff_cb" <?php echo $eventNeedsDropoff ? 'checked' : ''; ?>
                            onchange="this.parentNode.classList.toggle('checked', this.checked)">
                        送りあり
                    </label>
                    <label class="checkbox-item <?php echo $taxiEventDropoff ? 'checked' : ''; ?>">
                        <input type="checkbox" name="taxi_needs_dropoff" id="taxi_event_dropoff_cb" <?php echo $taxiEventDropoff ? 'checked' : ''; ?>
                            onchange="this.parentNode.classList.toggle('checked', this.checked)">
                        送りあり（タクシー）
                    </label>
                </div>
            </div>

            <!-- 送り迎えフラグ（送迎選択時のみ） -->
            <?php $showTransportFlags = in_array($row['event_type'], ['変更', '臨時', '']) && ($tm === '送迎'); ?>
            <div id="transport_flags"
                class="<?php echo $showTransportFlags ? '' : 'hidden'; ?>">
                <label>送迎の変更</label>
                <div class="checkbox-row">
                    <label class="checkbox-item <?php echo $needsPickup ? 'checked' : ''; ?>">
                        <input type="checkbox" name="needs_pickup" <?php echo $needsPickup ? 'checked' : ''; ?>
                            onchange="togglePickupTime(); this.parentNode.classList.toggle('checked', this.checked)">
                        迎え変更あり
                    </label>
                    <label class="checkbox-item <?php echo $needsDropoff ? 'checked' : ''; ?>">
                        <input type="checkbox" name="needs_dropoff" <?php echo $needsDropoff ? 'checked' : ''; ?>
                            onchange="this.parentNode.classList.toggle('checked', this.checked)"> 送り変更あり
                    </label>
                </div>

            <div class="pickup-time-box">
                    <label>🚗 迎え時間</label>
                    <input type="hidden" name="pickup_time" id="pickup_time" value="<?php echo h($pickupTime); ?>">
                    <div class="time-input-group">
                        <input type="tel" id="pickup_hour" class="time-part" maxlength="2" inputmode="numeric">
                        <span class="time-sep">:</span>
                        <input type="tel" id="pickup_minute" class="time-part" maxlength="2" inputmode="numeric">
                    </div>
                    <div class="pickup-time-hint">※ 数字のみ入力</div>
            </div>
                    
                    <style>
                        .time-input-group {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 5px;
                        }
                        .time-part {
                            width: 60px !important;
                            text-align: center;
                            font-size: 1.4rem !important;
                            padding: 10px 5px !important;
                            border-radius: 8px;
                            border: 1px solid #94a3b8;
                        }
                        .time-sep {
                            font-weight: 700;
                            font-size: 1.4rem;
                            color: #92400e;
                        }
                    </style>
                    <script>
                        (function() {
                            var pt = document.getElementById('pickup_time');
                            var ph = document.getElementById('pickup_hour');
                            var pm = document.getElementById('pickup_minute');

                            // 初期値セット
                            if (pt.value && pt.value.includes(':')) {
                                var parts = pt.value.split(':');
                                ph.value = parts[0];
                                pm.value = parts[1];
                            }

                            function updateHidden() {
                                var h = ph.value.padStart(2, '0');
                                var m = pm.value.padStart(2, '0');
                                if (ph.value === '' && pm.value === '') {
                                    pt.value = '';
                                } else {
                                    pt.value = h + ':' + m;
                                }
                            }

                            ph.addEventListener('input', function() {
                                if (this.value.length >= 2) pm.focus();
                                updateHidden();
                            });
                            pm.addEventListener('input', updateHidden);
                            
                            // フォーカスが外れた時に0埋めする
                            ph.addEventListener('blur', function() {
                                if(this.value.length === 1) this.value = this.value.padStart(2, '0');
                                updateHidden();
                            });
                            pm.addEventListener('blur', function() {
                                if(this.value.length === 1) this.value = this.value.padStart(2, '0');
                                updateHidden();
                            });
                        })();
                    </script>
            </div>

            <label>理由・備考</label>
            <textarea name="reason" id="reason_textarea" placeholder="任意"><?php echo h($row['reason']); ?></textarea>


            <div class="section-title">その他オプション</div>

            <div class="grid-2">
                <div>
                    <label>配薬連絡</label>
                    <select name="pharmacy_req" id="pharmacy_req" onchange="toggleExtra()">
                        <option value="不要" <?php if (($row['pharmacy_req'] ?? '') === '不要')
                            echo 'selected'; ?>>不要</option>
                        <option value="必要" <?php if (($row['pharmacy_req'] ?? '') === '必要')
                            echo 'selected'; ?>>必要</option>
                    </select>
                </div>
                <div>
                    <label>ベッド変更</label>
                    <select name="bed_change" id="bed_change" onchange="toggleExtra()">
                        <option value="なし" <?php if (($row['bed_change'] ?? '') === 'なし')
                            echo 'selected'; ?>>なし</option>
                        <option value="あり" <?php if (($row['bed_change'] ?? '') === 'あり')
                            echo 'selected'; ?>>あり</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>BX-P変更</label>
                    <select name="bxp_change">
                        <option value="なし" <?php if (($row['bxp_change'] ?? '') === 'なし')
                            echo 'selected'; ?>>なし</option>
                        <option value="あり" <?php if (($row['bxp_change'] ?? '') === 'あり')
                            echo 'selected'; ?>>あり</option>
                    </select>
                </div>
                <div>
                    <label>採血検査</label>
                    <select name="exam_change" id="exam_change" onchange="toggleExtra()">
                        <option value="なし" <?php if (($row['exam_change'] ?? '') === 'なし')
                            echo 'selected'; ?>>なし</option>
                        <option value="あり" <?php if (($row['exam_change'] ?? '') === 'あり')
                            echo 'selected'; ?>>あり</option>
                    </select>
                </div>
            </div>

            <div id="extra_pharmacy" class="<?php echo ($row['pharmacy_req'] ?? '') === '必要' ? '' : 'hidden'; ?>">
                <label>配薬変更日</label>
                <input type="date" name="pharmacy_date" id="pharmacy_date" value="<?php echo h($row['pharmacy_date']); ?>">
            </div>
            
            <div id="extra_exam" class="<?php echo ($row['exam_change'] ?? '') === 'あり' ? '' : 'hidden'; ?>">
                <label>採血検査日</label>
                <input type="date" name="exam_date" id="exam_date" value="<?php echo h($row['exam_date']); ?>">
            </div>

            <div id="extra_bed" class="<?php echo ($row['bed_change'] ?? '') === 'あり' ? '' : 'hidden'; ?>">
                <label>新ベッドNo</label>
                <input type="text" name="bed_no" value="<?php echo h($row['bed_no']); ?>">
            </div>

            <div class="btn-row">
                <a href="index.php" class="btn btn-ghost">キャンセル</a>
                <button type="submit" class="btn <?php echo $isNew ? 'btn-success' : 'btn-primary'; ?>" style="flex:1;">
                    <?php echo $isNew ? '登録する' : '保存する'; ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        function setType(el) {
            var btns = document.querySelectorAll('.type-btn');
            for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
            el.classList.add('active');
            var t = el.getAttribute('data-type');
            document.getElementById('event_type').value = t;

            var isChange = (t === '変更');
            var isTemp = (t === '臨時');
            var isEditor = (isChange || isTemp);
            var isHospital = (t === '入院');
            var isDischarge = (t === '退院');

            document.getElementById('date_change').className = isEditor ? '' : 'hidden';
            document.getElementById('date_single').className = isEditor ? 'hidden' : '';
            document.getElementById('btn_tomorrow').className = isTemp ? '' : 'hidden';
            
            // 来院方法セクションの表示制御（変更/臨時/退院で表示）
            var showTransportMethod = (isEditor || isDischarge);
            document.getElementById('transport_method_section').className = showTransportMethod ? '' : 'hidden';
            
            // 送迎フラグは来院方法が送迎の場合のみ表示
            var tm = document.getElementById('transport_method').value;
            document.getElementById('transport_flags').className = (isEditor && tm === '送迎') ? '' : 'hidden';

            // 入院時の送り選択セクション表示
            document.getElementById('hospital_dropoff_section').className = isHospital ? '' : 'hidden';

            if (t === '入院') document.getElementById('single_label').textContent = '入院日';
            else if (t === '退院') document.getElementById('single_label').textContent = '退院日';
            else if (t === '永眠') document.getElementById('single_label').textContent = '永眠日';
            else if (t === '連絡事項') document.getElementById('single_label').textContent = '表示開始日';

            var origDisp = isTemp ? 'none' : 'block';
            var gridStyle = isTemp ? '1fr' : '1fr 30px 1fr';

            document.getElementById('orig_date_wrapper').style.display = origDisp;
            document.getElementById('date_arrow').style.display = origDisp;
            document.getElementById('date_row_date').style.gridTemplateColumns = gridStyle;

            document.getElementById('orig_time_wrapper').style.display = origDisp;
            document.getElementById('time_arrow').style.display = origDisp;
            document.getElementById('date_row_time').style.gridTemplateColumns = gridStyle;

            // 臨時透析の場合のみ、理由欄に初期値を設定（空の場合のみ）
            var reasonTextarea = document.getElementById('reason_textarea');
            if (reasonTextarea) {
                if (isTemp && reasonTextarea.value === '') {
                    reasonTextarea.value = '今週は透析４回です。';
                }
                // 臨時以外に変更した場合、初期値が入っていたらクリア
                if (!isTemp && reasonTextarea.value === '今週は透析４回です。') {
                    reasonTextarea.value = '';
                }
            }

            // カード背景色を種別に応じて変更
            updateCardBackground(t);
        }

        function updateCardBackground(type) {
            var card = document.querySelector('.card');
            if (!card) return;
            // 既存のtype-クラスをすべて削除
            card.className = card.className.replace(/type-\S+/g, '').trim();
            // 新しいクラスを追加
            if (type) {
                card.classList.add('type-' + type);
            }
        }

        function setAmPm(el, prefix) {
            var btns = el.parentNode.querySelectorAll('.ampm-btn');
            for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
            el.classList.add('active');
            document.getElementById(prefix + '_schedule').value = el.textContent;
        }

        function setTomorrow() {
            var d = new Date();
            d.setDate(d.getDate() + 1);
            document.getElementById('target_date').value = d.toISOString().split('T')[0];
        }

        function toggleExtra() {
            // 配薬連絡
            var pharmacyReq = document.getElementById('pharmacy_req').value;
            var pharmacyDateInput = document.getElementById('pharmacy_date');
            document.getElementById('extra_pharmacy').className = (pharmacyReq === '必要') ? '' : 'hidden';
            
            // 配薬連絡がありの場合、新しい日付をデフォルトで表示（空の場合のみ）
            if (pharmacyReq === '必要' && pharmacyDateInput.value === '') {
                var targetDate = document.getElementById('target_date').value;
                if (targetDate) {
                    pharmacyDateInput.value = targetDate;
                }
            }

            // 採血検査
            var examChange = document.getElementById('exam_change').value;
            var examDateInput = document.getElementById('exam_date');
            document.getElementById('extra_exam').className = (examChange === 'あり') ? '' : 'hidden';

            // 採血検査がありの場合、新しい日付をデフォルトで表示（空の場合のみ）
            if (examChange === 'あり' && examDateInput.value === '') {
                var targetDate = document.getElementById('target_date').value;
                if (targetDate) {
                    examDateInput.value = targetDate;
                }
            }

            // ベッド変更
            document.getElementById('extra_bed').className = (document.getElementById('bed_change').value === 'あり') ? '' : 'hidden';
        }

        function togglePickupTime() {
            var cb = document.querySelector('input[name="needs_pickup"]');
            document.getElementById('pickup_time_section').className = cb.checked ? 'pickup-time-box' : 'pickup-time-box hidden';
        }

        // 来院方法の切り替え
        function setTransportMethod(el, method) {
            var btns = el.parentNode.querySelectorAll('.ampm-btn');
            for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
            el.classList.add('active');
            document.getElementById('transport_method').value = method;

            // 自走セクションの表示切替
            document.getElementById('self_transport_section').className = (method === '自走') ? '' : 'hidden';
            
            // 送迎フラグの表示切替（送迎の場合のみ表示）
            document.getElementById('transport_flags').className = (method === '送迎') ? '' : 'hidden';

            // タクシー送りセクションの表示切替
            document.getElementById('taxi_dropoff_section').className = (method === 'タクシー') ? '' : 'hidden';
            
            // 自走以外を選んだ場合、自走関連フィールドをリセット
            if (method !== '自走') {
                document.getElementById('self_transport_type').value = '';
                document.getElementById('parking_section').className = 'hidden';
                document.getElementById('self_dropoff_section').className = 'hidden';
                // 自走詳細ボタンのactiveをクリア
                var sttBtns = document.getElementById('self_transport_section').querySelectorAll('.ampm-btn');
                for (var i = 0; i < sttBtns.length; i++) sttBtns[i].classList.remove('active');
            }
        }

        // 自走詳細の切り替え
        function setSelfTransportType(el, type) {
            var btns = el.parentNode.querySelectorAll('.ampm-btn');
            for (var i = 0; i < btns.length; i++) btns[i].classList.remove('active');
            el.classList.add('active');
            document.getElementById('self_transport_type').value = type;

            // 駐車場番号：自動車/家族送迎の場合のみ表示
            var showParking = (type === '自動車' || type === '家族送迎');
            document.getElementById('parking_section').className = showParking ? '' : 'hidden';

            // 送りチェック：徒歩/家族送迎の場合のみ表示（自動車と自転車は不要）
            var showDropoff = (type === '徒歩' || type === '家族送迎');
            document.getElementById('self_dropoff_section').className = showDropoff ? '' : 'hidden';
        }

        window.onload = function () {
            var activeBtn = document.querySelector('.type-btn.active');
            if (activeBtn) {
                setType(activeBtn);
            }
            togglePickupTime();
            // 初期カード背景色を設定
            var eventType = document.getElementById('event_type').value;
            if (eventType) {
                updateCardBackground(eventType);
            }
        };
    </script>

</body>

</html>