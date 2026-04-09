<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

// ── CSV EXPORT ────────────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="carecalc_predictions_'.date('Y-m-d').'.csv"');
    $exp = $conn->query("SELECT p.id, u.username, u.email, p.predicted_premium, p.recommended_plan, p.coverage_plan, p.age, p.gender, p.bmi, p.smoker, p.alcohol_use, p.district, p.annual_income, p.heart_disease, p.diabetes, p.hypertension, p.asthma, p.created_at FROM predictions p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC");
    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Username','Email','Predicted Premium','Recommended Plan','Coverage Plan','Age','Gender','BMI','Smoker','Alcohol','District','Annual Income','Heart Disease','Diabetes','Hypertension','Asthma','Date']);
    while ($row = $exp->fetch_assoc()) fputcsv($out, $row);
    fclose($out); exit();
}

// ── FILTERS ───────────────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$plan_f = $_GET['plan'] ?? 'all';
$sort   = in_array($_GET['sort'] ?? '', ['asc','desc']) ? $_GET['sort'] : 'desc';

$where = "WHERE 1=1";
$params = []; $types = "";
if ($search) {
    $like = "%$search%";
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR p.district LIKE ?)";
    $params = [$like,$like,$like]; $types = "sss";
}
if ($plan_f !== 'all') {
    $where .= " AND p.recommended_plan=?";
    $params[] = $plan_f; $types .= "s";
}

$per_page = 20;
$page = max(1, intval($_GET['p'] ?? 1));
$offset = ($page-1)*$per_page;

$cs = $conn->prepare("SELECT COUNT(*) c FROM predictions p JOIN users u ON p.user_id=u.id $where");
if ($params) $cs->bind_param($types, ...$params);
$cs->execute();
$total_rows = $cs->get_result()->fetch_assoc()['c'];
$cs->close();
$total_pages = ceil($total_rows/$per_page);

