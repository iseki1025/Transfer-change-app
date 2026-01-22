<?php
/**
 * 透析・送迎管理システム - 連絡事項対応済み一覧
 * 運転手2名の確認が完了した連絡事項を表示
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

// 完了した連絡事項（運転手2名ともチェック済み）
// 入力の新しい順 (id DESC)
$sql = "SELECT * FROM records 
        WHERE event_type = '連絡事項' AND chk_drv1 = 1 AND chk_drv2 = 1 
        ORDER BY id DESC";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>連絡事項一覧 (対応済)</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
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
            color: var(--slate-600);
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
            border-bottom: 2px solid var(--slate-300);
            font-size: 0.9rem;
        }

        th {
            background: var(--slate-100);
            font-weight: 700;
            color: var(--slate-600);
            border-bottom: 2px solid var(--slate-300);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f8fafc;
        }

        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
            background: #d97706;
            /* 連絡事項の色 */
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--slate-400);
        }

        .created-at {
            font-size: 0.8rem;
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
        <h1>連絡事項一覧 (対応済)</h1>
        <a href="index.php" class="back-link">← トップへ</a>
    </header>

    <div class="container">
        <?php if (empty($records)): ?>
            <div class="empty">対応済みの連絡事項はありません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>登録日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th>内容</th>
                        <th class="hide-mobile">担当</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row):
                        $createdAt = date('Y/n/j', strtotime($row['created_at']));
                        ?>
                        <tr>
                            <td>
                                <?php echo $createdAt; ?>
                            </td>
                            <td><span class="type-badge">連絡事項</span></td>
                            <td>
                                <?php echo h($row['p_name']); ?>
                            </td>
                            <td>
                                <?php echo h($row['reason'] ?? ''); ?>
                            </td>
                            <td class="hide-mobile" style="font-size:0.8rem; color:var(--slate-600);">
                                <?php echo h($row['created_by']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>