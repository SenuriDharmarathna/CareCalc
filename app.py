"""
CareCalc — Flask Prediction API
================================
File:  app.py
Place: inside your CareCalc project folder (same level as your PHP files)

Run:   python app.py
       (keep this terminal open while the PHP site is running)

Endpoint:  POST http://127.0.0.1:5000/predict
           Content-Type: application/json

Expects JSON keys that match customer_dashboard.php POST fields:
  Gender, Age, BMI, Smoker, Alcohol_Use, Coverage_Plan, District,
  Heart_Disease, Diabetes, Hypertension, Asthma, Marital_Status,
  Number_of_Children, Annual_Income, Hospitalization_Last_5Yrs

Returns:
  { "predicted_cost": 185000.0,
    "plan": "Standard",
    "monthly": 15417,
    "quarterly": 46250,
    "half_yearly": 92500 }
"""

import os
import json
import joblib
import numpy as np
import pandas as pd
from flask import Flask, request, jsonify

# ── Try to import sklearn; give a clear error if missing ─────────────────────
try:
    from sklearn.ensemble import GradientBoostingRegressor
    from sklearn.preprocessing import LabelEncoder
except ImportError:
    raise SystemExit(
        "\n[ERROR] scikit-learn not found.\n"
        "Run:  pip install scikit-learn flask joblib pandas numpy\n"
    )

# ─────────────────────────────────────────────────────────────────────────────
# PATHS
# ─────────────────────────────────────────────────────────────────────────────
BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
MODEL_FILE  = os.path.join(BASE_DIR, "carecalc_model.pkl")
ENCODE_FILE = os.path.join(BASE_DIR, "carecalc_encoders.pkl")
DATA_FILE   = os.path.join(BASE_DIR, "SriLanka_Insurance_Dataset.csv")

# ─────────────────────────────────────────────────────────────────────────────
# CATEGORICAL COLUMNS (must match dataset exactly)
# ─────────────────────────────────────────────────────────────────────────────
CAT_COLS = ["District", "Marital_Status", "Coverage_Plan"]

FEATURE_ORDER = [
    "Gender", "Age", "BMI", "Smoker", "Alcohol_Use",
    "District", "Marital_Status", "Number_of_Children",
    "Annual_Income", "Hospitalization_Last_5Yrs",
    "Heart_Disease", "Diabetes", "Hypertension", "Asthma",
    "Coverage_Plan"
]

# ─────────────────────────────────────────────────────────────────────────────
# TRAIN & SAVE MODEL  (only runs once — skipped on subsequent starts)
# ─────────────────────────────────────────────────────────────────────────────
def train_and_save():
    print("[CareCalc] Training model from dataset...")

    if not os.path.exists(DATA_FILE):
        raise FileNotFoundError(
            f"\n[ERROR] Dataset not found at: {DATA_FILE}\n"
            "Make sure SriLanka_Insurance_Dataset.csv is in the same folder as app.py\n"
        )

    df = pd.read_csv(DATA_FILE)
    print(f"[CareCalc] Loaded {len(df):,} rows × {len(df.columns)} columns")

    # Fit one LabelEncoder per categorical column and save them
    encoders = {}
    for col in CAT_COLS:
        le = LabelEncoder()
        df[col] = le.fit_transform(df[col])
        encoders[col] = le
        print(f"[CareCalc]   Encoded '{col}': {list(le.classes_)}")

    X = df[FEATURE_ORDER]
    y = df["Annual_Premium"]

    model = GradientBoostingRegressor(
        n_estimators=300,
        learning_rate=0.10,
        max_depth=5,
        random_state=42
    )
    model.fit(X, y)

    # Quick sanity check
    sample_pred = model.predict(X.head(3))
    print(f"[CareCalc] Sample predictions: {sample_pred.round(0)}")
    print(f"[CareCalc] Actual values:      {y.head(3).values}")

    joblib.dump(model,    MODEL_FILE)
    joblib.dump(encoders, ENCODE_FILE)
    print(f"[CareCalc] ✅ Model saved  → {MODEL_FILE}")
    print(f"[CareCalc] ✅ Encoders saved → {ENCODE_FILE}")


def load_model():
    model    = joblib.load(MODEL_FILE)
    encoders = joblib.load(ENCODE_FILE)
    print("[CareCalc] ✅ Model loaded from disk")
    return model, encoders


# ─────────────────────────────────────────────────────────────────────────────
# BOOT: train if model doesn't exist yet, otherwise load from disk
# ─────────────────────────────────────────────────────────────────────────────
if not os.path.exists(MODEL_FILE) or not os.path.exists(ENCODE_FILE):
    train_and_save()

