<?php

namespace Groundhogg;

// Exit if accessed directly
use Groundhogg\DB\Contacts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact query class
 *
 * This class should be used for querying contacts.
 *
 * @since       0.9
 * @copyright   Copyright (c) 2018, Groundhogg Inc. (modified from EDD)
 * @license     http://opensource.org/licenses/gpl-3.0 GNU Public License
 * @package     Includes
 */
class Contact_Query {

	/**
	 * SQL for database query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    string
	 */
	public $request;

	/**
	 * Date query container.
	 *
	 * @access public
	 * @since  2.8
	 * @var    object \WP_Date_Query
	 */
	public $date_query = false;

	/**
	 * Meta query container.
	 *
	 * @access public
	 * @since  2.8
	 * @var    object \WP_Meta_Query
	 */
	public $meta_query = false;

	/**
	 * @var Tag_Query
	 */
	public $tag_query = null;

	/**
	 * Query vars set by the user.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $query_var_defaults;

	/**
	 * List of contacts located by the query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    array
	 */
	public $items;

	/**
	 * The amount of found contacts for the current query.
	 *
	 * @access public
	 * @since  2.8
	 * @var    int
	 */
	public $found_items = 0;

	/**
	 * The number of pages.
	 *
	 * @access public
	 * @since  2.8
	 * @var    int
	 */
	public $max_num_pages = 0;

