<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 1:18 PM
 */

abstract class Dashboard_Widget {

    /**
     * Hook to wp_dashboard_setup to add the widget.
     */
    public function __construct() {

        add_action( 'groundhogg/reporting/load' , [ $this,'register' ] );
        add_shortcode( sprintf( 'gh_%s', $this->get_id() ), [ $this, 'widget' ] );

    }

    abstract public function get_id();
    abstract public function get_name();

    /**
     * Regiter the widget
     */
    public function register()
    {

        //Register the widget...
        wp_add_dashboard_widget(
            $this->get_id(),
            $this->get_name(),
            [ $this,'widget' ]
        );
    }

    public function scripts(){ /*overwrite*/ }

    /**
     * Load the widget code
     */
    abstract public function widget();

    /**
     * Gets the options for a widget of the specified name.
     *
     * @return array|false An associative array containing the widget's options and values. False if no opts.
     */
    public function get_dashboard_widget_options()
    {
        $widget_id = $this->get_id();
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //If no widget is specified, return everything
        if ( empty( $widget_id ) )
            return $opts;

        //If we request a widget and it exists, return it
        if ( isset( $opts[$widget_id] ) )
            return $opts[$widget_id];

        //Something went wrong...
        return false;
    }

    /**
     * Gets one specific option for the specified widget.
     * @param $option
     * @param null $default
     *
     * @return string
     */
    public function get_option(  $option, $default=NULL  )
    {
        return $this->get_dashboard_widget_option($option, $default );
    }

    /**
     * Gets one specific option for the specified widget.
     * @param $option
     * @param null $default
     *
     * @return string
     */
    public function get_dashboard_widget_option( $option, $default=NULL ) {

        $opts = $this->get_dashboard_widget_options();

        //If widget opts dont exist, return false
        if ( ! $opts )
            return false;

        //Otherwise fetch the option or use default
        if ( isset( $opts[$option] ) && ! empty($opts[$option]) )
            return $opts[$option];
        else
            return ( isset($default) ) ? $default : false;

    }

    /**
     * Saves an array of options for a single dashboard widget to the database.
     * Can also be used to define default values for a widget.
     *
     * @param array $args An associative array of options being saved.
     * @param bool $add_only If true, options will not be added if widget options already exist
     *
     * @return bool
     */
    public function update_options( $args=array(), $add_only=false )
    {
        $widget_id = $this->get_id();
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //Get just our widget's options, or set empty array
        $w_opts = ( isset( $opts[$widget_id] ) ) ? $opts[$widget_id] : array();

        if ( $add_only ) {
            //Flesh out any missing options (existing ones overwrite new ones)
            $opts[$widget_id] = array_merge($args,$w_opts);
        }
        else {
            //Merge new options with existing ones, and add it back to the widgets array
            $opts[$widget_id] = array_merge($w_opts,$args);
        }

        //Save the entire widgets array back to the db
        return update_option('dashboard_widget_options', $opts);
    }

}