<?php
defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();
if ( bp_displayed_user_id() !== 0 ) $user_id = bp_displayed_user_id();

$threads = BP_Better_Messages()->functions->get_threads( $user_id );
$favorited = BP_Better_Messages()->functions->get_starred_count();

?>
<div class="bp-messages-wrap bp-messages-wrap-main">
    <div class="chat-header">
        <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="back ajax"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>

        <a href="#" class="mobileClose"><i class="fas fa-window-close"></i></a>
    </div>

    <div class="scroller scrollbar-inner ">
        <div class="bpbm-user-options">
            <h4 class="bpbm-user-option-title">
                Notifications
            </h4>

            <div class="bpbm-user-option">
                <?php
                $checked = (get_user_meta( $user_id, 'notification_messages_new_message', true ) !== 'no'); ?>
                <div class="bpbm-user-option-toggle">
                    <input id="email_notifications" type="checkbox" value="yes" <?php checked(true, $checked); ?>>
                    <label for="email_notifications"><?php esc_attr_e('Enable notifications via email', 'bp-better-messages'); ?></label>
                </div>
                <div class="bpbm-user-option-description">
                    <?php esc_attr_e('When enabled, you will receive notifications about new messages via email when you are offline.', 'bp-better-messages'); ?>
                </div>
            </div>

            <?php if(BP_Better_Messages()->settings['enablePushNotifications'] === '1' ) { ?>
            <div class="bpbm-user-option BPBMpushNotifications">
                <div class="bpbm-user-option-toggle">
                    <label><?php esc_attr_e('Browser push notifications', 'bp-better-messages'); ?></label>
                </div>
                <div class="bpbm-user-option-description">
                    <?php esc_attr_e('When enabled, you will receive messages notifications even if browser is closed.', 'bp-better-messages'); ?>
                    <div class="BPBMenablePushNotificationsControls">
                        <button class="BPBMenablePushNotifications" style="display: none"><?php _e('Enable', 'bp-better-messages'); ?></button>
                        <button class="BPBMdisablePushNotifications" style="display: none"><?php _e('Disable', 'bp-better-messages'); ?></button>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php /*

            <h4 class="bpbm-user-option-title">
                <?php esc_attr_e('Black list', 'bp-better-messages'); ?>
            </h4>
            <div class="bpbm-user-option">
                <div class="bpbm-user-option-description">
                    <?php esc_attr_e('This is list of users you added to blacklist, you can remove them from blacklist here if needed.', 'bp-better-messages'); ?>
                </div>
                <div class="bpbm-user-blacklist">
                    <ul>
                        <li>Recipient</li>
                        <li>Recipient</li>
                        <li>Recipient</li>
                        <li>Recipient</li>
                        <li>Recipient</li>
                        <li>Recipient</li>
                        <li>Recipient</li>
                    </ul>
                </div>
            </div> */ ?>


            <?php do_action('bp_better_messages_user_options_scripts'); ?>
            <script type="text/javascript">
                jQuery('#email_notifications').change(function(){
                    var is_checked = jQuery(this).is(':checked');
                    
                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        'action'   : 'bp_messages_change_user_option',
                        'user_id'  : '<?php echo $user_id; ?>',
                        'option'   : 'email_notifications',
                        'value'    : is_checked,
                        '_wpnonce' : '<?php echo wp_create_nonce( 'bp_messages_change_user_option_' . $user_id ); ?>'
                    }, function (response) {
                        if( response.result === true ){
                            BBPMNotice( response.message );
                        } else {
                            BBPMShowError( response.errors.join("\n") );
                        }
                    });
                });
            </script>


        </div>
    </div>

    <script type="text/javascript">
        jQuery('.bp-better-messages-unread').text(<?php echo BP_Messages_Thread::get_total_threads_for_user( get_current_user_id(), 'inbox', 'unread' ); ?>);
    </script>

    <div class="preloader"></div>

    <?php if( BP_Better_Messages()->settings['disableTapToOpen'] === '0' ){ ?>
        <div class="bp-messages-mobile-tap"><?php _e( 'Tap to open messages', 'bp-better-messages' ); ?></div>
    <?php } ?>
</div>