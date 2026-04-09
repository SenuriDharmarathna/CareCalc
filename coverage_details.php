<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coverage Plans — CareCalc</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --ink: #0a0f1e;
      --ink-soft: #1e2740;
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
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Sora', sans-serif;
      background: var(--surface);
      color: var(--ink);
      min-height: 100vh;
    }

    /* ===== TOPBAR ===== */
    .topbar {
      background: var(--ink);
      padding: 0;
    }
    .topbar-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 18px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .tb-brand {
      font-size: 1.1rem;
      font-weight: 800;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .tb-nav { display: flex; align-items: center; gap: 4px; }
    .tb-link {
      color: rgba(255,255,255,0.5);
      font-size: 0.82rem;
      font-weight: 500;
      text-decoration: none;
      padding: 7px 14px;
      border-radius: 50px;
      transition: all 0.2s;
    }
    .tb-link:hover, .tb-link.active { color: #fff; background: rgba(255,255,255,0.08); }

    /* ===== PAGE HEADER ===== */
    .page-header {
      background: var(--ink);
      padding: 60px 40px 80px;
      position: relative;
      overflow: hidden;
    }
    .page-header::before {
      content: '';
      position: absolute;
      top: -80px; right: -80px;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(37,99,255,0.2) 0%, transparent 70%);
      pointer-events: none;
    }
    .page-header::after {
      content: '';
      position: absolute;
      bottom: -60px; left: 30%;
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(0,212,170,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .ph-inner {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
      text-align: center;
    }
    .ph-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,212,170,0.1);
      border: 1px solid rgba(0,212,170,0.2);
      color: var(--accent2);
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 5px 14px;
      border-radius: 50px;
      margin-bottom: 20px;
    }
    .ph-title {
      font-size: clamp(1.8rem, 3vw, 2.8rem);
      font-weight: 800;
      color: #fff;
      letter-spacing: -1px;
      line-height: 1.15;
      margin-bottom: 14px;
    }
    .ph-sub { font-size: 0.88rem; color: rgba(255,255,255,0.45); max-width: 520px; margin: 0 auto; line-height: 1.7; }

    /* ===== CARDS AREA ===== */
    .cards-area {
      max-width: 1200px;
      margin: -40px auto 0;
      padding: 0 40px 80px;
      position: relative;
      z-index: 2;
    }
    .plans-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      align-items: start;
    }

    /* Plan card */
    .plan-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1.5px solid var(--border);
      overflow: hidden;
      transition: all 0.25s;
    }
    .plan-card:hover { transform: translateY(-4px); box-shadow: 0 20px 56px rgba(10,15,30,0.1); }
    .plan-card.featured {
      border-color: var(--accent);
      box-shadow: 0 8px 32px var(--accent-glow);
      transform: translateY(-8px);
    }
    .plan-card.featured:hover { transform: translateY(-12px); box-shadow: 0 24px 64px var(--accent-glow); }

    .plan-header-strip {
      padding: 28px 28px 20px;
      position: relative;
    }
    .plan-header-strip.basic { background: linear-gradient(135deg, #ecfdf5, #d1fae5); }
    .plan-header-strip.standard { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
    .plan-header-strip.premium { background: linear-gradient(135deg, #fffbeb, #fef3c7); }

    .plan-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 4px 12px;
      border-radius: 50px;
      margin-bottom: 12px;
    }
    .plan-badge.basic { background: rgba(16,185,129,0.12); color: #047857; }
    .plan-badge.standard { background: rgba(37,99,255,0.1); color: var(--accent); }
    .plan-badge.premium { background: rgba(249,115,22,0.12); color: #c2410c; }

    .popular-chip {
      position: absolute;
      top: 20px; right: 20px;
      background: var(--accent);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 50px;
    }

    .plan-name {
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--ink);
      letter-spacing: -0.4px;
      margin-bottom: 6px;
    }
    .plan-tagline { font-size: 0.78rem; color: var(--muted); line-height: 1.5; }

    .plan-body { padding: 24px 28px 28px; }

    .plan-price {
      display: flex;
      align-items: baseline;
      gap: 6px;
      margin-bottom: 6px;
    }
    .plan-price-val {
      font-family: 'JetBrains Mono', monospace;
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--ink);
      letter-spacing: -1px;
    }
    .plan-price-curr { font-size: 0.78rem; color: var(--muted); font-weight: 500; }
    .plan-price-note { font-size: 0.72rem; color: var(--muted); margin-bottom: 22px; }

    .plan-divider {
      height: 1px;
      background: var(--border);
      margin: 18px 0;
    }

    .feature-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 7px 0;
      font-size: 0.82rem;
      color: var(--ink);
      border-bottom: 1px solid var(--border);
    }
    .feature-row:last-of-type { border-bottom: none; }
    .feature-check {
      width: 18px; height: 18px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.6rem;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .feature-check.yes { background: var(--accent2-glow); color: #00a87e; }
    .feature-check.no { background: rgba(120,130,150,0.08); color: rgba(120,130,150,0.4); }
    .feature-label { flex: 1; line-height: 1.4; }
    .feature-val {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.75rem;
      color: var(--muted);
      white-space: nowrap;
    }

    .plan-suggest {
      margin-top: 18px;
      background: var(--surface);
      border-radius: var(--radius-sm);
      padding: 11px 14px;
      font-size: 0.72rem;
      color: var(--muted);
    }
    .plan-suggest strong { color: var(--ink); }

    /* ===== COMPARISON TABLE ===== */
    .compare-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 40px 80px;
    }
    .compare-head {
      text-align: center;
      margin-bottom: 40px;
    }
    .section-eyebrow {
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 10px;
    }
    .section-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--ink);
      letter-spacing: -0.5px;
    }

    .compare-table {
      width: 100%;
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      overflow: hidden;
      border-collapse: separate;
      border-spacing: 0;
    }
    .compare-table th {
      padding: 20px 24px;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      background: var(--ink);
      color: rgba(255,255,255,0.6);
      text-align: left;
    }
    .compare-table th:first-child { color: rgba(255,255,255,0.35); }
    .compare-table th.highlighted { color: var(--accent2); }
    .compare-table td {
      padding: 14px 24px;
      font-size: 0.83rem;
      color: var(--ink);
      border-bottom: 1px solid var(--border);
    }
    .compare-table tr:last-child td { border-bottom: none; }
    .compare-table tr:nth-child(even) td { background: rgba(244,246,251,0.5); }
    .compare-table td.highlighted { background: rgba(37,99,255,0.04) !important; font-weight: 600; }
    .compare-table td.feature-name { color: var(--muted); font-size: 0.8rem; }
    .check-yes { color: #00a87e; font-weight: 700; }
    .check-no { color: rgba(0,0,0,0.2); }

    /* ===== BOTTOM CTA ===== */
    .bottom-cta {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 40px 80px;
      text-align: center;
    }
    .cta-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn-back {
      background: var(--card);
      border: 1.5px solid var(--border);
      color: var(--ink);
      font-family: 'Sora', sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      padding: 12px 24px;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-back:hover { background: var(--ink); color: #fff; border-color: var(--ink); }
    .btn-predict {
      background: var(--ink);
      color: #fff;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 0.85rem;
      padding: 12px 28px;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: all 0.2s;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-predict:hover { background: var(--accent); transform: translateY(-1px); color: #fff; }

    footer {
      background: var(--ink);
      color: rgba(255,255,255,0.3);
      text-align: center;
      padding: 24px;
      font-size: 0.78rem;
    }

    @media (max-width: 900px) {
      .plans-grid { grid-template-columns: 1fr; }
      .plan-card.featured { transform: none; }
      .cards-area, .compare-section, .bottom-cta { padding-inline: 20px; }
      .topbar-inner { padding-inline: 20px; }
      .page-header { padding-inline: 20px; }
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
        <a class="tb-link active" href="coverage_details.php">Coverage Plans</a>
        <a class="tb-link" href="medical_reports.php">Medical Reports</a>
      </nav>
    </div>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="ph-inner">
      <div class="ph-eyebrow">◈ Plan Comparison</div>
      <h1 class="ph-title">Choose the right coverage<br>for your needs.</h1>
      <p class="ph-sub">Compare all three tiers side by side. Your dashboard will automatically recommend the plan that matches your predicted premium.</p>
    </div>
  </div>

  <!-- Plan Cards -->
  <div class="cards-area">
    <div class="plans-grid">

      <!-- Basic -->
      <div class="plan-card">
        <div class="plan-header-strip basic">
          <div class="plan-badge basic">◎ Basic</div>
          <div class="plan-name">Basic Plan</div>
          <div class="plan-tagline">Entry-level cover for essential medical needs. Ideal for young, low-risk individuals.</div>
        </div>
        <div class="plan-body">
          <div class="plan-price">
            <span class="plan-price-curr">Rs.</span>
            <span class="plan-price-val">500,000</span>
          </div>
          <div class="plan-price-note">Approximate annual coverage limit</div>

          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Ward Accommodation</div>
            <div class="feature-val">Shared/Standard</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">ICU Coverage</div>
            <div class="feature-val">Rs. 100,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Surgical Expenses</div>
            <div class="feature-val">Rs. 200,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">OPD Coverage</div>
            <div class="feature-val">Rs. 5,000/yr</div>
          </div>
          <div class="feature-row">
            <div class="feature-check no">—</div>
            <div class="feature-label">Maternity Benefits</div>
            <div class="feature-val" style="color:rgba(0,0,0,0.2)">None</div>
          </div>
          <div class="feature-row">
            <div class="feature-check no">—</div>
            <div class="feature-label">Dental Coverage</div>
            <div class="feature-val" style="color:rgba(0,0,0,0.2)">None</div>
          </div>
          <div class="feature-row">
            <div class="feature-check no">—</div>
            <div class="feature-label">Wellness Checkups</div>
            <div class="feature-val" style="color:rgba(0,0,0,0.2)">None</div>
          </div>

          <div class="plan-suggest">
            Suggested when annual premium is <strong>below Rs. 300,000</strong>
          </div>
        </div>
      </div>

      <!-- Standard (Featured) -->
      <div class="plan-card featured">
        <div class="plan-header-strip standard">
          <div class="popular-chip">Most Popular</div>
          <div class="plan-badge standard">◈ Standard</div>
          <div class="plan-name">Standard Plan</div>
          <div class="plan-tagline">Balanced benefits for working professionals and small families.</div>
        </div>
        <div class="plan-body">
          <div class="plan-price">
            <span class="plan-price-curr">Rs.</span>
            <span class="plan-price-val">1,500,000</span>
          </div>
          <div class="plan-price-note">Approximate annual coverage limit</div>

          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Ward Accommodation</div>
            <div class="feature-val">Private Room</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">ICU Coverage</div>
            <div class="feature-val">Rs. 250,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Surgical Expenses</div>
            <div class="feature-val">Rs. 450,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">OPD Coverage</div>
            <div class="feature-val">Rs. 15,000/yr</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Maternity Benefits</div>
            <div class="feature-val">Rs. 50,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Dental Coverage</div>
            <div class="feature-val">Basic</div>
          </div>
          <div class="feature-row">
            <div class="feature-check no">—</div>
            <div class="feature-label">Wellness Checkups</div>
            <div class="feature-val" style="color:rgba(0,0,0,0.2)">None</div>
          </div>

          <div class="plan-suggest">
            Suggested when annual premium is <strong>Rs. 300,000 – Rs. 700,000</strong>
          </div>
        </div>
      </div>

      <!-- Premium -->
      <div class="plan-card">
        <div class="plan-header-strip premium">
          <div class="plan-badge premium">⬡ Premium</div>
          <div class="plan-name">Premium Plan</div>
          <div class="plan-tagline">High-end VIP-level cover with maximum protection and benefits.</div>
        </div>
        <div class="plan-body">
          <div class="plan-price">
            <span class="plan-price-curr">Rs.</span>
            <span class="plan-price-val">5,000,000</span>
          </div>
          <div class="plan-price-note">Approximate annual coverage limit</div>

          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Ward Accommodation</div>
            <div class="feature-val">VIP / Luxury</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">ICU Coverage</div>
            <div class="feature-val">Rs. 800,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Surgical Expenses</div>
            <div class="feature-val">Rs. 1,500,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">OPD Coverage</div>
            <div class="feature-val">Rs. 50,000/yr</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Maternity Benefits</div>
            <div class="feature-val">Rs. 120,000</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Dental Coverage</div>
            <div class="feature-val">Full</div>
          </div>
          <div class="feature-row">
            <div class="feature-check yes">✓</div>
            <div class="feature-label">Wellness Checkups</div>
            <div class="feature-val">Annual</div>
          </div>

          <div class="plan-suggest">
            Suggested when annual premium is <strong>above Rs. 700,000</strong>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Comparison Table -->
  <div class="compare-section">
    <div class="compare-head">
      <div class="section-eyebrow">Side-by-side</div>
      <div class="section-title">Full Feature Comparison</div>
    </div>
    <table class="compare-table">
      <thead>
        <tr>
          <th>Feature</th>
          <th>Basic</th>
          <th class="highlighted">Standard</th>
          <th>Premium</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="feature-name">Annual Coverage Limit</td>
          <td>Rs. 500,000</td>
          <td class="highlighted">Rs. 1,500,000</td>
          <td>Rs. 5,000,000</td>
        </tr>
        <tr>
          <td class="feature-name">Room Type</td>
          <td>Shared/Standard</td>
          <td class="highlighted">Private</td>
          <td>VIP / Luxury</td>
        </tr>
        <tr>
          <td class="feature-name">ICU Coverage</td>
          <td>Rs. 100,000</td>
          <td class="highlighted">Rs. 250,000</td>
          <td>Rs. 800,000</td>
        </tr>
        <tr>
          <td class="feature-name">Surgical Expenses</td>
          <td>Rs. 200,000</td>
          <td class="highlighted">Rs. 450,000</td>
          <td>Rs. 1,500,000</td>
        </tr>
        <tr>
          <td class="feature-name">OPD (per year)</td>
          <td>Rs. 5,000</td>
          <td class="highlighted">Rs. 15,000</td>
          <td>Rs. 50,000</td>
        </tr>
        <tr>
          <td class="feature-name">Maternity Benefits</td>
          <td class="check-no">—</td>
          <td class="highlighted">Rs. 50,000</td>
          <td>Rs. 120,000</td>
        </tr>
        <tr>
          <td class="feature-name">Dental Coverage</td>
          <td class="check-no">—</td>
          <td class="highlighted">Basic</td>
          <td>Full</td>
        </tr>
        <tr>
          <td class="feature-name">Annual Wellness Checkup</td>
          <td class="check-no">—</td>
          <td class="highlighted check-no">—</td>
          <td class="check-yes">✓ Included</td>
        </tr>
        <tr>
          <td class="feature-name">Emergency Ambulance</td>
          <td class="check-no">—</td>
          <td class="highlighted check-no">—</td>
          <td class="check-yes">✓ Included</td>
        </tr>
        <tr>
          <td class="feature-name">Suggested Premium Range</td>
          <td>Below Rs. 300k</td>
          <td class="highlighted">Rs. 300k – 700k</td>
          <td>Above Rs. 700k</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Bottom CTA -->
  <div class="bottom-cta">
    <div class="cta-row">
      <a href="customer_dashboard.php" class="btn-back">← Back to Dashboard</a>
      <a href="customer_dashboard.php#predictCard" class="btn-predict">Get My Premium Prediction →</a>
    </div>
  </div>

  <footer>© 2025 CareCalc | All Rights Reserved</footer>

</body>
</html>