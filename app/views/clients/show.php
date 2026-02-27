<?php
$currency = setting('app_currency','L');
$docTypes = ['letra_cambio','pagare','identidad','contrato','evidencia','otro'];
$isAdmin  = \App\Core\Auth::isAdmin();
?>

<!-- BREADCRUMB + ACTIONS -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= url('/clients') ?>">Clientes</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($client['full_name']) ?></li>
    </ol>
  </nav>
  <?php if ($isAdmin): ?>
  <div class="btn-group btn-group-sm">
    <a href="<?= url('/clients/' . $client['id'] . '/edit') ?>" class="btn btn-outline-primary">
      <i class="bi bi-pencil me-1"></i>Editar
    </a>
    <a href="<?= url('/loans/create?client_id=' . $client['id']) ?>" class="btn btn-success">
      <i class="bi bi-plus me-1"></i>Nuevo Préstamo
    </a>
    <a href="#" onclick="confirmDelete('<?= url('/clients/' . $client['id'] . '/delete') ?>')"
       class="btn btn-outline-danger">
      <i class="bi bi-trash"></i>
    </a>
  </div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <!-- CLIENT INFO -->
  <div class="col-xl-4">
    <div class="card shadow-sm border-0">
      <div class="card-body text-center pb-2">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold fs-3"
             style="width:72px;height:72px">
          <?= strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)) ?>
        </div>
        <h5 class="fw-bold mb-0"><?= htmlspecialchars($client['full_name']) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($client['code']) ?></small>
        <div class="mt-2">
          <span class="badge <?= $client['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
            <?= $client['is_active'] ? 'Activo' : 'Inactivo' ?>
          </span>
        </div>
      </div>
      <hr class="my-1">
      <div class="card-body pt-1">
        <?php $rows = [
          ['bi bi-card-text','DNI/RTN',       $client['identity_number'] ?? '-'],
          ['bi bi-telephone','Teléfono',       $client['phone'] ?? '-'],
          ['bi bi-telephone-plus','Teléfono 2',$client['phone2'] ?? '-'],
          ['bi bi-envelope',  'Email',         $client['email'] ?? '-'],
          ['bi bi-geo-alt',   'Dirección',     $client['address'] ?? '-'],
          ['bi bi-building',  'Ciudad',        $client['city'] ?? '-'],
          ['bi bi-briefcase', 'Ocupación',     $client['occupation'] ?? '-'],
          ['bi bi-cash',      'Ingreso Mensual',$client['monthly_income'] ? $currency . ' ' . number_format($client['monthly_income'],2) : '-'],
          ['bi bi-person',    'Asesor',        $client['assigned_name'] ?? 'Sin asignar'],
          ['bi bi-calendar',  'Registrado',    date('d/m/Y', strtotime($client['created_at']))],
        ]; ?>
        <?php foreach ($rows as [$icon, $label, $val]): ?>
        <div class="d-flex gap-2 align-items-start mb-2" style="font-size:.85rem">
          <i class="<?= $icon ?> text-muted mt-1" style="width:16px"></i>
          <div>
            <div class="text-muted" style="font-size:.7rem;line-height:1"><?= $label ?></div>
            <div><?= htmlspecialchars($val) ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($client['reference_name']): ?>
        <hr class="my-2">
        <div style="font-size:.8rem">
          <div class="text-muted mb-1">Referencia Personal</div>
          <strong><?= htmlspecialchars($client['reference_name']) ?></strong>
          <?php if ($client['reference_phone']): ?>
          <div><?= htmlspecialchars($client['reference_phone']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($client['notes']): ?>
        <hr class="my-2">
        <div style="font-size:.8rem">
          <div class="text-muted mb-1">Notas</div>
          <div><?= nl2br(htmlspecialchars($client['notes'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT COLUMN -->
  <div class="col-xl-8">
    <!-- LOANS -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header fw-semibold bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-coin me-2 text-primary"></i>Préstamos (<?= count($loans) ?>)</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($loans)): ?>
          <div class="text-center text-muted py-3"><i class="bi bi-inbox"></i> Sin préstamos registrados.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-custom table-hover mb-0">
            <thead><tr>
              <th>Número</th><th>Tipo</th><th class="text-end">Monto</th>
              <th class="text-end">Saldo</th><th class="text-center">Estado</th>
              <th>Fecha</th><th class="text-center">Acción</th>
            </tr></thead>
            <tbody>
            <?php foreach ($loans as $l): ?>
            <?php
              $statusMap = ['active'=>['bg-success','Activo'],'paid'=>['bg-primary','Pagado'],
                            'defaulted'=>['bg-danger','Moroso'],'cancelled'=>['bg-secondary','Cancelado'],
                            'restructured'=>['bg-warning text-dark','Restructurado']];
              $typeMap   = ['A'=>'Nivelado','B'=>'Variable','C'=>'Simple'];
              [$sc, $sl]   = $statusMap[$l['status']] ?? ['bg-secondary','Otro'];
            ?>
            <tr>
              <td><a href="<?= url('/loans/' . $l['id']) ?>"><?= htmlspecialchars($l['loan_number']) ?></a></td>
              <td><span class="badge bg-info text-dark">Tipo <?= $l['loan_type'] ?> · <?= $typeMap[$l['loan_type']] ?? '' ?></span></td>
              <td class="text-end"><?= $currency ?> <?= number_format($l['principal'], 2) ?></td>
              <td class="text-end fw-semibold"><?= $currency ?> <?= number_format($l['balance'], 2) ?></td>
              <td class="text-center"><span class="badge <?= $sc ?>"><?= $sl ?></span></td>
              <td><?= date('d/m/Y', strtotime($l['disbursement_date'])) ?></td>
              <td class="text-center">
                <a href="<?= url('/loans/' . $l['id']) ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- DOCUMENTS -->
    <div class="card shadow-sm border-0">
      <div class="card-header fw-semibold bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-folder2 me-2 text-warning"></i>Documentos</span>
        <?php if ($isAdmin): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
          <i class="bi bi-upload me-1"></i>Subir Documento
        </button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (empty($docsByType)): ?>
        <div class="text-center text-muted py-3"><i class="bi bi-folder-x d-block fs-3 mb-1"></i>Sin documentos.</div>
        <?php endif; ?>

        <?php foreach ($docTypes as $dt): ?>
        <?php if (empty($docsByType[$dt])) continue; ?>
        <div class="mb-3">
          <div class="fw-semibold text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.05em">
            <i class="bi bi-folder me-1"></i><?= \App\Services\DocumentService::docTypeLabel($dt) ?>
          </div>
          <div class="row g-2">
          <?php foreach ($docsByType[$dt] as $doc): ?>
          <div class="col-sm-6 col-md-4">
            <div class="border rounded p-2 d-flex align-items-center gap-2" style="font-size:.8rem">
              <div style="font-size:1.5rem;color:#64748b">
                <?= str_contains($doc['mime_type'],'pdf') ? '<i class="bi bi-file-pdf text-danger"></i>' : '<i class="bi bi-file-image text-info"></i>' ?>
              </div>
              <div class="flex-grow-1 overflow-hidden">
                <div class="text-truncate" title="<?= htmlspecialchars($doc['original_name']) ?>">
                  <?= htmlspecialchars($doc['original_name']) ?>
                </div>
                <div class="text-muted" style="font-size:.7rem">
                  <?= round($doc['file_size']/1024, 1) ?> KB · <?= htmlspecialchars($doc['uploaded_by_name']) ?>
                  <br><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?>
                </div>
              </div>
              <div class="d-flex flex-column gap-1">
                <a href="<?= url('/clients/' . $client['id'] . '/doc/' . $doc['id'] . '/download') ?>"
                   class="btn btn-sm btn-outline-secondary py-0 px-1" title="Descargar">
                  <i class="bi bi-download"></i>
                </a>
                <?php if ($isAdmin): ?>
                <a href="#" onclick="confirmDelete('<?= url('/clients/' . $client['id'] . '/doc/' . $doc['id'] . '/delete') ?>')"
                   class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- UPLOAD MODAL -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="<?= url('/clients/' . $client['id'] . '/upload') ?>" enctype="multipart/form-data">
        <?= \App\Core\CSRF::field() ?>
        <div class="modal-header">
          <h6 class="modal-title fw-bold">Subir Documento</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
            <select name="doc_type" class="form-select" required>
              <?php foreach ($docTypes as $dt): ?>
              <option value="<?= $dt ?>"><?= \App\Services\DocumentService::docTypeLabel($dt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Archivo <span class="text-danger">*</span></label>
            <input type="file" name="document" class="form-control" required
                   accept=".pdf,.jpg,.jpeg,.png,.webp">
            <div class="form-text">PDF, JPG, PNG, WEBP · Máx. <?= setting('max_upload_size_mb', 10) ?> MB</div>
          </div>
          <div class="mb-0">
            <label class="form-label">Descripción</label>
            <input type="text" name="description" class="form-control" maxlength="255"
                   placeholder="Ej: DUI frente y reverso...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-upload me-1"></i>Subir
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>