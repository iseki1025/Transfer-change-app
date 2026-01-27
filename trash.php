<?php
/**
 * 送迎管理システム - ゴミ箱（削除済みレコード一覧）
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

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'restore') {
        // 復活（is_deleted = 0）
        $pdo->prepare("UPDATE records SET is_deleted = 0 WHERE id=?")->execute([$_POST['id']]);
        header('Location: trash.php');
        exit;
    } elseif ($action === 'delete_permanent') {
        // 完全削除
        $pdo->prepare("DELETE FROM records WHERE id=?")->execute([$_POST['id']]);
        header('Location: trash.php');
        exit;
    }
}

// 削除済みデータ取得
$sql = "SELECT * FROM records WHERE is_deleted = 1 ORDER BY target_date DESC, id DESC";
$records = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ゴミ箱 - 送迎管理</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --danger: #ef4444;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-100: #f1f5f9;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans JP', sans-serif;
            background: #f1f5f9;
            color: var(--slate-800);
        }

        header {
            background: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 16px;
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
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--slate-100);
            font-size: 0.9rem;
        }

        th {
            background: var(--slate-100);
            font-weight: 700;
            color: var(--slate-600);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-restore {
            background: #22c55e;
            color: #fff;
        }

        .btn-delete {
            background: transparent;
            color: var(--danger);
            border: 1px solid var(--danger);
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <header>
        <a href="index.php" class="back-link">← 一覧へ戻る</a>
        <h1 style="font-size:1.2rem; margin:0;">ゴミ箱</h1>
        <div style="width:50px;"></div>
    </header>

    <div class="container">
        <?php if (empty($records)): ?>
            <div style="text-align:center; padding: 40px; color:var(--slate-600);">ゴミ箱は空です</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>対象日</th>
                        <th>種別</th>
                        <th>患者名</th>
                        <th>備考</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td>
                                <?php echo date('Y/n/j', strtotime($row['target_date'])); ?>
                            </td>
                            <td>
                                <?php echo h($row['event_type']); ?>
                            </td>
                            <td>
                                <?php echo h($row['p_name']); ?>
                            </td>
                            <td style="font-size:0.8rem; color:#64748b;">
                                <?php echo h($row['reason'] ?? ''); ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-restore">復活する</button>
                                </form>
                                <form method="post" style="display:inline;"
                                    onsubmit="return confirm('本当に完全に削除しますか？これ以降復活できません。');">
                                    <input type="hidden" name="action" value="delete_permanent">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-delete">完全削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>