	/**
	 * SQL query clauses.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    array
	 */
	protected $sql_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'groupby' => '',
		'orderby' => '',
		'limits'  => '',
	);

	/**
	 * Metadata query clauses.
	 *
	 * @access protected
	 * @since  2.8
	 * @var array
	 */
	protected $meta_query_clauses = array();

	/**
	 * Tag query clauses
	 *
	 * @var array
	 */
	protected $tag_query_clauses = array();

	/**
	 * WPGH_DB_Contacts instance.
	 *
	 * @access protected
	 * @since  2.8
	 * @var Contacts
	 */
	protected $gh_db_contacts;

	/**
	 * The name of our database table.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $table_name;

	/**
	 * The meta type.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $meta_type;

	/**
	 * The name of the primary column.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $primary_key;

	/**
	 * The name of the date column.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $date_key;

	/**
	 * The name of the cache group.
	 *
	 * @access protected
	 * @since  2.8
	 * @var    string
	 */
	protected $cache_group;

	/**
	 * Constructor.
	 *
	 * Sets up the contact query defaults and optionally runs a query.
	 *
	 * @access public
	 *
	 * @param string|array $query {
	 *                                     Optional. Array or query string of contact query parameters. Default empty.
	 *
	 * @type int $number Maximum number of contacts to retrieve. Default 20.
	 * @type int $offset Number of contacts to offset the query. Default 0.
	 * @type string|array $orderby Customer status or array of statuses. To use 'meta_value'
	 *                                        or 'meta_value_num', `$meta_key` must also be provided.
	 *                                        To sort by a specific `$meta_query` clause, use that
	 *                                        clause's array key. Accepts 'ID', 'user_id', 'first_name',
	 *                                        'last_name', 'optin_status',
	 *                                        'notes', 'date_created', 'meta_value', 'meta_value_num',
	 *                                        the value of `$meta_key`, and the array keys of `$meta_query`.
	 *                                        Also accepts false, an empty array, or 'none' to disable the
	 *                                        `ORDER BY` clause. Default 'ID'.
	 * @type string $order How to order retrieved contacts. Accepts 'ASC', 'DESC'.
	 *                                        Default 'DESC'.
	 * @type string|array $include String or array of contact IDs to include. Default empty.
	 * @type string|array $exclude String or array of contact IDs to exclude. Default empty.
	 * @type string|array $users_include String or array of contact user IDs to include. Default
	 *                                        empty.
	 * @type string|array $users_exclude String or array of contact user IDs to exclude. Default
	 *                                        empty.
	 * @type string|array $tags_include String or array of tags the contact should have
	 * @type string|array $tags_exclude String or array of tags the contact should not have
	 * @type string|array $email Limit results to those contacts affiliated with one of
	 *                                        the given emails. Default empty.
	 * @type string|array $report array of args for an activity report.
	 * @type string $search Search term(s) to retrieve matching contacts for. Searches
	 *                                        through contact names. Default empty.
	 * @type string|array $search_columns Columns to search using the value of `$search`. Default 'first_name'.
	 * @type string $meta_key Include contacts with a matching contact meta key.
	 *                                        Default empty.
	 * @type string $meta_value Include contacts with a matching contact meta value.
	 *                                        Requires `$meta_key` to be set. Default empty.
	 * @type array $meta_query Meta query clauses to limit retrieved contacts by.
	 *                                        See `WP_Meta_Query`. Default empty.
	 * @type array $date_query Date query clauses to limit retrieved contacts by.
	 *                                        See `WP_Date_Query`. Default empty.
	 * @type bool $count Whether to return a count (true) instead of an array of
	 *                                        contact objects. Default false.
	 * @type bool $no_found_rows Whether to disable the `SQL_CALC_FOUND_ROWS` query.
	 *                                        Default true.
	 * }
	 * @since  2.8
	 *
	 */
	public function __construct( $query = '', $gh_db_contacts = null ) {
		if ( $gh_db_contacts ) {
			$this->gh_db_contacts = $gh_db_contacts;
		} else {
			$this->gh_db_contacts = Plugin::$instance->dbs->get_db( 'contacts' );
		}

		$this->table_name  = $this->gh_db_contacts->get_table_name();
		$this->meta_type   = $this->gh_db_contacts->get_object_type();
		$this->primary_key = $this->gh_db_contacts->get_primary_key();
		$this->date_key    = $this->gh_db_contacts->get_date_key();
		$this->cache_group = $this->gh_db_contacts->get_cache_group();

		$this->query_var_defaults = array(
			'number'                 => - 1,
			'offset'                 => 0,
			'orderby'                => 'ID',
			'order'                  => 'DESC',
			'include'                => '',
			'exclude'                => '',
			'users_include'          => '',
			'users_exclude'          => '',
			'tags_include'           => 0,
			'tags_include_needs_all' => false,
			'tags_exclude'           => 0,
			'tags_exclude_needs_all' => false,
			'tags_relation'          => 'AND',
			'tag_query'              => [],
			'optin_status'           => 'any',
			'owner'                  => 0,
			'report'                 => false,
			'activity'               => false,
			'email'                  => '',
			'email_compare'          => '',
			'search'                 => '',
			'first_name'             => '',
			'first_name_compare'     => '',
			'last_name'              => '',
			'last_name_compare'      => '',
			'search_columns'         => array(),
			'meta_key'               => '',
			'meta_value'             => '',
			'meta_compare'           => '=',
			'meta_query'             => '',
			'date_query'             => null,
			'count'                  => false,
			'no_found_rows'          => true,
		);

		// Only show contacts associated with the current owner...
		if ( current_user_can( 'view_contacts' ) && ! current_user_can( 'view_all_contacts' ) ){
			$this->query_var_defaults[ 'owner' ] = get_current_user_id();
		}

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Sets up the query for retrieving contacts.
	 *
	 * @access public
	 *
	 * @param string|array $query Array or query string of parameters. See WPGH_Contact_Query::__construct().
	 *
	 * @return array|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 * @see    WPGH_Contact_Query::__construct()
	 *
	 */
	public function query( $query ) {
		$this->query_vars = wp_parse_args( $query );
		$items            = $this->get_items();

		return $items;
	}

	/**
	 * Set the date key
	 *
	 * @param $key
	 */
	public function set_date_key( $key ) {
		$this->date_key = $key;
	}

	/**
	 * Parses arguments passed to the contact query with default query parameters.
	 *
	 * @access protected
	 * @since  2.8
	 */
	protected function parse_query() {
		$this->query_vars = wp_parse_args( $this->query_vars, $this->query_var_defaults );

		if ( $this->query_vars['number'] < 1 ) {
			$this->query_vars['number'] = false;
		}

		$this->query_vars['offset'] = absint( $this->query_vars['offset'] );

		if ( ! empty( $this->query_vars['date_query'] ) && is_array( $this->query_vars['date_query'] ) ) {
			$this->date_query = new \WP_Date_Query( $this->query_vars['date_query'], $this->table_name . '.' . $this->date_key );
		}

		$this->meta_query = new \WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->query_vars );

		if ( ! empty( $this->meta_query->queries ) ) {
			$this->meta_query_clauses = $this->meta_query->get_sql( $this->meta_type, $this->table_name, $this->primary_key, $this );
		}

		if ( ! empty( $this->query_vars['tags_include'] ) || ! empty( $this->query_vars['tags_exclude'] ) || ! empty( $this->query_vars['tag_query'] ) ) {

			$backup_query = [
				'relation' => $this->query_vars['tags_relation'],
			];

			if ( ! empty( $this->query_vars['tags_include'] ) ) {

				if ( ! empty( $this->query_vars['tags_include_needs_all'] ) ) {

					if ( ! is_array( $this->query_vars['tags_include'] ) ) {
						$this->query_vars['tags_include'] = explode( ',', $this->query_vars['tags_include'] );
					}

					foreach ( $this->query_vars['tags_include'] as $tag ) {
						$backup_query[] = [
							'tags'     => $tag,
							'field'    => 'tag_id',
							'operator' => 'IN',
						];
					}

				} else {
					$backup_query[] = [
						'tags'     => $this->query_vars['tags_include'],
						'field'    => 'tag_id',
						'operator' => 'IN',
					];
				}
			}

			if ( ! empty( $this->query_vars['tags_exclude'] ) ) {

				if ( ! empty( $this->query_vars['tags_exclude_needs_all'] ) ) {

					if ( ! is_array( $this->query_vars['tags_exclude'] ) ) {
						$this->query_vars['tags_exclude'] = explode( ',', $this->query_vars['tags_exclude'] );
					}

					foreach ( $this->query_vars['tags_exclude'] as $tag ) {
						$backup_query[] = [
							'tags'     => $tag,
							'field'    => 'tag_id',
							'operator' => 'NOT IN',
						];
					}

				} else {
					$backup_query[] = [
						'tags'     => $this->query_vars['tags_exclude'],
						'field'    => 'tag_id',
						'operator' => 'NOT IN',
					];
				}
			}

			$query = ( ! empty( $this->query_vars['tag_query'] ) ) ? $this->query_vars['tag_query'] : $backup_query;

			$this->tag_query = new Tag_Query( $query );

			if ( ! empty( $this->tag_query->queries ) ) {
				$this->tag_query_clauses = $this->tag_query->get_sql( $this->table_name, $this->primary_key );
			}
		}

		/**
		 * Fires after the contact query vars have been parsed.
		 *
		 * @param Contact_Query &$this The WPGH_Contact_Query instance (passed by reference).
		 *
		 * @since 2.8
		 *
		 */
		do_action_ref_array( 'gh_parse_contact_query', array( &$this ) );
	}

	/**
	 * Retrieves a list of contacts matching the query vars.
	 *
	 * Tries to use a cached value and otherwise uses `WPGH_Contact_Query::query_items()`.
	 *
	 * @access protected
	 * @return array|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 */
	protected function get_items() {
		$this->parse_query();

		/**
		 * Fires before contacts are retrieved.
		 *
		 * @param Contact_Query &$this Current instance of WPGH_Contact_Query, passed by reference.
		 *
		 * @since 2.8
		 *
		 */
		do_action_ref_array( 'gh_pre_get_contacts', array( &$this ) );

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$key = md5( serialize( wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) ) ) );

		$last_changed = $this->gh_db_contacts->get_last_changed();

		$cache_key   = "query:$key:$last_changed:$this->date_key";
		$cache_value = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $cache_value ) {
			$items = $this->query_items();

			if ( $items ) {
				$this->set_found_items();
			}

			$cache_value = array(
				'items'       => $items,
				'found_items' => $this->found_items,
			);
			wp_cache_add( $cache_key, $cache_value, $this->cache_group );
		} else {
			$items             = $cache_value['items'];
			$this->found_items = $cache_value['found_items'];
		}

		if ( $this->found_items && $this->query_vars['number'] ) {
			$this->max_num_pages = ceil( $this->found_items / $this->query_vars['number'] );
		}

		// If querying for a count only, there's nothing more to do.
		if ( $this->query_vars['count'] ) {

			// Count items will be an array of counts, so return the number of counts.
			if ( ! empty( $this->sql_clauses['groupby'] ) ) {
				return count( $items );
			}

			// $items is actually a count in this case.
			return intval( $items[0]->count );
		}

		$this->items = $items;

		return $this->items;
	}

	/**
	 * Runs a database query to retrieve contacts.
	 *
	 * @access protected
	 * @return array|int List of contacts, or number of contacts when 'count' is passed as a query var.
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 */
	protected function query_items() {
		global $wpdb;

		$fields = $this->construct_request_fields();
		$join   = $this->construct_request_join();

		$this->sql_clauses['where'] = $this->construct_request_where();

		$orderby = $this->construct_request_orderby();
		$limits  = $this->construct_request_limits();
		$groupby = $this->construct_request_groupby();

		$found_rows = ! $this->query_vars['no_found_rows'] ? 'SQL_CALC_FOUND_ROWS' : '';

		$where = implode( ' AND ', $this->sql_clauses['where'] );

		if ( $where ) {
			$where = "WHERE $where";
		}

		if ( $orderby ) {
			$orderby = "ORDER BY $orderby";
		}

		if ( $groupby ) {
			$groupby = "GROUP BY $groupby";
		}

		$this->sql_clauses['select'] = "SELECT $found_rows $fields";
		$this->sql_clauses['from']   = "FROM $this->table_name $join";

		// No need for this in count.
		$this->sql_clauses['groupby'] = $groupby;

		if ( ! $this->query_vars['count'] ) {
			$this->sql_clauses['orderby'] = $orderby;
		}

		$this->sql_clauses['limits'] = $limits;

		$this->request = "{$this->sql_clauses['select']} {$this->sql_clauses['from']} {$where} {$this->sql_clauses['groupby']} {$this->sql_clauses['orderby']} {$this->sql_clauses['limits']}";

		$results = $wpdb->get_results( $this->request );

		return $results;
	}

	/**
	 * Populates the found_items property for the current query if the limit clause was used.
	 *
	 * @access protected
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	protected function set_found_items() {
		global $wpdb;

		if ( $this->query_vars['number'] && ! $this->query_vars['no_found_rows'] ) {
			/**
			 * Filters the query used to retrieve the count of found contacts.
			 *
			 * @param Contact_Query $contact_query The `WPGH_Contact_Query` instance.
			 * @param string $found_contacts_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 *
			 * @since 2.8
			 *
			 */
			$found_items_query = apply_filters( 'gh_found_contacts_query', 'SELECT FOUND_ROWS()', $this );

			$this->found_items = (int) $wpdb->get_var( $found_items_query );
		}
	}

	/**
	 * Constructs the fields segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL fields segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_fields() {
		if ( $this->query_vars['count'] ) {
			return "COUNT($this->table_name.$this->primary_key) AS count";
		}

		return "$this->table_name.*";
	}

	/**
	 * Constructs the join segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL join segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_join() {
		$join = '';

		if ( ! empty( $this->meta_query_clauses['join'] ) ) {
			$join .= $this->meta_query_clauses['join'];
		}

		if ( ! empty( $this->tag_query_clauses['join'] ) ) {
			$join .= $this->tag_query_clauses['join'];
		}

		if ( ! empty( $this->query_vars['email'] ) && ! is_array( $this->query_vars['email'] ) ) {
			$meta_table = _get_meta_table( $this->meta_type );

			$join_type = false !== strpos( $join, 'INNER JOIN' ) ? 'INNER JOIN' : 'LEFT JOIN';

			$join .= " $join_type $meta_table AS email_mt ON $this->table_name.$this->primary_key = email_mt.{$this->meta_type}_id";
		}

		return $join;
	}

	/**
	 * Constructs the where segment of the SQL request.
	 *
	 * @access protected
	 * @return array SQL where segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_where() {
		global $wpdb;

		$where = array();

		if ( ! empty( $this->query_vars['include'] ) ) {
			$include_ids      = implode( ',', wp_parse_id_list( $this->query_vars['include'] ) );
			$where['include'] = "$this->primary_key IN ( $include_ids )";
		}

		if ( ! empty( $this->query_vars['exclude'] ) ) {
			$exclude_ids      = implode( ',', wp_parse_id_list( $this->query_vars['exclude'] ) );
			$where['exclude'] = "$this->primary_key NOT IN ( $exclude_ids )";
		}

		if ( ! empty( $this->query_vars['users_include'] ) ) {
			$users_include_ids      = implode( ',', wp_parse_id_list( $this->query_vars['users_include'] ) );
			$where['users_include'] = "user_id IN ( $users_include_ids )";
		}

		if ( ! empty( $this->query_vars['users_exclude'] ) ) {
			$users_exclude_ids      = implode( ',', wp_parse_id_list( $this->query_vars['users_exclude'] ) );
			$where['users_exclude'] = "user_id NOT IN ( $users_exclude_ids )";
		}

		if ( $this->query_vars['optin_status'] !== 'any' ) {

			if ( is_array( $this->query_vars['optin_status'] ) ) {
				$this->query_vars['optin_status'] = implode( ',', wp_parse_id_list( $this->query_vars['optin_status'] ) );
			} else {
				$this->query_vars['optin_status'] = absint( $this->query_vars['optin_status'] );
			}

			$where['optin_status'] = "optin_status in ( {$this->query_vars['optin_status']} )";
		}

		if ( $this->query_vars['owner'] ) {
			$where['owner'] = "owner_id IN ( {$this->query_vars['owner']} )";
		}

		if ( strlen( $this->query_vars['email'] ) ) {
			$search_email   = $this->compare_string( $this->query_vars['email'], $this->query_vars['email_compare'] );
			$where['email'] = $this->get_search_sql( $search_email, array( 'email' ) );
		}

		if ( $this->query_vars['report'] && is_array( $this->query_vars['report'] ) ) {

			$map = [
				'step'   => 'step_id',
				'funnel' => 'funnel_id',
				'start'  => 'after',
				'end'    => 'before',
				'type'   => 'event_type',
			];

			foreach ( $map as $old_key => $new_key ) {
				if ( $val = get_array_var( $this->query_vars['report'], $old_key ) ) {
					$this->query_vars['report'][ $new_key ] = $val;
				}
			}

			$subwhere = [ 'relationship' => 'AND' ];

			foreach ( $this->query_vars['report'] as $col => $val ) {

				if ( ! empty( $val ) ) {
					switch ( $col ) {
						default:
							$compare = '=';
							break;
						case 'before':
							$compare = '<=';
							$col     = 'time';
							break;
						case 'after':
							$compare = '>=';
							$col     = 'time';
							break;
					}

					$subwhere[] = [ 'col' => $col, 'val' => $val, 'compare' => $compare ];
				}

			}

			$table = get_array_var( $this->query_vars[ 'report' ], 'status' ) === Event::WAITING ? 'event_queue' : 'events';

			$sql = get_db( $table )->get_sql( [
				'where'   => $subwhere,
				'select'  => 'contact_id',
				'orderby' => false,
				'order'   => ''
			] );

			$where['report'] = "$this->table_name.$this->primary_key IN ( $sql )";
		}

		if ( $this->query_vars['activity'] && is_array( $this->query_vars['activity'] ) ) {

			$map = [
				'step'   => 'step_id',
				'funnel' => 'funnel_id',
				'start'  => 'after',
				'end'    => 'before'
			];

			foreach ( $map as $old_key => $new_key ) {
				if ( $val = get_array_var( $this->query_vars['activity'], $old_key ) ) {
					$this->query_vars['activity'][ $new_key ] = $val;
				}
			}

			$subwhere = [ 'relationship' => 'AND' ];

			foreach ( $this->query_vars['activity'] as $col => $val ) {

				if ( ! empty( $val ) ) {
					switch ( $col ) {
						default:
							$compare = '=';
							break;
						case 'before':
							$compare = '<=';
							$col     = 'timestamp';
							break;
						case 'after':
							$compare = '>=';
							$col     = 'timestamp';
							break;
					}

					$subwhere[] = [ 'col' => $col, 'val' => $val, 'compare' => $compare ];
				}

			}

			$sql = get_db( 'activity' )->get_sql( [
				'where'   => $subwhere,
				'select'  => 'contact_id',
				'orderby' => false,
				'order'   => ''
			] );

			$where['activity'] = "$this->table_name.$this->primary_key IN ( $sql )";
		}

		if ( strlen( $this->query_vars['search'] ) ) {
			if ( ! empty( $this->query_vars['search_columns'] ) ) {
				$search_columns = array_map( 'sanitize_key', (array) $this->query_vars['search_columns'] );
			} else {
				$search_columns = array( 'first_name', 'last_name', 'email' );
			}

			$where['search'] = $this->get_search_sql( $this->query_vars['search'], $search_columns );
		}

		if ( strlen( $this->query_vars['first_name'] ) ) {

			$search_first        = $this->compare_string( $this->query_vars['first_name'], $this->query_vars['first_name_compare'] );
			$where['first_name'] = $this->get_search_sql( $search_first, array( 'first_name' ) );
		}

		if ( strlen( $this->query_vars['last_name'] ) ) {
			$search_last        = $this->compare_string( $this->query_vars['last_name'], $this->query_vars['last_name_compare'] );
			$where['last_name'] = $this->get_search_sql( $search_last, array( 'last_name' ) );
		}


		if ( $this->date_query ) {
			$where['date_query'] = preg_replace( '/^\s*AND\s*/', '', $this->date_query->get_sql() );
		}

		if ( ! empty( $this->meta_query_clauses['where'] ) ) {
			$where['meta_query'] = preg_replace( '/^\s*AND\s*/', '', $this->meta_query_clauses['where'] );
		}

		if ( ! empty( $this->tag_query_clauses['where'] ) ) {
			$where['tax_query'] = preg_replace( '/^\s*AND\s*/', '', $this->tag_query_clauses['where'] );
		}

		return $where;
	}

	/**
	 * Constructs the orderby segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL orderby segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_orderby() {
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			return '';
		}

		if ( empty( $this->query_vars['orderby'] ) ) {
			return $this->primary_key . ' ' . $this->parse_order_string( $this->query_vars['order'], $this->query_vars['orderby'] );
		}

		if ( is_string( $this->query_vars['orderby'] ) ) {
			$ordersby = array( $this->query_vars['orderby'] => $this->query_vars['order'] );
		} else {
			$ordersby = $this->query_vars['orderby'];
		}

		$orderby_array = array();

		foreach ( $ordersby as $orderby => $order ) {
			$parsed_orderby = $this->parse_orderby_string( $orderby );
			if ( ! $parsed_orderby ) {
				continue;
			}

			$parsed_order = $this->parse_order_string( $order, $orderby );

			if ( $parsed_order ) {
				$orderby_array[] = $parsed_orderby . ' ' . $parsed_order;
			} else {
				$orderby_array[] = $parsed_orderby;
			}
		}

		return implode( ', ', $orderby_array );
	}

	/**
	 * Constructs the limits segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL limits segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_limits() {
		if ( $this->query_vars['number'] ) {
			if ( $this->query_vars['offset'] ) {
				return "LIMIT {$this->query_vars['offset']},{$this->query_vars['number']}";
			}

			return "LIMIT {$this->query_vars['number']}";
		}

		return '';
	}

	/**
	 * Constructs the groupby segment of the SQL request.
	 *
	 * @access protected
	 * @return string SQL groupby segment.
	 * @since  2.8
	 *
	 */
	protected function construct_request_groupby() {
		if ( ! empty( $this->meta_query_clauses['join'] )
		     || ! empty( $this->tag_query_clauses['join'] )
		     || ! empty( $this->query_vars['report'] )
		     || ! empty( $this->query_vars['activity'] )
		     || ( ! empty( $this->query_vars['email'] ) && ! is_array( $this->query_vars['email'] ) )
		) {
			return "$this->table_name.$this->primary_key";
		}

		return '';
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @access protected
	 *
	 * @param array $columns Columns to search.
	 * @param string $string Search string.
	 *
	 * @return string Search SQL.
	 * @since  2.8
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 */
	protected function get_search_sql( $string, $columns ) {
		global $wpdb;

		if ( false !== strpos( $string, '**' ) ) {
			$like = str_replace( '**', '%', $string );
		} else if ( false !== strpos( $string, '*' ) ) {
			$like = '%' . implode( '%', array_map( array( $wpdb, 'esc_like' ), explode( '*', $string ) ) ) . '%';
		} else {
			$like = '%' . $wpdb->esc_like( $string ) . '%';
		}

		$searches = array();
		foreach ( $columns as $column ) {
			$searches[] = $wpdb->prepare( "$column LIKE %s", $like );
		}

		return '(' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * @param $val
	 * @param $compare_type
	 *
	 * @return string
	 */
	protected function compare_string( $val, $compare_type ) {
		switch ( $compare_type ) {
			case '':
			case 'equals':
				break;
			case 'contains':
				$val = '**' . $val . '**';
				break;
			case 'starts_with':
				$val = $val . '**';
				break;
			case 'ends_with':
				$val = '**' . $val;
				break;
		}

		return $val;
	}

	/**
	 * Parses a single orderby string.
	 *
	 * @access protected
	 *
	 * @param string $orderby Orderby string.
	 *
	 * @return string Parsed orderby string to use in the SQL request, or an empty string.
	 * @since  2.8
	 *
	 */
	protected function parse_orderby_string( $orderby ) {
		if ( 'include' === $orderby ) {
			if ( empty( $this->query_vars['include'] ) ) {
				return '';
			}

			$ids = implode( ',', wp_parse_id_list( $this->query_vars['include'] ) );

			return "FIELD( $this->table_name.$this->primary_key, $ids )";
		}

		if ( ! empty( $this->meta_query_clauses['where'] ) ) {
			$meta_table = _get_meta_table( $this->meta_type );

			if ( $this->query_vars['meta_key'] === $orderby || 'meta_value' === $orderby ) {
				return "$meta_table.meta_value";
			}

			if ( 'meta_value_num' === $orderby ) {
				return "$meta_table.meta_value+0";
			}

			$meta_query_clauses = $this->meta_query->get_clauses();

			if ( isset( $meta_query_clauses[ $orderby ] ) ) {
				return sprintf( "CAST(%s.meta_value AS %s)", esc_sql( $meta_query_clauses[ $orderby ]['alias'] ), esc_sql( $meta_query_clauses[ $orderby ]['cast'] ) );
			}
		}

		$allowed_keys = $this->get_allowed_orderby_keys();

		if ( in_array( $orderby, $allowed_keys, true ) ) {
			/* This column needs special handling here. */

			return "$this->table_name.$orderby";
		}

		return '';
	}

	/**
	 * Parses a single order string.
	 *
	 * @access protected
	 *
	 * @param string $orderby Order string.
	 *
	 * @return string Parsed order string to use in the SQL request, or an empty string.
	 * @since  2.8
	 *
	 */
	protected function parse_order_string( $order, $orderby ) {
		if ( 'include' === $orderby ) {
			return '';
		}

		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Returns the basic allowed keys to use for the orderby clause.
	 *
	 * @access protected
	 * @return array Allowed keys.
	 * @since  2.8
	 *
	 */
	protected function get_allowed_orderby_keys() {
		return array_keys( $this->gh_db_contacts->get_columns() );
	}
}