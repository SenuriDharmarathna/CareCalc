<?php
session_start();

if (!isset($_SESSION['has_prediction']) || !$_SESSION['has_prediction']) {
    header("Location: customer_dashboard.php");
    exit();
}

$input = isset($_SESSION['prediction_input']) ? $_SESSION['prediction_input'] : null;
$predicted_value = isset($_SESSION['predicted_value']) ? $_SESSION['predicted_value'] : null;

if (!$input) {
    header("Location: customer_dashboard.php");
    exit();
}

$age          = (int) ($input["Age"] ?? 0);
$bmi          = (float) ($input["BMI"] ?? 0);
$smoker       = (int) ($input["Smoker"] ?? 0);
$alcohol_use  = (int) ($input["Alcohol_Use"] ?? 0);
$heart_dis    = (int) ($input["Heart_Disease"] ?? 0);
$diabetes     = (int) ($input["Diabetes"] ?? 0);
$hypertension = (int) ($input["Hypertension"] ?? 0);
$asthma       = (int) ($input["Asthma"] ?? 0);
$hosp         = (int) ($input["Hospitalization_Last_5Yrs"] ?? 0);

$risk_level = "Low";
$risk_color = "#10b981";
$risk_bg = "rgba(16,185,129,0.08)";
$risk_border = "rgba(16,185,129,0.2)";
$risk_icon = "◎";
$risk_desc = "Your profile shows low overall insurance risk. Only basic health screening is typically required.";

if ($age > 50 || $heart_dis == 1 || ($diabetes == 1 && $hypertension == 1) || $hosp >= 3 || $bmi > 32 || $bmi < 18) {
    $risk_level = "High";
    $risk_color = "#ef4444";
    $risk_bg = "rgba(239,68,68,0.07)";
    $risk_border = "rgba(239,68,68,0.2)";
    $risk_icon = "⬡";
    $risk_desc = "Your profile indicates a higher insurance risk category. More detailed medical reports are usually required for underwriting.";
} elseif ($age >= 35 || $smoker == 1 || $alcohol_use == 1 || $diabetes == 1 || $hypertension == 1 || $asthma == 1 || $hosp > 0) {
    $risk_level = "Moderate";
    $risk_color = "#f97316";
    $risk_bg = "rgba(249,115,22,0.08)";
    $risk_border = "rgba(249,115,22,0.2)";
    $risk_icon = "◈";
    $risk_desc = "Your profile suggests a moderate insurance risk. Some additional medical checks may be required.";
}

