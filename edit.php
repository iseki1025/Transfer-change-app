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
    "is_handled INTEGER DEFAULT 0"
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
    'office_staff' => ''
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
    $origWd = getWeekday($_POST['orig_date'] ?? '');
    $targetWd = getWeekday($_POST['target_date'] ?? '');

    if ($isNew) {
        $stmt = $pdo->prepare("INSERT INTO records 
            (p_name, event_type, orig_date, orig_weekday, orig_schedule, target_date, target_weekday, new_schedule, reason, needs_pickup, needs_dropoff, pickup_time, bed_change, bed_no, bxp_change, exam_change, pharmacy_req, pharmacy_date, created_by, technician, office_staff) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $_POST['office_staff'] ?? ''
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE records SET 
            p_name=?, event_type=?, orig_date=?, orig_weekday=?, orig_schedule=?, 
            target_date=?, target_weekday=?, new_schedule=?, reason=?, 
            needs_pickup=?, needs_dropoff=?, pickup_time=?,
            bed_change=?, bed_no=?, bxp_change=?, exam_change=?,
            pharmacy_req=?, pharmacy_date=?,
            created_by=?, technician=?, office_staff=?
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

        .pickup-time-box input[type="time"] {
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
    </style>
</head>

<body>

    <header>
        <a href="index.php" class="back-link">← 戻る</a>
        <h1><?php echo $pageTitle; ?></h1>
        <div style="width:50px;"></div>
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
            </div>

            <label>患者氏名</label>
            <input type="text" name="p_name" value="<?php echo h($row['p_name']); ?>" required placeholder="氏名を入力">

            <label>種別</label>
            <div class="type-btns">
                <?php foreach (['変更', '臨時', '入院', '退院', '連絡事項', '永眠'] as $t): ?>
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
                class="<?php echo in_array($row['event_type'], ['入院', '退院', '永眠', '連絡事項']) ? '' : 'hidden'; ?>">
                <label id="single_label"><?php
                if ($row['event_type'] === '入院')
                    echo '入院日';
                elseif ($row['event_type'] === '退院')
                    echo '退院日';
                elseif ($row['event_type'] === '永眠')
                    echo '永眠日';
                else
                    echo '対象日';
                ?></label>
                <input type="date" name="target_date_single" id="target_date_single"
                    value="<?php echo h($row['target_date']); ?>">
            </div>

            <!-- 送り迎えフラグ -->
            <div id="transport_flags"
                class="<?php echo in_array($row['event_type'], ['変更', '臨時', '']) ? '' : 'hidden'; ?>">
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

                <div id="pickup_time_section" class="pickup-time-box <?php echo $needsPickup ? '' : 'hidden'; ?>">
                    <label>🚗 迎え時間</label>
                    <input type="time" name="pickup_time" id="pickup_time" value="<?php echo h($pickupTime); ?>">
                    <div class="pickup-time-hint">※ 入力すると一覧に「案内」ボタンが表示されます</div>
                </div>
            </div>

            <label>理由・備考</label>
            <textarea name="reason" placeholder="任意"><?php echo h($row['reason']); ?></textarea>

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
                    <label>検査変更</label>
                    <select name="exam_change">
                        <option value="なし" <?php if (($row['exam_change'] ?? '') === 'なし')
                            echo 'selected'; ?>>なし</option>
                        <option value="あり" <?php if (($row['exam_change'] ?? '') === 'あり')
                            echo 'selected'; ?>>あり</option>
                    </select>
                </div>
            </div>

            <div id="extra_pharmacy" class="<?php echo ($row['pharmacy_req'] ?? '') === '必要' ? '' : 'hidden'; ?>">
                <label>配薬変更日</label>
                <input type="date" name="pharmacy_date" value="<?php echo h($row['pharmacy_date']); ?>">
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

            document.getElementById('date_change').className = isEditor ? '' : 'hidden';
            document.getElementById('date_single').className = isEditor ? 'hidden' : '';
            document.getElementById('btn_tomorrow').className = isTemp ? '' : 'hidden';
            document.getElementById('transport_flags').className = isEditor ? '' : 'hidden';

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
            document.getElementById('extra_pharmacy').className = (document.getElementById('pharmacy_req').value === '必要') ? '' : 'hidden';
            document.getElementById('extra_bed').className = (document.getElementById('bed_change').value === 'あり') ? '' : 'hidden';
        }

        function togglePickupTime() {
            var cb = document.querySelector('input[name="needs_pickup"]');
            document.getElementById('pickup_time_section').className = cb.checked ? 'pickup-time-box' : 'pickup-time-box hidden';
        }

        window.onload = function () {
            var activeBtn = document.querySelector('.type-btn.active');
            if (activeBtn) setType(activeBtn);
            togglePickupTime();
        };
    </script>

</body>

</html>