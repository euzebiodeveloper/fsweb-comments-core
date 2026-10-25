<?php

/**
 * Register all actions and filters for the plugin.
 *
 * @link       https://flowsyncweb.com
 * @since      1.0.0
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/includes
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Maintains the list of hooks and registers them with WordPress.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array
	 */
	protected $actions;

	/**
	 * Registered filters.
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * Initialise the collections.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Queue an action.
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Object holding the callback.
	 * @param string $callback      Method name.
	 * @param int    $priority      Optional. Priority.
	 * @param int    $accepted_args Optional. Accepted arguments.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Queue a filter.
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Object holding the callback.
	 * @param string $callback      Method name.
	 * @param int    $priority      Optional. Priority.
	 * @param int    $accepted_args Optional. Accepted arguments.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a hook to a collection.
	 *
	 * @param array  $hooks         Collection.
	 * @param string $hook          Hook name.
	 * @param object $component     Object holding the callback.
	 * @param string $callback      Method name.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Accepted arguments.
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register every queued hook with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
