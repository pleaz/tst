<?php
namespace Groundhogg\Reporting\Reports;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

class Contacts_By_Source_Page extends Contacts_By_Meta
{

    /**
     * @return string
     */
    public function get_id()
    {
        return 'contacts_by_source_page';
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return __( 'Contacts By Source Page', 'groundhogg' );
    }

    /**
     * Return the meta_key used to query the DB
     *
     * @return string
     */
    public function get_meta_key()
    {
        return 'source_page';
    }
}