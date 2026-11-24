<?php
namespace SagaraBaja\Tests\Core;

use SagaraBaja\Core\Audit\AuditLogger;

class AuditLoggerTest {
    public function run(): void {
        $this->test_audit_log_inserts_record();
        $this->test_audit_log_payload_formatting();
    }

    private function test_audit_log_inserts_record(): void {
        global $wpdb;
        $wpdb->inserted_rows = [];

        $logger = new AuditLogger( $wpdb );
        $id = $logger->record( 'create_invoice', 'invoice_resmi', 'INV-001', [ 'total' => 0 ], [ 'total' => 1500000 ] );

        assert( $id > 0, "Gagal: id audit log harus bernilai positif." );
        assert( count( $wpdb->inserted_rows ) === 1, "Gagal: harus ada 1 baris di-insert ke audit_logs." );

        $row = $wpdb->inserted_rows[0]['data'];
        assert( $row['action'] === 'create_invoice', "Gagal: action salah." );
        assert( $row['object_type'] === 'invoice_resmi', "Gagal: object_type salah." );
        assert( $row['object_id'] === 'INV-001', "Gagal: object_id salah." );
        assert( $row['user_name'] === 'Owner Sagara', "Gagal: user_name salah." );
        echo "  [PASS] test_audit_log_inserts_record\n";
    }

    private function test_audit_log_payload_formatting(): void {
        global $wpdb;
        $wpdb->inserted_rows = [];

        $logger = new AuditLogger( $wpdb );
        $logger->record( 'stock_adjustment', 'stock', 'SKU-001', null, [ 'qty_diff' => 10 ] );

        $row = $wpdb->inserted_rows[0]['data'];
        assert( $row['old_values'] === null, "Gagal: old_values harus null saat tidak diberikan." );
        assert( $row['new_values'] === '{"qty_diff":10}', "Gagal: new_values JSON formatting salah: " . $row['new_values'] );
        echo "  [PASS] test_audit_log_payload_formatting\n";
    }
}
