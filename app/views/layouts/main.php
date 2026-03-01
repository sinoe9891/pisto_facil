<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Dashboard') ?> · <?= htmlspecialchars($config['name'] ?? 'Préstamos') ?></title>
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <!-- Custom CSS -->
  <style>
    :root {
      --sidebar-width: 260px;
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
      --sidebar-bg: #1e293b;
      --sidebar-text: #cbd5e1;
      --sidebar-hover: #334155;
      --sidebar-active: #2563eb;
    }
    body { background:#f1f5f9; font-family:'Inter',system-ui,sans-serif; }
    /* SIDEBAR */
    #sidebar {
      position:fixed; top:0; left:0; height:100vh; width:var(--sidebar-width);
      background:var(--sidebar-bg); color:var(--sidebar-text); z-index:1040;
      overflow-y:auto; transition:transform .3s ease;
    }
    #sidebar .brand {
      display:flex; align-items:center; gap:10px; padding:1.2rem 1.5rem;
      border-bottom:1px solid #334155; color:#fff; font-weight:700; font-size:1.1rem;
    }
    #sidebar .brand .icon { background:var(--primary); padding:6px 9px; border-radius:8px; }
    #sidebar .nav-section { padding:.5rem 1rem .2rem; font-size:.7rem; text-transform:uppercase;
      letter-spacing:.1em; color:#64748b; margin-top:.5rem; }
    #sidebar .nav-link {
      color:var(--sidebar-text); border-radius:8px; margin:.1rem .5rem;
      padding:.5rem .8rem; display:flex; align-items:center; gap:.6rem; font-size:.875rem;
      transition:background .15s;
    }
    #sidebar .nav-link:hover { background:var(--sidebar-hover); color:#fff; }
    #sidebar .nav-link.active { background:var(--sidebar-active); color:#fff; }
    #sidebar .nav-link i { font-size:1rem; width:18px; text-align:center; }
    /* TOPBAR */
    #topbar {
      position:fixed; top:0; left:var(--sidebar-width); right:0; height:60px;
      background:#fff; border-bottom:1px solid #e2e8f0; z-index:1030;
      display:flex; align-items:center; padding:0 1.5rem; gap:1rem;
    }
    #topbar .page-title { font-weight:600; font-size:1rem; color:#1e293b; flex:1; }
    /* MAIN */
    #main { margin-left:var(--sidebar-width); padding-top:60px; min-height:100vh; }
    .main-content { padding:1.5rem; }
    /* CARDS */
    .stat-card { border:none; border-radius:12px; overflow:hidden; transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-card .card-body { padding:1.25rem 1.5rem; }
    .stat-icon { width:48px; height:48px; border-radius:12px; display:flex;
      align-items:center; justify-content:center; font-size:1.3rem; }
    /* BADGES */
    .badge-active   { background:#dcfce7; color:#166534; }
    .badge-overdue  { background:#fee2e2; color:#991b1b; }
    .badge-upcoming { background:#fef3c7; color:#92400e; }
    .badge-paid     { background:#dbeafe; color:#1e40af; }
    /* TABLE */
    .table-custom { font-size:.85rem; }
    .table-custom thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0;
      font-weight:600; color:#475569; text-transform:uppercase; font-size:.72rem; letter-spacing:.05em; }
    /* SIDEBAR COLLAPSED */
    @media (max-width:991px) {
      #sidebar { transform:translateX(-100%); }
      #sidebar.show { transform:translateX(0); }
      #topbar, #main { left:0; margin-left:0; }
    }
    /* ALERTS */
    .alert-dismissible .btn-close { padding:.6rem; }
    .brand-logo {
      width: 100px;
      /* ajusta aquí: 120/160/180... */
      height: auto;
      /* mantiene proporción */
      display: block;
      margin: 0.75rem auto 0.5rem;
      /* centrado */
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar">
  <div class="brand">
    <!-- <span class="icon"><i class="bi bi-bank2 text-white"></i></span> -->
     <img class="brand-logo" src="<?= e(url('assets/img/logo.jpg')) ?>" alt="Logo">
    <!-- <?= htmlspecialchars($config['name'] ?? 'Préstamos') ?> -->
  </div>

  <?php $role = $auth['role'] ?? ''; $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>

  <div class="nav-section">Principal</div>
  <a href="<?= url('/dashboard') ?>" class="nav-link <?= str_contains($currentPath,'dashboard') ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>

  <div class="nav-section">Gestión</div>
  <?php if (in_array($role, ['superadmin','admin','asesor'])): ?>
  <a href="<?= url('/clients') ?>" class="nav-link <?= str_contains($currentPath,'/clients') ? 'active' : '' ?>">
    <i class="bi bi-people"></i> Clientes
  </a>
  <a href="<?= url('/loans') ?>" class="nav-link <?= str_contains($currentPath,'/loans') ? 'active' : '' ?>">
    <i class="bi bi-cash-coin"></i> Préstamos
  </a>
  <a href="<?= url('/payments') ?>" class="nav-link <?= str_contains($currentPath,'/payments') ? 'active' : '' ?>">
    <i class="bi bi-credit-card"></i> Pagos
  </a>
  <?php endif; ?>

  <?php if ($role === 'cliente'): ?>
  <a href="<?= url('/my-loans') ?>" class="nav-link <?= str_contains($currentPath,'my-loans') ? 'active' : '' ?>">
    <i class="bi bi-file-text"></i> Mis Préstamos
  </a>
  <?php endif; ?>

  <?php if (in_array($role, ['superadmin','admin'])): ?>
  <div class="nav-section">Reportes</div>
  <a href="<?= url('/reports/general') ?>" class="nav-link <?= str_contains($currentPath,'reports') ? 'active' : '' ?>">
    <i class="bi bi-bar-chart-line"></i> Reportes
  </a>
  <?php endif; ?>

  <?php if (in_array($role, ['superadmin','admin'])): ?>
  <div class="nav-section">Administración</div>
  <a href="<?= url('/users') ?>" class="nav-link <?= str_contains($currentPath,'/users') ? 'active' : '' ?>">
    <i class="bi bi-person-gear"></i> Usuarios
  </a>
  <?php endif; ?>

  <?php if ($role === 'superadmin'): ?>
  <a href="<?= url('/settings') ?>" class="nav-link <?= str_contains($currentPath,'settings') ? 'active' : '' ?>">
    <i class="bi bi-gear"></i> Configuración
  </a>
  <?php endif; ?>

  <div class="mt-auto p-3 border-top border-secondary" style="border-color:#334155!important; margin-top:auto;">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold"
           style="width:36px;height:36px;font-size:.8rem;flex-shrink:0">
        <?= strtoupper(substr($auth['name'] ?? 'U', 0, 2)) ?>
      </div>
      <div style="overflow:hidden">
        <div style="font-size:.8rem;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($auth['name'] ?? '') ?>
        </div>
        <div style="font-size:.7rem;color:#64748b"><?= htmlspecialchars($auth['role'] ?? '') ?></div>
      </div>
      <a href="<?= url('/logout') ?>" class="ms-auto text-secondary" title="Cerrar sesión" style="font-size:1.1rem">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>
</nav>

<!-- TOPBAR -->
<header id="topbar">
  <button class="btn btn-sm btn-light d-lg-none me-2" onclick="document.getElementById('sidebar').classList.toggle('show')">
    <i class="bi bi-list fs-5"></i>
  </button>
  <span class="page-title"><?= htmlspecialchars($title ?? '') ?></span>

  <!-- Notifications placeholder -->
  <div class="d-flex align-items-center gap-2">
    <?php $overdueCount = $overdueCount ?? 0; ?>
    <span class="text-muted" style="font-size:.8rem"><?= date('d/m/Y') ?></span>
  </div>
</header>

<!-- MAIN CONTENT -->
<main id="main">
  <div class="main-content">
    <!-- Flash messages -->
    <?php foreach ($flash ?? [] as $f): ?>
    <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : ($f['type'] === 'success' ? 'success' : 'warning') ?> alert-dismissible fade show" role="alert">
      <i class="bi bi-<?= $f['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-2"></i>
      <?= htmlspecialchars($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endforeach; ?>

    <?= $content ?>
  </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
// Global SweetAlert2 confirm helper
if (typeof window.confirmDelete !== 'function') {
  window.confirmDelete = function(url, msg = '¿Está seguro de eliminar este registro?') {
    Swal.fire({
      title: '¿Confirmar eliminación?',
      text: msg,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(r => { if (r.isConfirmed) window.location.href = url; });
  }
}

function confirmAction(url, title, msg, icon = 'question') {
  Swal.fire({
    title, text: msg, icon,
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Confirmar',
    cancelButtonText: 'Cancelar'
  }).then(r => { if (r.isConfirmed) window.location.href = url; });
}

// Show flash via SweetAlert2 if data attribute exists
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-swal-type]').forEach(el => {
    Swal.fire({ icon: el.dataset.swalType, title: el.dataset.swalTitle,
      text: el.dataset.swalText, timer: 3000, timerProgressBar: true });
  });
});
</script>
</body>
</html>