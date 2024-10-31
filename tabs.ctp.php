<?php 
namespace OST\Plugin;
defined( 'ABSPATH' ) or die( 'This action is not allowed.' ); 
?>
<!-- WordPress 'wrap' container -->
<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>Manage your Scripts for Google Services</h2><br />
    <?php settings_errors(); ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=settings" 
           class="nav-tab <?php echo self::$active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
        Settings</a>
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=webmastertools" 
           class="nav-tab <?php echo self::$active_tab == 'webmastertools' ? 'nav-tab-active' : ''; ?>">
        Webmaster Tools</a>
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=analytics" 
           class="nav-tab <?php echo self::$active_tab == 'analytics' ? 'nav-tab-active' : ''; ?>">
        Google Analytics</a>
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=adwords" 
           class="nav-tab <?php echo self::$active_tab == 'adwords' ? 'nav-tab-active' : ''; ?>">
            <?php echo self::$ost_dynremarketing_enabled == '1' ? 'Dynamic Remarketing' : 'AdWords Remarketing'; ?>
        </a>
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=tagmanager" 
           class="nav-tab <?php echo self::$active_tab == 'tagmanager' ? 'nav-tab-active' : ''; ?>">
        Tag Manager</a>        
        <a href="?page=<?php echo OST_TEXTDOMAIN; ?>&tab=help" 
           class="nav-tab <?php echo self::$active_tab == 'help' ? 'nav-tab-active' : ''; ?>">
        Help</a>        
    </h2>
    <p>
    <?php if(isset(self::$active_tab) && self::$active_tab != '') $tab = '&tab='.self::$active_tab; ?>
    <form method="post" action="<?php echo get_admin_url().'admin.php?page='.OST_TEXTDOMAIN.$tab; ?>">
        <?php 
        switch (self::$active_tab) {
            case 'settings':
                    self::ost_general_settings_tab();
                break;            
            case 'webmastertools':
                    self::ost_webmastertools_tab();
                break;
            case 'analytics':
                    self::ost_analytics_tab();
                break;
            case 'adwords':
                    self::ost_adwords_tab();
                break;
            case 'tagmanager':
                    self::ost_tagmanager_tab();
                break;
            case 'help':
                    self::ost_help_tab();
                break;
            default:
                break;
        } 
        ?>
    </p>
    <?php 
        // set self $nosubmit to true to hide submit button
        if(!self::$nosubmit){
            submit_button();
        }
        // out error message for post form if set
        if(isset(self::$errMsg) && self::$errMsg != ''){
            echo '<p style="background-color: #EFEFEF; color: red;">'.self::$errMsg.'</p>';
        } 
    ?>
</div><!-- /.wrap -->

<script>
if (window.jQuery) { 
   /* Plugin definition setTextAreaSize */
  (function( jQuery ) { 
      var row = {};    // std row obj
      row.height = 22; // std row height
        jQuery.fn.loadPlugin = function(cols){
        jQuery(this).css('max-width','688px');
        jQuery(this).css('width','100%');
        jQuery(this).css('max-height','350px');
        jQuery(this).css('width',Math.max(50,this.scrollWidth)+'px');
        jQuery(this).css('height',Math.max(50,this.scrollHeight)+'px');
        // size textarea depending on active tab
        this.name = this.attr('name');
        switch(this.name)
        {
            case 'ost_webmastertools_tag':
                jQuery(this).css('height',(2*row.height));
                break;
            case 'ost_analytics_tag':
                jQuery(this).css('height',(9*row.height));
                break;
            case 'ost_adwords_tag':
                jQuery(this).css('height',(11*row.height));
                break;
            case 'ost_tagmanager_tag_head':
                jQuery(this).css('height',(7*row.height));
                jQuery( "#ost_tagmanager_tag_body" ).css('height',(5*row.height));
                break;
            default:
                break;
        }
        return jQuery(this);
     }; 
   //plugin
  }(jQuery)); 
  /* 
   * Load plugin to set textarea size 
  */ 
  jQuery( "textarea" ).loadPlugin();     
} 
</script>




