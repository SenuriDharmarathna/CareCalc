<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

$msg = '';

// ── DELETE user ───────────────────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $s = $conn->prepare("DELETE FROM users WHERE id=? AND role='user'");
    $s->bind_param("i", $del_id); $s->execute(); $s->close();
    $msg = ['type'=>'success','text'=>'User deleted successfully.'];
    header("Location: admin_users.php?deleted=1"); exit();
}

// ── TOGGLE block ─────────────────────────────────────────────────────────────
if (isset($_GET['toggle_block']) && is_numeric($_GET['toggle_block'])) {
    $tid = intval($_GET['toggle_block']);
    $s = $conn->prepare("UPDATE users SET is_blocked = NOT COALESCE(is_blocked,0) WHERE id=?");
    $s->bind_param("i", $tid); $s->execute(); $s->close();
    header("Location: admin_users.php"); exit();
}

// ── EDIT user (POST) ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $eid  = intval($_POST['edit_user_id']);
    $uname = trim($_POST['username'] ?? '');
    $email = trim($_POST['email']    ?? '');
    $city  = trim($_POST['city']     ?? '');
    $cont  = trim($_POST['contact']  ?? '');
    $role  = in_array($_POST['role'],['user','admin']) ? $_POST['role'] : 'user';
    $s = $conn->prepare("UPDATE users SET username=?,email=?,city=?,contact=?,role=? WHERE id=?");
    $s->bind_param("sssssi",$uname,$email,$city,$cont,$role,$eid);
    $ok = $s->execute(); $s->close();
    $msg = $ok ? ['type'=>'success','text'=>"User #{$eid} updated."] : ['type'=>'error','text'=>'Update failed.'];
}

// ── SEARCH / FILTER ───────────────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$filter = $_GET['role_filter'] ?? 'all';

$where = "WHERE 1=1";
$params = [];
$types  = "";
if ($search) {
    $like = "%$search%";
    $where .= " AND (username LIKE ? OR email LIKE ? OR city LIKE ?)";
    $params = [$like,$like,$like]; $types = "sss";
}
if ($filter === 'user')  $where .= " AND role='user'";
if ($filter === 'admin') $where .= " AND role='admin'";

// Pagination
$per_page = 15;
$page = max(1, intval($_GET['p'] ?? 1));
$offset = ($page - 1) * $per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) c FROM users $where");
if ($params) { $count_stmt->bind_param($types, ...$params); }
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['c'];
$count_stmt->close();
$total_pages = ceil($total_rows / $per_page);

$stmt = $conn->prepare("SELECT id,username,email,city,contact,role,created_at,COALESCE(is_blocked,0) is_blocked FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?");
$all_params = array_merge($params, [$per_page, $offset]);
$all_types  = $types . "ii";
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$users = $stmt->get_result();

// View single user detail
$view_user = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $vs = $conn->prepare("SELECT * FROM users WHERE id=?");
    $vs->bind_param("i", intval($_GET['view']));
    $vs->execute();
    $view_user = $vs->get_result()->fetch_assoc();
    $vs->close();
    // Their predictions
    $vp = $conn->prepare("SELECT id,predicted_premium,recommended_plan,coverage_plan,created_at FROM predictions WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
    $vp->bind_param("i", intval($_GET['view']));
    $vp->execute();
    $view_preds = $vp->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — CareCalc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>
.user-detail-panel {
    background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
    padding:28px;margin-bottom:24px;
}
.udp-header{display:flex;align-items:flex-start;gap:18px;margin-bottom:24px;}
.udp-avatar{
    width:56px;height:56px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:800;font-size:1.2rem;
}
.udp-name{font-size:1.1rem;font-weight:700;color:var(--ink);}
.udp-email{font-size:0.8rem;color:var(--muted);margin-top:3px;}
.udp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;}
.udp-field{background:var(--surface);padding:12px 14px;border-radius:var(--radius-sm);}
.udp-field-label{font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:4px;}
.udp-field-val{font-size:0.85rem;color:var(--ink);font-weight:500;}
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<?php if(!empty($msg)): ?>
<div class="alert <?= $msg['type'] ?> fade-in"><?= $msg['text'] ?></div>
<?php endif; ?>
<?php if(isset($_GET['deleted'])): ?>
<div class="alert success fade-in">✓ User deleted successfully.</div>
<?php endif; ?>

