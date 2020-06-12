<?php

namespace Groundhogg\Bulk_Jobs;

// Exit if accessed directly
use function Groundhogg\get_post_var;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk job
 *
 * Provides a framework for extensions which require bulk jobs through the bulk job processor.
 *
 * @since       1.3
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class Bulk_Job {

	/**
	 * keep track of skipped items.
	 *
	 * @var int
	 */
	protected $skipped = 0;

	/**
	 * WPGH_Bulk_Jon constructor.
	 */
	public function __construct() {
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/max_items", [ $this, 'max_items' ], 10, 2 );
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/query", [ $this, 'query' ] );
		add_action( "groundhogg/bulk_job/{$this->get_action()}/ajax", [ $this, 'process' ] );
	}

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	abstract public function get_action();

	/**
	 * Start the bulk job by redirecting to the bulk jobs page.
	 *
	 * @param $additional array any additional arguments to add to the link
	 */
	public function start( $additional = [] ) {
		wp_redirect( $this->get_start_url( $additional ) );
		die();
	}

	/**
	 * Get the URL which will start the job.
	 *
	 * @param $additional array any additional arguments to add to the link
	 *
	 * @return string
	 */
	public function get_start_url( $additional = [] ) {
		return add_query_arg( array_merge( [ 'action' => $this->get_action() ], $this->get_start_query_args(), $additional ), admin_url( 'admin.php?page=gh_bulk_jobs' ) );
	}

	/**
	 * Get additional query args if any
	 *
	 * @return array
	 */
	protected function get_start_query_args() {
		return [];
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	abstract public function query( $items );

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max   int
	 * @param $items array
	 *
	 * @return int
	 */
	abstract public function max_items( $max, $items );

	/**
	 * Check to see if the current process will be the final one.
	 *
	 * @return mixed
	 */
	public function is_then_end() {
		$the_end = get_post_var( 'the_end', false );

		return filter_var( $the_end, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Do something when an item is skipped
	 *
	 * @param $item
	 */
	protected function skip_item( $item ){
		$this->skipped++;
	}

	/**
	 * Process the bulk job.
	 */
	public function process() {

		$start = microtime( true );

		if ( ! key_exists( 'the_end', $_POST ) ) {

			$error = new \WP_Error(
				'error',
				__( 'There was an error performing this process. This is most likely due to the PHP max_input_vars variable not being high enough.', 'groundhogg' )
			);

			wp_send_json_error( $error );
		}

		$items = $this->get_items();

		$completed = 0;

		$this->pre_loop();

		foreach ( $items as $item ) {
			$this->process_item( $item );
			$completed ++;
		}

		$this->post_loop();

		$end  = microtime( true );
		$diff = round( $end - $start, 2 );

		if ( $this->skipped > 0 ){
			$msg = sprintf( __( 'Processed %d items in %s seconds. Skipped %s items.', 'groundhogg' ), $completed, $diff, $this->skipped );
		} else {
			$msg = sprintf( __( 'Processed %d items in %s seconds.', 'groundhogg' ), $completed, $diff );
		}

		$response = [
			'complete' => $completed - $this->skipped,
			'skipped'  => $this->skipped,
			'message'  => esc_html( $msg ),
		];

		$the_end = get_post_var( 'the_end', false );

		if ( filter_var( $the_end, FILTER_VALIDATE_BOOLEAN ) ) {

			$this->clean_up();

			$response['return_url'] = $this->get_return_url();

			Plugin::instance()->notices->add( 'finished', $this->get_finished_notice() );

		}

		$this->send_response( $response );
	}

	/**
	 * Get a list of items from the bulk job.
	 *
	 * @return array
	 */
	public function get_items() {
		return isset_not_empty( $_POST, 'items' ) ? $_POST['items'] : [];
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	abstract protected function pre_loop();

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 * @param $args array
	 *
	 * @return void
	 */
	abstract protected function process_item( $item );

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	abstract protected function post_loop();

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	abstract protected function clean_up();

	/**
	 * Get the return url.
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_url( 'admin.php?page=groundhogg' );
	}

	/**
	 * get text for the finished notice
	 *
	 * @return string
	 */
	protected function get_finished_notice() {
		return _x( 'Job finished!', 'notice', 'groundhogg' );
	}

	protected function send_response( $response ) {
		wp_send_json( apply_filters( "groundhogg/bulk_job/{$this->get_action()}/send_response", $response ) );
	}

}

