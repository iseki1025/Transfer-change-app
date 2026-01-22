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

// データ取得（入院・退院イベントを全て取得し、日付順に並べる）
$today = date('Y-m-d');
$todayTs = strtotime($today);

// 入院・退院レコードをすべて取得（未来の日付を含む一連のタイムラインが必要なため、日付順昇順で取得）
$sql = "SELECT * FROM records WHERE event_type IN ('入院', '退院') ORDER BY target_date ASC, id ASC";
$allRecords = $pdo->query($sql)->fetchAll();

// 名前正規化関数（空白除去）
function normalizeName($name)
{
    // 全角スペース、半角スペース、タブなどを除去
    return preg_replace('/\s+/u', '', $name);
}

// 患者ごとのステータスを追跡
// $patientsStatus[正規化された名前] = ['status' => 'Hospitalized'|'Discharged', 'admission_date' => '...', 'record' => row]
$patientsStatus = [];

foreach ($allRecords as $row) {
    if (empty($row['p_name']))
        continue;
    $normName = normalizeName($row['p_name']);

    // 入院イベント
    if ($row['event_type'] === '入院') {
        // 上書き（最新の入院日が有効になる）
        $patientsStatus[$normName] = [
            'status' => 'Hospitalized',
            'admission_date' => $row['target_date'],
            'record' => $row
        ];
    }
    // 退院イベント
    elseif ($row['event_type'] === '退院') {
        // 退院したらステータスを消去（現在入院していない）
        if (isset($patientsStatus[$normName])) {
            unset($patientsStatus[$normName]);
        }
    }
}

// 表示用リストの作成（4日以上経過している人のみ）
$displayRecords = [];
foreach ($patientsStatus as $normName => $data) {
    if ($data['status'] === 'Hospitalized') {
        $admDate = $data['admission_date'];
        $admTs = strtotime($admDate);

        // 経過日数計算 (今日 - 入院日)
        // 例: 入院日1日、今日4日 -> 3日経過 (4日目)
        // 要望: 「4日以上退院がなかったら」-> 入院日から4日後の日付 <= 今日
        // つまり (Today - AdmissionDate) >= 4 days ???
        // または「入院してから4日経っている」
        // Difference in seconds
        $diffSeconds = $todayTs - $admTs;
        $diffDays = floor($diffSeconds / (60 * 60 * 24));

        // 4日以上経過（入院日を0日目とするか1日目とするかだが、ここでは単純に差分4日以上とする）
        // 入院日20日 -> 今日24日 (差分4日) -> 表示
        if ($diffDays >= 4) {
            // 表示データに追加（経過日数も入れておく）
            $row = $data['record'];
            $row['_days_elapsed'] = $diffDays;
            $displayRecords[] = $row;
        }
    }
}

// 表示順を入院日が古い順（長期入院順）にする
usort($displayRecords, function ($a, $b) {
    return strcmp($a['target_date'], $b['target_date']);
});

$records = $displayRecords; // テンプレートで使用する変数にセット
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

        .days-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--slate-600);
            background: var(--slate-100);
            margin-left: 8px;
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
        <h1>長期入院一覧（4日以上）</h1>
        <div style="width:50px;"></div>
    </header>

    <div class="container">

        <?php if (empty($records)): ?>
            <div class="empty">現在、長期入院中の患者さんはいません</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>入院日 (経過)</th>
                        <th>状態</th>
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
                        $days = $row['_days_elapsed'];
                        ?>
                        <tr>
                            <td class="date-cell">
                                <?php echo $targetDateStr; ?>
                                <span class="days-badge"><?php echo $days; ?>日目</span>
                            </td>
                            <td><span class="type-badge">入院中</span></td>
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