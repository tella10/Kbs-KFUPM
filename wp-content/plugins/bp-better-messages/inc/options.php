<?php

defined( 'ABSPATH' ) || exit;
class BP_Better_Messages_Options
{
    protected  $path ;
    public  $settings ;
    public static function instance()
    {
        static  $instance = null ;
        
        if ( null === $instance ) {
            $instance = new BP_Better_Messages_Options();
            $instance->setup_globals();
            $instance->setup_actions();
        }
        
        return $instance;
    }
    
    public function setup_globals()
    {
        $this->path = BP_Better_Messages()->path . '/views/';
        $defaults = array(
            'mechanism'                 => 'ajax',
            'thread_interval'           => 3,
            'site_interval'             => 10,
            'messagesPerPage'           => 20,
            'attachmentsFormats'        => array(),
            'attachmentsRetention'      => 365,
            'attachmentsEnable'         => '0',
            'attachmentsHide'           => '1',
            'attachmentsRandomName'     => '1',
            'attachmentsMaxSize'        => wp_max_upload_size() / 1024 / 1024,
            'miniChatsEnable'           => '0',
            'searchAllUsers'            => '0',
            'disableSubject'            => '0',
            'disableEnterForTouch'      => '1',
            'disableTapToOpen'          => '0',
            'autoFullScreen'            => '0',
            'mobilePopup'               => '0',
            'mobileFullScreen'          => '1',
            'chatPage'                  => '0',
            'messagesStatus'            => '0',
            'allowDeleteMessages'       => '0',
            'disableDeleteThreadCheck'  => '0',
            'fastStart'                 => '1',
            'miniThreadsEnable'         => '0',
            'miniFriendsEnable'         => '0',
            'friendsMode'               => '0',
            'singleThreadMode'          => '0',
            'redirectToExistingThread'  => '0',
            'disableGroupThreads'       => '0',
            'replaceStandardEmail'      => '1',
            'oEmbedEnable'              => '1',
            'disableEnterForDesktop'    => '0',
            'restrictNewThreads'        => [],
            'restrictNewThreadsMessage' => __( 'You are not allowed to start new threads', 'bp-better-messages' ),
            'restrictNewReplies'        => [],
            'restrictNewRepliesMessage' => __( 'You are not allowed to reply', 'bp-better-messages' ),
            'videoCalls'                => '0',
            'audioCalls'                => '0',
            'blockScroll'               => '1',
            'userListButton'            => '0',
            'combinedView'              => '0',
            'enablePushNotifications'   => '0',
            'colorGeneral'              => '#21759b',
            'mobileEmojiEnable'         => '0',
            'encryptionEnabled'         => '0',
            'stipopApiKey'              => '',
            'stipopLanguage'            => 'en',
            'allowMuteThreads'          => '1',
        );
        $args = get_option( 'bp-better-chat-settings', array() );
        
        if ( !bpbm_fs()->can_use_premium_code() ) {
            $args['mechanism'] = 'ajax';
            $args['miniChatsEnable'] = '0';
            $args['messagesStatus'] = '0';
            $args['miniThreadsEnable'] = '0';
            $args['videoCalls'] = '0';
            $args['audioCalls'] = '0';
            $args['encryptionEnabled'] = '0';
        }
        
        $this->settings = wp_parse_args( $args, $defaults );
    }
    
