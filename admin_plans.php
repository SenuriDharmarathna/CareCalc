<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

// ── CREATE TABLE IF NOT EXISTS ────────────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS insurance_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        tagline VARCHAR(255),
        color_hex VARCHAR(10) DEFAULT '#2563ff',
        annual_premium_min INT DEFAULT 0,
        annual_premium_max INT DEFAULT 0,
        inpatient_limit INT DEFAULT 0,
        outpatient_limit INT DEFAULT 0,
        surgery_limit INT DEFAULT 0,
        icu_limit INT DEFAULT 0,
        dental_covered TINYINT DEFAULT 0,
        optical_covered TINYINT DEFAULT 0,
        maternity_covered TINYINT DEFAULT 0,
        emergency_covered TINYINT DEFAULT 1,
        pre_existing_covered TINYINT DEFAULT 0,
        waiting_period_months INT DEFAULT 0,
        max_age_limit INT DEFAULT 70,
        features TEXT,
        exclusions TEXT,
        is_active TINYINT DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// ── SEED DEFAULT PLANS IF EMPTY ───────────────────────────────────────────────
$plan_count = $conn->query("SELECT COUNT(*) c FROM insurance_plans")->fetch_assoc()['c'];
if ($plan_count == 0) {
    $conn->query("INSERT INTO insurance_plans (name, slug, tagline, color_hex, annual_premium_min, annual_premium_max, inpatient_limit, outpatient_limit, surgery_limit, icu_limit, dental_covered, optical_covered, maternity_covered, emergency_covered, pre_existing_covered, waiting_period_months, max_age_limit, features, exclusions, is_active, sort_order) VALUES
    ('Basic', 'basic', 'Essential coverage for everyday health needs', '#2563ff', 30000, 120000, 300000, 50000, 150000, 200000, 0, 0, 0, 1, 0, 3, 65, 'Inpatient hospitalisation;Emergency ambulance;24/7 helpline;Basic diagnostics', 'Dental & optical;Cosmetic procedures;Pre-existing conditions;Maternity', 1, 1),
    ('Standard', 'standard', 'Balanced protection for individuals & families', '#00d4aa', 120000, 400000, 750000, 150000, 400000, 500000, 1, 1, 0, 1, 0, 6, 70, 'All Basic benefits;Outpatient consultations;Dental & optical;Specialist referrals;Prescription drugs', 'Cosmetic procedures;Pre-existing conditions (first year);Maternity', 1, 2),
    ('Premium', 'premium', 'Comprehensive cover with zero compromise', '#f97316', 400000, 1200000, 2000000, 400000, 1000000, 1500000, 1, 1, 1, 1, 1, 0, 75, 'All Standard benefits;Maternity & newborn;Pre-existing conditions;International emergency;Annual health check;Mental health support;No waiting period', 'Experimental treatments;Self-inflicted injuries', 1, 3)
    ");
}

$msg = '';

// ── DELETE ────────────────────────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $s = $conn->prepare("DELETE FROM insurance_plans WHERE id=?");
    $s->bind_param("i", intval($_GET['delete'])); $s->execute(); $s->close();
    header("Location: admin_plans.php?deleted=1"); exit();
}

// ── TOGGLE ACTIVE ─────────────────────────────────────────────────────────────
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $s = $conn->prepare("UPDATE insurance_plans SET is_active = 1 - is_active WHERE id=?");
    $s->bind_param("i", intval($_GET['toggle'])); $s->execute(); $s->close();
    header("Location: admin_plans.php?toggled=1"); exit();
}

