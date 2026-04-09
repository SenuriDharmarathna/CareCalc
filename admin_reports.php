<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

// ── All analytics queries ──────────────────────────────────────────────────────

// Registrations per month (last 12)
$reg_monthly = [];
for ($i = 11; $i >= 0; $i--) {
    $r = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user' AND YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL $i MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL $i MONTH))")->fetch_assoc();
    $reg_monthly[] = ['label' => date('M y', strtotime("-$i months")), 'count' => (int)$r['c']];
}

// Predictions per month (last 12)
$pred_monthly = [];
for ($i = 11; $i >= 0; $i--) {
    $r = $conn->query("SELECT COUNT(*) c, ROUND(AVG(predicted_premium),0) avg FROM predictions WHERE YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL $i MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL $i MONTH))")->fetch_assoc();
    $pred_monthly[] = ['label' => date('M y', strtotime("-$i months")), 'count' => (int)$r['c'], 'avg' => (int)$r['avg']];
}

// Plan distribution
$plan_data = [];
$pr = $conn->query("SELECT recommended_plan, COUNT(*) c FROM predictions GROUP BY recommended_plan");
while ($row = $pr->fetch_assoc()) $plan_data[$row['recommended_plan']] = (int)$row['c'];
$total_preds = array_sum($plan_data) ?: 1;

// District distribution (top 10)
$district_data = [];
$dr = $conn->query("SELECT district, COUNT(*) c FROM predictions GROUP BY district ORDER BY c DESC LIMIT 10");
while ($row = $dr->fetch_assoc()) $district_data[] = $row;

// Gender distribution
$gender_data = $conn->query("SELECT gender, COUNT(*) c FROM predictions GROUP BY gender")->fetch_all(MYSQLI_ASSOC);

// Smoker vs non-smoker avg premium
$smoker_stats = $conn->query("SELECT smoker, ROUND(AVG(predicted_premium),0) avg, COUNT(*) c FROM predictions GROUP BY smoker")->fetch_all(MYSQLI_ASSOC);

// Age group distribution
$age_groups = $conn->query("
    SELECT
        CASE
            WHEN age < 25 THEN '18-24'
            WHEN age < 35 THEN '25-34'
            WHEN age < 45 THEN '35-44'
            WHEN age < 55 THEN '45-54'
            WHEN age < 65 THEN '55-64'
            ELSE '65+'
        END AS age_group,
        COUNT(*) c,
        ROUND(AVG(predicted_premium),0) avg_prem
    FROM predictions GROUP BY age_group ORDER BY age_group
")->fetch_all(MYSQLI_ASSOC);

// Condition prevalence
$conditions = ['heart_disease','diabetes','hypertension','asthma'];
$cond_stats = [];
foreach ($conditions as $c) {
    $r = $conn->query("SELECT SUM($c) yes_count, COUNT(*) total, ROUND(AVG(CASE WHEN $c=1 THEN predicted_premium ELSE NULL END),0) avg_when_yes FROM predictions")->fetch_assoc();
    $cond_stats[$c] = $r;
}

// Premium range buckets
$prem_ranges = $conn->query("
    SELECT
        CASE
            WHEN predicted_premium < 50000  THEN 'Under 50k'
            WHEN predicted_premium < 100000 THEN '50k-100k'
            WHEN predicted_premium < 200000 THEN '100k-200k'
            WHEN predicted_premium < 400000 THEN '200k-400k'
            WHEN predicted_premium < 700000 THEN '400k-700k'
            ELSE 'Over 700k'
        END AS range_label,
        COUNT(*) c
    FROM predictions GROUP BY range_label
")->fetch_all(MYSQLI_ASSOC);

// Income brackets avg premium
$income_prem = $conn->query("
    SELECT
        CASE
            WHEN annual_income < 1000000 THEN 'Under 1M'
            WHEN annual_income < 3000000 THEN '1M-3M'
            WHEN annual_income < 6000000 THEN '3M-6M'
            ELSE 'Over 6M'
        END AS bracket,
        ROUND(AVG(predicted_premium),0) avg_prem, COUNT(*) c
    FROM predictions GROUP BY bracket
")->fetch_all(MYSQLI_ASSOC);

// Overall stats
$overall = $conn->query("SELECT COUNT(*) total, ROUND(AVG(predicted_premium),0) avg, MAX(predicted_premium) mx, MIN(predicted_premium) mn, ROUND(STDDEV(predicted_premium),0) stddev FROM predictions")->fetch_assoc();
$total_users_stat = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics — CareCalc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>
.analytics-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.an-stat{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;}
.an-stat-label{font-size:0.64rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin-bottom:4px;}
.an-stat-val{font-family:'JetBrains Mono',monospace;font-size:1.35rem;font-weight:700;color:var(--ink);}
.an-stat-sub{font-size:0.7rem;color:var(--muted);margin-top:4px;}
.chart-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;}
.chart-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:22px;}
.chart-box{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;}
.chart-box-title{font-size:0.8rem;font-weight:700;color:var(--ink);margin-bottom:16px;}

/* Horizontal bar */
.hbar-list{display:flex;flex-direction:column;gap:9px;}
.hbar-row{display:flex;align-items:center;gap:10px;font-size:0.77rem;}
.hbar-label{width:90px;color:var(--ink);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hbar-track{flex:1;height:9px;background:var(--surface);border-radius:5px;overflow:hidden;}
.hbar-fill{height:100%;border-radius:5px;transition:width 0.6s ease;}
.hbar-val{width:32px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--muted);}

/* Vertical bar */
.vbar-chart{display:flex;align-items:flex-end;gap:6px;height:130px;margin-top:4px;}
.vbar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;}
.vbar{width:100%;border-radius:4px 4px 0 0;min-height:3px;}
.vbar-lbl{font-size:0.58rem;color:var(--muted);text-align:center;}
.vbar-num{font-family:'JetBrains Mono',monospace;font-size:0.58rem;color:var(--muted);}

