<?php
/**
 * WordPress Hooks Loader
 *
 * @package DGW\PendingRevisions\Core
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DGW\PendingRevisions\Core;

/**
 * WordPress Hooks Loader
 *
 * Manages the registration and execution of WordPress hooks (actions and filters).
 * Provides a centralized way to handle all plugin hooks.
 *
 * @since 1.0.0
 */
class Loader {
    
    /**
     * Array of actions to register
     *
     * @since 1.0.0
     * @var array
     */
    private array $actions = [];
    
    /**
     * Array of filters to register
     *
     * @since 1.0.0
     * @var array
     */
    private array $filters = [];
    
    /**
     * Add an action to be registered
     *
     * @since 1.0.0
     * @param string $hook The WordPress action hook name
     * @param object $component The object instance containing the callback method
     * @param string $callback The callback method name
     * @param int $priority The priority of the callback (default: 10)
     * @param int $accepted_args Number of arguments the callback accepts (default: 1)
     * @return void
     */
    public function add_action(
        string $hook, 
        object $component, 
        string $callback, 
        int $priority = 10, 
        int $accepted_args = 1
    ): void {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a filter to be registered
     *
     * @since 1.0.0
     * @param string $hook The WordPress filter hook name
     * @param object $component The object instance containing the callback method
     * @param string $callback The callback method name
     * @param int $priority The priority of the callback (default: 10)
     * @param int $accepted_args Number of arguments the callback accepts (default: 1)
     * @return void
     */
    public function add_filter(
        string $hook, 
        object $component, 
        string $callback, 
        int $priority = 10, 
        int $accepted_args = 1
    ): void {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a hook to the collection
     *
     * @since 1.0.0
     * @param array $hooks The collection of hooks
     * @param string $hook The WordPress hook name
     * @param object $component The object instance containing the callback method
     * @param string $callback The callback method name
     * @param int $priority The priority of the callback
     * @param int $accepted_args Number of arguments the callback accepts
     * @return array The updated hooks collection
     */
    private function add(
        array $hooks, 
        string $hook, 
        object $component, 
        string $callback, 
        int $priority, 
        int $accepted_args
    ): array {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        ];
        
        return $hooks;
    }
    
    /**
     * Register all hooks with WordPress
     *
     * @since 1.0.0
     * @return void
     */
    public function run(): void {
        // Register all actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Register all filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
    
    /**
     * Get registered actions
     *
     * @since 1.0.0
     * @return array
     */
    public function get_actions(): array {
        return $this->actions;
    }
    
    /**
     * Get registered filters
     *
     * @since 1.0.0
     * @return array
     */
    public function get_filters(): array {
        return $this->filters;
    }
    
    /**
     * Clear all registered hooks
     *
     * @since 1.0.0
     * @return void
     */
    public function clear(): void {
        $this->actions = [];
        $this->filters = [];
    }
    
    /**
     * Get hook count
     *
     * @since 1.0.0
     * @return array
     */
    public function get_hook_count(): array {
        return [
            'actions' => count($this->actions),
            'filters' => count($this->filters),
            'total' => count($this->actions) + count($this->filters),
        ];
    }
}