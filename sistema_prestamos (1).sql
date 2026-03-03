-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 03-03-2026 a las 19:41:47
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_prestamos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(50) NOT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `entity`, `entity_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'create', 'clients', 4, NULL, '{\"first_name\":\"Danny Sino\\u00e9\",\"last_name\":\"Vel\\u00e1squez Cadenas\"}', '::1', NULL, '2026-03-01 20:34:55'),
(2, 1, 'update', 'clients', 4, NULL, NULL, '::1', NULL, '2026-03-01 20:39:59'),
(3, 1, 'update', 'clients', 4, NULL, NULL, '::1', NULL, '2026-03-01 20:41:57'),
(4, 1, 'update', 'clients', 4, NULL, NULL, '::1', NULL, '2026-03-01 20:42:35'),
(5, 1, 'create', 'loans', 1, NULL, NULL, '::1', NULL, '2026-03-01 20:43:06'),
(6, 1, 'create', 'loans', 2, NULL, NULL, '::1', NULL, '2026-03-01 21:17:28'),
(7, 1, 'create', 'loans', 3, NULL, NULL, '::1', NULL, '2026-03-01 22:01:45'),
(8, 1, 'create', 'loans', 4, NULL, NULL, '::1', NULL, '2026-03-02 16:17:31'),
(9, 1, 'create', 'loans', 5, NULL, NULL, '::1', NULL, '2026-03-02 20:52:11'),
(10, 1, 'create', 'loans', 6, NULL, NULL, '::1', NULL, '2026-03-02 21:47:20'),
(11, 1, 'create', 'loans', 7, NULL, NULL, '::1', NULL, '2026-03-02 22:01:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avales`
--

CREATE TABLE `avales` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Cliente al que pertenece este aval',
  `aval_client_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Si el aval es otro cliente del sistema',
  `full_name` varchar(200) NOT NULL,
  `identity_number` varchar(30) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Hondureña',
  `relationship` varchar(100) DEFAULT NULL COMMENT 'Relación con el deudor',
  `identity_front_path` varchar(500) DEFAULT NULL,
  `identity_back_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `avales`
--

