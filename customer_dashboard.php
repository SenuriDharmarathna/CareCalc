<?php
session_start();
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }

include "config.php";
$user_id  = $_SESSION["user_id"];
$username = $_SESSION["username"];

// ── Fetch user ────────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT id, username, email, city, contact, role, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$stmt->bind_result($u_id, $u_username, $u_email, $u_city, $u_contact, $u_role, $u_password);
$stmt->fetch();
$user = ["id"=>$u_id,"username"=>$u_username,"email"=>$u_email,"city"=>$u_city,"contact"=>$u_contact,"role"=>$u_role,"password"=>$u_password];
$stmt->close();

// ── Profile update ────────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $email   = trim($_POST["email"] ?? '');
    $city    = trim($_POST["city"] ?? '');
    $contact = trim($_POST["contact"] ?? '');
    $pwd     = !empty($_POST["password"]) ? password_hash($_POST["password"], PASSWORD_DEFAULT) : $user["password"];
    $upd = $conn->prepare("UPDATE users SET email=?, city=?, contact=?, password=? WHERE id=?");
    $upd->bind_param("ssssi", $email, $city, $contact, $pwd, $user_id);
    $profile_msg = $upd->execute()
        ? ['type'=>'success','text'=>'Profile updated successfully.']
        : ['type'=>'error','text'=>'Failed to update. Please try again.'];
    $upd->close();
    // refresh user
    $user['email'] = $email; $user['city'] = $city; $user['contact'] = $contact;
}

// ── Dismiss notification ──────────────────────────────────────────────────────
if (isset($_GET['dismiss_notif']) && is_numeric($_GET['dismiss_notif'])) {
    // We store dismissed notification IDs in session
    $_SESSION['dismissed_notifs'][] = intval($_GET['dismiss_notif']);
    header("Location: customer_dashboard.php#notifications"); exit();
}

// ── Restore last prediction from session ──────────────────────────────────────
$prediction_result = ""; $predicted_value = null; $recommended_plan = null;
$api_monthly = null; $api_quarterly = null; $api_half_yearly = null;
$prediction_error = ""; $has_prediction = $_SESSION['has_prediction'] ?? false;

if ($has_prediction && isset($_SESSION['predicted_value'])) {
    $predicted_value   = $_SESSION['predicted_value'];
    $prediction_result = number_format($predicted_value, 2);
    $recommended_plan  = $_SESSION['recommended_plan'] ?? null;
    $api_monthly       = $_SESSION['api_monthly']      ?? null;
    $api_quarterly     = $_SESSION['api_quarterly']    ?? null;
    $api_half_yearly   = $_SESSION['api_half_yearly']  ?? null;
}

// ── Run new prediction ────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_POST["form_action"] ?? "") === "predict") {
    $prediction_error = ""; $api_error = false;
    $gender=$_POST["gender"]??""; $age=trim($_POST["age"]??""); $bmi=trim($_POST["bmi"]??"");
    $marital=trim($_POST["marital_status"]??""); $children=trim($_POST["children"]??"");
    $district=trim($_POST["district"]??""); $income=trim($_POST["annual_income"]??"");
    $hospitals=trim($_POST["hospitalizations_last_5yrs"]??"");
    $smoker=$_POST["smoker"]??""; $alcohol=$_POST["alcohol_use"]??"";
    $heart=$_POST["heart_disease"]??""; $diabetes=$_POST["diabetes"]??"";
    $hypertension=$_POST["hypertension"]??""; $asthma=$_POST["asthma"]??"";
    $coverage=trim($_POST["coverage_plan"]??"");

    if ($gender===""||$age===""||$bmi===""||$marital===""||$children===""||$district===""||$income===""||$hospitals===""||$smoker===""||$alcohol===""||$heart===""||$diabetes===""||$hypertension===""||$asthma===""||$coverage==="")
        $prediction_error="Please fill in all required fields.";
    elseif (!is_numeric($age)||(int)$age<18) $prediction_error="Age must be at least 18.";
    elseif (!is_numeric($bmi)||(float)$bmi<=0) $prediction_error="BMI must be greater than 0.";
    elseif (!is_numeric($income)||(int)$income<=0) $prediction_error="Annual income must be greater than 0.";
    elseif (!is_numeric($children)||(int)$children<0) $prediction_error="Children cannot be negative.";
    elseif (!is_numeric($hospitals)||(int)$hospitals<0) $prediction_error="Hospitalisations cannot be negative.";

    if (empty($prediction_error)) {
        $data = ["Gender"=>(int)$gender,"Age"=>(int)$age,"BMI"=>(float)$bmi,"Smoker"=>(int)$smoker,"Alcohol_Use"=>(int)$alcohol,"Coverage_Plan"=>$coverage,"District"=>$district,"Heart_Disease"=>(int)$heart,"Diabetes"=>(int)$diabetes,"Hypertension"=>(int)$hypertension,"Asthma"=>(int)$asthma,"Marital_Status"=>$marital,"Number_of_Children"=>(int)$children,"Annual_Income"=>(int)$income,"Hospitalization_Last_5Yrs"=>(int)$hospitals];
        $ch = curl_init("http://127.0.0.1:5000/predict");
        curl_setopt_array($ch, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_CONNECTTIMEOUT=>5]);
        $response = curl_exec($ch); $curl_err = curl_error($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($curl_err) { $prediction_error="Could not connect to prediction service."; $api_error=true; }
        elseif ($http_code!==200) { $prediction_error="Prediction service error (HTTP $http_code)."; $api_error=true; }
        else {
            $result = json_decode($response, true);
            if (isset($result["predicted_cost"]) && $result["status"]==="success") {
                $predicted_value  = (float)$result["predicted_cost"];
                $prediction_result= number_format($predicted_value,2);
                $recommended_plan = $result["plan"] ?? ($predicted_value<120000?"Basic":($predicted_value<400000?"Standard":"Premium"));
                $api_monthly      = (int)($result["monthly"]     ?? round($predicted_value/12));
                $api_quarterly    = (int)($result["quarterly"]   ?? round($predicted_value/4));
                $api_half_yearly  = (int)($result["half_yearly"] ?? round($predicted_value/2));
                // Save prediction to session
                $_SESSION['has_prediction']   = true;
                $_SESSION['prediction_input'] = $data;
                $_SESSION['predicted_value']  = $predicted_value;
                $_SESSION['recommended_plan'] = $recommended_plan;
                $_SESSION['api_monthly']      = $api_monthly;
                $_SESSION['api_quarterly']    = $api_quarterly;
                $_SESSION['api_half_yearly']  = $api_half_yearly;
                $has_prediction = true;
                $ins = $conn->prepare("INSERT INTO predictions (user_id,predicted_premium,recommended_plan,gender,age,bmi,smoker,alcohol_use,coverage_plan,district,heart_disease,diabetes,hypertension,asthma,marital_status,number_of_children,annual_income,hospitalization_last_5yrs,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                if ($ins) {
                    $ins->bind_param(
                        "idsiidiissiiiisiii",
                        $user_id, $predicted_value, $recommended_plan,
                        $data["Gender"], $data["Age"], $data["BMI"],
                        $data["Smoker"], $data["Alcohol_Use"],
                        $data["Coverage_Plan"], $data["District"],
                        $data["Heart_Disease"], $data["Diabetes"],
                        $data["Hypertension"], $data["Asthma"],
                        $data["Marital_Status"], $data["Number_of_Children"],
                        $data["Annual_Income"], $data["Hospitalization_Last_5Yrs"]
                    );
                    $ins->execute();
                    $ins->close();
                }
                $_SESSION['show_result'] = true;
                // Variables already set above — result will render directly below
            } else { $prediction_error=$result["error"]??"Unexpected response."; $api_error=true; }
        }
    }
    if (!empty($prediction_error)) { $prediction_result="error"; $has_prediction=false; }
}

