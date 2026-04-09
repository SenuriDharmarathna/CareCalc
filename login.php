<?php
session_start();          // MUST be first — before any include or output
include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Prepared statement — prevents SQL injection
        $stmt = $conn->prepare("SELECT id, username, role, password FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $username, $role, $hashed_password);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    session_regenerate_id(true); // prevent session fixation attacks
                    $_SESSION["user_id"]  = $id;
                    $_SESSION["username"] = $username;
                    $_SESSION["role"]     = $role;

                    if ($role === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: customer_dashboard.php");
                    }
                    exit;
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with that email.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — CareCalc</title>
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
      --error: #ef4444;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Sora', sans-serif;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: var(--ink);
    }

    /* Left panel */
    .left-panel {
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px;
      background: var(--ink);
    }
    .left-panel::before {
      content: '';
      position: absolute;
      top: -100px; right: -100px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(37,99,255,0.2) 0%, transparent 65%);
      pointer-events: none;
    }
    .left-panel::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -60px;
      width: 360px; height: 360px;
      background: radial-gradient(circle, rgba(0,212,170,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .lp-brand {
      font-size: 1.2rem;
      font-weight: 800;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
      position: relative;
      z-index: 1;
    }
    .lp-content {
      position: relative;
      z-index: 1;
    }
    .lp-eyebrow {
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--accent2);
      margin-bottom: 16px;
    }
    .lp-title {
      font-size: 2.4rem;
      font-weight: 800;
      color: #fff;
      letter-spacing: -1px;
      line-height: 1.15;
      margin-bottom: 16px;
    }
    .lp-title span { color: var(--accent2); }
    .lp-sub {
      font-size: 0.85rem;
      color: rgba(255,255,255,0.4);
      line-height: 1.7;
      max-width: 380px;
    }
    .lp-features {
      display: flex;
      flex-direction: column;
      gap: 14px;
      position: relative;
      z-index: 1;
    }
    .lp-feat {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.82rem;
      color: rgba(255,255,255,0.5);
    }
    .lp-feat-dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--accent2);
      flex-shrink: 0;
    }

    /* Right panel */
    .right-panel {
      background: var(--surface);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
    }
    .login-box {
      width: 100%;
      max-width: 400px;
    }
    .login-box h2 {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--ink);
      letter-spacing: -0.5px;
      margin-bottom: 6px;
    }
    .login-box .subtitle {
      font-size: 0.83rem;
      color: var(--muted);
      margin-bottom: 36px;
    }

    .form-field { margin-bottom: 18px; }
    .form-field label {
      display: block;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      margin-bottom: 7px;
    }
    .form-field input {
      width: 100%;
      padding: 13px 16px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      background: var(--card);
      font-family: 'Sora', sans-serif;
      font-size: 0.88rem;
      color: var(--ink);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-field input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
    }

    .error-box {
      background: rgba(239,68,68,0.07);
      border: 1px solid rgba(239,68,68,0.2);
      color: var(--error);
      border-radius: var(--radius-sm);
      padding: 11px 14px;
      font-size: 0.8rem;
      font-weight: 500;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      background: var(--ink);
      color: #fff;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 0.9rem;
      border: none;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-login:hover { background: var(--accent); transform: translateY(-1px); box-shadow: 0 8px 24px var(--accent-glow); }

    .login-divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 24px 0;
      color: var(--muted);
      font-size: 0.75rem;
    }
    .login-divider::before, .login-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .signup-link {
      text-align: center;
      font-size: 0.82rem;
      color: var(--muted);
    }
    .signup-link a {
      color: var(--accent);
      font-weight: 600;
      text-decoration: none;
    }

    @media (max-width: 768px) {
      body { grid-template-columns: 1fr; }
      .left-panel { display: none; }
      .right-panel { padding: 40px 24px; }
    }
  </style>
</head>
<body>

  <!-- Left Panel -->
  <div class="left-panel">
    <div class="lp-brand">🩺 CareCalc</div>
    <div class="lp-content">
      <div class="lp-eyebrow">Welcome back</div>
      <h1 class="lp-title">Your health<br>finances,<br><span>clarified</span>.</h1>
      <p class="lp-sub">Log in to access your premium predictions, coverage recommendations and medical report suggestions.</p>
    </div>
    <div class="lp-features">
      <div class="lp-feat"><div class="lp-feat-dot"></div> AI-estimated annual premiums in seconds</div>
      <div class="lp-feat"><div class="lp-feat-dot"></div> Personalised coverage plan recommendations</div>
      <div class="lp-feat"><div class="lp-feat-dot"></div> Medical report guidance for your insurer</div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="right-panel">
    <div class="login-box">
      <h2>Log in to CareCalc</h2>
      <p class="subtitle">Enter your credentials to continue</p>

      <?php if (!empty($error)): ?>
        <div class="error-box">⚠ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
        </div>
        <div class="form-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login">Log In →</button>
      </form>

      <div class="login-divider">or</div>
      <div class="signup-link">
        Don't have an account? <a href="index.php">Sign up for free</a>
      </div>
    </div>
  </div>

</body>
</html>