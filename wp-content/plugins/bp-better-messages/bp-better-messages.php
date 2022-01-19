<?php

/*
@wordpress-plugin
Plugin Name: BP Better Messages
Plugin URI: https://www.wordplus.org
Description: Enhanced Private Messages System for BuddyPress and WordPress
Version: 1.9.8.38
Author: WordPlus
Author URI: https://www.wordplus.org
License: GPL2
Text Domain: bp-better-messages
Domain Path: /languages
*/
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages' ) && !function_exists( 'bpbm_fs' ) ) {
    class BP_Better_Messages
    {
        public  $realtime ;
        public  $version = '1.9.8.38' ;
        public  $path ;
        public  $url ;
        public  $settings ;
        /** @var BP_Better_Messages_Options $functions */
        public  $options ;
        /** @var BP_Better_Messages_Functions $functions */
        public  $functions ;
        /** @var BP_Better_Messages_Ajax $functions */
        public  $ajax ;
        /** @var BP_Better_Messages_Premium $functions */
        public  $premium = false ;
        public static function instance()
        {
            // Store the instance locally to avoid private static replication
            static  $instance = null ;
            // Only run these methods if they haven't been run previously
            
            if ( null === $instance ) {
                $instance = new BP_Better_Messages();
                $instance->load_textDomain();
                $instance->setup_vars();
                $instance->setup_actions();
                $instance->setup_classes();
            }
            
            // Always return the instance
            return $instance;
            // The last metroid is in captivity. The galaxy is at peace.
        }
        
        public function setup_vars()
        {
            $this->realtime = false;
            $this->path = plugin_dir_path( __FILE__ );
            $this->url = plugin_dir_url( __FILE__ );
        }
        
        public function setup_actions()
        {
            $this->require_files();
            add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
        }
        
        /**
         * Require necessary files
         */
        public function require_files()
        {
            require_once 'inc/functions.php';
            /**
             * Require component only if BuddyPress is active
             */
            if ( class_exists( 'BP_Component' ) ) {
                require_once 'inc/component.php';
            }
            require_once 'inc/ajax.php';
            require_once 'inc/hooks.php';
            require_once 'inc/options.php';
            require_once 'inc/notifications.php';
            require_once 'inc/bulk.php';
            require_once 'inc/rooms.php';
            require_once 'inc/mini-list.php';
            require_once 'addons/urls.php';
            require_once 'addons/files.php';
            require_once 'addons/emojies.php';
            require_once 'addons/stickers.php';
            require_once 'addons/calls.php';
            require_once BP_Better_Messages()->path . 'vendor/AES256.php';
        }
        
        public function setup_classes()
        {
            $this->options = BP_Better_Messages_Options();
            $this->functions = BP_Better_Messages_Functions();
            $this->load_options();
            $this->hooks = BP_Better_Messages_Hooks();
            $this->ajax = BP_Better_Messages_Ajax();
            if ( function_exists( 'BP_Better_Messages_Tab' ) ) {
                $this->tab = BP_Better_Messages_Tab();
            }
            if ( $this->settings['replaceStandardEmail'] === '1' ) {
                $this->email = BP_Better_Messages_Notifications();
            }
            $this->bulk = BP_Better_Messages_Bulk();
            $this->rooms = BP_Better_Messages_Rooms();
            $this->mini_list = BP_Better_Messages_Mini_List();
            if ( $this->settings['attachmentsEnable'] === '1' ) {
                $this->files = BP_Better_Messages_Files();
            }
            if ( $this->settings['searchAllUsers'] === '1' && !defined( 'BP_MESSAGES_AUTOCOMPLETE_ALL' ) ) {
                define( 'BP_MESSAGES_AUTOCOMPLETE_ALL', true );
            }
            $this->urls = BP_Better_Messages_Urls();
            $this->emoji = BP_Better_Messages_Emojies();
            $this->stickers = BP_Better_Messages_Stickers();
            if ( $this->settings['videoCalls'] === '1' || $this->settings['audioCalls'] === '1' ) {
                $this->calls = BP_Better_Messages_Calls();
            }
        }
        
        public function load_options()
        {
            $this->settings = $this->options->settings;
        }
        
        public function load_textDomain()
        {
            load_plugin_textdomain( 'bp-better-messages', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }
        
        public function load_scripts()
        {
            if ( !is_user_logged_in() ) {
                return false;
            }
            $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
            wp_register_script( 'emojionearea_js', plugins_url( 'assets/js/emojionearea' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'taggle_js', plugins_url( 'assets/js/taggle.min.js', __FILE__ ), $this->version );
            wp_register_script( 'scrollbar_js', plugins_url( 'assets/js/overlay-scrollbars' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'jquery_runner_js', plugins_url( 'assets/js/jquery.runner' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'ion_sound_js', plugins_url( 'assets/js/ion.sound.min.js', __FILE__ ), $this->version );
            wp_register_script( 'amaran_js', plugins_url( 'assets/js/jquery.amaran' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'store_js', plugins_url( 'assets/js/store.min.js', __FILE__ ), $this->version );
            wp_register_script( 'if_visible', plugins_url( 'assets/js/ifvisible.min.js', __FILE__ ), $this->version );
            wp_register_script( 'bpbm-magnific-popup', plugins_url( 'assets/js/magnific' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'bpbm-medium-editor-js', plugins_url( 'assets/js/medium-editor' . $suffix . '.js', __FILE__ ), $this->version );
            wp_register_script( 'bpbm-aes-256-js', plugins_url( 'assets/js/aes256.min.js', __FILE__ ), $this->version );
            $dependencies = array(
                'jquery',
                'jquery-ui-autocomplete',
                'emojionearea_js',
                'scrollbar_js',
                'taggle_js',
                'amaran_js',
                'store_js',
                'if_visible',
                'wp-mediaelement',
                'bpbm-magnific-popup',
                'ion_sound_js',
                'bp-livestamp',
                'jquery_runner_js',
                'bpbm-medium-editor-js'
            );
            if ( !wp_script_is( 'bp-moment' ) ) {
                wp_register_script(
                    'bp-moment',
                    plugins_url( 'vendor/buddypress/moment-js/moment.js', __FILE__ ),
                    array(),
                    $this->version
                );
            }
            if ( !wp_script_is( 'bp-livestamp' ) ) {
                wp_register_script(
                    'bp-livestamp',
                    plugins_url( 'vendor/buddypress/livestamp.js', __FILE__ ),
                    array( 'jquery', 'bp-moment' ),
                    $this->version
                );
            }
            
            if ( wp_script_is( 'bp-moment-locale' ) ) {
                $dependencies[] = 'bp-moment-locale';
            } else {
                $locale = sanitize_file_name( strtolower( get_locale() ) );
                $locale = str_replace( '_', '-', $locale );
                
                if ( file_exists( "/vendor/buddypress/moment-js/locale/{$locale}.min.js" ) ) {
                    $moment_locale_url = "/vendor/buddypress/moment-js/locale/{$locale}.min.js";
                } else {
                    $locale = substr( $locale, 0, strpos( $locale, '-' ) );
                    if ( file_exists( "/vendor/buddypress/moment-js/locale/{$locale}.min.js" ) ) {
                        $moment_locale_url = "/vendor/buddypress/moment-js/locale/{$locale}.min.js";
                    }
                }
                
                if ( isset( $moment_locale_url ) ) {
                    wp_register_script(
                        'bp-livestamp',
                        plugins_url( $moment_locale_url, __FILE__ ),
                        array( 'bp-moment' ),
                        $this->version
                    );
                }
            }
            
            $script_variables = array(
                'ajaxUrl'                => rtrim( admin_url( 'admin-ajax.php' ), '/' ),
                'siteRefresh'            => ( isset( $this->settings['site_interval'] ) ? intval( $this->settings['site_interval'] ) * 1000 : 10000 ),
                'threadRefresh'          => ( isset( $this->settings['thread_interval'] ) ? intval( $this->settings['thread_interval'] ) * 1000 : 3000 ),
                'url'                    => $this->functions->get_link(),
                'threadUrl'              => $this->functions->get_link( get_current_user_id() ) . '?thread_id=',
                'baseUrl'                => $this->functions->get_link( get_current_user_id() ),
                'assets'                 => apply_filters( 'bp_better_messages_sounds_assets', plugin_dir_url( __FILE__ ) . 'assets/sounds/' ),
                'user_id'                => get_current_user_id(),
                'displayed_user_id'      => $this->functions->get_displayed_user_id(),
                'realtime'               => $this->realtime,
                'total_unread'           => BP_Messages_Thread::get_total_threads_for_user( get_current_user_id(), 'inbox', 'unread' ),
                'max_height'             => apply_filters( 'bp_better_messages_max_height', 500 ),
                'fastStart'              => ( $this->settings['fastStart'] == '1' ? '1' : '0' ),
                'blockScroll'            => ( $this->settings['blockScroll'] == '1' ? '1' : '0' ),
                'disableEnterForTouch'   => ( $this->settings['disableEnterForTouch'] == '1' ? '1' : '0' ),
                'mobileFullScreen'       => ( $this->settings['mobileFullScreen'] == '1' ? '1' : '0' ),
                'disableEnterForDesktop' => ( $this->settings['disableEnterForDesktop'] == '1' ? '1' : '0' ),
                'disableTapToOpen'       => ( $this->settings['disableTapToOpen'] == '1' ? '1' : '0' ),
                'autoFullScreen'         => ( $this->settings['autoFullScreen'] == '1' ? '1' : '0' ),
                'miniChats'              => ( $this->realtime && $this->settings['miniChatsEnable'] ? '1' : '0' ),
                'miniMessages'           => ( $this->realtime && $this->settings['miniThreadsEnable'] ? '1' : '0' ),
                'messagesStatus'         => ( $this->realtime && $this->settings['messagesStatus'] ? '1' : '0' ),
                'mobileEmojiEnable'      => ( $this->settings['mobileEmojiEnable'] == '1' ? '1' : '0' ),
                'allowDeleteMessages'    => ( $this->settings['allowDeleteMessages'] == '1' ? '1' : '0' ),
                'combinedView'           => ( $this->settings['combinedView'] == '1' ? '1' : '0' ),
                'allowMuteThreads'       => ( $this->settings['allowMuteThreads'] == '1' ? '1' : '0' ),
                'enableSound'            => '1',
                'forceMobile'            => apply_filters( 'bp_better_messages_force_mobile_view', '0' ),
                'strings'                => array(
                'writing'         => __( 'typing...', 'bp-better-messages' ),
                'sent'            => __( 'Sent', 'bp-better-messages' ),
                'delivered'       => __( 'Delivered', 'bp-better-messages' ),
                'seen'            => __( 'Seen', 'bp-better-messages' ),
                'new_messages'    => __( 'You have %s new messages', 'bp-better-messages' ),
                'confirm_delete'  => __( 'Are you sure you want to delete %s messages', 'bp-better-messages' ),
                'connection_drop' => __( 'Connection dropped! Most likely the user closed the browser', 'bp-better-messages' ),
                'incoming_call'   => __( 'Incoming Call', 'bp-better-messages' ),
                'call_reject'     => __( 'User rejected your call', 'bp-better-messages' ),
                'call_offline'    => __( 'User is offline at the moment. Try later.', 'bp-better-messages' ),
                'cam_not_works'   => __( 'Webcam not available', 'bp-better-messages' ),
                'answer_call'     => __( 'Answer', 'bp-better-messages' ),
                'reject_call'     => __( 'Reject', 'bp-better-messages' ),
                'you_are_in_call' => __( 'You are currently in call, are you sure you want to leave this page?', 'bp-better-messages' ),
                'no_webrtc'       => __( 'This browser not support video calls feature yet. Please use another browser. iOS only support safari web browser at the moment.', 'bp-better-messages' ),
                'push_request'    => __( 'Enable browser push notifications to receive private messages when you are offline?', 'bp-better-messages' ),
                'enable'          => __( 'Enable', 'bp-better-messages' ),
                'dismiss'         => __( 'Dismiss', 'bp-better-messages' ),
                'mute_thread'     => __( 'Are you sure you want to mute this thread?', 'bp-better-messages' ),
                'unmute_thread'   => __( 'Are you sure you want to unmute this thread?', 'bp-better-messages' ),
            ),
            );
            $script_variables['mutedThreads'] = BP_Better_Messages()->functions->get_user_muted_threads( get_current_user_id() );
            wp_register_script(
                'bp_messages_js',
                plugins_url( 'assets/js/bp-messages' . $suffix . '.js', __FILE__ ),
                $dependencies,
                $this->version
            );
            wp_localize_script( 'bp_messages_js', 'BP_Messages', $script_variables );
            wp_localize_script( 'emojionearea_js', 'BP_Messages_Emoji', array(
                'tab'              => __( 'Use the TAB key to insert emoji faster', 'bp-better-messages' ),
                'Recent'           => __( 'Recent', 'bp-better-messages' ),
                'Smileys & People' => __( 'Smileys & People', 'bp-better-messages' ),
                'Animals & Nature' => __( 'Animals & Nature', 'bp-better-messages' ),
                'Food & Drink'     => __( 'Food & Drink', 'bp-better-messages' ),
                'Activity'         => __( 'Activity', 'bp-better-messages' ),
                'Travel & Places'  => __( 'Travel & Places', 'bp-better-messages' ),
                'Objects'          => __( 'Objects', 'bp-better-messages' ),
                'Symbols'          => __( 'Symbols', 'bp-better-messages' ),
                'Flags'            => __( 'Flags', 'bp-better-messages' ),
            ) );
            bp_core_enqueue_livestamp();
            wp_enqueue_script( 'bp_messages_js' );
            wp_enqueue_style(
                'emojionearea_css',
                plugins_url( 'assets/css/emojionearea' . $suffix . '.css', __FILE__ ),
                false,
                $this->version
            );
            wp_enqueue_style(
                'font_awesome_css',
                plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ),
                false,
                $this->version
            );
            wp_enqueue_style(
                'amaran_css',
                plugins_url( 'assets/css/amaran.min.css', __FILE__ ),
                false,
                $this->version
            );
            wp_enqueue_style( 'wp-mediaelement' );
            wp_enqueue_style(
                'bp_messages_css',
                plugins_url( 'assets/css/bp-messages' . $suffix . '.css', __FILE__ ),
                false,
                $this->version
            );
            wp_enqueue_style(
                'magnific-popup-css',
                plugins_url( 'assets/css/magnific' . $suffix . '.css', __FILE__ ),
                false,
                $this->version
            );
            wp_enqueue_style(
                'bpbm-medium-editor-css',
                plugins_url( 'assets/css/medium-editor' . $suffix . '.css', __FILE__ ),
                false,
                $this->version
            );
            return true;
        }
    
    }
    function BP_Better_Messages()
    {
        return BP_Better_Messages::instance();
    }
    
    function BP_Better_Messages_Init()
    {
        global  $wpdb ;
        $wpdb->__set( 'bpbm_threadsmeta', $wpdb->base_prefix . 'bpbm_threadsmeta' );
        
        if ( class_exists( 'BuddyPress' ) && bp_is_active( 'messages' ) ) {
            BP_Better_Messages();
        } else {
            
            if ( isset( $_GET['plugin'] ) && isset( $_GET['action'] ) && $_GET['plugin'] == 'buddypress/bp-loader.php' && $_GET['action'] == 'activate' || isset( $_GET['plugin'] ) && isset( $_GET['action'] ) && $_GET['plugin'] == 'buddyboss-platform/bp-loader.php' && $_GET['action'] == 'activate' ) {
                BP_Better_Messages();
            } else {
                require_once 'vendor/buddypress/functions.php';
                require_once 'vendor/buddypress/class-bp-messages-thread.php';
                require_once 'vendor/buddypress/class-bp-messages-message.php';
                require_once 'vendor/buddypress/class-bp-user-query.php';
                require_once 'vendor/buddypress/class-bp-suggestions.php';
                require_once 'vendor/buddypress/class-bp-members-suggestions.php';
                BP_Better_Messages();
            }
        
        }
    
    }
    
    add_action( 'plugins_loaded', 'BP_Better_Messages_Init', 20 );
    require_once 'inc/install.php';
    register_activation_hook( __FILE__, 'bp_better_messages_activation' );
    register_deactivation_hook( __FILE__, 'bp_better_messages_deactivation' );
    
    if ( !function_exists( 'bpbm_fs' ) ) {
        // Create a helper function for easy SDK access.
        function bpbm_fs()
        {
            global  $bbm_fs ;
            
            if ( !isset( $bbm_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/inc/freemius/start.php';
                $bbm_fs = fs_dynamic_init( array(
                    'id'             => '1557',
                    'slug'           => 'bp-better-messages',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_8af54172153e9907893f32a4706e2',
                    'is_premium'     => false,
                    'premium_suffix' => '',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'    => 'bp-better-messages',
                    'support' => false,
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $bbm_fs;
        }
        
        // Init Freemius.
        bpbm_fs();
        // Signal that SDK was initiated.
        do_action( 'bbm_fs_loaded' );
    }

}

/**
 * @todo KLEO theme testing & styles review
 * @todo Woffice theme testing & styles review
 * @todo SweetDate theme testing & styles review
 * @todo Olympus theme testing & styles review
 * @todo Gwangi theme testing & styles review
 * @todo Learndash integration
 * @todo WCFM integration
 * @todo user-pro integration
 * @todo push notifications
 */