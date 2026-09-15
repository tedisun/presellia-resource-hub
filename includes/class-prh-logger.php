<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger officiel pour Presellia Resource Hub — écrit dans {prefix}prh_logs.
 */
class PRH_Logger {

    private static ?string $table = null;

    private static function table(): string {
        if ( null === self::$table ) {
            global $wpdb;
            self::$table = $wpdb->prefix . 'prh_logs';
        }

        return self::$table;
    }

    public static function info( string $event, string $message, array $context = [] ): void {
        self::log( 'info', $event, $message, $context );
    }

    public static function warning( string $event, string $message, array $context = [] ): void {
        self::log( 'warning', $event, $message, $context );
    }

    public static function error( string $event, string $message, array $context = [] ): void {
        self::log( 'error', $event, $message, $context );
    }

    public static function log( string $level, string $event, string $message, array $context = [] ): void {
        global $wpdb;

        $wpdb->insert(
            self::table(),
            [
                'level'      => substr( sanitize_key( $level ), 0, 10 ),
                'event'      => substr( sanitize_key( $event ), 0, 50 ),
                'message'    => sanitize_text_field( $message ),
                'context'    => ! empty( $context ) ? wp_json_encode( $context ) : null,
                'ip'         => self::get_client_ip(),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    public static function get_recent( int $limit = 50, string $level = '', string $event = '' ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $level ) ) {
            $where   .= ' AND level = %s';
            $params[] = sanitize_key( $level );
        }

        if ( ! empty( $event ) ) {
            $where   .= ' AND event = %s';
            $params[] = sanitize_key( $event );
        }

        $limit    = min( max( 1, $limit ), 500 );
        $params[] = $limit;

        $sql = "SELECT id, level, event, message, context, ip, created_at
                FROM " . self::table() . "
                WHERE {$where}
                ORDER BY id DESC
                LIMIT %d";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        foreach ( $rows as &$row ) {
            if ( ! empty( $row['context'] ) ) {
                $row['context'] = json_decode( $row['context'], true );
            }
        }

        return $rows;
    }

    public static function get_counts( int $days = 30 ): array {
        global $wpdb;

        $since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

        $sql = "SELECT level, COUNT(*) as cnt
                FROM " . self::table() . "
                WHERE created_at >= %s
                GROUP BY level";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $since ), ARRAY_A );

        $counts = [
            'total'   => 0,
            'info'    => 0,
            'warning' => 0,
            'error'   => 0,
        ];

        if ( is_array( $rows ) ) {
            foreach ( $rows as $row ) {
                $lvl = $row['level'];
                $cnt = (int) $row['cnt'];
                $counts['total'] += $cnt;
                if ( isset( $counts[ $lvl ] ) ) {
                    $counts[ $lvl ] = $cnt;
                }
            }
        }

        return $counts;
    }

    public static function purge_old( int $days = 30 ): int {
        global $wpdb;

        $before = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        $table  = self::table();

        $deleted = $wpdb->query(
            $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $before )
        );

        return false === $deleted ? 0 : $deleted;
    }

    public static function clear_all(): bool {
        global $wpdb;
        $table   = self::table();
        $cleared = $wpdb->query( "TRUNCATE TABLE {$table}" );
        return false !== $cleared;
    }

    public static function purge_weekly(): void {
        self::purge_old( 30 );
    }

    public static function get_client_ip(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $parts = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            $ip    = trim( $parts[0] );
        }
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}

add_action( 'prh_weekly_cleanup', [ 'PRH_Logger', 'purge_weekly' ] );
