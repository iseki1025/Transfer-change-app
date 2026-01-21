<?php
/**
 * 透析・送迎管理システム - トップページ（今日以降の予定 + 未対応の連絡事項）
 * v9: 連絡事項対応、入院一覧リンク追加、ソート修正
 */

$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
}

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
    (event_type = '連絡事項' AND is_handled = 0) OR
    (event_type != '連絡事項' AND event_type != '入院' AND target_date >= '$today') OR
    (event_type = '入院' AND target_date >= '$today') OR
    (event_type = '変更' AND (orig_date >= '$today' OR target_date >= '$today'))
    ORDER BY target_date ASC, 
    CASE WHEN (needs_pickup = 1 OR needs_dropoff = 1) AND (pickup_time IS NULL OR pickup_time = '') THEN 0 ELSE 1 END";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>透析・送迎管理</title>
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
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.8rem;
        }

        th {
            background: var(--slate-100);
            font-weight: 700;
            color: var(--slate-600);
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
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 700;
            margin-right: 2px;
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
        }

        .check-cell {
            text-align: center;
        }

        .check-box {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            border: 2px solid var(--slate-400);
            background: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .check-box.checked {
            background: var(--success);
            border-color: var(--success);
        }

        .check-box svg {
            display: none;
            width: 14px;
            height: 14px;
            stroke: #fff;
            stroke-width: 3;
        }

        .check-box.checked svg {
            display: block;
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

        .today-row {
            background: #fffbeb !important;
        }

        .notice-row {
            background: #fff7ed !important;
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
                padding: 12px 5px;
                font-size: 0.9rem;
                white-space: normal;
            }

            .hide-mobile {
                display: none;
            }

            .type-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>透析・送迎管理</h1>
        <div class="nav-links">
            <a href="hospitalization.php" class="nav-link">入院一覧</a>
            <a href="archive.php" class="nav-link">過去一覧 →</a>
        </div>
    </header>

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
                        <th class="check-cell">運①</th>
                        <th class="check-cell">運②</th>
                        <th class="hide-mobile">備考／連絡内容</th>
                        <th class="hide-mobile">担当</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $isToday = ($row['target_date'] === $today);
                        $isNotice = ($row['event_type'] === '連絡事項');

                        $needsPickup = $row['needs_pickup'] ?? 1;
                        $needsDropoff = $row['needs_dropoff'] ?? 1;
                        // 迎えが必要(連絡事項以外)かつ時間未入力
                        $needsDriverInput = (!$isNotice && $needsPickup && empty($row['pickup_time']));

                        $rowClass = $isNotice ? 'notice-row' : ($needsDriverInput ? 'needs-driver' : ($isToday ? 'today-row' : ''));

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
                                <div class="date-main">
                                    <?php if ($origDateStr && $row['event_type'] === '変更'): ?>
                                        <?php echo $origDateStr; ?>→<br><?php echo $targetDateStr; ?>
                                    <?php else: ?>
                                        <?php echo $targetDateStr; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['orig_schedule'] || $row['new_schedule']): ?>
                                    <div class="date-sub">
                                        <?php
                                        if ($row['orig_schedule'] && $row['new_schedule']) {
                                            echo h($row['orig_schedule']) . '→' . h($row['new_schedule']);
                                        } elseif ($row['new_schedule']) {
                                            echo h($row['new_schedule']);
                                        } else {
                                            echo h($row['orig_schedule']);
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
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
                            <td class="check-cell">
                                <div class="check-box <?php echo $chkDrv1 ? 'checked' : ''; ?>"
                                    onclick="toggleCheck(this, <?php echo $row['id']; ?>, 'chk_drv1', <?php echo $chkDrv1 ? 0 : 1; ?>)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </div>
                            </td>
                            <td class="check-cell">
                                <div class="check-box <?php echo $chkDrv2 ? 'checked' : ''; ?>"
                                    onclick="toggleCheck(this, <?php echo $row['id']; ?>, 'chk_drv2', <?php echo $chkDrv2 ? 0 : 1; ?>)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </div>
                            </td>
                            <td class="hide-mobile" style="font-size:0.75rem; color:var(--slate-600); max-width:100px;">
                                <?php echo h($row['reason'] ?? ''); ?></td>
                            <td class="hide-mobile staff-info">
                                <?php if ($createdBy): ?>入力:<?php echo h($createdBy); ?><br><?php endif; ?>
                                <?php if ($technician): ?>技士:<?php echo h($technician); ?><br><?php endif; ?>
                                <?php if ($officeStaff): ?>医事:<?php echo h($officeStaff); ?><?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($isNotice): ?>
                                        <form method="post" style="display:inline;"
                                            onsubmit="return confirm('対応済みにしますか？（一覧から消えます）');">
                                            <input type="hidden" name="action" value="handle">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-handle">対応済</button>
                                        </form>
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