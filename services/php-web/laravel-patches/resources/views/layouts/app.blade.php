<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Space Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    :root {
      --mint-light: #e0f7ed;
      --mint-pale: #c4f0e0;
      --mint: #a8e6cf;
      --mint-medium: #7dd3c0;
      --mint-deep: #5fb3a8;
      --mint-dark: #4a9b8e;
      --mint-darker: #3d8a7d;
      --bg-gradient: linear-gradient(135deg, #e0f7ed 0%, #c4f0e0 25%, #a8e6cf 50%, #7dd3c0 75%, #5fb3a8 100%);
      --glass: rgba(255,255,255,0.75);
      --glass-border: rgba(125,211,192,0.4);
      --accent: #4a9b8e;
      --accent-2: #5fb3a8;
      --text-main: #1a3d35;
      --text-dim: #2d5a50;
      --text-light: #ffffff;
    }
    body {
      min-height: 100vh;
      background: var(--bg-gradient);
      background-attachment: fixed;
      color: var(--text-main);
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    .glass {
      background: var(--glass);
      border: 2px solid var(--glass-border);
      backdrop-filter: blur(20px);
      box-shadow: 0 8px 32px rgba(74,155,142,0.2), 0 2px 8px rgba(0,0,0,0.1);
      transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 16px;
    }
    .glass:hover {
      transform: translateY(-6px) scale(1.01);
      box-shadow: 0 16px 48px rgba(74,155,142,0.3), 0 4px 12px rgba(0,0,0,0.15);
      border-color: rgba(95,179,168,0.6);
    }
    .mint-btn {
      background: linear-gradient(135deg, var(--mint-medium) 0%, var(--mint-deep) 100%);
      color: var(--text-light);
      border: none;
      box-shadow: 0 4px 16px rgba(74,155,142,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
      transition: all .25s ease;
      border-radius: 12px;
      font-weight: 600;
      padding: 10px 24px;
    }
    .mint-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(74,155,142,0.5), inset 0 1px 0 rgba(255,255,255,0.3);
      background: linear-gradient(135deg, var(--mint-deep) 0%, var(--mint-dark) 100%);
      color: var(--text-light);
    }
    .mint-btn:active {
      transform: translateY(0);
      box-shadow: 0 2px 8px rgba(74,155,142,0.3);
    }
    .page-hero {
      position: relative;
      overflow: hidden;
      border-radius: 20px;
      background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(224,247,237,0.95) 100%);
      border: 2px solid rgba(125,211,192,0.3);
    }
    .page-hero::after {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 70% 10%, rgba(168,230,207,0.3), transparent 40%),
                  radial-gradient(circle at 10% 80%, rgba(95,179,168,0.25), transparent 40%);
      opacity: 0.8;
      pointer-events: none;
      border-radius: 20px;
    }
    .navbar {
      background: rgba(255,255,255,0.85) !important;
      backdrop-filter: blur(20px);
      border-bottom: 2px solid rgba(125,211,192,0.3);
    }
    a.nav-link, .navbar-brand { 
      color: var(--text-main) !important; 
      font-weight: 500;
      transition: all .2s ease;
    }
    a.nav-link.active, a.nav-link:hover { 
      color: var(--mint-dark) !important; 
      text-shadow: 0 0 8px rgba(74,155,142,0.3);
    }
    .table {
      background: rgba(255,255,255,0.9);
      border-radius: 12px;
      overflow: hidden;
    }
    .table thead { 
      background: linear-gradient(135deg, var(--mint-medium) 0%, var(--mint-deep) 100%);
      color: var(--text-light);
    }
    .table tbody tr {
      transition: all .2s ease;
    }
    .table tbody tr:hover {
      background: rgba(168,230,207,0.3);
      transform: scale(1.01);
    }
    .text-secondary { color: var(--text-dim) !important; }
    .card {
      background: rgba(255,255,255,0.85);
      border: 2px solid rgba(125,211,192,0.3);
      border-radius: 16px;
      backdrop-filter: blur(10px);
    }
    .card-header {
      background: linear-gradient(135deg, var(--mint-pale) 0%, var(--mint) 100%);
      color: var(--text-main);
      border-bottom: 2px solid rgba(125,211,192,0.3);
      font-weight: 600;
    }
    input.form-control, select.form-select {
      background: rgba(255,255,255,0.9);
      border: 2px solid rgba(125,211,192,0.4);
      border-radius: 10px;
      color: var(--text-main);
      transition: all .2s ease;
    }
    input.form-control:focus, select.form-select:focus {
      background: rgba(255,255,255,1);
      border-color: var(--mint-deep);
      box-shadow: 0 0 0 4px rgba(95,179,168,0.2);
      outline: none;
    }
    #map{height:340px; border-radius: 12px; border: 2px solid rgba(125,211,192,0.3);}
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
      animation: fadeInUp 0.6s ease-out;
    }
  </style>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg mb-3 glass">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/dashboard">Space Dashboard</a>
    <div class="d-flex gap-3">
      <a class="nav-link" href="/dashboard">Главная</a>
      <a class="nav-link" href="/iss">ISS</a>
      <a class="nav-link" href="/osdr">OSDR</a>
      <a class="nav-link" href="/telemetry">Telemetry</a>
      <a class="nav-link" href="/cms/page/demo">CMS</a>
    </div>
  </div>
</nav>
@yield('content')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
