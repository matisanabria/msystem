-- Migration: Replace US Tax system with Paraguay IVA (10% included)
-- Date: 2026-02-11
-- Description: Configure database for simple IVA 10% calculation (Price / 11)

-- Set tax as included in price (IVA is always included)
UPDATE ospos_app_config SET value = '1' WHERE `key` = 'tax_included';

-- Disable destination-based tax (not needed for Paraguay)
UPDATE ospos_app_config SET value = '0' WHERE `key` = 'use_destination_based_tax';

-- Show taxes on receipts
UPDATE ospos_app_config SET value = '1' WHERE `key` = 'receipt_show_taxes';

-- Set default tax 1 to IVA 10%
UPDATE ospos_app_config SET value = 'IVA' WHERE `key` = 'default_tax_1_name';
UPDATE ospos_app_config SET value = '10' WHERE `key` = 'default_tax_1_rate';

-- Hide the taxes module from the menu
UPDATE ospos_modules SET sort = 0 WHERE module_id = 'taxes';

-- Make all customers taxable (IVA applies to everyone in Paraguay)
UPDATE ospos_customers SET taxable = 1 WHERE taxable = 0;
