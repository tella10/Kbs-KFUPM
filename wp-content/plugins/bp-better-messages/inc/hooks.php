<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Hooks' ) ):

    class BP_Better_Messages_Hooks
    {

        public static function instance()
        {

            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Hooks();
            }

            return $instance;
        }

        public function __construct()
        {
            add_action( 'admin_init', array( $this, 'update_db_if_needed' ) );
            add_action( 'bp_init', array( $this, 'redirect_standard_component' ) );
            add_action( 'admin_bar_menu', array( $this, 'remove_standard_topbar' ), 999 );

            add_filter( 'bp_nouveau_get_members_buttons',   array( $this, 'pm_link_nouveau' ), 20, 3);
            add_filter( 'bp_get_send_private_message_link', array( $this, 'pm_link' ), 20, 1 );
            add_filter( 'yz_get_send_private_message_url',  array( $this, 'pm_link' ), 20, 1 );
            add_filter( 'bp_get_send_message_button_args',  array( $this, 'pm_link_args'), 20, 1 );


            if( BP_Better_Messages()->settings['userListButton'] == '1' ) {
                add_action('bp_directory_members_actions', array($this, 'pm_link_legacy'), 10);
            }

            add_filter( 'bp_get_message_thread_view_link',  array( $this, 'thread_link' ), 20, 2 );

            add_filter( 'cron_schedules', array( $this, 'cron_intervals' ) );
	        add_action( 'ajax_query_attachments_args',  array( $this, 'exclude_attachments' ) );

	        add_action( 'wp_head', array( $this, 'themes_adaptation' ) );

            if( BP_Better_Messages()->settings['chatPage'] !== '0' ){
                add_filter( 'the_content', array( $this, 'chat_page' ) );
            }

            add_action( 'admin_notices', array( $this, 'admin_notice') );
            if( BP_Better_Messages()->settings['fastStart'] == '1' ) {
                add_action('template_redirect', array($this, 'catch_fast_thread'));
            }

            if( BP_Better_Messages()->settings['friendsMode'] == '1' && function_exists('friends_check_friendship') ) {
                add_filter( 'bp_better_messages_can_send_message', array( $this, 'disable_non_friends_reply' ), 10, 3);
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'disable_start_thread_for_non_friends' ), 10, 2 );
            }


            /*
             * Block, Suspend, Report for BuddyPress integration
             */
            add_filter( 'bp_better_messages_can_send_message', array( $this, 'disable_message_for_blocked_user' ), 10, 3);


            if( isset(BP_Better_Messages()->settings['restrictNewReplies'])
                && is_array(BP_Better_Messages()->settings['restrictNewReplies'])
                && count(BP_Better_Messages()->settings['restrictNewReplies']) > 0
            ) {
                add_filter( 'bp_better_messages_can_send_message', array( $this, 'disable_message_for_blocked_restricted_role' ), 10, 3);
            }


            if( isset(BP_Better_Messages()->settings['restrictNewThreads'])
                && is_array(BP_Better_Messages()->settings['restrictNewThreads'])
                && count(BP_Better_Messages()->settings['restrictNewThreads']) > 0
            ) {
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'disable_thread_for_blocked_restricted_role' ), 10, 2);
            }

            if( BP_Better_Messages()->settings['singleThreadMode'] == '1' ) {
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'disable_start_thread_if_thread_exist' ), 10, 2 );
            }

            if( BP_Better_Messages()->settings['disableGroupThreads'] == '1' ) {
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'disable_group_threads' ), 10, 2 );
            }

            if( BP_Better_Messages()->settings['mechanism'] == 'websocket' && BP_Better_Messages()->settings['messagesStatus'] == '0'){
                add_action( 'wp_head', array( $this, 'disableStatuses') );
            }

            add_action( 'template_redirect', array( $this, 'update_last_activity' ) );

            // I have 0 idea why this fixes messages link when using Youzer plugin
            add_action( 'init', array( $this, 'fix_youzer' ) );

            add_action( 'bp_screens', array( $this, 'fix_404' ) );

            /*
             * BuddyBoss trying to fix
             */
            add_action('wp_head', array( $this, 'buddyboss_inbox_counter' ) );
            add_filter('messages_thread_get_inbox_count', array( $this, 'replace_unread_count' ), 10, 2 );
            add_filter('bp_messages_thread_current_threads', array( $this, 'buddyboss_notifications_fix' ), 10, 1 );

            add_action( 'wp_footer', array( $this, 'mobile_popup_button') );

            /*
             * BeeHive premium theme integration
             * https://www.wordplus.org/beehive
            */
            add_action('wp_head', array( $this, 'beehive_theme_integration' ), 100 );
            add_action('wp_head', array( $this, 'max_height_css' ), 100 );

            add_action( 'bp_core_user_updated_last_activity', array( $this, 'override_last_activity_2' ), 10, 2 );
            add_filter( 'update_user_metadata', array( $this, 'override_last_activity' ), 1, 5 );

            add_action( 'wp_head', array( $this, 'colors_customizations' ) );

            /**
             * PMPRO Access
             */
            if( defined('PMPROBP_DIR') && function_exists('pmpro_bp_user_can') ){
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'disable_thread_for_pmpro_restricted_role' ), 10, 2);
                add_filter( 'bp_better_messages_can_send_message', array( $this, 'disable_messages_for_pmpro_restricted_role' ), 10, 3);
            }


            add_action('admin_head', array($this, 'hide_admin_counter' ) );

            add_filter( 'bp_messages_allowed_tags', array($this, 'allow_additional_tags'), 10, 1 );

            add_filter( 'bp_better_messages_can_send_message', array( $this, 'disable_message_to_deleted_users' ), 10, 3);

            /**
             * https://wordpress.org/plugins/asgaros-forum/
             */
            if( class_exists('AsgarosForum') ){
                add_action('asgarosforum_custom_profile_menu',        array( $this, 'asragaros_profile_link'));
                if( apply_filters('asgarosforum_filter_show_header', true) ){
                    add_action('asgarosforum_content_header', array( $this, 'asragaros_profile_messages'));
                } else {
                    add_action('asgarosforum_profile_custom_content_top', array( $this, 'asragaros_profile_messages'));
                }

                add_action('asgarosforum_after_post_author', array( $this, 'asragaros_thread_view'), 10, 2);
            }

            /**
             * BBPress
             */
            if( class_exists('bbPress') ){
                add_action( 'bbp_theme_after_reply_author_details', array( $this, 'pm_link_bbpress' ) );
                #add_action( 'bbp_template_after_user_details_menu_items', array( $this, 'show_in_bbpress_profile' ) );
            }

            /**
             *
            */
            if( class_exists('BP_Verified_Member') ){
                add_filter('bp_better_messages_full_chat_username', array( $this, 'verified_member_badge' ), 10, 3 );
                add_filter('bp_better_messages_mini_chat_username', array( $this, 'verified_member_badge' ), 10, 3 );
                add_filter('bp_better_messages_thread_displayname', array( $this, 'verified_member_badge' ), 10, 3 );
            }

            if( defined('BP_PLATFORM_VERSION') ){
                add_filter( 'bp_better_messages_after_format_message', array( $this, 'buddyboss_group_messages' ), 10, 4 );
            }

            if( function_exists('bb_access_control_member_can_send_message') ) {
                add_filter( 'bp_better_messages_can_send_message', array($this, 'buddyboss_blocked_message'), 10, 3);
            }

            if ( class_exists( 'myCRED_BP_Charge_Messaging' ) ){
                add_filter( 'bp_better_messages_can_send_message',   array( $this, 'mycred_bp_charge_message'), 10, 3);
                add_action( 'messages_message_sent',                array( $this, 'mycred_bp_charge_for_message' ) );
                add_action( 'bp_better_messages_before_new_thread', array( $this, 'mycred_bp_charge_for_new_thread' ), 10, 2 );
            }

            if( BP_Better_Messages()->settings['allowMuteThreads'] === '1' ) {
                add_action('bp_better_messages_thread_pre_header', array($this, 'mute_thread_button'), 10, 3);

                /**
                 * Remove standard Email & Notifications
                 */
                add_filter('bp_email_validate', array($this, 'mute_thread_remove_standard_email'), 10, 2);
                add_action('bp_notification_after_save', array( $this, 'mute_thread_delete_notification'), 10, 1 );
            }
            //add_action( 'bp_better_messages_thread_pre_header', array( $this, 'leave_thread_button' ), 9, 3 );
        }

        public function mute_thread_delete_notification( $notification ){
            if( $notification->component_name !== 'messages') return false;
            if( $notification->component_action !== 'new_message') return false;
            if( ! isset( $_REQUEST['thread_id'] ) ) return false;

            $thread_id = intval($_REQUEST['thread_id']);
            $user_id   = $notification->user_id;

            $muted_threads = BP_Better_Messages()->functions->get_user_muted_threads( $user_id );

            if( isset( $muted_threads[ $thread_id ] ) ){
                BP_Notifications_Notification::delete( array( 'id' => $notification->id ) );
            }

            return true;
        }

        public function mute_thread_remove_standard_email($retval, $email){
            if($email->get('type') !== 'messages-unread') return $retval;
            if( ! isset( $_REQUEST['thread_id'] ) ) return $retval;

            $user_id = $email->get_to()[0]->get_user()->ID;
            $thread_id = intval($_REQUEST['thread_id']);
            $muted_threads = BP_Better_Messages()->functions->get_user_muted_threads( $user_id );

            if( isset( $muted_threads[ $thread_id ] ) ){
                $error_code = 'messages_user_muted_thread';
                $feedback   = __( 'Your message was not sent. User muted this thread.', 'bp-better-messages' );
                return new WP_Error( $error_code, $feedback );
            }

            return $retval;
        }

        public function mute_thread_button( $thread_id, $participants, $is_mini ){
            $muted_threads = BP_Better_Messages()->functions->get_user_muted_threads( get_current_user_id() );

            if( isset( $muted_threads[$thread_id] ) ) {
                echo '<a href="#" title="' . __('Unmute thread notifications', 'bp-better-messages') . '" class="bpbm-unmute-thread bpbm-can-be-hidden"><i class="fas fa-bell"></i></a>';
            } else {
                echo '<a href="#" title="' . __('Mute thread notifications', 'bp-better-messages') . '" class="bpbm-mute-thread bpbm-can-be-hidden"><i class="fas fa-bell-slash"></i></a>';
            }
        }

        public function leave_thread_button( $thread_id, $participants, $is_mini ){
            echo '<a href="#" title="' . __('Leave thread', 'bp-better-messages') . '" class="bpbm-leave-thread bpbm-can-be-hidden"><i class="fas fa-sign-out-alt"></i></a>';
        }

        public function mycred_bp_charge_for_message( $message ){
            global $wp_filter;

            $myCRED_BP_Charge_Messaging = false;
            foreach($wp_filter[ 'mycred_init' ]->callbacks[10] as $callback){
                if( isset($callback['function'])){
                    if( isset( $callback['function'][0] ) ){
                        if( is_a($callback['function'][0], 'myCRED_BP_Charge_Messaging') ){
                            $myCRED_BP_Charge_Messaging = $callback['function'][0];
                        }
                    }
                }
            }

            if( $myCRED_BP_Charge_Messaging !== false ){
                if( doing_action('wp_ajax_bp_messages_new_thread') ){
                    $myCRED_BP_Charge_Messaging->charge_messages( $message->recipients, (int) $message->thread_id );
                } else {
                    $myCRED_BP_Charge_Messaging->charge_new_reply( (int) $message->thread_id );
                }
            }
        }

        public function mycred_bp_charge_for_new_thread( &$args, &$errors ){
            global $wp_filter;

            $myCRED_BP_Charge_Messaging = false;
            foreach($wp_filter[ 'mycred_init' ]->callbacks[10] as $callback){
                if( isset($callback['function'])){
                    if( isset( $callback['function'][0] ) ){
                        if( is_a($callback['function'][0], 'myCRED_BP_Charge_Messaging') ){
                            $myCRED_BP_Charge_Messaging = $callback['function'][0];
                        }
                    }
                }
            }

            if( $myCRED_BP_Charge_Messaging !== false ){
                $can_afford = $myCRED_BP_Charge_Messaging->current_user_can_afford( 'new_message', count( $args['recipients'] ) );

                if( ! $can_afford ){
                    $errors['mycred_restricted'] = __('You don\'t have sufficient balance to start new thread', 'bp-better-messages');
                }
            }
        }

        public function mycred_bp_charge_message( $allowed, $user_id, $thread_id ){
            global $wp_filter;

            $myCRED_BP_Charge_Messaging = false;
            foreach($wp_filter[ 'mycred_init' ]->callbacks[10] as $callback){
                if( isset($callback['function'])){
                    if( isset( $callback['function'][0] ) ){
                        if( is_a($callback['function'][0], 'myCRED_BP_Charge_Messaging') ){
                            $myCRED_BP_Charge_Messaging = $callback['function'][0];
                        }
                    }
                }
            }

            if( $myCRED_BP_Charge_Messaging !== false ){
                $can_afford = $myCRED_BP_Charge_Messaging->current_user_can_afford( 'new_reply' );

                if( ! $can_afford ){
                    $allowed = false;
                    global $bp_better_messages_restrict_send_message;
                    $bp_better_messages_restrict_send_message['mycred_restricted'] = __('You don\'t have sufficient balance to reply', 'bp-better-messages');
                }
            }

            return $allowed;
        }

        public function buddyboss_blocked_message( $allowed, $user_id, $thread_id ){
            $thread = new BP_Messages_Thread( $thread_id );
            $check_buddyboss_access = bb_access_control_member_can_send_message( $thread, $thread->recipients, 'wp_error' );

            if( is_wp_error($check_buddyboss_access) ){
                $allowed = false;
                global $bp_better_messages_restrict_send_message;
                $bp_better_messages_restrict_send_message['buddyboss_restricted'] = $check_buddyboss_access->get_error_message();
            }
            return $allowed;
        }

        public function buddyboss_group_messages( $message, $message_id, $context, $user_id ){
            $group_id         = bp_messages_get_meta( $message_id, 'group_id', true );
            $message_deleted  = bp_messages_get_meta( $message_id, 'bp_messages_deleted', true );

            if( $group_id ) {
                if ( function_exists('bp_get_group_name') ) {
                    $group_name = bp_get_group_name(groups_get_group($group_id));
                } else {
                    global $wpdb;
                    $table = $wpdb->prefix . 'bp_groups';
                    $group_name = $wpdb->get_var( "SELECT `name` FROM `{$table}` WHERE `id` = '{$group_id}';" );
                }

                $message_left     = bp_messages_get_meta( $message_id, 'group_message_group_left', true );
                $message_joined   = bp_messages_get_meta( $message_id, 'group_message_group_joined', true );

                if ($message_left && 'yes' === $message_left) {
                    $message = '<i>' . sprintf(__('Left "%s"', 'bp-better-messages'), ucwords($group_name)) . '</i>';
                } else if ($message_joined && 'yes' === $message_joined) {
                    $message = '<i>' . sprintf(__('Joined "%s"', 'bp-better-messages'), ucwords($group_name)) . '</i>';
                }
            }

            if ( $message_deleted && 'yes' === $message_deleted ) {
                $message =  '<i>' . __( 'This message was deleted.', 'bp-better-messages' ) . '</i>';
            }

            return $message;
        }

        public function verified_member_badge($username, $user_id, $thread_id){
            global $bp_verified_member_admin, $bp_verified_member;
            if ( empty( get_user_meta( $user_id, $bp_verified_member_admin->meta_box->meta_keys['verified'], true ) ) ) {
                return $username;
            }

            $badge = $bp_verified_member->get_verified_badge();

            if( strpos($username, '</a>') !== false ) {
                return str_replace('</a>', $badge . '</a>', $username);
            }

            return $username . $badge;
        }

        public function show_in_bbpress_profile(){
            $messages_total = BP_Messages_Thread::get_total_threads_for_user( get_current_user_id(), 'inbox', 'unread' );
            $class = ( 0 === $messages_total ) ? 'no-count' : 'count';

            $title = sprintf( _x( 'Messages <span class="%s bp-better-messages-unread">%s</span>', 'Messages list sub nav', 'bp-better-messages' ), esc_attr( $class ), bp_core_number_format( $messages_total ) );
            echo '<ul><li><a href="#">' . $title . '</a></li></ul>';
        }

        public function pm_link_bbpress(){
            if( ! is_user_logged_in() ) return false;
            $reply_id = bbp_get_reply_id();
            if ( bbp_is_reply_anonymous( $reply_id ) ) return false;

            $user_id = bbp_get_reply_author_id( $reply_id );

            if( get_current_user_id() === $user_id ) return false;
            $user = get_userdata($user_id);
            $nice_name = $user->user_nicename;
            $link = BP_Better_Messages()->functions->get_link() . '?new-message&to=' . $nice_name;
            echo '<a href="' . $link . '" class="bpbm-private-message-link-buddypress">' . __('Private Message', 'bp-better-message') . '</a>';
        }

        public function asragaros_thread_view($author_id, $author_posts){
            if( ! is_user_logged_in() ) return false;
            if( get_current_user_id() === intval($author_id) ) return false;

            $view_user = get_userdata( $author_id );
            $nice_name = $view_user->user_nicename;
            $link = BP_Better_Messages()->functions->get_link() . '?new-message&to=' . $nice_name;
            echo '<a href="' . $link .'" class="bpbm-asragaros-messages-link">' . __('Private Message', 'bp-better-messages') . ' </a>';
        }

        public function asragaros_profile_messages(){
            if( ! is_user_logged_in() ) return false;
            $url_parts = explode('/', $_SERVER['REQUEST_URI']);
            if( ! in_array('profile', $url_parts) || ! in_array('messages', $url_parts) ) return false;

            global $asgarosforum;

            $asgarosforum->current_view = 'messages';
            $asgarosforum->error        = 'No error';

            echo '<style type="text/css">#af-wrapper .error{display:none}</style>';
            $user_id = $asgarosforum->current_element;
            $userData = get_user_by('id', $user_id);

            if ($userData) {
                if ($asgarosforum->profile->hideProfileLink()) {
                    _e('You need to login to have access to profiles.', 'asgaros-forum');
                } else {
                    $asgarosforum->profile->show_profile_header($userData);
                    $asgarosforum->profile->show_profile_navigation($userData);

                    echo '<div id="profile-content" style="padding: 0">';
                    echo BP_Better_Messages()->functions->get_page();
                    echo '</div>';
                }
            } else {
                _e('This user does not exist.', 'asgaros-forum');
            }
        }

        public function asragaros_profile_link(){
            if( ! is_user_logged_in() ) return false;

            global $asgarosforum;
            $user_id = get_current_user_id();
            $view_id = $asgarosforum->current_element;

            if( $user_id !== $view_id ) {
                $view_user = get_userdata( $view_id );
                $nice_name = $view_user->user_nicename;
                $link = BP_Better_Messages()->functions->get_link() . '?new-message&to=' . $nice_name;
                echo '<a href="' . $link .'">' . __('Private Message', 'bp-better-messages') . ' </a>';
            } else {
                $messages_total = BP_Messages_Thread::get_total_threads_for_user( $user_id, 'inbox', 'unread' );
                $class = ( 0 === $messages_total ) ? 'no-count' : 'count';

                $title = sprintf( _x( 'Messages <span class="%s bp-better-messages-unread">%s</span>', 'Messages list sub nav', 'bp-better-messages' ), esc_attr( $class ), bp_core_number_format( $messages_total ) );

                $link = $asgarosforum->get_link('profile', $user_id) . 'messages/';

                if( $asgarosforum->current_view === 'messages' ) {
                    echo '<a class="active" href="' . $link .'">' . $title . ' </a>';
                } else {
                    echo '<a href="' . $link .'">' . $title . ' </a>';
                }
            }
        }

        public function disable_message_to_deleted_users( $allowed, $user_id, $thread_id ){
            $thread = new BP_Messages_Thread();
            $recipients = $thread->get_recipients( $thread_id );
            unset( $recipients[$user_id] );

            if( count( $recipients ) === 1 ){
                $userdata = get_userdata(array_keys($recipients)[0]);
                if( ! $userdata ) return false;
            }

            return $allowed;
        }

        public function allow_additional_tags( $tags ){
            $tags['u'] = [];
            $tags['sub'] = [];
            $tags['sup'] = [];

            return $tags;
        }

        public function hide_admin_counter(){
            echo '<style type="text/css">.no-count.bp-better-messages-unread{display:none!important}</style>';
        }

        public function disable_thread_for_pmpro_restricted_role( &$args, &$errors ){
            if( ! pmpro_bp_user_can( 'private_messaging', get_current_user_id() ) ) {
                $errors['pmpro_restricted'] = __('Your membership does not allow to use messages', 'bp-better-messages');
            }
        }

        public function disable_messages_for_pmpro_restricted_role( $allowed, $user_id, $thread_id ){
            if( ! pmpro_bp_user_can( 'private_messaging', $user_id ) ) {
                $allowed = false;
                global $bp_better_messages_restrict_send_message;
                $bp_better_messages_restrict_send_message['pmpro_restricted'] = __('Your membership does not allow to use messages', 'bp-better-messages');
            }

            return $allowed;
        }

        public function colors_customizations(){
            if( ! is_user_logged_in() ) return false;

            $rules = array();

            if( ! empty( BP_Better_Messages()->settings['colorGeneral'] ) && BP_Better_Messages()->settings['colorGeneral'] !== '#21759b' ){
                $main_color     = BP_Better_Messages()->settings['colorGeneral'];
                $rgba_color_075 = BP_Better_Messages()->functions->hex2rgba($main_color, 0.075);
                $rgba_color_06  = BP_Better_Messages()->functions->hex2rgba($main_color, 0.6);
                $rgba_color_003  = BP_Better_Messages()->functions->hex2rgba($main_color, 0.03);

                $rules[] = '.bp-better-messages-list .tabs>div[data-tab=messages] .unread-count, .bp-better-messages-mini .chats .chat .head .unread-count{background:' . $main_color . ' !important}';
                $rules[] = '.bp-messages-wrap .chat-header .fas,.bp-messages-wrap .chat-header>a,.bp-messages-wrap:not(.bp-messages-mobile) .reply .send button[type=submit],.uppy-Dashboard-browse,.bp-messages-wrap.mobile-ready:not(.bp-messages-mobile) .bp-messages-mobile-tap{color:' . $main_color . ' !important}';
                $rules[] = '.uppy-Dashboard-close .UppyIcon{fill:' . $main_color . '!important}';
                $rules[] = '#bp-better-messages-mini-mobile-open{background:' . $main_color . '}';
                $rules[] = '.bp-messages-wrap .bp-emojionearea.focused,.bp-messages-wrap .new-message form>div input:focus,.bp-messages-wrap .active .taggle_list,.bp-messages-wrap .chat-header .bpbm-search form input:focus{border-color:' . $main_color . '!important;-moz-box-shadow: inset 0 1px 1px '. $rgba_color_075 . ', 0 0 8px ' . $rgba_color_06 . ';-webkit-box-shadow: inset 0 1px 1px ' . $rgba_color_075 . ', 0 0 8px ' . $rgba_color_06 . ';box-shadow: inset 0 1px 1px ' . $rgba_color_075 . ', 0 0 8px ' . $rgba_color_06 . '}';
                $rules[] = '.bp-messages-wrap #send-to .ui-autocomplete{border-color: ' . $main_color . ';-moz-box-shadow: inset 0 0 0 ' . $rgba_color_075 . ', 0 3px 3px ' . $rgba_color_06 . ';-webkit-box-shadow: inset 0 0 0 ' . $rgba_color_075 . ', 0 3px 3px ' . $rgba_color_06 . ';box-shadow: inset 0 0 0 ' . $rgba_color_075 . ', 0 3px 3px ' . $rgba_color_06 . '}';
                $rules[] = '.bp-messages-wrap .list .messages-stack .content .info .name a{color: ' . $main_color . '}';
                $rules[] = '.bp-messages-wrap.bp-messages-mobile .reply .send button[type=submit]{background-color: ' . $main_color . '!important}';
                $rules[] = '.bp-messages-wrap .threads-list .thread.bp-messages-active-thread {background: ' . $rgba_color_003 . '}';

            }

            if( count( $rules ) > 0 ) {
                echo '<style type="text/css">';
                echo implode('', $rules);
                echo '</style>';
            }
        }

        public function override_last_activity_2($object_id, $meta_value){
            update_user_meta($object_id, 'bpbm_last_activity', $meta_value);
        }

        public function override_last_activity($null, $object_id, $meta_key, $meta_value, $prev_value){
            if( $meta_key === 'last_activity' ) {
                update_user_meta($object_id, 'bpbm_last_activity', $meta_value);
            }

            return $null;
        }

        public function max_height_css(){
            if( ! is_user_logged_in() ) return false;
            $max_height = apply_filters('bp_better_messages_max_height', 500);

            echo '<style type="text/css">body:not(.bp-messages-mobile) .bp-messages-wrap.bp-messages-wrap-main > .scroller,body:not(.bp-messages-mobile) .bp-messages-wrap.bp-messages-wrap-main > .bp-messages-side-threads-wrapper > .bp-messages-column > .scroller,body:not(.bp-messages-mobile) .bp-messages-wrap.bp-messages-wrap-main > .bp-messages-side-threads-wrapper > .bp-messages-column > .scroller > .scroller,body:not(.bp-messages-mobile) .bp-messages-wrap.bp-messages-wrap-main > .scroller > .scroller{max-height:'. $max_height .'px;}body:not(.bp-messages-mobile) .bp-messages-side-threads-wrapper:not(.threads-hidden){max-height:' . ($max_height) .'px!important;}</style>';
        }

        public function buddyboss_notifications_fix( $array ){
            if ( function_exists( 'buddyboss_theme_register_required_plugins' ) || class_exists('BuddyBoss_Theme') ) {
                if( count( $array['threads'] ) > 0 && isset( $array['total'] ) ) {
                    foreach ($array['threads'] as $i => $thread) {
                        if ( strtotime($thread->last_message_date) <= 0 ) {
                            unset($array['threads'][$i]);
                            $array['total']--;
                        }
                    }
                }

                if( $array['total'] < 0 ) $array['total'] = 0;
            }

            return $array;
        }

        public function beehive_theme_integration(){
            if( ! class_exists('Beehive') ) return false;

            $options = get_option('beehive_opts', [
                'primary' => '#21759b'
            ]);

            if( is_array($options) && isset($options['primary'] ) ) {
                $main_color = $options['primary'];
                $rgba_color_075 = BP_Better_Messages()->functions->hex2rgba($main_color, 0.075);
                $rgba_color_06  = BP_Better_Messages()->functions->hex2rgba($main_color, 0.6);
                ?><style type="text/css">
                    body.bp-messages-mobile header{
                        display: none;
                    }

                    .bp-messages-wrap.bp-messages-mobile .reply .send button[type=submit]{
                        background: #f7f7f7 !important;
                    }

                    .bp-better-messages-list .tabs>div[data-tab=messages] .unread-count, .bp-better-messages-mini .chats .chat .head .unread-count{
                        background: <?php echo $main_color; ?> !important;
                    }

                    .bp-messages-wrap .chat-header .fas,
                    .bp-messages-wrap .chat-header>a,
                    .bp-messages-wrap .reply .send button[type=submit],
                    .uppy-Dashboard-browse,
                    .bp-messages-wrap.mobile-ready:not(.bp-messages-mobile) .bp-messages-mobile-tap{
                        color: <?php echo $main_color; ?> !important;
                    }

                    .uppy-Dashboard-close .UppyIcon{
                        fill: <?php echo $main_color; ?> !important;
                    }

                    .bp-messages-wrap .bp-emojionearea.focused,
                    .bp-messages-wrap .new-message form>div input:focus,
                    .bp-messages-wrap .active .taggle_list,
                    .bp-messages-wrap .chat-header .bpbm-search form input:focus{
                        border-color: <?php echo $main_color; ?>!important;
                        -moz-box-shadow: inset 0 1px 1px <?php echo $rgba_color_075; ?>, 0 0 8px <?php echo $rgba_color_06; ?>;
                        -webkit-box-shadow: inset 0 1px 1px <?php echo $rgba_color_075; ?>, 0 0 8px <?php echo $rgba_color_06; ?>;
                        box-shadow: inset 0 1px 1px <?php echo $rgba_color_075; ?>, 0 0 8px <?php echo $rgba_color_06; ?>;
                    }

                    .bp-messages-wrap #send-to .ui-autocomplete{
                        border-color: <?php echo $main_color; ?>;
                        -moz-box-shadow: inset 0 0 0 <?php echo $rgba_color_075; ?>, 0 3px 3px <?php echo $rgba_color_06; ?>;
                        -webkit-box-shadow: inset 0 0 0 <?php echo $rgba_color_075; ?>, 0 3px 3px <?php echo $rgba_color_06; ?>;
                        box-shadow: inset 0 0 0 <?php echo $rgba_color_075; ?>, 0 3px 3px <?php echo $rgba_color_06; ?>;
                    }
                </style>
                <script type="text/javascript">
                    jQuery(document).on('bp-better-messages-update-unread', function( event, unread ) {
                        var private_messages = jQuery('#nav_private_messages');

                        if( unread > 0 ){
                            var count = private_messages.find('span.count');
                            if( count.length === 0 ){
                                private_messages.append('<span class="count">' + unread + '</span>');
                            } else {
                                private_messages.find('.count').text(unread);
                            }
                        } else {
                            private_messages.find('span.count').remove();
                        }
                    });
                </script>
                <?php
            }
        }

        public function update_last_activity(){
            if( is_user_logged_in() ) {
                $user_id = get_current_user_id();
                bp_update_user_last_activity($user_id);
            }
        }

        public function mobile_popup_button(){
            if( ! is_user_logged_in() || BP_Better_Messages()->settings['mobilePopup'] == '0' ) return '';
            $count = BP_Messages_Thread::get_total_threads_for_user( get_current_user_id(), 'inbox', 'unread' );
            $class = ($count === 0) ? 'no-count' : '';
            echo '<div id="bp-better-messages-mini-mobile-open" class="bp-messages-wrap"><i class="fas fa-comments"></i><span class="count ' . $class . ' bp-better-messages-unread">' . $count . '</span></div>';
            echo '<div id="bp-better-messages-mini-mobile-container" class="bp-messages-wrap"></div>';
        }

        public function buddyboss_inbox_counter(){
            if( class_exists('BuddyBoss_Theme') ){ ?>
                <script type="text/javascript">
                    jQuery(document).on('bp-better-messages-update-unread', function( event, unread ) {
                        var messages_count = jQuery('.header-notifications.user-messages span');
                        if( unread > 0 ){
                            messages_count.text(unread).attr('class', 'count');
                        } else {
                            messages_count.text(unread).attr('class', 'no-alert');
                        }
                    });
                </script>
            <?php } else if( function_exists( 'buddyboss_theme_register_required_plugins' ) ){ ?>
                <script type="text/javascript">
                    jQuery(document).on('bp-better-messages-update-unread', function( event, unread ) {
                        var messages_count = jQuery('.notification-wrap.messages-wrap .count');


                        if( unread > 0 ){
                            if( messages_count.length === 0 ){
                                jQuery('.notification-wrap.messages-wrap').find('.bb-icon-inbox-small').parent().append( '<span class="count">' + unread + '</span>' );
                            } else {
                                messages_count.text(unread).show();
                            }

                        } else {
                            messages_count.text(unread).hide();
                        }
                    });
                </script>
            <?php }
        }


        public function replace_unread_count( $unread_count, $user_id ){
            return BP_Messages_Thread::get_total_threads_for_user( $user_id, 'inbox', 'unread' );
        }
        

        public function fix_404(){
            if ( function_exists('bp_core_no_access') && bp_is_current_component( 'bp-messages' ) && ! is_user_logged_in() ) {
                bp_core_no_access();
            }
        }

        public function fix_youzer(){
            if( function_exists('bp_nav_menu_get_item_url') ){
                $messages_link = bp_nav_menu_get_item_url( 'messages' );
            }
        }

        public function themes_adaptation(){
            $theme = wp_get_theme();
            $theme_name = $theme->get_template();

            switch ($theme_name){
                case 'boss':
                    echo '<style type="text/css">';
                    echo 'body.bp-messages-mobile #mobile-header{display:none}';
                    echo 'body.bp-messages-mobile #inner-wrap{margin-top:0}';
                    echo 'body.bp-messages-mobile .site{min-height:auto}';
                    echo '</style>';
                    break;
            }
        }

        public function disableStatuses(){
            ?><style type="text/css">.bp-messages-wrap .list .messages-stack .content .messages-list li .status{display: none !important;}.bp-messages-wrap .list .messages-stack .content .messages-list li .favorite{right: 5px !important;}</style><?php
        }

        public function disable_group_threads(&$args, &$errors){
            $recipients = $args['recipients'];
            if(count($recipients) > 1) {
                $message = __('You can start conversation only with 1 user per thread', 'bp-better-messages');
                $errors[] = $message;
            }
        }

        public function disable_message_for_blocked_user( $allowed, $user_id, $thread_id ){
            if( ! class_exists('BPTK_Block') ) return $allowed;

            $participants = BP_Better_Messages()->functions->get_participants($thread_id);

            foreach( $participants['recipients'] as $recipient_user_id ){
                $list = get_user_meta( $recipient_user_id, 'bptk_block', true );
                if ( empty($list) ) {
                    $list = array();
                }
                $_list = apply_filters( 'get_blocked_users', $list, $recipient_user_id );
                $recipient_blocked = array_filter( $_list );

                if( in_array( $user_id, $recipient_blocked ) ){
                    global $bp_better_messages_restrict_send_message;
                    $bp_better_messages_restrict_send_message['blocked_by_user'] = __('You was blocked by recipient', 'bp-better-messages');
                    $allowed = false;
                }
            }


            return $allowed;
        }

        public function disable_message_for_blocked_restricted_role( $allowed, $user_id, $thread_id ){

            $user             = wp_get_current_user();
            $restricted_roles = (array)  BP_Better_Messages()->settings['restrictNewReplies'];
            $user_roles       = (array) $user->roles;

            $is_restricted = false;
            foreach( $user_roles as $user_role ){
                if( in_array( $user_role, $restricted_roles ) ){
                    $is_restricted = true;
                }
            }

            if( $is_restricted ) {
                $allowed = false;
                global $bp_better_messages_restrict_send_message;
                $bp_better_messages_restrict_send_message['role_reply_restricted'] = BP_Better_Messages()->settings['restrictNewRepliesMessage'];
            }
            return $allowed;
        }

        public function disable_thread_for_blocked_restricted_role( &$args, &$errors ){

            $user             = wp_get_current_user();
            $restricted_roles = (array)  BP_Better_Messages()->settings['restrictNewThreads'];
            $user_roles       = (array) $user->roles;

            $is_restricted = false;
            foreach( $user_roles as $user_role ){
                if( in_array( $user_role, $restricted_roles ) ){
                    $is_restricted = true;
                }
            }

            if( $is_restricted ) {
                $errors['restrictNewThreadsMessage'] = BP_Better_Messages()->settings['restrictNewThreadsMessage'];
            }
        }

        public function disable_non_friends_reply( $allowed, $user_id, $thread_id ){
            $participants = BP_Better_Messages()->functions->get_participants($thread_id);
            if(count($participants['users']) !== 2) return $allowed;
            unset($participants['users'][$user_id]);
            reset($participants['users']);


            $friend_id = key($participants['users']);
            /**
             * Allow users reply to admins even if not friends
             */
            if( current_user_can('manage_options') || user_can( $friend_id, 'manage_options' ) ) {
                return $allowed;
            }

            return friends_check_friendship($user_id, $friend_id);
        }

        public function disable_start_thread_if_thread_exist(&$args, &$errors){
            $recipients = $args['recipients'];
            if(count($recipients) > 1) return false;
            $threadExists = array();
            foreach($recipients as $recipient){
                $user = get_user_by('slug', $recipient);

                $from = get_current_user_id();
                $to   = $user->ID;

                $threads = BP_Better_Messages()->functions->find_existing_threads($from, $to);

                if( count($threads) > 0) {
                    $threadExists[] = $recipient;

                    if(BP_Better_Messages()->settings['redirectToExistingThread'] == '1'){
                        $args['thread_id'] = $threads[0];

                        if( apply_filters('bp_better_messages_can_send_message', BP_Messages_Thread::check_access( $args['thread_id'] ), $from, $args['thread_id'] ) ) {
                            messages_new_message( $args );
                        }

                        wp_send_json( array(
                            'result'   => $threads[0]
                        ) );
                        exit;
                    }
                }
            }

            if(count($threadExists) > 0){
                $message = sprintf(__('You already have threads with %s', 'bp-better-messages'), implode(', ', $threadExists));
                $errors[] = $message;
            }
        }

        public function disable_start_thread_for_non_friends(&$args, &$errors){
            if( current_user_can('manage_options' ) ) {
                return null;
            }

            $recipients = $args['recipients'];

            $notFriends = array();
            foreach($recipients as $recipient){
                $user = get_user_by('slug', $recipient);
                if( ! friends_check_friendship( get_current_user_id(), $user->ID ) ) {
                    $notFriends[] = $recipient;
                }
            }

            if(count($notFriends) > 0){
                $message = sprintf(__('%s not on your friends list', 'bp-better-messages'), implode(', ', $notFriends));
                $errors[] = $message;
            }
        }

        public function catch_fast_thread(){
            if(
                (rtrim(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']), '?') === str_replace(site_url(''), '', BP_Better_Messages()->functions->get_link()))
                && isset($_GET['new-message'])
                && isset($_GET['fast'])
                && isset($_GET['to'])
                && ! empty($_GET['fast'])
                && ! empty($_GET['to'])
            ){
                $to = get_user_by('slug', sanitize_text_field($_GET['to']));
                if( ! $to ) return false;

                if( BP_Better_Messages()->settings['singleThreadMode'] == '1' ) {
                    $threads = BP_Better_Messages()->functions->find_existing_threads(get_current_user_id(), $to->ID);
                    if( count($threads) > 0) {
                        wp_redirect(BP_Better_Messages()->functions->get_link() . '?thread_id=' . $threads[0]);
                        exit;
                    }
                }

                $thread_id = BP_Better_Messages()->functions->get_pm_thread_id($to->ID);
                $url = BP_Better_Messages()->functions->get_link() . '?thread_id=' . $thread_id;

                wp_redirect($url);
                exit;
            }
        }

        public function admin_notice(){
            if( ! class_exists('BuddyPress')){
               if(BP_Better_Messages()->settings['chatPage'] == '0'){
                   echo '<div class="notice notice-error">';
                   echo '<p><b>BP Better Messages</b> require <b><a href="'. admin_url('options-general.php?page=bp-better-messages#general').'">installing Chat Page</a></b> or installed <b>BuddyPress</b> with <b>Messages Component</b> active.</p>';
                   echo '</div>';
               }
            } else {
                if( ! bp_is_active('messages') ) {
                    echo '<div class="notice notice-error">';
                    echo '<p><b>BP Better Messages</b> require <b>BuddyPress</b> <b>Messages Component</b> to be active. <a href="'.admin_url('options-general.php?page=bp-components').'">Activate</a></p>';
                    echo '</div>';
                }
            }
        }

        public function chat_page($content){
            $page_id = get_the_ID();
            $chat_page_id = BP_Better_Messages()->settings['chatPage'];

            if( defined('ICL_LANGUAGE_CODE') ){
                $chat_page_id = apply_filters( 'wpml_object_id', $chat_page_id, 'page', true, ICL_LANGUAGE_CODE );
            }

            if( $chat_page_id != $page_id ) return $content;

            if( ! is_user_logged_in() ){
                ob_start();
                wp_login_form();
                return ob_get_clean();
            }

            if( function_exists('pmpro_has_membership_access') ) {
                // PM PRO PLUGIN ACTIVE
                $hasaccess = pmpro_has_membership_access(NULL, NULL, false);

                if( ! $hasaccess ) return $content;
            }

            $messages_content = BP_Better_Messages()->functions->get_page();

            if( strpos($content, '[bp-better-messages]') !== FALSE ){
                $content = str_replace( '[bp-better-messages]', $messages_content, $content );
            } else {
                $content = $messages_content;
            }

            return $content;
        }

        function exclude_attachments($query){
	        if( BP_Better_Messages()->settings['attachmentsHide'] !== '1' ) return $query;

		    $meta_query = $query['meta_query'];
	        if( ! is_array($meta_query) ) $meta_query = array();

	        $meta_query[] = array(
	        	'key'     => 'bp-better-messages-attachment',
		        'value'   => '1',
		        'compare' => 'NOT EXISTS'
	        );

	        $query['meta_query'] = $meta_query;
	        return $query;
        }

        function cron_intervals( $schedules )
        {
            /*
             * Cron for our new mailer!
             */
            $schedules[ 'fifteen_minutes' ] = array(
                'interval' => 60 * 15,
                'display'  => esc_html__( 'Every Fifteen Minutes' ),
            );

            $schedules[ 'one_minute' ] = array(
                'interval' => 60,
                'display'  => esc_html__( 'Every Minute' ),
            );

            return $schedules;
        }

        function pm_link_nouveau($buttons, $user_id, $type){
            if( $user_id === get_current_user_id() ) {
                return $buttons;
            }

            if( isset( $buttons['private_message'] ) ){
                $buttons['private_message']['button_attr']['href'] = $this->pm_link();
            }

            return $buttons;
        }

        public function pm_link_legacy(){
            if( ! is_user_logged_in() ) return false;
            $user_id = BP_Better_Messages()->functions->get_member_id();
            if( get_current_user_id() === $user_id ) return false;
            echo '<div class="generic-button bp-better-messages-private-message-link"><a href="' . $this->pm_link() . '">' . __('Private Message', 'bp-better-message') . '</a></div>';
        }

        public function pm_link( $link = false )
        {
            if( BP_Better_Messages()->settings['fastStart'] == '1' ){
                return BP_Better_Messages()->functions->get_link(get_current_user_id()) . '?new-message&fast=1&to=' . bp_core_get_username( BP_Better_Messages()->functions->get_member_id() );
            } else {
                return BP_Better_Messages()->functions->get_link(get_current_user_id()) . '?new-message&to=' . bp_core_get_username( BP_Better_Messages()->functions->get_member_id() );
            }
        }

        public function pm_link_args($args){
            if ( ! is_user_logged_in() ) {
                return false;
            }

            $args['link_href'] = $this->pm_link();

            return $args;
        }

        public function thread_link( $thread_link, $thread_id )
        {
            return BP_Better_Messages()->functions->get_link() . '?thread_id=' . $thread_id;
        }

        public function redirect_standard_component()
        {
            if ( bp_is_messages_component() ) {
                $link = BP_Better_Messages()->functions->get_link();

                if( bp_action_variable(0) !== false ){
                    $link = BP_Better_Messages()->functions->get_link() . '?thread_id=' . bp_action_variable(0);
                }

                if(isset($_GET['r'])){
                    $username = sanitize_text_field($_GET['r']);
                    $user = get_user_by('slug', $username);
                    if( $user ) {
                        $nice_name = $user->user_nicename;
                        $link = BP_Better_Messages()->functions->get_link() . '?new-message&to=' . $nice_name;
                    }
                }

                wp_redirect( $link );
                exit;
            }
        }

        public function update_db_if_needed(){
            $db_version = get_option('bp_better_messages_db_version', false);
            $current_db = '1.0';
            if( $db_version !== $current_db ) {
                $sql = array();
                $charset_collate = $GLOBALS['wpdb']->get_charset_collate();
                $bp_prefix = $GLOBALS['wpdb']->base_prefix;

                $sql[] = "CREATE TABLE {$bp_prefix}bp_messages_messages (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    thread_id bigint(20) NOT NULL,
                    sender_id bigint(20) NOT NULL,
                    subject varchar(200) NOT NULL,
                    message longtext NOT NULL,
                    date_sent datetime NOT NULL,
                    KEY sender_id (sender_id),
                    KEY thread_id (thread_id)
                ) {$charset_collate};";

                $sql[] = "CREATE TABLE {$bp_prefix}bp_messages_recipients (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    user_id bigint(20) NOT NULL,
                    thread_id bigint(20) NOT NULL,
                    unread_count int(10) NOT NULL DEFAULT '0',
                    sender_only tinyint(1) NOT NULL DEFAULT '0',
                    is_deleted tinyint(1) NOT NULL DEFAULT '0',
                    KEY user_id (user_id),
                    KEY thread_id (thread_id),
                    KEY is_deleted (is_deleted),
                    KEY sender_only (sender_only),
                    KEY unread_count (unread_count)
                ) {$charset_collate};";

                $sql[] = "CREATE TABLE {$bp_prefix}bp_messages_notices (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    subject varchar(200) NOT NULL,
                    message longtext NOT NULL,
                    date_sent datetime NOT NULL,
                    is_active tinyint(1) NOT NULL DEFAULT '0',
                    KEY is_active (is_active)
                ) {$charset_collate};";

                $sql[] = "CREATE TABLE {$bp_prefix}bp_messages_meta (
                    id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    message_id bigint(20) NOT NULL,
                    meta_key varchar(255) DEFAULT NULL,
                    meta_value longtext DEFAULT NULL,
                    KEY message_id (message_id),
                    KEY meta_key (meta_key(191))
                ) {$charset_collate};";


                $sql[] = "CREATE TABLE IF NOT EXISTS `{$bp_prefix}bpbm_threadsmeta` (
                  `meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `bpbm_threads_id` bigint(20) NOT NULL,
                  `meta_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                  `meta_value` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`meta_id`),
                  KEY `meta_key` (`meta_key`(191)),
                  KEY `thread_id` (`bpbm_threads_id`) USING BTREE
                ) {$charset_collate};";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);

                update_option('bp_better_messages_db_version', $current_db);
            }
        }

        public function remove_standard_topbar( $wp_admin_bar )
        {
            $wp_admin_bar->remove_node( 'my-account-messages' );
        }

    }

endif;

function BP_Better_Messages_Hooks()
{
    return BP_Better_Messages_Hooks::instance();
}
