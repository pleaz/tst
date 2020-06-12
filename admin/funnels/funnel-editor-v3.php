<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\Admin\Reports\Views\get_funnel_id;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\is_white_labeled;
use function Groundhogg\key_to_words;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

/**
 * Edit Funnel
 *
 * This page allows one to edit the funnels they have installed.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$funnel_id = absint( get_request_var( 'funnel' ) );

$funnel = new Funnel( $funnel_id );

?>
<form method="post" id="funnel-form">
	<?php wp_nonce_field(); ?>
	<?php $args = array(
		'type'  => 'hidden',
		'name'  => 'funnel',
		'id'    => 'funnel',
		'value' => $funnel_id
	);
	echo Plugin::$instance->utils->html->input( $args ); ?>
    <div class="header-wrap">
        <div class="funnel-editor-header">
            <div class="title-section">
                <div class="title-view">
					<?php printf( __( 'Now editing %s', 'groundhogg' ), html()->e( 'span', [ 'class' => 'title' ], $funnel->get_title() ) ); ?>
                </div>
                <div class="title-edit hidden">
                    <input class="title" placeholder="<?php echo __( 'Enter Funnel Name Here', 'groundhogg' ); ?>"
                           type="text"
                           name="funnel_title" size="30" value="<?php esc_attr_e( $funnel->get_title() ); ?>" id="title"
                           spellcheck="true" autocomplete="off">
                </div>
            </div>
            <div class="status-options">
                <div id="status">
					<?php echo Plugin::$instance->utils->html->toggle( [
						'name'    => 'funnel_status',
						'id'      => 'status-toggle',
						'class'   => 'big-toggle',
						'value'   => 'active',
						'checked' => $funnel->is_active(),
						'on'      => 'Active',
						'off'     => 'Inactive',
					] ); ?>
                </div>
                <div id="save">
					<?php
					echo html()->button( [
						'type'  => 'submit',
						'text'  => dashicon( 'yes' ) . html()->wrap( __( 'Save' ), 'span', [ 'class' => 'save-text' ] ),
						'name'  => 'update',
						'id'    => 'update',
						'class' => 'button button-primary save-button',
						'value' => 'save',
					] );
					?>
                </div>
            </div>
            <div id="close">
				<?php

				echo html()->e( 'a', [
					'href'  => admin_page_url( 'gh_funnels' ),
					'class' => 'exit'
				], dashicon( 'no' ) );

				?>
            </div>
        </div>
        <div class="funnel-editor-header sub-header">

            <div class="toolbar-buttons">
                <div>
					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Settings', 'groundhogg' ),
						'text'               => dashicon( 'admin-generic' ) . __( 'Settings', 'groundhogg' ),
						'id'                 => 'settings',
						'class'              => 'no-padding settings settings-button button-secondary',
						'source'             => admin_page_url( 'gh_funnels', [
							'action' => 'funnel_settings',
							'funnel' => $funnel_id
						] ),
						'height'             => 500,
						'width'              => 900,
						'footer_button_text' => __( 'Close' ),
					) ); ?>
                </div>
                <div>
					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Replacements', 'groundhogg' ),
						'text'               => dashicon( 'admin-users' ) . __( 'Replacements', 'groundhogg' ),
						'footer_button_text' => __( 'Insert' ),
						'id'                 => 'replacements',
						'class'              => 'no-padding replacements replacements-button button-secondary',
						'source'             => 'footer-replacement-codes',
						'height'             => 900,
						'width'              => 700,
					) ); ?>
                </div>
                <div id="add-contacts" class="<?php if ( ! $funnel->is_active() ) {
					echo 'hidden';
				} ?>">
					<?php echo html()->modal_link( [
						'title'              => __( 'Add Contacts', 'groundhogg' ),
						'text'               => dashicon( 'plus' ) . __( 'Add Contacts', 'groundhogg' ),
						'footer_button_text' => __( 'Close' ),
						'id'                 => '',
						'class'              => 'add-contacts-link button',
						'source'             => 'add-contact-modal',
						'height'             => 500,
						'width'              => 600,
						'footer'             => 'true',
					] ); ?>
                </div>
                <div id="export">
                    <a id="copy-share-link" href="#" class="button"><span
                                title="<?php esc_attr_e( 'Copy share link', 'groundhogg' ) ?>"
                                class="dashicons dashicons-share"></span> <?php _e( 'Share', 'groundhogg' ); ?></a>
                    <input id="share-link" type="hidden" value="<?php echo esc_attr( $funnel->export_url() ); ?>">
                    <a href="<?php echo esc_url( $funnel->export_url() ); ?>" class="button"><span
                                title="<?php esc_attr_e( 'Export', 'groundhogg' ) ?>"
                                class="dashicons dashicons-download"></span> <?php _e( 'Export', 'groundhogg' ); ?></a>
                </div>
                <div id="report">
                    <a href="<?php echo admin_url( sprintf( 'admin.php?page=gh_reporting&tab=funnels&funnel=%s', $funnel_id ) ); ?>"
                       class="button"><span
                                title="<?php esc_attr_e( 'Reports', 'groundhogg' ) ?>"
                                class="dashicons dashicons-chart-area"
                                style="width: auto;height: auto;vertical-align: middle;font-size: 20px;margin-right: 3px;"></span> <?php _e( 'Reports', 'groundhogg' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2 main" style="clear: both">
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="step-sortable" class=" ui-sortable">
					<?php foreach ( $funnel->get_steps() as $step ): ?>
						<?php $step->sortable_item(); ?>
					<?php endforeach; ?>
                </div>
                <div class="add-step-bottom-wrap">
					<?php
					echo html()->modal_link( [
						'title'              => __( 'Add Step', 'groundhogg' ),
						'text'               => dashicon( 'plus' ) . __( 'Add Step', 'groundhogg' ),
						'footer_button_text' => __( 'Cancel' ),
						'class'              => 'add-step button button-secondary no-padding',
						'id'                 => 'add-step-bottom',
						'source'             => 'steps',
						'height'             => 700,
						'width'              => 600,
						'footer'             => 'true',
						'preventSave'        => 'true',
					] );
					?>
                </div>
            </div>
            <div id="postbox-container-2" class="postbox-container">
                <div id="postbox-container-2-inner">
					<?php Plugin::$instance->notices->print_notices(); ?>
                    <div id="intro">
						<?php

						if ( ! is_white_labeled() ) :

							// Output image to show the link to the youtube video!
							echo html()->modal_link( [
								'title'              => __( 'Funnel Builder Tour', 'groundhogg' ),
								'text'               => html()->e( 'img', [
									'src' => GROUNDHOGG_ASSETS_URL . 'images/funnel-intro/funnel-builder-guided-tour.png'
								] ),
								'footer_button_text' => __( 'Close' ),
								'source'             => 'funnel-builder-guided-tour',
								'class'              => 'img-link no-padding demo-video',
								'height'             => 555,
								'width'              => 800,
								'footer'             => 'true',
								'preventSave'        => 'true',
							] );

							?>
                            <div class="hidden" id="funnel-builder-guided-tour">
                                <iframe width="800" height="450" src="https://www.youtube.com/embed/Bof5kMEpXrY"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
						<?php else:

							// White labeled option
                            do_action( 'groundhogg/admin/funnel_builder/white_labeled', $funnel );

						endif; ?>
                    </div>
                    <div class="step-settings">
						<?php

						$step_active = false;

						foreach ( $funnel->get_steps() as $step ):
							$step->html_v2();

							if ( $step->get_meta( 'is_active' ) ) {
								$step_active = true;
							}

						endforeach; ?>
                    </div>
                </div>

            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</form>
<?php if ( $step_active ): ?>
    <script>jQuery("html").addClass("active-step");</script>
<?php endif; ?>
<div class="hidden" id="steps">
    <div class="steps-select">
		<?php

		html()->tabs( [
			'benchmarks-tab' => __( 'Benchmarks', 'groundhogg' ),
			'actions-tab'    => __( 'Actions', 'groundhogg' )
		], 'benchmarks-tab' );

		?>
        <div id='benchmarks'>
            <div class="elements-inner inside">
                <table>
                    <tbody>
                    <tr><?php
						$benchmarks = Plugin::$instance->step_manager->get_benchmarks();
						$i          = 0;
						foreach ( $benchmarks

						as $benchmark ):
						if ( ( $i % 4 ) == 0 ):
						?></tr>
                    <tr><?php
						endif;
						?>
                        <td class="step-icon">
                            <div id='<?php echo $benchmark->get_type(); ?>'
                                 title="<?php esc_attr_e( $benchmark->get_description() ); ?>"
                                 class="wpgh-element ui-draggable">
                                <div class="step-icon"><img width="60"
                                                            src="<?php echo esc_url( $benchmark->get_icon() ); ?>">
                                </div>
                                <p><?php echo $benchmark->get_name() ?></p></div>
                        </td><?php
						$i ++;

						endforeach;

						?></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div id='actions' class="hidden">
            <div class="inside">
                <table>
                    <tbody>
                    <tr><?php
						$actions = Plugin::$instance->step_manager->get_actions();
						$i       = 0;
						foreach ( $actions

						as $action ):
						if ( ( $i % 4 ) == 0 ):
						?></tr>
                    <tr><?php
						endif;
						?>
                        <td class="step-icon">
                            <div id='<?php echo $action->get_type(); ?>'
                                 title="<?php esc_attr_e( $action->get_description() ); ?>"
                                 class="wpgh-element ui-draggable">
                                <div class="step-icon"><img width="60"
                                                            src="<?php echo esc_url( $action->get_icon() ); ?>">
                                </div>
                                <p><?php echo $action->get_name() ?></p></div>
                        </td><?php
						$i ++;

						endforeach;

						?></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- End Action Icons-->
</div>
<script>
    jQuery(function ($) {

        var $benchmarks = $("#benchmarks");
        var $actions = $("#actions");
        var $tabs = $(".nav-tab");

        $("#actions-tab").on("click", function (e) {
            e.preventDefault();

            $tabs.removeClass("nav-tab-active");
            $(this).addClass("nav-tab-active");

            $benchmarks.addClass("hidden");
            $actions.removeClass("hidden");
        });

        $("#benchmarks-tab").on("click", function (e) {
            e.preventDefault();

            $tabs.removeClass("nav-tab-active");
            $(this).addClass("nav-tab-active");

            $actions.addClass("hidden");
            $benchmarks.removeClass("hidden");
        });

    });
</script>

<div class="hidden" id="add-contact-modal" style="display: none;">
    <form method="post">
		<?php wp_nonce_field(); ?>
		<?php wp_nonce_field( 'add_contacts_to_funnel', 'add_contacts_nonce' ); ?>
        <div>
            <div class="add-contacts-response hidden"></div>
            <table class="add-contact-form" style="width: 100%;">
                <tbody>
                <tr>
                    <th>
						<?php _e( 'Select contacts to add into funnel:', 'groundhogg' ); ?>
                    </th>
                </tr>
                <tr>
                    <td>
						<?php

						echo html()->dropdown( [
							'name'        => 'tags_include_needs_all',
							'id'          => 'tags_include_needs_all',
							'class'       => '',
							'options'     => array(
								0 => __( 'Any', 'groundhogg' ),
								1 => __( 'All', 'groundhogg' )
							),
							'option_none' => false
						] );

						_e( ' of ', 'groundhogg' );

						echo Plugin::$instance->utils->html->tag_picker( array(
							'name' => 'include_tags[]',
							'id'   => 'include_tags',
						) );

						?>
                    </td>
                </tr>
                <tr>
                    <th>
						<?php _e( 'Exclude these contacts:', 'groundhogg' ); ?>
                    </th>
                </tr>
                <tr>
                    <td>
						<?php

						echo html()->dropdown( [
							'name'        => 'tags_exclude_needs_all',
							'id'          => 'tags_exclude_needs_all',
							'class'       => '',
							'options'     => array(
								0 => __( 'Any', 'groundhogg' ),
								1 => __( 'All', 'groundhogg' )
							),
							'option_none' => false
						] );

						_e( ' of ', 'groundhogg' );

						echo Plugin::$instance->utils->html->tag_picker( array(
							'name' => 'exclude_tags[]',
							'id'   => 'exclude_tags',
						) );

						?>
                    </td>
                </tr>
                <tr>
                    <th>
						<?php _e( 'Select where to start:', 'groundhogg' ); ?>
                    </th>
                </tr>
                <tr>
                    <td>
						<?php

						$options = [];

						$steps = $funnel->get_steps();

						foreach ( $steps as $step ) {
							$options[ $step->get_id() ] = sprintf( "%d. %s (%s)", $step->get_order(), $step->get_title(), key_to_words( $step->get_type() ) );
						}

						echo Plugin::$instance->utils->html->select2( array(
							'name'        => 'which_step',
							'id'          => 'which_step',
							'data'        => $options,
							'multiple'    => false,
							'option_none' => false,
						) );

						?>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php submit_button( __( 'Add Contacts to Funnel', 'groundhogg' ) ); ?>
        </div>
    </form>
</div>


