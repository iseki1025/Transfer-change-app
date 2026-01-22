<?php
/**
 * 透析・送迎管理システム - 設定ページ
 * settings.php
 */

$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
}

// Configテーブル作成
$pdo->exec("CREATE TABLE IF NOT EXISTS app_config (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    hospital_name TEXT DEFAULT '',
    hospital_tel TEXT DEFAULT '',
    pharmacy_name TEXT DEFAULT '',
    transfer_schedule_url TEXT DEFAULT ''
)");

// 初期データ投入
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

$message = '';

// 保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_name = $_POST['hospital_name'] ?? '';
    $hospital_tel = $_POST['hospital_tel'] ?? '';
    $pharmacy_name = $_POST['pharmacy_name'] ?? '';
    $transfer_schedule_url = $_POST['transfer_schedule_url'] ?? '';

    $stmt = $pdo->prepare("UPDATE app_config SET hospital_name=?, hospital_tel=?, pharmacy_name=?, transfer_schedule_url=? WHERE id=1");
    $stmt->execute([$hospital_name, $hospital_tel, $pharmacy_name, $transfer_schedule_url]);

    // 再取得
    $config['hospital_name'] = $hospital_name;
    $config['hospital_tel'] = $hospital_tel;
    $config['pharmacy_name'] = $pharmacy_name;
    $config['transfer_schedule_url'] = $transfer_schedule_url;

    $message = '設定を保存しました';
}

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>設定 - 透析・送迎管理</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --slate-800: #1e293b;
            --slate-600: #475569;
            --slate-100: #f1f5f9;
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
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--slate-600);
        }

        input[type="text"],
        input[type="url"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #cbd5e1;
            font-size: 1rem;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .hint {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-save:hover {
            opacity: 0.9;
        }

        .message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <header>
        <h1>設定</h1>
        <a href="index.php" class="back-link">← トップへ</a>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="message">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label>クリニック名</label>
                    <input type="text" name="hospital_name" value="<?php echo h($config['hospital_name']); ?>"
                        placeholder="○○透析クリニック">
                    <div class="hint">FAX送付状や案内に表示されます</div>
                </div>

                <div class="form-group">
                    <label>電話番号</label>
                    <input type="text" name="hospital_tel" value="<?php echo h($config['hospital_tel']); ?>"
                        placeholder="03-1234-5678">
                    <div class="hint">FAX送付状や案内に表示されます</div>
                </div>

                <div class="form-group">
                    <label>薬局名 (デフォルト)</label>
                    <input type="text" name="pharmacy_name" value="<?php echo h($config['pharmacy_name']); ?>"
                        placeholder="○○薬局">
                    <div class="hint">FAX送付状の宛先初期値です</div>
                </div>

                <div class="form-group">
                    <label>送迎表 URL</label>
                    <input type="url" name="transfer_schedule_url"
                        value="<?php echo h($config['transfer_schedule_url']); ?>" placeholder="https://...">
                    <div class="hint">トップページの「送迎表」ボタンのリンク先です (Googleスプレッドシートなど)</div>
                </div>

                <button type="submit" class="btn-save">保存する</button>
            </form>
        </div>
    </div>

</body>

</html>