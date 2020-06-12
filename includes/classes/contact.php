<?php

namespace Groundhogg;

// Exit if accessed directly
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Tag_Relationships;
use Groundhogg\DB\Tags;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact
 *
 * Lots going on here, to much to cover. Essentially, you have a contact, lost of helper methods, cool stuff.
 * This was originally modified from the EDD_Customer class by easy digital downloads, but quickly came into it's own.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Contact extends Base_Object_With_Meta {

	/**
	 * Tag IDs
	 *
	 * @var int[]
	 */
	protected $tags;

	/**
	 * An instance of the WP User
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var WP_User
	 */
	protected $owner;

	/**
	 * Contact constructor.
	 *
	 * @param bool|int|string|array $_id_or_email_or_args
	 * @param bool                  $by_user_id
	 */
	public function __construct( $_id_or_email_or_args = false, $by_user_id = false ) {
		$field = false;

		if ( ! is_array( $_id_or_email_or_args ) ) {
			if ( false === $_id_or_email_or_args || ( is_numeric( $_id_or_email_or_args ) && (int) $_id_or_email_or_args !== absint( $_id_or_email_or_args ) ) ) {
				return;
			}

			$by_user_id = is_bool( $by_user_id ) ? $by_user_id : false;

			if ( is_numeric( $_id_or_email_or_args ) ) {
				$field = $by_user_id ? 'user_id' : 'ID';
			} else {
				$field = 'email';
			}
		}

		parent::__construct( $_id_or_email_or_args, $field );
	}

	/**
	 * Gets the avatar image.
	 *
	 * @return false|string
	 */
	public function get_profile_picture() {
		if ( $this->profile_picture ) {
			return $this->profile_picture;
		}

		$profile_pic = get_avatar_url( $this->get_email(), [ 'size' => 300 ] );

//        $result = wp_remote_retrieve_body( wp_remote_get( sprintf( 'https://api.devidentify.com/%s', $this->get_email() ) ) );
//        $result = json_decode( $result );

//        $profile_pic = ( isset( $result->success ) && filter_var( $result->success, FILTER_VALIDATE_BOOLEAN ) ) ? $result->profile_picture : get_avatar_url( $this->get_email(), [ 'size' => 300 ] );

		$this->update_meta( 'profile_picture', $profile_pic );

		return $profile_pic;

	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'contacts' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'contactmeta' );
	}

	/**
	 * Get the tags DB
	 *
	 * @return Tags
	 */
	protected function get_tags_db() {
		return get_db( 'tags' );
	}

	/**
	 * Get the tag rel DB
	 *
	 * @return Tag_Relationships
	 */
	protected function get_tag_rel_db() {
		return get_db( 'tag_relationships' );
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'contact';
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		$this->tags  = wp_parse_id_list( $this->get_tag_rel_db()->get_relationships( $this->ID ) );
		$this->user  = get_userdata( $this->get_user_id() );
		$this->owner = get_userdata( $this->get_owner_id() );
	}

	/**
	 * The contact ID
	 *
	 * @return int
	 */
	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * Get the tags
	 *
	 * @return array
	 */
	public function get_tags() {
		return wp_parse_id_list( $this->tags );
	}

	/**
	 * @return array
	 */
	public function get_tags_for_select2() {
		$return = [];

		foreach ( $this->get_tags() as $tag_id ) {
			$tag = new Tag( $tag_id );

			$return[] = [
				'id'   => $tag->get_id(),
				'text' => $tag->get_name()
			];
		}

		return $return;
	}


	/**
	 * Get the contact's email address
	 *
	 * @return string
	 */
	public function get_email() {
		return strtolower( $this->email );
	}

	/**
	 * Gets the contact's optin status
	 *
	 * @return int
	 */
	public function get_optin_status() {
		return absint( $this->optin_status );
	}

	/**
	 * Get the contact's first name
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->first_name;
	}

	/**
	 * Gtet the contact's last name
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->last_name;
	}

	/**
	 * Get the contact's full name
	 *
	 * @return string
	 */
	public function get_full_name() {
		return sprintf( '%s %s', $this->get_first_name(), $this->get_last_name() );
	}

	/**
	 * Get the user ID
	 *
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->user_id );
	}

	/**
	 * Get the user ID
	 *
	 * @return int
	 */
	public function get_owner_id() {
		return absint( $this->owner_id );
	}

	/**
	 * Get the user data
	 *
	 * @return WP_User|false
	 */
	public function get_userdata() {
		return $this->user;
	}

	/**
	 * @return WP_User
	 */
	public function get_ownerdata() {
		return $this->owner;
	}

	/**
	 * @return string
	 */
	public function get_phone_number() {
		return $this->get_meta( 'primary_phone' );
	}

	/**
	 * @return string
	 */
	public function get_phone_extension() {
		return $this->get_meta( 'primary_phone_extension' );
	}

	/**
	 * Get the contacts's IP
	 *
	 * @return mixed
	 */
	public function get_ip_address() {
		return $this->get_meta( 'ip_address' );
	}

	/**
	 * Get the contact's time_zone
	 *
	 * @return mixed
	 */
	public function get_time_zone() {
		return $this->get_meta( 'time_zone' );
	}

	/**
	 * @return bool|mixed
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * Get the address
	 *
	 * @return array
	 */
	public function get_address() {
		$address_keys = [
			'street_address_1',
			'street_address_2',
			'postal_zip',
			'city',
			'region',
			'region_code',
			'country',
			'country_name',
		];

		$address = [];

		foreach ( $address_keys as $key ) {

			$val = $this->get_meta( $key );
			if ( ! empty( $val ) ) {
				$address[ $key ] = $val;
			}
		}

		return $address;
	}

	/**
	 * Return whether the contact actually exists
	 */
	public function exists() {
		return (bool) is_email( $this->email );
	}

	/**
	 * Return whether the contact is marketable or not.
	 *
	 * @return bool
	 */
	public function is_marketable() {
		return apply_filters( 'groundhogg/contact/is_marketable', Plugin::instance()->preferences->is_marketable( $this->ID ), $this );
	}

	/**
	 * Add a note to the contact
	 *
	 * @param $note
	 *
	 * @return bool
	 */
	public function add_note( $note ) {
		if ( ! $note || ! is_string( $note ) ) {
			return false;
		}

		$note = sanitize_textarea_field( $note );

		$current_notes = $this->get_meta( 'notes' );

		$new_notes = sprintf( "===== %s =====\n\n", date_i18n( get_option( 'date_format' ) ) );
		$new_notes .= sprintf( "%s\n\n", $note );
		$new_notes .= $current_notes;

		$new_notes = sanitize_textarea_field( $new_notes );

		$this->update_meta( 'notes', $new_notes );

		do_action( 'groundhogg/contact/note/added', $this->ID, $note, $this );

		return true;
	}

	/**
	 * get the contact's notes
	 *
	 * @return string
	 */
	public function get_notes() {
		return $this->get_meta( 'notes' );
	}

	/**
	 * Wrapper function for add_tag to make it easier
	 *
	 * @param $tag_id_or_array
	 *
	 * @return bool
	 */
	public function apply_tag( $tag_id_or_array ) {
		return $this->add_tag( $tag_id_or_array );
	}

	/**
	 * Add a list of tags or a single tag top the contact
	 *
	 * @param $tag_id_or_array array|int
	 *
	 * @return bool
	 */
	public function add_tag( $tag_id_or_array ) {

		if ( ! is_array( $tag_id_or_array ) ) {
			$tags = explode( ',', $tag_id_or_array );
		} else if ( is_array( $tag_id_or_array ) ) {
			$tags = $tag_id_or_array;
		} else {
			return false;
		}

		$tags = $this->get_tags_db()->validate( $tags );

		foreach ( $tags as $tag_id ) {

			if ( ! $this->has_tag( $tag_id ) ) {

				$this->tags[] = $tag_id;

				$result = $this->get_tag_rel_db()->add( $tag_id, $this->get_id() );

				// No ID so check for int 0
				if ( $result === 0 ) {
					do_action( 'groundhogg/contact/tag_applied', $this, $tag_id );
				}

			}

		}

		return true;

	}


	/**
	 * Remove a single tag or several tag from the contact
	 *
	 * @param $tag_id_or_array
	 *
	 * @return bool
	 */
	public function remove_tag( $tag_id_or_array ) {
		if ( ! is_array( $tag_id_or_array ) ) {
			$tags = explode( ',', $tag_id_or_array );
		} else if ( is_array( $tag_id_or_array ) ) {
			$tags = $tag_id_or_array;
		} else {
			return false;
		}

		$tags = $this->get_tags_db()->validate( $tags );

		foreach ( $tags as $tag_id ) {

			if ( $this->has_tag( $tag_id ) ) {

				unset( $this->tags[ array_search( $tag_id, $this->tags ) ] );

				$result = $this->get_tag_rel_db()->delete( [ 'tag_id' => $tag_id, 'contact_id' => $this->ID ] );

				if ( $result ) {
					do_action( 'groundhogg/contact/tag_removed', $this, $tag_id );
				}

			}

		}

		return true;
	}

	/**
	 * return whether the contact has a specific tag
	 *
	 * @param int|string $tag_id_or_name the ID or name or the tag
	 *
	 * @return bool
	 */
	public function has_tag( $tag_id_or_name ) {
		if ( ! is_numeric( $tag_id_or_name ) ) {
			$tag    = (object) $this->get_tags_db()->get_tag_by( 'tag_slug', sanitize_title( $tag_id_or_name ) );
			$tag_id = absint( $tag->tag_id );
		} else {
			$tag_id = absint( $tag_id_or_name );
		}

		return in_array( $tag_id, $this->tags );
	}

	/**
	 * Change the marketing preferences of a contact.
	 *
	 * @param $preference
	 */
	public function change_marketing_preference( $preference ) {
		$old_preference = $this->get_optin_status();

		// Don't do anything if the preference didn't change
		if ( $old_preference === $preference ) {
			return;
		}

		$this->update( [
			'optin_status'              => $preference,
			'date_optin_status_changed' => current_time( 'mysql' )
		] );

		$this->update_meta( 'preferences_changed', time() );

		do_action( 'groundhogg/contact/preferences/updated', $this->ID, $preference, $old_preference );

		if ( $preference === Preferences::UNSUBSCRIBED ) {
			do_action( 'groundhogg/contact/preferences/unsubscribed', $this->ID, $preference, $old_preference );
		}

	}

	/**
	 * Unsubscribe a contact
	 */
	public function unsubscribe() {
		$this->change_marketing_preference( Preferences::UNSUBSCRIBED );
	}

	/**
	 * This will find a WP account with the same email and update the user_id accordingly
	 *
	 * @return bool true if we found a relevant user account, false otherwise.
	 */
	public function auto_link_account() {
		if ( $this->get_user_id() ) {
			return true;
		}

		$user = get_user_by( 'email', $this->get_email() );

		if ( $user ) {
			$this->update( [ 'user_id' => $user->ID ] );

			return true;
		}

		return false;
	}

	/**
	 * Extrapolate the contact's location from an IP.
	 *
	 * @param bool $override
	 *
	 * @return array|bool
	 */
	public function extrapolate_location( $override = false ) {
		$ip_address = $this->get_ip_address();

		/* Do not run for localhost IPv6 blank IP */
		if ( ! $ip_address || $ip_address === "::1" ) {
			return false;
		}

		$info = Plugin::instance()->utils->location->ip_info( $ip_address );

		if ( ! $info || empty( $info ) ) {
			return false;
		}

		$location_meta = [
			'city'         => 'city',
			'region'       => 'region',
			'region_code'  => 'region_code',
			'country_name' => 'country',
			'country'      => 'country_code',
			'time_zone'    => 'time_zone',
		];

		foreach ( $location_meta as $meta_key => $ip_info_key ) {
			$has_meta = $this->get_meta( $meta_key );
			if ( key_exists( $ip_info_key, $info ) && ( ! $has_meta || $override ) ) {
				$this->update_meta( $meta_key, $info[ $ip_info_key ] );
			}
		}

		return $info;
	}

	/**
	 * Returns the local time of the contact
	 * If time specified, converts the timestamp dependant on the timezone of the user.
	 *
	 * @param int $time UNIX timestamp
	 *
	 * @return int UNIX timestamp
	 */
	function get_local_time( $time = 0 ) {

		if ( ! $time ) {
			$time = time();
		}

		$time_zone = $this->get_time_zone();

		if ( $time_zone ) {
			try {
				$local_time = Plugin::$instance->utils->date_time->convert_to_foreign_time( $time, $time_zone );
			} catch ( \Exception $e ) {
				$local_time = $time;
			}
		} else {
			// If no timezone specified assume local time of site
			$local_time = Plugin::$instance->utils->date_time->convert_to_local_time( $time );
		}

		return $local_time;

	}

	/**
	 * Compensate for hour difference between local site time and the timezone of the contact.
	 *
	 * @param int $time
	 *
	 * @return int
	 */
	function get_local_time_in_utc_0( $time = 0 ) {

		if ( ! $time ) {
			$time = time();
		}

		return $time + $this->get_utc_0_offset();
	}

	/**
	 * Get the contacts timezone offset.
	 *
	 * @return int
	 */
	function get_time_zone_offset() {

		// Return site timezone offset if no timezone in contact record?
		if ( ! $this->get_time_zone() ) {
			return Plugin::$instance->utils->date_time->get_wp_offset();
		}

		try {
			return Plugin::$instance->utils->date_time->get_timezone_offset( $this->get_time_zone() );
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	/**
	 * Get a proper UTC offset
	 *
	 * @return int
	 */
	function get_utc_0_offset() {
		return Plugin::$instance->utils->date_time->get_wp_offset() - $this->get_time_zone_offset();
	}

	/**
	 * @no_access Do not access
	 *
	 * @param $dirs
	 *
	 * @return mixed
	 */
	public function map_upload( $dirs ) {
		$dirs['path']   = $this->upload_paths['path'];
		$dirs['url']    = $this->upload_paths['url'];
		$dirs['subdir'] = $this->upload_paths['subdir'];

		return $dirs;
	}

	/**
	 * Upload a file
	 *
	 * Usage: $contact->upload_file( $_FILES[ 'file_name' ] )
	 *
	 * @param $file
	 *
	 * @return array|\WP_Error
	 */
	public function upload_file( &$file ) {

		$file['name'] = sanitize_file_name( $file['name'] );

		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->get_uploads_folder();
		add_filter( 'upload_dir', [ $this, 'map_upload' ] );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', [ $this, 'map_upload' ] );

		if ( isset_not_empty( $mfile, 'error' ) ) {
			return new \WP_Error( 'bad_upload', __( 'Unable to upload file.', 'groundhogg' ) );
		}

		return $mfile;
	}

	/**
	 * Get the basename of the path
	 *
	 * @return string
	 */
	public function get_upload_folder_basename() {
		return md5( Plugin::$instance->utils->encrypt_decrypt( $this->get_email() ) );
	}

	/**
	 * get the upload folder for this contact
	 */
	public function get_uploads_folder() {
		$paths = [
			'subdir' => sprintf( '/groundhogg/uploads/%s', $this->get_upload_folder_basename() ),
			'path'   => Plugin::$instance->utils->files->get_contact_uploads_dir( $this->get_upload_folder_basename() ),
			'url'    => Plugin::$instance->utils->files->get_contact_uploads_url( $this->get_upload_folder_basename() )
		];

		$this->upload_paths = $paths;

		return $paths;
	}

	/**
	 * Get a list of associated files.
	 */
	public function get_files() {
		$data = [];

		$uploads_dir = $this->get_uploads_folder();

		if ( file_exists( $uploads_dir['path'] ) ) {

			$scanned_directory = array_diff( scandir( $uploads_dir['path'] ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = $uploads_dir['path'] . '/' . $filename;
				$file     = [
					'file_name'     => $filename,
					'file_path'     => $filepath,
					'file_url'      => file_access_url( '/uploads/' . $this->get_upload_folder_basename() . '/' . $filename ),
					'date_uploaded' => filectime( $filepath ),
				];

				$data[] = $file;

			}
		}

		return $data;
	}

	/**
	 * Get the age of the contact
	 *
	 * @return int
	 */
	public function get_age() {

		$date = $this->get_meta( 'birthday' );

		if ( empty( $date ) ) {
			return false;
		}

		$age_in_seconds = time() - strtotime( $date );
		$age_in_years   = round( $age_in_seconds / YEAR_IN_SECONDS, 0 );

		return absint( $age_in_years );
	}


	/**
	 * Get the contact data as an array.
	 *
	 * @return array
	 */
	public function get_as_array() {
		$contact = $this->get_data();

		$contact['ID']       = (string) $this->get_id();
		$contact['gravatar'] = $this->get_profile_picture();
		$contact['age']      = $this->get_age();

		return apply_filters(
			"groundhogg/{$this->get_object_type()}/get_as_array",
			[
				'data'  => $contact,
				'meta'  => $this->get_meta(),
				'tags'  => $this->get_tags(),
				'files' => $this->get_files()
			]
		);
	}

	/**
	 * Output a contact. Just give the full name & email
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf( "%s (%s)", $this->get_full_name(), $this->get_email() );
	}


	public function get_company() {
		return $this->get_meta( 'company_name' );
	}

	public function get_job_title() {
		return $this->get_meta( 'job_title' );
	}


}