// ── SAVE (ADD / EDIT) ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $id            = intval($_POST['plan_id'] ?? 0);
    $name          = trim($_POST['name'] ?? '');
    $slug          = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($_POST['slug'] ?? '')));
    $tagline       = trim($_POST['tagline'] ?? '');
    $color_hex     = trim($_POST['color_hex'] ?? '#2563ff');
    $pmin          = intval($_POST['annual_premium_min'] ?? 0);
    $pmax          = intval($_POST['annual_premium_max'] ?? 0);
    $inpatient     = intval($_POST['inpatient_limit'] ?? 0);
    $outpatient    = intval($_POST['outpatient_limit'] ?? 0);
    $surgery       = intval($_POST['surgery_limit'] ?? 0);
    $icu           = intval($_POST['icu_limit'] ?? 0);
    $dental        = isset($_POST['dental_covered'])       ? 1 : 0;
    $optical       = isset($_POST['optical_covered'])      ? 1 : 0;
    $maternity     = isset($_POST['maternity_covered'])    ? 1 : 0;
    $emergency     = isset($_POST['emergency_covered'])    ? 1 : 0;
    $pre_existing  = isset($_POST['pre_existing_covered']) ? 1 : 0;
    $waiting       = intval($_POST['waiting_period_months'] ?? 0);
    $max_age       = intval($_POST['max_age_limit'] ?? 70);
    $features      = trim($_POST['features'] ?? '');
    $exclusions    = trim($_POST['exclusions'] ?? '');
    $sort_order    = intval($_POST['sort_order'] ?? 0);
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    if (!$name || !$slug) {
        $msg = ['type'=>'error','text'=>'Plan name and slug are required.'];
    } else {
        if ($id > 0) {
            // 4s + 13i + 2s + 3i = ssssiiiiiiiiiiiiissiiii → 22 params + id = 22 + 1
            // name,slug,tagline,color_hex = 4s
            // pmin,pmax,inpatient,outpatient,surgery,icu,dental,optical,maternity,emergency,pre_existing,waiting,max_age = 13i
            // features,exclusions = 2s
            // sort_order,is_active,id = 3i
            $s = $conn->prepare("UPDATE insurance_plans SET name=?,slug=?,tagline=?,color_hex=?,annual_premium_min=?,annual_premium_max=?,inpatient_limit=?,outpatient_limit=?,surgery_limit=?,icu_limit=?,dental_covered=?,optical_covered=?,maternity_covered=?,emergency_covered=?,pre_existing_covered=?,waiting_period_months=?,max_age_limit=?,features=?,exclusions=?,sort_order=?,is_active=? WHERE id=?");
            $s->bind_param("ssssiiiiiiiiiiiiissiii",
                $name,$slug,$tagline,$color_hex,
                $pmin,$pmax,$inpatient,$outpatient,$surgery,$icu,
                $dental,$optical,$maternity,$emergency,$pre_existing,$waiting,$max_age,
                $features,$exclusions,
                $sort_order,$is_active,$id);
        } else {
            // name,slug,tagline,color_hex = 4s
            // pmin,pmax,inpatient,outpatient,surgery,icu,dental,optical,maternity,emergency,pre_existing,waiting,max_age = 13i
            // features,exclusions = 2s
            // sort_order,is_active = 2i  → total 21 params
            $s = $conn->prepare("INSERT INTO insurance_plans (name,slug,tagline,color_hex,annual_premium_min,annual_premium_max,inpatient_limit,outpatient_limit,surgery_limit,icu_limit,dental_covered,optical_covered,maternity_covered,emergency_covered,pre_existing_covered,waiting_period_months,max_age_limit,features,exclusions,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("ssssiiiiiiiiiiiiissii",
                $name,$slug,$tagline,$color_hex,
                $pmin,$pmax,$inpatient,$outpatient,$surgery,$icu,
                $dental,$optical,$maternity,$emergency,$pre_existing,$waiting,$max_age,
                $features,$exclusions,
                $sort_order,$is_active);
        }
        if ($s->execute()) {
            $msg = ['type'=>'success','text'=> $id > 0 ? "Plan '{$name}' updated successfully." : "Plan '{$name}' created successfully."];
        } else {
            $msg = ['type'=>'error','text'=>'Database error: ' . $s->error];
        }
        $s->close();
    }
}

// ── FETCH ALL PLANS ───────────────────────────────────────────────────────────
$plans = $conn->query("SELECT * FROM insurance_plans ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);

// ── FETCH PLAN TO EDIT ────────────────────────────────────────────────────────
$edit_plan = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $es = $conn->prepare("SELECT * FROM insurance_plans WHERE id=?");
    $es->bind_param("i", intval($_GET['edit']));
    $es->execute();
    $edit_plan = $es->get_result()->fetch_assoc();
    $es->close();
}

