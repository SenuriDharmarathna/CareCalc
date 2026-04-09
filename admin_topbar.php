<?php
$page_titles = [
    'admin_dashboard.php'   => ['Dashboard',   'System overview & analytics'],
    'admin_users.php'       => ['Users',        'Manage registered customers'],
    'admin_predictions.php' => ['Predictions',  'All premium prediction records'],
    'admin_messages.php'    => ['Messages',     'Customer contact messages'],
    'admin_reports.php'     => ['Analytics',    'System reports & statistics'],
];
$cp    = basename($_SERVER['PHP_SELF']);
$title = $page_titles[$cp][0] ?? 'Admin';
$sub   = $page_titles[$cp][1] ?? '';
?>
<div class="a-topbar">
  <div>
    <div class="a-topbar-title"><?= $title ?></div>
    <div class="a-topbar-sub"><?= date('l, F j, Y') ?> &nbsp;·&nbsp; <?= $sub ?></div>
  </div>
  <div class="a-topbar-right">
    <a href="index.php" class="tb-btn">🌐 View Site</a>
    <a href="admin_logout.php" class="tb-btn danger">↩ Logout</a>
  </div>
</div>