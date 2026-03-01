-- ============================================================
-- MIGRATION v2: Agregar frecuencia de pago a préstamos
-- Ejecutar: mysql -u user -p prestamos_db < migration_v2.sql
-- ============================================================

ALTER TABLE `loans`
  ADD COLUMN `payment_frequency` ENUM('weekly','biweekly','monthly','bimonthly','quarterly','semiannual','annual')
  NOT NULL DEFAULT 'monthly'
  AFTER `term_months`;

-- Actualizar préstamos existentes (asegurar que tengan monthly)
UPDATE `loans` SET `payment_frequency` = 'monthly' WHERE `payment_frequency` IS NULL OR `payment_frequency` = '';
