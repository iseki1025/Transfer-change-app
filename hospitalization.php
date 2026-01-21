<?php
/**
 * 透析・送迎管理システム - 入院一覧
 * 現在入院中（と推測される）または過去の入院記録を表示
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

// データ取得（入院イベントで、日付が今日以前のもの）
$today = date('Y-m-d');
$sql = "SELECT * FROM records WHERE event_type = '入院' AND target_date <= '$today' ORDER BY target_date DESC";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>入院一覧 - 透析・送迎管理</title>
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
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.9rem;
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

        .type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fff;
            background: #ef4444;
        }

        .name-cell {
            font-weight: 700;
        }

        .date-cell {
            font-weight: 700;
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--slate-400);
            background: #fff;
            border-radius: 12px;
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
        }
    </style>
</head>

<body>

    <header>
        <a href="index.php" class="nav-link">← 戻る</a>
        <h1>入院一覧</h1>
        <div style="width:50px;"></div>
    </header>

    <div class="container">

        <?php if (empty($records)): ?>
            <div class="empty">入院記録はありません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>入院日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th class="hide-mobile">理由・備考</th>
                        <th class="hide-mobile">担当</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $targetDateStr = $row['target_date'] ? date('n/j', strtotime($row['target_date'])) . '(' . h($row['target_weekday']) . ')' : '-';
                        $createdBy = $row['created_by'] ?? '';
                        $technician = $row['technician'] ?? '';
                        $officeStaff = $row['office_staff'] ?? '';
                        ?>
                        <tr>
                            <td class="date-cell">
                                <?php echo $targetDateStr; ?>
                            </td>
                            <td><span class="type-badge">入院</span></td>
                            <td class="name-cell">
                                <?php echo h($row['p_name']); ?>
                            </td>
                            <td class="hide-mobile" style="color:var(--slate-600);">
                                <?php echo h($row['reason'] ?? ''); ?>
                            </td>
                            <td class="hide-mobile" style="font-size:0.75rem; color:var(--slate-600);">
                                <?php if ($createdBy): ?>入力:
                                    <?php echo h($createdBy); ?><br>
                                <?php endif; ?>
                                <?php if ($technician): ?>技士:
                                    <?php echo h($technician); ?><br>
                                <?php endif; ?>
                                <?php if ($officeStaff): ?>医事:
                                    <?php echo h($officeStaff); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

</body>

</html>