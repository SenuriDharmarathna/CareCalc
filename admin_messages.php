<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit();
}
include "config.php";

// ── MARK READ ─────────────────────────────────────────────────────────────────
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $s = $conn->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?");
    $s->bind_param("i", intval($_GET['read'])); $s->execute(); $s->close();
}

// ── MARK ALL READ ─────────────────────────────────────────────────────────────
if (isset($_GET['read_all'])) {
    $conn->query("UPDATE contact_messages SET is_read=1");
    header("Location: admin_messages.php"); exit();
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $s = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
    $s->bind_param("i", intval($_GET['delete'])); $s->execute(); $s->close();
    header("Location: admin_messages.php?deleted=1"); exit();
}

// ── FILTER ────────────────────────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = []; $types = "";
if ($filter === 'unread') $where .= " AND is_read=0";
if ($filter === 'read')   $where .= " AND is_read=1";
if ($search) {
    $like = "%$search%";
    $where .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $params = [$like,$like,$like]; $types = "sss";
}

$per_page = 15;
$page = max(1,intval($_GET['p'] ?? 1));
$offset = ($page-1)*$per_page;

$cs = $conn->prepare("SELECT COUNT(*) c FROM contact_messages $where");
if ($params) $cs->bind_param($types,...$params);
$cs->execute();
$total_rows = $cs->get_result()->fetch_assoc()['c'];
$cs->close();
$total_pages = ceil($total_rows/$per_page);

$stmt = $conn->prepare("SELECT * FROM contact_messages $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$ap = array_merge($params,[$per_page,$offset]); $at = $types."ii";
$stmt->bind_param($at,...$ap);
$stmt->execute();
$msgs = $stmt->get_result();

$total_msgs  = $conn->query("SELECT COUNT(*) c FROM contact_messages")->fetch_assoc()['c'];
$unread_msgs = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE is_read=0")->fetch_assoc()['c'];

// View single message
$view_msg = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $vs = $conn->prepare("SELECT * FROM contact_messages WHERE id=?");
    $vs->bind_param("i",intval($_GET['view']));
    $vs->execute();
    $view_msg = $vs->get_result()->fetch_assoc();
    $vs->close();
    // Mark as read
    $mr = $conn->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?");
    $mr->bind_param("i",intval($_GET['view'])); $mr->execute(); $mr->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — CareCalc Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
<?php include 'admin_shared.css.php'; ?>
.inbox-layout{display:grid;grid-template-columns:1fr 420px;gap:20px;}
.msg-card{
    background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
    padding:20px 22px;cursor:pointer;transition:all 0.15s;
    border-left:3px solid transparent;
    text-decoration:none;display:block;margin-bottom:0;
}
.msg-card:hover{box-shadow:0 4px 16px rgba(10,15,30,0.07);border-left-color:var(--accent);}
.msg-card.unread{border-left-color:var(--accent);background:#fafbff;}
.msg-card-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:6px;}
.msg-sender{font-size:0.84rem;font-weight:700;color:var(--ink);}
.msg-date{font-size:0.68rem;color:var(--muted);white-space:nowrap;}
.msg-subject{font-size:0.8rem;font-weight:600;color:var(--ink);margin-bottom:4px;}
.msg-preview{font-size:0.76rem;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.msg-email-tag{font-size:0.68rem;color:var(--muted);margin-top:4px;}
.unread-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:5px;}

.detail-card{
    background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
    padding:24px;position:sticky;top:80px;
}
.detail-meta{display:flex;align-items:center;gap:12px;margin-bottom:20px;}
.detail-avatar{
    width:44px;height:44px;border-radius:50%;
    background:linear-gradient(135deg,var(--warn),#fbbf24);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-weight:800;font-size:1rem;flex-shrink:0;
}
.detail-name{font-size:1rem;font-weight:700;color:var(--ink);}
.detail-email-addr{font-size:0.78rem;color:var(--muted);}
.detail-subject{font-size:0.9rem;font-weight:700;color:var(--ink);margin-bottom:14px;}
.detail-body{
    font-size:0.83rem;color:#374151;line-height:1.75;
    background:var(--surface);padding:16px;border-radius:var(--radius-sm);
    margin-bottom:18px;white-space:pre-wrap;
}
.reply-area{width:100%;min-height:80px;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'Sora',sans-serif;font-size:0.82rem;color:var(--ink);resize:vertical;outline:none;}
.reply-area:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow);}

.filter-tabs{display:flex;gap:6px;margin-bottom:14px;}
.ftab{
    padding:6px 16px;border-radius:20px;font-size:0.76rem;font-weight:600;
    border:1px solid var(--border);background:var(--card);color:var(--muted);
    text-decoration:none;transition:all 0.15s;
}
.ftab.active,.ftab:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.ftab.active{background:var(--accent);border-color:var(--accent);}
</style>
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="a-main">
<?php include 'admin_topbar.php'; ?>
<div class="a-content">

<?php if(isset($_GET['deleted'])): ?>
<div class="alert success fade-in">✓ Message deleted.</div>
<?php endif; ?>

