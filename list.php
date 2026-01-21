<?php
/**
 * 透析・送迎管理システム - 変更届一覧ページ
 * v2: 送り迎えフラグ表示対応
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

// 対象日が近い順
$today = date('Y-m-d');
$sql = "SELECT *, 
    CASE WHEN target_date >= ? THEN 0 ELSE 1 END as is_past
    FROM records 
    ORDER BY is_past ASC, 
        CASE WHEN target_date >= ? THEN target_date END ASC,
        CASE WHEN target_date < ? THEN target_date END DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$today, $today, $today]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>変更届一覧</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --danger: #ef4444;
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            padding: 14px 16px;
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
            max-width: 900px;
            margin: 0 auto;
            padding: 16px;
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
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.9rem;
        }

        th {
            background: var(--slate-100);
            font-weight: 700;
            color: var(--slate-600);
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

        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
        }

        .flag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-right: 4px;
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
            color: #94a3b8;
        }

        .edit-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 6px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .past-row {
            opacity: 0.5;
        }

        .today-row {
            background: #fffbeb !important;
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--slate-400);
        }

        @media (max-width: 600px) {

            th,
            td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }

            .hide-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>変更届一覧</h1>
        <a href="index.php" class="back-link">← トップへ</a>
    </header>

    <div class="container">
        <?php if (empty($records)): ?>
            <div class="empty">登録がありません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>対象日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th>送迎</th>
                        <th class="hide-mobile">内容</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $isPast = ($row['target_date'] < $today);
                        $isToday = ($row['target_date'] === $today);
                        $rowClass = $isPast ? 'past-row' : ($isToday ? 'today-row' : '');

                        $origDateStr = $row['orig_date'] ? date('n/j', strtotime($row['orig_date'])) : '';
                        $targetDateStr = $row['target_date'] ? date('n/j', strtotime($row['target_date'])) : '-';

                        $badgeColor = '#3b82f6';
                        if (in_array($row['event_type'], ['入院', '永眠']))
                            $badgeColor = '#ef4444';
                        if ($row['event_type'] === '臨時')
                            $badgeColor = '#f97316';

                        $needsPickup = isset($row['needs_pickup']) ? $row['needs_pickup'] : 1;
                        $needsDropoff = isset($row['needs_dropoff']) ? $row['needs_dropoff'] : 1;
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td class="date-cell">
                                <?php if ($origDateStr && $row['event_type'] === '変更'): ?>
                                    <?php echo $origDateStr; ?>→<?php echo $targetDateStr; ?>
                                <?php else: ?>
                                    <?php echo $targetDateStr; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="type-badge"
                                    style="background:<?php echo $badgeColor; ?>;"><?php echo h($row['event_type']); ?></span>
                            </td>
                            <td><?php echo h($row['p_name']); ?></td>
                            <td>
                                <span class="flag <?php echo $needsPickup ? 'flag-pickup' : 'flag-off'; ?>">迎</span>
                                <span class="flag <?php echo $needsDropoff ? 'flag-dropoff' : 'flag-off'; ?>">送</span>
                            </td>
                            <td class="hide-mobile">
                                <?php echo h($row['orig_schedule']); ?>        <?php echo $row['orig_schedule'] ? '→' : ''; ?>        <?php echo h($row['new_schedule']); ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="edit-link">編集</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>