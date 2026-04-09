:root {
  --ink: #0a0f1e;
  --ink-soft: #1a2035;
  --surface: #f4f6fb;
  --card: #ffffff;
  --accent: #2563ff;
  --accent-glow: rgba(37,99,255,0.12);
  --accent2: #00d4aa;
  --accent2-glow: rgba(0,212,170,0.12);
  --warn: #f97316;
  --danger: #ef4444;
  --success: #10b981;
  --purple: #8b5cf6;
  --muted: #7a859e;
  --border: rgba(30,39,64,0.09);
  --radius: 14px;
  --radius-sm: 9px;
  --sidebar-w: 240px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Sora',sans-serif;background:var(--surface);color:var(--ink);min-height:100vh;display:flex;}

/* ── SIDEBAR ── */
.a-sidebar{
  width:var(--sidebar-w);min-height:100vh;background:var(--ink);
  display:flex;flex-direction:column;padding:0;
  position:fixed;top:0;left:0;z-index:200;
}
.sb-logo{
  display:flex;align-items:center;gap:10px;
  padding:22px 22px 20px;
  font-size:1rem;font-weight:800;color:#fff;
  border-bottom:1px solid rgba(255,255,255,0.06);
}
.sb-logo-mark{
  width:32px;height:32px;border-radius:9px;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;
  font-size:1rem;flex-shrink:0;
}
.sb-section{
  font-size:0.58rem;font-weight:700;letter-spacing:0.15em;
  text-transform:uppercase;color:rgba(255,255,255,0.2);
  padding:20px 22px 6px;
}
.sb-link{
  display:flex;align-items:center;gap:11px;
  padding:9px 22px;
  color:rgba(255,255,255,0.42);
  text-decoration:none;
  font-size:0.8rem;font-weight:500;
  transition:all 0.18s;
  position:relative;
  border-left:3px solid transparent;
}
.sb-link:hover{color:#fff;background:rgba(255,255,255,0.04);border-left-color:rgba(255,255,255,0.1);}
.sb-link.active{color:#fff;background:rgba(255,255,255,0.06);border-left-color:var(--accent2);}
.sb-icon{width:18px;text-align:center;font-style:normal;flex-shrink:0;}
.sb-badge{
  margin-left:auto;
  background:var(--danger);color:#fff;
  font-size:0.6rem;font-weight:700;
  padding:1px 6px;border-radius:10px;
}
.sb-bottom{
  margin-top:auto;padding:16px 22px;
  border-top:1px solid rgba(255,255,255,0.06);
}
.sb-user{display:flex;align-items:center;gap:10px;}
.sb-avatar{
  width:32px;height:32px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:0.72rem;
}
.sb-user-name{font-size:0.78rem;font-weight:600;color:#fff;}
.sb-user-role{font-size:0.65rem;color:rgba(255,255,255,0.3);}

/* ── MAIN ── */
.a-main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}

/* ── TOPBAR ── */
.a-topbar{
  padding:16px 36px;
  display:flex;align-items:center;justify-content:space-between;
  background:var(--card);border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.a-topbar-title{font-size:1rem;font-weight:700;color:var(--ink);letter-spacing:-0.3px;}
.a-topbar-sub{font-size:0.72rem;color:var(--muted);margin-top:1px;}
.a-topbar-right{display:flex;align-items:center;gap:10px;}
.tb-btn{
  padding:7px 16px;border-radius:50px;font-family:'Sora',sans-serif;
  font-size:0.75rem;font-weight:600;cursor:pointer;
  border:1px solid var(--border);background:var(--surface);color:var(--ink);
  text-decoration:none;transition:all 0.2s;
}
.tb-btn:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.tb-btn.danger:hover{background:var(--danger);border-color:var(--danger);}
.tb-btn.primary{background:var(--accent);color:#fff;border-color:var(--accent);}
.tb-btn.primary:hover{background:#1d4ed8;}

/* ── CONTENT ── */
.a-content{padding:28px 36px 60px;flex:1;}

/* ── TABLE ── */
.tbl-wrap{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
.cc-table{width:100%;border-collapse:collapse;}
.cc-table thead th{
  padding:11px 18px;
  font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;
  color:rgba(255,255,255,0.5);background:var(--ink);
  text-align:left;white-space:nowrap;
}
.cc-table tbody td{
  padding:12px 18px;
  font-size:0.8rem;color:var(--ink);
  border-bottom:1px solid var(--border);vertical-align:middle;
}
.cc-table tbody tr:last-child td{border-bottom:none;}
.cc-table tbody tr:hover td{background:rgba(244,246,251,0.6);}

.user-cell{display:flex;align-items:center;gap:9px;}
.mini-avatar{
  width:28px;height:28px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:0.62rem;
}
.cell-muted{color:var(--muted);font-size:0.75rem;}
.mono{font-family:'JetBrains Mono',monospace;font-size:0.76rem;color:var(--muted);}

/* chips */
.role-chip{display:inline-block;font-size:0.62rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;padding:3px 8px;border-radius:20px;}
.role-chip.admin{background:rgba(37,99,255,0.1);color:var(--accent);}
.role-chip.user{background:var(--accent2-glow);color:#00a87e;}
.plan-chip{display:inline-block;font-size:0.62rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;padding:3px 8px;border-radius:20px;}
.plan-chip.plan-basic{background:rgba(37,99,255,0.1);color:var(--accent);}
.plan-chip.plan-standard{background:var(--accent2-glow);color:#00a87e;}
.plan-chip.plan-premium{background:rgba(249,115,22,0.1);color:var(--warn);}
.status-chip{display:inline-block;font-size:0.62rem;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;padding:3px 8px;border-radius:20px;}
.status-chip.unread{background:rgba(239,68,68,0.1);color:var(--danger);}
.status-chip.read{background:rgba(16,185,129,0.1);color:var(--success);}

/* section header */
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.sec-title{font-size:0.88rem;font-weight:700;color:var(--ink);display:flex;align-items:center;gap:8px;}
.sec-count{font-family:'JetBrains Mono',monospace;font-size:0.65rem;background:var(--surface);border:1px solid var(--border);color:var(--muted);padding:2px 8px;border-radius:20px;}

/* table action buttons */
.tbl-btn{
  display:inline-block;padding:4px 12px;border-radius:6px;font-size:0.7rem;font-weight:600;
  text-decoration:none;border:1px solid var(--border);color:var(--ink);background:var(--surface);
  transition:all 0.15s;cursor:pointer;font-family:'Sora',sans-serif;
}
.tbl-btn:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.tbl-btn.danger{color:var(--danger);border-color:rgba(239,68,68,0.2);}
.tbl-btn.danger:hover{background:var(--danger);color:#fff;border-color:var(--danger);}
.tbl-btn.success{color:var(--success);border-color:rgba(16,185,129,0.2);}
.tbl-btn.success:hover{background:var(--success);color:#fff;border-color:var(--success);}

/* search/filter bar */
.filter-bar{
  display:flex;align-items:center;gap:10px;
  padding:14px 18px;
  border-bottom:1px solid var(--border);
  background:var(--surface);
}
.filter-input{
  padding:8px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);
  font-family:'Sora',sans-serif;font-size:0.8rem;color:var(--ink);background:var(--card);
  outline:none;min-width:240px;transition:border-color 0.2s,box-shadow 0.2s;
}
.filter-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow);}
.filter-select{
  padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);
  font-family:'Sora',sans-serif;font-size:0.8rem;color:var(--ink);background:var(--card);
  outline:none;cursor:pointer;
}

/* pagination */
.pagination{display:flex;align-items:center;gap:6px;padding:16px 18px;border-top:1px solid var(--border);}
.page-btn{
  width:32px;height:32px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  font-size:0.78rem;font-weight:600;text-decoration:none;color:var(--ink);
  border:1px solid var(--border);background:var(--card);transition:all 0.15s;
}
.page-btn:hover{background:var(--ink);color:#fff;border-color:var(--ink);}
.page-btn.active{background:var(--accent);color:#fff;border-color:var(--accent);}
.page-info{font-size:0.75rem;color:var(--muted);margin-left:auto;}

/* empty state */
.empty-state{padding:48px 24px;text-align:center;color:var(--muted);font-size:0.82rem;}
.empty-icon{font-size:2.4rem;margin-bottom:10px;}

/* modal overlay */
.modal-overlay{
  display:none;position:fixed;inset:0;background:rgba(10,15,30,0.55);
  z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(3px);
}
.modal-overlay.open{display:flex;}
.modal{
  background:var(--card);border-radius:var(--radius);
  width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;
  padding:32px;box-shadow:0 32px 80px rgba(10,15,30,0.25);
  animation:modalIn 0.22s ease;
}
@keyframes modalIn{from{opacity:0;transform:scale(0.97) translateY(10px);}to{opacity:1;transform:scale(1) translateY(0);}}
.modal-title{font-size:1.1rem;font-weight:800;color:var(--ink);margin-bottom:6px;}
.modal-sub{font-size:0.8rem;color:var(--muted);margin-bottom:24px;}
.modal-close{position:absolute;top:16px;right:18px;background:none;border:none;font-size:1.2rem;color:var(--muted);cursor:pointer;padding:4px;}

/* form fields */
.f-field{margin-bottom:16px;}
.f-label{display:block;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:6px;}
.f-input{
  width:100%;padding:11px 14px;border:1.5px solid var(--border);
  border-radius:var(--radius-sm);background:var(--surface);
  font-family:'Sora',sans-serif;font-size:0.85rem;color:var(--ink);
  outline:none;transition:border-color 0.2s,box-shadow 0.2s;
}
.f-input:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-glow);}
.f-select{
  width:100%;padding:11px 14px;border:1.5px solid var(--border);
  border-radius:var(--radius-sm);background:var(--surface);
  font-family:'Sora',sans-serif;font-size:0.85rem;color:var(--ink);
  outline:none;cursor:pointer;
}
.f-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

/* alerts */
.alert{
  padding:11px 14px;border-radius:var(--radius-sm);
  font-size:0.8rem;font-weight:500;margin-bottom:18px;
  display:flex;align-items:center;gap:8px;
}
.alert.success{background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);color:var(--success);}
.alert.error{background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.2);color:var(--danger);}

/* fade-in */
.fade-in{opacity:0;transform:translateY(8px);animation:fi 0.4s ease forwards;}
@keyframes fi{to{opacity:1;transform:translateY(0);}}
.d1{animation-delay:0.05s;}.d2{animation-delay:0.10s;}.d3{animation-delay:0.15s;}
.d4{animation-delay:0.20s;}.d5{animation-delay:0.25s;}