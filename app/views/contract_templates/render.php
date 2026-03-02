<!DOCTYPE html>
<?php // app/views/contract_templates/render.php ?>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'Documento') ?></title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Times New Roman',serif; font-size:11.5pt; color:#000; background:#fff; padding:2cm 2.5cm; }
    h1,h2,h3 { margin-bottom:.5em; }
    p { text-align:justify; line-height:1.7; margin-bottom:8px; }
    table { width:100%; border-collapse:collapse; margin:10px 0; }
    table td, table th { padding:5px 8px; border:1px solid #ccc; }
    table td:first-child { font-weight:bold; background:#f9f9f9; }
    ol, ul { padding-left:20px; margin-bottom:8px; }
    li { margin-bottom:4px; line-height:1.6; }
    @media print {
      body { padding:1.5cm 2cm; }
      .no-print { display:none!important; }
      @page { margin:1.5cm; size:letter; }
    }
    .print-btn { position:fixed; top:10px; right:10px; background:#2563eb; color:#fff;
                 border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:11pt; z-index:999; }
  </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">🖨 Imprimir</button>
<?= $html ?? '' ?>
</body>
</html>