// Prediction count per plan slug
$plan_usage = [];
$ur = $conn->query("SELECT recommended_plan, COUNT(*) c FROM predictions GROUP BY recommended_plan");
if ($ur) while ($row = $ur->fetch_assoc()) $plan_usage[$row['recommended_plan']] = $row['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Insurance Plans — CareCalc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>

/* ── page-specific ── */
.plans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}
.plan-tile {
    background: var(--card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
    position: relative;
}
.plan-tile:hover { box-shadow: 0 12px 36px rgba(10,15,30,0.09); transform: translateY(-2px); }
.plan-tile.inactive { opacity: 0.55; }
.plan-tile-top {
    height: 6px;
}
.plan-tile-body { padding: 22px 22px 18px; }
.plan-tile-name {
    font-size: 1.1rem; font-weight: 800; color: var(--ink);
    display: flex; align-items: center; gap: 10px; margin-bottom: 4px;
}
.plan-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.plan-tile-tag { font-size: 0.74rem; color: var(--muted); margin-bottom: 16px; }
.plan-stat-row {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 8px; margin-bottom: 14px;
}
.plan-stat {
    background: var(--surface); border-radius: var(--radius-sm);
    padding: 9px 12px;
}
.plan-stat-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 3px; }
.plan-stat-val { font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; font-weight: 600; color: var(--ink); }
.plan-coverage-pills {
    display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 14px;
}
.cov-pill {
    font-size: 0.62rem; font-weight: 600; padding: 2px 8px; border-radius: 20px;
    border: 1px solid;
}
.cov-pill.yes { background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.25); color: #059669; }
.cov-pill.no  { background: rgba(239,68,68,0.05);  border-color: rgba(239,68,68,0.15);  color: #dc2626; }
.plan-tile-actions {
    display: flex; gap: 8px; padding: 12px 22px;
    border-top: 1px solid var(--border); background: var(--surface);
}
.usage-badge {
    margin-left: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.65rem; font-weight: 600;
    color: var(--muted); padding: 3px 9px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: 20px;
}
.inactive-tag {
    position: absolute; top: 14px; right: 14px;
    font-size: 0.6rem; font-weight: 700; letter-spacing: 0.07em;
    text-transform: uppercase;
    background: rgba(239,68,68,0.1); color: var(--danger);
    padding: 2px 8px; border-radius: 20px;
}

/* ── form modal ── */
.plan-form-wrap {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 30px 32px;
    margin-bottom: 28px;
    animation: fi 0.3s ease forwards;
}
.plan-form-title { font-size: 1rem; font-weight: 800; color: var(--ink); margin-bottom: 4px; }
.plan-form-sub   { font-size: 0.78rem; color: var(--muted); margin-bottom: 24px; }
.form-row { display: grid; gap: 16px; margin-bottom: 18px; }
.form-row.cols-3 { grid-template-columns: repeat(3, 1fr); }
.form-row.cols-4 { grid-template-columns: repeat(4, 1fr); }
.form-row.cols-2 { grid-template-columns: repeat(2, 1fr); }

.ff { display: flex; flex-direction: column; gap: 5px; }
.ff label { font-size: 0.64rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: var(--muted); }
.ff input[type=text],
.ff input[type=number],
.ff input[type=color],
.ff textarea {
    padding: 10px 13px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-family: 'Sora', sans-serif;
    font-size: 0.84rem; color: var(--ink);
    background: var(--surface); outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.ff input:focus, .ff textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-glow);
    background: #fff;
}
.ff input[type=color] { padding: 4px 8px; height: 42px; cursor: pointer; }
.ff textarea { resize: vertical; min-height: 80px; }

.check-row { display: flex; flex-wrap: wrap; gap: 10px; padding: 14px 0; }
.check-item { display: flex; align-items: center; gap: 7px; cursor: pointer; }
.check-item input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }
.check-item span { font-size: 0.8rem; font-weight: 500; color: var(--ink); }

.section-divider {
    font-size: 0.64rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.12em; color: var(--muted); margin: 20px 0 14px;
    display: flex; align-items: center; gap: 10px;
}
.section-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.form-actions { display: flex; gap: 10px; margin-top: 24px; }

.limit-hint { font-size: 0.62rem; color: var(--muted); margin-top: 3px; }
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<?php if (isset($_GET['deleted'])): ?>
<div class="alert error fade-in">Plan deleted.</div>
<?php endif; ?>
<?php if (isset($_GET['toggled'])): ?>
<div class="alert success fade-in">Plan status updated.</div>
<?php endif; ?>
<?php if ($msg): ?>
<div class="alert <?= $msg['type'] ?> fade-in"><?= htmlspecialchars($msg['text']) ?></div>
<?php endif; ?>

<!-- Header -->
<div class="sec-header fade-in d1">
    <div class="sec-title">
        Insurance Plans
        <span class="sec-count"><?= count($plans) ?> plans</span>
    </div>
    <button class="tb-btn primary" onclick="toggleForm()">＋ Add New Plan</button>
