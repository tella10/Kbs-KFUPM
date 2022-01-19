<?php
defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();
if ( bp_displayed_user_id() !== 0 ) $user_id = bp_displayed_user_id();

$threads = BP_Better_Messages()->functions->get_threads( $user_id );
$favorited = BP_Better_Messages()->functions->get_starred_count();
?>
<div class="bp-messages-wrap bp-messages-wrap-main">
    <div class="chat-header">
        <a href="<?php echo add_query_arg( 'new-message', '', BP_Better_Messages()->functions->get_link() ); ?>" class="new-message ajax" title="<?php _e( 'New Thread', 'bp-better-messages' ); ?>"><i class="fas fa-plus" aria-hidden="true"></i></a>
        <a href="<?php echo add_query_arg( 'starred', '', BP_Better_Messages()->functions->get_link() ); ?>" class="starred-messages ajax" title="<?php _e( 'Starred', 'bp-better-messages' ); ?>"><i class="fas fa-star" aria-hidden="true"></i> <?php echo $favorited; ?></a>

        <div class="bpbm-search">
            <form style="display: none">
                <input title="<?php _e( 'Search', 'bp-better-messages' ); ?>" type="text" name="search" value="">
                <span class="close"><i class="fas fa-times" aria-hidden="true"></i></span>
            </form>
            <a href="#" class="search" title="<?php _e( 'Search', 'bp-better-messages' ); ?>"><i class="fas fa-search" aria-hidden="true"></i></a>
        </div>

        <?php /*
        <span class="push-notifications" title="<?php _e( 'Enable browser notifications', 'bp-better-messages' ); ?>"><i class="fas fa-bell" aria-hidden="true"></i></span>
        */ ?>
        <a href="#" class="mobileClose"><i class="fas fa-window-close"></i></a>
        <a href="<?php echo add_query_arg( 'settings', '', BP_Better_Messages()->functions->get_link() ); ?>" class="settings ajax" title="<?php _e( 'Settings', 'bp-better-messages' ); ?>"><i class="fas fa-cog"></i></a>

    </div>
    <?php if ( ! empty( $threads ) ) { ?>
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
    <?php } else { ?>
    <div class="threads-list">
        <p class="empty">
            <?php _e( 'Nothing found', 'bp-better-messages' ); ?>
        </p>
    </div>
    <?php } ?>

    <script type="text/javascript">
        jQuery('.bp-better-messages-unread').text(<?php echo BP_Messages_Thread::get_total_threads_for_user( get_current_user_id(), 'inbox', 'unread' ); ?>);
    </script>

    <div class="preloader"></div>

    <?php if( BP_Better_Messages()->settings['disableTapToOpen'] === '0' ){ ?>
        <div class="bp-messages-mobile-tap"><?php _e( 'Tap to open messages', 'bp-better-messages' ); ?></div>
    <?php } ?>
</div>