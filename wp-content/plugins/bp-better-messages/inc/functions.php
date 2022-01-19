<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Functions' ) ):

    class BP_Better_Messages_Functions
    {

        public static function instance()
        {
            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Functions();
            }

            return $instance;
        }

        public function is_thread_moderator($user_id, $thread_id){
            global $wpdb;

            $admin_user = (int) $wpdb->get_var($wpdb->prepare("
                SELECT sender_id 
                FROM `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id` = %d 
                AND   `sender_id` != '0'
                ORDER BY `{$wpdb->base_prefix}bp_messages_messages`.`date_sent` ASC
                LIMIT 0,1
            ", $thread_id));

            if( intval($user_id) === $admin_user){
                return true;
            }

            return false;
        }

        public function get_thread_subject($thread_id){
            global $wpdb;

            $subject = $wpdb->get_var( $wpdb->prepare( "
                SELECT subject 
                FROM `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id` = %d 
                ORDER BY `date_sent` ASC
            ", $thread_id ) );

            return esc_attr($subject);
        }

        public function get_threads( $user_id = 0, $exclude_threads = [] )
        {
            global $wpdb;

            if( is_array( $exclude_threads )  && count( $exclude_threads ) > 0 ){
                foreach( $exclude_threads as $key => $value ){
                    $exclude_threads[ $key ] = intval( $value );
                }

                $sql = $wpdb->prepare( "
                    SELECT
                    recipients.`thread_id`,
                    recipients.`unread_count`,
                    MAX(messages.date_sent) as date_sent
                    FROM
                        {$wpdb->base_prefix}bp_messages_recipients as recipients
                    INNER JOIN {$wpdb->base_prefix}bp_messages_messages messages 
                        ON recipients.`thread_id` = messages.`thread_id`
                    WHERE
                        recipients.`user_id` = %d AND recipients.`is_deleted` = 0
                        AND recipients.`thread_id` NOT IN (" . implode(',', $exclude_threads) . ")
                    GROUP BY recipients.thread_id
                    ORDER BY date_sent DESC
                    LIMIT 0, 20
                ", $user_id);

                $threads = $wpdb->get_results($sql);
            } else {
                $sql = $wpdb->prepare( "
                    SELECT
                    recipients.`thread_id`,
                    recipients.`unread_count`,
                    MAX(messages.date_sent) as date_sent
                    FROM
                        {$wpdb->base_prefix}bp_messages_recipients as recipients
                    INNER JOIN {$wpdb->base_prefix}bp_messages_messages messages 
                        ON recipients.`thread_id` = messages.`thread_id`
                    WHERE
                        recipients.`user_id` = %d AND recipients.`is_deleted` = 0
                    GROUP BY recipients.thread_id
                    ORDER BY date_sent DESC
                    LIMIT 0, 20
                ", $user_id );

                $threads = $wpdb->get_results( $sql );
            }



            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$wpdb->base_prefix}bp_messages_recipients WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array) $results as $recipient ) {
                    if ( $user_id == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $threads[ $index ]->recipients = $recipients;

                $last_message = $wpdb->get_row( $wpdb->prepare( "
                    SELECT id, sender_id as user_id, subject, message, date_sent
                    FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                    WHERE `thread_id` = %d
                    ORDER BY `date_sent` DESC 
                    LIMIT 0, 1
                ", $thread->thread_id ) );

                if( ! $last_message || $last_message->user_id == 0 ){
                    unset($threads[$index]);
                    continue;
                }

                $user = get_userdata( $last_message->user_id );
                $threads[ $index ]->subject = ltrim($last_message->subject, 'Re: ');
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $last_message->message, $last_message->id, 'site', $user_id );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $last_message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->message_id = intval( $last_message->id );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            /*
            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->date_sent ) == strtotime( $item2->date_sent ) ) return 0;

                return ( strtotime( $item1->date_sent ) < strtotime( $item2->date_sent ) ) ? 1 : -1;
            } );*/

            return $threads;
        }

        public function get_thread_message_count($thread_id){
            global $wpdb;

            return $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*)
            FROM  {$wpdb->base_prefix}bp_messages_messages
            WHERE `thread_id` = %d
            ", $thread_id ) );
        }

        public function get_stacks( $thread_id, $message = false, $action = 'last_messages' )
        {
            global $wpdb;

            $thread = new BP_Messages_Thread($thread_id);
            if( isset($thread::$noCache) ){
                $thread::$noCache = true;
            }

            if ( $this->get_thread_message_count( $thread_id ) === 0 ) return array();

            $stacks = array();

            $per_page = (int) BP_Better_Messages()->settings['messagesPerPage'];
            $usersIds = array_keys($thread->get_recipients());
            $userLast = array();
            foreach($usersIds as $userId){
                $userLast[$userId] = (int) get_user_meta($userId, 'bpbm-last-seen-thread-' . $thread_id, true);
            }

            switch ($action){
                case 'last_messages':
                    $query = $wpdb->prepare( "
                    SELECT id, thread_id, sender_id, message, date_sent
                    FROM  {$wpdb->base_prefix}bp_messages_messages
                    WHERE `thread_id` = %d
                    ORDER BY `date_sent` DESC
                    LIMIT 0, %d
                    ", $thread_id, $per_page );
                    break;
                case 'from_message':
                    $query = $wpdb->prepare( "
                    SELECT id, thread_id, sender_id, message, date_sent
                    FROM  {$wpdb->base_prefix}bp_messages_messages
                    WHERE `thread_id` = %d
                    AND   `id` < %d
                    ORDER BY `date_sent` DESC
                    LIMIT 0, %d
                    ", $thread_id, $message, $per_page );
                    break;
                case 'to_message':
                    $query = $wpdb->prepare( "
                    SELECT id, thread_id, sender_id, message, date_sent
                    FROM  {$wpdb->base_prefix}bp_messages_messages
                    WHERE `thread_id` = %d
                    AND   `id` >= %d
                    ORDER BY `date_sent` DESC
                    ", $thread_id, $message );
                    break;
            }

            $messages = $wpdb->get_results( $query );
            $messages = array_reverse($messages);

            $lastUser = 0;
            $lastTimestamp = 0;
            foreach ( $messages as $index => $message ) {
                $timestamp = strtotime( $message->date_sent );

                if($message->sender_id == get_current_user_id()){
                    $lastSeen = 0;
                    foreach($userLast as $id => $last){
                        if($id == get_current_user_id()) continue;
                        if($last > $lastSeen) $lastSeen = $last;
                    }
                } else {
                    $lastSeen = $userLast[get_current_user_id()];
                }


                if ( $message->sender_id != $lastUser || date('Y-m-d H:i', $timestamp) !== date('Y-m-d H:i', $lastTimestamp) ) {
                    $lastUser = $message->sender_id;
                    $lastTimestamp = $timestamp;
                    $stacks[] = array(
                        'id'        => $message->id,
                        'user_id'   => $message->sender_id,
                        'user'      => get_userdata( $message->sender_id ),
                        'thread_id' => $message->thread_id,
                        'messages'  => array(
                            array(
                                'id'        => $message->id,
                                'message'   => self::format_message( $message->message, $message->id, 'stack', get_current_user_id() ),
                                'date'      => $message->date_sent,
                                'timestamp' => $timestamp,
                                'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() ),
                                'seen'      => ($lastSeen >= $timestamp) ? true : false
                            )
                        )
                    );
                } else {
                    end($stacks);         // move the internal pointer to the end of the array
                    $key = key($stacks);
                    $stacks[ $key ][ 'messages' ][] = array(
                        'id'        => $message->id,
                        'message'   => self::format_message( $message->message, $message->id, 'stack', get_current_user_id() ),
                        'date'      => $message->date_sent,
                        'timestamp' => $timestamp,
                        'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() ),
                        'seen'      => ($lastSeen >= $timestamp) ? true : false
                    );
                }
            }

            return $stacks;

        }

        public function get_participants( $thread_id )
        {

            $thread = new BP_Messages_Thread();
            $recipients = $thread->get_recipients( $thread_id );

            $participants = array(
                'links' => array()
            );

            foreach ( $recipients as $recipient ) {
                $user = get_userdata( $recipient->user_id );

                if( ! $user ){
                    continue;
                }

                if($user->ID != get_current_user_id()) {
                    $participants[ 'links' ][] = '<a href="' . bp_core_get_userlink( $recipient->user_id, false, true ) . '" class="user">' . BP_Better_Messages_Functions()->get_avatar( $recipient->user_id, 20 ) . $user->display_name . '</a>';
                    $participants[ 'recipients' ][] = $recipient->user_id;
                }

                $args = array(
                    'name'    => ( ! empty( $user->display_name ) ) ? $user->display_name : $user->user_login,
                    'link'    => bp_core_get_userlink( $recipient->user_id, false, true ),
                    'avatar'  => BP_Better_Messages_Functions()->get_avatar($user->ID, 40)
                );

                $participants[ 'users' ][ $recipient->user_id ] = $args;
            }

            return $participants;

        }

        public function get_displayed_user_id(){
            $current_user_id = get_current_user_id();

            if( doing_action('wp_ajax_buddyboss_theme_get_header_unread_messages') ){
                $user_id = $current_user_id;
            }

            if ( ! isset( $user_id ) || $user_id == false ) {
                $user_id = bp_displayed_user_id();
            }

            if ( ! isset( $user_id ) || $user_id == false ) {
                $user_id = $current_user_id;
            }

            return $user_id;
        }

        public function get_link( $user_id = false )
        {
            $current_user_id = $this->get_displayed_user_id();

            if ( $user_id == false ) {
                $user_id = $current_user_id;
            }

            $url_overwritten = apply_filters( 'bp_better_messages_page', null, $user_id );

            if( $url_overwritten !== null ){
                return $url_overwritten;
            }

            if( class_exists('AsgarosForum') && BP_Better_Messages()->settings['chatPage'] === 'asgaros-forum' ) {
                global $asgarosforum;
                $link = $asgarosforum->get_link('profile', $user_id) . 'messages/';
                return $link;
            }

            if( BP_Better_Messages()->settings['chatPage'] !== '0' ){
                return get_permalink(BP_Better_Messages()->settings['chatPage']);
            }

            if( class_exists('BuddyPress') && $user_id !== $current_user_id ){
                return bp_core_get_user_domain( $user_id ) . 'bp-messages/';
            }

            if(class_exists('BuddyPress')) {
                return bp_core_get_user_domain( $user_id ) . 'bp-messages/';
            }

            return '';
        }

        public function get_starred_count()
        {
            global $wpdb;
            $user_id = get_current_user_id();

            return $wpdb->get_var( "
                SELECT
                  COUNT({$wpdb->base_prefix}bp_messages_messages.id) AS count
                FROM {$wpdb->base_prefix}bp_messages_meta
                  INNER JOIN {$wpdb->base_prefix}bp_messages_messages
                    ON {$wpdb->base_prefix}bp_messages_meta.message_id = {$wpdb->base_prefix}bp_messages_messages.id
                  INNER JOIN {$wpdb->base_prefix}bp_messages_recipients
                    ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_messages.thread_id
                WHERE {$wpdb->base_prefix}bp_messages_meta.meta_key = 'starred_by_user'
                AND {$wpdb->base_prefix}bp_messages_meta.meta_value = $user_id
                AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
                AND {$wpdb->base_prefix}bp_messages_recipients.user_id = $user_id
            " );
        }

        public function get_starred_stacks()
        {
            global $wpdb;

            $user_id = get_current_user_id();

            $query = $wpdb->prepare( "
                SELECT
                  {$wpdb->base_prefix}bp_messages_messages.*
                FROM {$wpdb->base_prefix}bp_messages_meta
                  INNER JOIN {$wpdb->base_prefix}bp_messages_messages
                    ON {$wpdb->base_prefix}bp_messages_meta.message_id = {$wpdb->base_prefix}bp_messages_messages.id
                  INNER JOIN {$wpdb->base_prefix}bp_messages_recipients
                    ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_messages.thread_id
                WHERE {$wpdb->base_prefix}bp_messages_meta.meta_key = 'starred_by_user'
                AND {$wpdb->base_prefix}bp_messages_meta.meta_value = %d
                AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
                AND {$wpdb->base_prefix}bp_messages_recipients.user_id = %d
            ", $user_id, $user_id );

            $messages = $wpdb->get_results( $query );

            $stacks = array();

            $lastUser = 0;
            foreach ( $messages as $index => $message ) {
                if ( $message->sender_id != $lastUser ) {
                    $lastUser = $message->sender_id;

                    $stacks[] = array(
                        'id'        => $message->id,
                        'user_id'   => $message->sender_id,
                        'user'      => get_userdata( $message->sender_id ),
                        'thread_id' => $message->thread_id,
                        'messages'  => array(
                            array(
                                'id'        => $message->id,
                                'message'   => self::format_message( $message->message, $message->id, 'stack', $user_id ),
                                'date'      => $message->date_sent,
                                'timestamp' => strtotime( $message->date_sent ),
                                'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() )
                            )
                        )
                    );
                } else {
                    $stacks[ count( $stacks ) - 1 ][ 'messages' ][] = array(
                        'id'        => $message->id,
                        'message'   => self::format_message( $message->message, $message->id, 'stack', $user_id ),
                        'date'      => $message->date_sent,
                        'timestamp' => strtotime( $message->date_sent ),
                        'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() )
                    );
                }
            }

            return $stacks;
        }

        public function get_search_stacks( $search = '' )
        {
            global $wpdb;

            if( empty( trim($search) ) ) return array();

            $user_id = get_current_user_id();

            $searchTerm = '%' . sanitize_text_field($search) . '%';

            $query = $wpdb->prepare( "
                SELECT {$wpdb->base_prefix}bp_messages_messages.*
                FROM {$wpdb->base_prefix}bp_messages_messages
                INNER JOIN {$wpdb->base_prefix}bp_messages_recipients
                ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_messages.thread_id
                WHERE
                {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0 
                AND {$wpdb->base_prefix}bp_messages_recipients.user_id = %d
                AND {$wpdb->base_prefix}bp_messages_messages.message LIKE %s
            ", $user_id, $searchTerm );

            $messages = $wpdb->get_results( $query );

            $stacks = array();

            $lastUser = 0;
            foreach ( $messages as $index => $message ) {
                if ( $message->sender_id != $lastUser ) {
                    $lastUser = $message->sender_id;

                    $stacks[] = array(
                        'id'        => $message->id,
                        'user_id'   => $message->sender_id,
                        'user'      => get_userdata( $message->sender_id ),
                        'thread_id' => $message->thread_id,
                        'messages'  => array(
                            array(
                                'id'        => $message->id,
                                'message'   => self::format_message( $message->message, $message->id, 'stack', $user_id ),
                                'date'      => $message->date_sent,
                                'timestamp' => strtotime( $message->date_sent ),
                                'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() )
                            )
                        )
                    );
                } else {
                    $stacks[ count( $stacks ) - 1 ][ 'messages' ][] = array(
                        'id'        => $message->id,
                        'message'   => self::format_message( $message->message, $message->id, 'stack', $user_id ),
                        'date'      => $message->date_sent,
                        'timestamp' => strtotime( $message->date_sent ),
                        'stared'    => bp_messages_is_message_starred( $message->id, get_current_user_id() )
                    );
                }
            }

            return $stacks;
        }

        public function render_stack( $stack ){
            if( $stack[ 'user_id' ] == 0 ) return '';
            ob_start();
            $status    = (BP_Better_Messages()->realtime && BP_Better_Messages()->settings['messagesStatus']);
            $timestamp = $stack[ 'messages' ][0][ 'timestamp' ];
            $userdata  = get_userdata($stack[ 'user_id' ]);
            ?>
            <div class="messages-stack <?php echo ($stack['user_id'] == get_current_user_id()) ? 'outgoing' : 'incoming'; ?>" data-user-id="<?php echo $stack[ 'user_id' ]; ?>">
                <div class="pic"><?php echo BP_Better_Messages_Functions()->get_avatar( $stack[ 'user_id' ], 40 ); ?></div>
                <div class="content">
                    <div class="info">
                        <div class="name">
                            <?php if( ! $userdata ) { ?>
                                <a href="#" class="bpbm-deleted-user-link"><?php _e('Deleted User', 'bp-better-messages'); ?></a>
                                <?php
                            } else { ?>
                            <a href="<?php echo bp_core_get_userlink( $stack[ 'user_id' ], false, true ); ?>"><?php echo $stack[ 'user' ]->display_name; ?></a>
                            <?php } ?>
                        </div>
                        <div class="time" title="<?php echo date('Y-m-d H:i:s', $timestamp); ?>" data-livestamp="<?php echo $timestamp; ?>"></div>
                    </div>
                    <ul class="messages-list">
                        <?php foreach ( $stack[ 'messages' ] as $message ) {
                            $timestamp = $message[ 'timestamp' ];
                            $class = array();
                            if($stack['user_id'] == get_current_user_id()) $class[] = 'my';
                            if(isset($message['seen']) && $status && $message['seen']) $class[] = 'seen';
                            ?>
                            <li class="<?php echo implode(' ', $class); ?>" data-thread="<?php echo $stack[ 'thread_id' ]; ?>" data-time="<?php echo $message[ 'timestamp' ]; ?>" data-id="<?php echo $message[ 'id' ]; ?>">
                                <span class="favorite <?php if ( $message[ 'stared' ] ) echo 'active'; ?>"><i class="fas" aria-hidden="true"></i></span>
                                <?php if($stack[ 'user_id' ] == get_current_user_id()){ ?>
                                    <span class="status" title="<?php _e('Seen', 'bp-better-messages'); ?>"></span>
                                <?php } ?>
                                <span class="message-content"><?php echo $message[ 'message' ]; ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        public function format_message( $message = '', $message_id = 0, $context = 'stack', $user_id = false )
        {
            global $processedUrls;

            if ( !isset( $processedUrls ) ) $processedUrls = array();

            $message = apply_filters( 'bp_better_messages_pre_format_message', $message, $message_id, $context, $user_id );

            // Removing slashes
            $message = wp_unslash( $message );

            if ( $context == 'site' ) {
                $message = $this->truncate( $message, 100 );
            } else {
                // New line to html <br>
                $message = nl2br( $message );
            }

            //Removing new emojies, while we dont support them yet
            #$message = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $message );

            #var_dump( $message );
            $message = apply_filters( 'bp_better_messages_after_format_message', $message, $message_id, $context, $user_id );

            if ( isset( $processedUrls[ $message_id ] ) && !empty( $processedUrls[ $message_id ] ) ) {
                foreach ( $processedUrls[ $message_id ] as $index => $link ) {
                    $message = str_replace( '%%link_' . ( $index + 1 ) . '%%', $link, $message );
                }
            }

            $message = str_replace('--', '—', $message);

            return $this->clean_string( $message );
        }

        public function filter_message_content( $content ){
            $allowed_tags = [
                'p', 'b', 'i', 'u', 'strike', 'sub', 'sup'
            ];

            if (substr($content, 0, strlen('<p>')) == '<p>') {
                $content = substr($content, strlen('<p>'));
            }

            if (substr($content, 0 - strlen('</p>') ) == '</p>') {
                $content = substr($content, 0, 0 - strlen('</p>'));
            }

            $content = str_replace(array(' style=""', ' style=\"\"'), '', $content);
            $content = esc_textarea( str_replace('<br>', "\n", $content) );

            foreach( $allowed_tags as $tag ){
                $content = str_replace("&lt;".$tag."&gt;", "<".$tag.">",    $content);
                $content = str_replace("&lt;/".$tag."&gt;", "</".$tag.">", $content);
            }

            $content = trim(str_replace(array("&nbsp;", '&amp;nbsp;'), " ", $content));

            return $content;
        }

        function truncate($text, $length) {
            $is_sticker = strpos( $text, '<span class="bpbm-sticker">', 0 ) === 0;
            $is_file    = strpos( $text, '<i class="fas fa-file">' ) !== false;

            if( ! $is_sticker && ! $is_file ) {
                $text = strip_tags($text);
            }

            $length = abs((int)$length);
            if(strlen($text) > $length) {
                $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1...', $text);
            }
            return($text);
        }

        public function get_thread_count( $thread_id, $user_id )
        {
            global $wpdb, $bp;

            return $wpdb->get_var( $wpdb->prepare( "
            SELECT unread_count 
            FROM   {$wpdb->base_prefix}bp_messages_recipients
            WHERE  `thread_id` = %d
            AND    `user_id`   = %d
            ", $thread_id, $user_id ) );
        }

        public function get_name($user_id){
            $user = get_userdata($user_id);
            $name = (!empty( $user->fullname )) ? $user->fullname : $user->display_name;

            return $name;
        }

        public function get_avatar($user_id, $size, $args = array()){
            $user = get_userdata($user_id);

            $fullname = (!empty( $user->fullname )) ? $user->fullname : $user->display_name;

            $defaults = array(
                'type'   => 'full',
                'width'  => $size,
                'height' => $size,
                'class'  => 'avatar',
                'html'   => true,
                'id'     => false,
                'alt'    => sprintf( __( 'Profile picture of %s', 'buddypress' ), $fullname )
            );

            $r = wp_parse_args( $args, $defaults );

            extract( $r, EXTR_SKIP );

            $avatar = apply_filters( 'bp_get_member_avatar',
                bp_core_fetch_avatar(
                    array(
                        'item_id' => $user->ID,
                        'type' => $type,
                        'alt' => $alt,
                        'css_id' => $id,
                        'class' => $class,
                        'width' => $width,
                        'height' => $height,
                        'email' => $user->user_email,
                        'html'  => $html,
                        'extra_attr' => ' '
                    )
                ), $r );

            $avatar = str_replace('/>', '>', $avatar);
            $avatar = str_replace(array('>'), ' data-user-id="' . $user->ID . '">', $avatar);

            return $avatar;
        }

        public function find_existing_threads($from, $to){
            global $wpdb;

            $query = $wpdb->prepare("SELECT
                  {$wpdb->base_prefix}bp_messages_recipients.thread_id
                FROM {$wpdb->base_prefix}bp_messages_recipients
                  INNER JOIN {$wpdb->base_prefix}bp_messages_recipients {$wpdb->base_prefix}bp_messages_recipients_1
                    ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_recipients_1.thread_id
                WHERE {$wpdb->base_prefix}bp_messages_recipients.user_id = %d
                AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
                AND {$wpdb->base_prefix}bp_messages_recipients_1.user_id = %d 
                AND {$wpdb->base_prefix}bp_messages_recipients.thread_id NOT IN (
                    SELECT meta_value 
                    FROM `{$wpdb->base_prefix}postmeta`
                    WHERE post_id IN ( SELECT ID FROM `{$wpdb->base_prefix}posts` WHERE `post_type` = 'bpbm-bulk-report')
                    AND meta_key = 'thread_ids'
                    GROUP BY meta_value
                )
                ORDER BY {$wpdb->base_prefix}bp_messages_recipients.thread_id ASC
                LIMIT 0, 1", $from, $to);

            $threads = $wpdb->get_col($query);

            return $threads;
        }

        public function render_thread( $thread, $user_id = false )
        {
            $current_user_id = get_current_user_id();

            if ( $user_id == false ) {
                $user_id = bp_displayed_user_id();
            }

            if ( $user_id == false ) {
                $user_id = $current_user_id;
            }

            $admin_mode = false;
            if( get_current_user_id() !== $user_id ) $admin_mode = true;

            ob_start();

            $classes = [];
            if ( $thread->unread_count > 0 && BP_Better_Messages()->settings['mechanism'] === 'ajax' ) {
                $classes[] = 'unread';
            }

            $muted_threads = $this->get_user_muted_threads( get_current_user_id() );
            $is_muted = false;
            if( isset($muted_threads[ $thread->thread_id ]) ){
                $is_muted = true;
            }

            if( $is_muted ){
                $classes[] = 'muted';
            }

            ?><div class="thread <?php echo implode(' ', $classes); ?>"
                 data-id="<?php echo $thread->thread_id; ?>"
                 data-message="<?php echo $thread->message_id; ?>"
                 data-href="<?php echo add_query_arg( 'thread_id', $thread->thread_id, BP_Better_Messages()->functions->get_link( $user_id ) ); ?>">
                <div class="pic <?php if ( count( $thread->recipients ) > 1 ) echo 'group'; ?>">
                    <?php
                    if ( count( $thread->recipients ) > 1 ) {
                        $i = 0;
                        foreach ( $thread->recipients as $recipient ) {
                            $i++;
                            echo '<a href="' . bp_core_get_userlink( $recipient, false, true ) . '">' . BP_Better_Messages_Functions()->get_avatar( $recipient, 25 ) . '</a>';
                            if ( $i == 4 ) break;
                        }
                        if ( $i < 4 ) echo BP_Better_Messages_Functions()->get_avatar( $user_id, 25 );
                    } else {
                        $user_id  = array_values( $thread->recipients )[ 0 ];
                        $userdata = get_userdata( $user_id );

                        if( $userdata ){
                            $link = bp_core_get_userlink( $user_id, false, true );
                            $avatar = BP_Better_Messages_Functions()->get_avatar( $user_id, 50 );
                            echo '<a href="' . $link . '">' . $avatar . '</a>';
                        } else {
                            $avatar = BP_Better_Messages_Functions()->get_avatar( 0, 50 );
                            echo $avatar;
                        }
                    } ?>
                </div>
                <div class="info">
                    <?php
                    if ( count( $thread->recipients ) == 1 ) {
                        $user_id  = array_values( $thread->recipients )[ 0 ];
                        $userdata = get_userdata( $user_id );

                        if( $userdata ){
                            $name = apply_filters( 'bp_better_messages_thread_displayname', bp_core_get_user_displayname( $user_id ), $user_id, $thread->thread_id );
                        } else {
                            $name = __('Deleted User', 'bp-better-messages');
                        } ?>
                        <h4 class="name"><?php echo $name; ?></h4>
                    <?php } ?>
                    <?php if(BP_Better_Messages()->settings['disableSubject'] !== '1' && ! empty( $thread->subject )) { ?>
                        <h4><?php echo $thread->subject; ?></h4>
                    <?php } ?>
                    <p><?php
                        if ( ( $thread->user_id !== $user_id ) && ( count( $thread->recipients ) > 1 ) )
                        echo BP_Better_Messages_Functions()->get_avatar( $thread->user_id, 20 );
                        echo $thread->message;
                        ?>
                    </p>
                </div>
                <div class="time">
                    <span class="delete" data-nonce="<?php echo wp_create_nonce( 'delete_' . $thread->thread_id ); ?>"><i class="fas fa-times" aria-hidden="true"></i></span>
                    <span class="time-wrapper" data-livestamp="<?php echo strtotime( $thread->date_sent ); ?>"></span>
                    <div class="bpbm-counter-row">
                        <?php if ( $is_muted ) echo '<span class="bpbm-thread-muted"><i class="fas fa-bell-slash"></i></span>'; ?>
                    <span class="unread-count"><?php if ( $thread->unread_count > 0 && BP_Better_Messages()->settings['mechanism'] === 'ajax' ) echo '+' . $thread->unread_count; ?></span>
                    </div>
                </div>

                <div class="deleted">
                    <?php _e( 'Thread was deleted.', 'bp-better-messages' ); ?>
                    <a class="undelete" data-nonce="<?php echo wp_create_nonce( 'un_delete_' . $thread->thread_id ); ?>" href="#"><?php _e( 'Recover?', 'bp-better-messages' ); ?></a>
                </div>
                <div class="loading">
                    <div class="bounce1"></div>
                    <div class="bounce2"></div>
                    <div class="bounce3"></div>
                </div>
            </div>
            <?php
            return $this->clean_string( ob_get_clean() );
        }

        public function get_pm_thread_id( $to, $from = false ){
            global $wpdb;

            if( ! is_user_logged_in() ) return false;

            if($from === false) $from = get_current_user_id();

            $to_user = get_userdata($to);
            $from_user = get_userdata($from);

            $existing_threads = $this->find_existing_threads( $from_user->ID, $to_user->ID );

            if( count( $existing_threads ) > 0 ){
                return $existing_threads[ 0 ];
            }

            $last_thread = intval($wpdb->get_var("SELECT MAX(thread_id) FROM `{$wpdb->base_prefix}bp_messages_messages`;"));
            $thread_id = $last_thread + 1;

            $wpdb->insert(
                "{$wpdb->base_prefix}bp_messages_recipients",
                array(
                    'user_id' => $from_user->ID,
                    'thread_id' => $thread_id,
                    'unread_count' => 0,
                    'sender_only' => 1,
                    'is_deleted' => 0
                )
            );

            $wpdb->insert(
                "{$wpdb->base_prefix}bp_messages_recipients",
                array(
                    'user_id' => $to_user->ID,
                    'thread_id' => $thread_id,
                    'unread_count' => 0,
                    'sender_only' => 0,
                    'is_deleted' => 0
                )
            );

            $wpdb->insert(
                "{$wpdb->base_prefix}bp_messages_messages",
                array(
                    'sender_id' => 0,
                    'thread_id' => $thread_id,
                    'subject' => '',
                    'message' => '<!-- BBPM START THREAD -->'
                )
            );

            return $thread_id;
        }

        public function get_member_id(){
            $loop_user_id = bp_get_member_user_id();
            if( !! $loop_user_id ) return $loop_user_id;

            $displayed_user_id = bp_displayed_user_id();
            if( !! $displayed_user_id ) return $displayed_user_id;

            $member_user_id = bp_get_member_user_id();
            if( !! $member_user_id ) return $member_user_id;

            return false;
        }

        public function clean_string( $string )
        {
            $string = str_replace( PHP_EOL, ' ', $string );
            $string = preg_replace( '/[\r\n]+/', "\n", $string );
            $string = preg_replace( '/[ \t]+/', ' ', $string );

            return trim($string);
        }

        public function clean_site_url( $url )
        {

            $url = strtolower( $url );

            $url = str_replace( '://www.', '://', $url );

            $url = str_replace( array( 'http://', 'https://' ), '', $url );

            $port = parse_url( $url, PHP_URL_PORT );

            if ( $port ) {
                // strip port number
                $url = str_replace( ':' . $port, '', $url );
            }

            return sanitize_text_field( $url );
        }

        public function hex2rgba($color, $opacity = false) {

            $default = 'rgb(0,0,0)';

            //Return default if no color provided
            if(empty($color))
                return $default;

            //Sanitize $color if "#" is provided
            if ($color[0] == '#' ) {
                $color = substr( $color, 1 );
            }

            //Check if color has 6 or 3 characters and get values
            if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
            } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
            } else {
                return $default;
            }

            //Convert hexadec to rgb
            $rgb =  array_map('hexdec', $hex);

            //Check if opacity is set(rgba or rgb)
            if($opacity){
                if(abs($opacity) > 1)
                    $opacity = 1.0;
                $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
            } else {
                $output = 'rgb('.implode(",",$rgb).')';
            }

            //Return rgb(a) color string
            return $output;
        }

        public function get_page(){
            error_reporting(0);

            do_action('bp_better_messages_before_generation');

            $path = apply_filters('bp_better_messages_views_path', BP_Better_Messages()->path . '/views/');

            $thread_id = false;
            $is_mini = (isset($_GET['mini'])) ? true : false;

            if ( isset( $_GET[ 'thread_id' ] ) ) {
                $thread_id = absint( $_GET[ 'thread_id' ] );
                if ( ! BP_Messages_Thread::check_access( $thread_id ) && ! current_user_can('manage_options') ) {
                    $thread_id = false;
                    echo '<p>' . __( 'Access restricted', 'bp-better-messages' ) . '</p>';

                    if( $is_mini ){
                        wp_send_json('', 403);
                    }

                    $template = 'layout-index.php';
                } else {
                    $template =  'layout-thread.php';
                }
            } else if ( isset( $_GET[ 'new-message' ] ) ) {
                $template =  'layout-new.php';
            } else if ( isset( $_GET[ 'starred' ] ) ) {
                $template = 'layout-starred.php';
            } else if ( isset( $_GET[ 'search' ] ) ) {
                $template = 'layout-search.php';
            } else if ( isset( $_GET[ 'bulk-message' ] ) && current_user_can('manage_options')){
                $template = 'layout-bulk.php';
            } else if (isset( $_GET[ 'settings' ] ) ){
                $template = 'layout-user-settings.php';
            } else {
                $template = 'layout-index.php';
            }

            $template = apply_filters( 'bp_better_messages_current_template', $template );

            ob_start();

            if($template !== false) include($path . $template);

            if( isset($thread_id) && is_int($thread_id)  && ! isset($_GET['mini']) ){
                messages_mark_thread_read( $thread_id );
                update_user_meta(get_current_user_id(), 'bpbm-last-seen-thread-' . $thread_id, time());
            }

            return ob_get_clean();
        }

        public function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
            // Make sure meta is added to the post, not a revision.
            $the_post = wp_is_post_revision( $post_id );
            if ( $the_post ) {
                $post_id = $the_post;
            }

            return add_metadata( 'post', $post_id, $meta_key, $meta_value, $unique );
        }

        public function get_thread_meta( $thread_id, $key = '' ) {
            return get_metadata( 'bpbm_threads', $thread_id, $key, true );
        }

        function update_thread_meta( $thread_id, $meta_key, $meta_value ) {
            return update_metadata( 'bpbm_threads', $thread_id, $meta_key, $meta_value );
        }

        function delete_thread_meta( $thread_id, $meta_key ) {
            return delete_metadata( 'bpbm_threads', $thread_id, $meta_key);
        }

        public function get_user_muted_threads( $user_id ){
            if( BP_Better_Messages()->settings['allowMuteThreads'] !== '1' ) {
                return [];
            }

            $meta_key  = 'bpbm_muted_threads';
            $muted_threads = get_user_meta( $user_id, $meta_key, true);

            if( ! is_array( $muted_threads ) ) {
                $muted_threads = [];
            }

            return $muted_threads;
        }
    }

endif;

/**
 * @return BP_Better_Messages_Functions instance | null
 */
function BP_Better_Messages_Functions()
{
    return BP_Better_Messages_Functions::instance();
}