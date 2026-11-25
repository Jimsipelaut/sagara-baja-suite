<?php
namespace SagaraBaja\Core\Auth;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Daftar konstanta Capability untuk otorisasi akses di Sagara Baja Suite.
 */
final class Capabilities {
    // Menu dasar
    public const ACCESS_DASHBOARD     = 'sb_access_dashboard';
    public const MANAGE_SETTINGS       = 'sb_manage_settings';
    public const VIEW_AUDIT_LOGS      = 'sb_view_audit_logs';

    // Inventory & Gudang
    public const VIEW_INVENTORY       = 'sb_view_inventory';
    public const MANAGE_INVENTORY     = 'sb_manage_inventory';
    public const APPROVE_STOCK_OPNAME = 'sb_approve_stock_opname';

    // Penjualan & POS
    public const ACCESS_POS           = 'sb_access_pos';
    public const VIEW_SALES           = 'sb_view_sales';
    public const PROCESS_SALE         = 'sb_process_sale';
    public const MANAGE_SHIFTS        = 'sb_manage_shifts';
    public const CREATE_SALES         = 'sb_create_sales';
    public const CREATE_INVOICE_RESMI = 'sb_create_invoice_resmi';
    public const CREATE_NOTA_NONRESMI = 'sb_create_nota_nonresmi';
    public const VOID_TRANSACTION     = 'sb_void_transaction';

    // Pembelian & Supplier
    public const VIEW_PURCHASING      = 'sb_view_purchasing';
    public const MANAGE_PURCHASING    = 'sb_manage_purchasing';

    // Akuntansi & Keuangan
    public const VIEW_ACCOUNTING      = 'sb_view_accounting';
    public const MANAGE_JOURNAL       = 'sb_manage_journal';
    public const CLOSE_PERIOD         = 'sb_close_period';
    public const VIEW_FINANCIAL_REPORTS = 'sb_view_financial_reports';

    // HR & Penggajian
    public const VIEW_EMPLOYEES       = 'sb_view_employees';
    public const MANAGE_EMPLOYEES     = 'sb_manage_employees';
    public const RECORD_ATTENDANCE    = 'sb_record_attendance';
    public const APPROVE_LEAVE        = 'sb_approve_leave';
    public const APPROVE_OVERTIME     = 'sb_approve_overtime';
    public const VIEW_PAYROLL         = 'sb_view_payroll';
    public const PROCESS_PAYROLL      = 'sb_process_payroll';
    public const LOCK_PAYROLL         = 'sb_lock_payroll';
    public const VIEW_OWN_DATA        = 'sb_view_own_data';
}