MODEL, ENCODERS = load_model()


# ─────────────────────────────────────────────────────────────────────────────
# FLASK APP
# ─────────────────────────────────────────────────────────────────────────────
app = Flask(__name__)


def plan_from_premium(premium: float) -> str:
    """Map predicted premium to recommended plan label."""
    if premium < 120000:
        return "Basic"
    elif premium < 400000:
        return "Standard"
    else:
        return "Premium"


def safe_int(value, default=0):
    try:
        return int(float(value))
    except (ValueError, TypeError):
        return default


def safe_float(value, default=0.0):
    try:
        return float(value)
    except (ValueError, TypeError):
        return default


@app.route("/predict", methods=["POST"])
def predict():
    try:
        data = request.get_json(force=True)
        if not data:
            return jsonify({"error": "No JSON data received"}), 400

        # ── Build input row ────────────────────────────────────────────────
        row = {
            "Gender":                    safe_int(data.get("Gender", 0)),
            "Age":                       safe_int(data.get("Age", 30)),
            "BMI":                       safe_float(data.get("BMI", 22.0)),
            "Smoker":                    safe_int(data.get("Smoker", 0)),
            "Alcohol_Use":               safe_int(data.get("Alcohol_Use", 0)),
            "District":                  str(data.get("District", "Colombo")),
            "Marital_Status":            str(data.get("Marital_Status", "Single")),
            "Number_of_Children":        safe_int(data.get("Number_of_Children", 0)),
            "Annual_Income":             safe_float(data.get("Annual_Income", 1200000)),
            "Hospitalization_Last_5Yrs": safe_int(data.get("Hospitalization_Last_5Yrs", 0)),
            "Heart_Disease":             safe_int(data.get("Heart_Disease", 0)),
            "Diabetes":                  safe_int(data.get("Diabetes", 0)),
            "Hypertension":              safe_int(data.get("Hypertension", 0)),
            "Asthma":                    safe_int(data.get("Asthma", 0)),
            "Coverage_Plan":             str(data.get("Coverage_Plan", "Standard")),
        }

        # ── Encode categoricals using saved LabelEncoders ──────────────────
        for col in CAT_COLS:
            le = ENCODERS[col]
            raw_val = row[col]

            # Handle unseen labels gracefully — default to most common class
            if raw_val not in le.classes_:
                fallback = le.classes_[0]
                print(f"[WARN] Unknown value '{raw_val}' for '{col}', using '{fallback}'")
                raw_val = fallback

            row[col] = int(le.transform([raw_val])[0])

        # ── Create DataFrame in exact feature order ────────────────────────
        input_df = pd.DataFrame([row])[FEATURE_ORDER]

        # ── Predict ───────────────────────────────────────────────────────
        raw_prediction = float(MODEL.predict(input_df)[0])

        # Round to nearest 500 LKR (how premiums are quoted)
        premium = round(raw_prediction / 500) * 500
        premium = max(15000, premium)   # floor: never below LKR 15,000

        plan     = plan_from_premium(premium)
        monthly  = round(premium / 12)
        qtrly    = round(premium / 4)
        half_yr  = round(premium / 2)

        response = {
            "predicted_cost": premium,
            "plan":           plan,
            "monthly":        monthly,
            "quarterly":      qtrly,
            "half_yearly":    half_yr,
            "status":         "success"
        }

        print(f"[PREDICT] Input: Age={row['Age']}, Income={row['Annual_Income']:,.0f}, "
              f"Plan={data.get('Coverage_Plan')} → LKR {premium:,.0f} ({plan})")

        return jsonify(response)

    except Exception as e:
        print(f"[ERROR] Prediction failed: {e}")
        return jsonify({"error": str(e), "status": "error"}), 500


@app.route("/health", methods=["GET"])
def health():
    """Quick health check — visit http://127.0.0.1:5000/health in your browser."""
    return jsonify({
        "status": "ok",
        "model":  "GradientBoostingRegressor",
        "version": "CareCalc v1.0"
    })


@app.route("/", methods=["GET"])
def index():
    return jsonify({
        "name":     "CareCalc Prediction API",
        "endpoint": "POST /predict",
        "health":   "GET /health"
    })


# ─────────────────────────────────────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    print("\n" + "="*55)
    print("  CareCalc Prediction API")
    print("  Running at: http://127.0.0.1:5000")
    print("  Health check: http://127.0.0.1:5000/health")
    print("="*55 + "\n")
    app.run(host="127.0.0.1", port=5000, debug=False)