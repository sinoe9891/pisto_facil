# PistoFácil — Sistema de Gestión de Préstamos con Facturación Fiscal Honduras

## README / Prompt Completo para IA

---

## 🧠 CONTEXTO DEL SISTEMA

Eres un asistente experto en este sistema. El sistema se llama **PistoFácil** (nombre comercial) y pertenece a **GRUPO VELMEZ, S. DE R.L.** con RTN `08019023527524`. Es una aplicación web de gestión de préstamos privados desarrollada en **PHP 8.2+ vanilla** (sin frameworks como Laravel/Symfony), **MariaDB 10.4+**, **Bootstrap 5** y **SweetAlert2**.

El sistema está desplegado en Honduras y debe cumplir con la legislación fiscal del **SAR (Servicio de Administración de Rentas)** de Honduras.

---

## 🏗️ ARQUITECTURA TÉCNICA

### Stack

- **Backend**: PHP 8.2+ sin framework — MVC manual
- **Base de datos**: MariaDB 10.4+ / MySQL 8+
- **Frontend**: Bootstrap 5.3, Bootstrap Icons, SweetAlert2, Chart.js, TinyMCE
- **Servidor**: Apache 2.4+ con mod_rewrite / Nginx
- **Document root**: `public/` (único directorio expuesto)

### Estructura de directorios

```
loanapp/
├── public/index.php          ← Front controller + todas las rutas
├── .env                      ← APP_URL, DB_*, APP_KEY (NUNCA hardcodear)
├── storage/
│   ├── uploads/              ← Documentos clientes (fuera de webroot)
│   └── identities/           ← Fotos de identidad
└── app/
    ├── config/app.php        ← Lee todo de $_ENV
    ├── core/
    │   ├── Auth.php          ← Manejo de sesión y roles
    │   ├── Controller.php    ← Base: redirect(), render(), json(), flashRedirect()
    │   ├── CSRF.php          ← Tokens CSRF en todos los POST
    │   ├── DB.php            ← PDO wrapper: all(), row(), insert(), update(), delete()
    │   ├── Router.php        ← Registra rutas GET/POST con middleware
    │   ├── Validator.php     ← Validación de datos
    │   └── View.php          ← Renderizado con layouts, flash messages
    ├── models/               ← Client, Loan, User, Setting, Aval, ContractTemplate
    ├── controllers/          ← Auth, Dashboard, Client, Loan, Payment, User,
    │                           Report, Setting, Portal, Aval, ContractTemplate,
    │                           FiscalCai (NUEVO), InvoiceController (NUEVO)
    ├── services/
    │   ├── DocumentService.php
    │   └── LoanCalculator/   ← Interface + Tipo A, B, C
    └── views/                ← layouts/, auth/, clients/, loans/, payments/,
                                users/, reports/, settings/, portal/,
                                documents/, contract_templates/, errors/,
                                fiscal/ (NUEVO), invoices/ (NUEVO)
```

### Patrón URL — REGLA CRÍTICA

```php
// ✅ SIEMPRE usar url() helper — NUNCA hardcodear rutas
url('/payments/create')    // → http://dominio.com/public/payments/create
url('/loans/' . $id)

// ✅ SIEMPRE usar url() en redirects JS también
window.location.href = '<?= url('/payments/create') ?>?loan_id=' + val;

// ❌ NUNCA así — rompe en subdirectorios y en producción
window.location = '/payments/create';
header('Location: /loans/' . $id);
```

La función `url()` se define en `public/index.php`:

```php
function url(string $path = ''): string {
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}
```

`APP_URL` viene SOLO de `.env`. El default en `app/config/app.php` es solo para fallback local.

---

## 🗃️ ESQUEMA DE BASE DE DATOS

### Tablas principales

| Tabla                | Descripción                                                       |
| -------------------- | ----------------------------------------------------------------- |
| `users`              | Usuarios del sistema (superadmin, admin, asesor, cliente)         |
| `roles`              | 4 roles: superadmin, admin, asesor, cliente                       |
| `clients`            | Clientes con datos personales, referencias, imágenes de identidad |
| `avales`             | Avales/fiadores vinculados a clientes                             |
| `loans`              | Préstamos con tipo A/B/C, tasas, plazos, mora configurada         |
| `loan_installments`  | Cuotas de amortización generadas                                  |
| `payments`           | Pagos registrados contra préstamos                                |
| `payment_items`      | Desglose: capital, interés, mora por pago                         |
| `loan_events`        | Historial de eventos del préstamo                                 |
| `loan_guarantees`    | Garantías prendarias                                              |
| `client_documents`   | Documentos subidos (PDF/imágenes)                                 |
| `contract_templates` | Plantillas HTML editables con variables {{...}}                   |
| `settings`           | Configuración global clave-valor                                  |
| `audit_log`          | Log de auditoría                                                  |
| **`fiscal_cai`**     | **CAI registrados ante el SAR (NUEVO)**                           |
| **`invoices`**       | **Facturas emitidas vinculadas a pagos (NUEVO)**                  |
| **`invoice_items`**  | **Líneas de factura (NUEVO)**                                     |

