<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Preferences;
use function Groundhogg\isset_not_empty;
use Groundhogg\Contact_Query;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact DB
 *
 * Store contact info
 *
 * @since       File available since Release 0.1
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Contacts extends DB {

	/**
	 * The metadata type.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $meta_type = 'contact';

	/**
	 * The name of the date column.
	 *
	 * @access public
	 * @since  2.8
	 * @var string
	 */
	public $date_key = 'date_created';

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_contacts';
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return 'ID';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'contact';
	}

	/**
	 * Update contact record when user profile updated.
	 */
	protected function add_additional_actions() {
		add_action( 'profile_update', array( $this, 'update_contact_on_user_update' ), 10, 2 );
		parent::add_additional_actions();
	}

	/**
	 * Get columns and formats
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_columns() {
		return array(
			'ID'                        => '%d',
			'email'                     => '%s',
			'first_name'                => '%s',
			'last_name'                 => '%s',
			'user_id'                   => '%d',
			'owner_id'                  => '%d',
			'optin_status'              => '%d',
			'date_created'              => '%s',
			'date_optin_status_changed' => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return array(
			'ID'                        => 0,
			'email'                     => '',
			'first_name'                => '',
			'last_name'                 => '',
			'user_id'                   => 0,
			'owner_id'                  => 0,
			'optin_status'              => Preferences::UNCONFIRMED,
			'date_created'              => current_time( 'mysql' ),
			'date_optin_status_changed' => current_time( 'mysql' ),
		);
	}

	/**
	 * Add a contact
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		if ( empty( $args['email'] ) ) {
			return false;
		}

		$args = $this->sanitize_columns( $args );

		if ( $this->exists( $args['email'], 'email' ) ) {

			// update an existing contact
			$contact = $this->get_contact_by( 'email', $args['email'] );

			$contact_id = absint( $contact->ID );

			$this->update( $contact_id, $data );
			$result = $contact_id;

		} else {
			$result = $this->insert( $args );
		}

		return $result;

	}

	/**
	 * Insert a new contact
	 *
	 * @access  public
	 * @since   2.1
	 * @return  int
	 */
	public function insert( $data ) {
		$result = parent::insert( $data );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Update a contact
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function update( $row_id = 0, $data = [], $where = [] ) {

		$data = $this->sanitize_columns( $data );

		// Check for duplicate email.
		if ( isset_not_empty( $data, 'email' ) && $this->exists( $data['email'], 'email' ) ) {
			$a_row_id = absint( $this->get_contact_by( 'email', $data['email'] )->ID );
			if ( $a_row_id !== $row_id ) {
				// unset instead of return false;
				unset( $data['email'] );
			}
		}

		$result = parent::update( $row_id, $data, $where );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Checks if a contact exists
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function exists( $value = '', $field = 'email' ) {
		return parent::exists( $value, $field );
	}

	/**
	 * Updates the email address of a contact record when the email on a user is updated
	 *
	 * @access  public
	 * @since   2.4
	 */
	public function update_contact_on_user_update( $user_id = 0, $old_user_data = '' ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$contact = $this->get_contact_by( 'user_id', $user_id );

		if ( ! $contact ) {

			// get by email if UID fails.
			$contact = $this->get_contact_by( 'email', $user->user_email );
			if ( ! $contact ) {
				return false;
			}

		}

		$args = [
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
			'user_id'    => $user_id
		];

		$args = $this->sanitize_columns( $args );

		if ( $this->update( $contact->ID, $args ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves a single contact from the database
	 *
	 * @access public
	 * @since  2.3
	 *
	 * @param string $field id or email
	 * @param mixed  $value The Customer ID or email to search
	 *
	 * @return mixed          Upon success, an object of the contact. Upon failure, NULL
	 */
	public function get_contact_by( $field = 'ID', $value = 0 ) {
		if ( empty( $field ) || empty( $value ) ) {
			return null;
		}

		return parent::get_by( $field, $value );
	}

	/**
	 * Use contact query calss
	 *
	 * @param array  $data
	 * @param string $order
	 *
	 * @return array|bool|object|null
	 */
	public function query( $data = [], $order = '', $from_cache = false ) {
		$data  = $this->prepare_contact_query_args( $data );
		$query = new Contact_Query();

		return $query->query( $data );
	}

	/**
	 * Retrieve contacts from the database
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function get_contacts( $args = array() ) {
		$args          = $this->prepare_contact_query_args( $args );
		$args['count'] = false;

		$query = new Contact_Query( '', $this );

		return $query->query( $args );
	}

	/**
	 * Count the total number of contacts in the database
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function count( $args = array() ) {
		$args           = $this->prepare_contact_query_args( $args );
		$args['count']  = true;
		$args['offset'] = 0;

		$query   = new Contact_Query( '', $this );
		$results = $query->query( $args );

		return $results;
	}

	/**
	 * Prepare query arguments for `WPGH_Contact_Query`.
	 *
	 * This method ensures that old arguments transition seamlessly to the new system.
	 *
	 * @access protected
	 * @since  2.8
	 *
	 * @param array $args Arguments for `WPGH_Contact_Query`.
	 *
	 * @return array Prepared arguments.
	 */
	protected function prepare_contact_query_args( $args ) {
		if ( ! empty( $args['ID'] ) ) {
			$args['include'] = $args['ID'];
			unset( $args['ID'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$args['users_include'] = $args['user_id'];
			unset( $args['user_id'] );
		}

		if ( ! empty( $args['date'] ) ) {
			$date_query = array( 'relation' => 'AND' );

			if ( is_array( $args['date'] ) ) {
				$date_query[] = array(
					'after'     => date( 'Y-m-d 00:00:00', strtotime( $args['date']['start'] ) ),
					'inclusive' => true,
				);
				$date_query[] = array(
					'before'    => date( 'Y-m-d 23:59:59', strtotime( $args['date']['end'] ) ),
					'inclusive' => true,
				);
			} else {
				$date_query[] = array(
					'year'  => date( 'Y', strtotime( $args['date'] ) ),
					'month' => date( 'm', strtotime( $args['date'] ) ),
					'day'   => date( 'd', strtotime( $args['date'] ) ),
				);
			}

			if ( empty( $args['date_query'] ) ) {
				$args['date_query'] = $date_query;
			} else {
				$args['date_query'] = array(
					'relation' => 'AND',
					$date_query,
					$args['date_query'],
				);
			}

			unset( $args['date'] );
		}

		return $args;
	}

	/**
	 * Create the table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		email varchar(50) NOT NULL,
		first_name mediumtext NOT NULL,
		last_name mediumtext NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		owner_id bigint(20) unsigned NOT NULL,
		optin_status int unsigned NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		date_optin_status_changed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (ID),
		UNIQUE KEY email (email),
		KEY user (user_id),
		KEY owner_id (owner_id),
		KEY optin_status (optin_status),
		KEY date_created (date_created),
		KEY date_optin_status_changed (date_optin_status_changed)
		) {$this->get_charset_collate()};";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Sanitize the given columns
	 *
	 * @param $cols
	 *
	 * @return array
	 */
	public function sanitize_columns( $cols ) {

		foreach ( $cols as $key => $val ) {

			switch ( $key ) {

				case 'first_name':
				case 'last_name' :
					$cols[ $key ] = sanitize_text_field( $val );
					break;
				case 'email':
					$cols[ $key ] = strtolower( sanitize_email( $val ) );
					break;
				case 'optin_status':
				case 'owner_id':
				case 'user_id':
					$cols[ $key ] = absint( $val );
					break;
			}

		}

		return $cols;

	}
}