// ── Prediction history ────────────────────────────────────────────────────────
$history = $conn->prepare("SELECT id, predicted_premium, recommended_plan, coverage_plan, district, age, created_at FROM predictions WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$history->bind_param("i", $user_id); $history->execute();
$all_preds = [];
// Compatible with all PHP/MySQL drivers (no get_result needed)
$history->bind_result($h_id,$h_premium,$h_plan,$h_coverage,$h_district,$h_age,$h_created);
while ($history->fetch()) {
    $all_preds[] = ['id'=>$h_id,'predicted_premium'=>$h_premium,'recommended_plan'=>$h_plan,'coverage_plan'=>$h_coverage,'district'=>$h_district,'age'=>$h_age,'created_at'=>$h_created];
}
$history->close();
$total_preds = count($all_preds);

// ── Risk score (simple heuristic from last prediction input) ──────────────────
$risk_score = null; $risk_label = ''; $risk_color = '';
if ($has_prediction && isset($_SESSION['prediction_input'])) {
    $inp = $_SESSION['prediction_input'];
    $score = 0;
    $score += ($inp['Smoker']??0) * 25;
    $score += ($inp['Heart_Disease']??0) * 20;
    $score += ($inp['Diabetes']??0) * 18;
    $score += ($inp['Hypertension']??0) * 15;
    $score += ($inp['Asthma']??0) * 10;
    $score += ($inp['Alcohol_Use']??0) * 10;
    $score += min(($inp['Hospitalization_Last_5Yrs']??0) * 8, 20);
    $age_score = max(0, (($inp['Age']??30) - 30) / 40 * 20);
    $bmi = $inp['BMI'] ?? 22;
    $bmi_score = ($bmi > 30) ? 15 : (($bmi > 25) ? 8 : 0);
    $score += $age_score + $bmi_score;
    $risk_score = min(100, round($score));
    if ($risk_score < 30) { $risk_label='Low Risk'; $risk_color='#10b981'; }
    elseif ($risk_score < 60) { $risk_label='Moderate Risk'; $risk_color='#f97316'; }
    else { $risk_label='High Risk'; $risk_color='#ef4444'; }
}

// ── Plans from DB ─────────────────────────────────────────────────────────────
$plans_data = [];
// Check if insurance_plans table exists
$tbl_check = $conn->query("SHOW TABLES LIKE 'insurance_plans'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $pr = $conn->query("SELECT * FROM insurance_plans WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if ($pr) $plans_data = $pr->fetch_all(MYSQLI_ASSOC);
}
// Fallback static plans
if (empty($plans_data)) {
    $plans_data = [
        ['id'=>1,'name'=>'Basic','slug'=>'basic','tagline'=>'Essential everyday coverage','color_hex'=>'#2563ff','annual_premium_min'=>30000,'annual_premium_max'=>120000,'inpatient_limit'=>300000,'outpatient_limit'=>50000,'surgery_limit'=>150000,'icu_limit'=>200000,'dental_covered'=>0,'optical_covered'=>0,'maternity_covered'=>0,'emergency_covered'=>1,'pre_existing_covered'=>0,'waiting_period_months'=>3,'max_age_limit'=>65,'features'=>'Inpatient hospitalisation;Emergency ambulance;24/7 helpline;Basic diagnostics','exclusions'=>'Dental & optical;Cosmetic procedures;Pre-existing conditions'],
        ['id'=>2,'name'=>'Standard','slug'=>'standard','tagline'=>'Balanced protection for individuals & families','color_hex'=>'#00d4aa','annual_premium_min'=>120000,'annual_premium_max'=>400000,'inpatient_limit'=>750000,'outpatient_limit'=>150000,'surgery_limit'=>400000,'icu_limit'=>500000,'dental_covered'=>1,'optical_covered'=>1,'maternity_covered'=>0,'emergency_covered'=>1,'pre_existing_covered'=>0,'waiting_period_months'=>6,'max_age_limit'=>70,'features'=>'All Basic benefits;Outpatient consultations;Dental & optical;Specialist referrals','exclusions'=>'Cosmetic procedures;Pre-existing conditions (first year);Maternity'],
        ['id'=>3,'name'=>'Premium','slug'=>'premium','tagline'=>'Comprehensive cover with zero compromise','color_hex'=>'#f97316','annual_premium_min'=>400000,'annual_premium_max'=>1200000,'inpatient_limit'=>2000000,'outpatient_limit'=>400000,'surgery_limit'=>1000000,'icu_limit'=>1500000,'dental_covered'=>1,'optical_covered'=>1,'maternity_covered'=>1,'emergency_covered'=>1,'pre_existing_covered'=>1,'waiting_period_months'=>0,'max_age_limit'=>75,'features'=>'All Standard benefits;Maternity & newborn;Pre-existing conditions;International emergency;Annual health check','exclusions'=>'Experimental treatments;Self-inflicted injuries'],
    ];
}

// ── Active tab ────────────────────────────────────────────────────────────────
$active_tab = $_GET['tab'] ?? 'predict';
// Always show predict tab after a new prediction
if (isset($_GET['predicted'])) $active_tab = 'predict';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CareCalc — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --ink: #0a0f1e; --ink-soft: #1e2740; --surface: #f4f6fb; --card: #ffffff;
    --accent: #2563ff; --accent-glow: rgba(37,99,255,0.18);
    --accent2: #00d4aa; --accent2-glow: rgba(0,212,170,0.15);
    --warn: #f97316; --danger: #ef4444; --success: #10b981;
    --muted: #7a859e; --border: rgba(30,39,64,0.09);
    --radius: 18px; --radius-sm: 10px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Sora',sans-serif;background:var(--surface);color:var(--ink);min-height:100vh;}

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;width:72px;height:100vh;background:var(--ink);display:flex;flex-direction:column;align-items:center;padding:24px 0;z-index:200;transition:width 0.32s cubic-bezier(0.4,0,0.2,1);overflow:hidden;}
.sidebar:hover{width:220px;}
.sidebar-logo{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:40px;white-space:nowrap;width:100%;display:flex;align-items:center;padding-left:20px;gap:12px;}
.sidebar-logo-icon{font-size:1.5rem;min-width:32px;}
.sidebar-logo-text{opacity:0;transition:opacity 0.2s;font-size:1.1rem;}
.sidebar:hover .sidebar-logo-text{opacity:1;}
.nav-item-s{width:100%;display:flex;align-items:center;padding:12px 20px;gap:14px;color:rgba(255,255,255,0.45);text-decoration:none;transition:all 0.2s;white-space:nowrap;font-size:0.88rem;font-weight:500;position:relative;cursor:pointer;}
.nav-item-s:hover,.nav-item-s.active{color:#fff;background:rgba(255,255,255,0.07);}
.nav-item-s.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--accent2);border-radius:0 3px 3px 0;}
.nav-icon{font-size:1.2rem;min-width:32px;text-align:center;}
.nav-label{opacity:0;transition:opacity 0.2s;}
.sidebar:hover .nav-label{opacity:1;}
.sidebar-bottom{margin-top:auto;width:100%;}
.user-chip{display:flex;align-items:center;gap:12px;padding:14px 20px;cursor:pointer;border-top:1px solid rgba(255,255,255,0.07);}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem;flex-shrink:0;}
.user-info{opacity:0;transition:opacity 0.2s;}
.sidebar:hover .user-info{opacity:1;}
.user-name{font-size:0.82rem;font-weight:600;color:#fff;}
.user-role{font-size:0.72rem;color:rgba(255,255,255,0.4);}

/* MAIN */
.main{margin-left:72px;min-height:100vh;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:20px 40px;background:var(--card);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.topbar-left h1{font-size:1.45rem;font-weight:800;color:var(--ink);letter-spacing:-0.5px;}
.topbar-left p{font-size:0.8rem;color:var(--muted);margin-top:3px;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.topbar-btn{background:var(--card);border:1px solid var(--border);border-radius:50px;padding:8px 18px;font-size:0.82rem;font-weight:600;color:var(--ink);text-decoration:none;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:6px;font-family:'Sora',sans-serif;}
.topbar-btn:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.topbar-btn.danger:hover{background:#ef4444;border-color:#ef4444;color:#fff;}

/* HERO */
.hero-strip{margin:24px 40px 0;background:var(--ink);border-radius:var(--radius);padding:34px 44px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;}
.hero-strip::before{content:'';position:absolute;top:-60px;right:-60px;width:280px;height:280px;background:radial-gradient(circle,rgba(37,99,255,0.3) 0%,transparent 70%);pointer-events:none;}
.hero-strip::after{content:'';position:absolute;bottom:-80px;left:30%;width:220px;height:220px;background:radial-gradient(circle,rgba(0,212,170,0.2) 0%,transparent 70%);pointer-events:none;}
.hero-text h2{font-size:1.55rem;font-weight:800;color:#fff;letter-spacing:-0.5px;line-height:1.25;}
.hero-text p{font-size:0.83rem;color:rgba(255,255,255,0.5);margin-top:6px;}
.hero-cta{display:inline-flex;align-items:center;gap:8px;background:var(--accent2);color:var(--ink);font-weight:700;font-size:0.84rem;padding:11px 22px;border-radius:50px;text-decoration:none;margin-top:14px;transition:all 0.2s;}
.hero-cta:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(0,212,170,0.35);color:var(--ink);}
.hero-stats{display:flex;gap:14px;}
.hstat{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:16px 22px;text-align:center;min-width:120px;}
.hstat-val{font-size:1.25rem;font-weight:800;color:#fff;font-family:'JetBrains Mono',monospace;}
.hstat-label{font-size:0.7rem;color:rgba(255,255,255,0.4);margin-top:4px;}

/* TABS */
.tab-bar{margin:24px 40px 0;display:flex;gap:4px;background:var(--card);border-radius:var(--radius);padding:6px;border:1px solid var(--border);}
.tab-btn{flex:1;padding:10px 16px;border-radius:12px;font-size:0.82rem;font-weight:600;color:var(--muted);background:none;border:none;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:7px;font-family:'Sora',sans-serif;}
.tab-btn:hover{background:var(--surface);color:var(--ink);}
.tab-btn.active{background:var(--ink);color:#fff;}
.tab-content{display:none;padding:24px 40px 60px;}
.tab-content.active{display:block;}

/* CONTENT LAYOUT */
.two-col{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start;}
.card-pro{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:28px 30px;transition:box-shadow 0.2s;}
.card-pro:hover{box-shadow:0 12px 40px rgba(10,15,30,0.07);}
.card-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;}
.card-head-left h3{font-size:1rem;font-weight:700;color:var(--ink);}
.card-head-left p{font-size:0.76rem;color:var(--muted);margin-top:3px;}
.badge-pill{font-size:0.68rem;font-weight:600;padding:4px 11px;border-radius:50px;background:var(--accent-glow);color:var(--accent);white-space:nowrap;}
.badge-pill.green{background:var(--accent2-glow);color:#00a87e;}
.badge-pill.amber{background:rgba(249,115,22,0.1);color:var(--warn);}

/* FORM */
.form-section-label{font-size:0.68rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.form-section-label::after{content:'';flex:1;height:1px;background:var(--border);}
.field-group{display:grid;gap:12px;margin-bottom:20px;}
.field-group.cols-3{grid-template-columns:repeat(3,1fr);}
.field-group.cols-2{grid-template-columns:repeat(2,1fr);}
.field-group.cols-4{grid-template-columns:repeat(4,1fr);}
.field-group.cols-1{grid-template-columns:1fr;}
.field-wrap label{display:block;font-size:0.7rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:5px;}
.field-wrap select,.field-wrap input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);font-family:'Sora',sans-serif;font-size:0.84rem;color:var(--ink);transition:border-color 0.2s,box-shadow 0.2s;appearance:none;outline:none;}
.field-wrap select:focus,.field-wrap input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow);background:#fff;}
.field-wrap select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a859e' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;}
.btn-predict{width:100%;padding:14px;background:var(--ink);color:#fff;font-family:'Sora',sans-serif;font-size:0.9rem;font-weight:700;border:none;border-radius:var(--radius-sm);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:all 0.2s;margin-top:8px;}
.btn-predict:hover{background:var(--accent);transform:translateY(-1px);box-shadow:0 8px 24px var(--accent-glow);}

/* RESULT */
.result-panel{background:linear-gradient(135deg,var(--ink) 0%,#1a2540 100%);border-radius:var(--radius);padding:26px;margin-top:18px;position:relative;overflow:hidden;animation:slideUp 0.4s ease;}
@keyframes slideUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
.result-panel::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(0,212,170,0.25) 0%,transparent 70%);}
.result-label{font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.45);letter-spacing:0.1em;text-transform:uppercase;}
.result-amount{font-family:'JetBrains Mono',monospace;font-size:1.9rem;font-weight:700;color:var(--accent2);margin:6px 0 4px;letter-spacing:-1px;}
.result-sub{font-size:0.76rem;color:rgba(255,255,255,0.4);}
.breakdown-row{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:16px;}
.breakdown-item{background:rgba(255,255,255,0.06);border-radius:10px;padding:11px;text-align:center;}
.breakdown-val{font-family:'JetBrains Mono',monospace;font-size:0.8rem;font-weight:600;color:#fff;}
.breakdown-label{font-size:0.63rem;color:rgba(255,255,255,0.35);margin-top:2px;}
.btn-coverage{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;background:var(--accent2);color:var(--ink);font-family:'Sora',sans-serif;font-weight:700;font-size:0.84rem;border:none;border-radius:var(--radius-sm);cursor:pointer;text-decoration:none;margin-top:14px;transition:all 0.2s;}
.btn-coverage:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(0,212,170,0.4);color:var(--ink);}
.error-chip{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#dc2626;border-radius:10px;padding:11px 15px;font-size:0.81rem;font-weight:500;margin-top:14px;}

/* RIGHT CARDS */
.sidebar-cards{display:flex;flex-direction:column;gap:16px;}
.status-card{background:var(--card);border-radius:var(--radius);border:1px solid var(--border);padding:22px;}
.status-card-title{font-size:0.78rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.status-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);}
.status-row:last-child{border-bottom:none;padding-bottom:0;}
.status-key{font-size:0.77rem;color:var(--muted);}
.status-val{font-size:0.8rem;font-weight:600;color:var(--ink);font-family:'JetBrains Mono',monospace;}

/* RISK METER */
.risk-meter-wrap{margin-top:12px;}
.risk-track{height:10px;background:var(--surface);border-radius:5px;overflow:hidden;margin:10px 0;}
.risk-fill{height:100%;border-radius:5px;transition:width 1s ease;}
.risk-labels{display:flex;justify-content:space-between;font-size:0.65rem;color:var(--muted);}
.risk-score-display{display:flex;align-items:baseline;gap:6px;margin-top:4px;}
.risk-score-num{font-family:'JetBrains Mono',monospace;font-size:2rem;font-weight:800;}
.risk-score-label{font-size:0.8rem;font-weight:600;}

/* HISTORY TAB */
.history-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.cc-table{width:100%;border-collapse:collapse;}
.cc-table thead th{padding:11px 18px;font-size:0.63rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.5);background:var(--ink);text-align:left;white-space:nowrap;}
.cc-table tbody td{padding:13px 18px;font-size:0.8rem;color:var(--ink);border-bottom:1px solid var(--border);vertical-align:middle;}
.cc-table tbody tr:last-child td{border-bottom:none;}
.cc-table tbody tr:hover td{background:rgba(244,246,251,0.7);}
.plan-chip{display:inline-block;font-size:0.61rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:3px 9px;border-radius:20px;}
.plan-chip.plan-basic{background:rgba(37,99,255,0.1);color:var(--accent);}
.plan-chip.plan-standard{background:var(--accent2-glow);color:#00a87e;}
.plan-chip.plan-premium{background:rgba(249,115,22,0.1);color:var(--warn);}
.mono{font-family:'JetBrains Mono',monospace;font-size:0.75rem;color:var(--muted);}
.empty-state{padding:48px 24px;text-align:center;color:var(--muted);font-size:0.82rem;}
.empty-icon{font-size:2.4rem;margin-bottom:10px;}
.history-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
.hist-stat{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;}
.hist-stat-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted);margin-bottom:5px;}
.hist-stat-val{font-family:'JetBrains Mono',monospace;font-size:1.15rem;font-weight:700;color:var(--ink);}

/* PLANS TAB */
.plans-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
.plan-card{background:var(--card);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;transition:box-shadow 0.2s,transform 0.2s;position:relative;}
.plan-card:hover{box-shadow:0 14px 40px rgba(10,15,30,0.1);transform:translateY(-3px);}
.plan-card.recommended{border-width:2px;}
.plan-card-top{height:5px;}
.plan-rec-badge{position:absolute;top:16px;right:16px;font-size:0.62rem;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(0,212,170,0.15);color:#00a87e;border:1px solid rgba(0,212,170,0.3);}
.plan-card-body{padding:22px 22px 16px;}
.plan-name{font-size:1.05rem;font-weight:800;color:var(--ink);margin-bottom:3px;}
.plan-tag{font-size:0.74rem;color:var(--muted);margin-bottom:18px;}
.plan-price-range{margin-bottom:16px;}
.plan-price-label{font-size:0.62rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:4px;}
.plan-price-val{font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:700;color:var(--ink);}
.plan-limits{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;}
.plan-limit-item{background:var(--surface);border-radius:var(--radius-sm);padding:9px 12px;}
.plan-limit-label{font-size:0.59rem;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);margin-bottom:3px;}
.plan-limit-val{font-family:'JetBrains Mono',monospace;font-size:0.78rem;font-weight:600;color:var(--ink);}
.plan-features-list{margin-bottom:14px;}
.pf-item{display:flex;align-items:center;gap:7px;font-size:0.76rem;color:var(--ink);padding:3px 0;}
.pf-item.excluded{color:var(--muted);}
.pf-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.plan-card-footer{padding:14px 22px;border-top:1px solid var(--border);display:flex;gap:8px;}
.plan-btn{flex:1;padding:10px;border-radius:var(--radius-sm);font-size:0.8rem;font-weight:700;border:none;cursor:pointer;font-family:'Sora',sans-serif;transition:all 0.2s;text-decoration:none;display:block;text-align:center;}
.plan-btn.primary{background:var(--ink);color:#fff;}
.plan-btn.primary:hover{background:var(--accent);}
.plan-btn.secondary{background:var(--surface);color:var(--ink);border:1px solid var(--border);}
.plan-btn.secondary:hover{background:var(--ink);color:#fff;}

/* JOURNEY */
.progress-steps{display:flex;flex-direction:column;gap:14px;}
.step-item{display:flex;align-items:flex-start;gap:14px;}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.74rem;font-weight:700;flex-shrink:0;margin-top:1px;}
.step-dot.done{background:var(--accent2-glow);color:#00a87e;}
.step-dot.active{background:var(--accent-glow);color:var(--accent);}
.step-dot.pending{background:var(--surface);color:var(--muted);border:1.5px solid var(--border);}
.step-body h5{font-size:0.81rem;font-weight:600;color:var(--ink);}
.step-body p{font-size:0.72rem;color:var(--muted);margin-top:2px;}

/* ACTION CARDS */
.action-card{border-radius:var(--radius);padding:18px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;text-decoration:none;transition:all 0.2s;border:1.5px solid transparent;}
.action-card.blue{background:var(--accent-glow);border-color:rgba(37,99,255,0.15);}
.action-card.teal{background:var(--accent2-glow);border-color:rgba(0,212,170,0.2);}
.action-card.amber{background:rgba(249,115,22,0.08);border-color:rgba(249,115,22,0.15);}
.action-card:hover{transform:translateY(-2px);}
.action-icon{font-size:1.5rem;}
.action-text h5{font-size:0.86rem;font-weight:700;color:var(--ink);}
.action-text p{font-size:0.71rem;color:var(--muted);margin-top:2px;}
.action-lock{font-size:0.68rem;color:var(--muted);background:var(--surface);padding:2px 9px;border-radius:50px;border:1px solid var(--border);}

/* PROFILE MODAL */
.modal-content{border:none;border-radius:var(--radius);box-shadow:0 32px 80px rgba(10,15,30,0.18);}
.modal-header{border-bottom:1px solid var(--border);padding:22px 26px 18px;}
.modal-body{padding:22px 26px;}
.modal-title{font-size:1rem;font-weight:700;color:var(--ink);}
.modal-field label{font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:5px;display:block;}
.modal-field input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);font-family:'Sora',sans-serif;font-size:0.84rem;color:var(--ink);outline:none;transition:border-color 0.2s,box-shadow 0.2s;margin-bottom:14px;}
.modal-field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow);background:#fff;}
.btn-update{width:100%;padding:12px;background:var(--ink);color:#fff;font-family:'Sora',sans-serif;font-weight:700;font-size:0.87rem;border:none;border-radius:var(--radius-sm);cursor:pointer;transition:all 0.2s;}
.btn-update:hover{background:var(--accent);}

/* ALERTS */
.alert-strip{padding:11px 14px;border-radius:var(--radius-sm);font-size:0.8rem;font-weight:500;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-strip.success{background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);color:var(--success);}
.alert-strip.error{background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);color:var(--danger);}

/* COMPARE TOGGLE */
.compare-bar{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
.compare-bar-title{font-size:0.82rem;font-weight:700;color:var(--ink);}
.compare-chips{display:flex;gap:8px;flex-wrap:wrap;}
.compare-chip{padding:5px 14px;border-radius:20px;font-size:0.75rem;font-weight:600;border:1.5px solid var(--border);background:var(--surface);color:var(--muted);cursor:pointer;transition:all 0.2s;}
.compare-chip.selected{background:var(--ink);color:#fff;border-color:var(--ink);}
.compare-table-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:20px;}

/* FADE IN */
.fade-slide{opacity:0;transform:translateY(14px);animation:fadeSlide 0.5s ease forwards;}
@keyframes fadeSlide{to{opacity:1;transform:translateY(0);}}
.d1{animation-delay:0.05s;}.d2{animation-delay:0.1s;}.d3{animation-delay:0.15s;}.d4{animation-delay:0.2s;}

@media(max-width:1100px){.two-col{grid-template-columns:1fr;}.plans-grid{grid-template-columns:1fr 1fr;}}
@media(max-width:768px){.main{margin-left:0;}.sidebar{display:none;}.topbar{padding:16px 20px;}.hero-strip{margin:16px 20px 0;padding:22px 22px;}.tab-content{padding:20px 20px 40px;}.two-col{grid-template-columns:1fr;}.history-stats-row{grid-template-columns:1fr 1fr;}.plans-grid{grid-template-columns:1fr;}.field-group.cols-3,.field-group.cols-4{grid-template-columns:1fr 1fr;}.field-group.cols-2{grid-template-columns:1fr;}.hero-stats{display:none;}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <span class="sidebar-logo-icon">🩺</span>
        <span class="sidebar-logo-text">CareCalc</span>
    </div>
    <a href="?tab=predict" class="nav-item-s <?= $active_tab==='predict'?'active':'' ?>"><span class="nav-icon">◎</span><span class="nav-label">Get a Quote</span></a>
    <a href="?tab=history" class="nav-item-s <?= $active_tab==='history'?'active':'' ?>"><span class="nav-icon">⬕</span><span class="nav-label">My Predictions</span></a>
    <a href="?tab=plans" class="nav-item-s <?= $active_tab==='plans'?'active':'' ?>"><span class="nav-icon">◈</span><span class="nav-label">Coverage Plans</span></a>
    <a href="contact.php" class="nav-item-s"><span class="nav-icon">◇</span><span class="nav-label">Support</span></a>
    <div class="sidebar-bottom">
        <div class="user-chip" data-bs-toggle="modal" data-bs-target="#profileModal">
            <div class="avatar"><?= strtoupper(substr($username,0,2)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($username) ?></div>
                <div class="user-role">Customer</div>
            </div>
        </div>
    </div>
</aside>

<main class="main">

    <!-- TOPBAR -->
    <div class="topbar fade-slide">
        <div class="topbar-left">
            <h1>Good <?= date('H')<12?'morning':(date('H')<17?'afternoon':'evening') ?>, <?= htmlspecialchars(explode(' ',$username)[0]) ?> 👋</h1>
            <p><?= date('l, F j, Y') ?> &nbsp;·&nbsp; Your health financial hub</p>
        </div>
        <div class="topbar-right">
            <button class="topbar-btn" data-bs-toggle="modal" data-bs-target="#profileModal">
                <span><?= strtoupper(substr($username,0,1)) ?></span> Profile
            </button>
            <a href="index.php" class="topbar-btn danger">↩ Logout</a>
        </div>
    </div>

    <!-- HERO -->
    <div class="hero-strip fade-slide d1">
        <div class="hero-text">
            <h2>Your insurance<br>intelligence centre.</h2>
            <p>AI-powered predictions tailored to your health & lifestyle profile.</p>
            <a href="?tab=predict" class="hero-cta">＋ New Prediction</a>
        </div>
        <div class="hero-stats">
            <div class="hstat">
                <div class="hstat-val"><?= $total_preds ?></div>
                <div class="hstat-label">Predictions</div>
            </div>
            <div class="hstat">
                <div class="hstat-val"><?= $has_prediction && isset($_SESSION['predicted_value']) ? 'LKR '.number_format($_SESSION['predicted_value']/12,0) : '—' ?></div>
                <div class="hstat-label">Est. Monthly</div>
            </div>
            <div class="hstat">
                <div class="hstat-val"><?= $has_prediction ? ($recommended_plan ?? '—') : '—' ?></div>
                <div class="hstat-label">Recommended</div>
            </div>
            <?php if ($risk_score !== null): ?>
            <div class="hstat">
                <div class="hstat-val" style="color:<?= $risk_color ?>;"><?= $risk_score ?></div>
                <div class="hstat-label">Risk Score</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABS -->
    <div class="tab-bar fade-slide d2">
        <button class="tab-btn <?= $active_tab==='predict'?'active':'' ?>" onclick="switchTab('predict')">◎ Get a Quote</button>
        <button class="tab-btn <?= $active_tab==='history'?'active':'' ?>" onclick="switchTab('history')">
            ⬕ My Predictions
            <?php if ($total_preds > 0): ?><span style="background:var(--accent2);color:var(--ink);border-radius:20px;padding:1px 7px;font-size:0.62rem;"><?= $total_preds ?></span><?php endif; ?>
        </button>
        <button class="tab-btn <?= $active_tab==='plans'?'active':'' ?>" onclick="switchTab('plans')">◈ Coverage Plans</button>
    </div>

    <!-- ═══════════════ TAB 1: PREDICT ═══════════════ -->
    <div class="tab-content <?= $active_tab==='predict'?'active':'' ?>" id="tab-predict">
        <?php if (isset($profile_msg)): ?>
        <div class="alert-strip <?= $profile_msg['type'] ?>"><?= htmlspecialchars($profile_msg['text']) ?></div>
        <?php endif; ?>
        <div class="two-col">
            <!-- LEFT: Form -->
            <div>
                <div class="card-pro fade-slide d2">
                    <div class="card-head">
                        <div class="card-head-left">
                            <h3>Insurance Premium Calculator</h3>
                            <p>Fill in your details to get an instant AI-powered estimate</p>
                        </div>
                        <span class="badge-pill">AI-Powered</span>
                    </div>

                    <!-- <form method="POST" action="" id="predictForm"> -->
                        <form method="POST" action="" id="predictForm">
                         <input type="hidden" name="form_action" value="predict">
                        <div class="form-section-label">Personal Information</div>
                        <div class="field-group cols-3">
                            <div class="field-wrap"><label>Gender</label>
                                <select name="gender" required>
                                    <option value="0">Male</option>
                                    <option value="1">Female</option>
                                </select>
                            </div>
                            <div class="field-wrap"><label>Age</label>
                                <input type="number" name="age" placeholder="e.g. 34" min="18" required>
                            </div>
                            <div class="field-wrap"><label>BMI</label>
                                <input type="number" step="0.1" name="bmi" placeholder="e.g. 23.5" min="0.1" required>
                            </div>
                        </div>
                        <div class="field-group cols-3">
                            <div class="field-wrap"><label>Marital Status</label>
                                <select name="marital_status" required>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                </select>
                            </div>
                            <div class="field-wrap"><label>Children</label>
                                <input type="number" name="children" placeholder="0, 1, 2…" min="0" required>
                            </div>
                            <div class="field-wrap"><label>District</label>
                                <select name="district" required>
                                    <?php foreach(['Colombo','Gampaha','Kalutara','Kandy','Galle','Kurunegala','Matara','Matale','Ratnapura','Badulla','Hambantota','Jaffna','Trincomalee','Batticaloa','Anuradhapura','Polonnaruwa','Kegalle','Nuwara Eliya','Monaragala','Puttalam','Vavuniya','Mannar','Ampara','Kilinochchi','Mullaitivu'] as $d): ?>
                                    <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field-group cols-2">
                            <div class="field-wrap"><label>Annual Income (LKR)</label>
                                <input type="number" name="annual_income" placeholder="e.g. 1,200,000" min="1" required>
                            </div>
                            <div class="field-wrap"><label>Hospitalisations (Last 5 Yrs)</label>
                                <input type="number" name="hospitalizations_last_5yrs" placeholder="0, 1, 2…" min="0" required>
                            </div>
                        </div>

                        <div class="form-section-label">Lifestyle Factors</div>
                        <div class="field-group cols-2">
                            <div class="field-wrap"><label>Smoking Status</label>
                                <select name="smoker" required>
                                    <option value="0">Non-Smoker</option>
                                    <option value="1">Smoker</option>
                                </select>
                            </div>
                            <div class="field-wrap"><label>Alcohol Use</label>
                                <select name="alcohol_use" required>
                                    <option value="0">Does Not Use</option>
                                    <option value="1">Uses Alcohol</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-section-label">Pre-existing Conditions</div>
                        <div class="field-group cols-4">
                            <div class="field-wrap"><label>Heart Disease</label>
                                <select name="heart_disease" required><option value="0">None</option><option value="1">Yes</option></select>
                            </div>
                            <div class="field-wrap"><label>Diabetes</label>
                                <select name="diabetes" required><option value="0">None</option><option value="1">Yes</option></select>
                            </div>
                            <div class="field-wrap"><label>Hypertension</label>
                                <select name="hypertension" required><option value="0">None</option><option value="1">Yes</option></select>
                            </div>
                            <div class="field-wrap"><label>Asthma</label>
                                <select name="asthma" required><option value="0">None</option><option value="1">Yes</option></select>
                            </div>
                        </div>

                        <div class="form-section-label">Coverage Preference</div>
                        <div class="field-group cols-1" style="max-width:300px;">
                            <div class="field-wrap"><label>Preferred Coverage Plan</label>
                                <select name="coverage_plan" required>
                                    <?php foreach($plans_data as $p): ?>
                                    <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit"  class="btn-predict" id="predictBtn">
                            <span id="predictBtnIcon">⟳</span>
                            <span id="predictBtnText">Calculate My Premium</span>
                        </button>
                    </form>

                    

                    <!-- Result -->
                    <?php if (!empty($prediction_error)): ?>
                    <div class="error-chip">⚠ <?= htmlspecialchars($prediction_error) ?></div>
                    <?php elseif ($prediction_result && $prediction_result !== 'error' && $predicted_value !== null): ?>
                    <div class="result-panel">
                        <div class="result-label">Estimated Annual Premium</div>
                        <div class="result-amount">LKR <?= number_format($predicted_value,0) ?></div>
                        <div class="result-sub">Based on your submitted health &amp; lifestyle profile</div>
                        <div class="breakdown-row">
                            <div class="breakdown-item">
                                <div class="breakdown-val">LKR <?= number_format($api_half_yearly ?? round($predicted_value/2),0) ?></div>
                                <div class="breakdown-label">Half-Yearly</div>
                            </div>
                            <div class="breakdown-item">
                                <div class="breakdown-val">LKR <?= number_format($api_quarterly ?? round($predicted_value/4),0) ?></div>
                                <div class="breakdown-label">Quarterly</div>
                            </div>
                            <div class="breakdown-item">
                                <div class="breakdown-val">LKR <?= number_format($api_monthly ?? round($predicted_value/12),0) ?></div>
                                <div class="breakdown-label">Monthly</div>
                            </div>
                        </div>
                        <?php if ($recommended_plan): ?>
                        <a href="recommended_coverage.php?premium=<?= urlencode($predicted_value) ?>&plan=<?= urlencode($recommended_plan) ?>" class="btn-coverage">
                            View Recommended Coverage &nbsp;·&nbsp; <?= htmlspecialchars($recommended_plan) ?> Plan →
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: Status cards -->
            <div class="sidebar-cards fade-slide d3">

                <!-- Your Status -->
                <div class="status-card">
                    <div class="status-card-title">📊 Your Status</div>
                    <div class="status-row">
                        <span class="status-key">Prediction</span>
                        <span class="status-val" style="font-family:'Sora',sans-serif;font-size:0.77rem;color:<?= $has_prediction?'#00a87e':'var(--muted)'; ?>">
                            <?= $has_prediction?'✓ Done':'Pending' ?>
                        </span>
                    </div>
                    <div class="status-row">
                        <span class="status-key">Annual Est.</span>
                        <span class="status-val"><?= ($has_prediction&&isset($_SESSION['predicted_value']))?'LKR '.number_format($_SESSION['predicted_value'],0):'—' ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-key">Monthly Est.</span>
                        <span class="status-val"><?= ($has_prediction&&isset($_SESSION['predicted_value']))?'LKR '.number_format($_SESSION['predicted_value']/12,0):'—' ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-key">Recommended Plan</span>
                        <span class="status-val" style="font-family:'Sora',sans-serif;font-size:0.77rem;"><?= ($has_prediction&&$recommended_plan)?$recommended_plan:'—' ?></span>
                    </div>
                    <div class="status-row">
                        <span class="status-key">Total Predictions</span>
                        <span class="status-val"><?= $total_preds ?></span>
                    </div>
                </div>

                <!-- Risk Score Card -->
                <?php if ($risk_score !== null): ?>
                <div class="status-card">
                    <div class="status-card-title">🎯 Health Risk Profile</div>
                    <div class="risk-score-display">
                        <span class="risk-score-num" style="color:<?= $risk_color ?>;"><?= $risk_score ?></span>
                        <span class="risk-score-label" style="color:<?= $risk_color ?>;">/100 · <?= $risk_label ?></span>
                    </div>
                    <div class="risk-meter-wrap">
                        <div class="risk-track">
                            <div class="risk-fill" style="width:<?= $risk_score ?>%;background:<?= $risk_score<30?'var(--success)':($risk_score<60?'var(--warn)':'var(--danger)') ?>;"></div>
                        </div>
                        <div class="risk-labels"><span>Low</span><span>Moderate</span><span>High</span></div>
                    </div>
                    <div style="margin-top:12px;font-size:0.73rem;color:var(--muted);line-height:1.6;">
                        <?php if ($risk_score < 30): ?>
                            Your health profile shows low risk factors. You may qualify for lower premiums.
                        <?php elseif ($risk_score < 60): ?>
                            Moderate risk factors detected. Regular checkups are recommended.
                        <?php else: ?>
                            Higher risk factors present. Premium coverage may best suit your needs.
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Journey Steps -->
                <div class="status-card">
                    <div class="status-card-title">🗺 Your Journey</div>
                    <div class="progress-steps">
                        <div class="step-item">
                            <div class="step-dot done">✓</div>
                            <div class="step-body"><h5>Account Created</h5><p>Logged in and ready</p></div>
                        </div>
                        <div class="step-item">
                            <div class="step-dot <?= $has_prediction?'done':'active' ?>"><?= $has_prediction?'✓':'2' ?></div>
                            <div class="step-body"><h5>Generate Prediction</h5><p>Get your AI estimate</p></div>
                        </div>
                        <div class="step-item">
                            <div class="step-dot <?= $has_prediction?'active':'pending' ?>">3</div>
                            <div class="step-body"><h5>Review Coverage</h5><p>Explore the plans tab</p></div>
                        </div>
                        <div class="step-item">
                            <div class="step-dot pending">4</div>
                            <div class="step-body"><h5>Medical Reports</h5><p>Suggested tests for your profile</p></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access -->
                <div class="status-card">
                    <div class="status-card-title">⚡ Quick Access</div>
                    <div style="display:flex;flex-direction:column;gap:9px;">
                        <a href="?tab=plans" onclick="switchTab('plans');return false;" class="action-card blue">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="action-icon">📋</div>
                                <div class="action-text"><h5>Coverage Plans</h5><p>Compare all available plans</p></div>
                            </div>
                            <span>→</span>
                        </a>
                        <?php if ($has_prediction): ?>
                        <a href="medical_reports.php" class="action-card teal">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="action-icon">🧪</div>
                                <div class="action-text"><h5>Medical Reports</h5><p>Suggested tests for your profile</p></div>
                            </div>
                            <span>→</span>
                        </a>
                        <a href="?tab=history" onclick="switchTab('history');return false;" class="action-card" style="background:#f8f9fb;border-color:var(--border);">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="action-icon">📈</div>
                                <div class="action-text"><h5>Prediction History</h5><p>View all your past quotes</p></div>
                            </div>
                            <span>→</span>
                        </a>
                        <?php else: ?>
                        <div class="action-card amber">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="action-icon">🧪</div>
                                <div class="action-text"><h5>Medical Reports</h5><p>Available after prediction</p></div>
                            </div>
                            <div class="action-lock">Locked</div>
                        </div>
                        <?php endif; ?>
                        <a href="contact.php" class="action-card" style="background:#f8f9fb;border-color:var(--border);">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div class="action-icon">💬</div>
                                <div class="action-text"><h5>Contact Support</h5><p>We're here to help</p></div>
                            </div>
                            <span>→</span>
                        </a>
                    </div>
                </div>

            </div><!-- end sidebar-cards -->
        </div><!-- end two-col -->
    </div><!-- end tab-predict -->


    <!-- ═══════════════ TAB 2: HISTORY ═══════════════ -->
    <div class="tab-content <?= $active_tab==='history'?'active':'' ?>" id="tab-history">

        <!-- Stats row -->
        <?php
        $avg_prem = 0; $max_prem = 0; $min_prem = PHP_INT_MAX;
        $plan_counts = [];
        foreach ($all_preds as $p) {
            $avg_prem += $p['predicted_premium'];
            $max_prem = max($max_prem, $p['predicted_premium']);
            $min_prem = min($min_prem, $p['predicted_premium']);
            $plan_counts[$p['recommended_plan']] = ($plan_counts[$p['recommended_plan']] ?? 0) + 1;
        }
        $avg_prem = $total_preds > 0 ? round($avg_prem / $total_preds) : 0;
        if ($min_prem === PHP_INT_MAX) $min_prem = 0;
        arsort($plan_counts); $top_plan = $total_preds > 0 ? array_key_first($plan_counts) : '—';
        ?>
        <div class="history-stats-row fade-slide d1">
            <div class="hist-stat">
                <div class="hist-stat-label">Total Predictions</div>
                <div class="hist-stat-val"><?= $total_preds ?></div>
            </div>
            <div class="hist-stat">
                <div class="hist-stat-label">Average Premium</div>
                <div class="hist-stat-val"><?= $total_preds > 0 ? 'LKR '.number_format($avg_prem) : '—' ?></div>
            </div>
            <div class="hist-stat">
                <div class="hist-stat-label">Highest Quote</div>
                <div class="hist-stat-val"><?= $total_preds > 0 ? 'LKR '.number_format($max_prem) : '—' ?></div>
            </div>
            <div class="hist-stat">
                <div class="hist-stat-label">Most Common Plan</div>
                <div class="hist-stat-val"><?= htmlspecialchars($top_plan) ?></div>
            </div>
        </div>

        <div class="history-table-wrap fade-slide d2">
            <div style="padding:16px 20px 12px;font-size:0.82rem;font-weight:700;color:var(--ink);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                <span>Prediction History</span>
                <?php if ($total_preds > 0): ?>
                <span style="font-size:0.72rem;color:var(--muted);font-weight:400;">Last <?= min($total_preds,10) ?> of <?= $total_preds ?> records shown</span>
                <?php endif; ?>
            </div>
            <?php if (empty($all_preds)): ?>
            <div class="empty-state"><div class="empty-icon">📊</div>No predictions yet.<br><small>Use the <strong>Get a Quote</strong> tab to run your first prediction.</small></div>
            <?php else: ?>
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>#</th><th>Annual Premium</th><th>Plan</th><th>Coverage</th>
                        <th>District</th><th>Age</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($all_preds as $i => $p): ?>
                <tr>
                    <td class="mono"><?= $p['id'] ?></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--ink);">
                        LKR <?= number_format($p['predicted_premium'],0) ?>
                        <?php if ($i === 0): ?><span style="font-size:0.6rem;background:var(--accent2-glow);color:#00a87e;padding:1px 6px;border-radius:10px;margin-left:5px;font-weight:700;">Latest</span><?php endif; ?>
                    </td>
                    <td><span class="plan-chip plan-<?= strtolower($p['recommended_plan']) ?>"><?= $p['recommended_plan'] ?></span></td>
                    <td style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($p['coverage_plan']) ?></td>
                    <td style="font-size:0.78rem;"><?= htmlspecialchars($p['district']) ?></td>
                    <td class="mono"><?= $p['age'] ?></td>
                    <td class="mono"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php if ($total_preds > 1): ?>
        <!-- Premium trend mini chart -->
        <div class="card-pro fade-slide d3" style="margin-top:20px;">
            <div style="font-size:0.82rem;font-weight:700;color:var(--ink);margin-bottom:16px;">Premium Trend</div>
            <?php $reversed = array_reverse($all_preds); $max_t = max(array_column($reversed,'predicted_premium')); ?>
            <div style="display:flex;align-items:flex-end;gap:6px;height:80px;">
                <?php foreach($reversed as $j => $rp):
                    $pct = $max_t > 0 ? round(($rp['predicted_premium']/$max_t)*100) : 0;
                    $is_last = ($j === count($reversed)-1);
                ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end;">
                    <div style="width:100%;border-radius:4px 4px 0 0;background:<?= $is_last?'var(--accent2)':'var(--accent)' ?>;opacity:0.8;height:<?= $pct ?>%;min-height:4px;" title="LKR <?= number_format($rp['predicted_premium'],0) ?>"></div>
                    <span style="font-size:0.55rem;color:var(--muted);"><?= date('d M',strtotime($rp['created_at'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div><!-- end tab-history -->


    <!-- ═══════════════ TAB 3: PLANS ═══════════════ -->
    <div class="tab-content <?= $active_tab==='plans'?'active':'' ?>" id="tab-plans">

        <?php if ($has_prediction && $recommended_plan): ?>
        <div class="alert-strip success fade-slide d1">
            🎯 Based on your latest prediction, <strong><?= htmlspecialchars($recommended_plan) ?></strong> is the recommended plan for your profile.
        </div>
        <?php endif; ?>

        <div class="plans-grid fade-slide d2">
        <?php foreach($plans_data as $p):
            $feats = array_filter(array_map('trim', preg_split('/[;\n]/', $p['features'] ?? '')));
            $excls = array_filter(array_map('trim', preg_split('/[;\n]/', $p['exclusions'] ?? '')));
            $is_rec = $has_prediction && $recommended_plan && strtolower($recommended_plan) === strtolower($p['name']);
            $color  = htmlspecialchars($p['color_hex'] ?: '#2563ff');
        ?>
        <div class="plan-card <?= $is_rec ? 'recommended' : '' ?>" style="<?= $is_rec ? "border-color:$color;" : '' ?>">
            <div class="plan-card-top" style="background:<?= $color ?>;"></div>
            <?php if ($is_rec): ?><div class="plan-rec-badge">⭐ Recommended</div><?php endif; ?>
            <div class="plan-card-body">
                <div class="plan-name" style="<?= $is_rec ? "color:$color;" : '' ?>"><?= htmlspecialchars($p['name']) ?></div>
                <div class="plan-tag"><?= htmlspecialchars($p['tagline'] ?: '') ?></div>

                <div class="plan-price-range">
                    <div class="plan-price-label">Annual Premium Range</div>
                    <div class="plan-price-val">LKR <?= number_format($p['annual_premium_min']) ?> – <?= number_format($p['annual_premium_max']) ?></div>
                </div>

                <div class="plan-limits">
                    <div class="plan-limit-item">
                        <div class="plan-limit-label">Inpatient</div>
                        <div class="plan-limit-val">LKR <?= number_format($p['inpatient_limit']/1000,0) ?>k</div>
                    </div>
                    <div class="plan-limit-item">
                        <div class="plan-limit-label">Outpatient</div>
                        <div class="plan-limit-val">LKR <?= number_format($p['outpatient_limit']/1000,0) ?>k</div>
                    </div>
                    <div class="plan-limit-item">
                        <div class="plan-limit-label">Surgery</div>
                        <div class="plan-limit-val">LKR <?= number_format($p['surgery_limit']/1000,0) ?>k</div>
                    </div>
                    <div class="plan-limit-item">
                        <div class="plan-limit-label">ICU</div>
                        <div class="plan-limit-val">LKR <?= number_format($p['icu_limit']/1000,0) ?>k</div>
                    </div>
                </div>

                <?php if (!empty($feats)): ?>
                <div class="plan-features-list">
                    <?php foreach(array_slice($feats,0,5) as $f): ?>
                    <div class="pf-item"><div class="pf-dot" style="background:#10b981;"></div><?= htmlspecialchars($f) ?></div>
                    <?php endforeach; ?>
                    <?php if (!empty($excls)): ?>
                    <?php foreach(array_slice($excls,0,2) as $e): ?>
                    <div class="pf-item excluded"><div class="pf-dot" style="background:#ef4444;"></div><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                    <?php
                    $covs = ['dental_covered'=>'🦷','optical_covered'=>'👁','maternity_covered'=>'🍼','emergency_covered'=>'🚑','pre_existing_covered'=>'💊'];
                    foreach($covs as $ck=>$ci): if($p[$ck]): ?>
                    <span style="font-size:0.62rem;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);color:#059669;padding:2px 7px;border-radius:20px;"><?= $ci ?></span>
                    <?php endif; endforeach; ?>
                </div>

                <?php if ($p['waiting_period_months'] > 0): ?>
                <div style="font-size:0.7rem;color:var(--muted);margin-top:8px;">⏳ <?= $p['waiting_period_months'] ?>-month waiting period</div>
                <?php else: ?>
                <div style="font-size:0.7rem;color:#10b981;margin-top:8px;">✓ No waiting period</div>
                <?php endif; ?>
            </div>
            <div class="plan-card-footer">
                <a href="coverage_details.php?plan=<?= urlencode($p['slug']) ?>" class="plan-btn secondary">Learn More</a>
                <?php if ($has_prediction): ?>
                <a href="recommended_coverage.php?premium=<?= urlencode($predicted_value ?? 0) ?>&plan=<?= urlencode($p['name']) ?>" class="plan-btn primary" style="background:<?= $color ?>;">
                    <?= $is_rec ? '✓ Select Plan' : 'Choose Plan' ?>
                </a>
                <?php else: ?>
                <a href="?tab=predict" onclick="switchTab('predict');return false;" class="plan-btn primary" style="background:<?= $color ?>;">Get Quote First</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Compare table -->
        <?php if (count($plans_data) > 1): ?>
        <div class="compare-table-wrap fade-slide d3">
            <div style="padding:16px 20px 12px;font-size:0.82rem;font-weight:700;color:var(--ink);border-bottom:1px solid var(--border);">
                Side-by-Side Comparison
            </div>
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <?php foreach($plans_data as $p): ?>
                        <th style="color:<?= htmlspecialchars($p['color_hex']) ?>;"><?= htmlspecialchars($p['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $compare_rows = [
                        'Premium Range'   => fn($p) => 'LKR '.number_format($p['annual_premium_min']/1000,0).'k–'.number_format($p['annual_premium_max']/1000,0).'k',
                        'Inpatient'       => fn($p) => 'LKR '.number_format($p['inpatient_limit']/1000,0).'k',
                        'Outpatient'      => fn($p) => 'LKR '.number_format($p['outpatient_limit']/1000,0).'k',
                        'Surgery'         => fn($p) => 'LKR '.number_format($p['surgery_limit']/1000,0).'k',
                        'ICU'             => fn($p) => 'LKR '.number_format($p['icu_limit']/1000,0).'k',
                        'Dental'          => fn($p) => $p['dental_covered']    ? '<span style="color:#10b981;font-weight:700;">✓</span>' : '<span style="color:#ef4444;">✕</span>',
                        'Optical'         => fn($p) => $p['optical_covered']   ? '<span style="color:#10b981;font-weight:700;">✓</span>' : '<span style="color:#ef4444;">✕</span>',
                        'Maternity'       => fn($p) => $p['maternity_covered'] ? '<span style="color:#10b981;font-weight:700;">✓</span>' : '<span style="color:#ef4444;">✕</span>',
                        'Pre-existing'    => fn($p) => $p['pre_existing_covered'] ? '<span style="color:#10b981;font-weight:700;">✓</span>' : '<span style="color:#ef4444;">✕</span>',
                        'Waiting Period'  => fn($p) => $p['waiting_period_months']===0 ? '<span style="color:#10b981;">None</span>' : $p['waiting_period_months'].' months',
                        'Max Entry Age'   => fn($p) => $p['max_age_limit'].' yrs',
                    ];
                    foreach($compare_rows as $label => $fn): ?>
                    <tr>
                        <td style="font-weight:600;color:var(--ink);font-size:0.78rem;"><?= $label ?></td>
                        <?php foreach($plans_data as $p): ?>
                        <td style="font-size:0.78rem;font-family:'JetBrains Mono',monospace;"><?= $fn($p) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- end tab-plans -->

</main><!-- end .main -->

<!-- PROFILE MODAL -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="modal-field"><label>Username</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:0.6;">
                    </div>
                    <div class="modal-field"><label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="modal-field"><label>City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                    </div>
                    <div class="modal-field"><label>Contact Number</label>
                        <input type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>">
                    </div>
                    <div class="modal-field"><label>New Password <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                        <input type="password" name="password" placeholder="Leave blank to keep current">
                    </div>
                    <button type="submit" name="update_profile" class="btn-update">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tab switching
function switchTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b => {
        if (b.textContent.toLowerCase().includes(name === 'predict' ? 'quote' : name)) b.classList.add('active');
    });
    history.replaceState(null, '', '?tab=' + name);
}

// Predict form loader
document.getElementById('predictForm')?.addEventListener('submit', function() {
    document.getElementById('predictBtnIcon').textContent = '⟳';
    document.getElementById('predictBtnText').textContent = 'Calculating…';
    document.getElementById('predictBtn').style.opacity = '0.7';
    document.getElementById('predictBtn').disabled = true;
});

// Sidebar navigation links
document.querySelectorAll('.nav-item-s[href]').forEach(a => {
    const href = a.getAttribute('href');
    if (href && href.startsWith('?tab=')) {
        a.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(href.replace('?tab=', ''));
            // Update active state
            document.querySelectorAll('.nav-item-s').forEach(n => n.classList.remove('active'));
            this.classList.add('active');
        });
    }
});

// Profile modal auto-open on success
<?php if (isset($profile_msg) && $profile_msg['type'] === 'success'): ?>
document.addEventListener('DOMContentLoaded', function() {
    // don't re-open modal on success, just show the inline message
});
<?php endif; ?>

// Form validation
document.getElementById('predictForm')?.addEventListener('submit', function(e) {
    const age = this.querySelector('[name=age]');
    const bmi = this.querySelector('[name=bmi]');
    const income = this.querySelector('[name=annual_income]');
    age.setCustomValidity('');
    bmi.setCustomValidity('');
    income.setCustomValidity('');
    if (!age.value || parseInt(age.value) < 18) age.setCustomValidity('Age must be 18 or above.');
    if (!bmi.value || parseFloat(bmi.value) <= 0) bmi.setCustomValidity('BMI must be greater than 0.');
    if (!income.value || parseInt(income.value) <= 0) income.setCustomValidity('Income must be greater than 0.');
    if (!this.checkValidity()) { e.preventDefault(); this.reportValidity(); }
});
</script>
</body>
</html>