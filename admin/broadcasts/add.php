<?php
namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Contact_Query;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

/**
 * This is the page which allows the user to schedule a broadcast.
 *
 * Broadcasts are a closed process and thus have very limited hooks to modify the functionality.
 * If you are looking to extend the broadcast experience you are better off designing your own page to schedule broadcasts.
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Broadcasts_Page::add()
 * @since       File available since Release 0.1
 */

if (!defined('ABSPATH')) exit;

wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui');

$type = isset($_REQUEST['type']) && $_REQUEST['type'] === 'sms' ? 'sms' : 'email';
?>
<form name="edittag" id="edittag" method="post" action="">
    <?php wp_nonce_field(); ?>
    <table class="form-table">
        <?php if ($type === 'email'): ?>
        <tbody>
        <tr class="form-field term-email-wrap">
            <th scope="row"><label for="email_id"><?php _e('Select an email to send.', 'groundhogg') ?></label></th>
            <td><?php $args = array();
                $args['id'] = 'email_id';
                $args['name'] = 'object_id';
                $args['required'] = true;

                if ($email_id = absint(get_request_var('email'))) {
                    $args['selected'] = $email_id;
                }

                echo Plugin::$instance->utils->html->dropdown_emails($args);
                ?>
                <div class="row-actions">
                    <a target="_blank" class="button button-secondary"
                       href="<?php echo admin_url('admin.php?page=gh_emails&action=add'); ?>"><?php _e('Create New Email', 'groundhogg'); ?></a>
                </div>
                <p class="description"><?php _e('The Broadcast tool uses your global emails.', 'groundhogg') ?></p>
            </td>
        </tr>
        <?php else : ?>
            <tr class="form-field term-sms-wrap">
                <th scope="row"><label for="sms_id"><?php _e('Select an SMS to send.', 'groundhogg') ?></label></th>
                <td><?php $args = array();
                    $args['id'] = 'sms_id';
                    $args['name'] = 'object_id';
                    $args['required'] = true;

                    if ($sms_id = absint(get_request_var('sms'))) {
                        $args['selected'] = $sms_id;
                    }

                    echo Plugin::$instance->utils->html->dropdown_sms($args); ?>
                    <div class="row-actions">
                        <a target="_blank" class="button button-secondary"
                           href="<?php echo admin_url('admin.php?page=gh_sms&action=add'); ?>"><?php _e('Create New SMS', 'groundhogg'); ?></a>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        <tr class="form-field term-tags-wrap">
            <th scope="row"><label for="description"><?php _e('Send To:', 'groundhogg'); ?></label></th>
            <td><?php

                if (!isset_not_empty($_GET, 'use_query')):

                    echo html()->dropdown([
                        'name' => 'tags_include_needs_all',
                        'id' => 'tags_include_needs_all',
                        'class' => '',
                        'options' => array(
                            0 => __('Any', 'groundhogg'),
                            1 => __('All', 'groundhogg')
                        ),
                        'option_none' => false
                    ]);

                    $tag_args = array();
                    $tag_args['id'] = 'tags';
                    $tag_args['name'] = 'tags[]';
                    $tag_args['required'] = true;

                    _e( ' of ', 'groundhogg' );

                    echo Plugin::$instance->utils->html->tag_picker($tag_args);

                    ?>
                    <p class="description"><?php _e('This broadcast will be sent to contacts with these tags.', 'groundhogg'); ?></p>
                <?php else:
                    $query = new Contact_Query();
                    $num = count($query->query(get_request_query()));
                    printf(__("%d Contacts", 'groundhogg'), $num);
                endif; ?>
            </td>
        </tr>
        <tr class="form-field term-exclude-tags-wrap">
            <th scope="row"><label for="description"><?php _e('Exclude These Contacts:', 'groundhogg'); ?></label></th>
            <td><?php

                echo html()->dropdown([
                    'name' => 'tags_exclude_needs_all',
                    'id' => 'tags_exclude_needs_all',
                    'class' => '',
                    'options' => array(
                        0 => __('Any', 'groundhogg'),
                        1 => __('All', 'groundhogg')
                    ),
                    'option_none' => false
                ]);

                _e( ' of ', 'groundhogg' );

                $tag_args = array();
                $tag_args['id'] = 'exclude_tags';
                $tag_args['name'] = 'exclude_tags[]';
                $tag_args['required'] = false;

                echo Plugin::$instance->utils->html->tag_picker($tag_args); ?>
                <p class="description"><?php _e('These contacts will be excluded.', 'groundhogg'); ?></p>
            </td>
        </tr>
        <tr class="form-field term-date-wrap">
            <th scope="row">
                <label for="date"><?php _e('Send On:', 'groundhogg'); ?></label>
            </th>
            <td>
                <div style="display: inline-block; width: 100px;">
                    <?php echo Plugin::$instance->utils->html->date_picker(array('name' => 'date', 'id' => 'date', 'class' => 'input')); ?>
                </div>
                <input type="time" id="time" name="time" value="09:00" autocomplete="off"
                       required><?php _e('&nbsp;or&nbsp;', 'groundhogg'); ?>
                <?php echo Plugin::$instance->utils->html->checkbox(array(
                    'label' => _x('Send Now', 'action', 'groundhogg'),
                    'name' => 'send_now',
                    'id' => 'send_now',
                    'class' => '',
                    'value' => '1',
                    'checked' => false,
                    'title' => __('Send Now', 'groundhogg'),
                    'attributes' => '',
                    'required' => false,)); ?>
                <p class="description"><?php _e('The day the broadcast will be sent.', 'groundhogg'); ?></p>
                <div style="margin-top: 10px;">
                    <?php echo Plugin::$instance->utils->html->checkbox(array(
                        'label' => _x('Send in the contact\'s local time.', 'action', 'groundhogg'),
                        'name' => 'send_in_timezone',
                        'id' => 'send_in_timezone',
                        'class' => '',
                        'value' => '1',
                        'checked' => false,
                        'title' => __('Send in the contact\'s local time.', 'groundhogg'),
                        'attributes' => '',
                        'required' => false,)); ?>
                </div>
                <p class="description"><?php _e('If checked, this email will be sent at the specified time in their local timezone. If the time has already passed the email will be scheduled for the following day.', 'groundhogg'); ?></p>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="edit-tag-actions">
        <?php submit_button(_x('Schedule Broadcast', 'action', 'groundhogg'), 'primary', 'update', false); ?>
    </div>
</form>
