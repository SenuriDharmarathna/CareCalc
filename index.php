<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareCalc — Predict Your Medical Insurance</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      --radius: 18px;
      --radius-sm: 10px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Sora', sans-serif;
      background: var(--surface);
      color: var(--ink);
      overflow-x: hidden;
    }

    /* ===== NAVBAR ===== */
    .navbar-cc {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 1000;
      padding: 18px 0;
      transition: all 0.35s ease;
    }
    .navbar-cc.scrolled {
      background: rgba(10,15,30,0.9);
      backdrop-filter: blur(16px);
      padding: 12px 0;
      box-shadow: 0 4px 32px rgba(0,0,0,0.3);
    }
    .navbar-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .nav-brand {
      font-size: 1.25rem;
      font-weight: 800;
      color: #fff;
      text-decoration: none;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-link-cc {
      color: rgba(255,255,255,0.7);
      font-size: 0.85rem;
      font-weight: 500;
      text-decoration: none;
      padding: 7px 16px;
      border-radius: 50px;
      transition: all 0.2s;
    }
    .nav-link-cc:hover { color: #fff; background: rgba(255,255,255,0.1); }
    .nav-btn {
      background: var(--accent2);
      color: var(--ink) !important;
      font-weight: 700;
      padding: 8px 20px;
      border-radius: 50px;
      font-size: 0.83rem;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }
    .nav-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,212,170,0.35); }

    /* ===== HERO ===== */
    .hero {
      position: relative;
      height: 100vh;
      min-height: 600px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .hero video {
      position: absolute;
      inset: 0;
      width: 100%; height: 100%;
      object-fit: cover;
    }
    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(10,15,30,0.88) 0%, rgba(10,15,30,0.65) 50%, rgba(10,15,30,0.82) 100%);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 760px;
      padding: 0 24px;
    }
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,212,170,0.12);
      border: 1px solid rgba(0,212,170,0.25);
      color: var(--accent2);
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 6px 16px;
      border-radius: 50px;
      margin-bottom: 24px;
    }
    .hero-title {
      font-size: clamp(2.4rem, 5vw, 3.8rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.1;
      letter-spacing: -1.5px;
      margin-bottom: 20px;
    }
    .hero-title span { color: var(--accent2); }
    .hero-sub {
      font-size: 1rem;
      color: rgba(255,255,255,0.55);
      line-height: 1.7;
      max-width: 560px;
      margin: 0 auto 36px;
    }
    .hero-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn-hero-primary {
      background: var(--accent2);
      color: var(--ink);
      font-weight: 700;
      font-size: 0.9rem;
      padding: 14px 32px;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      font-family: 'Sora', sans-serif;
      transition: all 0.2s;
    }
    .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,212,170,0.4); }
    .btn-hero-ghost {
      background: transparent;
      color: rgba(255,255,255,0.7);
      font-weight: 600;
      font-size: 0.88rem;
      padding: 14px 28px;
      border-radius: 50px;
      border: 1.5px solid rgba(255,255,255,0.2);
      cursor: pointer;
      font-family: 'Sora', sans-serif;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    .btn-hero-ghost:hover { border-color: rgba(255,255,255,0.5); color: #fff; background: rgba(255,255,255,0.06); }

    .hero-scroll-hint {
      position: absolute;
      bottom: 32px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      color: rgba(255,255,255,0.3);
      font-size: 0.7rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .scroll-dot {
      width: 20px; height: 32px;
      border: 1.5px solid rgba(255,255,255,0.2);
      border-radius: 10px;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding-top: 5px;
    }
    .scroll-dot::after {
      content: '';
      width: 4px; height: 6px;
      background: var(--accent2);
      border-radius: 2px;
      animation: scrollBob 1.8s ease-in-out infinite;
    }
    @keyframes scrollBob {
      0%, 100% { transform: translateY(0); opacity: 1; }
      60% { transform: translateY(10px); opacity: 0.3; }
    }

    /* ===== STATS BAND ===== */
    .stats-band {
      background: var(--ink);
      padding: 48px 0;
    }
    .stats-band-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 32px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1px;
      background: rgba(255,255,255,0.07);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .stat-item {
      background: var(--ink);
      padding: 32px 24px;
      text-align: center;
    }
    .stat-num {
      font-family: 'JetBrains Mono', monospace;
      font-size: 2rem;
      font-weight: 700;
      color: var(--accent2);
      letter-spacing: -1px;
    }
    .stat-label { font-size: 0.78rem; color: rgba(255,255,255,0.4); margin-top: 6px; }

    /* ===== WHY SECTION ===== */
    .why-section {
      padding: 100px 0;
      background: var(--surface);
    }
    .section-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 32px;
    }
    .section-eyebrow {
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 12px;
    }
    .section-title {
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 800;
      color: var(--ink);
      letter-spacing: -1px;
      line-height: 1.15;
      margin-bottom: 16px;
    }
    .section-sub { font-size: 0.9rem; color: var(--muted); max-width: 480px; line-height: 1.7; }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-top: 60px;
    }
    .feature-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 36px;
      transition: all 0.25s;
      position: relative;
      overflow: hidden;
    }
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      border-radius: var(--radius) var(--radius) 0 0;
      opacity: 0;
      transition: opacity 0.25s;
    }
    .feature-card.blue::before { background: var(--accent); }
    .feature-card.teal::before { background: var(--accent2); }
    .feature-card.amber::before { background: var(--warn); }
    .feature-card.slate::before { background: #64748b; }
    .feature-card:hover { transform: translateY(-3px); box-shadow: 0 16px 48px rgba(10,15,30,0.08); }
    .feature-card:hover::before { opacity: 1; }

    .feature-icon {
      width: 48px; height: 48px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem;
      margin-bottom: 20px;
    }
    .feature-icon.blue { background: var(--accent-glow); }
    .feature-icon.teal { background: var(--accent2-glow); }
    .feature-icon.amber { background: rgba(249,115,22,0.1); }
    .feature-icon.slate { background: rgba(100,116,139,0.1); }

    .feature-card h4 {
      font-size: 1rem;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 10px;
      letter-spacing: -0.2px;
    }
    .feature-card p {
      font-size: 0.85rem;
      color: var(--muted);
      line-height: 1.7;
      margin: 0;
    }

    /* ===== HOW IT WORKS ===== */
    .how-section {
      padding: 100px 0;
      background: var(--ink);
      position: relative;
      overflow: hidden;
    }
    .how-section::before {
      content: '';
      position: absolute;
      top: -100px; right: -100px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(37,99,255,0.15) 0%, transparent 70%);
      pointer-events: none;
    }
    .how-section::after {
      content: '';
      position: absolute;
      bottom: -80px; left: 10%;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(0,212,170,0.1) 0%, transparent 70%);
      pointer-events: none;
    }
    .how-section .section-eyebrow { color: var(--accent2); }
    .how-section .section-title { color: #fff; }
    .how-section .section-sub { color: rgba(255,255,255,0.45); }

    .steps-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
      margin-top: 60px;
      background: rgba(255,255,255,0.04);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .step-card {
      background: rgba(255,255,255,0.02);
      padding: 40px 32px;
      position: relative;
    }
    .step-num {
      font-family: 'JetBrains Mono', monospace;
      font-size: 3rem;
      font-weight: 700;
      color: rgba(255,255,255,0.06);
      line-height: 1;
      margin-bottom: 20px;
    }
    .step-card h4 { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
    .step-card p { font-size: 0.83rem; color: rgba(255,255,255,0.4); line-height: 1.7; margin: 0; }
    .step-accent {
      width: 32px; height: 3px;
      border-radius: 2px;
      margin-bottom: 16px;
    }

    /* ===== CTA BAND ===== */
    .cta-band {
      padding: 100px 0;
      background: var(--surface);
    }
    .cta-box {
      max-width: 800px;
      margin: 0 auto;
      padding: 0 32px;
      text-align: center;
    }
    .cta-box .section-title { margin-bottom: 16px; }
    .cta-box p { font-size: 0.9rem; color: var(--muted); margin-bottom: 36px; }
    .cta-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn-cta {
      background: var(--ink);
      color: #fff;
      font-weight: 700;
      font-size: 0.88rem;
      padding: 14px 32px;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      font-family: 'Sora', sans-serif;
      transition: all 0.2s;
    }
    .btn-cta:hover { background: var(--accent); transform: translateY(-1px); }
    .btn-cta-outline {
      background: transparent;
      color: var(--ink);
      font-weight: 600;
      font-size: 0.88rem;
      padding: 14px 28px;
      border-radius: 50px;
      border: 1.5px solid var(--border);
      cursor: pointer;
      font-family: 'Sora', sans-serif;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex; align-items: center;
    }
    .btn-cta-outline:hover { border-color: var(--ink); background: var(--ink); color: #fff; }

    /* ===== FOOTER ===== */
    .footer-cc {
      background: var(--ink);
      padding: 40px 0;
    }
    .footer-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }
    .footer-brand { font-size: 1rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }
    .footer-links { display: flex; gap: 24px; }
    .footer-links a { font-size: 0.8rem; color: rgba(255,255,255,0.35); text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: rgba(255,255,255,0.7); }
    .footer-copy { font-size: 0.78rem; color: rgba(255,255,255,0.25); }

    /* ===== MODAL ===== */
    .modal-cc .modal-content {
      border: none;
      border-radius: var(--radius);
      box-shadow: 0 32px 80px rgba(10,15,30,0.2);
      font-family: 'Sora', sans-serif;
    }
    .modal-cc .modal-header {
      background: var(--ink);
      border-radius: var(--radius) var(--radius) 0 0;
      border: none;
      padding: 24px 28px;
    }
    .modal-cc .modal-title { font-size: 1rem; font-weight: 700; color: #fff; }
    .modal-cc .btn-close { filter: invert(1); opacity: 0.6; }
    .modal-cc .modal-body { padding: 28px; }
    .modal-cc .modal-footer { border: none; padding: 0 28px 24px; }

    .cc-label {
      display: block;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 6px;
    }
    .cc-input, .cc-select {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--surface);
      font-family: 'Sora', sans-serif;
      font-size: 0.85rem;
      color: var(--ink);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      margin-bottom: 16px;
      appearance: none;
    }
    .cc-input:focus, .cc-select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
      background: #fff;
    }
    .cc-select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a859e' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 12px;
      padding-right: 36px;
    }
    .btn-submit-cc {
      width: 100%;
      padding: 13px;
      background: var(--ink);
      color: #fff;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 0.88rem;
      border: none;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-submit-cc:hover { background: var(--accent); }
    .modal-switch { font-size: 0.8rem; color: var(--muted); text-align: center; margin-top: 16px; }
    .modal-switch a { color: var(--accent); text-decoration: none; font-weight: 600; }

    /* Animations */
    .fade-up {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .fade-up.visible { opacity: 1; transform: translateY(0); }

    @media (max-width: 768px) {
      .features-grid, .steps-grid { grid-template-columns: 1fr; }
      .stats-band-inner { grid-template-columns: repeat(2, 1fr); }
      .footer-inner { flex-direction: column; text-align: center; }
      .footer-links { flex-wrap: wrap; justify-content: center; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar-cc" id="mainNav">
    <div class="navbar-inner">
      <a class="nav-brand" href="#">🩺 CareCalc</a>
      <div class="nav-links">
        <a class="nav-link-cc" href="#why">Why CareCalc</a>
        <a class="nav-link-cc" href="#how">How It Works</a>
        <a class="nav-link-cc" href="contact.php">Contact</a>
        <a class="nav-link-cc" href="login.php">Log In</a>
        <button class="nav-btn" data-bs-toggle="modal" data-bs-target="#signupModal">Get Started →</button>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero" id="home">
    <video autoplay muted loop playsinline>
      <source src="assets/videos/vid3.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <div class="hero-eyebrow">
        <span>◈</span> AI-Powered Insurance Insights
      </div>
      <h1 class="hero-title">
        Know your premium<br>before you <span>commit</span>.
      </h1>
      <p class="hero-sub">
        CareCalc uses advanced machine learning to estimate your medical insurance cost — 
        personalised to your age, lifestyle, and health history.
      </p>
      <div class="hero-actions">
        <button class="btn-hero-primary" data-bs-toggle="modal" data-bs-target="#signupModal">
          Start for Free
        </button>
        <a class="btn-hero-ghost" href="login.php">
          I have an account →
        </a>
      </div>
    </div>
    <div class="hero-scroll-hint">
      <div class="scroll-dot"></div>
      Scroll
    </div>
  </section>

  <!-- Stats Band -->
  <div class="stats-band">
    <div class="stats-band-inner">
      <div class="stat-item">
        <div class="stat-num">15+</div>
        <div class="stat-label">Health Factors Analysed</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">3</div>
        <div class="stat-label">Coverage Plan Tiers</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">&lt;5s</div>
        <div class="stat-label">To Get Your Estimate</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">100%</div>
        <div class="stat-label">Data Secure & Private</div>
      </div>
    </div>
  </div>

  <!-- Why CareCalc -->
  <section class="why-section" id="why">
    <div class="section-inner">
      <div class="fade-up">
        <div class="section-eyebrow">Why Choose CareCalc</div>
        <h2 class="section-title">A smarter way to plan<br>your medical cover.</h2>
        <p class="section-sub">Stop guessing. Get data-backed premium estimates before talking to an agent.</p>
      </div>
      <div class="features-grid">
        <div class="feature-card blue fade-up">
          <div class="feature-icon blue">🤖</div>
          <h4>AI-Driven Predictions</h4>
          <p>Our machine learning model is trained on realistic insurance patterns to estimate your premium with accuracy — no guesswork, just transparent data-backed insights tailored to your profile.</p>
        </div>
        <div class="feature-card teal fade-up">
          <div class="feature-icon teal">🔒</div>
          <h4>Secure & Private</h4>
          <p>Your personal and health information is handled with care, using best-practice encryption. Your data is used only to generate predictions — nothing else.</p>
        </div>
        <div class="feature-card amber fade-up">
          <div class="feature-icon amber">⚡</div>
          <h4>Instant Results</h4>
          <p>Fill in a simple form and get your estimated annual premium in under five seconds. See monthly, quarterly and half-yearly breakdowns at a glance.</p>
        </div>
        <div class="feature-card slate fade-up">
          <div class="feature-icon slate">🗺</div>
          <h4>End-to-End Guidance</h4>
          <p>From prediction to coverage comparison to suggested medical reports — CareCalc walks you through every step of understanding your insurance needs.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="how-section" id="how">
    <div class="section-inner">
      <div class="fade-up">
        <div class="section-eyebrow">How It Works</div>
        <h2 class="section-title">Three steps to your<br>premium estimate.</h2>
        <p class="section-sub">Our process is simple, fast and entirely free to use.</p>
      </div>
      <div class="steps-grid">
        <div class="step-card fade-up">
          <div class="step-num">01</div>
          <div class="step-accent" style="background: var(--accent2);"></div>
          <h4>Create Your Account</h4>
          <p>Sign up in under a minute. No credit card required. Your account keeps your predictions safe and accessible.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">02</div>
          <div class="step-accent" style="background: var(--accent);"></div>
          <h4>Enter Your Health Profile</h4>
          <p>Provide basic details — age, BMI, existing conditions, lifestyle factors. The more accurate, the better your estimate.</p>
        </div>
        <div class="step-card fade-up">
          <div class="step-num">03</div>
          <div class="step-accent" style="background: var(--warn);"></div>
          <h4>Get Your Prediction</h4>
          <p>Instantly receive your estimated annual premium, recommended coverage plan and suggested medical reports for your insurer.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="cta-band">
    <div class="cta-box fade-up">
      <div class="section-eyebrow" style="text-align:center;">Get Started Today</div>
      <h2 class="section-title">Ready to understand<br>your insurance cost?</h2>
      <p>Join CareCalc and get a data-driven premium estimate in minutes — completely free.</p>
      <div class="cta-actions">
        <button class="btn-cta" data-bs-toggle="modal" data-bs-target="#signupModal">Create Free Account</button>
        <a class="btn-cta-outline" href="login.php">Sign In</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer-cc">
    <div class="footer-inner">
      <div class="footer-brand">🩺 CareCalc</div>
      <div class="footer-links">
        <a href="contact.php">Contact</a>
        <a href="coverage_details.php">Coverage Plans</a>
        <a href="login.php">Log In</a>
      </div>
      <div class="footer-copy">© 2025 CareCalc. All rights reserved.</div>
    </div>
  </footer>

  <!-- Sign Up Modal -->
  <div class="modal fade modal-cc" id="signupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="signup.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Create your CareCalc account</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label class="cc-label">Account Type</label>
            <select class="cc-select" name="role" id="roleSelect" onchange="toggleRoleFields()" required>
              <option value="">— Select role —</option>
              <option value="user">Customer</option>
              <option value="admin">Admin</option>
            </select>

            <label class="cc-label">Username</label>
            <input type="text" name="username" class="cc-input" placeholder="Your display name" required>

            <label class="cc-label">Email Address</label>
            <input type="email" name="email" class="cc-input" placeholder="you@example.com" required>

            <label class="cc-label">Password</label>
            <input type="password" name="password" class="cc-input" placeholder="Create a strong password" required>

            <div id="customerFields">
              <label class="cc-label">City</label>
              <input type="text" name="city" class="cc-input" placeholder="Your city">

              <label class="cc-label">Contact Number</label>
              <input type="text" name="contact" class="cc-input" placeholder="e.g. +94 77 000 0000">
            </div>

            <button type="submit" class="btn-submit-cc">Create Account →</button>
            <div class="modal-switch">Already have an account? <a href="login.php">Log in here</a></div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Navbar scroll
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 60);
    });

    // Role toggle
    function toggleRoleFields() {
      const role = document.getElementById('roleSelect').value;
      document.getElementById('customerFields').style.display = role === 'user' ? 'block' : 'none';
    }
    toggleRoleFields();

    // Scroll fade-in
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.12 });
    document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
  </script>
</body>
</html>