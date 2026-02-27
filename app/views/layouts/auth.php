<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Acceso') ?> · <?= htmlspecialchars($config['name'] ?? 'Préstamos') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="icon" type="image/x-icon" href="<?= e(url('assets/icons/favicon.ico')) ?>">
  <link rel="apple-touch-icon" href="<?= e(url('assets/icons/apple-touch-icon.png')) ?>">
  <style>
    body {
      background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 50%, #1e40af 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', system-ui, sans-serif;
    }

    .brand-logo {
      width: 140px;
      /* ajusta aquí: 120/160/180... */
      height: auto;
      /* mantiene proporción */
      display: block;
      margin: 0.75rem auto 0.5rem;
      /* centrado */
    }

    .auth-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 25px 60px rgba(0, 0, 0, .25);
      width: 100%;
      max-width: 420px;
      padding: 2.5rem;
    }

    .auth-logo {
      text-align: center;
      margin-bottom: 2rem;
    }

    .auth-logo .icon {
      background: #2563eb;
      width: 64px;
      height: 64px;
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
    }

    .auth-logo h1 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
      margin: 0;
    }

    .auth-logo p {
      color: #64748b;
      font-size: .875rem;
      margin: 0;
    }

    .form-label {
      font-weight: 600;
      font-size: .875rem;
      color: #374151;
    }

    .form-control {
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
      padding: .6rem .9rem;
    }

    .form-control:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
    }

    .btn-login {
      background: #2563eb;
      border: none;
      border-radius: 10px;
      padding: .7rem;
      font-weight: 600;
      width: 100%;
      transition: background .2s;
    }

    .btn-login:hover {
      background: #1d4ed8;
    }

    .input-group .btn {
      border-radius: 0 10px 10px 0;
      border: 1.5px solid #e2e8f0;
      border-left: 0;
    }
  </style>
</head>

<body>
  <div class="auth-card">
    <div class="auth-logo">
      <!-- <div class="icon"><i class="bi bi-bank2 text-white fs-3"></i></div> -->
      <img class="brand-logo" src="<?= e(url('assets/img/logo.jpg')) ?>" alt="Logo">
      <!-- <h1><?= htmlspecialchars($config['name'] ?? 'SistemaPréstamos') ?></h1> -->
      <p>Sistema de Gestión de Préstamos</p>
    </div>

    <?php foreach ($flash ?? [] as $f): ?>
      <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : 'success' ?> alert-sm py-2 mb-3" role="alert">
        <i class="bi bi-<?= $f['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?> me-1"></i>
        <?= htmlspecialchars($f['message']) ?>
      </div>
    <?php endforeach; ?>

    <?= $content ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>