</div>

<!-- ── ADD / EDIT FORM ──────────────────────────────────────────────────────── -->
<div class="plan-form-wrap fade-in d2" id="planFormWrap" style="<?= $edit_plan ? '' : 'display:none;' ?>">
    <div class="plan-form-title"><?= $edit_plan ? '✏️ Edit Plan: ' . htmlspecialchars($edit_plan['name']) : '＋ Create New Insurance Plan' ?></div>
    <div class="plan-form-sub">Define coverage limits, features, and pricing for this plan. Customers will see this on the Coverage Plans page.</div>

    <form method="POST" action="">
        <input type="hidden" name="plan_id" value="<?= $edit_plan['id'] ?? 0 ?>">

        <!-- Basic info -->
        <div class="form-row cols-3">
            <div class="ff">
                <label>Plan Name *</label>
                <input type="text" name="name" placeholder="e.g. Premium Plus" value="<?= htmlspecialchars($edit_plan['name'] ?? '') ?>" required>
            </div>
            <div class="ff">
                <label>Slug * <span class="limit-hint">(URL-safe, unique)</span></label>
                <input type="text" name="slug" placeholder="e.g. premium-plus" value="<?= htmlspecialchars($edit_plan['slug'] ?? '') ?>" required>
            </div>
            <div class="ff">
                <label>Brand Color</label>
                <input type="color" name="color_hex" value="<?= htmlspecialchars($edit_plan['color_hex'] ?? '#2563ff') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="ff">
                <label>Tagline / Short Description</label>
                <input type="text" name="tagline" placeholder="e.g. Comprehensive cover for peace of mind" value="<?= htmlspecialchars($edit_plan['tagline'] ?? '') ?>">
            </div>
        </div>

        <!-- Pricing -->
        <div class="section-divider">Premium Range</div>
        <div class="form-row cols-4">
            <div class="ff">
                <label>Min Annual Premium (LKR)</label>
                <input type="number" name="annual_premium_min" value="<?= $edit_plan['annual_premium_min'] ?? 0 ?>" min="0">
            </div>
            <div class="ff">
                <label>Max Annual Premium (LKR)</label>
                <input type="number" name="annual_premium_max" value="<?= $edit_plan['annual_premium_max'] ?? 0 ?>" min="0">
            </div>
            <div class="ff">
                <label>Waiting Period (months)</label>
                <input type="number" name="waiting_period_months" value="<?= $edit_plan['waiting_period_months'] ?? 0 ?>" min="0" max="24">
            </div>
            <div class="ff">
                <label>Max Entry Age</label>
                <input type="number" name="max_age_limit" value="<?= $edit_plan['max_age_limit'] ?? 70 ?>" min="18" max="100">
            </div>
        </div>

        <!-- Coverage limits -->
        <div class="section-divider">Coverage Limits (LKR)</div>
        <div class="form-row cols-4">
            <div class="ff">
                <label>Inpatient Limit</label>
                <input type="number" name="inpatient_limit" value="<?= $edit_plan['inpatient_limit'] ?? 0 ?>" min="0">
            </div>
            <div class="ff">
                <label>Outpatient Limit</label>
                <input type="number" name="outpatient_limit" value="<?= $edit_plan['outpatient_limit'] ?? 0 ?>" min="0">
            </div>
            <div class="ff">
                <label>Surgery Limit</label>
                <input type="number" name="surgery_limit" value="<?= $edit_plan['surgery_limit'] ?? 0 ?>" min="0">
            </div>
            <div class="ff">
                <label>ICU / Critical Care Limit</label>
                <input type="number" name="icu_limit" value="<?= $edit_plan['icu_limit'] ?? 0 ?>" min="0">
            </div>
        </div>

        <!-- Coverage toggles -->
        <div class="section-divider">Coverage Inclusions</div>
        <div class="check-row">
            <?php
            $checks = [
                'dental_covered'       => '🦷 Dental',
                'optical_covered'      => '👁️ Optical',
                'maternity_covered'    => '🍼 Maternity',
                'emergency_covered'    => '🚑 Emergency',
                'pre_existing_covered' => '💊 Pre-existing Conditions',
            ];
            foreach ($checks as $fname => $flabel):
                $checked = isset($edit_plan[$fname]) ? (bool)$edit_plan[$fname] : ($fname === 'emergency_covered');
            ?>
            <label class="check-item">
                <input type="checkbox" name="<?= $fname ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= $flabel ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Features / Exclusions -->
        <div class="section-divider">Features & Exclusions</div>
        <div class="form-row cols-2">
            <div class="ff">
                <label>Key Features <span class="limit-hint">(one per line or semicolon-separated)</span></label>
                <textarea name="features" placeholder="Inpatient hospitalisation&#10;Emergency ambulance&#10;24/7 helpline"><?= htmlspecialchars($edit_plan['features'] ?? '') ?></textarea>
            </div>
            <div class="ff">
                <label>Exclusions <span class="limit-hint">(one per line or semicolon-separated)</span></label>
                <textarea name="exclusions" placeholder="Cosmetic procedures&#10;Self-inflicted injuries"><?= htmlspecialchars($edit_plan['exclusions'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Sort & status -->
        <div class="form-row cols-2">
            <div class="ff">
                <label>Sort Order <span class="limit-hint">(lower = shown first)</span></label>
                <input type="number" name="sort_order" value="<?= $edit_plan['sort_order'] ?? 0 ?>" min="0">
            </div>
            <div class="ff" style="justify-content:flex-end;">
                <label>&nbsp;</label>
                <label class="check-item" style="margin-top:8px;">
                    <input type="checkbox" name="is_active" <?= (!isset($edit_plan) || (isset($edit_plan['is_active']) && $edit_plan['is_active'])) ? 'checked' : '' ?>>
                    <span style="font-size:0.85rem;font-weight:600;">Active (visible to customers)</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" name="save_plan" class="tb-btn primary">
                <?= $edit_plan ? '✓ Save Changes' : '＋ Create Plan' ?>
            </button>
            <a href="admin_plans.php" class="tb-btn">Cancel</a>
        </div>
    </form>
</div>

<!-- ── PLAN TILES GRID ───────────────────────────────────────────────────────── -->
<?php if (empty($plans)): ?>
<div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);">
    <div class="empty-state"><div class="empty-icon">📋</div>No plans yet. Create your first plan above.</div>
</div>
<?php else: ?>
<div class="plans-grid">
<?php foreach ($plans as $p):
    $feats = array_filter(array_map('trim', preg_split('/[;\n]/', $p['features'] ?? '')));
    $excls = array_filter(array_map('trim', preg_split('/[;\n]/', $p['exclusions'] ?? '')));
    $usage = $plan_usage[$p['name']] ?? 0;
    $color = htmlspecialchars($p['color_hex'] ?: '#2563ff');
?>
<div class="plan-tile fade-in <?= !$p['is_active'] ? 'inactive' : '' ?>">
    <div class="plan-tile-top" style="background:<?= $color ?>;"></div>
    <?php if (!$p['is_active']): ?><div class="inactive-tag">Inactive</div><?php endif; ?>
    <div class="plan-tile-body">
        <div class="plan-tile-name">
            <div class="plan-dot" style="background:<?= $color ?>;"></div>
            <?= htmlspecialchars($p['name']) ?>
            <span style="font-size:0.65rem;font-weight:400;color:var(--muted);font-family:'JetBrains Mono',monospace;">#<?= $p['id'] ?></span>
        </div>
        <div class="plan-tile-tag"><?= htmlspecialchars($p['tagline'] ?: '—') ?></div>

        <div class="plan-stat-row">
            <div class="plan-stat">
                <div class="plan-stat-label">Premium Range</div>
                <div class="plan-stat-val" style="font-size:0.7rem;">
                    LKR <?= number_format($p['annual_premium_min']/1000,0) ?>k
                    – <?= number_format($p['annual_premium_max']/1000,0) ?>k
                </div>
            </div>
            <div class="plan-stat">
                <div class="plan-stat-label">Inpatient Limit</div>
                <div class="plan-stat-val">LKR <?= number_format($p['inpatient_limit']/1000,0) ?>k</div>
            </div>
            <div class="plan-stat">
                <div class="plan-stat-label">Surgery Limit</div>
                <div class="plan-stat-val">LKR <?= number_format($p['surgery_limit']/1000,0) ?>k</div>
            </div>
            <div class="plan-stat">
                <div class="plan-stat-label">Waiting Period</div>
                <div class="plan-stat-val"><?= $p['waiting_period_months'] ?> mo<?= $p['waiting_period_months'] == 1 ? '' : 's' ?></div>
            </div>
        </div>

        <div class="plan-coverage-pills">
            <?php
            $cov_items = [
                'dental_covered'       => '🦷 Dental',
                'optical_covered'      => '👁 Optical',
                'maternity_covered'    => '🍼 Maternity',
                'emergency_covered'    => '🚑 Emergency',
                'pre_existing_covered' => '💊 Pre-existing',
            ];
            foreach ($cov_items as $k => $lbl):
            ?>
            <span class="cov-pill <?= $p[$k] ? 'yes' : 'no' ?>"><?= $p[$k] ? '✓' : '✕' ?> <?= $lbl ?></span>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($feats)): ?>
        <div style="margin-bottom:8px;">
            <div style="font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:6px;">Key Features</div>
            <?php foreach(array_slice($feats, 0, 3) as $f): ?>
            <div style="font-size:0.74rem;color:var(--ink);padding:2px 0;">✓ <?= htmlspecialchars($f) ?></div>
            <?php endforeach; ?>
            <?php if (count($feats) > 3): ?>
            <div style="font-size:0.7rem;color:var(--muted);margin-top:3px;">+<?= count($feats)-3 ?> more</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="plan-tile-actions">
        <a href="?edit=<?= $p['id'] ?>" class="tbl-btn" onclick="document.getElementById('planFormWrap').style.display='block';">✏️ Edit</a>
        <a href="?toggle=<?= $p['id'] ?>" class="tbl-btn <?= $p['is_active'] ? '' : 'success' ?>"
           onclick="return confirm('<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?> this plan?')">
            <?= $p['is_active'] ? '⏸ Deactivate' : '▶ Activate' ?>
        </a>
        <a href="?delete=<?= $p['id'] ?>" class="tbl-btn danger"
           onclick="return confirm('Delete «<?= htmlspecialchars(addslashes($p['name'])) ?>»? This cannot be undone.')">🗑 Delete</a>
        <span class="usage-badge">👥 <?= $usage ?> predictions</span>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── SUMMARY TABLE ─────────────────────────────────────────────────────────── -->
