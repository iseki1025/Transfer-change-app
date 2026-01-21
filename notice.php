<?php
/**
 * 患者向け案内 - Premium UI Ver.
 * notice.php
 */
$dbFile = __DIR__ . '/dialysis_manager.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) { die('DB Error'); }

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM records WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) die('No Data');

// 日付計算
$target = new DateTime($row['target_date']);
$today = new DateTime('today');
$diff = $today->diff($target);
$days = (int)$diff->format('%r%a');

$relText = '';
if ($days === 0) $relText = '本日';
elseif ($days === 1) $relText = '明日';
elseif ($days === 2) $relText = '明後日';

$dateStr = $target->format('n月j日') . ' (' . $row['target_weekday'] . ')';
$timeStr = $row['pickup_time'] ? date('G:i', strtotime($row['pickup_time'])) : '--:--';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送迎のご案内</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; padding: 20px;
            font-family: 'Noto Sans JP', sans-serif;
            background: #fff; color: #1e293b;
            text-align: center;
            display: flex; flex-direction: column; align-items: center; min-height: 100vh;
        }
        
        .header {
            width: 100%; max-width: 600px;
            border-bottom: 4px solid #3b82f6;
            margin-bottom: 40px; padding-bottom: 20px;
        }
        .header h1 {
            font-size: 2rem; margin: 0; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        
        .name-card {
            background: #f1f5f9; padding: 20px 40px; border-radius: 20px;
            margin-bottom: 40px;
        }
        .p-name { font-size: 2.5rem; font-weight: 900; }
        
        .main-content {
            width: 100%; max-width: 600px;
        }
        
        .rel-date {
            font-size: 5rem; color: #ef4444; font-weight: 900;
            line-height: 1.2; margin-bottom: 10px;
        }
        .abs-date { font-size: 2.5rem; color: #475569; margin-bottom: 40px; }
        
        .time-box {
            border: 8px solid #3b82f6; border-radius: 30px;
            padding: 30px; background: #eff6ff;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
        }
        .time-label { font-size: 1.5rem; color: #3b82f6; font-weight: 700; margin-bottom: 10px; }
        .time-val { font-size: 6rem; font-weight: 900; line-height: 1; color: #1e293b; letter-spacing: -2px; }
        
        .driver-info {
            font-size: 1.5rem; color: #64748b; margin-top: 20px;
            background: #fff; border: 2px solid #cbd5e1;
            padding: 10px 20px; border-radius: 10px; display: inline-block;
        }
        
        .fab {
            position: fixed; bottom: 30px; right: 30px;
            background: #1e293b; color: white;
            padding: 20px 40px; border-radius: 50px;
            font-size: 1.5rem; font-weight: 700; border: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            cursor: pointer; display: flex; align-items: center; gap: 10px;
        }
        
        @media print {
            .fab { display: none; }
            body { padding: 0; justify-content: start; }
            .time-box { border-width: 4px; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>
        <svg  width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.8 0-1.5.4-2 1.1-.5.6-.8 1.4-.8 2.2V16c0 .6.4 1 1 1h1"/><path d="M7 19a2 2 0 1 0 4 0 2 2 0 1 0-4 0"/><path d="M17 19a2 2 0 1 0 4 0 2 2 0 1 0-4 0"/><path d="m16 9 2 4"/></svg>
        送迎のご案内
    </h1>
</div>

<div class="name-card">
    <div class="p-name"><?php echo htmlspecialchars($row['p_name']); ?> 様</div>
</div>

<div class="main-content">
    <?php if ($relText): ?>
        <div class="rel-date"><?php echo $relText; ?></div>
    <?php endif; ?>
    
    <div class="abs-date"><?php echo $dateStr; ?></div>
    
    <?php if ($row['new_schedule']): ?>
        <div style="font-size: 2rem; margin-bottom: 2rem;">
            予定: <strong><?php echo htmlspecialchars($row['new_schedule']); ?></strong>
        </div>
    <?php endif; ?>
    
    <div class="time-box">
        <div class="time-label">お迎え予定時間</div>
        <div class="time-val"><?php echo $timeStr; ?></div>
    </div>
    
    <?php if ($row['drv_name']): ?>
    <div class="driver-info">
        担当ドライバー: <strong><?php echo htmlspecialchars($row['drv_name']); ?></strong>
    </div>
    <?php endif; ?>
</div>

<button class="fab" onclick="window.print()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
    印刷する
</button>

</body>
</html>