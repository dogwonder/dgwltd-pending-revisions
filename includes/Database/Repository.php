<?php
/**
 * Base Repository Class
 *
 * @package DGW\PendingRevisions\Database
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Database;

/**
 * Base Repository Class
 *
 * Provides common database operations for all repositories.
 *
 * @since 1.0.0
 */
abstract class Repository {
    
    /**
     * WordPress database instance
     *
     * @since 1.0.0
     * @var \wpdb
     */
    protected \wpdb $wpdb;
    
    /**
     * Table name
     *
     * @since 1.0.0
     * @var string
     */
    protected string $table_name;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Get table name with prefix
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_table_name(): string {
        return $this->wpdb->prefix . $this->table_name;
    }
    
    /**
     * Prepare query with placeholders
     *
     * @since 1.0.0
     * @param string $query SQL query with placeholders
     * @param mixed ...$args Arguments for placeholders
     * @return string
     */
    protected function prepare(string $query, ...$args): string {
        return $this->wpdb->prepare($query, ...$args);
    }
    
    /**
     * Execute query and return results
     *
     * @since 1.0.0
     * @param string $query SQL query
     * @return array|null
     */
    protected function get_results(string $query): ?array {
        return $this->wpdb->get_results($query);
    }
    
    /**
     * Execute query and return single row
     *
     * @since 1.0.0
     * @param string $query SQL query
     * @return object|null
     */
    protected function get_row(string $query): ?object {
        return $this->wpdb->get_row($query);
    }
    
    /**
     * Execute query and return single value
     *
     * @since 1.0.0
     * @param string $query SQL query
     * @return mixed
     */
    protected function get_var(string $query) {
        return $this->wpdb->get_var($query);
    }
    
    /**
     * Insert data into table
     *
     * @since 1.0.0
     * @param array $data Data to insert
     * @param array $format Data format
     * @return int|false
     */
    protected function insert(array $data, array $format = []): int|false {
        $result = $this->wpdb->insert($this->get_table_name(), $data, $format);
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Update data in table
     *
     * @since 1.0.0
     * @param array $data Data to update
     * @param array $where Where conditions
     * @param array $format Data format
     * @param array $where_format Where format
     * @return int|false
     */
    protected function update(array $data, array $where, array $format = [], array $where_format = []): int|false {
        return $this->wpdb->update($this->get_table_name(), $data, $where, $format, $where_format);
    }
    
    /**
     * Delete data from table
     *
     * @since 1.0.0
     * @param array $where Where conditions
     * @param array $where_format Where format
     * @return int|false
     */
    protected function delete(array $where, array $where_format = []): int|false {
        return $this->wpdb->delete($this->get_table_name(), $where, $where_format);
    }
    
    /**
     * Get last database error
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_last_error(): string {
        return $this->wpdb->last_error;
    }
    
    /**
     * Start database transaction
     *
     * @since 1.0.0
     * @return void
     */
    protected function start_transaction(): void {
        $this->wpdb->query('START TRANSACTION');
    }
    
    /**
     * Commit database transaction
     *
     * @since 1.0.0
     * @return void
     */
    protected function commit(): void {
        $this->wpdb->query('COMMIT');
    }
    
    /**
     * Rollback database transaction
     *
     * @since 1.0.0
     * @return void
     */
    protected function rollback(): void {
        $this->wpdb->query('ROLLBACK');
    }
}