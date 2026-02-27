# SistemaPréstamos – Sistema de Gestión de Préstamos
**PHP 8.2+ | MySQL 8+ | Bootstrap 5 | Sin frameworks pesados**

---

## 🔐 Credenciales iniciales
| Email | Contraseña | Rol |
|---|---|---|
| superadmin@prestamos.hn | Admin@1234 | SuperAdmin |
| admin@prestamos.hn | Admin@1234 | Admin |
| asesor@prestamos.hn | Admin@1234 | Asesor |

---

## 📦 Instalación paso a paso

### 1. Requisitos
- PHP 8.2+ con extensiones: `pdo_mysql`, `fileinfo`, `mbstring`, `json`
- MySQL 8+ o MariaDB 10.6+
- Apache 2.4+ con `mod_rewrite` habilitado, o Nginx

### 2. Clonar / descomprimir el proyecto
```bash
# En /var/www/html/ (Apache) o tu directorio web
cp -r loanapp/ /var/www/html/loanapp
```

### 3. Configurar base de datos
```sql
CREATE DATABASE prestamos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'prestamos'@'localhost' IDENTIFIED BY 'tu_password_aqui';
GRANT ALL ON prestamos_db.* TO 'prestamos'@'localhost';
FLUSH PRIVILEGES;
```

```bash
# Importar esquema y datos iniciales
mysql -u prestamos -p prestamos_db < database/schema.sql
mysql -u prestamos -p prestamos_db < database/seed.sql
```

### 4. Configurar .env
```bash
cp .env.example .env
nano .env
```
Editar los valores:
```
APP_URL=http://tudominio.com/loanapp/public
APP_KEY=genera_una_clave_aleatoria_de_32_chars
DB_HOST=localhost
DB_NAME=prestamos_db
DB_USER=prestamos
DB_PASS=tu_password_aqui
```

### 5. Permisos
```bash
chmod -R 755 /var/www/html/loanapp
chmod -R 775 /var/www/html/loanapp/storage
chown -R www-data:www-data /var/www/html/loanapp
```

### 6. Apache – Virtual Host
```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/html/loanapp/public

    <Directory /var/www/html/loanapp/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/prestamos-error.log
    CustomLog ${APACHE_LOG_DIR}/prestamos-access.log combined
</VirtualHost>
```
```bash
a2enmod rewrite
a2ensite prestamos
systemctl reload apache2
```

### 7. Nginx (alternativa)
```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/html/loanapp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Proteger storage
    location ~ ^/\.\.\/storage { deny all; }
}
```

---

## 📁 Estructura del Proyecto

```
loanapp/
├── .env.example              # Plantilla de configuración
├── .env                      # TU configuración (no commitear)
├── database/
│   ├── schema.sql            # Tablas, índices, constraints
│   └── seed.sql              # Datos iniciales (roles, usuarios, settings)
├── storage/
│   └── uploads/              # Documentos de clientes (protegido)
├── public/                   # Document root (único directorio expuesto)
│   ├── index.php             # Front controller
│   └── .htaccess
└── app/
    ├── config/               # database.php, app.php
    ├── core/                 # Auth, CSRF, DB, Router, Validator, View, Controller
    ├── models/               # Client, Loan, User, Setting
    ├── controllers/          # Auth, Dashboard, Client, Loan, Payment, User, Report, Setting, Portal
    ├── services/
    │   ├── DocumentService.php
    │   └── LoanCalculator/   # Interface + Tipo A (Nivelado), B (Variable), C (Simple)
    └── views/                # Layouts, auth, dashboard, clients, loans, payments, users, reports, settings, portal
```

---

## 🧮 Tipos de Préstamos

| Tipo | Nombre | Calculadora |
|---|---|---|
| A | Cuota Nivelada (French) | `LevelPaymentCalculator` |
| B | Variable / Abonos por días | `VariablePaymentCalculator` |
| C | Interés Simple Mensual | `MonthlySimpleInterestCalculator` |

Para agregar un tipo nuevo: implementar `LoanCalculatorInterface` y registrar en `CalculatorFactory`.

---

## 👥 Roles y Permisos

| Rol | Slug | Acceso |
|---|---|---|
| SuperAdmin | superadmin | Todo + configuración global |
| Admin | admin | CRUD completo excepto superadmin |
| Asesor/Cobrador | asesor | Cartera asignada + registrar pagos |
| Cliente | cliente | Solo ver sus propios préstamos |

---

## ✅ Checklist de Pruebas

### Autenticación
- [ ] Login con credenciales correctas → redirige a dashboard
- [ ] Login con credenciales incorrectas → mensaje de error
- [ ] Logout limpia la sesión
- [ ] CSRF token inválido retorna error

### Dashboard
- [ ] Métricas muestran datos reales de la DB
- [ ] Tabla de cuotas vencidas muestra días de mora
- [ ] Tabla por vencer respeta configuración de días
- [ ] Gráfico de estado de préstamos se renderiza

### Clientes
- [ ] Crear cliente con datos válidos
- [ ] Validación impide guardar sin campos requeridos
- [ ] Subir PDF → se guarda en storage/uploads/{client_id}/
- [ ] Subir archivo no permitido → error descriptivo
- [ ] Descargar documento → descarga correctamente
- [ ] Eliminar documento → desaparece de la lista