$reports = [];
if ($risk_level === "Low") {
    $reports = [
        ["name" => "Full Blood Count (FBC / CBC)", "note" => "General haematological screening"],
        ["name" => "Fasting Blood Sugar (FBS)", "note" => "Baseline glucose assessment"],
        ["name" => "Lipid Profile", "note" => "Cholesterol, LDL, HDL, Triglycerides"],
        ["name" => "Urine Full Report (UFR)", "note" => "Routine urinalysis"],
        ["name" => "Liver Function Tests (LFT)", "note" => "Basic hepatic panel"],
        ["name" => "Renal Function Tests (RFT)", "note" => "Basic kidney function"],
    ];
    if ($bmi >= 27) {
        $reports[] = ["name" => "HbA1c", "note" => "Long-term blood sugar control — recommended due to BMI"];
    }
} elseif ($risk_level === "Moderate") {
    $reports = [
        ["name" => "Full Blood Count (FBC / CBC)", "note" => "General haematological screening"],
        ["name" => "Fasting Blood Sugar (FBS) + HbA1c", "note" => "Glucose and long-term glycaemic control"],
        ["name" => "Lipid Profile", "note" => "Cholesterol and cardiovascular risk markers"],
        ["name" => "Urine Full Report (UFR)", "note" => "Routine urinalysis"],
        ["name" => "Liver & Renal Function Tests", "note" => "LFT and RFT combined panel"],
        ["name" => "ECG (Electrocardiogram)", "note" => "Baseline cardiac assessment"],
        ["name" => "Chest X-Ray (PA view)", "note" => "Pulmonary and cardiac baseline"],
    ];
    if ($diabetes == 1) {
        $reports[] = ["name" => "Random Blood Sugar (RBS)", "note" => "Supplemental glucose check"];
        $reports[] = ["name" => "Urine Microalbumin", "note" => "Kidney screening in diabetes"];
    }
    if ($hypertension == 1) {
        $reports[] = ["name" => "Urine Protein / Microalbumin", "note" => "Hypertension-related kidney impact"];
    }
    if ($alcohol_use == 1) {
        $reports[] = ["name" => "Detailed Liver Function Test (incl. GGT)", "note" => "Alcohol-related hepatic assessment"];
        $reports[] = ["name" => "Abdominal Ultrasound", "note" => "Liver assessment — if clinically indicated"];
    }
} else {
    $reports = [
        ["name" => "Full Blood Count (FBC / CBC)", "note" => "Complete haematological panel"],
        ["name" => "Fasting & Random Blood Sugar + HbA1c", "note" => "Comprehensive glycaemic assessment"],
        ["name" => "Full Lipid Profile", "note" => "Extended cardiovascular risk markers"],
        ["name" => "Urine Full Report & Microalbumin", "note" => "Complete urinalysis with protein screening"],
        ["name" => "Extended Liver Function Tests", "note" => "LFT including GGT"],
        ["name" => "Extended Renal Function Tests", "note" => "RFT and eGFR"],
        ["name" => "ECG (Electrocardiogram)", "note" => "Cardiac baseline"],
        ["name" => "2D Echo / Stress ECG", "note" => "If advised by cardiologist"],
        ["name" => "Chest X-Ray (PA view)", "note" => "Pulmonary and cardiac screening"],
        ["name" => "Abdominal Ultrasound Scan", "note" => "Abdominal organ assessment"],
    ];
    if ($asthma == 1 || $smoker == 1) {
        $reports[] = ["name" => "Pulmonary Function Test (Spirometry)", "note" => "If respiratory symptoms present"];
    }
    if ($heart_dis == 1) {
        $reports[] = ["name" => "Cardiologist Report / Clinic Summary", "note" => "Specialist documentation required"];
    }
    if ($diabetes == 1) {
        $reports[] = ["name" => "Diabetologist / Endocrinologist Report", "note" => "Specialist documentation required"];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Reports — CareCalc</title>
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
      --warn: #f97316;
      --muted: #7a859e;
      --border: rgba(30,39,64,0.09);
      --radius: 20px;
      --radius-sm: 10px;
      --risk-color: <?php echo $risk_color; ?>;
      --risk-bg: <?php echo $risk_bg; ?>;
      --risk-border: <?php echo $risk_border; ?>;
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
      max-width: 1200px; margin: 0 auto;
      padding: 18px 40px;
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
    .tb-link:hover, .tb-link.active { color: #fff; background: rgba(255,255,255,0.08); }

    /* PAGE HEADER */
    .page-header {
      background: var(--ink);
      padding: 56px 40px 80px;
      position: relative; overflow: hidden;
    }
    .page-header::before {
      content: ''; position: absolute;
      top: -60px; right: -80px;
      width: 380px; height: 380px;
      background: radial-gradient(circle, var(--risk-bg) 0%, transparent 65%);
      filter: blur(20px); pointer-events: none;
    }
    .ph-inner { max-width: 1200px; margin: 0 auto; position: relative; z-index: 1; }
    .ph-eyebrow {
      font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em;
      text-transform: uppercase; color: var(--accent2); margin-bottom: 14px;
    }
    .ph-title {
      font-size: clamp(1.8rem, 3vw, 2.6rem); font-weight: 800;
      color: #fff; letter-spacing: -1px; line-height: 1.15; margin-bottom: 12px;
    }
    .ph-sub { font-size: 0.85rem; color: rgba(255,255,255,0.4); max-width: 520px; line-height: 1.7; }

    /* MAIN CONTENT */
    .main-wrap {
      max-width: 1200px; margin: -40px auto 0;
      padding: 0 40px 80px;
      position: relative; z-index: 2;
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 24px;
      align-items: start;
    }

    /* Risk card */
    .risk-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      overflow: hidden;
      margin-bottom: 20px;
    }
    .risk-header {
      padding: 28px 32px;
      background: var(--risk-bg);
      border-bottom: 1px solid var(--risk-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }
    .risk-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--risk-color);
      background: rgba(255,255,255,0.8);
      border: 1px solid var(--risk-border);
      padding: 5px 14px;
      border-radius: 50px;
      margin-bottom: 10px;
    }
    .risk-header-left h3 {
      font-size: 1.1rem; font-weight: 700; color: var(--ink); margin-bottom: 6px;
    }
    .risk-header-left p { font-size: 0.82rem; color: var(--muted); line-height: 1.6; max-width: 480px; }
    .risk-premium-box { text-align: right; }
    .risk-premium-box .label { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
    .risk-premium-box .amount {
      font-family: 'JetBrains Mono', monospace;
      font-size: 1.4rem; font-weight: 700; color: var(--ink); letter-spacing: -0.5px;
    }

    /* Profile chips */
    .profile-chips {
      padding: 20px 32px;
      display: flex; flex-wrap: wrap; gap: 8px;
      border-bottom: 1px solid var(--border);
    }
    .chip {
      font-size: 0.72rem; font-weight: 500;
      padding: 5px 12px; border-radius: 50px;
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--ink);
      display: flex; align-items: center; gap: 5px;
    }
    .chip-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent2); }
    .chip.flag { background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.2); }
    .chip.flag .chip-dot { background: #ef4444; }

    /* Reports list */
    .reports-section { padding: 28px 32px; }
    .reports-section h4 {
      font-size: 0.9rem; font-weight: 700; color: var(--ink);
      letter-spacing: -0.2px; margin-bottom: 18px;
    }
    .report-item {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 14px 0;
      border-bottom: 1px solid var(--border);
      animation: slideUp 0.4s ease both;
    }
    .report-item:last-child { border-bottom: none; }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .report-num {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.65rem;
      font-weight: 500;
      color: rgba(255,255,255,0.6);
      background: var(--ink);
      width: 24px; height: 24px;
      border-radius: 6px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .report-body { flex: 1; }
    .report-body h5 { font-size: 0.85rem; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
    .report-body p { font-size: 0.75rem; color: var(--muted); }

    /* Disclaimer */
    .disclaimer {
      margin: 0 32px 28px;
      background: rgba(249,115,22,0.07);
      border: 1px solid rgba(249,115,22,0.18);
      border-radius: var(--radius-sm);
      padding: 14px 16px;
      font-size: 0.76rem;
      color: #92400e;
      line-height: 1.6;
    }
    .disclaimer strong { font-weight: 700; }

    /* SIDEBAR */
    .sidebar-col { display: flex; flex-direction: column; gap: 18px; }
    .side-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 24px;
    }
    .side-card-title {
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.1em; color: var(--muted); margin-bottom: 16px;
    }
    .side-stat {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0; border-bottom: 1px solid var(--border);
      font-size: 0.8rem;
    }
    .side-stat:last-child { border-bottom: none; padding-bottom: 0; }
    .side-stat-key { color: var(--muted); }
    .side-stat-val {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.78rem; font-weight: 600; color: var(--ink);
    }
    .side-stat-val.risk-high { color: #ef4444; font-family: 'Sora', sans-serif; }
    .side-stat-val.risk-mod { color: #f97316; font-family: 'Sora', sans-serif; }
    .side-stat-val.risk-low { color: #10b981; font-family: 'Sora', sans-serif; }

    .btn-row {
      display: flex; flex-direction: column; gap: 10px; margin-top: 6px;
    }
    .btn-side {
      padding: 11px 16px;
      border-radius: var(--radius-sm);
      font-family: 'Sora', sans-serif;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      text-align: center;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
    }
    .btn-side.primary { background: var(--ink); color: #fff; }
    .btn-side.primary:hover { background: var(--accent); color: #fff; }
    .btn-side.ghost { background: var(--surface); color: var(--ink); border: 1px solid var(--border); }
    .btn-side.ghost:hover { background: var(--ink); color: #fff; border-color: var(--ink); }

    footer {
      background: var(--ink);
      color: rgba(255,255,255,0.3);
      text-align: center; padding: 24px; font-size: 0.78rem;
    }

    @media (max-width: 960px) {
      .main-wrap { grid-template-columns: 1fr; }
      .topbar-inner, .page-header, .main-wrap { padding-inline: 20px; }
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
        <a class="tb-link active" href="medical_reports.php">Medical Reports</a>
      </nav>
    </div>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="ph-inner">
      <div class="ph-eyebrow">⬕ Health Profile Analysis</div>
      <h1 class="ph-title">Suggested Medical Reports</h1>
      <p class="ph-sub">Personalised report recommendations based on your age, BMI, lifestyle and existing health conditions.</p>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-wrap">

    <!-- Left: Main report card -->
    <div>
      <div class="risk-card">
        <!-- Risk Header -->
        <div class="risk-header">
          <div class="risk-header-left">
            <div class="risk-badge"><?php echo $risk_icon; ?> <?php echo $risk_level; ?> Risk</div>
            <h3>Your Insurance Medical Profile</h3>
            <p><?php echo htmlspecialchars($risk_desc); ?></p>
          </div>
          <?php if ($predicted_value): ?>
          <div class="risk-premium-box">
            <div class="label">Predicted Annual Premium</div>
            <div class="amount">LKR <?php echo number_format($predicted_value, 0); ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Profile chips -->
        <div class="profile-chips">
          <div class="chip"><div class="chip-dot"></div> Age: <?php echo $age; ?></div>
          <div class="chip"><div class="chip-dot"></div> BMI: <?php echo $bmi; ?></div>
          <div class="chip <?php echo $smoker ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Smoker: <?php echo $smoker ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $alcohol_use ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Alcohol: <?php echo $alcohol_use ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $diabetes ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Diabetes: <?php echo $diabetes ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $hypertension ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Hypertension: <?php echo $hypertension ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $heart_dis ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Heart Disease: <?php echo $heart_dis ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $asthma ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Asthma: <?php echo $asthma ? 'Yes' : 'No'; ?>
          </div>
          <div class="chip <?php echo $hosp > 0 ? 'flag' : ''; ?>">
            <div class="chip-dot"></div> Hospitalisations (5yr): <?php echo $hosp; ?>
          </div>
        </div>

        <!-- Reports -->
        <div class="reports-section">
          <h4>Recommended reports for insurance assessment (<?php echo count($reports); ?> tests)</h4>
          <?php foreach ($reports as $i => $r): ?>
          <div class="report-item" style="animation-delay: <?php echo $i * 0.05; ?>s;">
            <div class="report-num"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></div>
            <div class="report-body">
              <h5><?php echo htmlspecialchars($r['name']); ?></h5>
              <p><?php echo htmlspecialchars($r['note']); ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Disclaimer -->
        <div class="disclaimer">
          <strong>Disclaimer:</strong> These suggestions are auto-generated based on your profile for insurance risk assessment and do not replace clinical advice. Always follow the recommendations of your doctor or specialist.
        </div>
      </div>
    </div>

    <!-- Right: Sidebar -->
    <div class="sidebar-col">

      <div class="side-card">
        <div class="side-card-title">📊 Risk Summary</div>
        <div class="side-stat">
          <span class="side-stat-key">Risk Level</span>
          <span class="side-stat-val risk-<?php echo strtolower($risk_level) === 'high' ? 'high' : (strtolower($risk_level) === 'moderate' ? 'mod' : 'low'); ?>">
            <?php echo $risk_level; ?>
          </span>
        </div>
        <div class="side-stat">
          <span class="side-stat-key">Reports Required</span>
          <span class="side-stat-val"><?php echo count($reports); ?></span>
        </div>
        <div class="side-stat">
          <span class="side-stat-key">Age Group</span>
          <span class="side-stat-val"><?php echo $age < 30 ? 'Young Adult' : ($age < 50 ? 'Middle Age' : 'Senior'); ?></span>
        </div>
        <div class="side-stat">
          <span class="side-stat-key">BMI Category</span>
          <span class="side-stat-val">
            <?php
              if ($bmi < 18.5) echo 'Underweight';
              elseif ($bmi < 25) echo 'Normal';
              elseif ($bmi < 30) echo 'Overweight';
              else echo 'Obese';
            ?>
          </span>
        </div>
        <?php if ($predicted_value): ?>
        <div class="side-stat">
          <span class="side-stat-key">Monthly Est.</span>
          <span class="side-stat-val">LKR <?php echo number_format($predicted_value/12, 0); ?></span>
        </div>
        <?php endif; ?>
      </div>

      <div class="side-card">
        <div class="side-card-title">⚡ Next Steps</div>
        <p style="font-size:0.78rem; color:var(--muted); line-height:1.7; margin-bottom:16px;">
          Share this list with your GP before submitting to an insurer. Organising these reports in advance speeds up underwriting.
        </p>
        <div class="btn-row">
          <a href="coverage_details.php" class="btn-side primary">View Coverage Plans →</a>
          <a href="customer_dashboard.php" class="btn-side ghost">← Back to Dashboard</a>
        </div>
      </div>

    </div>
  </div>

  <footer>© 2025 CareCalc | All Rights Reserved</footer>

</body>
</html>