<?php
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($unread_msgs)) {
    $unread_msgs = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE is_read=0")->fetch_assoc()['c'] ?? 0;
}

$nav = [
    ['file'=>'admin_dashboard.php',   'icon'=>'⬡', 'label'=>'Dashboard',   'section'=>'Overview'],
    ['file'=>'admin_users.php',       'icon'=>'👥', 'label'=>'Users',       'section'=>'Manage'],
    ['file'=>'admin_predictions.php', 'icon'=>'📊', 'label'=>'Predictions', 'section'=>''],
    ['file'=>'admin_plans.php',       'icon'=>'📋', 'label'=>'Plans',       'section'=>''],
    ['file'=>'admin_messages.php',    'icon'=>'💬', 'label'=>'Messages',    'section'=>'', 'badge'=>$unread_msgs],
    ['file'=>'admin_reports.php',     'icon'=>'📈', 'label'=>'Analytics',   'section'=>'Reports'],
    ['file'=>'index.php',             'icon'=>'🌐', 'label'=>'View Site',   'section'=>'System'],
    ['file'=>'admin_logout.php',      'icon'=>'↩',  'label'=>'Logout',      'section'=>'', 'danger'=>true],
];
?>
<aside class="a-sidebar">
  <div class="sb-logo">
    <div class="sb-logo-mark">🩺</div>
    <span>CareCalc</span>
  </div>

  <?php
  $last_section = '';
  foreach ($nav as $item):
    if ($item['section'] && $item['section'] !== $last_section):
      $last_section = $item['section'];
  ?>
    <div class="sb-section"><?= $item['section'] ?></div>
  <?php endif; ?>
    <a href="<?= $item['file'] ?>"
       class="sb-link<?= $current_page === $item['file'] ? ' active' : '' ?>"
       <?= isset($item['danger']) ? 'style="color:rgba(239,68,68,0.55);"' : '' ?>>
      <i class="sb-icon"><?= $item['icon'] ?></i>
      <?= $item['label'] ?>
      <?php if (!empty($item['badge'])): ?>
        <span class="sb-badge"><?= $item['badge'] ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>

  <div class="sb-bottom">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2)) ?></div>
      <div>
        <div class="sb-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
        <div class="sb-user-role">Administrator</div>
      </div>
    </div>
  </div>
</aside>