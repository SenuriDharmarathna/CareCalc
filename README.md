# 🩺 CareCalc — Medical Insurance Premium Prediction System

> Data Management Project | W.P.S.S. Dharmarathna | 21/ENG/165

A web-based intelligent system that predicts medical insurance premiums using machine learning, automatically suggests required medical reports based on user health conditions, and shows recommended coverage plans — built specifically for Sri Lanka.

\---

## How to Run the Project

### Requirements

* WAMP Server — https://www.wampserver.com
* Python 3.8+ — https://www.python.org

\---

### Step 1 — Place the Project Folder

Copy the `CareCalc` folder into:

&#x20;   C:\\wamp64\\www\\CareCalc\\


### Step 2 — Set Up the Database

1. Start WAMP — wait for tray icon to turn **green**
2. Open your browser and go to: `http://localhost/phpmyadmin`
3. Click **New** on the left → create a database named `carecalc\_db`
4. Click on `carecalc\_db` → click **Import** tab
5. Click **Choose File** → select `carecalc\_db.sql` from the project folder
6. Click **Go**

### Step 3 — Check Database Connection

Open `config.php` and make sure it matches your WAMP setup:

&#x20;   $host     = "localhost";
    $user     = "root";
    $password = "";
    $database = "carecalc\_db";


### Step 4 — Install Python Libraries

Open **Command Prompt** and run:

&#x20;   pip install flask scikit-learn pandas numpy joblib


### Step 5 — Enable PHP cURL Extension

1. Left-click the WAMP tray icon
2. Go to **PHP** → **PHP extensions**
3. Find and click **php\_curl** to enable it
4. WAMP will restart automatically

### Step 6 — Start the Flask ML API

Open **Command Prompt** and run:

&#x20;   cd C:\\wamp64\\www\\CareCalc
    python app.py


Keep this window open the whole time.

To verify it is working, open: `http://127.0.0.1:5000/health`

You should see: `{"status": "ok"}`

### Step 7 — Open the Application

Make sure WAMP is green and Flask is running, then open:

&#x20;   http://localhost/CareCalc/index.php


\---

## Every Time You Use the App

&#x20;   1. Start WAMP (tray icon must be green)
    2. Open CMD → cd C:\\wamp64\\www\\CareCalc → python app.py
    3. Keep the CMD window open
    4. Open browser → http://localhost/CareCalc/index.php


\---

## Login Details

|Role|Email|Password|
|-|-|-|
|Admin|admin1@gmail.com|admin1|
|Customer|customer1@gmail.com|customer1|

To create a customer/admin account click **Sign Up** on the home page.

\---

## Features

### Customer Portal

* AI-predicted annual insurance premium using 15 health and lifestyle inputs
* Monthly, quarterly and half-yearly payment breakdown
* Recommended coverage plan — Basic, Standard or Premium
* Auto-generated medical report list based on health conditions
* Full prediction history

### Admin Portal

* Dashboard with KPI cards and monthly prediction charts
* User management — view, enable or disable accounts
* All predictions with search, filters and CSV export
* Insurance plan management — create, edit, delete plans
* Risk monitor — automatically flags high-risk customers
* Audit log — records every admin action with timestamp

\---

## Tech Stack

|Layer|Technology|
|-|-|
|Frontend|PHP 8+, HTML5, CSS3, Bootstrap 5, JS|
|Backend|PHP, Apache, WAMP64, cURL|
|ML API|Python 3, Flask, scikit-learn, joblib|
|Database|MySQL via phpMyAdmin|
|ML Tools|pandas, NumPy, Jupyter Notebook|

\---

## Machine Learning Model

|Detail|Value|
|-|-|
|Algorithm|Gradient Boosting Regressor|
|Dataset|5,000 records (Sri Lankan dataset)|
|Features|15 inputs — age, BMI, income, district, conditions|
|Districts|23 (all of Sri Lanka)|
|Accuracy|98.11% — R² = 0.9811|
|RMSE|LKR 25,606|
|Config|300 trees · learning rate 0.10 · max depth 5|

The model trains automatically on the first run of `app.py` and saves as `carecalc\_model.pkl`. All future predictions load from the saved file.

\---

## Database Tables

|Table|Purpose|
|-|-|
|users|Customer and admin accounts|
|predictions|All ML prediction records|
|contact\_messages|Messages from the contact form|
|admin\_messages|Messages sent from admin to users|
|insurance\_plans|Coverage plan details and limits|
|audit\_log|Admin action history with IP and timestamp|

\---

## Project Structure

&#x20;   CareCalc/
    ├── app.py                       Flask ML API
    ├── carecalc\_db.sql              Database setup file
    ├── config.php                   Database connection
    ├── index.php                    Landing page
    ├── login.php                    Login and register
    ├── customer\_dashboard.php       Main prediction form
    ├── coverage\_details.php         Coverage plan details
    ├── medical\_reports.php          Required medical reports
    ├── recommended\_coverage.php     Recommended plan page
    ├── contact.php                  Contact form
    ├── admin\_dashboard.php          Admin main dashboard
    ├── admin\_users.php              User management
    ├── admin\_predictions.php        Predictions view
    ├── admin\_plans.php              Plan management
    ├── admin\_messages.php           Messages
    ├── admin\_risk.php               Risk monitor
    ├── admin\_export.php             CSV export
    ├── admin\_audit.php              Audit log
    ├── admin\_analytics.php          Analytics
    ├── admin\_sidebar.php            Sidebar component
    ├── admin\_topbar.php             Topbar component
    └── admin\_shared.css.php         Shared admin styles


\---

*CareCalc · Data Management Project* 

