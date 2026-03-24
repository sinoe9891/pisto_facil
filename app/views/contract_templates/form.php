<?php
// app/views/contract_templates/form.php
// Variables: $template (array|null)
$isEdit  = !empty($template);
$action  = $isEdit ? url('/contract-templates/' . $template['id'] . '/update') : url('/contract-templates/store');
$content = $template['content'] ?? '';

// Grupos de variables disponibles
$variables = [
    'Cliente' => [
        '{{cliente_nombre}}'       => 'Nombre completo',
        '{{cliente_identidad}}'    => 'No. de Identidad',
        '{{cliente_estado_civil}}' => 'Estado Civil',
        '{{cliente_profesion}}'    => 'Profesión / Oficio',
        '{{cliente_nacionalidad}}' => 'Nacionalidad',
        '{{cliente_direccion}}'    => 'Dirección completa',
        '{{cliente_celular}}'      => 'Celular',
        '{{cliente_telefono}}'     => 'Teléfono fijo',
        '{{cliente_email}}'        => 'Correo electrónico',
        '{{conyugue_nombre}}'      => 'Nombre cónyuge',
    ],
    'Préstamo' => [
        '{{prestamo_numero}}'      => 'No. de Préstamo',
        '{{monto}}'                => 'Monto (número)',
        '{{monto_letras}}'         => 'Monto en letras',
        '{{moneda}}'               => 'Símbolo moneda',
        '{{tasa_interes}}'         => 'Tasa de Interés %',
        '{{tasa_mora}}'            => 'Tasa Moratoria % Mensual',
        '{{tasa_mora_mensual}}'    => 'Tasa Moratoria % Mensual (igual a tasa_mora)',
        '{{tasa_mora_diaria}}'     => 'Tasa Moratoria % Diaria (mensual ÷ 30)',
        '{{dias_gracia}}'          => 'Días de Gracia del préstamo',
        '{{plazo}}'                => 'Plazo (cuotas)',
        '{{frecuencia}}'           => 'Frecuencia de pago',
        '{{tipo_prestamo}}'        => 'Tipo de préstamo',
        '{{fecha_desembolso}}'     => 'Fecha desembolso',
        '{{fecha_primer_pago}}'    => 'Fecha primer pago',
        '{{fecha_vencimiento}}'    => 'Fecha vencimiento',
        '{{dias_gracia}}'          => 'Días de gracia',
        '{{forma_pago}}'           => 'Forma de pago',
        '{{lugar_pago}}'           => 'Lugar de pago',
    ],
    'Empresa' => [
        '{{empresa_nombre}}'       => 'Razón social',
        '{{empresa_rtn}}'          => 'RTN',
        '{{empresa_direccion}}'    => 'Dirección empresa',
        '{{rep_nombre}}'           => 'Representante legal',
        '{{rep_identidad}}'        => 'Identidad representante',
        '{{jurisdiccion}}'         => 'Jurisdicción',
        '{{ciudad_firma}}'         => 'Ciudad de firma',
    ],
    'Fecha de Hoy' => [
        '{{fecha_hoy}}'   => 'Fecha completa (dd/mm/aaaa)',
        '{{dia}}'         => 'Día',
        '{{mes}}'         => 'Mes (ENERO…)',
        '{{anio}}'        => 'Año',
    ],
    'Aval (si existe)' => [
        '{{aval_nombre}}'     => 'Nombre del aval',
        '{{aval_identidad}}'  => 'Identidad del aval',
        '{{aval_direccion}}'  => 'Dirección del aval',
        '{{aval_telefono}}'   => 'Teléfono del aval',
    ],
];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('/contract-templates') ?>">Plantillas</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar' : 'Nueva' ?></li>
        </ol>
    </nav>
</div>

