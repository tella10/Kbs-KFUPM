<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
$message_id = false;
if(isset($_GET['message_id'])) $message_id = intval($_GET['message_id']);
$participants = BP_Better_Messages()->functions->get_participants( $thread_id );
if($message_id){
	$stacks = BP_Better_Messages()->functions->get_stacks( $thread_id, $message_id, 'to_message');
} else {
	$stacks = BP_Better_Messages()->functions->get_stacks( $thread_id );
}

$side_threads = (BP_Better_Messages()->settings['combinedView'] === '1');
$user_id = get_current_user_id();

if( $is_mini ) {
    $side_threads = false;
}
?><div class="bp-messages-wrap bp-messages-wrap-main" data-thread-id="<?php esc_attr_e($thread_id); ?>">
    <div class="chat-header">
        <?php if( ! $is_mini ){ ?>
        <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="back ajax"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
        <?php }
        if(count($participants['links']) < 2) {
            $_user_id = $participants[ 'recipients' ][0];
            $name = $participants[ 'links' ][0];
            $user     = get_userdata($_user_id);

            if( ! $user ) {
                $name = '<a href="#" class="bpbm-deleted-user-link">' . __('Deleted User', 'bp-better-messages') . '</a>';
            }

            if($is_mini){
                echo apply_filters('bp_better_messages_mini_chat_username', strip_tags($name), $_user_id, $thread_id);
            } else {
                echo apply_filters('bp_better_messages_full_chat_username', $name, $_user_id, $thread_id);
            }
        } else {
            $subject = BP_Better_Messages()->functions->get_thread_subject($thread_id);
            $_subject = $subject;
            if( $is_mini ) {
                $_subject = mb_strimwidth($_subject,0, 20, '...');
            }
            echo '<strong title="' . $subject . '">' . $_subject . '</strong>';
            add_action('bp_better_messages_thread_pre_header', function( $thread_id, $participants, $is_mini ){
                echo ' <a href="#" class="participants" title="' . __('participants', 'bp-better-messages') . '" onclick="event.preventDefault();jQuery(\'.participants-panel\').toggleClass(\'open\')"><i class="fas fa-users"></i> ' . count($participants['links']) . '</a>';
            }, 10, 3);
        }?>

        <?php if( ! $is_mini ){ ?>
        <a href="#" class="mobileClose"><i class="fas fa-window-close"></i></a>
            <a href="#" class="mobileButtons"><i class="fas fa-ellipsis-v"></i></a>

        <?php do_action( 'bp_better_messages_thread_pre_header', $thread_id, $participants, $is_mini ); ?>

        <div class="chat-controls">
            <?php /*<span class="edit-message">Editing message<a href="#" class="bpbm-edit-cancel" style=""><i class="fas fa-times"></i> Cancel</a>
            </span>
            <a href="#" class="bpbm-edit" data-wp-nonce="<?php echo wp_create_nonce('edit_message_' . $thread_id); ?>"><i class="fas fa-pencil-alt"></i> <?php _e('Edit', 'bp-better-messages'); ?></a> */ ?>
            <a href="#" class="bpbm-delete" data-wp-nonce="<?php echo wp_create_nonce('delete_message_' . $thread_id); ?>"><i class="fas fa-trash"></i> Delete</a>
        </div>
        <?php } ?>
    </div>

    <?php
    if( $side_threads ) {
        echo '<div class="bp-messages-side-threads-wrapper threads-hidden">';


        if( ! isset( $_REQUEST['ignore_threads'] ) ) {
        $threads = BP_Better_Messages()->functions->get_threads( $user_id );

        if ( !empty( $threads ) ) { ?>
        <div class="bp-messages-side-threads">
            <div class="scroller scrollbar-inner threads-list-wrapper">
                <div class="threads-list">
                    <?php foreach ( $threads as $thread ) {
                        echo BP_Better_Messages()->functions->render_thread( $thread );
                    } ?>

                    <div class="loading-messages">
                        <div class="bounce1"></div>
                        <div class="bounce2"></div>
                        <div class="bounce3"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php }
        }
    } ?>

    <?php if( $side_threads ) echo '<div class="bp-messages-column">'; ?>

    <?php if(count($participants['links']) > 1 && ! isset($_GET['mini'])) {
        $can_moderate = BP_Better_Messages()->functions->is_thread_moderator( get_current_user_id(), $thread_id );
        ?>
        <div class="participants-panel <?php echo (isset($_GET['participants'])) ? 'open' : ''; ?>">
            <div class="scroller scrollbar-inner">
                <div class="bp-messages-user-list">
                    <?php foreach($participants['users'] as $user_id => $_user){
                        $user = get_userdata($user_id);
                        ?>
                        <div class="user" data-id="<?php esc_attr_e($user_id); ?>" data-thread-id="<?php esc_attr_e($thread_id); ?>" data-username="<?php esc_attr_e($user->user_login); ?>">
                            <div class="pic">
                                <?php echo BP_Better_Messages_Functions()->get_avatar( $user_id, 30 ); ?>
                            </div>
                            <div class="name"><a target="_blank"  href="<?php echo bp_core_get_userlink( $user_id, false, true ); ?>"><?php echo BP_Better_Messages_Functions()->get_name( $user_id ); ?></a></div>
                            <div class="actions">
                                <?php if($user_id !== get_current_user_id() && $can_moderate){ ?>
                                    <a href="#" class="remove-from-thread" title="<?php _e('Exclude user from thread', 'bp-better-messages'); ?>"><i class="fas fa-ban"></i></a>
                                <?php } ?>
                            </div>
                            <div class="loading"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php if($can_moderate){ ?>
                <div class="add-user" data-thread-id="<?php esc_attr_e($thread_id); ?>">
                    <p><?php _e('Add new participants', 'bp-better-messages'); ?></p>
                    <div id="send-to" class="input"></div>
                    <button type="submit"><?php _e('Add participants', 'bp-better-messages'); ?></button>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="scroller scrollbar-inner thread" data-users="<?php echo implode( ',', array_keys( $participants[ 'users' ] ) ); ?>" data-users-json="<?php esc_attr_e(json_encode( $participants[ 'users' ] )); ?>" data-id="<?php echo $thread_id; ?>"<?php do_action('bp_better_messages_thread_div', $thread_id) ?>>
        <div class="loading-messages">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
        <div class="list">
            <?php if(count($stacks) == 0 || count($stacks) == 1 && $stacks[0]['user_id'] == 0){ ?>
            <div class="empty-thread">
                <i class="fas fa-comments"></i>
                <span><?php esc_attr_e('Write the message to start conversation', 'bp-better-messages'); ?></span>
            </div>
            <?php } else {
                foreach ( $stacks as $stack ) {
                    echo BP_Better_Messages()->functions->render_stack( $stack );
                }
            } ?>
        </div>
    </div>

    <?php do_action( 'bp_better_messages_thread_after_scroller', $thread_id, $participants, $is_mini ); ?>

    <span class="writing" style="display: none"></span>

    <?php if( apply_filters('bp_better_messages_can_send_message', BP_Messages_Thread::check_access( $thread_id ), get_current_user_id(), $thread_id ) ) { ?>
    <div class="reply">
        <form action="" method="POST">
            <div class="message">
                <?php do_action( 'bp_messages_before_reply_textarea', $thread_id ); ?>
                <textarea placeholder="<?php esc_attr_e( "Write your message", 'bp-better-messages' ); ?>" name="message" autocomplete="off"></textarea>
                <?php do_action( 'bp_messages_after_reply_textarea', $thread_id ); ?>
            </div>
            <div class="send">
                <?php do_action('bp_better_messages_before_reply_send'); ?>
                <button type="submit"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                <?php do_action('bp_better_messages_after_reply_send'); ?>
            </div>
            <input type="hidden" name="action" value="bp_messages_send_message">
            <input type="hidden" name="message_id" value="">
            <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
            <?php wp_nonce_field( 'sendMessage_' . $thread_id ); ?>
        </form>

        <span class="clearfix"></span>

        <?php do_action( 'bp_messages_after_reply_form', $thread_id ); ?>
    </div>


   <?php do_action( 'bp_messages_after_reply_div', $thread_id ); ?>
    <?php } else {
        global $bp_better_messages_restrict_send_message;
        if( is_array($bp_better_messages_restrict_send_message) && ! empty( $bp_better_messages_restrict_send_message ) ){
            echo '<div class="reply">';
            echo '<ul class="bp-better-messages-restrict-send-message">';
            foreach( $bp_better_messages_restrict_send_message as $error ){
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    } ?>

    <div class="preloader"></div>

    <?php if( $side_threads ) echo '</div></div>'; ?>
    <?php if( ! $is_mini && BP_Better_Messages()->settings['disableTapToOpen'] === '0' ){ ?>
        <div class="bp-messages-mobile-tap"><?php _e( 'Tap to open messages', 'bp-better-messages' ); ?></div>
    <?php } ?>
</div>