INSERT INTO `avales` (`id`, `client_id`, `aval_client_id`, `full_name`, `identity_number`, `phone`, `phone2`, `address`, `city`, `occupation`, `nationality`, `relationship`, `identity_front_path`, `identity_back_path`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 'MARIA BAIRES MONDRAGON', '0318199000543', '+50433758070', '', 'Barrio Macaruya, Siguatepeque', 'Siguatepequie', 'Ing. Agronoma', 'Hondureña', 'Esposa', 'storage/identities/avales/aval_identity_front_69a4f91baf8b86.78523095.png', 'storage/identities/avales/aval_identity_back_69a4f91bb57ba8.94555796.png', '', '2026-03-02 02:34:55', '2026-03-02 02:42:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Linked user account if client role',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL COMMENT 'Asesor/Cobrador asignado',
  `code` varchar(20) NOT NULL COMMENT 'Código interno',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `identity_number` varchar(30) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `work_phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Hondureña',
  `profession` varchar(100) DEFAULT NULL,
  `marital_status` enum('soltero','casado','divorciado','viudo') NOT NULL DEFAULT 'soltero',
  `spouse_name` varchar(200) DEFAULT NULL,
  `spouse_phone` varchar(20) DEFAULT NULL,
  `spouse_identity` varchar(30) DEFAULT NULL,
  `ref_personal_name` varchar(150) DEFAULT NULL,
  `ref_personal_phone` varchar(20) DEFAULT NULL,
  `ref_personal_rel` varchar(80) DEFAULT NULL,
  `ref_labor_name` varchar(150) DEFAULT NULL,
  `ref_labor_phone` varchar(20) DEFAULT NULL,
  `ref_labor_company` varchar(150) DEFAULT NULL,
  `identity_front_path` varchar(500) DEFAULT NULL,
  `identity_back_path` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `monthly_income` decimal(12,2) DEFAULT NULL,
  `reference_name` varchar(150) DEFAULT NULL,
  `reference_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `assigned_to`, `code`, `first_name`, `last_name`, `identity_number`, `email`, `phone`, `phone2`, `work_phone`, `nationality`, `profession`, `marital_status`, `spouse_name`, `spouse_phone`, `spouse_identity`, `ref_personal_name`, `ref_personal_phone`, `ref_personal_rel`, `ref_labor_name`, `ref_labor_phone`, `ref_labor_company`, `identity_front_path`, `identity_back_path`, `address`, `city`, `occupation`, `monthly_income`, `reference_name`, `reference_phone`, `notes`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'CLI-00001', 'Juan', 'Pérez López', '0801-1990-12345', 'juan@demo.hn', '+504 9811-1111', NULL, NULL, 'Hondureña', NULL, 'soltero', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Col. Kennedy, Casa #5', 'Tegucigalpa', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08'),
(2, NULL, NULL, 'CLI-00002', 'María', 'García Sánchez', '0801-1985-67890', 'maria@demo.hn', '+504 9822-2222', NULL, NULL, 'Hondureña', NULL, 'soltero', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Bo. El Centro', 'San Pedro Sula', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08'),
(3, NULL, NULL, 'CLI-00003', 'Carlos', 'Martínez Ruiz', '0801-1978-11122', NULL, '+504 9833-3333', NULL, NULL, 'Hondureña', NULL, 'soltero', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Col. Palmira', 'Tegucigalpa', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08'),
(4, NULL, 1, 'CLI-00004', 'Danny Sinoé', 'Velásquez Cadenas', '0801198907280', 'sinoeproducciones@gmail.com', '+50431828143', '', '', 'Hondureña', 'Ing. En Sistemas', 'casado', 'María José Bares', '+50433758070', '0318199000543', 'Gary Velasquez', '+50431828143', 'Hermano', '', '', 'Naranja y Media Honduras', 'storage/identities/clients/identity_front_69a4f91ba36d66.84972571.png', 'storage/identities/clients/identity_back_69a4f91baa97f4.17748155.png', 'Barrio Macaruya', 'Siguatepeque', 'Ing. En Sistemas', 24000.00, 'Gary Velasquez', '+50431828143', '', 1, 1, '2026-03-01 20:34:55', '2026-03-01 20:42:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `client_documents`
--

CREATE TABLE `client_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `doc_type` enum('letra_cambio','pagare','identidad','contrato','evidencia','otro') NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL COMMENT 'Bytes',
  `file_hash` varchar(64) NOT NULL COMMENT 'SHA-256',
  `description` varchar(255) DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contract_templates`
--

CREATE TABLE `contract_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `template_type` enum('contrato','pagare','otro') NOT NULL DEFAULT 'contrato',
  `name` varchar(150) NOT NULL COMMENT 'Nombre descriptivo de la plantilla',
  `content` longtext NOT NULL COMMENT 'HTML con variables {{nombre}}, {{monto}}, etc.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contract_templates`
--

INSERT INTO `contract_templates` (`id`, `template_type`, `name`, `content`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'contrato', 'Contrato de Préstamo – Por defecto', '<h1 style=\"text-align:center;\">CONTRATO DE PRÉSTAMO</h1>\r\n<h2 style=\"text-align:center;\">{{empresa_nombre}}</h2>\r\n<p style=\"text-align:center;\">No. Préstamo: <strong>{{prestamo_numero}}</strong> &nbsp;|&nbsp; Fecha: <strong>{{fecha_hoy}}</strong> &nbsp;|&nbsp; RTN: <strong>{{empresa_rtn}}</strong></p>\r\n\r\n<h3>I. PARTES DEL CONTRATO</h3>\r\n<p><strong>PARTE PRESTAMISTA (EL ACREEDOR):</strong> {{empresa_nombre}}, con RTN {{empresa_rtn}}, con domicilio en {{empresa_direccion}}, representada por <strong>{{rep_nombre}}</strong>, Identidad No. {{rep_identidad}} (en adelante <em>\"EL PRESTAMISTA\"</em>).</p>\r\n<p><strong>PARTE PRESTATARIA (EL DEUDOR):</strong> <strong>{{cliente_nombre}}</strong>, mayor de edad, estado civil: {{cliente_estado_civil}}, profesión u oficio: {{cliente_profesion}}, nacionalidad: {{cliente_nacionalidad}}, Tarjeta de Identidad No. <strong>{{cliente_identidad}}</strong>, domicilio: {{cliente_direccion}}, celular: {{cliente_celular}} (en adelante <em>\"EL DEUDOR\"</em>).</p>\r\n\r\n<h3>II. OBJETO</h3>\r\n<p>EL PRESTAMISTA otorga a EL DEUDOR la cantidad de <strong>{{monto_letras}} LEMPIRAS EXACTOS ({{moneda}} {{monto}})</strong>, bajo la modalidad <strong>{{tipo_prestamo}}</strong>.</p>\r\n\r\n<h3>III. CONDICIONES FINANCIERAS</h3>\r\n<table border=\"1\" cellpadding=\"6\" style=\"width:100%;border-collapse:collapse;\">\r\n  <tr><td><strong>Monto del Préstamo</strong></td><td>{{moneda}} {{monto}}</td></tr>\r\n  <tr><td><strong>Tasa de Interés</strong></td><td>{{tasa_interes}}% mensual</td></tr>\r\n  <tr><td><strong>Tasa por Mora</strong></td><td>{{tasa_mora}}% mensual</td></tr>\r\n  <tr><td><strong>Plazo</strong></td><td>{{plazo}} cuotas</td></tr>\r\n  <tr><td><strong>Frecuencia</strong></td><td>{{frecuencia}}</td></tr>\r\n  <tr><td><strong>Desembolso</strong></td><td>{{fecha_desembolso}}</td></tr>\r\n  <tr><td><strong>Primer Pago</strong></td><td>{{fecha_primer_pago}}</td></tr>\r\n  <tr><td><strong>Vencimiento</strong></td><td>{{fecha_vencimiento}}</td></tr>\r\n  <tr><td><strong>Lugar de Pago</strong></td><td>{{lugar_pago}}</td></tr>\r\n  <tr><td><strong>Forma de Pago</strong></td><td>{{forma_pago}}</td></tr>\r\n</table>\r\n\r\n<h3>IV. OBLIGACIONES DEL DEUDOR</h3>\r\n<p>EL DEUDOR se obliga a: (a) cancelar las cuotas en las fechas pactadas; (b) notificar cambios de domicilio; (c) mantener la garantía vigente; (d) no contraer nuevas deudas sin autorización escrita.</p>\r\n\r\n<h3>V. MORA E INTERESES MORATORIOS</h3>\r\n<p>El incumplimiento generará un interés moratorio del <strong>{{tasa_mora}}%</strong> mensual sobre el saldo pendiente desde el primer día de atraso. Período de gracia: <strong>{{dias_gracia}}</strong> días.</p>\r\n\r\n<h3>VI. JURISDICCIÓN</h3>\r\n<p>Las partes se someten a la jurisdicción del <strong>{{jurisdiccion}}</strong> en la ciudad de <strong>{{ciudad_firma}}</strong>.</p>\r\n\r\n<p style=\"margin-top:30px;\">Firmado en {{ciudad_firma}}, a los {{dia}} días del mes de {{mes}} del año {{anio}}.</p>', 1, 1, '2026-03-02 02:08:44', '2026-03-02 02:08:44'),
(2, 'pagare', 'Pagaré – Por defecto', '<h1 style=\"text-align:center;\">PAGARÉ</h1>\n<p style=\"text-align:center;font-size:18pt;font-weight:bold;border:2px solid #000;display:inline-block;padding:8px 20px;\">POR {{moneda}} {{monto}}</p>\n\n<p>Yo, <strong>{{cliente_nombre}}</strong>, mayor de edad, estado civil: <strong>{{cliente_estado_civil}}</strong>, profesión u oficio: <strong>{{cliente_profesion}}</strong>, nacionalidad: <strong>{{cliente_nacionalidad}}</strong>, Tarjeta de Identidad: <strong>{{cliente_identidad}}</strong>, con domicilio en: <strong>{{cliente_direccion}}</strong>, celular: <strong>{{cliente_celular}}</strong>.</p>\n\n<p>Por el presente <strong>PAGARÉ</strong>, <strong>HAGO CONSTAR</strong> que <strong>DEBO Y CANCELARÉ</strong> sin requerimiento legal a: <strong>{{empresa_nombre}}</strong>, representada por <strong>{{rep_nombre}}</strong>, Identidad No. <strong>{{rep_identidad}}</strong>; por la cantidad de <strong>{{monto_letras}} LEMPIRAS EXACTOS ({{moneda}} {{monto}})</strong>, a pagar el día <strong>{{fecha_vencimiento}}</strong>.</p>\n\n<ol>\n  <li><strong>Lugar de pago:</strong> {{lugar_pago}} ({{forma_pago}}).</li>\n  <li><strong>Interés moratorio:</strong> {{tasa_mora}}% mensual sobre saldo.</li>\n  <li><strong>Gastos de cobro:</strong> asumo todos los gastos judiciales y honorarios.</li>\n  <li><strong>Domicilio notificaciones:</strong> {{cliente_direccion}} ({{cliente_email}}).</li>\n</ol>\n\n<p>Firmado en {{ciudad_firma}}, a los {{dia}} días del mes de {{mes}} del año {{anio}}.</p>', 1, 1, '2026-03-02 02:08:44', '2026-03-02 03:19:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loans`
--

CREATE TABLE `loans` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `aval_id` int(10) UNSIGNED DEFAULT NULL,
  `has_guarantee` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `loan_number` varchar(20) NOT NULL,
  `loan_type` enum('A','B','C') NOT NULL COMMENT 'A=Nivelada, B=Variable/Abonos, C=Simple Mensual',
  `principal` decimal(14,2) NOT NULL COMMENT 'Monto desembolsado',
  `interest_rate` decimal(7,4) NOT NULL COMMENT 'Tasa (mensual si type C, anual si A)',
  `rate_type` enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `term_months` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Plazo en meses (requerido para tipo A)',
  `payment_frequency` enum('weekly','biweekly','monthly','bimonthly','quarterly','semiannual','annual') NOT NULL DEFAULT 'monthly',
  `late_fee_rate` decimal(7,4) NOT NULL DEFAULT 0.0500 COMMENT 'Tasa moratoria mensual',
  `grace_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Días de gracia antes de mora',
  `disbursement_date` date NOT NULL,
  `first_payment_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL COMMENT 'Fecha real último pago',
  `maturity_date` date DEFAULT NULL,
  `status` enum('active','paid','defaulted','cancelled','restructured','deleted') NOT NULL,
  `balance` decimal(14,2) NOT NULL COMMENT 'Saldo capital actual',
  `total_interest_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_late_fees_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `apply_payment_to` enum('capital','interest_first') NOT NULL DEFAULT 'interest_first',
  `notes` text DEFAULT NULL,
  `payment_method_cash` tinyint(1) DEFAULT 1,
  `payment_method_transfer` tinyint(1) DEFAULT 0,
  `payment_method_check` tinyint(1) DEFAULT 0,
  `payment_method_atm` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash' COMMENT 'cash | transfer | deposit',
  `payment_location` varchar(255) DEFAULT NULL COMMENT 'Dirección u datos de cuenta bancaria para el pago',
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `loans`
--

INSERT INTO `loans` (`id`, `client_id`, `aval_id`, `has_guarantee`, `assigned_to`, `created_by`, `loan_number`, `loan_type`, `principal`, `interest_rate`, `rate_type`, `term_months`, `payment_frequency`, `late_fee_rate`, `grace_days`, `disbursement_date`, `first_payment_date`, `last_payment_date`, `maturity_date`, `status`, `balance`, `total_interest_paid`, `total_late_fees_paid`, `total_paid`, `apply_payment_to`, `notes`, `payment_method_cash`, `payment_method_transfer`, `payment_method_check`, `payment_method_atm`, `created_at`, `updated_at`, `payment_method`, `payment_location`, `deleted_at`) VALUES
(7, 4, NULL, 0, 1, 1, 'PRES-000001', 'A', 5000.00, 0.1500, 'monthly', 3, 'monthly', 0.0000, 3, '2026-03-02', '2026-04-02', NULL, '2026-06-02', 'active', 5000.00, 0.00, 0.00, 0.00, 'interest_first', NULL, 0, 1, 0, 0, '2026-03-02 22:01:50', '2026-03-02 22:01:50', 'cash', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loan_events`
--

CREATE TABLE `loan_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_type` enum('created','updated','cancelled','deleted','payment','status_change') NOT NULL,
  `description` text NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `loan_events`
--

INSERT INTO `loan_events` (`id`, `loan_id`, `user_id`, `event_type`, `description`, `meta`, `created_at`) VALUES
(13, 7, 1, 'created', 'Préstamo creado. Monto: 5000 · Tipo: A', '{\"installments\":[{\"installment_number\":1,\"due_date\":\"2026-04-02\",\"principal_amount\":1439.8800000000001091393642127513885498046875,\"interest_amount\":750,\"total_amount\":2189.8800000000001091393642127513885498046875,\"balance_after\":3560.1199999999998908606357872486114501953125,\"paid_amount\":0,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"late_fee\":0,\"days_late\":0,\"status\":\"pending\"},{\"installment_number\":2,\"due_date\":\"2026-05-02\",\"principal_amount\":1655.859999999999899955582804977893829345703125,\"interest_amount\":534.01999999999998181010596454143524169921875,\"total_amount\":2189.8800000000001091393642127513885498046875,\"balance_after\":1904.259999999999990905052982270717620849609375,\"paid_amount\":0,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"late_fee\":0,\"days_late\":0,\"status\":\"pending\"},{\"installment_number\":3,\"due_date\":\"2026-06-02\",\"principal_amount\":1904.259999999999990905052982270717620849609375,\"interest_amount\":285.6399999999999863575794734060764312744140625,\"total_amount\":2189.90000000000009094947017729282379150390625,\"balance_after\":0,\"paid_amount\":0,\"paid_principal\":0,\"paid_interest\":0,\"paid_late_fee\":0,\"late_fee\":0,\"days_late\":0,\"status\":\"pending\"}],\"monthly_payment\":2189.8800000000001091393642127513885498046875,\"total_interest\":1569.660000000000081854523159563541412353515625,\"total_payment\":6569.65999999999985448084771633148193359375,\"monthly_rate\":0.1499999999999999944488848768742172978818416595458984375,\"period_rate\":0.1499999999999999944488848768742172978818416595458984375,\"annual_rate\":1.79999999999999982236431605997495353221893310546875,\"frequency\":\"monthly\"}', '2026-03-02 22:01:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loan_guarantees`
--

CREATE TABLE `loan_guarantees` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `guarantee_type` enum('vehiculo','articulo','inmueble','otro') NOT NULL DEFAULT 'articulo',
  `description` text NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `plate` varchar(20) DEFAULT NULL,
  `serial` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loan_installments`
--

CREATE TABLE `loan_installments` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `installment_number` tinyint(3) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `interest_amount` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_principal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_interest` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_late_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(12,2) NOT NULL,
  `status` enum('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `late_fee` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Mora calculada',
  `days_late` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `loan_installments`
--

INSERT INTO `loan_installments` (`id`, `loan_id`, `installment_number`, `due_date`, `principal_amount`, `interest_amount`, `total_amount`, `paid_amount`, `paid_principal`, `paid_interest`, `paid_late_fee`, `balance_after`, `status`, `paid_date`, `late_fee`, `days_late`, `created_at`, `updated_at`) VALUES
(13, 7, 1, '2026-04-02', 1439.88, 750.00, 2189.88, 0.00, 0.00, 0.00, 0.00, 3560.12, 'pending', NULL, 0.00, 0, '2026-03-02 22:01:50', '2026-03-02 22:01:50'),
(14, 7, 2, '2026-05-02', 1655.86, 534.02, 2189.88, 0.00, 0.00, 0.00, 0.00, 1904.26, 'pending', NULL, 0.00, 0, '2026-03-02 22:01:50', '2026-03-02 22:01:50'),
(15, 7, 3, '2026-06-02', 1904.26, 285.64, 2189.90, 0.00, 0.00, 0.00, 0.00, 0.00, 'pending', NULL, 0.00, 0, '2026-03-02 22:01:50', '2026-03-02 22:01:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `payment_number` varchar(20) NOT NULL,
  `payment_date` date NOT NULL,
  `total_received` decimal(12,2) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_method` enum('cash','transfer','check','other') NOT NULL DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `registered_by` int(10) UNSIGNED NOT NULL,
  `voided` tinyint(1) NOT NULL DEFAULT 0,
  `voided_by` int(10) UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payment_items`
--

CREATE TABLE `payment_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_id` int(10) UNSIGNED NOT NULL,
  `installment_id` int(10) UNSIGNED DEFAULT NULL,
  `item_type` enum('capital','interest','late_fee','other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'SuperAdmin', 'superadmin', 'Acceso total al sistema y configuración'),
(2, 'Admin', 'admin', 'Gestión de clientes, préstamos y usuarios'),
(3, 'Asesor', 'asesor', 'Cartera asignada, registro de pagos'),
(4, 'Cliente', 'cliente', 'Solo ver sus préstamos y documentos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `group`, `updated_by`, `updated_at`) VALUES
(1, 'app_name', 'Pisto Facil', 'string', 'Nombre del sistema', 'general', 1, '2026-03-01 14:14:39'),
(2, 'app_currency', 'L', 'string', 'Símbolo de moneda', 'general', 1, '2026-03-01 13:44:17'),
(3, 'app_currency_name', 'Lempira', 'string', 'Nombre de la moneda', 'general', 1, '2026-03-01 13:44:17'),
(4, 'default_late_fee_rate', '0.00', 'decimal', 'Tasa moratoria mensual por defecto (5%)', 'loans', 1, '2026-03-01 13:44:17'),
(5, 'grace_days', '3', 'integer', 'Días de gracia antes de aplicar mora', 'loans', 1, '2026-03-01 13:44:17'),
(6, 'alert_days_upcoming', '3', 'integer', 'Días para alerta \"por vencer\"', 'dashboard', 1, '2026-03-01 13:44:17'),
(7, 'alert_days_warning', '7', 'integer', 'Días aviso previo', 'dashboard', 1, '2026-03-01 13:44:17'),
(8, 'initial_capital', '200000.00', 'decimal', 'Capital inicial para proyección (L)', 'reports', 1, '2026-03-01 13:44:17'),
(9, 'max_upload_size_mb', '10', 'integer', 'Tamaño máximo de archivo en MB', 'documents', 1, '2026-03-01 13:44:17'),
(10, 'allowed_mime_types', '[\"application/pdf\",\"image/jpeg\",\"image/png\",\"image/webp\"]', 'json', 'MIME permitidos', 'documents', 1, '2026-03-01 13:44:17'),
(11, 'loan_number_prefix', 'PRES-', 'string', 'Prefijo para número de préstamo', 'loans', 1, '2026-03-01 13:44:17'),
(12, 'client_number_prefix', 'CLI-', 'string', 'Prefijo para código de cliente', 'clients', NULL, '2026-02-26 20:30:08'),
(13, 'payment_number_prefix', 'PAG-', 'string', 'Prefijo para número de pago', 'loans', 1, '2026-03-01 13:44:17'),
(14, 'items_per_page', '10', 'integer', 'Registros por página en tablas', 'general', 1, '2026-03-01 13:44:17'),
(15, 'timezone', 'America/Tegucigalpa', 'string', 'Zona horaria del servidor', 'general', 1, '2026-03-01 13:44:17'),
(44, 'company_legal_name', 'GRUPO VELMEZ, S. DE R.L.', 'string', 'Nombre Legal de la Empresa', 'documents', 1, '2026-03-01 15:32:16'),
(45, 'company_rtn', '‭08019023527524‬', 'string', 'RTN de la Empresa', 'documents', 1, '2026-03-01 15:32:16'),
(46, 'company_address', 'Barrio El Centro', 'string', 'Domicilio Legal de la Empresa', 'documents', 1, '2026-03-01 15:32:16'),
(47, 'company_city', 'Siguatepeque', 'string', 'Ciudad / Municipio', 'documents', 1, '2026-03-01 15:32:16'),
(48, 'company_phone', '+50422434972', 'string', 'Teléfono de la Empresa', 'documents', 1, '2026-03-01 21:46:51'),
(49, 'company_rep_name', 'GARY ANTHONY VELASQUEZ CADENAS', 'string', 'Nombre del Representante Legal / Apoderado', 'documents', 1, '2026-03-01 15:32:16'),
(50, 'company_rep_identity', '0801199015117', 'string', 'No. Identidad del Representante', 'documents', 1, '2026-03-01 15:32:16'),
(51, 'company_rep_nationality', 'Hondureña', 'string', 'Nacionalidad del Representante', 'documents', 1, '2026-03-01 15:32:16'),
(52, 'pagare_jurisdiction', 'Juzgado de Letras de lo Civil', 'string', 'Jurisdicción competente para pagarés', 'documents', 1, '2026-03-03 12:38:15'),
(53, 'pagare_city', '', 'string', 'Ciudad para firmar pagaré (vacío = usa company_city)', 'documents', 1, '2026-03-03 12:38:15'),
(54, 'aval_required_amount', '10000', 'decimal', 'Monto mínimo que requiere aval (L)', 'loans', 1, '2026-03-01 15:32:16'),
(131, 'bank_name_1', '', 'string', 'Nombre del banco 1', 'general', 1, '2026-03-03 12:38:15'),
(132, 'bank_account_1', '', 'string', 'Número de cuenta banco 1', 'general', 1, '2026-03-03 12:38:15'),
(133, 'bank_account_type_1', 'checking', 'string', 'Tipo de cuenta (checking, savings)', 'general', 1, '2026-03-03 12:38:15'),
(134, 'bank_account_holder_1', '', 'string', 'Titular de la cuenta 1', 'general', 1, '2026-03-03 12:38:15'),
(135, 'bank_account_iban_1', '', 'string', 'IBAN cuenta 1 (opcional)', 'general', 1, '2026-03-03 12:38:15'),
(136, 'bank_name_2', '', 'string', 'Nombre del banco 2', 'general', 1, '2026-03-03 12:38:16'),
(137, 'bank_account_2', '', 'string', 'Número de cuenta banco 2', 'general', 1, '2026-03-03 12:38:16'),
(138, 'bank_account_type_2', 'savings', 'string', 'Tipo de cuenta (checking, savings)', 'general', 1, '2026-03-03 12:38:16'),
(139, 'bank_account_holder_2', '', 'string', 'Titular de la cuenta 2', 'general', 1, '2026-03-03 12:38:16'),
(140, 'bank_account_iban_2', '', 'string', 'IBAN cuenta 2 (opcional)', 'general', 1, '2026-03-03 12:38:16'),
(141, 'bank_name_3', '', 'string', 'Nombre del banco 3', 'general', 1, '2026-03-03 12:38:16'),
(142, 'bank_account_3', '', 'string', 'Número de cuenta banco 3', 'general', 1, '2026-03-03 12:38:16'),
(143, 'bank_account_type_3', 'checking', 'string', 'Tipo de cuenta (checking, savings)', 'general', 1, '2026-03-03 12:38:16'),
(144, 'bank_account_holder_3', '', 'string', 'Titular de la cuenta 3', 'general', 1, '2026-03-03 12:38:16'),
(145, 'bank_account_iban_3', '', 'string', 'IBAN cuenta 3 (opcional)', 'general', 1, '2026-03-03 12:38:16'),
(146, 'contract_page_size', 'letter', 'string', 'Tamaño de página para contrato (letter, legal, a4)', 'documents', NULL, '2026-03-03 12:38:15'),
(147, 'contract_margin_top', '1.5cm', 'string', 'Margen superior del contrato', 'documents', NULL, '2026-03-03 12:38:15'),
(148, 'contract_margin_right', '2cm', 'string', 'Margen derecho/izquierdo del contrato', 'documents', NULL, '2026-03-03 12:38:15'),
(149, 'contract_jurisdiction', 'Juzgado de Letras de lo Civil', 'string', 'Jurisdicción competente para contratos', 'documents', NULL, '2026-03-03 12:38:15'),
(150, 'pagare_page_size', 'letter', 'string', 'Tamaño de página para pagaré (letter, legal, a4)', 'documents', NULL, '2026-03-03 12:38:15'),
(151, 'pagare_margin_top', '1.5cm', 'string', 'Margen superior del pagaré', 'documents', NULL, '2026-03-03 12:38:15'),
(152, 'pagare_margin_right', '2cm', 'string', 'Margen derecho/izquierdo del pagaré', 'documents', NULL, '2026-03-03 12:38:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `name` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `phone`, `avatar`, `is_active`, `remember_token`, `password_reset_token`, `password_reset_expires`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 'Super Administrador', 'superadmin@pistofacil.com', '$2y$10$EIUlqL8jgIkyukumJNRUhe2akfSztxrBATrAi6cZXKKf0Yf2jjD.S', '+504 9999-0000', NULL, 1, NULL, NULL, NULL, '2026-03-03 12:38:32', '2026-02-26 20:30:08', '2026-03-03 12:38:32'),
(2, 2, 'Admin Demo', 'admin@pistofacil.com', '$2y$10$zsgnRT18MCpBk2ps6pHxqeBZrjgAaKfiy6zt1c7AvO2RNx0p2q/iq', '+504 9999-0001', NULL, 1, NULL, NULL, NULL, NULL, '2026-02-26 20:30:08', '2026-02-26 21:19:18'),
(3, 3, 'Asesor Demo', 'asesor@pistofacil.com', '$2y$10$zsgnRT18MCpBk2ps6pHxqeBZrjgAaKfiy6zt1c7AvO2RNx0p2q/iq', '+504 9999-0002', NULL, 1, NULL, NULL, NULL, NULL, '2026-02-26 20:30:08', '2026-02-26 21:19:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_entity` (`entity`,`entity_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indices de la tabla `avales`
--
ALTER TABLE `avales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aval_client` (`client_id`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clients_code` (`code`),
  ADD KEY `idx_clients_user` (`user_id`),
  ADD KEY `idx_clients_assigned` (`assigned_to`),
  ADD KEY `idx_clients_name` (`last_name`,`first_name`),
  ADD KEY `fk_clients_created` (`created_by`);

--
-- Indices de la tabla `client_documents`
--
ALTER TABLE `client_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_docs_client` (`client_id`),
  ADD KEY `idx_docs_type` (`doc_type`),
  ADD KEY `fk_docs_uploader` (`uploaded_by`);

--
-- Indices de la tabla `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loans_number` (`loan_number`),
  ADD KEY `idx_loans_client` (`client_id`),
  ADD KEY `idx_loans_assigned` (`assigned_to`),
  ADD KEY `idx_loans_status` (`status`),
  ADD KEY `idx_loans_type` (`loan_type`),
  ADD KEY `fk_loans_created` (`created_by`),
  ADD KEY `fk_loan_aval` (`aval_id`);

--
-- Indices de la tabla `loan_events`
--
ALTER TABLE `loan_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_loan` (`loan_id`);

--
-- Indices de la tabla `loan_guarantees`
--
ALTER TABLE `loan_guarantees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guarantee_loan` (`loan_id`);

--
-- Indices de la tabla `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inst_loan` (`loan_id`),
  ADD KEY `idx_inst_due` (`due_date`),
  ADD KEY `idx_inst_status` (`status`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_number` (`payment_number`),
  ADD KEY `idx_pay_loan` (`loan_id`),
  ADD KEY `idx_pay_date` (`payment_date`),
  ADD KEY `fk_pay_registered` (`registered_by`);

--
-- Indices de la tabla `payment_items`
--
ALTER TABLE `payment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pitems_payment` (`payment_id`),
  ADD KEY `idx_pitems_inst` (`installment_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_slug` (`slug`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_settings_key` (`setting_key`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `avales`
--
ALTER TABLE `avales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `client_documents`
--
ALTER TABLE `client_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contract_templates`
--
ALTER TABLE `contract_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `loan_events`
--
ALTER TABLE `loan_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `loan_guarantees`
--
ALTER TABLE `loan_guarantees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `loan_installments`
--
ALTER TABLE `loan_installments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `payment_items`
--
ALTER TABLE `payment_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `avales`
--
ALTER TABLE `avales`
  ADD CONSTRAINT `fk_aval_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_clients_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `client_documents`
--
ALTER TABLE `client_documents`
  ADD CONSTRAINT `fk_docs_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_docs_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `contract_templates`
--
ALTER TABLE `contract_templates`
  ADD CONSTRAINT `contract_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loan_aval` FOREIGN KEY (`aval_id`) REFERENCES `avales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_loans_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `fk_loans_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `loan_events`
--
ALTER TABLE `loan_events`
  ADD CONSTRAINT `fk_events_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `loan_guarantees`
--
ALTER TABLE `loan_guarantees`
  ADD CONSTRAINT `fk_guarantee_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD CONSTRAINT `fk_inst_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `fk_pay_registered` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `payment_items`
--
ALTER TABLE `payment_items`
  ADD CONSTRAINT `fk_pitems_inst` FOREIGN KEY (`installment_id`) REFERENCES `loan_installments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pitems_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
