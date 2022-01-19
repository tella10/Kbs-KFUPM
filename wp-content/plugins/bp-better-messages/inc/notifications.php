<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Notifications' ) ):

    class BP_Better_Messages_Notifications
    {

        public static function instance()
        {

            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Notifications();
            }

            return $instance;
        }

        public function __construct()
        {
            add_action( 'init', array( $this, 'remove_standard_notification' ) );

            add_action( 'bp_better_messages_send_notifications', array( $this, 'notifications_sender' ) );
            add_action( 'init', array( $this, 'register_event' ) );

            if(class_exists('BP_Notifications_Notification')){
                add_action( 'messages_thread_mark_as_read', array($this, 'mark_notification_as_read') );
            }
        }

        public function mark_notification_as_read($target_thread_id){
            global $wpdb;

            $notifications = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM `{$wpdb->base_prefix}bp_notifications` 
            WHERE `user_id` = %d
            AND `component_name` = 'messages' 
            AND `component_action` = 'new_message' 
            AND `is_new` = 1 
            ORDER BY `id` DESC", get_current_user_id()));


            $notifications_ids = array();
            foreach($notifications as $notification){
                $thread_id = $wpdb->get_var($wpdb->prepare("SELECT thread_id FROM `{$wpdb->base_prefix}bp_messages_messages` WHERE `id` = %d", $notification->item_id));
                if($thread_id === NULL)
                {
                    bp_notifications_delete_notification($notification->id);
                    continue;
                } else {
                    if($thread_id == $target_thread_id) $notifications_ids[] = $notification->id;
                }
            }

            if( count($notifications_ids) > 0){
                $notifications_ids = array_unique($notifications_ids);
                foreach($notifications_ids as $notification_id){
                    BP_Notifications_Notification::update(
                        array( 'is_new' => false ),
                        array( 'id'     => $notification_id )
                    );
                }
            }
        }

        public function register_event()
        {
            if ( ! wp_next_scheduled( 'bp_better_messages_send_notifications' ) ) {
                wp_schedule_event( time(), 'fifteen_minutes', 'bp_better_messages_send_notifications' );
            }
        }

        public function install_template_if_missing(){
            if( ! function_exists('bp_get_email_post_type') ) return false;

            $defaults = array(
                'post_status' => 'publish',
                'post_type'   => bp_get_email_post_type(),
            );

            $emails = array(
                'messages-unread-group' => array(
                    /* translators: do not remove {} brackets or translate its contents. */
                    'post_title'   => __( '[{{{site.name}}}] You have unread messages: {{subject}}', 'bp-better-messages' ),
                    /* translators: do not remove {} brackets or translate its contents. */
                    'post_content' => __( "You have unread messages: &quot;{{subject}}&quot;\n\n{{{messages.html}}}\n\n<a href=\"{{{thread.url}}}\">Go to the discussion</a> to reply or catch up on the conversation.", 'bp-better-messages' ),
                    /* translators: do not remove {} brackets or translate its contents. */
                    'post_excerpt' => __( "You have unread messages: \"{{subject}}\"\n\n{{messages.raw}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'bp-better-messages' ),
                )
            );

            $descriptions[ 'messages-unread-group' ] = __( 'A member has unread private messages.', 'bp-better-messages' );

            // Add these emails to the database.
            foreach ( $emails as $id => $email ) {
                $post_args = bp_parse_args( $email, $defaults, 'install_email_' . $id );

                $template = get_page_by_title( $post_args[ 'post_title' ], OBJECT, bp_get_email_post_type() );
                if ( $template ) continue;

                $post_id = wp_insert_post( $post_args );

                if ( !$post_id ) {
                    continue;
                }

                $tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
                foreach ( $tt_ids as $tt_id ) {
                    $term = get_term_by( 'term_taxonomy_id', (int)$tt_id, bp_get_email_tax_type() );
                    wp_update_term( (int)$term->term_id, bp_get_email_tax_type(), array(
                        'description' => $descriptions[ $id ],
                    ) );
                }
            }
        }

        public function notifications_sender()
        {
            global $wpdb;

            $this->install_template_if_missing();

            /**
             * Update users without activity
             */
            $user_without_last_activity = get_users( array(
                'number'       => -1,
                'meta_key'     => 'bpbm_last_activity',
                'meta_compare' => 'NOT EXISTS',
                'fields'       => 'ids'
            ) );

            if( count( $user_without_last_activity ) > 0 ){
                foreach( $user_without_last_activity as $user_id ){
                    $last_activity = get_user_meta( $user_id, 'last_activity', true );

                    if( ! empty( $last_activity ) ){
                        update_user_meta( $user_id, 'bpbm_last_activity', $last_activity );
                    } else {
                        update_user_meta( $user_id, 'bpbm_last_activity', gmdate( 'Y-m-d H:i:s',  0 ) );
                    }
                }
            }

            $time = gmdate( 'Y-m-d H:i:s', ( strtotime( bp_core_current_time() ) - 600 ) );

            $unread_threads = $wpdb->get_results( "SELECT
              {$wpdb->base_prefix}usermeta.meta_value AS last_visit,
              {$wpdb->base_prefix}usermeta.user_id,
              {$wpdb->base_prefix}bp_messages_recipients.thread_id,
              {$wpdb->base_prefix}bp_messages_recipients.unread_count,
              {$wpdb->base_prefix}bp_messages_messages.id AS last_id
            FROM {$wpdb->base_prefix}bp_messages_recipients
              INNER JOIN {$wpdb->base_prefix}usermeta
                ON {$wpdb->base_prefix}bp_messages_recipients.user_id = {$wpdb->base_prefix}usermeta.user_id
              INNER JOIN {$wpdb->base_prefix}bp_messages_messages
                ON {$wpdb->base_prefix}bp_messages_messages.thread_id = {$wpdb->base_prefix}bp_messages_recipients.thread_id
                  AND {$wpdb->base_prefix}bp_messages_messages.id = (
                      SELECT MAX(m2.id)
                      FROM {$wpdb->base_prefix}bp_messages_messages m2 
                      WHERE m2.thread_id = {$wpdb->base_prefix}bp_messages_recipients.thread_id
                  )
            WHERE {$wpdb->base_prefix}usermeta.meta_key = 'bpbm_last_activity'
            AND STR_TO_DATE({$wpdb->base_prefix}usermeta.meta_value, '%s') < " . $wpdb->prepare('%s', $time) . "
            AND {$wpdb->base_prefix}bp_messages_recipients.unread_count > 0
            AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
            GROUP BY {$wpdb->base_prefix}usermeta.user_id,
                     {$wpdb->base_prefix}bp_messages_recipients.thread_id" );

            $last_notified = array();

            foreach ( array_unique( wp_list_pluck( $unread_threads, 'user_id' ) ) as $user_id ) {
                $meta = get_user_meta( $user_id, 'bp-better-messages-last-notified', true );
                $last_notified[ $user_id ] = ( !empty( $meta ) ) ? $meta : array();
            }

            $gmt_offset = get_option('gmt_offset') * 3600;

            foreach ( $unread_threads as $thread ) {
                $user_id = $thread->user_id;
                $thread_id = $thread->thread_id;

                $muted_threads = BP_Better_Messages()->functions->get_user_muted_threads( $user_id );
                if( isset( $muted_threads[ $thread_id ] ) ){
                    continue;
                }

                if ( function_exists('bp_send_email') && get_user_meta( $user_id, 'notification_messages_new_message', true ) == 'no' ) {
                    $last_notified[ $user_id ][ $thread_id ] = $thread->last_id;
                    continue;
                }

                $ud = get_userdata( $user_id );

                if ( ! isset( $last_notified[ $user_id ][ $thread_id ] ) || ( $thread->last_id > $last_notified[ $user_id ][ $thread_id ] ) ) {

                    $user_last = ( isset( $last_notified[ $user_id ][ $thread_id ] ) ) ? $last_notified[ $user_id ][ $thread_id ] : 0;

                    $messages = array_reverse( $wpdb->get_results( $wpdb->prepare( "
                        SELECT
                          {$wpdb->base_prefix}bp_messages_messages.message,
                          {$wpdb->base_prefix}bp_messages_messages.sender_id,
                          {$wpdb->base_prefix}bp_messages_messages.subject,
                          {$wpdb->base_prefix}bp_messages_messages.date_sent
                        FROM {$wpdb->base_prefix}bp_messages_messages
                        WHERE {$wpdb->base_prefix}bp_messages_messages.thread_id = %d
                        AND {$wpdb->base_prefix}bp_messages_messages.id > %d 
                        ORDER BY id DESC
                        LIMIT 0, %d
                    ", $thread->thread_id, $user_last, $thread->unread_count ) ) );

                    if ( empty( $messages ) ) {
                        continue;
                    }

                    foreach($messages as $index => $message){
                        if( $message->message ){
                            $is_sticker = strpos( $message->message, '<span class="bpbm-sticker">' ) !== false;
                            if( $is_sticker ){
                                $message->message = __('Sticker', 'bp-better-message');
                            }
                        }
                    }

                    if ( empty( $messages ) ) {
                        continue;
                    }

                    $messageRaw = '';
                    $messageHtml = '<table style="margin: 0!important;width: 100%;"><tbody>';
                    $last_id = 0;
                    foreach ( $messages as $message ) {
                        $sender = get_userdata( $message->sender_id );

                        $timestamp = strtotime( $message->date_sent ) + $gmt_offset;
                        $time_format = get_option( 'time_format' );

                        if ( gmdate( 'Ymd' ) != gmdate( 'Ymd', $timestamp ) ) {
                            $time_format .= ' ' . get_option( 'date_format' );
                        }

                        $time = wp_strip_all_tags( stripslashes( date_i18n( $time_format, $timestamp ) ) );
                        $author = wp_strip_all_tags( stripslashes( sprintf( __( '%s wrote:', 'bp-better-messages' ), $sender->display_name ) ) );
                        $message = wp_strip_all_tags( stripslashes( $message->message ) );

                        if ( $last_id == 0 || $last_id != $sender->ID ) {
                            $messageHtml .= '<tr><td colspan="2"><b>' . $author . '</b></td></tr>';
                            $messageRaw .= "$author\n";
                        }

                        $messageRaw .= "$time\n$message\n\n";

                        $messageHtml .= '<tr>';
                        $messageHtml .= '<td style="padding-right: 10px;">' . $message . '</td>';
                        $messageHtml .= '<td style="width: 1px;white-space: nowrap;vertical-align: top;"><i>' . $time . '</i></td>';
                        $messageHtml .= '</tr>';

                        $last_id = $sender->ID;
                    }

                    $messageHtml .= '</tbody></table>';


                    if( function_exists('bp_notifications_add_notification') ){
                        bp_notifications_add_notification( array(
                            'user_id'           => $user_id,
                            'item_id'           => $thread->last_id,
                            'secondary_item_id' => $last_id,
                            'component_name'    => buddypress()->messages->id,
                            'component_action'  => 'new_message',
                            'date_notified'     => bp_core_current_time(),
                            'is_new'            => 1
                        ) );
                    }

                    if( function_exists('bp_send_email') ){
                        $args = array(
                            'tokens' =>
                                apply_filters( 'bp_better_messages_notification_tokens', array(
                                    'messages.html' => $messageHtml,
                                    'messages.raw'  => $messageRaw,
                                    'sender.name'   => $sender->display_name,
                                    'thread.id'     => $thread_id,
                                    'thread.url'    => esc_url( BP_Better_Messages()->functions->get_link( $user_id ) . '?thread_id=' . $thread_id ),
                                    'subject'       => sanitize_text_field( stripslashes( $messages[ 0 ]->subject ) ),
                                    'unsubscribe'   => esc_url( bp_email_get_unsubscribe_link( array(
                                        'user_id'           => $user_id,
                                        'notification_type' => 'messages-unread',
                                    ) ) )
                                ),
                                    $ud, // userdata object of receiver
                                    $sender, // userdata object of sender
                                    $thread_id
                                ),
                        );

                        bp_send_email( 'messages-unread-group', $ud, $args );
                    } else {
                        $user = get_userdata( $user_id );

                        $subject = sprintf( _x( 'You have unread messages: "%s"', 'Email notification header for non BuddyPress websites', 'bp-better-messages' ), sanitize_text_field( stripslashes( $messages[ 0 ]->subject ) ) );
                        $plainHeader  = sprintf( _x( 'Hi %s', 'Email notification header for non BuddyPress websites', 'bp-better-messages' ), $user->display_name ) . ",\r\n";
                        $plainHeader .= $subject . "\r\n";
                        $plainHeader .= "\r\n\r\n\r\n";


                        $plainFooter  = "\r\n\r\n\r\n";
                        $plainFooter .= _x( "Go to the discussion to reply or catch up on the conversation:", 'Email notification header for non BuddyPress websites', 'bp-better-messages') . "\r\n";
                        $plainFooter .= esc_url( BP_Better_Messages()->functions->get_link( $user_id ) . '?thread_id=' . $thread_id );

                        $messageRaw = $plainHeader . $messageRaw . $plainFooter;
                        wp_mail( $user->user_email, $subject, $messageRaw );
                    }

                    $last_notified[ $user_id ][ $thread_id ] = $thread->last_id;
                }

            }

            foreach ( $last_notified as $user_id => $threads ) {
                update_user_meta( $user_id, 'bp-better-messages-last-notified', $threads );
            }
        }

        public function remove_standard_notification()
        {
            remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
            remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
        }
    }

endif;

function BP_Better_Messages_Notifications()
{
    return BP_Better_Messages_Notifications::instance();
}