    public function setup_actions()
    {
        add_action( 'admin_menu', array( $this, 'settings_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'add_color_picker' ) );
    }
    
    /**
     * Settings page
     */
    public function settings_page()
    {
        add_menu_page(
            __( 'BP Better Messages' ),
            __( 'Better Messages' ),
            'manage_options',
            'bp-better-messages',
            array( $this, 'settings_page_html' ),
            'dashicons-format-chat'
        );
    }
    
    public function add_color_picker( $hook )
    {
        
        if ( $hook === 'toplevel_page_bp-better-messages' && is_admin() ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }
    
    }
    
    public function settings_page_html()
    {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        if ( isset( $_POST['_wpnonce'] ) && !empty($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'bp-better-messages-settings' ) ) {
            unset( $_POST['_wpnonce'], $_POST['_wp_http_referer'] );
            
            if ( isset( $_POST['save'] ) ) {
                unset( $_POST['save'] );
                $this->update_settings( $_POST );
            }
        
        }
        
        include $this->path . 'layout-settings.php';
    }
    
    public function update_settings( $settings )
    {
        if ( !isset( $settings['attachmentsEnable'] ) ) {
            $settings['attachmentsEnable'] = '0';
        }
        if ( !isset( $settings['attachmentsHide'] ) ) {
            $settings['attachmentsHide'] = '0';
        }
        if ( !isset( $settings['attachmentsRandomName'] ) ) {
            $settings['attachmentsRandomName'] = '0';
        }
        if ( !isset( $settings['miniChatsEnable'] ) ) {
            $settings['miniChatsEnable'] = '0';
        }
        if ( !isset( $settings['searchAllUsers'] ) ) {
            $settings['searchAllUsers'] = '0';
        }
        if ( !isset( $settings['disableSubject'] ) ) {
            $settings['disableSubject'] = '0';
        }
        if ( !isset( $settings['disableEnterForTouch'] ) ) {
            $settings['disableEnterForTouch'] = '0';
        }
        if ( !isset( $settings['disableTapToOpen'] ) ) {
            $settings['disableTapToOpen'] = '0';
        }
        if ( !isset( $settings['mobileFullScreen'] ) ) {
            $settings['mobileFullScreen'] = '0';
        }
        if ( !isset( $settings['messagesStatus'] ) ) {
            $settings['messagesStatus'] = '0';
        }
        if ( !isset( $settings['allowDeleteMessages'] ) ) {
            $settings['allowDeleteMessages'] = '0';
        }
        if ( !isset( $settings['fastStart'] ) ) {
            $settings['fastStart'] = '0';
        }
        if ( !isset( $settings['miniFriendsEnable'] ) ) {
            $settings['miniFriendsEnable'] = '0';
        }
        if ( !isset( $settings['miniThreadsEnable'] ) ) {
            $settings['miniThreadsEnable'] = '0';
        }
        if ( !isset( $settings['friendsMode'] ) ) {
            $settings['friendsMode'] = '0';
        }
        if ( !isset( $settings['singleThreadMode'] ) ) {
            $settings['singleThreadMode'] = '0';
        }
        if ( !isset( $settings['redirectToExistingThread'] ) ) {
            $settings['redirectToExistingThread'] = '0';
        }
        if ( !isset( $settings['disableGroupThreads'] ) ) {
            $settings['disableGroupThreads'] = '0';
        }
        if ( !isset( $settings['replaceStandardEmail'] ) ) {
            $settings['replaceStandardEmail'] = '0';
        }
        if ( !isset( $settings['mobilePopup'] ) ) {
            $settings['mobilePopup'] = '0';
        }
        if ( !isset( $settings['autoFullScreen'] ) ) {
            $settings['autoFullScreen'] = '0';
        }
        if ( !isset( $settings['disableDeleteThreadCheck'] ) ) {
            $settings['disableDeleteThreadCheck'] = '0';
        }
        if ( !isset( $settings['oEmbedEnable'] ) ) {
            $settings['oEmbedEnable'] = '0';
        }
        if ( !isset( $settings['disableEnterForDesktop'] ) ) {
            $settings['disableEnterForDesktop'] = '0';
        }
        if ( !isset( $settings['restrictNewThreads'] ) ) {
            $settings['restrictNewThreads'] = [];
        }
        if ( !isset( $settings['restrictNewReplies'] ) ) {
            $settings['restrictNewReplies'] = [];
        }
        if ( !isset( $settings['videoCalls'] ) ) {
            $settings['videoCalls'] = '0';
        }
        if ( !isset( $settings['audioCalls'] ) ) {
            $settings['audioCalls'] = '0';
        }
        if ( !isset( $settings['blockScroll'] ) ) {
            $settings['blockScroll'] = '0';
        }
        if ( !isset( $settings['userListButton'] ) ) {
            $settings['userListButton'] = '0';
        }
        if ( !isset( $settings['combinedView'] ) ) {
            $settings['combinedView'] = '0';
        }
        if ( !isset( $settings['enablePushNotifications'] ) ) {
            $settings['enablePushNotifications'] = '0';
        }
        if ( !isset( $settings['mobileEmojiEnable'] ) ) {
            $settings['mobileEmojiEnable'] = '0';
        }
        if ( !isset( $settings['encryptionEnabled'] ) ) {
            $settings['encryptionEnabled'] = '0';
        }
        if ( !isset( $settings['allowMuteThreads'] ) ) {
            $settings['allowMuteThreads'] = '0';
        }
        $links_allowed = [ 'restrictNewThreadsMessage', 'restrictNewRepliesMessage' ];
        foreach ( $settings as $key => $value ) {
            /** Processing checkbox groups **/
            
            if ( is_array( $value ) ) {
                $this->settings[$key] = array();
                foreach ( $value as $val ) {
                    $this->settings[$key][] = sanitize_text_field( $val );
                }
            } else {
                
                if ( in_array( $key, $links_allowed ) ) {
                    $this->settings[$key] = wp_kses( $value, 'user_description' );
                } else {
                    $this->settings[$key] = sanitize_text_field( $value );
                }
            
            }
        
        }
        update_option( 'bp-better-chat-settings', $this->settings );
        do_action( 'bp_better_chat_settings_updated', $this->settings );
    }

}
function BP_Better_Messages_Options()
{
    return BP_Better_Messages_Options::instance();
}
