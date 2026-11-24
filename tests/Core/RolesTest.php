<?php
namespace SagaraBaja\Tests\Core;

use SagaraBaja\Core\Auth\Roles;
use SagaraBaja\Core\Auth\Capabilities;

class RolesTest {
    public function run(): void {
        $this->test_all_roles_defined();
        $this->test_cashier_cannot_view_payroll();
        $this->test_warehouse_cannot_create_invoice();
        $this->test_owner_has_all_capabilities();
    }

    private function test_all_roles_defined(): void {
        $roles = Roles::get_role_definitions();
        $expected = [
            Roles::ROLE_OWNER,
            Roles::ROLE_ADMIN,
            Roles::ROLE_CASHIER,
            Roles::ROLE_WAREHOUSE,
            Roles::ROLE_FINANCE,
            Roles::ROLE_EMPLOYEE,
        ];

        foreach ( $expected as $role ) {
            assert( isset( $roles[ $role ] ), "Gagal: peran {$role} belum terdaftar." );
        }
        echo "  [PASS] test_all_roles_defined\n";
    }

    private function test_cashier_cannot_view_payroll(): void {
        $roles = Roles::get_role_definitions();
        $cashier_caps = $roles[ Roles::ROLE_CASHIER ]['caps'];

        assert( empty( $cashier_caps[ Capabilities::VIEW_PAYROLL ] ), "Pelanggaran: Kasir tidak boleh memiliki akses VIEW_PAYROLL." );
        assert( empty( $cashier_caps[ Capabilities::VIEW_ACCOUNTING ] ), "Pelanggaran: Kasir tidak boleh memiliki akses VIEW_ACCOUNTING." );
        assert( ! empty( $cashier_caps[ Capabilities::ACCESS_POS ] ), "Gagal: Kasir wajib memiliki ACCESS_POS." );
        echo "  [PASS] test_cashier_cannot_view_payroll\n";
    }

    private function test_warehouse_cannot_create_invoice(): void {
        $roles = Roles::get_role_definitions();
        $warehouse_caps = $roles[ Roles::ROLE_WAREHOUSE ]['caps'];

        assert( empty( $warehouse_caps[ Capabilities::CREATE_INVOICE_RESMI ] ), "Pelanggaran: Petugas gudang tidak boleh menerbitkan invoice resmi." );
        assert( empty( $warehouse_caps[ Capabilities::CREATE_NOTA_NONRESMI ] ), "Pelanggaran: Petugas gudang tidak boleh menerbitkan nota non-resmi." );
        assert( ! empty( $warehouse_caps[ Capabilities::VIEW_INVENTORY ] ), "Gagal: Petugas gudang wajib memiliki VIEW_INVENTORY." );
        echo "  [PASS] test_warehouse_cannot_create_invoice\n";
    }

    private function test_owner_has_all_capabilities(): void {
        $roles = Roles::get_role_definitions();
        $owner_caps = $roles[ Roles::ROLE_OWNER ]['caps'];
        $all_caps = Roles::get_all_capabilities();

        foreach ( $all_caps as $cap ) {
            assert( ! empty( $owner_caps[ $cap ] ), "Gagal: Owner wajib memiliki capability {$cap}." );
        }
        echo "  [PASS] test_owner_has_all_capabilities\n";
    }
}