<!-- Stats row -->
<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;" class="fade-in d1">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 18px;font-size:0.8rem;">
        Total: <strong><?= $total_msgs ?></strong>
    </div>
    <div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.18);border-radius:var(--radius-sm);padding:12px 18px;font-size:0.8rem;color:var(--danger);">
        Unread: <strong><?= $unread_msgs ?></strong>
    </div>
    <?php if($unread_msgs > 0): ?>
    <a href="?read_all=1" class="tb-btn">✓ Mark All Read</a>
    <?php endif; ?>
    <div style="margin-left:auto;">
        <form method="GET" style="display:flex;gap:8px;">
            <input class="filter-input" style="min-width:200px;" type="text" name="q" placeholder="🔍 Search…" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <button type="submit" class="tb-btn primary">Go</button>
        </form>
    </div>
</div>

<div class="filter-tabs fade-in d2">
    <a href="?filter=all" class="ftab <?= $filter==='all'?'active':'' ?>">All (<?= $total_msgs ?>)</a>
    <a href="?filter=unread" class="ftab <?= $filter==='unread'?'active':'' ?>">Unread (<?= $unread_msgs ?>)</a>
    <a href="?filter=read" class="ftab <?= $filter==='read'?'active':'' ?>">Read</a>
</div>

<div class="inbox-layout fade-in d3">
    <!-- Message list -->
    <div>
    <?php if ($total_rows == 0): ?>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);">
            <div class="empty-state"><div class="empty-icon">💬</div>No messages found.</div>
        </div>
    <?php endif; ?>
    <?php while($m = $msgs->fetch_assoc()): ?>
    <a href="?view=<?= $m['id'] ?>&filter=<?= $filter ?>&q=<?= urlencode($search) ?>"
       class="msg-card <?= !$m['is_read'] ? 'unread' : '' ?>"
       style="margin-bottom:10px;">
        <div class="msg-card-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <?php if(!$m['is_read']): ?><div class="unread-dot"></div><?php endif; ?>
                <span class="msg-sender"><?= htmlspecialchars($m['name']) ?></span>
            </div>
            <span class="msg-date"><?= date('d M Y, H:i', strtotime($m['created_at'])) ?></span>
        </div>
        <div class="msg-subject"><?= htmlspecialchars($m['subject']) ?></div>
        <div class="msg-preview"><?= htmlspecialchars($m['message']) ?></div>
        <div class="msg-email-tag">📧 <?= htmlspecialchars($m['email']) ?></div>
    </a>
    <?php endwhile; ?>

    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-top:10px;">
        <?php if($page>1): ?><a href="?p=<?=$page-1?>&filter=<?=$filter?>&q=<?=urlencode($search)?>" class="page-btn">‹</a><?php endif; ?>
        <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
        <a href="?p=<?=$i?>&filter=<?=$filter?>&q=<?=urlencode($search)?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
        <?php if($page<$total_pages): ?><a href="?p=<?=$page+1?>&filter=<?=$filter?>&q=<?=urlencode($search)?>" class="page-btn">›</a><?php endif; ?>
        <span class="page-info"><?=$total_rows?> messages</span>
    </div>
    <?php endif; ?>
    </div>

    <!-- Detail panel -->
    <div>
    <?php if ($view_msg): ?>
    <div class="detail-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <span style="font-size:0.78rem;font-weight:600;color:var(--muted);">Message #<?= $view_msg['id'] ?></span>
            <a href="?delete=<?= $view_msg['id'] ?>" class="tbl-btn danger" onclick="return confirm('Delete this message?')">Delete</a>
        </div>
        <div class="detail-meta">
            <div class="detail-avatar"><?= strtoupper(substr($view_msg['name'],0,2)) ?></div>
            <div>
                <div class="detail-name"><?= htmlspecialchars($view_msg['name']) ?></div>
                <div class="detail-email-addr">📧 <?= htmlspecialchars($view_msg['email']) ?></div>
                <div style="font-size:0.68rem;color:var(--muted);margin-top:2px;"><?= date('d M Y, H:i', strtotime($view_msg['created_at'])) ?></div>
            </div>
        </div>
        <div class="detail-subject">Re: <?= htmlspecialchars($view_msg['subject']) ?></div>
        <div class="detail-body"><?= htmlspecialchars($view_msg['message']) ?></div>
        <div style="font-size:0.75rem;font-weight:700;color:var(--muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.08em;">Reply via Email</div>
        <textarea class="reply-area" id="replyText" placeholder="Type your reply…"></textarea>
        <a id="replyLink" href="mailto:<?= htmlspecialchars($view_msg['email']) ?>?subject=Re: <?= urlencode($view_msg['subject']) ?>&body=" class="tb-btn primary" style="display:block;text-align:center;margin-top:10px;padding:10px;">📧 Open in Email Client</a>
    </div>
    <?php else: ?>
    <div class="detail-card" style="text-align:center;color:var(--muted);">
        <div style="font-size:2rem;margin-bottom:10px;">💬</div>
        <div style="font-size:0.85rem;">Select a message to read it</div>
    </div>
    <?php endif; ?>
    </div>
</div>

</div></div>
<script>
document.getElementById('replyText')?.addEventListener('input', function() {
    const link = document.getElementById('replyLink');
    const base = link.href.split('&body=')[0];
    link.href = base + '&body=' + encodeURIComponent(this.value);
});
</script>
</body></html>