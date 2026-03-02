<?php
$isEdit   = $editMode ?? false;
$c        = $client   ?? [];
$av       = $aval     ?? [];
$currency = setting('app_currency', 'L');
$allClients = $allClients ?? [];
?>
<div class="row justify-content-center">
<div class="col-xl-11">

<!-- BREADCRUMB -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a href="<?= url('/clients') ?>">Clientes</a></li>
    <li class="breadcrumb-item active">
      <?= $isEdit ? 'Editar: '.htmlspecialchars(($c['first_name']??'').' '.($c['last_name']??'')) : 'Nuevo Cliente' ?>
    </li>
  </ol>
</nav>

<form method="POST"
      action="<?= $isEdit ? url('/clients/' . $c['id'] . '/update') : url('/clients/store') ?>"
      enctype="multipart/form-data"
      id="clientForm">
  <?= \App\Core\CSRF::field() ?>

  <div class="row g-3">

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 1: DATOS PERSONALES
    ════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-person-vcard text-primary fs-5"></i>
          <span>Datos Personales</span>
        </div>
        <div class="card-body">
          <div class="row g-3">

            <div class="col-md-3">
              <label class="form-label">Nombre(s) <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control"
                     value="<?= htmlspecialchars($c['first_name'] ?? '') ?>" required maxlength="100">
            </div>
            <div class="col-md-3">
              <label class="form-label">Apellido(s) <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control"
                     value="<?= htmlspecialchars($c['last_name'] ?? '') ?>" required maxlength="100">
            </div>
            <div class="col-md-3">
              <label class="form-label">No. Identidad <span class="text-danger">*</span></label>
              <input type="text" name="identity_number" class="form-control"
                     value="<?= htmlspecialchars($c['identity_number'] ?? '') ?>" required maxlength="30"
                     placeholder="0801-YYYY-XXXXX">
            </div>
            <div class="col-md-3">
              <label class="form-label">Nacionalidad</label>
              <input type="text" name="nationality" class="form-control"
                     value="<?= htmlspecialchars($c['nationality'] ?? 'Hondureña') ?>" maxlength="100">
            </div>

            <div class="col-md-3">
              <label class="form-label">Profesión <small class="text-muted">(formal)</small></label>
              <input type="text" name="profession" class="form-control"
                     value="<?= htmlspecialchars($c['profession'] ?? '') ?>" maxlength="100"
                     placeholder="Ej: Contador, Docente, Ingeniero">
              <div class="form-text">Título o carrera formal</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Oficio / Ocupación <small class="text-muted">(actual)</small></label>
              <input type="text" name="occupation" class="form-control"
                     value="<?= htmlspecialchars($c['occupation'] ?? '') ?>" maxlength="100"
                     placeholder="Ej: Comerciante, Agricultor, Mecánico">
              <div class="form-text">Actividad económica actual</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ingreso Mensual (<?= $currency ?>)</label>
              <input type="number" name="monthly_income" class="form-control" step="0.01" min="0"
                     value="<?= htmlspecialchars($c['monthly_income'] ?? '') ?>" placeholder="0.00">
            </div>
            <div class="col-md-3">
              <label class="form-label">Correo Electrónico</label>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($c['email'] ?? '') ?>" maxlength="180"
                     placeholder="correo@ejemplo.com">
            </div>

            <!-- TELÉFONOS -->
            <div class="col-md-3">
              <label class="form-label">Celular <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control"
                     value="<?= htmlspecialchars($c['phone'] ?? '') ?>" required maxlength="20"
                     placeholder="+504 XXXX-XXXX">
              <div class="form-text text-muted">Número de celular principal</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Teléfono Fijo</label>
              <input type="tel" name="phone2" class="form-control"
                     value="<?= htmlspecialchars($c['phone2'] ?? '') ?>" maxlength="20"
                     placeholder="+504 XXXX-XXXX">
              <div class="form-text text-muted">Teléfono fijo o alternativo</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Teléfono Trabajo <span class="text-muted small">(opcional)</span></label>
              <input type="tel" name="work_phone" class="form-control"
                     value="<?= htmlspecialchars($c['work_phone'] ?? '') ?>" maxlength="20"
                     placeholder="+504 XXXX-XXXX">
            </div>

            <!-- DOMICILIO -->
            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <textarea name="address" class="form-control" rows="2" maxlength="500"
                        placeholder="Barrio, colonia, calle, número de casa..."><?= htmlspecialchars($c['address'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ciudad / Municipio</label>
              <input type="text" name="city" class="form-control"
                     value="<?= htmlspecialchars($c['city'] ?? '') ?>" maxlength="100"
                     placeholder="Ej: Siguatepeque">
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 2: ESTADO CIVIL
    ════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-heart text-danger fs-5"></i>
          <span>Estado Civil</span>
        </div>
        <div class="card-body">
          <div class="row g-3 align-items-start">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Estado Civil</label>
              <div class="d-flex flex-wrap gap-3 mt-1">
                <?php
                // Ahora incluye Unión Libre
                $maritalOptions = [
                  'soltero'     => 'Soltero/a',
                  'casado'      => 'Casado/a',
                  'union_libre' => 'Unión Libre',
                  'divorciado'  => 'Divorciado/a',
                  'viudo'       => 'Viudo/a',
                ];
                $currentMarital = $c['marital_status'] ?? 'soltero';
                foreach ($maritalOptions as $val => $label):
                ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="marital_status"
                         id="ms_<?= $val ?>" value="<?= $val ?>"
                         <?= $currentMarital === $val ? 'checked' : '' ?>
                         onchange="toggleSpouse()">
                  <label class="form-check-label" for="ms_<?= $val ?>"><?= $label ?></label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <?php $showSpouse = in_array($currentMarital, ['casado','union_libre']); ?>
            <div class="col-md-7" id="spouseFields"
                 style="display:<?= $showSpouse ? 'block' : 'none' ?>">
              <div class="p-3 rounded border border-danger border-opacity-25 bg-danger bg-opacity-5">
                <div class="fw-semibold text-danger mb-2">
                  <i class="bi bi-people-fill me-1"></i>Datos del Cónyuge / Conviviente
                </div>
                <div class="row g-2">
                  <div class="col-md-5">
                    <label class="form-label form-label-sm">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="spouse_name" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($c['spouse_name'] ?? '') ?>" maxlength="200"
                           placeholder="Nombre completo" id="spouseName">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label form-label-sm">No. Identidad</label>
                    <input type="text" name="spouse_identity" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($c['spouse_identity'] ?? '') ?>" maxlength="30"
                           placeholder="0801-XXXX-XXXXX">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label form-label-sm">Teléfono</label>
                    <input type="tel" name="spouse_phone" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($c['spouse_phone'] ?? '') ?>" maxlength="20"
                           placeholder="+504 XXXX-XXXX">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 3: FOTO DE IDENTIDAD (CLIENTE)
    ════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-card-image text-success fs-5"></i>
          <span>Fotografía de Identidad — <small class="text-muted fw-normal">Frente y Reverso (fondo blanco)</small></span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <?php foreach ([
              ['identity_front', 'Frente (Anverso)', '🪪', $c['identity_front_path'] ?? null],
              ['identity_back',  'Reverso',          '🔄', $c['identity_back_path']  ?? null],
            ] as [$field, $label, $icon, $existing]): ?>
            <div class="col-md-6">
              <div class="identity-capture-box border rounded p-3" data-field="<?= $field ?>">
                <div class="fw-semibold mb-2"><?= $icon ?> <?= $label ?></div>

                <div class="identity-preview-wrapper mb-2 text-center"
                     style="background:repeating-linear-gradient(45deg,#f8f9fa,#f8f9fa 5px,#e9ecef 5px,#e9ecef 10px);
                            border-radius:8px;overflow:hidden;position:relative;
                            min-height:140px;display:flex;align-items:center;justify-content:center;">
                  <?php if ($existing): ?>
                  <img src="<?= url('/' . $existing) ?>" id="preview_<?= $field ?>"
                       class="img-fluid" style="max-height:160px;object-fit:contain;display:block">
                  <?php else: ?>
                  <div id="preview_<?= $field ?>" class="text-muted p-3 text-center">
                    <i class="bi bi-card-image fs-1 d-block opacity-25"></i>
                    <div style="font-size:.78rem">Sin imagen</div>
                  </div>
                  <?php endif; ?>
                  <div id="guide_<?= $field ?>"
                       style="display:none;position:absolute;inset:8px;
                              border:2px dashed rgba(255,255,255,.8);border-radius:6px;pointer-events:none">
                    <span style="position:absolute;top:50%;left:50%;
                                 transform:translate(-50%,-50%);
                                 color:rgba(255,255,255,.7);font-size:.72rem;text-align:center">
                      Centra la identidad<br>dentro del recuadro
                    </span>
                  </div>
                </div>

                <div id="camBox_<?= $field ?>" style="display:none" class="mb-2">
                  <video id="video_<?= $field ?>" autoplay playsinline
                         style="width:100%;max-height:180px;border-radius:6px;
                                background:#000;object-fit:cover"></video>
                  <div class="d-flex gap-2 mt-1">
                    <button type="button" class="btn btn-success btn-sm flex-fill"
                            onclick="capturePhoto('<?= $field ?>')">
                      <i class="bi bi-camera me-1"></i>Capturar
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="stopCamera('<?= $field ?>')">
                      <i class="bi bi-x"></i>
                    </button>
                  </div>
                </div>

                <input type="hidden" name="<?= $field ?>_b64" id="b64_<?= $field ?>">
                <input type="file"   name="<?= $field ?>"     id="file_<?= $field ?>"
                       accept="image/jpeg,image/png,image/webp"
                       class="d-none" onchange="previewFile('<?= $field ?>', this)">

                <div class="d-grid gap-1">
                  <div class="btn-group btn-group-sm w-100">
                    <button type="button" class="btn btn-outline-primary"
                            onclick="document.getElementById('file_<?= $field ?>').click()">
                      <i class="bi bi-upload me-1"></i>Subir archivo
                    </button>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="startCamera('<?= $field ?>')">
                      <i class="bi bi-camera me-1"></i>Tomar foto
                    </button>
                  </div>
                  <?php if ($existing): ?>
                  <div class="text-muted text-center" style="font-size:.72rem">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>Imagen actual guardada
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-2 text-muted" style="font-size:.75rem">
            <i class="bi bi-info-circle me-1"></i>
            Fotografiar sobre superficie blanca. Máximo 5 MB. Formatos: JPG, PNG, WEBP.
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 4: REFERENCIAS
    ════════════════════════════════════════════════════════ -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-person-lines-fill text-secondary fs-5"></i>
          <span>Referencia Personal</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">Nombre Completo</label>
              <input type="text" name="ref_personal_name" class="form-control" maxlength="150"
                     value="<?= htmlspecialchars($c['ref_personal_name'] ?? $c['reference_name'] ?? '') ?>"
                     placeholder="Nombre y apellidos">
            </div>
            <div class="col-md-5">
              <label class="form-label">Teléfono / Celular</label>
              <input type="tel" name="ref_personal_phone" class="form-control" maxlength="20"
                     value="<?= htmlspecialchars($c['ref_personal_phone'] ?? $c['reference_phone'] ?? '') ?>"
                     placeholder="+504 XXXX-XXXX">
            </div>
            <div class="col-12">
              <label class="form-label">Relación / Parentesco</label>
              <input type="text" name="ref_personal_rel" class="form-control" maxlength="80"
                     placeholder="Ej: Hermano, Amigo, Vecino, Primo..."
                     value="<?= htmlspecialchars($c['ref_personal_rel'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-building text-secondary fs-5"></i>
          <span>Referencia Laboral</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">Nombre del Jefe / Contacto</label>
              <input type="text" name="ref_labor_name" class="form-control" maxlength="150"
                     value="<?= htmlspecialchars($c['ref_labor_name'] ?? '') ?>"
                     placeholder="Nombre y apellidos">
            </div>
            <div class="col-md-5">
              <label class="form-label">Teléfono Laboral</label>
              <input type="tel" name="ref_labor_phone" class="form-control" maxlength="20"
                     value="<?= htmlspecialchars($c['ref_labor_phone'] ?? '') ?>"
                     placeholder="+504 XXXX-XXXX">
            </div>
            <div class="col-12">
              <label class="form-label">Empresa / Centro de Trabajo</label>
              <input type="text" name="ref_labor_company" class="form-control" maxlength="150"
                     placeholder="Nombre de la empresa donde trabaja..."
                     value="<?= htmlspecialchars($c['ref_labor_company'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 5: ASIGNACIÓN Y ESTADO
    ════════════════════════════════════════════════════════ -->
    <div class="col-md-6">
      <div class="card shadow-sm border-0">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center gap-2">
          <i class="bi bi-person-badge text-secondary fs-5"></i>
          <span>Asignación y Estado</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-<?= $isEdit ? '8' : '12' ?>">
              <label class="form-label">Asesor / Cobrador Asignado</label>
              <select name="assigned_to" class="form-select">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($advisors as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($c['assigned_to'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($isEdit): ?>
            <div class="col-4">
              <label class="form-label">Activo</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                       <?= ($c['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Habilitado</label>
              </div>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Notas Internas</label>
              <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                        placeholder="Observaciones, historial, información adicional..."><?= htmlspecialchars($c['notes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         SECCIÓN 6: AVAL / FIADOR SOLIDARIO (OPCIONAL)
    ════════════════════════════════════════════════════════ -->
    <div class="col-12">
      <div class="card shadow-sm border-0" style="border-top:4px solid #f59e0b!important">
        <div class="card-header fw-semibold py-2 bg-white border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-warning fs-5"></i>
            <span>AVAL / Fiador Solidario
              <span class="badge bg-secondary fw-normal ms-1" style="font-size:.72rem">Opcional</span>
            </span>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="enableAval"
                   <?= !empty($av['full_name']) ? 'checked' : '' ?>
                   onchange="toggleAval()">
            <label class="form-check-label fw-normal" for="enableAval">Agregar Aval</label>
          </div>
        </div>

        <div id="avalSection" style="display:<?= !empty($av['full_name']) ? 'block' : 'none' ?>">
          <div class="card-body">

            <!-- Buscar cliente existente del sistema como aval -->
            <?php if (!empty($allClients)): ?>
            <div class="row g-3 mb-3">
              <div class="col-12">
                <div class="alert alert-info py-2 mb-0" style="font-size:.82rem">
                  <i class="bi bi-info-circle me-1"></i>
                  Puede seleccionar un <strong>cliente existente</strong> como aval
                  o ingresar los datos manualmente.
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Buscar cliente del sistema como aval</label>
                <select name="aval_client_id" id="avalClientSelect" class="form-select"
                        onchange="fillAvalFromClient(this)">
                  <option value="">— Ingresar datos manualmente —</option>
                  <?php foreach ($allClients as $ac): ?>
                  <option value="<?= $ac['id'] ?>"
                          data-name="<?= htmlspecialchars($ac['full_name']           ?? '') ?>"
                          data-identity="<?= htmlspecialchars($ac['identity_number'] ?? '') ?>"
                          data-phone="<?= htmlspecialchars($ac['phone']              ?? '') ?>"
                          data-address="<?= htmlspecialchars($ac['address']          ?? '') ?>"
                          data-city="<?= htmlspecialchars($ac['city']                ?? '') ?>"
                          <?= ($av['aval_client_id'] ?? '') == $ac['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ac['full_name']) ?>
                    <?= !empty($ac['identity_number']) ? '— '.$ac['identity_number'] : '' ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <?php endif; ?>

            <!-- Datos del aval -->
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Nombre Completo del Aval <span class="text-danger">*</span></label>
                <input type="text" name="aval_full_name" id="avalFullName" class="form-control"
                       value="<?= htmlspecialchars($av['full_name'] ?? '') ?>" maxlength="200"
                       placeholder="Nombre y apellidos completos">
              </div>
              <div class="col-md-3">
                <label class="form-label">No. Identidad</label>
                <input type="text" name="aval_identity" id="avalIdentity" class="form-control"
                       value="<?= htmlspecialchars($av['identity_number'] ?? '') ?>" maxlength="30"
                       placeholder="0801-XXXX-XXXXX">
              </div>
              <div class="col-md-2">
                <label class="form-label">Celular</label>
                <input type="tel" name="aval_phone" id="avalPhone" class="form-control"
                       value="<?= htmlspecialchars($av['phone'] ?? '') ?>" maxlength="20"
                       placeholder="+504 XXXX-XXXX">
              </div>
              <div class="col-md-2">
                <label class="form-label">Teléfono Fijo</label>
                <input type="tel" name="aval_phone2" class="form-control"
                       value="<?= htmlspecialchars($av['phone2'] ?? '') ?>" maxlength="20"
                       placeholder="+504 XXXX-XXXX">
              </div>
              <div class="col-md-1">
                <label class="form-label">Nac.</label>
                <input type="text" name="aval_nationality" class="form-control"
                       value="<?= htmlspecialchars($av['nationality'] ?? 'Hondureña') ?>" maxlength="50">
              </div>
              <div class="col-md-3">
                <label class="form-label">Ocupación / Trabajo</label>
                <input type="text" name="aval_occupation" class="form-control"
                       value="<?= htmlspecialchars($av['occupation'] ?? '') ?>" maxlength="100"
                       placeholder="Ej: Comerciante, Empleado...">
              </div>
              <div class="col-md-3">
                <label class="form-label">Relación con el Deudor</label>
                <input type="text" name="aval_relationship" class="form-control"
                       value="<?= htmlspecialchars($av['relationship'] ?? '') ?>" maxlength="100"
                       placeholder="Ej: Hermano, Cónyuge, Amigo...">
              </div>
              <div class="col-md-4">
                <label class="form-label">Dirección</label>
                <input type="text" name="aval_address" id="avalAddress" class="form-control"
                       value="<?= htmlspecialchars($av['address'] ?? '') ?>" maxlength="300"
                       placeholder="Barrio, colonia, calle...">
              </div>
              <div class="col-md-2">
                <label class="form-label">Ciudad</label>
                <input type="text" name="aval_city" id="avalCity" class="form-control"
                       value="<?= htmlspecialchars($av['city'] ?? '') ?>" maxlength="100">
              </div>
              <div class="col-12">
                <label class="form-label">Notas del Aval</label>
                <textarea name="aval_notes" class="form-control" rows="2" maxlength="500"
                          placeholder="Información adicional sobre el aval..."><?= htmlspecialchars($av['notes'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Fotos de identidad del aval -->
            <div class="mt-3 pt-3 border-top">
              <div class="fw-semibold mb-2">
                <i class="bi bi-card-image me-1 text-warning"></i>
                Fotografía de Identidad del Aval
                <small class="text-muted fw-normal">(frente y reverso)</small>
              </div>
              <div class="row g-3">
                <?php foreach ([
                  ['aval_identity_front', 'Frente (Anverso)', $av['identity_front_path'] ?? null],
                  ['aval_identity_back',  'Reverso',          $av['identity_back_path']  ?? null],
                ] as [$field, $label, $existing]): ?>
                <div class="col-md-6">
                  <div class="identity-capture-box border rounded p-3" data-field="<?= $field ?>">
                    <div class="fw-semibold mb-2 small"><?= $label ?></div>
                    <div class="text-center mb-2 position-relative"
                         style="background:#f8f9fa;border-radius:6px;min-height:100px;
                                display:flex;align-items:center;justify-content:center;overflow:hidden">
                      <?php if ($existing): ?>
                      <img src="<?= url('/' . $existing) ?>" id="preview_<?= $field ?>"
                           class="img-fluid" style="max-height:120px;object-fit:contain">
                      <?php else: ?>
                      <div id="preview_<?= $field ?>" class="text-muted text-center p-3">
                        <i class="bi bi-card-image fs-2 opacity-25 d-block"></i>
                        <span style="font-size:.72rem">Sin imagen</span>
                      </div>
                      <?php endif; ?>
                      <div id="guide_<?= $field ?>"
                           style="display:none;position:absolute;inset:6px;
                                  border:2px dashed rgba(255,255,255,.7);
                                  border-radius:4px;pointer-events:none"></div>
                    </div>
                    <div id="camBox_<?= $field ?>" style="display:none" class="mb-2">
                      <video id="video_<?= $field ?>" autoplay playsinline
                             style="width:100%;max-height:140px;border-radius:4px;background:#000"></video>
                      <div class="d-flex gap-2 mt-1">
                        <button type="button" class="btn btn-success btn-sm flex-fill"
                                onclick="capturePhoto('<?= $field ?>')">
                          <i class="bi bi-camera me-1"></i>Capturar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="stopCamera('<?= $field ?>')">
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                    </div>
                    <input type="hidden" name="<?= $field ?>_b64" id="b64_<?= $field ?>">
                    <input type="file"   name="<?= $field ?>"     id="file_<?= $field ?>"
                           accept="image/jpeg,image/png,image/webp" class="d-none"
                           onchange="previewFile('<?= $field ?>', this)">
                    <div class="btn-group btn-group-sm w-100">
                      <button type="button" class="btn btn-outline-secondary"
                              onclick="document.getElementById('file_<?= $field ?>').click()">
                        <i class="bi bi-upload me-1"></i>Subir
                      </button>
                      <button type="button" class="btn btn-outline-secondary"
                              onclick="startCamera('<?= $field ?>')">
                        <i class="bi bi-camera me-1"></i>Cámara
                      </button>
                    </div>
                    <?php if ($existing): ?>
                    <div class="text-muted text-center mt-1" style="font-size:.7rem">
                      <i class="bi bi-check-circle-fill text-success me-1"></i>Imagen guardada
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <?php if ($isEdit && !empty($av)): ?>
            <div class="mt-3 pt-2 border-top d-flex justify-content-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_aval" value="1" id="removeAval">
                <label class="form-check-label text-danger" for="removeAval">
                  <i class="bi bi-trash me-1"></i>Eliminar aval registrado
                </label>
              </div>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

    <!-- BOTONES -->
    <div class="col-12">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="bi bi-check-lg me-1"></i>
          <?= $isEdit ? 'Actualizar Cliente' : 'Guardar Cliente' ?>
        </button>
        <a href="<?= url('/clients' . ($isEdit ? '/' . $c['id'] : '')) ?>"
           class="btn btn-outline-secondary btn-lg">
          <i class="bi bi-x me-1"></i>Cancelar
        </a>
      </div>
    </div>

  </div><!-- /row -->
</form>
</div>
</div>

<script>
// ── Estado civil → mostrar/ocultar cónyuge ────────────────────
// Reacciona a 'casado' Y 'union_libre'
function toggleSpouse() {
  const val  = document.querySelector('input[name="marital_status"]:checked')?.value;
  const show = ['casado','union_libre'].includes(val);
  document.getElementById('spouseFields').style.display = show ? 'block' : 'none';
  const nameInput = document.getElementById('spouseName');
  if (nameInput) nameInput.required = show;
}

// ── Toggle sección aval ───────────────────────────────────────
function toggleAval() {
  const show = document.getElementById('enableAval').checked;
  document.getElementById('avalSection').style.display = show ? 'block' : 'none';
}

// ── Autocompletar aval desde cliente del sistema ──────────────
// Llena: nombre, identidad, teléfono, dirección y ciudad
function fillAvalFromClient(sel) {
  if (!sel.value) return;
  const opt = sel.options[sel.selectedIndex];
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
  set('avalFullName', opt.dataset.name);
  set('avalIdentity', opt.dataset.identity);
  set('avalPhone',    opt.dataset.phone);
  set('avalAddress',  opt.dataset.address);
  set('avalCity',     opt.dataset.city);
}

// ── Cámara ────────────────────────────────────────────────────
const streams = {};

async function startCamera(field) {
  const camBox = document.getElementById('camBox_' + field);
  const video  = document.getElementById('video_' + field);
  const guide  = document.getElementById('guide_' + field);
  const open   = async (constraints) => {
    const s = await navigator.mediaDevices.getUserMedia(constraints);
    streams[field] = s;
    video.srcObject = s;
    camBox.style.display = 'block';
    if (guide) guide.style.display = 'block';
  };
  try {
    await open({ video: { facingMode:'environment', width:{ideal:1280}, height:{ideal:720} } });
  } catch {
    try { await open({ video: true }); }
    catch { alert('No se pudo acceder a la cámara. Sube el archivo manualmente.'); }
  }
}

function stopCamera(field) {
  streams[field]?.getTracks().forEach(t => t.stop());
  delete streams[field];
  document.getElementById('camBox_' + field).style.display = 'none';
  const guide = document.getElementById('guide_' + field);
  if (guide) guide.style.display = 'none';
}

function capturePhoto(field) {
  const video   = document.getElementById('video_' + field);
  const preview = document.getElementById('preview_' + field);
  // Proporción tarjeta hondureña 85.6×54 mm ≈ 1.585:1
  const canvas = document.createElement('canvas');
  const W = 1200, H = Math.round(W / 1.585);
  canvas.width = W; canvas.height = H;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, W, H);
  const vw = video.videoWidth, vh = video.videoHeight;
  const tr = W/H, vr = vw/vh;
  let sx,sy,sw,sh;
  if (vr>tr){sh=vh;sw=Math.round(vh*tr);sx=Math.round((vw-sw)/2);sy=0;}
  else      {sw=vw;sh=Math.round(vw/tr);sx=0;sy=Math.round((vh-sh)/2);}
  ctx.drawImage(video,sx,sy,sw,sh,0,0,W,H);
  const b64 = canvas.toDataURL('image/jpeg', 0.88);
  const img = document.createElement('img');
  img.src = b64; img.className = 'img-fluid';
  img.style.cssText = 'max-height:160px;object-fit:contain;display:block';
  img.id = 'preview_' + field;
  preview.replaceWith(img);
  document.getElementById('b64_' + field).value = b64;
  stopCamera(field);
}

function previewFile(field, input) {
  if (!input.files?.[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const preview = document.getElementById('preview_' + field);
    if (preview.tagName === 'IMG') {
      preview.src = e.target.result;
    } else {
      const img = document.createElement('img');
      img.src = e.target.result; img.className = 'img-fluid';
      img.style.cssText = 'max-height:160px;object-fit:contain;display:block';
      img.id = 'preview_' + field;
      preview.replaceWith(img);
    }
    document.getElementById('b64_' + field).value = '';
  };
  reader.readAsDataURL(input.files[0]);
}

// ── Submit: convertir base64 a File ──────────────────────────
document.getElementById('clientForm').addEventListener('submit', async function() {
  const fields = ['identity_front','identity_back','aval_identity_front','aval_identity_back'];
  for (const field of fields) {
    const b64Input  = document.getElementById('b64_' + field);
    const fileInput = document.getElementById('file_' + field);
    if (!b64Input?.value || !fileInput) continue;
    const blob = await (await fetch(b64Input.value)).blob();
    const dt   = new DataTransfer();
    dt.items.add(new File([blob], field+'.jpg', {type:'image/jpeg'}));
    fileInput.files = dt.files;
    b64Input.value  = '';
  }
});
</script>