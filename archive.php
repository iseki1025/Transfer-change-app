<?php
/**
 * 透析・送迎管理システム - 過去一覧
 * v2: 医事課チェック欄削除、担当に医事追加
 */

$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// データ取得（両方の日付が過去のレコードのみ）
$today = date('Y-m-d');
$sql = "SELECT * FROM records WHERE target_date < '$today' AND (orig_date IS NULL OR orig_date = '' OR orig_date < '$today') ORDER BY target_date DESC";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>過去一覧 - 透析・送迎管理</title>
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
            color: var(--slate-600);
        }

        .nav-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 16px;
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
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr {
            opacity: 0.7;
        }

        tr:hover {
            background: #f8fafc;
            opacity: 1;
        }

        .date-cell {
            font-weight: 700;
            white-space: nowrap;
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

        .check-done {
            color: var(--success);
            font-weight: 700;
        }

        .check-none {
            color: var(--slate-400);
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
        <a href="index.php" class="nav-link">← 戻る</a>
        <h1>過去一覧</h1>
        <div style="width:50px;"></div>
    </header>

    <div class="container">

        <?php if (empty($records)): ?>
            <div class="empty">過去の記録はありません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>対象日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th>迎え時間</th>
                        <th>運①</th>
                        <th>運②</th>
                        <th class="hide-mobile">備考</th>
                        <th class="hide-mobile">担当</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $targetDateStr = $row['target_date'] ? date('n/j', strtotime($row['target_date'])) . '(' . h($row['target_weekday']) . ')' : '-';

                        $badgeColor = '#94a3b8';
                        if (in_array($row['event_type'], ['入院', '永眠']))
                            $badgeColor = '#ef4444';
                        if ($row['event_type'] === '臨時')
                            $badgeColor = '#f97316';
                        if ($row['event_type'] === '退院')
                            $badgeColor = '#22c55e';
                        if ($row['event_type'] === '変更')
                            $badgeColor = '#3b82f6';

                        $chkDrv1 = $row['chk_drv1'] ?? 0;
                        $chkDrv2 = $row['chk_drv2'] ?? 0;
                        $createdBy = $row['created_by'] ?? '';
                        $technician = $row['technician'] ?? '';
                        $officeStaff = $row['office_staff'] ?? '';
                        ?>
                        <tr>
                            <td class="date-cell"><?php echo $targetDateStr; ?></td>
                            <td><span class="type-badge"
                                    style="background:<?php echo $badgeColor; ?>;"><?php echo h($row['event_type']); ?></span>
                            </td>
                            <td class="name-cell"><?php echo h($row['p_name']); ?></td>
                            <td><?php echo h($row['pickup_time'] ?? '-'); ?></td>
                            <td class="<?php echo $chkDrv1 ? 'check-done' : 'check-none'; ?>">
                                <?php echo $chkDrv1 ? '✓' : '-'; ?>
                            </td>
                            <td class="<?php echo $chkDrv2 ? 'check-done' : 'check-none'; ?>">
                                <?php echo $chkDrv2 ? '✓' : '-'; ?>
                            </td>
                            <td class="hide-mobile" style="font-size:0.75rem; color:var(--slate-600);">
                                <?php echo h($row['reason'] ?? ''); ?>
                            </td>
                            <td class="hide-mobile staff-info">
                                <?php if ($createdBy): ?>入力:<?php echo h($createdBy); ?><br><?php endif; ?>
                                <?php if ($technician): ?>技士:<?php echo h($technician); ?><br><?php endif; ?>
                                <?php if ($officeStaff): ?>医事:<?php echo h($officeStaff); ?><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

</body>

</html>