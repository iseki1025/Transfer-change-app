<?php
/**
 * 薬局FAX送付状 - Premium UI Ver.
 * fax.php
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

// Mark as sent
$pdo->prepare("UPDATE records SET fax_sent = 1 WHERE id = ?")->execute([$id]);

// 日付フォーマット
$targetDateStr = $row['target_date'] ? date('n月j日', strtotime($row['target_date'])) : '';
$eventType = $row['event_type'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>FAX送付状</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+JP:wght@400;700&family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A5; margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0 auto; padding: 0;
            width: 148mm; min-height: 210mm;
            font-family: 'Noto Serif JP', serif;
            background: #e2e8f0; /* Preview background */
            color: #1e293b;
            display: flex; justify-content: center;
        }
        .page-container {
            width: 148mm; height: 210mm;
            background: #fff;
            padding: 15mm;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .header {
            border-bottom: 3px double #333;
            padding-bottom: 5mm; margin-bottom: 10mm;
            text-align: center;
        }
        .header-title {
            font-size: 22pt; font-weight: 700;
            letter-spacing: 0.2em; margin-bottom: 2mm;
        }
        .header-sub { font-size: 10pt; font-family: 'Noto Sans JP', sans-serif; }
        
        /* Content */
        .row { display: flex; justify-content: space-between; margin-bottom: 8mm; }
        .label { font-size: 10pt; color: #64748b; font-family: 'Noto Sans JP', sans-serif; margin-bottom: 1mm; }
        .value { font-size: 16pt; border-bottom: 1px solid #94a3b8; padding-bottom: 1mm; width: 100%; }
        
        .main-msg {
            margin: 10mm 0; padding: 8mm;
            border: 1px solid #cbd5e1; background: #f8fafc;
            font-size: 14pt; line-height: 1.8;
            font-family: 'Noto Sans JP', sans-serif;
        }
        .highlight { font-weight: 700; font-size: 16pt; text-decoration: underline; }
        
        .patient-box { text-align: center; margin: 10mm 0; padding: 5mm; border: 2px solid #333; }
        .p-name { font-size: 26pt; font-weight: 700; }
        
        /* Footer */
        .footer {
            position: absolute; bottom: 15mm; right: 15mm;
            text-align: right;
            font-size: 11pt; font-family: 'Noto Sans JP', sans-serif;
        }
        
        /* Print Button (FAB) */
        .fab {
            position: fixed; bottom: 30px; right: 30px;
            width: 60px; height: 60px; border-radius: 50%;
            background: #3b82f6; color: white;
            border: none; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: transform 0.2s;
        }
        .fab:hover { transform: scale(1.1); background: #2563eb; }
        .fab svg { width: 28px; height: 28px; stroke: currentColor; fill: none; stroke-width: 2; }
        
        @media print {
            body { background: none; width: auto; height: auto; }
            .page-container { box-shadow: none; margin: 0; }
            .fab { display: none; }
        }
    </style>
</head>
<body>

<div class="page-container">
    <div class="header">
        <div class="header-title">連絡票</div>
        <div class="header-sub">PHARMACY COMMUNICATION SHEET</div>
    </div>
    
    <div class="row">
        <div style="width: 60%">
            <div class="label">宛先</div>
            <div class="value" style="font-size: 18pt; font-weight: 700;">○○薬局 御中</div>
        </div>
        <div style="width: 30%; text-align: right;">
            <div class="label">送信日</div>
            <div class="value" style="font-size: 12pt; border: none; text-align: right;"><?php echo date('Y年n月j日'); ?></div>
        </div>
    </div>
    
    <div class="patient-box">
        <div class="label">対象患者</div>
        <div class="p-name"><?php echo htmlspecialchars($row['p_name']); ?> 様</div>
    </div>
    
    <div class="main-msg">
        <?php if ($eventType === '入院'): ?>
            入院のため、<span class="highlight"><?php echo $targetDateStr; ?></span> より<br>配薬を <span class="highlight">停止</span> してください。
        <?php elseif ($eventType === '退院'): ?>
            退院のため、<span class="highlight"><?php echo $targetDateStr; ?></span> より<br>配薬を <span class="highlight">再開</span> してください。
        <?php elseif ($eventType === '永眠'): ?>
            <span class="highlight"><?php echo $targetDateStr; ?></span> にご永眠されました。<br>配薬を <span class="highlight">終了</span> してください。
        <?php elseif ($row['pharmacy_date']): ?>
            透析日の変更に伴い、<br><span class="highlight"><?php echo date('n月j日', strtotime($row['pharmacy_date'])); ?></span> より配薬日を変更してください。
        <?php else: ?>
            配薬に関する連絡事項がございます。
        <?php endif; ?>
    </div>
    
    <?php if ($row['reason']): ?>
    <div style="margin-top: 5mm; border-top: 1px dashed #ccc; padding-top: 5mm;">
        <div class="label">備考</div>
        <p><?php echo htmlspecialchars($row['reason']); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <strong>○○透析クリニック</strong><br>
        TEL: 00-0000-0000<br>
        担当: スタッフ一同
    </div>
</div>

<button class="fab" onclick="window.print()" title="印刷">
    <svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
</button>

</body>
</html>