<div class="row g-3">
    <!-- Panel de variables -->
    <div class="col-lg-3">
        <div class="card shadow-sm border-0 sticky-top" style="top:70px">
            <div class="card-header bg-white fw-semibold py-2 border-bottom">
                <i class="bi bi-braces me-1 text-primary"></i>Variables disponibles
            </div>
            <div class="card-body p-2" style="max-height:75vh;overflow-y:auto">
                <div class="form-text text-muted mb-2">
                    Haga clic en una variable para insertarla donde está el cursor en el editor.
                </div>
                <?php foreach ($variables as $grupo => $vars): ?>
                    <div class="mb-2">
                        <div class="fw-semibold text-muted"
                            style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;padding:4px 0">
                            <?= $grupo ?></div>
                        <?php foreach ($vars as $tag => $desc): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 var-btn"
                                data-tag="<?= htmlspecialchars($tag) ?>" title="<?= htmlspecialchars($desc) ?>"
                                style="font-size:.72rem;padding:2px 6px">
                                <?= htmlspecialchars($tag) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Editor -->
    <div class="col-lg-9">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold py-2 border-bottom">
                <i class="bi bi-file-earmark-text me-1 text-primary"></i>
                <?= $isEdit ? 'Editar Plantilla' : 'Nueva Plantilla' ?>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $action ?>">
                    <?= \App\Core\CSRF::field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre de la Plantilla <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                value="<?= htmlspecialchars($template['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select name="template_type" class="form-select">
                                <option value="contrato"
                                    <?= ($template['template_type'] ?? 'contrato') === 'contrato' ? 'selected' : '' ?>>
                                    Contrato de Préstamo</option>
                                <option value="pagare"
                                    <?= ($template['template_type'] ?? '') === 'pagare'   ? 'selected' : '' ?>>Pagaré
                                </option>
                                <option value="otro"
                                    <?= ($template['template_type'] ?? '') === 'otro'     ? 'selected' : '' ?>>Otro
                                </option>
                            </select>
                        </div>
                        <?php if ($isEdit): ?>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Estado</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= ($template['is_active'] ?? 1) ? 'selected' : '' ?>>Activa</option>
                                    <option value="0" <?= !($template['is_active'] ?? 1) ? 'selected' : '' ?>>Inactiva
                                    </option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TinyMCE editor -->
                    <label class="form-label fw-semibold">Contenido del Documento <span
                            class="text-danger">*</span></label>
                    <textarea name="content" id="template-content" class="form-control" rows="20"
                        style="font-family:monospace"><?= htmlspecialchars($content) ?></textarea>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Guardar Cambios' : 'Crear Plantilla' ?>
                        </button>
                        <a href="<?= url('/contract-templates') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- TinyMCE CDN (gratuito, sin API key para versión básica) -->
<script src="<?= url('/assets/vendor/tinymce/tinymce/tinymce.min.js') ?>"></script>
<script>
    tinymce.init({
        selector: '#template-content',
        language: 'es',
        language_url: '<?= url("/assets/vendor/tinymce/tinymce/langs/es.js") ?>',
        base_url: '<?= url("/assets/vendor/tinymce/tinymce") ?>',
        suffix: '.min',
        plugins: 'table lists link code fullscreen',
        toolbar: [
            'undo redo | bold italic underline | forecolor backcolor',
            'alignleft aligncenter alignright alignjustify | bullist numlist | table | link | code fullscreen'
        ],
        menubar: false,
        height: 550,
        content_css: 'document',
        valid_elements: '*[*]',
        extended_valid_elements: '*[*]',
        forced_root_block: 'p',
        setup: function(editor) {
            window._tinyEditor = editor;
        }
    });

    // Insertar variable en la posición del cursor
    document.querySelectorAll('.var-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tag = this.getAttribute('data-tag');
            if (window._tinyEditor) {
                window._tinyEditor.insertContent(tag);
                window._tinyEditor.focus();
            } else {
                // Fallback si TinyMCE no cargó
                const ta = document.getElementById('template-content');
                const start = ta.selectionStart,
                    end = ta.selectionEnd;
                ta.value = ta.value.substring(0, start) + tag + ta.value.substring(end);
                ta.selectionStart = ta.selectionEnd = start + tag.length;
                ta.focus();
            }
        });
    });
</script>