/* Condition cards */
.cond-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
.cond-card{background:var(--surface);border-radius:var(--radius-sm);padding:12px 14px;}
.cond-name{font-size:0.72rem;font-weight:700;color:var(--ink);margin-bottom:6px;text-transform:capitalize;}
.cond-pct{font-family:'JetBrains Mono',monospace;font-size:1.2rem;font-weight:700;color:var(--accent);}
.cond-sub{font-size:0.68rem;color:var(--muted);margin-top:2px;}

/* Comparison table */
.cmp-table{width:100%;border-collapse:collapse;}
.cmp-table th{font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);padding:8px 0;border-bottom:1px solid var(--border);text-align:left;}
.cmp-table td{font-size:0.78rem;color:var(--ink);padding:9px 0;border-bottom:1px solid var(--border);}
.cmp-table tr:last-child td{border-bottom:none;}
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<!-- Top stats -->
<div class="analytics-grid fade-in d1">
    <div class="an-stat"><div class="an-stat-label">Total Predictions</div><div class="an-stat-val"><?= number_format($overall['total']) ?></div><div class="an-stat-sub">All time</div></div>
    <div class="an-stat"><div class="an-stat-label">Avg Premium</div><div class="an-stat-val">LKR <?= number_format($overall['avg'],0) ?></div><div class="an-stat-sub">±LKR <?= number_format($overall['stddev'],0) ?> std dev</div></div>
    <div class="an-stat"><div class="an-stat-label">Highest Premium</div><div class="an-stat-val">LKR <?= number_format($overall['mx'],0) ?></div></div>
    <div class="an-stat"><div class="an-stat-label">Registered Users</div><div class="an-stat-val"><?= $total_users_stat ?></div><div class="an-stat-sub">Customers</div></div>
</div>

