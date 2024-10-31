<?php
namespace OST\Plugin;
/**
* Plugin Name: OmniLeads Scripts and Tags Manager
* Plugin URI: https://www.omnileads.nl/omnileads-scripts-tags-manager-wordpress-plugin/
* Description: Free plugin for configuring your website for all Google related services: Webmaster Tools website verification, Analytics, Remarketing and Tag Manager.
* Version: 1.3
* Author: danielmuldernl
* Author URI: https://www.omnileads.nl/
* License: GPL3
*/

   /* 
    * This plugin lets users ad their tracking scripts and tags 
    * and the plugin implements them correctly.
    * 
   */

    defined( 'ABSPATH' ) or die( 'This action is not allowed.' );
    
    if( !class_exists('OST_PluginController') ) {

        class OST_PluginController {
            
            static $active_tab;
            static $ost_plugin_enabled;
            static $ost_webmastertools_tag;
            static $ost_webmastertools_enabled;
            static $ost_analytics_tag;
            static $ost_analytics_enabled;
            static $ost_adwords_tag;
            static $ost_adwords_enabled;
            static $drm_tags;
            static $ost_dynremarketing_enabled;
            static $ost_remarketing_enabled;
            static $ost_remarketing_cid;
            static $ost_tagmanager_tag_head;
            static $ost_tagmanager_tag_body;
            static $ost_tagmanager_enabled;
            static $pageList=array();
            static $nosubmit;
            static $errMsg;
            static $ajax_nonce;

            
            function __construct() {
                self::ost_init_constants();
                self::ost_init_hooks();
                self::$active_tab = $_GET['tab'];
            } 
            
            /** 
            * Set constants at constructy
            */
            
            public static function ost_init_constants() {
                define('OST_SCRIPT_PATH', plugins_url( ' ', __FILE__ ) ); 
                define('OST_PLUGIN_BASENAME', plugin_basename( __FILE__ ));
                define('OST_TEXTDOMAIN', 'omnileads-scripts-and-tags-manager');
                define('OST_OPTIONS_PAGE', 'omnileads-scripts-and-tags-manager');
                define('OST_JS_URL', plugins_url().'/'.OST_OPTIONS_PAGE);
                define('OST_IMG_URL', plugins_url().'/'.OST_TEXTDOMAIN.'/images');
                define('OST_PLUGIN_PREFIX', 'ost');
                define('OST_PLUGIN_MENU', 'topmenu' );
                define('OST_OPTIONS_GROUP', 'ost_options' );
                define('OST_SETTINGS_SECTION', 'general_settings_section');
                define('OST_WEBMASTERTOOLS_SECTION', 'ost_webmastertools_section');
                define('OST_ANALYTICS_SECTION', 'ost_analytics_section');
                define('OST_ADWORDS_SECTION', 'ost_adwords_section');
                define('OST_DRM_SECTION', 'drm_tags');
                define('OST_TAGMANAGER_SECTION', 'ost_tagmanager_section');
            }
            
            /**
            *  Add action hooks actions at construct
            */            
            
            public static function ost_init_hooks(){
                self::$ost_plugin_enabled = get_option('ost_plugin_enabled'); // Check if plugin is turned on
                /* 
                 * Front-end hooks
                */
                if( self::$ost_plugin_enabled )
                {
                    // Tag Manager enabled check
                    self::$ost_tagmanager_enabled = get_option('ost_tagmanager_enabled');
                    if(self::$ost_tagmanager_enabled == '1'){ // add to head
                        add_action('wp_head', array(__CLASS__,'ost_add_tagmanager_head')); 
                    }
                    // Webmaster Tools enabled check
                    self::$ost_webmastertools_enabled = get_option('ost_webmastertools_enabled');
                    if(self::$ost_webmastertools_enabled == '1'){ // add to head
                        add_action('wp_head', array(__CLASS__,'ost_add_webmastertools')); 
                    }
                    // Analytics enabled check
                    self::$ost_analytics_enabled = get_option('ost_analytics_enabled');
                    if( self::$ost_analytics_enabled ){ // ad to head 
                        add_action('wp_head', array(__CLASS__,'ost_add_analytics')); 
                    }
                    // Adwords Remarketing and Tag Mannager enabled check
                    self::$ost_adwords_enabled = get_option('ost_adwords_enabled');
                    self::$ost_dynremarketing_enabled = get_option('ost_dynremarketing_enabled');
                    if(self::$ost_adwords_enabled || self::$ost_tagmanager_enabled || self::$ost_dynremarketing_enabled ){ 
                       add_action('wp_loaded', array(__CLASS__,'ost_wp_loaded')); // add to body
                    }       
                } 
                /* 
                 * Back-end (admin) hooks
                 */
                if(is_admin()){
                    // Load options in local object
                    self::$ost_webmastertools_enabled = get_option('ost_webmastertools_enabled');
                    self::$ost_analytics_enabled = get_option('ost_analytics_enabled');
                    self::$ost_adwords_enabled = get_option('ost_adwords_enabled');
                    self::$ost_tagmanager_enabled = get_option('ost_tagmanager_enabled');
                    self::$ost_dynremarketing_enabled = get_option('ost_dynremarketing_enabled');
                    add_action( 'wp_ajax_my_action_drmtags', array(__CLASS__, 'my_action_drmtags' ) ); // Admin API
                    add_action('admin_menu', array(__CLASS__, 'ost_create_menu')); 
                    // Legacy check at update version
                    if(!get_option('ost_version') == '10' ){
                        self::install();
                    }
                } 
            }
            
            /** 
            * Legacy check at update to alter config and options if needed
            */
            
            private static function install() {
                update_option('ost_version', '10');
                // Update tag storage options for version < 09
                if( get_option('ost_version') != '09' ){
                    self::$drm_tags = array();
                    update_option('drm_tags', self::$drm_tags);
                }
                // Update storage for new Tag Manager insert method and version < 10
                self::ost_init_constants();
                self::ost_register_settings();
                $ost_tagmanager_tag_body = wp_unslash(get_option('ost_tagmanager_tag'));
                update_option('ost_tagmanager_tag_body', $ost_tagmanager_tag_body);
            }
            
            /** 
            * Add webmaster tools tag to head 
            */
            
            public static function ost_add_tagmanager_head(){
                self::$ost_tagmanager_tag_head = wp_unslash(get_option('ost_tagmanager_tag_head'));
                if(self::$ost_tagmanager_tag_head  == '') return;
                echo self::$ost_tagmanager_tag_head.PHP_EOL;      
            }
                        
            /* Add webmaster tools tag to head */
            
            
            public static function ost_add_webmastertools(){
                self::$ost_webmastertools_tag = wp_unslash(get_option('ost_webmastertools_tag'));
                if(self::$ost_webmastertools_tag == '') return;
                echo self::$ost_webmastertools_tag.PHP_EOL;      
            }
                        
            /** 
            * Add Analytics tracking script to head 
            */
            
            public static function ost_add_analytics(){
                self::$ost_analytics_tag = wp_unslash(get_option('ost_analytics_tag'));
                if(self::$ost_analytics_tag == '') return;
                echo self::$ost_analytics_tag.PHP_EOL;     
            }
            
            /** 
            * Add Tag Manager and Remarketing scripts to body 
            */
                        
            public static function ost_add_body($contents){                
                
                // Append body after <body> open tag
                self::$ost_tagmanager_tag_body = wp_unslash(get_option('ost_tagmanager_tag_body'));
                if(self::$ost_tagmanager_tag_body != '' && self::$ost_tagmanager_enabled =='1'){
                    $pattern = '/<body[^>]+>/';  
                    preg_match($pattern, $contents, $matches);
                    $replace = $matches[0];     
                    $replace = $replace.PHP_EOL.self::$ost_tagmanager_tag_body;
                    $contents = preg_replace($pattern, $replace, $contents);
                }
                
                // Append before before </body> close tag
                self::$ost_adwords_tag = wp_unslash(get_option('ost_adwords_tag'));
                if(self::$ost_adwords_tag != '' && self::$ost_adwords_enabled == '1'){
                    $pattern = '/<\/body>/';  
                    preg_match($pattern, $contents, $matches);
                    $replace = $matches[0];     
                    $replace = self::$ost_adwords_tag.PHP_EOL.$replace;
                    $contents = preg_replace($pattern, $replace, $contents);
                }
                else{  // Eval dynamic remarketing tags 
                    
                    self::$ost_dynremarketing_enabled = wp_unslash(get_option('ost_dynremarketing_enabled'));
                    self::$ost_remarketing_enabled = wp_unslash(get_option('ost_remarketing_enabled'));
                    self::$ost_remarketing_cid = wp_unslash(get_option('ost_remarketing_cid'));
                    
                    // Append before before </body> close tag
                    if(self::$ost_dynremarketing_enabled  == '1')
                    {
                        if( is_page() ){ 
                            self::$drm_tags = get_option('drm_tags');
                            if(count(self::$drm_tags) < 1) return $contents;
                            // loop tags and set if page id
                            $current_page_id = get_the_ID ();
                            foreach(self::$drm_tags as $i => $tag)
                            {
                                if($tag['tag_page_id'] == $current_page_id ){
                                    $dyntag = TRUE;
                                    $script = wp_unslash($tag['tag_script']);
                                    $pattern = '/<\/body>/';  
                                    preg_match($pattern, $contents, $matches);
                                    $replace = $matches[0]; 
                                    $replace = $script.PHP_EOL.$replace;
                                    $contents = preg_replace($pattern, $replace, $contents);
                                    break;
                                }
                            }
                        } // endif page
                        
                        // if no dynamic tag found for current page: eval remarketing tag
                        if(!isset($dyntag) && self::$ost_remarketing_enabled && self::$ost_remarketing_cid != ''){
                            $script = self::ost_create_stattag(self::$ost_remarketing_cid);
                            $pattern = '/<\/body>/';  
                            preg_match($pattern, $contents, $matches);
                            $replace = $matches[0]; 
                            $replace = $script.PHP_EOL.$replace;
                            $contents = preg_replace($pattern, $replace, $contents);
                        }
                    
                    } // endif dynremarketing enabled
                    
                }   // endelse remarketing enabled
                
                return $contents; // altered output buffer html
                
            }
            
            
            /* Create static remarketing tag from template */
            
            
            static function ost_create_stattag($conversion_id){
                if($conversion_id == '') return;
                
                $tag = '<!-- Google Code for Remarketing Tag -->
                <!--------------------------------------------------
                Remarketing tags may not be associated with personally identifiable information or placed on pages related to sensitive categories. See more information and instructions on how to setup the tag on: http://google.com/ads/remarketingsetup
                --------------------------------------------------->
                <script type="text/javascript">
                /* <![CDATA[ */
                var google_conversion_id = '.$conversion_id.';
                var google_custom_params = window.google_tag_params;
                var google_remarketing_only = true;
                /* ]]> */
                </script>
                <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
                </script>
                <noscript>
                <div style="display:inline;">
                <img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/'
                        .$conversion_id.'/?value=0&amp;guid=ON&amp;script=0"/>
                </div>
                </noscript>';
                return $tag;
                
            }

            /** 
            * Hooks wp_loaded to ob output callback to append directly 
            * after body open and before body close tags
            */
            
            public function ost_wp_loaded() {
                self::$ost_plugin_enabled = get_option('ost_plugin_enabled');
                if(self::$ost_plugin_enabled && !is_admin()){
                    ob_start(array(__CLASS__, 'ost_add_body'));
                }
            }
            
            /////////////////////////////////////////////////////////////////// 
            // Admin area functions part starts here                         //
            ///////////////////////////////////////////////////////////////////
            
            /** 
            * Create submenu of Plugins or top menu item 
            */
            
            public static function ost_create_menu() {
                if(OST_PLUGIN_MENU == 'plugins_page'){
                    /* add to Plugins menu */
                    add_plugins_page(
                        'Scripts and tags',                 // Browser title
                        'Scripts and tags',                 // Menu text
                        'administrator',                    // User privileges
                        OST_OPTIONS_PAGE,                   // Page slug
                        array(__CLASS__, 'ost_options')     // Rendering function
                    );
                }else{                   
                    /* top level menu */
                    add_menu_page(
                        'Scripts & Tags', 
                        'Scripts & Tags', 
                        'manage_options', 
                        OST_TEXTDOMAIN, 
                        array(__CLASS__, 'ost_options'),
                        plugins_url( OST_TEXTDOMAIN.'/images/icon.png' )
                    );
                }
                add_action('admin_init', array(__CLASS__, 'ost_register_settings'), 0);
            }
            
            /** 
            * Register plugin WordPress settings
            */
            
            public static function ost_register_settings() {
				global $wp_version;
				// Check wp version to work with WordPress 4.7 and up and 4.6 and lower
				if ( $wp_version >= 4.7 ) {
					/** register wp settings WordPress 4.7 and up */
					$args = array('show_in_rest'=> '','type' =>'string','default' =>'',);
					register_setting( OST_SETTINGS_SECTION, 'ost_plugin_enabled', $args );
					register_setting( OST_SETTINGS_SECTION, 'ost_plugin_enabled', $args );
					register_setting( OST_SETTINGS_SECTION, 'ost_menu_plugins', $args );
					register_setting( OST_WEBMASTERTOOLS_SECTION, 'ost_webmastertools_tag', $args );
					register_setting( OST_WEBMASTERTOOLS_SECTION, 'ost_webmastertools_enabled', $args );
					register_setting( OST_ANALYTICS_SECTION, 'ost_analytics_tag', $args );
					register_setting( OST_ANALYTICS_SECTION, 'ost_analytics_enabled', $args );
					register_setting( OST_ADWORDS_SECTION, 'ost_adwords_tag', $args);
					register_setting( OST_ADWORDS_SECTION, 'ost_adwords_enabled', $args );
					register_setting( OST_ADWORDS_SECTION, 'ost_remarketing_enabled', $args );
					register_setting( OST_ADWORDS_SECTION, 'ost_remarketing_cid', $args );
					register_setting( OST_ADWORDS_SECTION, 'ost_dynremarketing_enabled', $args );
					register_setting( OST_TAGMANAGER_SECTION, 'ost_tagmanager_tag_head', $args );
					register_setting( OST_TAGMANAGER_SECTION, 'ost_tagmanager_tag_body', $args );
					register_setting( OST_TAGMANAGER_SECTION, 'ost_tagmanager_enabled', $args );
					register_setting( OST_SETTINGS_SECTION, 'ost_version', $args );
				}else {
					/** register wp settings WordPress 4.6 and lower */
					register_setting( OST_SETTINGS_SECTION, array(__CLASS__,'ost_plugin_enabled' ) ); 
					register_setting( OST_SETTINGS_SECTION, array(__CLASS__,'ost_plugin_enabled' ) ); 
					register_setting( OST_SETTINGS_SECTION, array(__CLASS__,'ost_menu_plugins' ) ); 
					register_setting( OST_WEBMASTERTOOLS_SECTION, array(__CLASS__,'ost_webmastertools_tag' ) ); 
					register_setting( OST_WEBMASTERTOOLS_SECTION, array(__CLASS__,'ost_webmastertools_enabled' ) ); 
					register_setting( OST_ANALYTICS_SECTION, array(__CLASS__,'ost_analytics_tag' ) ); 
					register_setting( OST_ANALYTICS_SECTION, array(__CLASS__,'ost_analytics_enabled' ) ); 
					register_setting( OST_ADWORDS_SECTION, array(__CLASS__,'ost_adwords_tag' ) ); 
					register_setting( OST_ADWORDS_SECTION, array(__CLASS__,'ost_adwords_enabled' ) ); 
					register_setting( OST_ADWORDS_SECTION, array(__CLASS__,'ost_remarketing_enabled' ) ); 
					register_setting( OST_ADWORDS_SECTION, array(__CLASS__,'ost_remarketing_cid' ) ); 
					register_setting( OST_ADWORDS_SECTION, array(__CLASS__,'ost_dynremarketing_enabled' ) ); 
					register_setting( OST_TAGMANAGER_SECTION, array(__CLASS__,'ost_tagmanager_tag_head' ) ); 
					register_setting( OST_TAGMANAGER_SECTION, array(__CLASS__,'ost_tagmanager_tag_body' ) ); 
					register_setting( OST_TAGMANAGER_SECTION, array(__CLASS__,'ost_tagmanager_enabled' ) ); 
					register_setting( OST_SETTINGS_SECTION, array( __CLASS__,'ost_version' ) ); 
				}
            }
            
            
            /**
            * Callback function for admin only api calls. 
            * Option page admin only API calls and access control:
            * user needs admin privileges or similar for access.
            */
            
            static function my_action_drmtags() {     
                // Security checks for back-end ajax calls
                if ( !current_user_can( 'manage_options' ) )  {
                    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
                }
                $nonce = $_POST['security'];
                if ( ! wp_verify_nonce( $nonce, 'sec-callback' ) ) {
                    wp_die(); // nonce not valid so die
                    return;
                } 
                /* 
                 * Switch api call category to determine operation 
                */
                switch ($_POST['category']) 
                {
                    case 'drm_tags': 
                            // return dynamic remarketing tags list
                            $tags = wp_unslash(get_option('drm_tags'));
                            foreach ($tags as $i => $tag) {
                                $tags[$i]['tag_script'] = wp_unslash($tag['tag_script']);
                                $tags[$i]['tag_script'] = esc_html($tag['tag_script']); 
                            }
                            if(!is_array($tags)){
                                $tags = array();
                            }
                            
                            $response = json_encode($tags);
                        break;    
                    case 'drm_remarketing_status': 
                            // check enabled/disabled
                            $enabled = get_option('ost_remarketing_enabled');
                            $response = json_encode($enabled);
                            
                        break;
                    case 'drm_remarketing_cid': 
                            // get remaketing id
                            $cid = get_option('ost_remarketing_cid');
                            $response = json_encode($cid);
                        
                        break; 
                    case 'drm_remarketing_save':  
                            // save tag settings and info
                            self::$ost_remarketing_enabled = $_POST['remarketing_enabled'];
                            update_option('ost_remarketing_enabled', self::$ost_remarketing_enabled);
                            self::$ost_remarketing_cid = ( $_POST['remarketing_cid'] != '' ? $_POST['remarketing_cid'] : '' );
                            update_option('ost_remarketing_cid', self::$ost_remarketing_cid);
                            if(self::$ost_remarketing_cid == '' &&  self::$ost_remarketing_enabled){
                                self::$ost_remarketing_enabled = '';
                                update_option('ost_remarketing_enabled', self::$ost_remarketing_enabled);
                                $response['error'] = "Warning remarketing tag not saved: conversion id can not be empty.";
                                $response = json_encode($response);
                                echo $response;
                                wp_die();   
                            }
                            $response = json_encode(self::$ost_remarketing_enabled);
                            
                        break;
                    case 'drm_delete':
                            // Delete dynamic remarketing tag
                            self::$drm_tags = get_option('drm_tags');
                            $tag_page_id = $_POST['tag_page_id'];
                            // loop tags and delete target tag
                            foreach(self::$drm_tags  as $key => $tag) {
                                if ($tag['tag_page_id'] == $tag_page_id) {
                                    unset(self::$drm_tags[$key]);
                                    update_option('drm_tags',  self::$drm_tags);
                                    $response = $tag['tag_page_id']; 
                                }
                            }
                            $response = ( $response = '' ? json_encode(0) : json_encode($response) );
                            
                        break;
                    default:
                        break;                    
                }
                
                echo $response;  // output response to browser
                wp_die();        // WordPress required for api
            }
            
            
            /* 
            * Process admin form posts and options page view 
            * Non api options page logic and access:
            * admin privileges or similar required.
            */
            
            public static function ost_options() {
                // Security checks post backs back-end settings
                if ( !current_user_can( 'manage_options' ) )  {
                    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
                }
                /* 
                 * Handle post submit settings form back-end options page
                */
                if($_SERVER['REQUEST_METHOD'] == 'POST')
                {       
                    switch ($_GET['tab']) 
                    {  
                        case 'webmastertools':
                                self::$active_tab = 'webmastertools';
                                self::$ost_webmastertools_tag =  $_POST['ost_webmastertools_tag']; 
                                if(self::$ost_webmastertools_tag == '' && $_POST['ost_webmastertools_enabled'] == '1'){
                                    update_option('ost_webmastertools_enabled',false);
                                    update_option('ost_webmastertools_tag','');
                                    self::$errMsg = 'Warning: website verification tag not enabled because can not be be empty.';
                                }else{
                                    update_option('ost_webmastertools_enabled',$_POST['ost_webmastertools_enabled'] );
                                    update_option('ost_webmastertools_tag',self::$ost_webmastertools_tag);
                                }
                            break;
                        case 'analytics':
                                self::$active_tab = 'analytics';
                                self::$ost_analytics_tag = $_POST['ost_analytics_tag']; 
                                if(self::$ost_analytics_tag == '' && $_POST['ost_analytics_enabled'] == '1'){
                                    update_option('ost_analytics_enabled',false);
                                    update_option('ost_analytics_tag','');
                                    self::$errMsg = 'Warning: Analytics tracking not enabled because can not be be empty.';
                                }else{
                                    update_option('ost_analytics_enabled',$_POST['ost_analytics_enabled']);
                                    update_option('ost_analytics_tag',self::$ost_analytics_tag);
                                }
                            break;
                        case 'adwords':
                                self::$active_tab = 'adwords';
                                self::$ost_adwords_tag = $_POST['ost_adwords_tag']; 
                                
                                if($_POST['action'] == 'savedrmtag'){
                                       // persist dynamic remarketing tag      
                                       self::$drm_tags = get_option('drm_tags');
                                       if(!is_array(self::$drm_tags)) self::$drm_tags = array();

                                       $tag['tag_script'] = urldecode($_POST['tag_script']);
                                       $tag['tag_script'] = $tag['tag_script'];
                                       $tag['tag_page_id'] = intval($_POST['tag_page_id']);
                                       
                                       // check form input values
                                       if($tag['tag_script']=='' || $tag['tag_page_id']==''){
                                           self::$errMsg = 'Warning tag not saved: can not except empty form values.';
                                       }
                                       else{    
                                            // get new tag id if page id not exists
                                            foreach(self::$drm_tags as $i => $elm){
                                                if($elm['tag_page_id'] == $tag['tag_page_id'] ){
                                                    self::$errMsg = 'Warning tag not saved: page already has tag.';
                                                    $error = 1;
                                                }
                                            }
                                            if(!isset($error)){
                                                // pop and persist options
                                                self::$drm_tags[] = $tag;
                                                update_option('drm_tags',  self::$drm_tags);
                                            }
                                            
                                       }
                                }
                                else{
                                        if(self::$ost_adwords_tag == '' && $_POST['ost_adwords_enabled'] == '1'){
                                            update_option('ost_adwords_enabled',false);
                                            update_option('ost_adwords_tag','');
                                            self::$errMsg = 'Warning: AdWords Remarketing tracking code not enabled because can not be be empty.';
                                        }else{
                                            update_option('ost_adwords_enabled',$_POST['ost_adwords_enabled']);
                                            update_option('ost_adwords_tag',self::$ost_adwords_tag);
                                        }
                                }
                                
                            break;
                        case 'tagmanager':
                                self::$active_tab = 'tagmanager';
                                self::$ost_tagmanager_tag_head = $_POST['ost_tagmanager_tag_head']; 
                                if(self::$ost_tagmanager_tag_head == '' && $_POST['ost_tagmanager_enabled'] == '1'){
                                    update_option('ost_tagmanager_enabled',false);
                                    update_option('ost_tagmanager_tag_head','');
                                    self::$errMsg = 'Warning: Google Tag Manager code not enabled because can not be be empty.';
                                }else{
                                    update_option('ost_tagmanager_enabled',$_POST['ost_tagmanager_enabled']);
                                    update_option('ost_tagmanager_tag_head',self::$ost_tagmanager_tag_head);
                                }
                                self::$ost_tagmanager_tag_body = $_POST['ost_tagmanager_tag_body']; 
                                if(self::$ost_tagmanager_tag_body == '' && $_POST['ost_tagmanager_enabled'] == '1'){
                                    update_option('ost_tagmanager_enabled',false);
                                    update_option('ost_tagmanager_tag_body','');
                                    self::$errMsg = 'Warning: Google Tag Manager code not enabled because can not be be empty.';
                                }else{
                                    update_option('ost_tagmanager_enabled',$_POST['ost_tagmanager_enabled']);
                                    update_option('ost_tagmanager_tag_body',self::$ost_tagmanager_tag_body);
                                }
                            break;
                        case 'settings':
                                self::$active_tab = 'settings';
                            
                                self::$ost_plugin_enabled = $_POST['ost_plugin_enabled']; 
                                if(self::$ost_plugin_enabled == false || self::$ost_plugin_enabled == '1'){
                                    update_option('ost_plugin_enabled',self::$ost_plugin_enabled);
                                }
                                
                                self::$ost_dynremarketing_enabled = $_POST['ost_dynremarketing_enabled']; 
                                if(self::$ost_dynremarketing_enabled == false || self::$ost_dynremarketing_enabled == '1'){
                                    update_option('ost_dynremarketing_enabled',self::$ost_dynremarketing_enabled);
                                }
                                
                                // disable static tag if dynamic on
                                if(self::$ost_dynremarketing_enabled == '1'){
                                    update_option('ost_adwords_enabled',false);
                                }
                                
                            break; 
                        default:
                            break;
                    }                       
                }  
                else{   
                        /* Set active tab for all GET request types */
                        switch ($_GET['tab'])
                        {
                            case 'webmastertools':
                                    self::$active_tab = 'webmastertools';
                                break;

                            case 'analytics':
                                    self::$active_tab = 'analytics';
                                break;

                            case 'adwords':
                                    self::$active_tab = 'adwords';
                                break;

                            case 'tagmanager':
                                    self::$active_tab = 'tagmanager';
                                break;

                            case 'settings':
                                    self::$active_tab = 'settings';
                                break;

                            default:
                                    self::$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
                                break;
                        }
                }
                
                // API calls from options page require this nonce
                self::$ajax_nonce = wp_create_nonce( "sec-callback" );
                
                /* include view file */
                include_once('tabs.ctp.php');
            }     
            
            ////////////////////////////////////////////////////////////////////
            //  generic function section                                      //
            ////////////////////////////////////////////////////////////////////     
            
            /**
            * Check if user is logged in with administrator rights
            */
            
            static function drm_is_admin_logged_in(){
                $userInfo = wp_get_current_user();
                if (in_array( 'administrator', (array) $userInfo->roles)){
                return true;
                }
                return false;
            }
            
            
            /** 
            * Dev only reset drm_tag option value 
            */
                        
            function resetDrmTags(){
                if(!self::drm_is_admin_logged_in() || OST_DEBUG != '1' ) {
                    echo json_encode('This action is not allowed.');
                    return false;
                }
                $drm_tag = array();
                update_option('drm_tags',  $drm_tag);
            }
            
            
            /** 
            * Create list of pages for create tag form dropdown 
            */
                        
            static function createPageList(){
                $pages = get_pages(); 
                foreach ( $pages as $page ) {
                    $tmp['ID'] = $page->ID;
                    $tmp['page_title'] = $page->post_title;
                    $tmp['page_link'] = get_page_link( $page->ID );
                    array_push(self::$pageList, $tmp );
                }
                return self::$pageList;
            }
            
            /** 
            * Generic function to get tag list of type x.
            */            
            
            static function getTags($type){
                if(empty($type)) return;
                switch ($type) {                    
                    case 'drm':
                            self::$drm_tags = get_option('drm_tags');
                            return self::$drm_tags;
                        break;
                    default:
                        break;
                   }
            }
            
            /** 
            * Uses WordPress enque en localize script functions to add scripts 
            * and localized scripts and ajaxurl object to hmtl head.
            */            
            
            static function gst_enqueue_script() {
                $file  = OST_PLUGIN_PREFIX.'.js';
                $url = OST_JS_URL.'/js/'.$file;
                wp_localize_script( 'ajax-script', 'ajax_object',
                array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
                wp_enqueue_script( 'gstjs', $url, false );
            }
            
            ////////////////////////////////////////////////////////////////////
            //  Create tabs section                                           //
            ////////////////////////////////////////////////////////////////////            
                
            function ost_general_settings_tab(){
                self::$nosubmit = true;
                $html = '<h2>Plugin General Settings</h2>
                        <p><input type="checkbox" id="ost_plugin_enabled" name="ost_plugin_enabled" value="1" '
                        .checked(1,get_option('ost_plugin_enabled'),false).'/>
                         <label for="ost_plugin_enabled">Activate setting to enable plugin</label></p>
                        <p><input type="checkbox" id="ost_dynremarketing_enabled" name="ost_dynremarketing_enabled" value="1" '
                        .checked(1,get_option('ost_dynremarketing_enabled'),false).'/>
                        <label for="ost_dynremarketing_enabled">Activate setting to use dynamic remarketing</label></p>';
                             
                        if(get_option('ost_webmastertools_enabled') == '1' && get_option('ost_plugin_enabled') == '1' ){
                            $status = 'enabled';
                            $img = OST_IMG_URL.'/on.png';
                        }else{
                            $status = 'not enabled';
                            $img = OST_IMG_URL.'/off.png';
                        }
                        $html .= '<p><img src="'.$img.'" alt="Webmaster Tools tag '.$status.'" style="vertical-align: middle;" />&nbsp;
                                 <label for="ost_tagmanager_enabled"><strong> Webmaster Tools verification tag '
                                 .$status.'</strong></label></p>';
                        if(get_option('ost_analytics_enabled') == '1' && get_option('ost_plugin_enabled') == '1' ){
                            $status = 'enabled';
                            $img = OST_IMG_URL.'/on.png';
                        }else{
                            $status = 'not enabled';
                            $img = OST_IMG_URL.'/off.png';
                        }
                        $html .= '<p><img src="'.$img.'" alt="Analytics tracking code '.$status.'" style="vertical-align: middle;" />
                                  &nbsp;<label for="ost_analytics_enabled"><strong> Analytics tracking code '
                                  .$status.'</strong></label></p>';  
                        if(get_option('ost_adwords_enabled') == '1' || get_option('ost_dynremarketing_enabled') == '1' ){
                             if(get_option('ost_plugin_enabled') == '1'){
                                $status = 'enabled';
                                $img = OST_IMG_URL.'/on.png';
                             }
                        }else{
                            $status = 'not enabled';
                            $img = OST_IMG_URL.'/off.png';
                        }
                        
                        if(get_option('ost_adwords_enabled') == '1' && get_option('ost_adwords_enabled') == '1'){
                            $status = 'enabled';
                            $img = OST_IMG_URL.'/on.png';
                            $html .= '<p><img src="'.$img.'" alt="AdWords remarketing '.$status.'" style="vertical-align: middle;" />
                                      &nbsp;<label for="ost_adwords_enabled"><strong> AdWords remarketing code '.$status.'</strong></label></p>';
                        }
                        else{
                            if(get_option('ost_dynremarketing_enabled') == '1' && get_option('ost_remarketing_enabled') == '1'){
                                $status = 'enabled';
                                $img = OST_IMG_URL.'/on.png';
                                $html .= '<p><img src="'.$img.'" alt="Dynamic remarketing '.$status.'" style="vertical-align: middle;" />
                                          &nbsp;<label for="ost_adwords_enabled"><strong> Dynamic remarketing code '.$status.'</strong></label></p>';
                            }
                            elseif(get_option('ost_dynremarketing_enabled') == '1' && count(get_option('drm_tags')) > 0 ){
                                $status = 'enabled';
                                $img = OST_IMG_URL.'/on.png';
                                $html .= '<p><img src="'.$img.'" alt="Dynamic remarketing '.$status.'" style="vertical-align: middle;" />
                                          &nbsp;<label for="ost_adwords_enabled"><strong> Dynamic remarketing code '.$status.'</strong></label></p>';
                            }
                            else{
                                $status = 'not enabled';
                                $img = OST_IMG_URL.'/off.png';
                                $html .= '<p><img src="'.$img.'" alt="AdWords remarketing '.$status.'" style="vertical-align: middle;" />
                                          &nbsp;<label for="ost_adwords_enabled"><strong> AdWords remarketing '.$status.'</strong></label></p>';
                            }
                        }
                        if(get_option('ost_tagmanager_enabled') == '1' && get_option('ost_plugin_enabled') == '1' ){
                            $status = 'enabled';
                            $img = OST_IMG_URL.'/on.png';
                        }else{
                            $status = 'not enabled';
                            $img = OST_IMG_URL.'/off.png';
                        }
                        $html .= '<p><img src="'.$img.'" alt="Google Tagmanager code '.$status.'" style="vertical-align: middle;" />
                                  &nbsp;<label for="ost_tagmanager_enabled"><strong> Google Tag Manager code '
                                  .$status.'</strong></label></p>';
                echo $html;
                submit_button();
            }
            
            
            function ost_webmastertools_tab(){
                $html = '<h2>Webmaster Tools / Search Console</h2>
                        <p><textarea id="ost_webmastertools_tag" name="ost_webmastertools_tag" cols="70" rows="2" placeholder="Your website verification tag (optional)" />'
                            .wp_unslash(get_option('ost_webmastertools_tag', '')).'</textarea>
                        <label for="ost_webmastertools_tag"></label></p>                
                        <p><input type="checkbox" id="ost_webmastertools_enabled" name="ost_webmastertools_enabled" value="1" '
                           .checked(1,get_option('ost_webmastertools_enabled'),false).'/>
                        <label for="ost_webmastertools_enabled">Enable this option to add website verification tag to website</label></p>';
                echo $html;
            }
            
            
            function ost_analytics_tab(){
                $html = '<h2>Analytics</h2>
                        <p><textarea id="ost_analytics_tag" name="ost_analytics_tag" cols="70" rows="12" placeholder="Your Analytics script (optional)" />'
                            .wp_unslash(get_option('ost_analytics_tag', '')).'</textarea>
                        <label for="ost_analytics_tag"></label></p>
                        <p><input type="checkbox" id="ost_analytics_enabled" name="ost_analytics_enabled" value="1" '
                           .checked(1,get_option('ost_analytics_enabled'),false).'/>
                        <label for="ost_analytics_enabled">Enable this option to add Analytics tracking code to website</label></p>';
                echo $html;
            }
            
            
            function ost_adwords_tab(){
                self::$ost_dynremarketing_enabled = get_option('ost_dynremarketing_enabled');
                if(self::$ost_dynremarketing_enabled) self::$nosubmit = true;
                if(self::$ost_dynremarketing_enabled){
                    if($_GET['action'] == 'newdrmtag'){
                        include_once('newdrmtag.ctp.php');
                    }else{
                        include_once('dynremarketing.ctp.php');
                    }
                }
                else{
                    $html = '<h2>Remarketing</h2>
                            <p><textarea id="ost_adwords_tag" name="ost_adwords_tag" cols="70" rows="12" 
                            placeholder="Your normal or statuc AdWords Remarketing script" />'
                            .wp_unslash(get_option('ost_adwords_tag', '')).'</textarea>
                            <label for="ost_adwords_tag"></label></p>
                            <p><input type="checkbox" id="ost_adwords_enabled" name="ost_adwords_enabled" value="1" '
                               .checked(1,get_option('ost_adwords_enabled'),false).'/>
                            <label for="ost_adwords_enabled">Enable this option to add AdWords Remarketing tracking code to website</label></p>';
                    echo $html;
                }
            }
            
            
            function ost_tagmanager_tab(){
                $html = '<h2>Tag Manager</h2>
                    
                        <label for="ost_tagmanager_tag_head"><h4>&lt;head&gt; script</h4></label>
                        <p><textarea id="ost_tagmanager_tag_head" name="ost_tagmanager_tag_head" placeholder="Your Tag Manager Head Script" />'
                            .wp_unslash(get_option('ost_tagmanager_tag_head', '')).'</textarea></p>
                    
                        <p><label for="ost_tagmanager_enabled"><h4>&lt;body&gt; script</h4></label>
                        <p><textarea id="ost_tagmanager_tag_body" name="ost_tagmanager_tag_body" placeholder="Your Tag Mamager Body Script" />'
                            .wp_unslash(get_option('ost_tagmanager_tag_body', '')).'</textarea></p>
                                
                        <label for="ost_tagmanager_enabled"></label></p>
                        <p><input type="checkbox" id="ost_tagmanager_enabled" name="ost_tagmanager_enabled" value="1" '
                           .checked(1,get_option('ost_tagmanager_enabled'),false).'/>
                        <label for="ost_tagmanager_enabled">Enable this option to add Google Tag Manager tracking code to website</label></p>';
                echo $html;
            }
            
            
            function ost_help_tab(){
                self::$nosubmit = true;
                $html = '<h2>Helpfull information and links</h2>';
                $html .= file_get_contents(ABSPATH.'wp-content/plugins/'.OST_TEXTDOMAIN.'/help.ctp.php');
                echo $html;
            }
            
            
        } // end class
        
        
        /* 
         * Create new instance of plugin 
        */
        
        
        $plugin = new OST_PluginController();
        
        
    } // end if
    
