<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Ajax' ) ):

    class BP_Better_Messages_Ajax
    {

        public static function instance()
        {

            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Ajax();
            }

            return $instance;
        }

        public function __construct()
        {
            /**
             * Ajax checker actions
             */
            add_action( 'wp_ajax_bp_messages_thread_check_new', array( $this, 'thread_check_new' ) );
            add_action( 'wp_ajax_bp_messages_check_new',        array( $this, 'check_new' ) );

            /**
             * New thread actions
             */
            add_action( 'wp_ajax_bp_messages_new_thread',   array( $this, 'new_thread' ) );
            add_action( 'wp_ajax_bp_messages_send_message', array( $this, 'send_message' ) );
            add_action( 'wp_ajax_bp_messages_autocomplete', array( $this, 'bp_messages_autocomplete_results' ) );

            /**
             * Thread actions
             */
            add_action( 'wp_ajax_bp_messages_favorite',             array( $this, 'favorite' ) );
            add_action( 'wp_ajax_bp_messages_delete_thread',        array( $this, 'delete_thread' ) );
            add_action( 'wp_ajax_bp_messages_un_delete_thread',     array( $this, 'un_delete_thread' ) );
            add_action( 'wp_ajax_bp_messages_thread_load_messages', array( $this, 'thread_load_messages' ) );

            add_action( 'wp_ajax_bp_messages_prepare_edit_message', array( $this, 'prepare_edit_message' ) );

            add_action( 'wp_ajax_bp_messages_last_activity_refresh', array( $this, 'last_activity_refresh' ) );
            add_action( 'wp_ajax_bp_messages_get_pm_thread',         array( $this, 'get_pm_thread' ) );
            add_action( 'wp_ajax_bp_messages_delete_message',        array( $this, 'delete_message' ) );

            /**
             * Group Thread actions
             */
            add_action('wp_ajax_bp_better_messages_exclude_user_from_thread', array( $this, 'exclude_user_from_thread' ));
            add_action('wp_ajax_bp_better_messages_add_user_to_thread',       array( $this, 'add_user_to_thread') );

            /**
             * List threads
             */
            add_action( 'wp_ajax_bp_messages_get_more_threads',               array( $this, 'get_more_threads' ) );

            /*
             * User settings
             */
            add_action( 'wp_ajax_bp_messages_change_user_option',             array( $this, 'change_user_option' ) );

            add_action( 'wp_ajax_bp_messages_load_via_ajax', array( $this, 'load_via_ajax' ) );

            if( BP_Better_Messages()->settings['allowMuteThreads'] === '1' ) {
                add_action('wp_ajax_bp_messages_mute_thread', array($this, 'mute_thread'));
                add_action('wp_ajax_bp_messages_unmute_thread', array($this, 'unmute_thread'));
            }

            add_action( 'wp_ajax_bp_messages_get_edit_message', array( $this, 'get_edit_message' ) );
        }

        public function mute_thread(){
            $thread_id = intval($_POST['thread_id']);
            $user_id   = get_current_user_id();
            $muted_threads = BP_Better_Messages_Functions()->get_user_muted_threads( $user_id );

            $muted_threads[$thread_id] = time();

            update_user_meta( $user_id, 'bpbm_muted_threads', $muted_threads );
            wp_send_json(true);
        }

        public function unmute_thread(){
            $thread_id = intval($_POST['thread_id']);
            $user_id   = get_current_user_id();
            $muted_threads = BP_Better_Messages_Functions()->get_user_muted_threads( $user_id );

            if( isset( $muted_threads[ $thread_id ] ) ){
                unset( $muted_threads[ $thread_id ] );
            }

            update_user_meta( $user_id, 'bpbm_muted_threads', $muted_threads );

            wp_send_json(true);
        }

        public function load_via_ajax(){
            echo BP_Better_Messages()->functions->get_page();
            exit;
        }

        public function delete_message()
        {
            $thread_id = intval($_POST['thread_id']);
            $messages_ids = $_POST['messages_ids'];

            $errors = [];
            if ( ! wp_verify_nonce($_POST['_wpnonce'], 'delete_message_' . $thread_id ) ) {
                $errors[] = __('Security error while deleting messages', 'bp-better-messages');
            }

            if( ! empty($errors) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            }

            global $wpdb;

            $user_id = get_current_user_id();

            foreach( $messages_ids as $message_id ){
                $message = new BP_Messages_Message( $message_id );
                if( $message->sender_id === $user_id ){
                    $attachments = get_posts(array(
                        'post_type' => 'attachment',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => 'bp-better-messages-attachment',
                                'value' => '1',
                                'compare' => '='
                            ),
                            array(
                                'key' => 'bp-better-messages-message-id',
                                'value' => $message->id,
                                'compare' => '='
                            )
                        ),
                        'fields' => 'ids'
                    ));

                    foreach( $attachments as $attachment_id ){
                        wp_delete_attachment( $attachment_id, true );
                    }

                    $sql = $wpdb->prepare("DELETE FROM {$wpdb->base_prefix}bp_messages_messages WHERE id = %d", $message->id);
                    $wpdb->query( $sql );
                    $sql = $wpdb->prepare("DELETE FROM {$wpdb->base_prefix}bp_messages_meta WHERE message_id = %d", $message->id);
                    $wpdb->query( $sql );

                    do_action('bp_better_messages_message_deleted', $message->id );
                }
            }

            if( ! empty($errors) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            } else {
                wp_send_json( array(
                    'result'   => true,
                    'message'  => __('Deleted successfully', 'bp-better-messages'),
                    'redirect' => false
                ) );
            }

            exit;
        }

        public function change_user_option(){
            $user_id = intval($_POST['user_id']);
            $option  = sanitize_text_field( $_POST['option'] );
            $value   = sanitize_text_field( $_POST['value'] );

            $errors = [];
            if ( !wp_verify_nonce( $_POST[ '_wpnonce' ], 'bp_messages_change_user_option_' . $user_id ) ) {
                $errors[] = __( 'Security error while changing user option', 'bp-better-messages' );
            }

            /** User can change option? */
            $can_change = false;
            if( get_current_user_id() === $user_id ){
                $can_change = true;
            } else if( current_user_can('manage_options') ){
                $can_change = true;
            } else {
                $errors[] = __( 'You can`t change options for this user', 'bp-better-messages' );
            }

            if( ! empty($errors) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            }

            $message = __('Saved successfully', 'bp-better-messages');

            $errors  = [];

            switch( $option ){
                case 'email_notifications':
                    $new_value = ( $value === 'false' ) ? 'no' : 'yes';
                    update_user_meta( $user_id, 'notification_messages_new_message', $new_value );
                    break;
            }

            if( ! empty($errors) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            } else {
                wp_send_json( array(
                    'result'   => true,
                    'message'  => $message,
                    'redirect' => false
                ) );
            }

            exit;
        }

        public function get_more_threads(){
            $user_id = get_current_user_id();

            if( current_user_can('manage_options') ){
                $user_id = intval( $_POST['user_id'] );
            }

            $loaded_threads = (array) $_POST['loaded_threads'];

            $threads = BP_Better_Messages()->functions->get_threads( $user_id, $loaded_threads );

            foreach ( $threads as $thread ) {
                echo BP_Better_Messages()->functions->render_thread( $thread );
            }

            exit;
        }

        public function add_user_to_thread(){
            global $wpdb;
            $errors = array();
            $thread_id = intval($_POST['thread_id']);
            $users = (array) $_POST['users'];

            $userCanAdd = BP_Better_Messages_Functions()->is_thread_moderator(get_current_user_id(), $thread_id);

            if( ! $userCanAdd ) $errors[] = __('You can`t add members to this thread', 'bp-better-messages');

            if( empty($errors) ) {
                foreach ($users as $username) {
                    $user = get_user_by('slug', $username);
                    if (!$user) continue;

                    $userIsParticipant = (bool)$wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM `{$wpdb->base_prefix}bp_messages_recipients` WHERE `user_id` = %d AND `thread_id` = %d AND `sender_only` = '0'
                    ", $user->ID, $thread_id));

                    if($userIsParticipant) continue;

                    $wpdb->insert(
                        "{$wpdb->base_prefix}bp_messages_recipients",
                        array(
                            'user_id' => $user->ID,
                            'thread_id' => $thread_id,
                            'unread_count' => 0,
                            'sender_only' => 0,
                            'is_deleted' => 0
                        )
                    );
                }
            }

            exit;
        }

        public function exclude_user_from_thread(){
            global $wpdb;

            $errors = array();
            $user_id = intval($_POST['user_id']);
            $thread_id = intval($_POST['thread_id']);

            $userCanExclude = BP_Better_Messages_Functions()->is_thread_moderator(get_current_user_id(), $thread_id);

            if( ! $userCanExclude ) $errors[] = __('You can`t exclude members from this thread', 'bp-better-messages');

            $userIsParticipant = (bool) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM `{$wpdb->base_prefix}bp_messages_recipients` WHERE `user_id` = %d AND `thread_id` = %d AND `sender_only` = '0'
            ", $user_id, $thread_id));

            if( ! $userIsParticipant ) $errors[] = __('Not found member in this thread', 'bp-better-messages');

            if( empty($errors) ){
                $result = $wpdb->delete(
                    "{$wpdb->base_prefix}bp_messages_recipients",
                    array(
                        'user_id' => $user_id,
                        'thread_id' => $thread_id
                    ),
                    array( '%d', '%d' )
                );

                wp_send_json(array(
                    'result'   => true
                ));
            } else {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors
                ) );
            }

            exit;
        }

        public function prepare_edit_message(){
            global $wpdb;

            $thread_id  = intval($_POST['thread_id']);
            $message_id = intval($_POST['message_id']);
            $user_id    = get_current_user_id();

            $message = $wpdb->get_row($wpdb->prepare(
                "SELECT * 
                FROM `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id` = %d 
                AND `id` = %d 
                AND `sender_id` = %d"
                , $thread_id, $message_id, $user_id));

            if( ! $message ) wp_send_json(false);

            $attachments = bp_messages_get_meta( $message->id, 'attachments', true );

            $json = array(
                'id'      => $message->id,
                'message' => str_replace('  ', ' ', BP_Better_Messages_Emojies()->convert_emojies_to_unicode($message->message))
            );

            wp_send_json($json);
            exit;
        }

        public function get_edit_message(){
            global $wpdb;

            $user_id    = get_current_user_id();
            $message_id = intval($_POST['message_id']);

            $message_content = $wpdb->get_var( $wpdb->prepare( "SELECT message FROM {$wpdb->base_prefix}bp_messages_messages WHERE id = %d AND sender_id = %d", $message_id, $user_id ) );

            $attachments = bp_messages_get_meta( $message_id, 'attachments', true );
            if( is_array( $attachments ) && count( $attachments ) > 0 ) {
                foreach ($attachments as $attachment) {
                    $message_content = str_replace($attachment, '', $message_content);
                }
            }

            echo trim($message_content);
            exit;
        }

        public function edit_message(){
            global $wpdb;

            $thread_id  = intval( $_POST[ 'thread_id' ] );
            $message_id = intval( $_POST['message_id'] );
            $user_id    = get_current_user_id();
            $errors    = array();

            $new_message = sanitize_text_field($_POST['message']);

            if( trim($new_message) == '') $errors['empty'] = __( 'Your message was empty.', 'bp-better-messages' );

            $old_message_content = $wpdb->get_var( $wpdb->prepare( "SELECT message FROM {$wpdb->base_prefix}bp_messages_messages WHERE id = %d AND sender_id = %d", $message_id, $user_id ) );
            $old_message = $old_message_content;

            $attachments = bp_messages_get_meta( $message_id, 'attachments', true );
            if( is_array( $attachments ) && count( $attachments ) > 0 ) {
                foreach ($attachments as $attachment) {
                    $old_message_content = str_replace($attachment, '', $old_message_content);
                }
            }

            $old_message_content = trim($old_message_content);
            $update_message = str_replace( $old_message_content, $new_message, $old_message );

            $message = $wpdb->get_row($wpdb->prepare(
                "SELECT * 
                FROM `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id` = %d 
                AND `id` = %d 
                AND `sender_id` = %d"
                , $thread_id, $message_id, $user_id)
            );

            if( ! $message ) $errors['not_found'] = __('Message not found', 'bp-better-messages');

            $updated = false;
            if( empty($errors) ){
                $updated = $wpdb->update(
                    "{$wpdb->base_prefix}bp_messages_messages",
                    array(
                        'message'   => $new_message
                    ),
                    array(
                        'thread_id' => $thread_id,
                        'id'        => $message_id,
                        'sender_id' => $user_id
                    ),
                    array('%s'),
                    array('%d', '%d', '%d')
                );

                $message->message = $new_message;
                $message->recipients = array();
                $participants = BP_Better_Messages()->functions->get_participants($thread_id);
                foreach(array_keys($participants['users']) as $user_id){
                    $message->recipients[$user_id] = $user_id;
                }
            }

            if( ! empty($errors) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            } else {
                BP_Better_Messages_Premium()->on_message_sent($message);

                wp_send_json( array(
                    'result'   => $updated,
                    'redirect' => false
                ) );
            }
        }

        public function get_pm_thread(){
            $user_id = intval($_POST['user_id']);

            if( BP_Better_Messages()->settings['singleThreadMode'] == '1' ) {
                $threads = BP_Better_Messages()->functions->find_existing_threads(get_current_user_id(), $user_id);
                if( count($threads) > 0) {
                    $thread_id = $threads[0];
                    wp_send_json($thread_id);
                    exit;
                }
            }

            $thread_id = BP_Better_Messages()->functions->get_pm_thread_id($user_id);
            wp_send_json($thread_id);
        }

        public function thread_load_messages(){
            $thread_id = intval($_POST['thread_id']);
            $last_message = intval($_POST['message_id']);

            if ( ! BP_Messages_Thread::check_access( $thread_id ) && ! current_user_can('manage_options')  ) die();

            $stacks = BP_Better_Messages()->functions->get_stacks( $thread_id, $last_message, 'from_message' );

            if( empty($stacks) ) exit;

            foreach ( $stacks as $stack ) {
                echo BP_Better_Messages()->functions->render_stack( $stack );
            }

            exit;
        }

        public function last_activity_refresh()
        {
            $user_id = get_current_user_id();
            bp_update_user_last_activity( $user_id );
            exit;
        }

        public function thread_check_new()
        {
            status_header(200);
            global $wpdb;

            $user_id = get_current_user_id();
            #$bp = buddypress();

            $response = array();

            $last_check = date( "Y-m-d H:i:s", 0 );

            if ( isset( $_POST[ 'last_check' ] ) ) {
                $last_check = date( "Y-m-d H:i:s", intval( $_POST[ 'last_check' ] ) );
            }

            $last_message = date( "Y-m-d H:i:s", intval( $_POST[ 'last_message' ] ) );
            $thread_id = intval( $_POST[ 'thread_id' ] );

            if ( ! BP_Messages_Thread::check_access( $thread_id ) && ! current_user_can('manage_options') ) die();

            setcookie( 'bp-messages-last-check', time(), time() + ( 86400 * 31 ), '/' );

            $messages = $wpdb->get_results( $wpdb->prepare( "
            SELECT id, sender_id as user_id, subject, message as content, date_sent as date
            FROM  `{$wpdb->base_prefix}bp_messages_messages` 
            WHERE `thread_id`  = %d
            AND   `date_sent`  > %s
            ORDER BY `date_sent` ASC
            ", $thread_id, $last_message ) );

            foreach ( $messages as $index => $message ) {
                $user = get_userdata( $message->user_id );
                $messages[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id, 'stack', $user_id );
                $messages[ $index ]->timestamp = strtotime( $message->date );
                $messages[ $index ]->avatar = BP_Better_Messages_Functions()->get_avatar( $message->user_id, 40 );
                $messages[ $index ]->name = $user->display_name;
                $messages[ $index ]->link = bp_core_get_userlink( $message->user_id, false, true );
            }

            $response[ 'messages' ] = $messages;

            $threads = $wpdb->get_results( "
                SELECT thread_id, unread_count 
                FROM   {$wpdb->base_prefix}bp_messages_recipients
                WHERE  `user_id`      = {$user_id}
                AND    `is_deleted`   = 0
                AND    `unread_count` > 0
                AND    `thread_id`    != {$thread_id}
            " );

            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}bp_messages_recipients WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array)$results as $recipient ) {
                    if ( get_current_user_id() == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $message = $wpdb->get_row( $wpdb->prepare( "
                SELECT id, sender_id as user_id, subject, message as content, date_sent
                FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id`  = %d
                AND   `sender_id`  != %d
                AND   `date_sent`  >= %s
                ORDER BY `date_sent` DESC 
                LIMIT 0, 1", $thread->thread_id, $user_id, $last_check ) );

                if ( !$message ) {
                    unset( $threads[ $index ] );
                    continue;
                }

                $user = get_userdata( $message->user_id );
                $threads[ $index ]->subject = $message->subject;
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id, 'site', $user_id );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->message->date_sent ) == strtotime( $item2->message->date_sent ) ) return 0;

                return ( strtotime( $item1->message->date_sent ) < strtotime( $item2->message->date_sent ) ) ? 1 : -1;
            } );

            $response[ 'threads' ] = $threads;

            messages_mark_thread_read( $thread_id );

            $response[ 'total_unread' ] = BP_Messages_Thread::get_total_threads_for_user( $user_id, 'inbox', 'unread' );

            wp_send_json( $response );

            exit;
        }

        public function check_new()
        {
            status_header(200);

            global $wpdb;

            $user_id = get_current_user_id();

            $last_check = date( "Y-m-d H:i:s", 0 );

            if ( isset( $_POST[ 'last_check' ] ) ) {
                $last_check = date( "Y-m-d H:i:s", absint( $_POST[ 'last_check' ] ) );
            }

            setcookie( 'bp-messages-last-check', time(), time() + ( 86400 * 31 ), '/' );

            $threads = $wpdb->get_results( $wpdb->prepare( "
                SELECT thread_id, unread_count 
                FROM   {$wpdb->base_prefix}bp_messages_recipients
                WHERE  `user_id`      = %d
                AND    `is_deleted`   = 0
                AND    `unread_count` > 0
            ", $user_id ) );

            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}bp_messages_recipients WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array)$results as $recipient ) {
                    if ( get_current_user_id() == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $message = $wpdb->get_row( $wpdb->prepare( "
                SELECT id, sender_id as user_id, subject, message as content, date_sent
                FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id`  = %d
                AND   `sender_id`  != %d
                AND   `date_sent`  >= %s
                ORDER BY `id` DESC 
                LIMIT 0, 1", $thread->thread_id, $user_id, $last_check ) );

                if ( !$message ) {
                    unset( $threads[ $index ] );
                    continue;
                }

                $user = get_userdata( $message->user_id );
                $threads[ $index ]->subject = $message->subject;
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id, 'site', $user_id );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->message->date_sent ) == strtotime( $item2->message->date_sent ) ) return 0;

                return ( strtotime( $item1->message->date_sent ) < strtotime( $item2->message->date_sent ) ) ? 1 : -1;
            } );

            $response[ 'threads' ] = $threads;

            $response[ 'total_unread' ] = BP_Messages_Thread::get_total_threads_for_user( $user_id, 'inbox', 'unread' );

            wp_send_json( $response );

            exit;
        }

        public function favorite()
        {

            $message_id = absint( $_POST[ 'message_id' ] );
            $thread_id  = absint( $_POST[ 'thread_id' ] );
            $type       = sanitize_text_field( $_POST[ 'type' ] );

            $result = bp_messages_star_set_action( array(
                'action'     => $type,
                'message_id' => $message_id,
                'thread_id'  => $thread_id,
                'user_id'    => get_current_user_id(),
            ) );

            wp_send_json( $result );

            exit;
        }

        public function send_message()
        {
            $thread_id = intval( $_POST[ 'thread_id' ] );
            $errors    = array();

            if ( !wp_verify_nonce( $_POST[ '_wpnonce' ], 'sendMessage_' . $thread_id ) ) {
                $errors[] = __( 'Security error while sending message', 'bp-better-messages' );
            } else {
                if(isset($_POST['message_id']) && ! empty($_POST['message_id'])){
                    $this->edit_message();
                    return false;
                }

                $content = BP_Better_Messages()->functions->filter_message_content($_POST['message']);

                $args = array(
                    'content'    => $content,
                    'thread_id'  => $thread_id,
                    'error_type' => 'wp_error'
                );

                if( ! apply_filters('bp_better_messages_can_send_message', BP_Messages_Thread::check_access( $thread_id ), get_current_user_id(), $thread_id ) ) {
                    $errors[] = __( 'You can`t reply to this thread.', 'bp-better-messages' );
                }

                if( trim($args['content']) == '') $errors['empty'] = __( 'Your message was empty.', 'bp-better-messages' );

                do_action_ref_array( 'bp_better_messages_before_message_send', array( &$args, &$errors ));

                if( empty( $errors ) ){
                    $sent = messages_new_message( $args );

                    messages_mark_thread_read( $thread_id );

                    if ( is_wp_error( $sent ) ) {
                        $errors[] = $sent->get_error_message();
                    }
                }
            }

            if( ! empty($errors) ) {
                do_action( 'bp_better_messages_on_message_not_sent', $thread_id, $errors );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => 'refresh'
                ) );
            } else {
                wp_send_json( array(
                    'result'   => $sent,
                    'redirect' => false
                ) );
            }

            exit;
        }

        public function new_thread()
        {
            $errors = array();

            if ( !wp_verify_nonce( $_POST[ '_wpnonce' ], 'newThread' ) ) {
                $errors[] = __( 'Security error while starting new thread', 'bp-better-messages' );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else {
                $user = wp_get_current_user();

                $content = BP_Better_Messages()->functions->filter_message_content($_POST['message']);

                $args = array(
                    'subject'       => (isset ($_POST[ 'subject' ]) ) ? sanitize_text_field( $_POST[ 'subject' ] ) : '',
                    'content'       => $content,
                    'error_type'    => 'wp_error',
                    'append_thread' => false
                );

                if ( isset( $_POST[ 'recipients' ] ) && is_array( $_POST[ 'recipients' ] ) && !empty( $_POST[ 'recipients' ] ) ) {
                    foreach ( $_POST[ 'recipients' ] as $one ) {
                        if($user->user_login == $one || $user->ID == $one) continue;
                        $args[ 'recipients' ][] = sanitize_text_field( $one );
                    }
                }

                do_action_ref_array( 'bp_better_messages_before_new_thread', array( &$args, &$errors ));


                if( empty( $errors ) ){
                    $sent = messages_new_message( $args );
                    if ( is_wp_error( $sent ) ) $errors[] = $sent->get_error_message();
                }
            }


            if( ! empty( $errors ) ) {
                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            } else {
                wp_send_json( array(
                    'result'   => $sent,
                    'redirect' => false
                ) );
            }

            exit;
        }

        /**
         * AJAX handler for autocomplete.
         *
         * Displays friends only, unless BP_MESSAGES_AUTOCOMPLETE_ALL is defined.
         *
         * @since 1.0.0
         */
        public function bp_messages_autocomplete_results()
        {
            /**
             * Filters the max results default value for ajax messages autocomplete results.
             *
             * @since 1.0.0
             *
             * @param int $value Max results for autocomplete. Default 10.
             */
            $limit = isset( $_GET[ 'limit' ] ) ? absint( $_GET[ 'limit' ] ) : (int)apply_filters( 'bp_autocomplete_max_results', 10 );
            $term = isset( $_GET[ 'q' ] ) ? sanitize_text_field( $_GET[ 'q' ] ) : '';

            // Include everyone in the autocomplete, or just friends?
            if ( defined('BP_MESSAGES_AUTOCOMPLETE_ALL') ) {
                $only_friends = ( BP_MESSAGES_AUTOCOMPLETE_ALL === false );
            } else {
                $only_friends = true;
            }

            if( BP_Better_Messages()->settings['friendsMode'] === '1' ){
                $only_friends = true;
            }

            $suggestions = bp_core_get_suggestions( array(
                'limit'        => $limit,
                'only_friends' => $only_friends,
                'term'         => $term,
                'type'         => 'members',
            ) );

            if ( $suggestions && !is_wp_error( $suggestions ) ) {
                $response = array();

                foreach ( $suggestions as $index => $suggestion ) {
                    $response[] = array(
                        'id'    => $suggestion->ID,
                        'label' => $suggestion->name,
                        'value' => $suggestion->ID,
                        'img'   => BP_Better_Messages_Functions()->get_avatar( $suggestion->user_id, 40 )
                    );
                }

                wp_send_json( $response );
            }

            exit;
        }

        public function delete_thread()
        {

            $errors = array();

            $thread_id = intval( $_POST[ 'thread_id' ] );

            if (
                ( BP_Better_Messages()->settings['disableDeleteThreadCheck'] !== '1' && ! wp_verify_nonce( $_POST[ 'nonce' ], 'delete_' . $thread_id ) )
                || ( ! BP_Messages_Thread::check_access( $thread_id )  && ! current_user_can('manage_options') )
            ) {
                $errors[] = __( 'Security error while deleting thread', 'bp-better-messages' );

                status_header( 200 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else if( ! apply_filters( 'bp_better_messages_can_delete_thread', true, $thread_id, get_current_user_id() ) ) {
                $errors[] = __( 'You can`t delete this thread', 'bp-better-messages' );

                status_header( 200 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );
            } else {
                global $wpdb;

                $thread_id = (int) $thread_id;
                $user_id = bp_loggedin_user_id();

                /**
                 * Fires before a message thread is marked as deleted.
                 *
                 * @since 2.2.0
                 * @since 2.7.0 The $user_id parameter was added.
                 *
                 * @param int $thread_id ID of the thread being deleted.
                 * @param int $user_id   ID of the user that the thread is being deleted for.
                 */
                do_action( 'bp_messages_thread_before_mark_delete', $thread_id, $user_id );

                #$bp = buddypress();

                // Mark messages as deleted
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}bp_messages_recipients SET is_deleted = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) );

                // Get the message ids in order to pass to the action.
                $message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->base_prefix}bp_messages_messages WHERE thread_id = %d", $thread_id ) );

                // Check to see if any more recipients remain for this message.
                $recipients = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->base_prefix}bp_messages_recipients WHERE thread_id = %d AND is_deleted = 0", $thread_id ) );

                // No more recipients so delete all messages associated with the thread.
                if ( empty( $recipients ) ) {

                    /**
                     * Fires before an entire message thread is deleted.
                     *
                     * @since 2.2.0
                     *
                     * @param int   $thread_id   ID of the thread being deleted.
                     * @param array $message_ids IDs of messages being deleted.
                     */
                    do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids );

                    // Delete all the messages.
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}bp_messages_messages WHERE thread_id = %d", $thread_id ) );

                    // Do something for each message ID.
                    foreach ( $message_ids as $message_id ) {

                        // Delete message meta.
                        bp_messages_delete_meta( $message_id );

                        /**
                         * Fires after a message is deleted. This hook is poorly named.
                         *
                         * @since 1.0.0
                         *
                         * @param int $message_id ID of the message.
                         */
                        do_action( 'messages_thread_deleted_thread', $message_id );
                    }

                    // Delete all the recipients.
                    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}bp_messages_recipients WHERE thread_id = %d", $thread_id ) );
                }

                /**
                 * Fires after a message thread is either marked as deleted or deleted.
                 *
                 * @since 2.2.0
                 * @since 2.7.0 The $user_id parameter was added.
                 *
                 * @param int   $thread_id   ID of the thread being deleted.
                 * @param array $message_ids IDs of messages being deleted.
                 * @param int   $user_id     ID of the user the threads were deleted for.
                 */
                do_action( 'bp_messages_thread_after_delete', $thread_id, $message_ids, $user_id );

                wp_send_json( array(
                    'result'   => true,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            }

            die();
        }

        public function un_delete_thread()
        {
            global $wpdb;

            $errors = array();

            $thread_id = intval( $_POST[ 'thread_id' ] );
            $user_id = bp_loggedin_user_id();

            $has_access = (bool)$wpdb->get_var( $wpdb->prepare( "
                SELECT COUNT(*)
                FROM {$wpdb->base_prefix}bp_messages_recipients
                WHERE `thread_id`  = %d
                AND   `user_id`    = %d
                AND   `is_deleted` = 1
            ", $thread_id, $user_id ) );


            if ( ( BP_Better_Messages()->settings['disableDeleteThreadCheck'] !== '1' && ! wp_verify_nonce( $_POST[ 'nonce' ], 'un_delete_' . $thread_id ) ) || ! $has_access ) {
                $errors[] = __( 'Security error while recovering thread', 'bp-better-messages' );

                status_header( 200 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else {

                $restored = $wpdb->update( $wpdb->base_prefix . 'bp_messages_recipients', array(
                    'is_deleted' => 0
                ), array(
                    'thread_id' => $thread_id,
                    'user_id'   => $user_id
                ) );

                wp_send_json( array(
                    'result'   => $restored,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            }

            die();
        }
    }
endif;

function BP_Better_Messages_Ajax()
{
    return BP_Better_Messages_Ajax::instance();
}
