<?php
namespace SagaraBaja\Core\Audit;

if ( ! defined( 'ABSPATH' ) && ! defined( 'SB_TEST_SUITE' ) ) {
    exit;
}

/**
 * Append-only Audit Logger untuk melacak setiap perubahan kritis (uang, stok, penggajian, penomoran).
 */
class AuditLogger {
    private \wpdb $wpdb;
    private string $table_name;

    public function __construct( ?\wpdb $wpdb = null ) {
        global $wpdb;
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . SB_TABLE_PREFIX . 'audit_logs';
    }

    /**
     * Catat log audit baru. Append-only, dilarang ada update/delete.
     *
     * @param string      $action      Jenis aksi (create, update, void, approve, lock, login)
     * @param string      $object_type Tipe objek (invoice, stock_move, journal, payroll, sequence)
     * @param string|null $object_id   ID atau identifier objek
     * @param array|null  $old_values  Data lama sebelum diubah
     * @param array|null  $new_values  Data baru setelah diubah
     * @return int|false ID baris log yang baru dibuat
     */
    public function record(
        string $action,
        string $object_type,
        ?string $object_id = null,
        ?array $old_values = null,
        ?array $new_values = null
    ): int|false {
        $user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
        $user_name = null;

        if ( $user_id > 0 && function_exists( 'wp_get_current_user' ) ) {
            $user = wp_get_current_user();
            $user_name = $user->display_name ?: $user->user_login;
        }

        $ip_address = $this->get_client_ip();
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null;
        $created_at = function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );

        $data = [
            'user_id'     => $user_id ?: null,
            'user_name'   => $user_name,
            'action'      => sanitize_key( $action ),
            'object_type' => sanitize_key( $object_type ),
            'object_id'   => $object_id !== null ? substr( sanitize_text_field( $object_id ), 0, 50 ) : null,
            'old_values'  => ! empty( $old_values ) ? wp_json_encode( $old_values ) : null,
            'new_values'  => ! empty( $new_values ) ? wp_json_encode( $new_values ) : null,
            'ip_address'  => $ip_address,
            'user_agent'  => $user_agent,
            'created_at'  => $created_at,
        ];

        $format = [
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ];

        $inserted = $this->wpdb->insert( $this->table_name, $data, $format );

        return $inserted ? (int) $this->wpdb->insert_id : false;
    }

    /**
     * Helper statis agar mudah dipanggil dari modul mana pun.
     */
    public static function log(
        string $action,
        string $object_type,
        ?string $object_id = null,
        ?array $old_values = null,
        ?array $new_values = null
    ): int|false {
        $instance = new self();
        return $instance->record( $action, $object_type, $object_id, $old_values, $new_values );
    }

    /**
     * Dapatkan IP client yang aman.
     */
    private function get_client_ip(): ?string {
        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
        return null;
    }
}