### Columna `settings` clave → valores importantes

| setting_key             | Valor ejemplo                    | Descripción               |
| ----------------------- | -------------------------------- | ------------------------- |
| `app_name`              | `Pisto Facil`                    | Nombre del sistema        |
| `app_currency`          | `L`                              | Símbolo moneda            |
| `default_late_fee_rate` | `0.021`                          | Mora 2.1%/mes = 0.07%/día |
| `grace_days`            | `3`                              | Días gracia global        |
| `company_legal_name`    | `GRUPO VELMEZ, S. DE R.L.`       |                           |
| `company_rtn`           | `08019023527524`                 | RTN empresa               |
| `company_rep_name`      | `GARY ANTHONY VELASQUEZ CADENAS` |                           |
| `company_rep_identity`  | `0801199015117`                  |                           |

---

## 👥 ROLES Y PERMISOS

| Rol        | Slug         | Middleware   | Acceso                                        |
| ---------- | ------------ | ------------ | --------------------------------------------- |
| SuperAdmin | `superadmin` | `superadmin` | Todo + configuración fiscal + anular/eliminar |
| Admin      | `admin`      | `admin`      | CRUD completo excepto configuración global    |
| Asesor     | `asesor`     | `asesor`     | Cartera asignada + registrar pagos            |
| Cliente    | `cliente`    | `auth`       | Solo portal `/my-loans`                       |

El middleware en `Router.php`:

```php
'admin'      => Auth::requireRole(['superadmin', 'admin'])
'asesor'     => Auth::requireRole(['superadmin', 'admin', 'asesor'])
'superadmin' => Auth::requireRole('superadmin')
```

**Acciones destructivas** (eliminar, anular, editar condiciones financieras) requieren modal SweetAlert2 pidiendo **confirmación con usuario + contraseña + comentario** y solo superadmin/admin.

---

## 🧮 LÓGICA FINANCIERA

### Tipos de préstamo

| Tipo  | Calculadora                       | Lógica                                                                                               |
| ----- | --------------------------------- | ---------------------------------------------------------------------------------------------------- |
| **A** | `LevelPaymentCalculator`          | Cuota nivelada (francés). PMT fijo.                                                                  |
| **B** | `VariablePaymentCalculator`       | Capital fijo decreciente. Con plazo=cuotas decreci-entes. Sin plazo=cuota abierta, interés por días. |
| **C** | `MonthlySimpleInterestCalculator` | Solo interés por período. Capital se abona libre-mente. Si no paga, interés acumula por períodos.    |

### Fórmula de mora (igual en los 3 tipos)

```
Mora = saldo_vencido × (late_fee_rate / 30) × días_efectivos
días_efectivos = max(0, días_vencido - grace_days)
late_fee_rate almacenado como decimal mensual (0.021 = 2.1%/mes = 0.07%/día)
```

### Prioridad de pago (siempre)

```
1. Mora → 2. Interés corriente → 3. Capital
```

### Tasas recomendadas Honduras (contexto de negocio)

- Interés préstamo: 5% mensual (práctica del negocio)
- Mora: máx 2.1%/mes = 0.07%/día (legal)
- Banco referencia: 2.67%/mes normal, 2.1%/mes mora

---

## 🧾 MÓDULO FISCAL — LEYES HONDURAS SAR

### Marco legal aplicable

- **ISV (Impuesto Sobre Ventas)**: Decreto-Ley 24
    - Tasa general: **15%** sobre servicios gravados
    - Tasa selectiva: **18%** (alcohol, tabaco, bebidas carbonatadas, vuelos business)
    - Servicios financieros (intereses de préstamos): **EXENTOS** de ISV en Honduras
    - Los servicios de cobranza/administrativos: gravados al 15%
- **CAI (Código de Autorización de Impresión)**: Código único emitido por el SAR para cada talonario/rango de facturas. Tiene fecha límite de emisión.
- **Correlativo de factura**: Formato `000-001-10-XXXXXXXX` (punto de emisión - establecimiento - tipo - correlativo)
- **RTN Emisor**: RTN de la empresa que factura
- **BMT RTN**: RTN de la imprenta que imprimió los talonarios físicos
- **Rango autorizado**: Rango de números de factura autorizados por ese CAI