<?php if ($view_user): ?>
<!-- ── USER DETAIL VIEW ── -->
<div class="user-detail-panel fade-in d1">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
        <h3 style="font-size:0.9rem;font-weight:700;color:var(--ink);">User Profile</h3>
        <a href="admin_users.php" class="tbl-btn">← Back to Users</a>
    </div>
    <div class="udp-header">
        <div class="udp-avatar"><?= strtoupper(substr($view_user['username'],0,2)) ?></div>
        <div>
            <div class="udp-name"><?= htmlspecialchars($view_user['username']) ?></div>
            <div class="udp-email"><?= htmlspecialchars($view_user['email']) ?></div>
            <div style="margin-top:6px;display:flex;gap:6px;">
                <span class="role-chip <?= $view_user['role'] ?>"><?= $view_user['role'] ?></span>
                <?php if(!empty($view_user['is_blocked'])): ?>
                <span class="status-chip unread">Blocked</span>
                <?php endif; ?>
            </div>
        </div>
        <div style="margin-left:auto;display:flex;gap:8px;">
            <button onclick="openEditModal(<?= $view_user['id'] ?>, '<?= addslashes($view_user['username']) ?>', '<?= addslashes($view_user['email']) ?>', '<?= addslashes($view_user['city'] ?? '') ?>', '<?= addslashes($view_user['contact'] ?? '') ?>', '<?= $view_user['role'] ?>')" class="tbl-btn">✏️ Edit</button>
            <a href="admin_users.php?toggle_block=<?= $view_user['id'] ?>" class="tbl-btn <?= empty($view_user['is_blocked']) ? 'danger' : 'success' ?>"><?= empty($view_user['is_blocked']) ? '🚫 Block' : '✓ Unblock' ?></a>
        </div>
    </div>
    <div class="udp-grid">
        <div class="udp-field"><div class="udp-field-label">User ID</div><div class="udp-field-val mono">#<?= $view_user['id'] ?></div></div>
        <div class="udp-field"><div class="udp-field-label">City</div><div class="udp-field-val"><?= htmlspecialchars($view_user['city'] ?? '—') ?></div></div>
        <div class="udp-field"><div class="udp-field-label">Contact</div><div class="udp-field-val mono"><?= htmlspecialchars($view_user['contact'] ?? '—') ?></div></div>
        <div class="udp-field"><div class="udp-field-label">Registered</div><div class="udp-field-val"><?= date('d M Y, H:i', strtotime($view_user['created_at'])) ?></div></div>
        <div class="udp-field"><div class="udp-field-label">Role</div><div class="udp-field-val"><?= $view_user['role'] ?></div></div>
        <div class="udp-field"><div class="udp-field-label">Status</div><div class="udp-field-val"><?= empty($view_user['is_blocked']) ? 'Active' : 'Blocked' ?></div></div>
    </div>
</div>

<div class="sec-header"><div class="sec-title">Prediction History</div></div>
<div class="tbl-wrap fade-in d2">
<table class="cc-table">
  <thead><tr><th>#</th><th>Predicted Premium</th><th>Recommended Plan</th><th>Coverage Plan</th><th>Date</th></tr></thead>
  <tbody>
  <?php
  $has = false;
  while ($vpr = $view_preds->fetch_assoc()):
    $has = true;
  ?>
  <tr>
    <td class="mono"><?= $vpr['id'] ?></td>
    <td style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--ink);">LKR <?= number_format($vpr['predicted_premium'],0) ?></td>
    <td><span class="plan-chip plan-<?= strtolower($vpr['recommended_plan']) ?>"><?= $vpr['recommended_plan'] ?></span></td>
    <td><?= htmlspecialchars($vpr['coverage_plan']) ?></td>
    <td class="mono"><?= date('d M Y H:i', strtotime($vpr['created_at'])) ?></td>
  </tr>
  <?php endwhile; ?>
  <?php if (!$has): ?><tr><td colspan="5"><div class="empty-state"><div class="empty-icon">📊</div>No predictions yet.</div></td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php else: ?>
<!-- ── USER LIST ── -->
<div class="sec-header">
    <div class="sec-title">All Users <span class="sec-count"><?= $total_rows ?></span></div>
</div>

