<?php
namespace SagaraBaja\Core\Auth;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Pengelola pendaftaran Peran (Roles) dan Hak Akses (Capabilities).
 */
class Roles {
    public const ROLE_OWNER     = 'sb_owner';
    public const ROLE_ADMIN     = 'sb_admin';
    public const ROLE_CASHIER   = 'sb_cashier';
    public const ROLE_WAREHOUSE = 'sb_warehouse';
    public const ROLE_FINANCE   = 'sb_finance';
    public const ROLE_EMPLOYEE  = 'sb_employee';

    /**
     * Daftarkan seluruh peran dan mapping capabilities.
     */
    public static function register_roles(): void {
        $definitions = self::get_role_definitions();

        foreach ( $definitions as $role_key => $data ) {
            add_role( $role_key, $data['label'], $data['caps'] );
        }

        // Berikan semua capability ke administrator bawaan WordPress
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( self::get_all_capabilities() as $cap ) {
                $admin_role->add_cap( $cap );
            }
        }
    }

    /**
     * Hapus peran kustom saat uninstall jika diperlukan.
     */
    public static function remove_roles(): void {
        $roles = [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_CASHIER,
            self::ROLE_WAREHOUSE,
            self::ROLE_FINANCE,
            self::ROLE_EMPLOYEE,
        ];

        foreach ( $roles as $role ) {
            remove_role( $role );
        }
    }

    /**
     * Definisi lengkap peran dan capability.
     */
    public static function get_role_definitions(): array {
        $read_cap = [ 'read' => true ];

        return [
            self::ROLE_OWNER => [
                'label' => __( 'SB Owner', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, array_fill_keys( self::get_all_capabilities(), true ) ),
            ],
            self::ROLE_ADMIN => [
                'label' => __( 'SB Admin', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, [
                    Capabilities::ACCESS_DASHBOARD       => true,
                    Capabilities::MANAGE_SETTINGS        => true,
                    Capabilities::VIEW_AUDIT_LOGS        => true,
                    Capabilities::VIEW_INVENTORY         => true,
                    Capabilities::MANAGE_INVENTORY       => true,
                    Capabilities::APPROVE_STOCK_OPNAME   => true,
                    Capabilities::ACCESS_POS             => true,
                    Capabilities::VIEW_SALES             => true,
                    Capabilities::PROCESS_SALE           => true,
                    Capabilities::MANAGE_SHIFTS          => true,
                    Capabilities::CREATE_SALES           => true,
                    Capabilities::CREATE_INVOICE_RESMI   => true,
                    Capabilities::CREATE_NOTA_NONRESMI   => true,
                    Capabilities::VOID_TRANSACTION       => true,
                    Capabilities::VIEW_PURCHASING        => true,
                    Capabilities::MANAGE_PURCHASING      => true,
                    Capabilities::VIEW_EMPLOYEES         => true,
                    Capabilities::MANAGE_EMPLOYEES       => true,
                    Capabilities::RECORD_ATTENDANCE      => true,
                    Capabilities::APPROVE_LEAVE          => true,
                    Capabilities::APPROVE_OVERTIME       => true,
                    Capabilities::VIEW_OWN_DATA          => true,
                ] ),
            ],
            self::ROLE_CASHIER => [
                'label' => __( 'SB Kasir / Sales', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, [
                    Capabilities::ACCESS_POS             => true,
                    Capabilities::VIEW_SALES             => true,
                    Capabilities::PROCESS_SALE           => true,
                    Capabilities::MANAGE_SHIFTS          => true,
                    Capabilities::CREATE_SALES           => true,
                    Capabilities::CREATE_INVOICE_RESMI   => true,
                    Capabilities::CREATE_NOTA_NONRESMI   => true,
                    Capabilities::VIEW_INVENTORY         => true,
                    Capabilities::RECORD_ATTENDANCE      => true,
                    Capabilities::VIEW_OWN_DATA          => true,
                ] ),
            ],
            self::ROLE_WAREHOUSE => [
                'label' => __( 'SB Petugas Gudang', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, [
                    Capabilities::VIEW_INVENTORY         => true,
                    Capabilities::MANAGE_INVENTORY       => true,
                    Capabilities::VIEW_PURCHASING        => true,
                    Capabilities::RECORD_ATTENDANCE      => true,
                    Capabilities::VIEW_OWN_DATA          => true,
                ] ),
            ],
            self::ROLE_FINANCE => [
                'label' => __( 'SB Keuangan & Akunting', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, [
                    Capabilities::ACCESS_DASHBOARD       => true,
                    Capabilities::VIEW_AUDIT_LOGS        => true,
                    Capabilities::VIEW_ACCOUNTING        => true,
                    Capabilities::MANAGE_JOURNAL         => true,
                    Capabilities::CLOSE_PERIOD           => true,
                    Capabilities::VIEW_FINANCIAL_REPORTS => true,
                    Capabilities::CREATE_INVOICE_RESMI   => true,
                    Capabilities::CREATE_NOTA_NONRESMI   => true,
                    Capabilities::VIEW_PURCHASING        => true,
                    Capabilities::VIEW_PAYROLL           => true,
                    Capabilities::PROCESS_PAYROLL        => true,
                    Capabilities::LOCK_PAYROLL           => true,
                    Capabilities::RECORD_ATTENDANCE      => true,
                    Capabilities::VIEW_OWN_DATA          => true,
                ] ),
            ],
            self::ROLE_EMPLOYEE => [
                'label' => __( 'SB Karyawan', 'sagara-baja-suite' ),
                'caps'  => array_merge( $read_cap, [
                    Capabilities::RECORD_ATTENDANCE      => true,
                    Capabilities::VIEW_OWN_DATA          => true,
                ] ),
            ],
        ];
    }

    /**
     * Ambil seluruh daftar capability yang ada.
     */
    public static function get_all_capabilities(): array {
        $reflection = new \ReflectionClass( Capabilities::class );
        return array_values( $reflection->getConstants() );
    }
}
