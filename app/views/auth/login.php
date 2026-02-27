<form action="<?= url('/login') ?>" method="POST" autocomplete="off">
  <?= \App\Core\CSRF::field() ?>

  <div class="mb-3">
    <label class="form-label">Correo Electrónico</label>
    <div class="input-group">
      <span class="input-group-text bg-light border-end-0 rounded-start-3">
        <i class="bi bi-envelope text-muted"></i>
      </span>
      <input type="email" name="email" class="form-control border-start-0 ps-0"
             placeholder="usuario@empresa.hn" required autofocus
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
  </div>

  <div class="mb-4">
    <label class="form-label">Contraseña</label>
    <div class="input-group">
      <span class="input-group-text bg-light border-end-0 rounded-start-3">
        <i class="bi bi-lock text-muted"></i>
      </span>
      <input type="password" name="password" id="passwordInput"
             class="form-control border-start-0 border-end-0 ps-0"
             placeholder="••••••••" required>
      <button type="button" class="btn btn-light" onclick="togglePassword()">
        <i class="bi bi-eye" id="eyeIcon"></i>
      </button>
    </div>
  </div>

  <div class="d-flex align-items-center mb-4">
    <div class="form-check m-0">
      <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
      <label class="form-check-label text-muted small" for="remember">Recordarme</label>
    </div>
  </div>

  <button type="submit" class="btn btn-primary btn-login text-white">
    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
  </button>

  <hr class="my-4">
  <p class="text-center text-muted small mb-0">
    <i class="bi bi-shield-lock me-1"></i>Acceso seguro · Sistema v<?= APP_VERSION ?>
  </p>
</form>

<script>
function togglePassword() {
  const input = document.getElementById('passwordInput');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text'; icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password'; icon.className = 'bi bi-eye';
  }
}
</script>