<div class="tbl-wrap fade-in d1">
    <div class="filter-bar">
        <form method="GET" style="display:flex;align-items:center;gap:10px;width:100%;">
            <input class="filter-input" type="text" name="q" placeholder="🔍  Search by name, email, city…" value="<?= htmlspecialchars($search) ?>">
            <select class="filter-select" name="role_filter" onchange="this.form.submit()">
                <option value="all" <?= $filter==='all' ? 'selected' : '' ?>>All Roles</option>
                <option value="user" <?= $filter==='user' ? 'selected' : '' ?>>Users Only</option>
                <option value="admin" <?= $filter==='admin' ? 'selected' : '' ?>>Admins Only</option>
            </select>
            <button type="submit" class="tb-btn primary">Search</button>
            <?php if ($search || $filter !== 'all'): ?>
            <a href="admin_users.php" class="tb-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="cc-table">
        <thead>
            <tr><th>#</th><th>User</th><th>Email</th><th>City</th><th>Contact</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php
        $row_num = $offset + 1;
        while ($u = $users->fetch_assoc()):
        ?>
        <tr>
            <td class="mono"><?= $u['id'] ?></td>
            <td>
                <div class="user-cell">
                    <div class="mini-avatar" style="<?= !empty($u['is_blocked']) ? 'opacity:0.4;' : '' ?>"><?= strtoupper(substr($u['username'],0,2)) ?></div>
                    <span style="<?= !empty($u['is_blocked']) ? 'color:var(--muted);' : '' ?>"><?= htmlspecialchars($u['username']) ?></span>
                </div>
            </td>
            <td class="cell-muted"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['city'] ?? '—') ?></td>
            <td class="mono"><?= htmlspecialchars($u['contact'] ?? '—') ?></td>
            <td><span class="role-chip <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
            <td>
                <?php if(!empty($u['is_blocked'])): ?>
                <span class="status-chip unread">Blocked</span>
                <?php else: ?>
                <span class="status-chip read">Active</span>
                <?php endif; ?>
            </td>
            <td class="mono"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td style="display:flex;gap:5px;flex-wrap:wrap;">
                <a href="admin_users.php?view=<?= $u['id'] ?>" class="tbl-btn">View</a>
                <button onclick="openEditModal(<?= $u['id'] ?>,'<?= addslashes($u['username']) ?>','<?= addslashes($u['email']) ?>','<?= addslashes($u['city']??'') ?>','<?= addslashes($u['contact']??'') ?>','<?= $u['role'] ?>')" class="tbl-btn">Edit</button>
                <a href="admin_users.php?toggle_block=<?= $u['id'] ?>" class="tbl-btn <?= empty($u['is_blocked']) ? 'danger' : 'success' ?>"><?= empty($u['is_blocked']) ? 'Block' : 'Unblock' ?></a>
                <a href="admin_users.php?delete=<?= $u['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this user and all their data? This cannot be undone.')">Delete</a>
            </td>
        </tr>
        <?php $row_num++; endwhile; ?>
        <?php if ($total_rows === 0): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-icon">👥</div>No users found.</div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?p=<?= $page-1 ?>&q=<?= urlencode($search) ?>&role_filter=<?= $filter ?>" class="page-btn">‹</a><?php endif; ?>
        <?php for($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
        <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>&role_filter=<?= $filter ?>" class="page-btn <?= $i===$page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?><a href="?p=<?= $page+1 ?>&q=<?= urlencode($search) ?>&role_filter=<?= $filter ?>" class="page-btn">›</a><?php endif; ?>
        <span class="page-info">Showing <?= $offset+1 ?>–<?= min($offset+$per_page,$total_rows) ?> of <?= $total_rows ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

</div></div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
<div class="modal" style="position:relative;">
    <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">✕</button>
    <div class="modal-title">Edit User</div>
    <div class="modal-sub">Update user account details and role.</div>
    <form method="POST">
        <input type="hidden" name="edit_user_id" id="edit_uid">
        <div class="f-grid-2">
            <div class="f-field"><label class="f-label">Username</label><input class="f-input" type="text" name="username" id="edit_uname" required></div>
            <div class="f-field"><label class="f-label">Email</label><input class="f-input" type="email" name="email" id="edit_email" required></div>
        </div>
        <div class="f-grid-2">
            <div class="f-field"><label class="f-label">City</label><input class="f-input" type="text" name="city" id="edit_city"></div>
            <div class="f-field"><label class="f-label">Contact</label><input class="f-input" type="text" name="contact" id="edit_contact"></div>
        </div>
        <div class="f-field">
            <label class="f-label">Role</label>
            <select class="f-select" name="role" id="edit_role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="tb-btn primary" style="width:100%;padding:12px;font-size:0.85rem;border-radius:var(--radius-sm);">Save Changes</button>
    </form>
</div>
</div>

<script>
function openEditModal(id, uname, email, city, contact, role) {
    document.getElementById('edit_uid').value     = id;
    document.getElementById('edit_uname').value   = uname;
    document.getElementById('edit_email').value   = email;
    document.getElementById('edit_city').value    = city;
    document.getElementById('edit_contact').value = contact;
    document.getElementById('edit_role').value    = role;
    document.getElementById('editModal').classList.add('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});
</script>
</body></html>