<div class="tbl-wrap fade-in d4">
    <div style="padding:16px 20px 12px;font-size:0.82rem;font-weight:700;color:var(--ink);border-bottom:1px solid var(--border);">
        Plans Summary Table
    </div>
    <table class="cc-table">
        <thead>
            <tr>
                <th>#</th><th>Name</th><th>Status</th><th>Premium Range</th>
                <th>Inpatient</th><th>Outpatient</th><th>ICU</th>
                <th>Waiting</th><th>Max Age</th><th>Usage</th><th>Updated</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
        <tr>
            <td class="mono"><?= $p['id'] ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($p['color_hex']) ?>;flex-shrink:0;"></div>
                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                </div>
            </td>
            <td>
                <span class="status-chip <?= $p['is_active'] ? 'read' : 'unread' ?>">
                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </td>
            <td class="mono">LKR <?= number_format($p['annual_premium_min']) ?>–<?= number_format($p['annual_premium_max']) ?></td>
            <td class="mono">LKR <?= number_format($p['inpatient_limit']) ?></td>
            <td class="mono">LKR <?= number_format($p['outpatient_limit']) ?></td>
            <td class="mono">LKR <?= number_format($p['icu_limit']) ?></td>
            <td class="mono"><?= $p['waiting_period_months'] ?> mo</td>
            <td class="mono"><?= $p['max_age_limit'] ?> yrs</td>
            <td class="mono"><?= $plan_usage[$p['name']] ?? 0 ?></td>
            <td class="mono"><?= date('d M Y', strtotime($p['updated_at'])) ?></td>
            <td style="display:flex;gap:6px;">
                <a href="?edit=<?= $p['id'] ?>" class="tbl-btn" onclick="document.getElementById('planFormWrap').style.display='block';window.scrollTo(0,0);">Edit</a>
                <a href="?delete=<?= $p['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this plan?')">Del</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div></div>

<script>
function toggleForm() {
    const w = document.getElementById('planFormWrap');
    w.style.display = w.style.display === 'none' ? 'block' : 'none';
    if (w.style.display === 'block') w.scrollIntoView({behavior:'smooth', block:'start'});
}

// Auto-generate slug from name
document.querySelector('[name=name]')?.addEventListener('input', function() {
    const slugField = document.querySelector('[name=slug]');
    if (!slugField.dataset.manual) {
        slugField.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.querySelector('[name=slug]')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});

// Scroll to form if editing
<?php if ($edit_plan): ?>
document.getElementById('planFormWrap').scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>
</script>
</body>
</html>