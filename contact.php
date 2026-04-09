<?php
include "config.php";

$success = false;
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = $_POST["name"];
    $email   = $_POST["email"];
    $subject = $_POST["subject"];
    $message = $_POST["message"];

    $sql = "INSERT INTO contact_messages (name, email, subject, message)
            VALUES ('$name', '$email', '$subject', '$message')";

    if ($conn->query($sql)) {
        $success = true;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us — CareCalc</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --ink: #0a0f1e;
      --surface: #f4f6fb;
      --card: #ffffff;
      --accent: #2563ff;
      --accent-glow: rgba(37,99,255,0.15);
      --accent2: #00d4aa;
      --accent2-glow: rgba(0,212,170,0.13);
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

    /* ===== NAVBAR ===== */
    .navbar-cc {
      position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
      padding: 18px 0;
      transition: all 0.35s ease;
    }
    .navbar-cc.scrolled {
      background: rgba(10,15,30,0.92);
      backdrop-filter: blur(16px);
      padding: 12px 0;
      box-shadow: 0 4px 32px rgba(0,0,0,0.3);
    }
    .navbar-inner {
      max-width: 1200px; margin: 0 auto; padding: 0 40px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .nav-brand {
      font-size: 1.15rem; font-weight: 800; color: #fff;
      text-decoration: none; display: flex; align-items: center; gap: 8px;
    }
    .nav-links { display: flex; align-items: center; gap: 4px; }
    .nav-link-cc {
      color: rgba(255,255,255,0.65); font-size: 0.83rem; font-weight: 500;
      text-decoration: none; padding: 7px 14px; border-radius: 50px; transition: all 0.2s;
    }
    .nav-link-cc:hover, .nav-link-cc.active { color: #fff; background: rgba(255,255,255,0.1); }
    .nav-btn {
      background: var(--accent2); color: var(--ink); font-weight: 700;
      font-size: 0.8rem; padding: 8px 18px; border-radius: 50px;
      border: none; cursor: pointer; font-family: 'Sora', sans-serif; transition: all 0.2s;
    }
    .nav-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,212,170,0.35); }

    /* ===== PAGE HEADER ===== */
    .page-header {
      background: var(--ink);
      padding: 120px 40px 80px;
      position: relative; overflow: hidden; text-align: center;
    }
    .page-header::before {
      content: ''; position: absolute;
      top: -60px; left: 50%; transform: translateX(-50%);
      width: 600px; height: 400px;
      background: radial-gradient(ellipse, rgba(37,99,255,0.18) 0%, transparent 65%);
      pointer-events: none;
    }
    .page-header::after {
      content: ''; position: absolute;
      bottom: -40px; right: 10%;
      width: 280px; height: 280px;
      background: radial-gradient(circle, rgba(0,212,170,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .ph-inner { position: relative; z-index: 1; max-width: 600px; margin: 0 auto; }
    .ph-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(0,212,170,0.1); border: 1px solid rgba(0,212,170,0.2);
      color: var(--accent2); font-size: 0.68rem; font-weight: 700;
      letter-spacing: 0.14em; text-transform: uppercase;
      padding: 5px 14px; border-radius: 50px; margin-bottom: 20px;
    }
    .ph-title {
      font-size: clamp(2rem, 4vw, 3rem); font-weight: 800;
      color: #fff; letter-spacing: -1.5px; line-height: 1.1; margin-bottom: 14px;
    }
    .ph-sub { font-size: 0.88rem; color: rgba(255,255,255,0.42); line-height: 1.7; }

    /* ===== CONTACT GRID ===== */
    .contact-wrap {
      max-width: 1100px; margin: -32px auto 0;
      padding: 0 40px 80px;
      position: relative; z-index: 2;
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 24px;
      align-items: start;
    }

    /* Info panel */
    .info-panel {
      display: flex; flex-direction: column; gap: 16px;
    }
    .info-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 24px;
      transition: box-shadow 0.2s;
    }
    .info-card:hover { box-shadow: 0 10px 32px rgba(10,15,30,0.07); }
    .info-item {
      display: flex; align-items: flex-start; gap: 14px;
      padding: 12px 0;
      border-bottom: 1px solid var(--border);
    }
    .info-item:last-child { border-bottom: none; padding-bottom: 0; }
    .info-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem; flex-shrink: 0;
    }
    .info-icon.blue { background: var(--accent-glow); }
    .info-icon.teal { background: var(--accent2-glow); }
    .info-icon.amber { background: rgba(249,115,22,0.1); }
    .info-icon.slate { background: rgba(100,116,139,0.1); }
    .info-text .label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 3px; }
    .info-text .value { font-size: 0.82rem; color: var(--ink); font-weight: 500; }

    .hours-card {
      background: var(--ink);
      border-radius: var(--radius);
      border: 1px solid rgba(255,255,255,0.05);
      padding: 24px;
      position: relative; overflow: hidden;
    }
    .hours-card::before {
      content: ''; position: absolute; top: -40px; right: -40px;
      width: 180px; height: 180px;
      background: radial-gradient(circle, rgba(0,212,170,0.15) 0%, transparent 70%);
    }
    .hours-card-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(255,255,255,0.35); margin-bottom: 16px; }
    .hours-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 0.8rem;
    }
    .hours-row:last-child { border-bottom: none; padding-bottom: 0; }
    .hours-row .day { color: rgba(255,255,255,0.45); }
    .hours-row .time { color: var(--accent2); font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; }
    .hours-row .closed { color: rgba(255,255,255,0.2); font-size: 0.72rem; }

    /* Form panel */
    .form-card {
      background: var(--card);
      border-radius: var(--radius);
      border: 1px solid var(--border);
      padding: 36px;
    }
    .form-card-head { margin-bottom: 28px; }
    .form-card-head h3 { font-size: 1.1rem; font-weight: 700; color: var(--ink); letter-spacing: -0.3px; margin-bottom: 5px; }
    .form-card-head p { font-size: 0.8rem; color: var(--muted); }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-field { margin-bottom: 16px; }
    .form-field label {
      display: block; font-size: 0.68rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 7px;
    }
    .form-field input,
    .form-field textarea {
      width: 100%; padding: 12px 14px;
      border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      background: var(--surface); font-family: 'Sora', sans-serif;
      font-size: 0.85rem; color: var(--ink); outline: none;
      transition: border-color 0.2s, box-shadow 0.2s; resize: vertical;
    }
    .form-field input:focus,
    .form-field textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
      background: #fff;
    }

    .btn-send {
      width: 100%; padding: 14px;
      background: var(--ink); color: #fff;
      font-family: 'Sora', sans-serif; font-weight: 700; font-size: 0.9rem;
      border: none; border-radius: var(--radius-sm); cursor: pointer;
      transition: all 0.2s; margin-top: 4px;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-send:hover { background: var(--accent); transform: translateY(-1px); box-shadow: 0 8px 24px var(--accent-glow); }

    /* Alerts */
    .alert-success-cc {
      background: rgba(0,212,170,0.08); border: 1px solid rgba(0,212,170,0.25);
      color: #00836a; border-radius: var(--radius-sm);
      padding: 14px 16px; font-size: 0.82rem; font-weight: 500;
      display: flex; align-items: center; gap: 10px; margin-bottom: 24px;
    }
    .alert-error-cc {
      background: rgba(239,68,68,0.07); border: 1px solid rgba(239,68,68,0.2);
      color: #dc2626; border-radius: var(--radius-sm);
      padding: 14px 16px; font-size: 0.82rem; font-weight: 500;
      display: flex; align-items: center; gap: 10px; margin-bottom: 24px;
    }

    /* Modal */
    .modal-cc .modal-content {
      border: none; border-radius: var(--radius);
      box-shadow: 0 32px 80px rgba(10,15,30,0.2); font-family: 'Sora', sans-serif;
    }
    .modal-cc .modal-header {
      background: var(--ink); border-radius: var(--radius) var(--radius) 0 0;
      border: none; padding: 22px 28px;
    }
    .modal-cc .modal-title { font-size: 0.95rem; font-weight: 700; color: #fff; }
    .modal-cc .btn-close { filter: invert(1); opacity: 0.5; }
    .modal-cc .modal-body { padding: 24px 28px 8px; }
    .modal-cc .modal-footer { border: none; padding: 0 28px 24px; }
    .cc-label { display: block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 6px; }
    .cc-input, .cc-select {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      background: var(--surface); font-family: 'Sora', sans-serif;
      font-size: 0.85rem; color: var(--ink); outline: none; transition: border-color 0.2s, box-shadow 0.2s; margin-bottom: 14px; appearance: none;
    }
    .cc-input:focus, .cc-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); background: #fff; }
    .cc-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a859e' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 12px; padding-right: 36px; }
    .btn-submit-cc { width: 100%; padding: 13px; background: var(--ink); color: #fff; font-family: 'Sora', sans-serif; font-weight: 700; font-size: 0.88rem; border: none; border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s; }
    .btn-submit-cc:hover { background: var(--accent); }
    .modal-switch { font-size: 0.78rem; color: var(--muted); text-align: center; margin-top: 14px; }
    .modal-switch a { color: var(--accent); text-decoration: none; font-weight: 600; }

    /* Footer */
    .footer-cc { background: var(--ink); padding: 32px 0; }
    .footer-inner { max-width: 1200px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
    .footer-brand { font-size: 1rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }
    .footer-copy { font-size: 0.75rem; color: rgba(255,255,255,0.25); }

    @media (max-width: 860px) {
      .contact-wrap { grid-template-columns: 1fr; padding-inline: 20px; }
      .form-row { grid-template-columns: 1fr; }
      .navbar-inner { padding-inline: 20px; }
      .page-header { padding-inline: 20px; }
      .footer-inner { padding-inline: 20px; flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar-cc" id="mainNav">
    <div class="navbar-inner">
      <a class="nav-brand" href="index.php">🩺 CareCalc</a>
      <div class="nav-links">
        <a class="nav-link-cc" href="index.php">Home</a>
        <a class="nav-link-cc active" href="contact.php">Contact</a>
        <a class="nav-link-cc" href="login.php">Log In</a>
        <button class="nav-btn" data-bs-toggle="modal" data-bs-target="#signupModal">Sign Up</button>
      </div>
    </div>
  </nav>

  <!-- Page Header -->
  <div class="page-header">
    <div class="ph-inner">
      <div class="ph-eyebrow">◇ Get in Touch</div>
      <h1 class="ph-title">We're here<br>to help.</h1>
      <p class="ph-sub">Have questions about your prediction, coverage options or how CareCalc works? Reach out — we'll get back to you promptly.</p>
    </div>
  </div>

  <!-- Contact Grid -->
  <div class="contact-wrap">

    <!-- Info Panel -->
    <div class="info-panel">
      <div class="info-card">
        <div class="info-item">
          <div class="info-icon blue">📧</div>
          <div class="info-text">
            <div class="label">Email</div>
            <div class="value">carecalc.help@gmail.com</div>
          </div>
        </div>
        <div class="info-item">
          <div class="info-icon teal">📞</div>
          <div class="info-text">
            <div class="label">Phone</div>
            <div class="value">+94 71 234 5678</div>
          </div>
        </div>
        <div class="info-item">
          <div class="info-icon amber">🏢</div>
          <div class="info-text">
            <div class="label">Address</div>
            <div class="value">123 Health Street, Colombo, Sri Lanka</div>
          </div>
        </div>
      </div>

      <div class="hours-card">
        <div class="hours-card-title">⏰ Working Hours</div>
        <div class="hours-row">
          <span class="day">Monday – Friday</span>
          <span class="time">09:00 – 18:00</span>
        </div>
        <div class="hours-row">
          <span class="day">Saturday</span>
          <span class="time">10:00 – 14:00</span>
        </div>
        <div class="hours-row">
          <span class="day">Sunday</span>
          <span class="closed">Closed</span>
        </div>
      </div>
    </div>

    <!-- Form Panel -->
    <div class="form-card">
      <div class="form-card-head">
        <h3>Send us a message</h3>
        <p>Fill in the form below and we'll respond within one business day.</p>
      </div>

      <?php if ($success): ?>
        <div class="alert-success-cc">
          ✓ &nbsp;Your message was sent successfully! We'll be in touch soon.
        </div>
      <?php elseif ($error): ?>
        <div class="alert-error-cc">
          ⚠ &nbsp;Something went wrong. Please try again.
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-row">
          <div class="form-field">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Your full name" required>
          </div>
          <div class="form-field">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>
        </div>
        <div class="form-field">
          <label>Subject</label>
          <input type="text" name="subject" placeholder="What's this about?" required>
        </div>
        <div class="form-field">
          <label>Message</label>
          <textarea name="message" rows="6" placeholder="Tell us what you need help with..." required></textarea>
        </div>
        <button type="submit" class="btn-send">Send Message →</button>
      </form>
    </div>

  </div>

  <!-- Footer -->
  <footer class="footer-cc">
    <div class="footer-inner">
      <div class="footer-brand">🩺 CareCalc</div>
      <div class="footer-copy">© 2025 CareCalc. All rights reserved.</div>
    </div>
  </footer>

  <!-- Signup Modal -->
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
              <input type="text" name="contact" class="cc-input" placeholder="+94 77 000 0000">
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
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 60));

    function toggleRoleFields() {
      const role = document.getElementById('roleSelect').value;
      document.getElementById('customerFields').style.display = role === 'user' ? 'block' : 'none';
    }
    toggleRoleFields();
  </script>
</body>
</html>