### Préstamos
- [ ] Crear Tipo A: tabla de amortización se genera correctamente
- [ ] Crear Tipo B: cuota única open se crea
- [ ] Crear Tipo C: cuotas de solo interés
- [ ] Vista de amortización muestra todos los cálculos
- [ ] Filtros por estado y tipo funcionan

### Pagos
- [ ] Registrar pago completo → cuota marca 'paid'
- [ ] Pago parcial → cuota marca 'partial'
- [ ] Pago excede cuota → exceso va a capital (Tipo C)
- [ ] Mora se calcula y guarda correctamente
- [ ] Anular pago → revierte saldo del préstamo
- [ ] Comprobante de pago muestra desglose

### Usuarios
- [ ] Crear usuario con contraseña fuerte → ok
- [ ] Contraseña débil → error descriptivo
- [ ] Asesor no puede crear préstamos
- [ ] Cliente solo ve portal de mis préstamos

### Reportes
- [ ] Reporte general filtra por fecha / estado / tipo / asesor
- [ ] Exportar CSV → descarga correctamente
- [ ] Reporte por cliente muestra historial completo
- [ ] Proyección calcula correctamente con interés compuesto

### Seguridad
- [ ] Rutas de admin inaccesibles para asesor/cliente
- [ ] Storage/uploads/ devuelve 403 directo
- [ ] SQL injection bloqueado (prepared statements)
- [ ] XSS bloqueado (htmlspecialchars en vistas)

---

## 🔒 Supuestos de Negocio

1. **Mora**: Se calcula solo sobre el saldo/cuota pendiente, no sobre el monto original.
2. **Días de gracia**: Configurable globalmente (default: 3 días).
3. **Tipo C sin fecha final fija**: Las cuotas se proyectan pero la real es variable según pagos.
4. **Tipo B**: Un solo registro de cuota; el interés se calcula por días transcurridos al momento del pago.
5. **Pagos**: Prioridad: mora → interés → capital.
6. **Préstamo pagado**: Se marca automáticamente cuando balance ≤ 0.01.
7. **Documentos**: Se almacenan fuera del webroot en `storage/uploads/`. Protegidos con `.htaccess`.

---

## 🚀 Actualizaciones futuras sugeridas

- Notificaciones por WhatsApp/SMS (días antes del vencimiento)
- Firma digital de documentos
- App móvil con PWA
- Restructuración de préstamos (módulo)
- Multi-empresa / multi-sucursal
- Backup automático a S3


loanapp/
├── .env.example                          ✅
├── database/
│   ├── schema.sql                        ✅
│   └── seed.sql                          ✅
├── public/
│   ├── index.php                         ✅
│   └── .htaccess                         ✅
├── storage/uploads/.htaccess             ✅
└── app/
    ├── config/
    │   ├── app.php                       ✅
    │   └── database.php                  ✅
    ├── core/
    │   ├── Auth.php                      ✅
    │   ├── Controller.php                ✅
    │   ├── CSRF.php                      ✅
    │   ├── DB.php                        ✅
    │   ├── Router.php                    ✅
    │   ├── Validator.php                 ✅
    │   └── View.php                      ✅
    ├── models/
    │   ├── Client.php                    ✅
    │   ├── Loan.php                      ✅
    │   ├── Setting.php                   ✅
    │   └── User.php                      ✅
    ├── services/
    │   ├── DocumentService.php           ✅
    │   └── LoanCalculator/
    │       ├── CalculatorFactory.php     ✅
    │       ├── LevelPaymentCalculator.php ✅
    │       ├── LoanCalculatorInterface.php ✅
    │       ├── MonthlySimpleInterestCalculator.php ✅
    │       └── VariablePaymentCalculator.php ✅
    ├── controllers/
    │   ├── AuthController.php            ✅
    │   ├── ClientController.php          ✅
    │   ├── DashboardController.php       ✅
    │   ├── LoanController.php            ✅
    │   ├── PaymentController.php         ❌ FALTA
    │   ├── UserController.php            ❌ FALTA
    │   ├── ReportController.php          ❌ FALTA
    │   ├── SettingController.php         ❌ FALTA
    │   └── PortalController.php          ❌ FALTA
    └── views/
        ├── layouts/
        │   ├── main.php                  ✅
        │   └── auth.php                  ✅
        ├── auth/login.php                ✅
        ├── dashboard/index.php           ✅
        ├── clients/
        │   ├── index.php                 ✅
        │   ├── form.php                  ✅
        │   └── show.php                  ✅
        ├── loans/
        │   ├── create.php                ✅
        │   ├── show.php                  ✅
        │   ├── index.php                 ❌ VACÍO
        │   ├── edit.php                  ❌ FALTA
        │   └── amortization.php          ❌ FALTA
        ├── payments/
        │   ├── index.php                 ❌ FALTA
        │   ├── create.php                ❌ FALTA
        │   └── show.php                  ❌ FALTA
        ├── users/
        │   ├── index.php                 ❌ FALTA
        │   └── form.php                  ❌ FALTA
        ├── reports/
        │   ├── general.php               ❌ FALTA
        │   ├── client.php                ❌ FALTA
        │   └── projection.php            ❌ FALTA
        ├── settings/index.php            ❌ FALTA
        ├── portal/index.php              ❌ FALTA
        └── errors/
            ├── 403.php                   ❌ FALTA
            ├── 404.php                   ❌ FALTA
            └── 500.php                   ❌ FALTA