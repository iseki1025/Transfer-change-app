<?php
/**
 * 患者向け案内（駐車場） - Premium UI Ver.
 * parking_notice.php
 */
$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB Error');
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM records WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row)
    die('No Data');

// 設定取得
$stmt = $pdo->query("SELECT * FROM app_config WHERE id = 1");
$config = $stmt->fetch();
$hospitalName = $config['hospital_name'] ?: '○○透析クリニック';

// 日付計算
$target = new DateTime($row['target_date']);
$dateStr = $target->format('n月j日') . ' (' . $row['target_weekday'] . ')';
$parkingNo = $row['parking_number'] ?: '---';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>駐車場の案内</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Noto Sans JP', sans-serif;
            background: #fff;
            color: #1e293b;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .header {
            width: 100%;
            max-width: 600px;
            border-bottom: 4px solid #f59e0b;
            /* Orange for Parking */
            margin-bottom: 40px;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .name-card {
            background: #f1f5f9;
            padding: 20px 40px;
            border-radius: 20px;
            margin-bottom: 40px;
        }

        .p-name {
            font-size: 2.5rem;
            font-weight: 900;
        }

        .main-content {
            width: 100%;
            max-width: 600px;
        }

        .abs-date {
            font-size: 4rem;
            color: #ef4444;
            font-weight: 900;
            margin-bottom: 30px;
            letter-spacing: 0.05em;
        }

        .time-box {
            border: 8px solid #f59e0b;
            border-radius: 30px;
            padding: 30px;
            background: #fffbeb;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.3);
        }

        .time-label {
            font-size: 1.5rem;
            color: #d97706;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .time-val {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1.2;
            color: #1e293b;
            letter-spacing: -1px;
        }

        .info-msg {
            font-size: 1.2rem;
            color: #64748b;
            margin-top: 20px;
            line-height: 1.6;
        }

        .footer {
            margin-top: auto;
            color: #94a3b8;
            font-size: 1rem;
            padding-bottom: 80px;
        }

        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e293b;
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 700;
            border: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media print {
            .fab {
                display: none;
            }

            body {
                padding: 0;
                justify-content: start;
            }

            .time-box {
                border-width: 4px;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2" />
                <path d="M9 17V7h4a3 3 0 0 1 0 6H9" />
            </svg>
            駐車場の案内
        </h1>
    </div>

    <div class="name-card">
        <div class="p-name">
            <?php echo htmlspecialchars($row['p_name']); ?> 様
        </div>
    </div>

    <div class="main-content">

        <div class="abs-date">
            <?php echo $dateStr; ?>
        </div>

        <?php if ($row['new_schedule']): ?>
            <div style="font-size: 2rem; margin-bottom: 2rem;">
                予定: <strong>
                    <?php echo htmlspecialchars($row['new_schedule']); ?>
                </strong>
            </div>
        <?php endif; ?>

        <div class="time-box">
            <div class="time-label">駐車場番号</div>
            <div class="time-val">
                <?php echo htmlspecialchars($parkingNo); ?>
            </div>
        </div>

        <div class="info-msg">
            当日は指定の駐車場をご利用ください。<br>
            お気をつけてお越しください。
        </div>
    </div>

    <div class="footer">
        <?php echo htmlspecialchars($hospitalName); ?>
    </div>

    <button class="fab" onclick="window.print()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7" />
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M6 14h12v8H6z" />
        </svg>
        印刷する
    </button>

</body>

</html>