<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

$admin_name = $_SESSION['username'] ?? 'Admin';

// Stats
$total_users    = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_preds    = $conn->query("SELECT COUNT(*) c FROM predictions")->fetch_assoc()['c'];
$total_msgs     = $conn->query("SELECT COUNT(*) c FROM contact_messages")->fetch_assoc()['c'];
$unread_msgs    = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE is_read=0")->fetch_assoc()['c'] ?? 0;
$new_users_month= $conn->query("SELECT COUNT(*) c FROM users WHERE role='user' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['c'];
$preds_month    = $conn->query("SELECT COUNT(*) c FROM predictions WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['c'];

$avg_row  = $conn->query("SELECT ROUND(AVG(predicted_premium),0) a, MAX(predicted_premium) mx, MIN(predicted_premium) mn FROM predictions")->fetch_assoc();
$avg_premium = $avg_row['a'] ? number_format($avg_row['a'],0) : '0';
$max_premium = $avg_row['mx'] ? number_format($avg_row['mx'],0) : '0';

// Plan distribution
$plan_dist = [];
$pr = $conn->query("SELECT recommended_plan, COUNT(*) c FROM predictions GROUP BY recommended_plan");
while ($row = $pr->fetch_assoc()) $plan_dist[$row['recommended_plan']] = (int)$row['c'];

// Monthly predictions (last 6 months)
$monthly_preds = [];
for ($i = 5; $i >= 0; $i--) {
    $r = $conn->query("SELECT COUNT(*) c FROM predictions WHERE YEAR(created_at)=YEAR(DATE_SUB(NOW(),INTERVAL $i MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(NOW(),INTERVAL $i MONTH))")->fetch_assoc();
    $monthly_preds[] = ['label' => date('M', strtotime("-$i months")), 'count' => (int)$r['c']];
}

// Top districts
$district_dist = [];
$dr = $conn->query("SELECT district, COUNT(*) c FROM predictions GROUP BY district ORDER BY c DESC LIMIT 5");
while ($row = $dr->fetch_assoc()) $district_dist[] = $row;

// Recent data
$recent_preds = $conn->query("SELECT p.id, p.predicted_premium, p.recommended_plan, p.created_at, u.username FROM predictions p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 6");
$recent_users = $conn->query("SELECT id, username, email, city, created_at FROM users WHERE role='user' ORDER BY id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — CareCalc Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:26px;}
.kpi-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 20px 16px;position:relative;overflow:hidden;transition:box-shadow 0.2s,transform 0.2s;cursor:default;}
.kpi-card:hover{box-shadow:0 12px 36px rgba(10,15,30,0.08);transform:translateY(-2px);}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.kpi-card.c1::before{background:var(--accent);}
.kpi-card.c2::before{background:var(--accent2);}
.kpi-card.c3::before{background:var(--warn);}
.kpi-card.c4::before{background:var(--purple);}
.kpi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:0.95rem;margin-bottom:12px;}
.kpi-card.c1 .kpi-icon{background:var(--accent-glow);}
.kpi-card.c2 .kpi-icon{background:var(--accent2-glow);}
.kpi-card.c3 .kpi-icon{background:rgba(249,115,22,0.1);}
.kpi-card.c4 .kpi-icon{background:rgba(139,92,246,0.1);}
.kpi-label{font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin-bottom:5px;}
.kpi-value{font-family:'JetBrains Mono',monospace;font-size:1.9rem;font-weight:700;color:var(--ink);letter-spacing:-1.5px;line-height:1;}
.kpi-value.sm{font-size:1.3rem;}
.kpi-sub{font-size:0.7rem;color:var(--muted);margin-top:5px;}
.kpi-badge{display:inline-block;background:rgba(0,212,170,0.12);color:#00a87e;font-size:0.62rem;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:5px;}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:22px;}
.grid-32{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:22px;}
.chart-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px;}
.chart-title{font-size:0.8rem;font-weight:700;color:var(--ink);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;}
.chart-title a{font-size:0.7rem;color:var(--accent);text-decoration:none;font-weight:600;}
.chart-title span{font-size:0.68rem;color:var(--muted);font-weight:400;}
.bar-chart{display:flex;align-items:flex-end;gap:8px;height:110px;}
.bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;height:100%;justify-content:flex-end;}
.bar{width:100%;border-radius:5px 5px 0 0;min-height:4px;transition:opacity 0.2s;}
.bar:hover{opacity:0.75;}
.bar-val{font-family:'JetBrains Mono',monospace;font-size:0.6rem;font-weight:600;color:var(--muted);}
.bar-lbl{font-size:0.6rem;color:var(--muted);}
.plan-bars{display:flex;flex-direction:column;gap:10px;}
.plan-bar-row{display:flex;align-items:center;gap:10px;font-size:0.78rem;}
.plan-bar-label{width:70px;color:var(--ink);font-weight:500;}
.plan-bar-track{flex:1;height:8px;background:var(--surface);border-radius:4px;overflow:hidden;}
.plan-bar-fill{height:100%;border-radius:4px;}
.plan-bar-count{width:26px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--muted);}
.dist-list{display:flex;flex-direction:column;gap:8px;}
.dist-row{display:flex;align-items:center;gap:10px;font-size:0.77rem;}
.dist-name{flex:1;color:var(--ink);}
.dist-track{width:70px;height:6px;background:var(--surface);border-radius:3px;overflow:hidden;}
.dist-fill{height:100%;background:var(--accent2);border-radius:3px;}
.dist-val{width:22px;text-align:right;font-family:'JetBrains Mono',monospace;font-size:0.7rem;color:var(--muted);}
.prem-stat{display:flex;flex-direction:column;gap:8px;margin-top:16px;}
.prem-row{display:flex;justify-content:space-between;align-items:center;padding:9px 12px;background:var(--surface);border-radius:var(--radius-sm);}
.prem-lbl{font-size:0.73rem;color:var(--muted);font-weight:500;}
.prem-val{font-family:'JetBrains Mono',monospace;font-size:0.82rem;font-weight:600;color:var(--ink);}
.qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;}
.qa-btn{display:flex;align-items:center;gap:9px;padding:11px 13px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);text-decoration:none;font-size:0.76rem;font-weight:600;color:var(--ink);transition:all 0.18s;}
.qa-btn:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.unread-badge{background:var(--danger);color:#fff;font-size:0.6rem;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:4px;}
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<!-- KPIs -->
<div class="kpi-grid">
<div class="kpi-card c1 fade-in d1">
  <div class="kpi-icon">👥</div>
  <div class="kpi-label">Registered Users</div>
  <div class="kpi-value"><?= $total_users ?></div>
  <div class="kpi-sub">+<?= $new_users_month ?> this month</div>
</div>
<div class="kpi-card c2 fade-in d2">
  <div class="kpi-icon">📊</div>
  <div class="kpi-label">Total Predictions</div>
  <div class="kpi-value"><?= $total_preds ?></div>
  <div class="kpi-sub">+<?= $preds_month ?> this month</div>
</div>
<div class="kpi-card c3 fade-in d3">
  <div class="kpi-icon">💬</div>
  <div class="kpi-label">Messages</div>
  <div class="kpi-value"><?= $total_msgs ?><?php if($unread_msgs): ?><span class="kpi-badge"><?= $unread_msgs ?> new</span><?php endif; ?></div>
  <div class="kpi-sub"><?= $unread_msgs ?> unread</div>
</div>
<div class="kpi-card c4 fade-in d4">
  <div class="kpi-icon">💰</div>
  <div class="kpi-label">Avg Premium</div>
  <div class="kpi-value sm">LKR <?= $avg_premium ?></div>
  <div class="kpi-sub">Max: LKR <?= $max_premium ?></div>
</div>
</div>

<!-- Charts row -->
<div class="grid-2 fade-in d3">
<div class="chart-card">
  <div class="chart-title">Monthly Predictions <span>Last 6 months</span></div>
  <?php $mc = max(array_column($monthly_preds,'count') ?: [1]); $mc = max($mc,1); ?>
  <div class="bar-chart">
  <?php foreach($monthly_preds as $m): $pct = round(($m['count']/$mc)*100); $is_max = ($m['count'] === max(array_column($monthly_preds,'count'))); ?>
    <div class="bar-wrap">
      <div class="bar-val"><?= $m['count'] ?></div>
      <div class="bar" style="height:<?= $pct ?>%;background:<?= $is_max ? 'var(--accent2)' : 'var(--accent)'; ?>;opacity:0.85;"></div>
      <div class="bar-lbl"><?= $m['label'] ?></div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
<div class="chart-card">
  <div class="chart-title">Coverage Plan Distribution</div>
  <?php $tp = array_sum($plan_dist) ?: 1; $pc = ['Basic'=>'#2563ff','Standard'=>'#00d4aa','Premium'=>'#f97316']; ?>
  <div class="plan-bars">
  <?php foreach(['Basic','Standard','Premium'] as $plan): $cnt = $plan_dist[$plan] ?? 0; ?>
    <div class="plan-bar-row">
      <div class="plan-bar-label"><?= $plan ?></div>
      <div class="plan-bar-track"><div class="plan-bar-fill" style="width:<?= round(($cnt/$tp)*100) ?>%;background:<?= $pc[$plan] ?>;"></div></div>
      <div class="plan-bar-count"><?= $cnt ?></div>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="prem-stat">
    <div class="prem-row"><span class="prem-lbl">Average Annual Premium</span><span class="prem-val">LKR <?= $avg_premium ?></span></div>
    <div class="prem-row"><span class="prem-lbl">Highest Premium</span><span class="prem-val">LKR <?= $max_premium ?></span></div>
  </div>
</div>
</div>

<!-- Recent preds + district + quick actions -->
<div class="grid-32 fade-in d4">
<div class="chart-card">
  <div class="chart-title">Recent Predictions <a href="admin_predictions.php">View all →</a></div>
  <table class="cc-table">
    <thead><tr><th>User</th><th>Premium</th><th>Plan</th><th>Date</th></tr></thead>
    <tbody>
    <?php while($p = $recent_preds->fetch_assoc()): ?>
    <tr>
      <td><div class="user-cell"><div class="mini-avatar"><?= strtoupper(substr($p['username'],0,2)) ?></div><?= htmlspecialchars($p['username']) ?></div></td>
      <td class="mono" style="color:var(--ink);font-weight:600;">LKR <?= number_format($p['predicted_premium'],0) ?></td>
      <td><span class="plan-chip plan-<?= strtolower($p['recommended_plan']) ?>"><?= $p['recommended_plan'] ?></span></td>
      <td class="mono"><?= date('d M', strtotime($p['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<div style="display:flex;flex-direction:column;gap:18px;">
  <div class="chart-card">
    <div class="chart-title">Top Districts</div>
    <?php $md = $district_dist ? max(array_column($district_dist,'c')) : 1; ?>
    <div class="dist-list">
    <?php foreach($district_dist as $d): ?>
      <div class="dist-row">
        <div class="dist-name"><?= htmlspecialchars($d['district']) ?></div>
        <div class="dist-track"><div class="dist-fill" style="width:<?= round(($d['c']/$md)*100) ?>%"></div></div>
        <div class="dist-val"><?= $d['c'] ?></div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  <div class="chart-card">
    <div class="chart-title">Quick Actions</div>
    <div class="qa-grid">
      <a href="admin_users.php" class="qa-btn">👥 Users</a>
      <a href="admin_predictions.php" class="qa-btn">📊 Predictions</a>
      <a href="admin_messages.php" class="qa-btn">💬 Messages<?php if($unread_msgs): ?><span class="unread-badge"><?= $unread_msgs ?></span><?php endif; ?></a>
      <a href="admin_reports.php" class="qa-btn">📈 Analytics</a>
    </div>
  </div>
</div>
</div>

<!-- Recent Users -->
<div class="chart-card fade-in d5">
  <div class="chart-title">Recent Registrations <a href="admin_users.php">View all →</a></div>
  <table class="cc-table">
    <thead><tr><th>#</th><th>User</th><th>Email</th><th>City</th><th>Registered</th><th>Actions</th></tr></thead>
    <tbody>
    <?php while($u = $recent_users->fetch_assoc()): ?>
    <tr>
      <td class="mono"><?= $u['id'] ?></td>
      <td><div class="user-cell"><div class="mini-avatar"><?= strtoupper(substr($u['username'],0,2)) ?></div><?= htmlspecialchars($u['username']) ?></div></td>
      <td class="cell-muted"><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['city'] ?? '—') ?></td>
      <td class="mono"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
      <td><a href="admin_users.php?view=<?= $u['id'] ?>" class="tbl-btn">View</a></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

</div></div>
</body></html>