<!-- Registration & Prediction trends -->
<div class="chart-grid-2 fade-in d2">
    <div class="chart-box">
        <div class="chart-box-title">New Registrations — Last 12 Months</div>
        <?php $mr = max(array_column($reg_monthly,'count') ?: [1]); $mr = max($mr,1); ?>
        <div class="vbar-chart">
        <?php foreach($reg_monthly as $m): ?>
        <div class="vbar-col">
            <div class="vbar-num"><?= $m['count'] ?></div>
            <div class="vbar" style="height:<?= round(($m['count']/$mr)*100) ?>%;background:var(--accent);opacity:0.8;"></div>
            <div class="vbar-lbl"><?= $m['label'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="chart-box">
        <div class="chart-box-title">Predictions Run — Last 12 Months</div>
        <?php $mp = max(array_column($pred_monthly,'count') ?: [1]); $mp = max($mp,1); ?>
        <div class="vbar-chart">
        <?php foreach($pred_monthly as $m): ?>
        <div class="vbar-col">
            <div class="vbar-num"><?= $m['count'] ?></div>
            <div class="vbar" style="height:<?= round(($m['count']/$mp)*100) ?>%;background:var(--accent2);opacity:0.85;"></div>
            <div class="vbar-lbl"><?= $m['label'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Plan dist + District + Age groups -->
<div class="chart-grid-3 fade-in d3">
    <!-- Coverage plan -->
    <div class="chart-box">
        <div class="chart-box-title">Coverage Plan Distribution</div>
        <?php $pc = ['Basic'=>'#2563ff','Standard'=>'#00d4aa','Premium'=>'#f97316']; ?>
        <div class="hbar-list">
        <?php foreach(['Basic','Standard','Premium'] as $plan): $cnt = $plan_dist[$plan] ?? 0; $pct = round(($cnt/$total_preds)*100); ?>
        <div class="hbar-row">
            <div class="hbar-label"><?= $plan ?></div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= $pct ?>%;background:<?= $pc[$plan] ?>;"></div></div>
            <div class="hbar-val"><?= $cnt ?></div>
        </div>
        <div style="font-size:0.66rem;color:var(--muted);margin-left:100px;margin-top:-6px;margin-bottom:2px;"><?= $pct ?>%</div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Top districts -->
    <div class="chart-box">
        <div class="chart-box-title">Predictions by District (Top 10)</div>
        <?php $md = $district_data ? max(array_column($district_data,'c')) : 1; ?>
        <div class="hbar-list">
        <?php foreach($district_data as $d): ?>
        <div class="hbar-row">
            <div class="hbar-label" title="<?= htmlspecialchars($d['district']) ?>"><?= htmlspecialchars($d['district']) ?></div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= round(($d['c']/$md)*100) ?>%;background:var(--purple);"></div></div>
            <div class="hbar-val"><?= $d['c'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Age groups -->
    <div class="chart-box">
        <div class="chart-box-title">Predictions by Age Group</div>
        <?php $ma = $age_groups ? max(array_column($age_groups,'c')) : 1; ?>
        <div class="hbar-list">
        <?php foreach($age_groups as $ag): ?>
        <div class="hbar-row">
            <div class="hbar-label"><?= $ag['age_group'] ?></div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= round(($ag['c']/$ma)*100) ?>%;background:var(--warn);"></div></div>
            <div class="hbar-val"><?= $ag['c'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Condition analysis + Smoker + Premium ranges -->
<div class="chart-grid-2 fade-in d4">
    <!-- Medical condition prevalence -->
    <div class="chart-box">
        <div class="chart-box-title">Medical Condition Prevalence & Impact</div>
        <div class="cond-grid">
        <?php foreach($cond_stats as $cname => $cs): ?>
        <?php $pct = $cs['total'] ? round(($cs['yes_count']/$cs['total'])*100,1) : 0; ?>
        <div class="cond-card">
            <div class="cond-name"><?= str_replace('_',' ',ucfirst($cname)) ?></div>
            <div class="cond-pct"><?= $pct ?>%</div>
            <div class="cond-sub">Avg premium if yes: LKR <?= $cs['avg_when_yes'] ? number_format($cs['avg_when_yes'],0) : '—' ?></div>
        </div>
        <?php endforeach; ?>
        </div>

        <div style="margin-top:18px;">
            <div class="chart-box-title" style="margin-bottom:10px;">Smoker vs Non-Smoker Premium</div>
            <table class="cmp-table">
                <thead><tr><th>Group</th><th>Count</th><th>Avg Premium</th></tr></thead>
                <tbody>
                <?php foreach($smoker_stats as $ss): ?>
                <tr>
                    <td><?= $ss['smoker'] ? '🚬 Smoker' : '✓ Non-Smoker' ?></td>
                    <td class="mono"><?= $ss['c'] ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:600;">LKR <?= number_format($ss['avg'],0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Premium range + Income bracket -->
    <div class="chart-box">
        <div class="chart-box-title">Premium Distribution by Range</div>
        <?php
        $range_order = ['Under 50k','50k-100k','100k-200k','200k-400k','400k-700k','Over 700k'];
        $range_map = [];
        foreach($prem_ranges as $r) $range_map[$r['range_label']] = $r['c'];
        $mr2 = max(array_values($range_map) ?: [1]);
        ?>
        <div class="hbar-list">
        <?php foreach($range_order as $rl): $cnt = $range_map[$rl] ?? 0; ?>
        <div class="hbar-row">
            <div class="hbar-label">LKR <?= $rl ?></div>
            <div class="hbar-track"><div class="hbar-fill" style="width:<?= round(($cnt/$mr2)*100) ?>%;background:var(--accent2);"></div></div>
            <div class="hbar-val"><?= $cnt ?></div>
        </div>
        <?php endforeach; ?>
        </div>

        <div style="margin-top:18px;">
            <div class="chart-box-title" style="margin-bottom:10px;">Avg Premium by Income Bracket</div>
            <table class="cmp-table">
                <thead><tr><th>Income</th><th>Users</th><th>Avg Premium</th></tr></thead>
                <tbody>
                <?php foreach($income_prem as $ip): ?>
                <tr>
                    <td><?= htmlspecialchars($ip['bracket']) ?></td>
                    <td class="mono"><?= $ip['c'] ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:600;">LKR <?= number_format($ip['avg_prem'],0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div></div>
</body></html>