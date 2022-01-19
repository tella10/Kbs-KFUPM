<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Calls' ) ):

    class BP_Better_Messages_Calls
    {

        public $new_thread_upload = false;

        public $audio = false;

        public $video = false;

        public static function instance()
        {
            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Calls();
            }

            return $instance;
        }


        public function __construct()
        {
            $this->audio = BP_Better_Messages()->settings['audioCalls'] === '1';
            $this->video = BP_Better_Messages()->settings['videoCalls'] === '1';

            add_action( 'bp_better_messages_thread_pre_header', array( $this, 'call_button' ), 10, 3 );
            add_action( 'bp_better_messages_thread_after_scroller', array( $this, 'html_content' ), 10, 3 );
            //exit;
        }

        public function call_button($thread_id, $participants, $is_mini){
            if( $is_mini ) return false;
            if( ! apply_filters('bp_better_messages_can_send_message', BP_Messages_Thread::check_access( $thread_id ), get_current_user_id(), $thread_id ) ) return false;
            if( count( $participants['recipients'] ) === 1 ){
                if( $this->video ){
                    echo '<a href="#" class="video-call bpbm-can-be-hidden" data-user-id="' . $participants[ "recipients" ][0] . '"  title="' . __("Video Call", "bp-better-messages") . '"><i class="fas fa-video"></i></a>';
                }

                if( $this->audio ){
                    echo '<a href="#" class="audio-call bpbm-can-be-hidden" data-user-id="' . $participants[ "recipients" ][0] . '"  title="' . __("Audio Call", "bp-better-messages") . '"><i class="fas fa-phone"></i></a>';
                }
            }
        }

        public function html_content( $thread_id, $participants, $is_mini ){
            if( $is_mini ) return false;
            if( ! apply_filters('bp_better_messages_can_send_message', BP_Messages_Thread::check_access( $thread_id ), get_current_user_id(), $thread_id ) ) return false;

            if( $this->video ){
            ?><div class="bp-messages-video-container bp-messages-call-container" style="display: none;" data-thread-id="<?php echo $thread_id ?>" data-my-name="<?php echo BP_Better_Messages_Functions()->get_name( get_current_user_id() ) ?>" data-my-avatar='<?php echo BP_Better_Messages_Functions()->get_avatar( get_current_user_id(), 100, [ 'html' => false ] ); ?>'>
                <span class="bp-messages-main-video" style="display:none;"></span>
                <span class="bp-messages-small-video"></span>

                <div class="bp-messages-main-placeholder">
                    <div class="bp-messages-placeholder-video"></div>
                    <div class="bp-messages-call-animation">
                        <?php echo BP_Better_Messages_Functions()->get_avatar($participants[ 'recipients' ][0], 100); ?>
                    </div>
                    <div class="bp-messages-placeholder-message">
                        <span class="bp-messages-placeholder-incoming-text"><?php _e('Incoming Call', 'bp-better-messages'); ?></span>
                        <span class="bp-messages-placeholder-outgoing-text"><?php _e('Calling...', 'bp-better-messages'); ?></span>
                    </div>
                </div>
                <div class="bp-messages-call-controls" style="display: none">
                    <div class="bpbm-call-out">
                        <span class="bpbm-disable-video" title="<?php _e('Disable Video', 'bp-better-messages'); ?>"><i class="fas fa-video-slash"></i></span>
                        <span class="bpbm-enable-video" title="<?php _e('Enable Video', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-video"></i></span>

                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>
                        <span class="bpbm-cancel" title="<?php _e('Cancel', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                    <div class="bpbm-call-in">
                        <span class="bpbm-answer" data-user-id="<?php echo $participants[ 'recipients' ][0]; ?>" title="<?php _e('Answer', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>

                        <span class="bpbm-disable-video" title="<?php _e('Disable Video', 'bp-better-messages'); ?>"><i class="fas fa-video-slash"></i></span>
                        <span class="bpbm-enable-video" title="<?php _e('Enable Video', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-video"></i></span>

                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>

                        <span class="bpbm-reject" data-user-id="<?php echo $participants[ 'recipients' ][0]; ?>" title="<?php _e('Reject', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                    <div class="bpbm-call-in-progress">
                        <span class="bpbm-disable-video" title="<?php _e('Disable Video', 'bp-better-messages'); ?>"><i class="fas fa-video-slash"></i></span>
                        <span class="bpbm-enable-video" title="<?php _e('Enable Video', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-video"></i></span>

                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>

                        <span class="bpbm-call-end" title="<?php _e('End call', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                </div>
            </div>
            <?php
            }

            if( $this->audio ){ ?>
            <div class="bp-messages-audio-container bp-messages-call-container" style="display: none" data-thread-id="<?php echo $thread_id ?>" data-my-name="<?php echo BP_Better_Messages_Functions()->get_name( get_current_user_id() ) ?>" data-my-avatar='<?php echo BP_Better_Messages_Functions()->get_avatar( get_current_user_id(), 100, [ 'html' => false ] ); ?>'>

                <div class="bp-messages-main-placeholder">
                    <div class="bp-messages-call-animation">
                        <?php echo BP_Better_Messages_Functions()->get_avatar($participants[ 'recipients' ][0], 100); ?>
                    </div>
                    <div class="bp-messages-placeholder-message">
                        <span class="bp-messages-timer"></span>
                        <span class="bp-messages-placeholder-incoming-text"><?php _e('Incoming Call', 'bp-better-messages'); ?></span>
                        <span class="bp-messages-placeholder-outgoing-text"><?php _e('Calling...', 'bp-better-messages'); ?></span>
                    </div>
                </div>

                <div class="bp-messages-call-controls" style="display: none">
                    <div class="bpbm-call-out">
                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>
                        <span class="bpbm-cancel" title="<?php _e('Cancel', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                    <div class="bpbm-call-in">
                        <span class="bpbm-answer" data-user-id="<?php echo $participants[ 'recipients' ][0]; ?>" title="<?php _e('Answer', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>

                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>

                        <span class="bpbm-reject" data-user-id="<?php echo $participants[ 'recipients' ][0]; ?>" title="<?php _e('Reject', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                    <div class="bpbm-call-in-progress">
                        <span class="bpbm-disable-mic" title="<?php _e('Disable Microphone', 'bp-better-messages'); ?>"><i class="fas fa-microphone-slash"></i></span>
                        <span class="bpbm-enable-mic" title="<?php _e('Enable Microphone', 'bp-better-messages'); ?>" style="display: none"><i class="fas fa-microphone"></i></span>

                        <span class="bpbm-call-end" title="<?php _e('End call', 'bp-better-messages'); ?>"><i class="fas fa-phone"></i></span>
                    </div>
                </div>


                <div class="bp-messages-audio-element">
                </div>
            </div>
            <?php }
        }

    }

endif;


function BP_Better_Messages_Calls()
{
    return BP_Better_Messages_Calls::instance();
}
