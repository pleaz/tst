<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use function Groundhogg\get_db;

class Total_Emails_Sent extends Base_Quick_Stat {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		if ( $this->get_email_id() ) {

			$email = new Email( $this->get_email_id() );
			$stats = $email->get_email_stats( $start, $end );
			$data  = $stats['sent'];

		} else {
			global $wpdb;

			$events_table = get_db( 'events' )->get_table_name();
			$steps_table  = get_db( 'steps' )->get_table_name();

			$data = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d
                        ORDER BY time DESC"
				, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
				$start, $end )
			);
		}

		return $data;
	}
}