$stmt = $conn->prepare("
    SELECT p.id, p.predicted_premium, p.recommended_plan, p.coverage_plan,
           p.age, p.gender, p.bmi, p.district, p.smoker, p.alcohol_use,
           p.heart_disease, p.diabetes, p.hypertension, p.asthma,
           p.annual_income, p.number_of_children, p.hospitalization_last_5yrs,
           p.created_at, u.username, u.email, p.user_id
    FROM predictions p JOIN users u ON p.user_id=u.id
    $where ORDER BY p.created_at $sort LIMIT ? OFFSET ?
");
$ap = array_merge($params, [$per_page, $offset]); $at = $types."ii";
$stmt->bind_param($at, ...$ap);
$stmt->execute();
$preds = $stmt->get_result();

// Stats
$stats = $conn->query("SELECT COUNT(*) total, AVG(predicted_premium) avg, MAX(predicted_premium) mx, MIN(predicted_premium) mn FROM predictions")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Predictions — CareCalc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>
.stats-mini{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.stat-mini{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;}
.stat-mini-label{font-size:0.64rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin-bottom:4px;}
.stat-mini-val{font-family:'JetBrains Mono',monospace;font-size:1.2rem;font-weight:700;color:var(--ink);}
.detail-row{background:var(--surface);}
.detail-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;padding:14px 18px;}
.detail-item{font-size:0.75rem;}
.detail-label{color:var(--muted);margin-bottom:2px;font-size:0.65rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;}
.detail-val{color:var(--ink);font-weight:500;}
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<!-- Stats mini -->
<div class="stats-mini fade-in d1">
    <div class="stat-mini"><div class="stat-mini-label">Total Predictions</div><div class="stat-mini-val"><?= number_format($stats['total']) ?></div></div>
    <div class="stat-mini"><div class="stat-mini-label">Average Premium</div><div class="stat-mini-val">LKR <?= $stats['avg'] ? number_format($stats['avg'],0) : '—' ?></div></div>
    <div class="stat-mini"><div class="stat-mini-label">Highest Premium</div><div class="stat-mini-val">LKR <?= $stats['mx'] ? number_format($stats['mx'],0) : '—' ?></div></div>
    <div class="stat-mini"><div class="stat-mini-label">Lowest Premium</div><div class="stat-mini-val">LKR <?= $stats['mn'] ? number_format($stats['mn'],0) : '—' ?></div></div>
</div>

<div class="sec-header">
    <div class="sec-title">All Predictions <span class="sec-count"><?= $total_rows ?></span></div>
    <a href="?export=1" class="tb-btn primary">⬇ Export CSV</a>
</div>

<div class="tbl-wrap fade-in d2">
    <div class="filter-bar">
        <form method="GET" style="display:flex;align-items:center;gap:10px;width:100%;">
            <input class="filter-input" type="text" name="q" placeholder="🔍  Search user, email, district…" value="<?= htmlspecialchars($search) ?>">
            <select class="filter-select" name="plan" onchange="this.form.submit()">
                <option value="all" <?= $plan_f==='all'?'selected':'' ?>>All Plans</option>
                <option value="Basic" <?= $plan_f==='Basic'?'selected':'' ?>>Basic</option>
                <option value="Standard" <?= $plan_f==='Standard'?'selected':'' ?>>Standard</option>
                <option value="Premium" <?= $plan_f==='Premium'?'selected':'' ?>>Premium</option>
            </select>
            <select class="filter-select" name="sort" onchange="this.form.submit()">
                <option value="desc" <?= $sort==='desc'?'selected':'' ?>>Newest First</option>
                <option value="asc" <?= $sort==='asc'?'selected':'' ?>>Oldest First</option>
            </select>
            <button type="submit" class="tb-btn primary">Apply</button>
            <?php if ($search || $plan_f !== 'all'): ?>
            <a href="admin_predictions.php" class="tb-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="cc-table">
        <thead>
            <tr><th>#</th><th>User</th><th>Premium</th><th>Plan</th><th>Coverage</th><th>District</th><th>Age</th><th>Income</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php while($p = $preds->fetch_assoc()): ?>
        <tr onclick="toggleDetail(<?= $p['id'] ?>)" style="cursor:pointer;">
            <td class="mono"><?= $p['id'] ?></td>
            <td>
                <div class="user-cell">
                    <div class="mini-avatar"><?= strtoupper(substr($p['username'],0,2)) ?></div>
                    <div>
                        <div><?= htmlspecialchars($p['username']) ?></div>
                        <div class="cell-muted"><?= htmlspecialchars($p['email']) ?></div>
                    </div>
                </div>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--ink);">LKR <?= number_format($p['predicted_premium'],0) ?></td>
            <td><span class="plan-chip plan-<?= strtolower($p['recommended_plan']) ?>"><?= $p['recommended_plan'] ?></span></td>
            <td><?= htmlspecialchars($p['coverage_plan']) ?></td>
            <td><?= htmlspecialchars($p['district']) ?></td>
            <td class="mono"><?= $p['age'] ?></td>
            <td class="mono">LKR <?= number_format($p['annual_income'],0) ?></td>
            <td class="mono"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
            <td><span style="color:var(--accent);font-size:0.75rem;font-weight:600;">Details ▾</span></td>
        </tr>
        <tr id="detail-<?= $p['id'] ?>" class="detail-row" style="display:none;">
            <td colspan="10">
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Gender</div><div class="detail-val"><?= $p['gender'] == 1 ? 'Female' : 'Male' ?></div></div>
                    <div class="detail-item"><div class="detail-label">BMI</div><div class="detail-val"><?= $p['bmi'] ?></div></div>
                    <div class="detail-item"><div class="detail-label">Smoker</div><div class="detail-val"><?= $p['smoker'] ? 'Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Alcohol Use</div><div class="detail-val"><?= $p['alcohol_use'] ? 'Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Children</div><div class="detail-val"><?= $p['number_of_children'] ?></div></div>
                    <div class="detail-item"><div class="detail-label">Heart Disease</div><div class="detail-val"><?= $p['heart_disease'] ? '✓ Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Diabetes</div><div class="detail-val"><?= $p['diabetes'] ? '✓ Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Hypertension</div><div class="detail-val"><?= $p['hypertension'] ? '✓ Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Asthma</div><div class="detail-val"><?= $p['asthma'] ? '✓ Yes' : 'No' ?></div></div>
                    <div class="detail-item"><div class="detail-label">Hospitalisations</div><div class="detail-val"><?= $p['hospitalization_last_5yrs'] ?> in 5yrs</div></div>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total_rows == 0): ?>
        <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">📊</div>No predictions found.</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page>1): ?><a href="?p=<?=$page-1?>&q=<?=urlencode($search)?>&plan=<?=$plan_f?>&sort=<?=$sort?>" class="page-btn">‹</a><?php endif; ?>
        <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
        <a href="?p=<?=$i?>&q=<?=urlencode($search)?>&plan=<?=$plan_f?>&sort=<?=$sort?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
        <?php if($page<$total_pages): ?><a href="?p=<?=$page+1?>&q=<?=urlencode($search)?>&plan=<?=$plan_f?>&sort=<?=$sort?>" class="page-btn">›</a><?php endif; ?>
        <span class="page-info">Showing <?=$offset+1?>–<?=min($offset+$per_page,$total_rows)?> of <?=$total_rows?></span>
    </div>
    <?php endif; ?>
</div>

</div></div>
<script>
function toggleDetail(id) {
    const row = document.getElementById('detail-'+id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>
</body></html>