-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 27-02-2026 a las 04:37:36
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
  `identity_number` varchar(30) DEFAULT NULL COMMENT 'DNI/RTN',
  `email` varchar(180) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
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

INSERT INTO `clients` (`id`, `user_id`, `assigned_to`, `code`, `first_name`, `last_name`, `identity_number`, `email`, `phone`, `phone2`, `address`, `city`, `occupation`, `monthly_income`, `reference_name`, `reference_phone`, `notes`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'CLI-00001', 'Juan', 'Pérez López', '0801-1990-12345', 'juan@demo.hn', '+504 9811-1111', NULL, 'Col. Kennedy, Casa #5', 'Tegucigalpa', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08'),
(2, NULL, NULL, 'CLI-00002', 'María', 'García Sánchez', '0801-1985-67890', 'maria@demo.hn', '+504 9822-2222', NULL, 'Bo. El Centro', 'San Pedro Sula', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08'),
(3, NULL, NULL, 'CLI-00003', 'Carlos', 'Martínez Ruiz', '0801-1978-11122', NULL, '+504 9833-3333', NULL, 'Col. Palmira', 'Tegucigalpa', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-02-26 20:30:08', '2026-02-26 20:30:08');

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
-- Estructura de tabla para la tabla `loans`
--

CREATE TABLE `loans` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `loan_number` varchar(20) NOT NULL,
  `loan_type` enum('A','B','C') NOT NULL COMMENT 'A=Nivelada, B=Variable/Abonos, C=Simple Mensual',
  `principal` decimal(14,2) NOT NULL COMMENT 'Monto desembolsado',
  `interest_rate` decimal(7,4) NOT NULL COMMENT 'Tasa (mensual si type C, anual si A)',
  `rate_type` enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `term_months` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'Plazo en meses (requerido para tipo A)',
  `late_fee_rate` decimal(7,4) NOT NULL DEFAULT 0.0500 COMMENT 'Tasa moratoria mensual',
  `grace_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Días de gracia antes de mora',
  `disbursement_date` date NOT NULL,
  `first_payment_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL COMMENT 'Fecha real último pago',
  `maturity_date` date DEFAULT NULL,
  `status` enum('active','paid','defaulted','cancelled','restructured') NOT NULL DEFAULT 'active',
  `balance` decimal(14,2) NOT NULL COMMENT 'Saldo capital actual',
  `total_interest_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_late_fees_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `apply_payment_to` enum('capital','interest_first') NOT NULL DEFAULT 'interest_first',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `loan_events`
--

CREATE TABLE `loan_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_type` enum('created','payment','restructured','status_change','note','document') NOT NULL,
  `description` text NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'app_name', 'SistemaPréstamos', 'string', 'Nombre del sistema', 'general', NULL, '2026-02-26 20:30:08'),
(2, 'app_currency', 'L', 'string', 'Símbolo de moneda', 'general', NULL, '2026-02-26 20:30:08'),
(3, 'app_currency_name', 'Lempira', 'string', 'Nombre de la moneda', 'general', NULL, '2026-02-26 20:30:08'),
(4, 'default_late_fee_rate', '0.05', 'decimal', 'Tasa moratoria mensual por defecto (5%)', 'loans', NULL, '2026-02-26 20:30:08'),
(5, 'grace_days', '3', 'integer', 'Días de gracia antes de aplicar mora', 'loans', NULL, '2026-02-26 20:30:08'),
(6, 'alert_days_upcoming', '7', 'integer', 'Días para alerta \"por vencer\"', 'dashboard', NULL, '2026-02-26 20:30:08'),
(7, 'alert_days_warning', '15', 'integer', 'Días aviso previo', 'dashboard', NULL, '2026-02-26 20:30:08'),
(8, 'initial_capital', '200000.00', 'decimal', 'Capital inicial para proyección (L)', 'reports', NULL, '2026-02-26 20:30:08'),
(9, 'max_upload_size_mb', '10', 'integer', 'Tamaño máximo de archivo en MB', 'documents', NULL, '2026-02-26 20:30:08'),
(10, 'allowed_mime_types', '[\"application/pdf\",\"image/jpeg\",\"image/png\",\"image/webp\"]', 'json', 'MIME permitidos', 'documents', NULL, '2026-02-26 20:30:08'),
(11, 'loan_number_prefix', 'PRES-', 'string', 'Prefijo para número de préstamo', 'loans', NULL, '2026-02-26 20:30:08'),
(12, 'client_number_prefix', 'CLI-', 'string', 'Prefijo para código de cliente', 'clients', NULL, '2026-02-26 20:30:08'),
(13, 'payment_number_prefix', 'PAG-', 'string', 'Prefijo para número de pago', 'loans', NULL, '2026-02-26 20:30:08'),
(14, 'items_per_page', '20', 'integer', 'Registros por página en tablas', 'general', NULL, '2026-02-26 20:30:08'),
(15, 'timezone', 'America/Tegucigalpa', 'string', 'Zona horaria del servidor', 'general', NULL, '2026-02-26 20:30:08');

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
(1, 1, 'Super Administrador', 'superadmin@pistofacil.com', '$2y$10$EIUlqL8jgIkyukumJNRUhe2akfSztxrBATrAi6cZXKKf0Yf2jjD.S', '+504 9999-0000', NULL, 1, NULL, NULL, NULL, '2026-02-26 21:22:00', '2026-02-26 20:30:08', '2026-02-26 21:22:00'),
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
-- Indices de la tabla `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loans_number` (`loan_number`),
  ADD KEY `idx_loans_client` (`client_id`),
  ADD KEY `idx_loans_assigned` (`assigned_to`),
  ADD KEY `idx_loans_status` (`status`),
  ADD KEY `idx_loans_type` (`loan_type`),
  ADD KEY `fk_loans_created` (`created_by`);

--
-- Indices de la tabla `loan_events`
--
ALTER TABLE `loan_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_loan` (`loan_id`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `client_documents`
--
ALTER TABLE `client_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `loan_events`
--
ALTER TABLE `loan_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `loan_installments`
--
ALTER TABLE `loan_installments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

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
-- Filtros para la tabla `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `fk_loans_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `loan_events`
--
ALTER TABLE `loan_events`
  ADD CONSTRAINT `fk_events_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

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
