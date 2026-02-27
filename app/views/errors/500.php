<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>500 – Error del servidor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    body { background:#f1f5f9; display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .error-code { font-size:5rem; font-weight:900; color:#64748b; line-height:1; }
  </style>
</head>
<body>
<div class="text-center">
  <div class="error-code">500</div>
  <h4 class="fw-bold mb-2">Error interno del servidor</h4>
  <p class="text-muted"><?= htmlspecialchars($message ?? 'Ocurrió un error inesperado. Por favor intente nuevamente.') ?></p>
  <a href="javascript:history.back()" class="btn btn-outline-secondary me-2">← Volver</a>
  <a href="/dashboard" class="btn btn-primary">Dashboard</a>
</div>
</body>
</html>
