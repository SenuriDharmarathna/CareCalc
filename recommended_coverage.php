<?php
$premium = isset($_GET['premium']) ? floatval($_GET['premium']) : 0;

$plan = "N/A";
$tagline = "";
$plan_key = "none";
$features = [];
$suitability = "";

if ($premium > 0 && $premium < 300000) {
    $plan = "Basic";
    $plan_key = "basic";
    $tagline = "Essential protection for everyday medical needs.";
    $suitability = "Ideal for young, healthy individuals or those looking for a starter medical cover at an accessible premium level.";
    $features = [
        ["label" => "Annual Coverage Limit", "val" => "Rs. 500,000"],
        ["label" => "Ward Accommodation", "val" => "Shared/Standard"],
        ["label" => "ICU Coverage", "val" => "Rs. 100,000"],
        ["label" => "Surgical Expenses", "val" => "Rs. 200,000"],
        ["label" => "OPD per year", "val" => "Rs. 5,000"],
        ["label" => "Maternity Benefits", "val" => "Not included"],
        ["label" => "Dental Coverage", "val" => "Not included"],
    ];
} elseif ($premium >= 300000 && $premium < 700000) {
    $plan = "Standard";
    $plan_key = "standard";
    $tagline = "Balanced coverage for individuals and small families.";
    $suitability = "A great fit for working professionals and small families seeking a balance between cost and comprehensive benefits.";
    $features = [
        ["label" => "Annual Coverage Limit", "val" => "Rs. 1,500,000"],
        ["label" => "Ward Accommodation", "val" => "Private Room"],
        ["label" => "ICU Coverage", "val" => "Rs. 250,000"],
        ["label" => "Surgical Expenses", "val" => "Rs. 450,000"],
        ["label" => "OPD per year", "val" => "Rs. 15,000"],
        ["label" => "Maternity Benefits", "val" => "Rs. 50,000"],
        ["label" => "Dental Coverage", "val" => "Basic included"],
    ];
} elseif ($premium >= 700000) {
    $plan = "Premium";
    $plan_key = "premium";
    $tagline = "Comprehensive VIP-level coverage with maximum benefits.";
    $suitability = "Best suited for higher-risk profiles, frequent travellers or those who prefer maximum peace of mind with VIP-level medical care.";
    $features = [
        ["label" => "Annual Coverage Limit", "val" => "Rs. 5,000,000"],
        ["label" => "Ward Accommodation", "val" => "VIP / Luxury"],
        ["label" => "ICU Coverage", "val" => "Rs. 800,000"],
        ["label" => "Surgical Expenses", "val" => "Rs. 1,500,000"],
        ["label" => "OPD per year", "val" => "Rs. 50,000"],
        ["label" => "Maternity Benefits", "val" => "Rs. 120,000"],
        ["label" => "Dental Coverage", "val" => "Full coverage"],
        ["label" => "Annual Wellness Checkup", "val" => "Included"],
        ["label" => "Emergency Ambulance", "val" => "Included"],
    ];
}

