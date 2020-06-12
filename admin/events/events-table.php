<?php

namespace Groundhogg\Admin\Events;

use Groundhogg\Event;
use function Groundhogg\action_url;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\scheduled_time;
use \WP_List_Table;
use function Groundhogg\scheduled_time_column;

/**
 * Events Table Class
 *
 * This class shows the events queue with bulk options to manage events or 1 at a time.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Events_Table extends WP_List_Table {

	protected $table;

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'event',     // Singular name of the listed records.
			'plural'   => 'events',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />', // Render a checkbox instead of text.
			'contact'       => _x( 'Contact', 'Column label', 'wp-funnels' ),
			'funnel'        => _x( 'Funnel', 'Column label', 'wp-funnels' ),
			'step'          => _x( 'Step', 'Column label', 'wp-funnels' ),
			'time'          => _x( 'Time', 'Column label', 'wp-funnels' ),
			'error_code'    => _x( 'Error Code', 'Column label', 'wp-funnels' ),
			'error_message' => _x( 'Error Message', 'Column label', 'wp-funnels' ),
		);

		return apply_filters( 'groundhogg_event_columns', $columns );
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'contact' => array( 'contact_id', false ),
			'funnel'  => array( 'funnel_id', false ),
			'step'    => array( 'step_id', false ),
			'time'    => array( 'time', false ),
		);

		return apply_filters( 'groundhogg_event_sortable_columns', $sortable_columns );
	}

	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Event( $item->ID, $this->table ) );
		echo '</tr>';
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_contact( $event ) {
		if ( ! $event->get_contact() || ! $event->get_contact()->exists() ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'contact deleted', 'status', 'groundhogg' ) );
		}

		$html = sprintf( "<a class='row-title' href='%s'>%s</a>",
			sprintf( admin_url( 'admin.php?page=gh_events&contact_id=%s&&status=%s' ), $event->get_contact_id(), $this->get_view() ),
			$event->get_contact()->get_email()
		);

		return $html;
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_funnel( $event ) {
		$funnel_title = $event->get_funnel_title();

		if ( ! $funnel_title ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'funnel deleted', 'status', 'groundhogg' ) );
		}

		return sprintf( "<a href='%s'>%s</a>",
			sprintf( admin_url( 'admin.php?page=gh_events&funnel_id=%s&event_type=%s&status=%s' ), $event->get_funnel_id(), $event->get_event_type(), $this->get_view() ),
			$funnel_title );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_step( $event ) {
		$step_title = $event->get_step_title();

		if ( ! $step_title ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'step deleted', 'status', 'groundhogg' ) );
		}

		return sprintf( "<a href='%s'>%s</a>",
			admin_url( sprintf( 'admin.php?page=gh_events&step_id=%d&event_type=%s&status=%s', $event->get_step_id(), $event->get_event_type(), $this->get_view() ) ),
			$step_title );

	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_time( $event ) {
		$status = $event->get_status();

		switch ( $status ) {
			default:
			case 'waiting':
				$time_prefix = _x( 'Will run', 'status', 'groundhogg' );
				break;
			case 'cancelled':
				$time_prefix = _x( 'Cancelled', 'status', 'groundhogg' );
				break;
			case 'skipped':
				$time_prefix = _x( 'Skipped', 'status', 'groundhogg' );
				break;
			case 'complete':
				$time_prefix = _x( 'Processed', 'status', 'groundhogg' );
				break;
			case 'failed':
				$time_prefix = _x( 'Failed', 'status', 'groundhogg' );
				break;
		}

		return $time_prefix . '&nbsp;' . scheduled_time_column( $event->get_time(), true, $event->get_contact() );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_error_code( $event ) {
		return $event->get_error_code() ? '<b>' . esc_html( strtolower( $event->get_error_code() ) ) . '</b>' : '&#x2014;';
	}


	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_error_message( $event ) {
		return $event->get_error_message() ? '<b>' . esc_html( strtolower( $event->get_error_message() ) ) . '</b>' : '&#x2014;';
	}

	/**
	 * Get default column value.
	 *
	 * @param Event $event A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $event, $column_name ) {

		do_action( 'groundhogg_events_custom_column', $event, $column_name );

		return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $event A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $event ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$event->ID           // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [];

		switch ( $this->get_view() ) {
			default:
			case 'waiting':
				$actions['execute_now'] = _x( 'Run Now', 'List table bulk action', 'wp-funnels' );
				$actions['cancel']      = _x( 'Cancel', 'List table bulk action', 'wp-funnels' );
				break;
			case 'complete':
			case 'skipped':
			case 'cancelled':
				$actions['uncancel'] = _x( 'Uncancel', 'List table bulk action', 'wp-funnels' );
				break;
			case 'failed':
				$actions['execute_again'] = _x( 'Run Again', 'List table bulk action', 'wp-funnels' );
				break;
		}

		return apply_filters( 'groundhogg_event_bulk_actions', $actions );
	}

	protected function get_view() {
		return get_url_var( 'status', 'waiting' );
	}

	protected function get_views() {
		$base_url = admin_url( 'admin.php?page=gh_events&status=' );

		$view = $this->get_view();

		$count = array(
			'waiting'   => get_db( 'event_queue' )->count( array( 'status' => 'waiting' ) ),
			'skipped'   => get_db( 'events' )->count( array( 'status' => 'skipped' ) ),
			'cancelled' => get_db( 'events' )->count( array( 'status' => 'cancelled' ) ),
			'completed' => get_db( 'events' )->count( array( 'status' => 'complete' ) ),
			'failed'    => get_db( 'events' )->count( array( 'status' => 'failed' ) )
		);

		return apply_filters( 'gh_event_views', array(
			'waiting'   => "<a class='" . ( $view === 'waiting' ? 'current' : '' ) . "' href='" . $base_url . "waiting" . "'>" . _x( 'Waiting', 'view', 'groundhogg' ) . ' <span class="count">(' . $count['waiting'] . ')</span>' . "</a>",
			'completed' => "<a class='" . ( $view === 'complete' ? 'current' : '' ) . "' href='" . $base_url . "complete" . "'>" . _x( 'Completed', 'view', 'groundhogg' ) . ' <span class="count">(' . $count['completed'] . ')</span>' . "</a>",
			'skipped'   => "<a class='" . ( $view === 'skipped' ? 'current' : '' ) . "' href='" . $base_url . "skipped" . "'>" . _x( 'Skipped', 'view', 'groundhogg' ) . ' <span class="count">(' . $count['skipped'] . ')</span>' . "</a>",
			'cancelled' => "<a class='" . ( $view === 'cancelled' ? 'current' : '' ) . "' href='" . $base_url . "cancelled" . "'>" . _x( 'Cancelled', 'view', 'groundhogg' ) . ' <span class="count">(' . $count['cancelled'] . ')</span>' . "</a>",
			'failed'    => "<a class='" . ( $view === 'failed' ? 'current' : '' ) . "' href='" . $base_url . "failed" . "'>" . _x( 'Failed', 'view', 'groundhogg' ) . ' <span class="count">(' . $count['failed'] . ')</span>' . "</a>"
		) );
	}

	/**
	 * Generates and displays row actions.
	 *
	 * @param Event $event Event being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $event, $column_name, $primary ) {

		$actions = [];

		if ( $primary === $column_name ) {

			$actions = array();

			switch ( $this->get_view() ) {
				default:
				case 'waiting':
					$actions['execute_now'] = html()->e( 'a', [
						'href' => action_url( 'execute_now', [ 'event' => $event->get_id() ] ),
					], __( 'Run now', 'groundhogg' ) );
					$actions['cancel']      = html()->e( 'a', [
						'href' => action_url( 'cancel', [ 'event' => $event->get_id() ] ),
					], __( 'Cancel', 'groundhogg' ) );
					break;
				case 'cancelled':
					$actions['uncancel'] = html()->e( 'a', [
						'href' => action_url( 'uncancel', [ 'event' => $event->get_id() ] ),
					], __( 'Uncancel', 'groundhogg' ) );
					break;
				case 'complete':
				case 'skipped':
				case 'failed':
					$actions['execute_again'] = html()->e( 'a', [
						'href' => action_url( 'execute_again', [ 'event' => $event->get_id(), 'status' => $this->get_view() ] ),
					], __( 'Run Again', 'groundhogg' ) );
					break;
			}


			if ( $event->get_contact() && $event->get_contact()->exists() ) {
				$actions['view'] = sprintf( "<a class='edit' href='%s' aria-label='%s'>%s</a>",
					admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $event->get_contact()->get_id() ),
					esc_attr( _x( 'View Contact', 'action', 'groundhogg' ) ),
					_x( 'View Contact', 'action', 'groundhogg' )
				);
			}
		} else if ( $column_name === 'funnel' ) {

			if ( $event->is_funnel_event() ) {
				$actions['edit'] = sprintf( "<a class='edit' href='%s' aria-label='%s'>%s</a>",
					admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $event->get_funnel_id() ),
					esc_attr( _x( 'Edit Funnel', 'action', 'groundhogg' ) ),
					_x( 'Edit Funnel', 'action', 'groundhogg' )
				);

			}

		} else if ( $column_name === 'step' ) {

			if ( $event->is_funnel_event() ) {
				$actions['edit'] = sprintf( "<a class='edit' href='%s' aria-label='%s'>%s</a>",
					admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%d#%d', $event->get_funnel_id(), $event->get_step_id() ) ),
					esc_attr( _x( 'Edit Step', 'action', 'groundhogg' ) ),
					_x( 'Edit Step', 'action', 'groundhogg' )
				);
			}
		}

		return $this->row_actions( apply_filters( 'groundhogg_event_row_actions', $actions, $event, $column_name ) );
	}

	protected function extra_tablenav( $which ) {

		?>
        <div class="alignleft gh-actions">
            <a class="button action"
               href="<?php echo Plugin::instance()->bulk_jobs->process_events->get_start_url(); ?>"><?php _ex( 'Process Events', 'action', 'groundhogg' ); ?></a>
            <a class="button action"
               href="<?php echo wp_nonce_url( add_query_arg( [ 'action' => 'cleanup' ], $_SERVER['REQUEST_URI'] ), 'cleanup' ); ?>"><?php _ex( 'Cleanup', 'action', 'groundhogg' ); ?></a>
			<?php if ( $this->get_view() === 'failed' ): ?>
                <a class="button action"
                   href="<?php echo wp_nonce_url( add_query_arg( [ 'action' => 'purge' ], $_SERVER['REQUEST_URI'] ), 'purge' ); ?>"><?php _ex( 'Purge failed events', 'action', 'groundhogg' ); ?></a>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Prepares the list of items for displaying.
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 */
	function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = absint( get_url_var( 'limit', get_screen_option( 'per_page' ) ) );
		$paged    = $this->get_pagenum();
		$offset   = $per_page * ( $paged - 1 );
//        $search  = get_url_var( 's' );
		$order   = get_url_var( 'order', 'DESC' );
		$orderby = get_url_var( 'orderby', 'time' );

		$where = [
			'relationship' => "AND",
			[ 'col' => 'status', 'val' => $this->get_view(), 'compare' => '=' ],
		];

		$request_query = get_request_query( [], [], array_keys( get_db( 'events' )->get_columns() ) );

		unset( $request_query['status'] );

		if ( ! empty( $request_query ) ) {
			foreach ( $request_query as $key => $value ) {
				$where[] = [ 'col' => $key, 'val' => $value, 'compare' => '=' ];
			}
		}

		$args = array(
			'where'   => $where,
			'limit'   => $per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);

		$this->table = $this->get_view() === Event::WAITING ? 'event_queue' : 'events';

		$events = get_db( $this->table )->query( $args );
		$total  = get_db( $this->table )->count( $args );

		$this->items = $events;

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $per_page ? ceil( (int) $total / (int) $per_page ) : 1;

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		) );
	}


}