### Campos OBLIGATORIOS en factura Honduras (SAR)

```
1. Nombre/Razón Social del emisor
2. RTN del emisor
3. Dirección del emisor
4. Teléfono del emisor
5. CAI
6. Fecha límite de emisión del CAI
7. Número de factura (correlativo dentro del rango)
8. Rango autorizado (desde - hasta)
9. Nombre/Razón Social del cliente
10. RTN del cliente (si aplica)
11. Dirección del cliente
12. Fecha de emisión de la factura
13. Descripción del servicio/bien
14. Monto exento
15. Monto gravado
16. ISV (15% o 18%)
17. Total a pagar
18. Cantidad en letras
19. BMT RTN (imprenta)
20. No. Certificado (del sistema de facturación)
```

### Estructura tabla `fiscal_cai`

```sql
CREATE TABLE fiscal_cai (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cai_code        VARCHAR(50)  NOT NULL,          -- "347A96-CC1ADA-F962E0-63BE03-0909EB-40"
  emission_type   VARCHAR(50)  DEFAULT 'Factura', -- tipo de documento
  range_from      VARCHAR(30)  NOT NULL,           -- "000-001-01-00001351"
  range_to        VARCHAR(30)  NOT NULL,           -- "000-001-01-00001650"
  current_counter INT UNSIGNED DEFAULT 0,          -- correlativo actual
  limit_date      DATE         NOT NULL,            -- fecha límite emisión
  bmt_rtn         VARCHAR(20)  DEFAULT NULL,        -- RTN imprenta
  cert_number     VARCHAR(50)  DEFAULT NULL,        -- no. certificado
  is_active       TINYINT(1)   DEFAULT 1,
  notes           TEXT         DEFAULT NULL,
  created_by      INT UNSIGNED DEFAULT NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Estructura tabla `invoices`

```sql
CREATE TABLE invoices (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cai_id          INT UNSIGNED NOT NULL,
  payment_id      INT UNSIGNED DEFAULT NULL,       -- pago que origina esta factura
  loan_id         INT UNSIGNED NOT NULL,
  client_id       INT UNSIGNED NOT NULL,
  invoice_number  VARCHAR(30)  NOT NULL UNIQUE,    -- "000-001-01-00001401"
  invoice_date    DATE         NOT NULL,
  client_name     VARCHAR(200) NOT NULL,
  client_rtn      VARCHAR(30)  DEFAULT NULL,
  client_address  VARCHAR(500) DEFAULT NULL,
  -- Montos
  subtotal        DECIMAL(14,2) DEFAULT 0.00,
  exempt_amount   DECIMAL(14,2) DEFAULT 0.00,      -- exento ISV (intereses)
  taxable_15      DECIMAL(14,2) DEFAULT 0.00,      -- gravado 15%
  taxable_18      DECIMAL(14,2) DEFAULT 0.00,      -- gravado 18%
  isv_15          DECIMAL(14,2) DEFAULT 0.00,
  isv_18          DECIMAL(14,2) DEFAULT 0.00,
  total           DECIMAL(14,2) NOT NULL,
  -- Estado
  status          ENUM('active','voided') DEFAULT 'active',
  voided_by       INT UNSIGNED DEFAULT NULL,
  voided_at       DATETIME     DEFAULT NULL,
  void_reason     VARCHAR(500) DEFAULT NULL,
  notes           TEXT         DEFAULT NULL,
  created_by      INT UNSIGNED NOT NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 📋 RUTAS REGISTRADAS EN `public/index.php`

```php
// LOANS
$router->post('/loans/{id}/refinance', 'LoanController@refinance', ['auth','admin']); // NUEVO

// FISCAL / CAI (NUEVO — solo superadmin)
$router->get( '/fiscal',               'FiscalCaiController@index',   ['auth','superadmin']);
$router->get( '/fiscal/create',        'FiscalCaiController@create',  ['auth','superadmin']);
$router->post('/fiscal/store',         'FiscalCaiController@store',   ['auth','superadmin']);
$router->get( '/fiscal/{id}/edit',     'FiscalCaiController@edit',    ['auth','superadmin']);
$router->post('/fiscal/{id}/update',   'FiscalCaiController@update',  ['auth','superadmin']);
$router->get( '/fiscal/{id}/toggle',   'FiscalCaiController@toggle',  ['auth','superadmin']);

// FACTURAS (NUEVO)
$router->get( '/invoices',             'InvoiceController@index',     ['auth','admin']);
$router->get( '/invoices/create',      'InvoiceController@create',    ['auth','admin']);
$router->post('/invoices/store',       'InvoiceController@store',     ['auth','admin']);
$router->get( '/invoices/{id}',        'InvoiceController@show',      ['auth','asesor']);
$router->post('/invoices/{id}/void',   'InvoiceController@void',      ['auth','admin']);
$router->get( '/payments/{id}/invoice','InvoiceController@fromPayment',['auth','asesor']);
```

---

## 🔧 CONFIGURACIÓN SETTINGS — GRUPOS

| Grupo       | Claves                                                                                                                                                                                                         |
| ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `general`   | app_name, app_currency, app_currency_name, timezone, items_per_page                                                                                                                                            |
| `loans`     | default_late_fee_rate, grace_days, loan_number_prefix, payment_number_prefix, aval_required_amount                                                                                                             |
| `dashboard` | alert_days_upcoming, alert_days_warning                                                                                                                                                                        |
| `documents` | company_legal_name, company_rtn, company_address, company_city, company_phone, company_rep_name, company_rep_identity, company_rep_nationality, contract_page_size, contract_jurisdiction, pagare_jurisdiction |
| `reports`   | initial_capital                                                                                                                                                                                                |
| `fiscal`    | **invoice_number_prefix** (NUEVO), **default_isv_rate** (NUEVO, 0=exento)                                                                                                                                      |
| `general`   | bank_name_1..3, bank_account_1..3, bank_account_type_1..3, bank_account_holder_1..3                                                                                                                            |

---

## 🛡️ SEGURIDAD

- **CSRF**: Todos los formularios POST incluyen `<?= \App\Core\CSRF::field() ?>` y el controller llama `CSRF::check()`
- **XSS**: Todo output usa `htmlspecialchars()` o helper `e()`
- **SQL Injection**: Solo prepared statements via `DB::all()`, `DB::row()`, `DB::insert()`, `DB::update()`
- **Auth**: Cada ruta tiene middleware; controllers con `Auth::requireRole()`
- **Archivos**: Almacenados fuera de webroot en `storage/`, servidos via PHP con verificación de permisos
- **Acciones destructivas**: Modal SweetAlert2 pidiendo usuario + contraseña + motivo antes de ejecutar

---

## 🎨 PATRONES DE VISTA

### Layout principal

- `app/views/layouts/main.php` → sidebar con navegación por rol
- Flash messages via `View::flash('success'|'error'|'warning', 'msg')` mostrados con SweetAlert2
- SweetAlert2 disponible globalmente: `confirmDelete(url, msg)`, `confirmAction(url, title, msg, icon)`

### Navbar items (con rol check)

```
Dashboard | Clientes | Préstamos | Pagos | Reportes | Usuarios | Configuración | Plantillas
NUEVO: Información Fiscal (solo admin/superadmin, submenu de Configuración)
```

### Convenciones de código PHP en vistas

```php
// Auth check en vistas
$isAdmin = \App\Core\Auth::isAdmin();   // NO usar Auth:: sin namespace en vistas

// URLs — SIEMPRE url() helper
url('/loans/' . $id . '/edit')

// Escapado — SIEMPRE
htmlspecialchars($var)  o  e($var)  // e() disponible globalmente

// SweetAlert — botones destructivos
onclick="confirmDelete('<?= url('/clients/' . $id . '/delete') ?>', 'Mensaje')"
```

---

## 📊 MÓDULO DE FACTURACIÓN — COMPORTAMIENTO

### Flujo de una factura

```
Pago registrado → Botón "Ver Factura" (ojo 👁) →
  Si no tiene factura: InvoiceController@fromPayment → formulario crear
  Si tiene factura: InvoiceController@show → PDF visual
```

### Contenido de la factura PDF (Honduras SAR compliant)

```
ENCABEZADO:
  Logo empresa | Nombre empresa | RTN | Dirección | Teléfono
  CAI: XXXXXX-XXXXXX-...  | Fecha límite: DD/MM/AAAA
  No. FACTURA: 000-001-01-XXXXXXXX | Fecha: DD/MM/AAAA
  Rango autorizado: 000-001-01-XXXXXX AL 000-001-01-XXXXXX

CLIENTE:
  Nombre | RTN | Dirección | Teléfono

CUERPO (tabla):
  Descripción | Exenta | Gravada 15% | Gravada 18%
  ─────────────────────────────────────────────
  Saldo Anterior (capital préstamo): L X,XXX.XX  [EXENTO]
  Interés Corriente: L X,XXX.XX                  [EXENTO - servicios financieros]
  Interés Moratorio: L X,XXX.XX                  [EXENTO - servicios financieros]
  Total: L X,XXX.XX
  Abono a Capital: L X,XXX.XX
  Nuevo Saldo: L X,XXX.XX

PIE:
  Importe Exonerado: L 0.00
  Importe Exento: L X,XXX.XX
  Importe Gravado 15%: L 0.00
  ISV 15%: L 0.00
  Subtotal: L X,XXX.XX
  GRAN TOTAL: L X,XXX.XX
  Cantidad en letras: XXXX LEMPIRAS CON XX/100
  BMT RTN: XXXXXXXXX | No. Certificado: XXXX-XX-XXXXX-XX

NOTA: Los intereses de préstamos son EXENTOS de ISV según ley hondureña.
```

### Anulación/Eliminación de factura

```
Solo superadmin y admin pueden anular/editar/eliminar facturas.
Al intentar cualquier acción destructiva → Modal SweetAlert2 con:
  1. Campo: Usuario (email)
  2. Campo: Contraseña (verificada contra BD)
  3. Campo: Motivo/Comentario (obligatorio, mín 10 chars)
  4. Botón confirmar → POST con auth
La factura anulada NO se elimina de la BD, solo status='voided'.
```

---

## 🐛 PROBLEMAS CONOCIDOS Y SOLUCIONES

| Problema                          | Causa                                | Solución                                                |
| --------------------------------- | ------------------------------------ | ------------------------------------------------------- |
| `Undefined type 'Auth'` en vistas | Sin `use` statement                  | Usar `\App\Core\Auth::isAdmin()`                        |
| Mora = 0 en préstamos nuevos      | `default_late_fee_rate = 0.00` en BD | `UPDATE settings SET setting_value='0.021'`             |
| URLs rotas en subdirectorio       | Hardcoded paths                      | Siempre usar `url()` helper                             |
| Cuota queda "Partial" con mora    | Monto pre-relleno no incluía mora    | Payments/create ahora suma mora al monto sugerido       |
| APP_URL hardcodeada               | Fallback en config/app.php           | El fallback es solo para dev local; producción usa .env |

---

## 🚀 MÓDULOS PENDIENTES / PRÓXIMOS

| Módulo                    | Estado           | Descripción                  |
| ------------------------- | ---------------- | ---------------------------- |
| Fiscal CAI                | 🔄 En desarrollo | Gestión de CAI del SAR       |
| Facturas                  | 🔄 En desarrollo | Emisión de facturas por pago |
| Refinanciamiento          | ✅ Completo      | `LoanController@refinance`   |
| Edit préstamo completo    | ✅ Completo      | Con recálculo de cuotas      |
| Mora configurable         | ✅ Completo      | Fix en settings              |
| SweetAlert confirmaciones | ✅ Completo      | Clientes, préstamos, pagos   |
| Pago con mora desglosada  | ✅ Completo      | Todos los tipos A, B, C      |
| Notificaciones WhatsApp   | ⏳ Futuro        | Antes de vencimiento         |
| Multi-empresa             | ⏳ Futuro        | Multi-tenant                 |
| App PWA cobrador          | ⏳ Futuro        | Offline payments             |

---

## 💡 INSTRUCCIONES PARA IA

Cuando trabajes con este sistema:

1. **URLs**: SIEMPRE usar `url('/ruta')` — NUNCA rutas absolutas hardcodeadas
2. **Auth en vistas**: SIEMPRE `\App\Core\Auth::isAdmin()` con namespace completo
3. **Destructivo**: SIEMPRE SweetAlert2 antes de ejecutar acciones irreversibles
4. **Factura anulada**: NUNCA DELETE, solo UPDATE status='voided'
5. **ISV Honduras**: Intereses de préstamos = EXENTOS. Servicios admin = 15%
6. **CAI**: Antes de emitir factura verificar que el correlativo está dentro del rango y la fecha no está vencida
7. **Correlativo**: Auto-incrementar `fiscal_cai.current_counter` en cada factura emitida
8. **Mora en contratos**: `$lateFeeRate = late_fee_rate * 100` (% mensual), `$lateFeeDaily = late_fee_rate / 30 * 100` (% diario)
9. **CSRF**: Todo POST necesita `CSRF::check()` en el controller y `CSRF::field()` en el form
10. **Transacciones**: Operaciones multi-tabla SIEMPRE en `DB::beginTransaction()` / `DB::commit()` / `DB::rollback()`