// Colors per plan
$colors = [
    'basic'    => ['accent' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)', 'border' => 'rgba(16,185,129,0.2)', 'icon' => '◎'],
    'standard' => ['accent' => '#2563ff', 'bg' => 'rgba(37,99,255,0.07)', 'border' => 'rgba(37,99,255,0.2)',  'icon' => '◈'],
    'premium'  => ['accent' => '#f97316', 'bg' => 'rgba(249,115,22,0.08)', 'border' => 'rgba(249,115,22,0.2)', 'icon' => '⬡'],
    'none'     => ['accent' => '#7a859e', 'bg' => 'rgba(120,130,150,0.07)', 'border' => 'rgba(120,130,150,0.2)', 'icon' => '—'],
];
$c = $colors[$plan_key];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recommended Coverage — CareCalc</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink: #0a0f1e;
      --surface: #f4f6fb;
      --card: #ffffff;
      --accent: #2563ff;
      --accent-glow: rgba(37,99,255,0.18);
      --accent2: #00d4aa;
      --accent2-glow: rgba(0,212,170,0.15);
      --muted: #7a859e;
      --border: rgba(30,39,64,0.09);
      --radius: 20px;
      --radius-sm: 10px;
      --plan-accent: <?php echo $c['accent']; ?>;
      --plan-bg: <?php echo $c['bg']; ?>;
      --plan-border: <?php echo $c['border']; ?>;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Sora', sans-serif;
      background: var(--surface);
      color: var(--ink);
      min-height: 100vh;
    }

    /* TOPBAR */
    .topbar { background: var(--ink); }
    .topbar-inner {
      max-width: 1200px; margin: 0 auto; padding: 18px 40px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .tb-brand {
      font-size: 1.1rem; font-weight: 800; color: #fff;
      text-decoration: none; display: flex; align-items: center; gap: 8px;
    }
    .tb-nav { display: flex; align-items: center; gap: 4px; }
    .tb-link {
      color: rgba(255,255,255,0.5); font-size: 0.82rem; font-weight: 500;
      text-decoration: none; padding: 7px 14px; border-radius: 50px; transition: all 0.2s;
    }
    .tb-link:hover { color: #fff; background: rgba(255,255,255,0.08); }

    /* HERO */
    .page-hero {
      background: var(--ink);
      padding: 60px 40px 90px;
      position: relative; overflow: hidden;
    }
    .page-hero::before {
      content: ''; position: absolute;
      top: -60px; right: -60px;
      width: 420px; height: 420px;
      background: radial-gradient(circle, var(--plan-bg) 0%, transparent 65%);
      filter: blur(30px); pointer-events: none;
    }
    .hero-inner {
      max-width: 1200px; margin: 0 auto;
      position: relative; z-index: 1;
      display: flex; align-items: flex-end;
      justify-content: space-between; gap: 32px; flex-wrap: wrap;
    }
    .hero-left { }
    .hero-eyebrow {
      font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em;
      text-transform: uppercase; color: var(--accent2); margin-bottom: 14px;
    }
    .hero-title {
      font-size: clamp(1.8rem, 3vw, 2.8rem); font-weight: 800;
      color: #fff; letter-spacing: -1px; line-height: 1.15; margin-bottom: 10px;
    }
    .hero-title span { color: var(--plan-accent); }
    .hero-sub { font-size: 0.85rem; color: rgba(255,255,255,0.4); max-width: 500px; line-height: 1.7; }
    .hero-right { }
    .premium-display {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      padding: 20px 28px;
      text-align: right;
    }
    .premium-display .label { font-size: 0.68rem; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; }
    .premium-display .amount {
      font-family: 'JetBrains Mono', monospace;
      font-size: 1.6rem; font-weight: 700; color: #fff; letter-spacing: -0.5px;
    }
    .premium-display .sublabel { font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 4px; }

    /* CONTENT */
    .content-wrap {
      max-width: 1200px; margin: -44px auto 0;
      padding: 0 40px 80px;
      position: relative; z-index: 2;
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 24px;
      align-items: start;
    }

    /* Main card */
    .main-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1.5px solid var(--plan-border);
      overflow: hidden;
      box-shadow: 0 8px 32px var(--plan-bg);
    }
    .plan-top-strip {
      background: var(--plan-bg);
      padding: 28px 32px;
      border-bottom: 1px solid var(--plan-border);
      display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;
    }
    .plan-badge-big {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
      color: var(--plan-accent); background: rgba(255,255,255,0.85);
      border: 1px solid var(--plan-border); padding: 5px 14px; border-radius: 50px;
      margin-bottom: 10px;
    }
    .plan-name-big { font-size: 1.6rem; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; margin-bottom: 6px; }
    .plan-tagline-big { font-size: 0.85rem; color: var(--muted); }
    .plan-top-right { text-align: right; }
    .plan-top-right .t { font-size: 0.68rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
    .plan-top-right .v {
      font-family: 'JetBrains Mono', monospace;
      font-size: 1.4rem; font-weight: 700; color: var(--ink); letter-spacing: -0.5px;
    }

    /* Features table */
    .features-table { width: 100%; border-collapse: collapse; }
    .features-table thead th {
      padding: 16px 24px; font-size: 0.7rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.08em;
      color: var(--muted); background: rgba(244,246,251,0.8);
      text-align: left; border-bottom: 1px solid var(--border);
    }
    .features-table thead th:last-child { text-align: right; }
    .features-table tbody td {
      padding: 14px 24px; font-size: 0.83rem;
      border-bottom: 1px solid var(--border);
    }
    .features-table tbody tr:last-child td { border-bottom: none; }
    .features-table tbody tr:hover td { background: rgba(244,246,251,0.6); }
    .feat-label { color: var(--muted); }
    .feat-val {
      text-align: right;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.8rem; font-weight: 600; color: var(--ink);
    }
    .feat-val.na { color: rgba(0,0,0,0.2); font-family: 'Sora', sans-serif; font-weight: 400; }

    /* Suitability box */
    .suitability-box {
      margin: 24px 28px;
      background: var(--plan-bg);
      border: 1px solid var(--plan-border);
      border-radius: var(--radius-sm);
      padding: 18px 20px;
    }
    .suitability-box h5 { font-size: 0.8rem; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
    .suitability-box p { font-size: 0.78rem; color: var(--muted); line-height: 1.65; }

    /* SIDEBAR */
    .side-cards { display: flex; flex-direction: column; gap: 18px; }
    .side-card {
      background: var(--card); border-radius: var(--radius);
      border: 1px solid var(--border); padding: 24px;
    }
    .side-card-title {
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.1em; color: var(--muted); margin-bottom: 16px;
    }
    .breakdown-grid {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
    }
    .breakdown-item {
      background: var(--surface); border-radius: var(--radius-sm); padding: 14px; text-align: center;
    }
    .breakdown-val {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.88rem; font-weight: 700; color: var(--ink); letter-spacing: -0.3px;
    }
    .breakdown-label { font-size: 0.65rem; color: var(--muted); margin-top: 3px; }

    .btn-row { display: flex; flex-direction: column; gap: 10px; margin-top: 6px; }
    .btn-side {
      padding: 11px 16px; border-radius: var(--radius-sm);
      font-family: 'Sora', sans-serif; font-size: 0.82rem; font-weight: 600;
      text-decoration: none; text-align: center; transition: all 0.2s;
      cursor: pointer; border: none;
    }
    .btn-side.primary { background: var(--ink); color: #fff; }
    .btn-side.primary:hover { background: var(--accent); color: #fff; }
    .btn-side.ghost { background: var(--surface); color: var(--ink); border: 1px solid var(--border); }
    .btn-side.ghost:hover { background: var(--ink); color: #fff; border-color: var(--ink); }

    /* No prediction state */
    .no-pred {
      background: var(--card); border-radius: var(--radius);
      border: 1px solid var(--border); padding: 60px 40px; text-align: center;
    }
    .no-pred h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; }
    .no-pred p { font-size: 0.85rem; color: var(--muted); max-width: 380px; margin: 0 auto 28px; line-height: 1.7; }

    footer {
      background: var(--ink); color: rgba(255,255,255,0.3);
      text-align: center; padding: 24px; font-size: 0.78rem;
    }

    @media (max-width: 960px) {
      .content-wrap { grid-template-columns: 1fr; }
      .hero-right { display: none; }
      .topbar-inner, .page-hero, .content-wrap { padding-inline: 20px; }
    }
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-inner">
      <a class="tb-brand" href="customer_dashboard.php">🩺 CareCalc</a>
      <nav class="tb-nav">
        <a class="tb-link" href="customer_dashboard.php">Dashboard</a>
        <a class="tb-link" href="coverage_details.php">Coverage Plans</a>
      </nav>
    </div>
  </div>

  <!-- Hero -->
  <div class="page-hero">
    <div class="hero-inner">
      <div class="hero-left">
        <div class="hero-eyebrow">◈ AI Recommendation</div>
        <h1 class="hero-title">
          Your recommended<br>plan is <span><?php echo htmlspecialchars($plan); ?></span>.
        </h1>
        <p class="hero-sub">Based on your predicted premium and health profile, this plan is the best match for your needs.</p>
      </div>
      <?php if ($premium > 0): ?>
      <div class="hero-right">
        <div class="premium-display">
          <div class="label">Predicted Annual Premium</div>
          <div class="amount">Rs. <?php echo number_format($premium, 0); ?></div>
          <div class="sublabel">≈ Rs. <?php echo number_format($premium/12, 0); ?>/month</div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content -->
  <div class="content-wrap">
    <?php if ($premium <= 0): ?>
      <!-- No prediction -->
      <div class="no-pred" style="grid-column: 1 / -1;">
        <h3>No prediction found</h3>
        <p>Please go back to the dashboard, fill in your health profile and generate a premium prediction first.</p>
        <a href="customer_dashboard.php" class="btn-side primary" style="max-width:220px; margin:0 auto; display:block;">← Back to Dashboard</a>
      </div>
    <?php else: ?>

      <!-- Main plan card -->
      <div class="main-card">
        <div class="plan-top-strip">
          <div>
            <div class="plan-badge-big"><?php echo $c['icon']; ?> Recommended: <?php echo htmlspecialchars($plan); ?> Plan</div>
            <div class="plan-name-big"><?php echo htmlspecialchars($plan); ?> Coverage</div>
            <div class="plan-tagline-big"><?php echo htmlspecialchars($tagline); ?></div>
          </div>
          <div class="plan-top-right">
            <div class="t">Your Predicted Premium</div>
            <div class="v">Rs. <?php echo number_format($premium, 2); ?></div>
          </div>
        </div>

        <table class="features-table">
          <thead>
            <tr>
              <th>Coverage Feature</th>
              <th>Included Amount / Detail</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($features as $f): ?>
            <tr>
              <td class="feat-label"><?php echo htmlspecialchars($f['label']); ?></td>
              <td class="feat-val <?php echo str_contains($f['val'], 'Not') ? 'na' : ''; ?>">
                <?php echo htmlspecialchars($f['val']); ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="suitability-box">
          <h5>Is this plan right for you?</h5>
          <p><?php echo htmlspecialchars($suitability); ?> Consider reviewing the full comparison before finalising your choice with an insurance advisor.</p>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="side-cards">

        <div class="side-card">
          <div class="side-card-title">💰 Payment Breakdown</div>
          <div class="breakdown-grid">
            <div class="breakdown-item">
              <div class="breakdown-val">Rs. <?php echo number_format($premium/12, 0); ?></div>
              <div class="breakdown-label">Monthly</div>
            </div>
            <div class="breakdown-item">
              <div class="breakdown-val">Rs. <?php echo number_format($premium/4, 0); ?></div>
              <div class="breakdown-label">Quarterly</div>
            </div>
            <div class="breakdown-item">
              <div class="breakdown-val">Rs. <?php echo number_format($premium/2, 0); ?></div>
              <div class="breakdown-label">Half-Yearly</div>
            </div>
            <div class="breakdown-item">
              <div class="breakdown-val">Rs. <?php echo number_format($premium, 0); ?></div>
              <div class="breakdown-label">Annual</div>
            </div>
          </div>
        </div>

        <div class="side-card">
          <div class="side-card-title">⚡ Next Steps</div>
          <p style="font-size:0.78rem; color:var(--muted); line-height:1.7; margin-bottom:16px;">
            Review the full plan comparison to confirm this is the right fit. Then check your suggested medical reports for the insurer.
          </p>
          <div class="btn-row">
            <a href="coverage_details.php" class="btn-side primary">Compare All Plans →</a>
            <a href="medical_reports.php" class="btn-side ghost">View Medical Reports</a>
            <a href="customer_dashboard.php" class="btn-side ghost">← Dashboard</a>
          </div>
        </div>

      </div>

    <?php endif; ?>
  </div>

  <footer>© 2025 CareCalc | All Rights Reserved</footer>

</body>
</html>