<?php 
namespace OST\Plugin;
defined( 'ABSPATH' ) or die( 'This action is not allowed.' ); 
?>
<h2>Remarketing tag</h2>
  
<div id="container2" style="visibility: hidden; border:solid 1px #ccc; max-width: 616px; padding-top: 5px; padding-bottom: 5px; margin-bottom: 8px;" >
<table>
    <tr>
        <td style="width: 30px; padding-left: 4px;">
        <center>
            <input type="checkbox" id="ost_remarketing_enabled" name="ost_remarketing_enabled" value="1" />
        </center>
        </td>
        <td><input type="text" id="ost_remarketing_cid" name="ost_remarketing_cid" placeholder="conversion id" /></td>
        <td><label id="lbl_ost_remarketing_cid" for="ost_remarketing_cid">Enable / disable remarketing and conversion id</label></td>
    </tr>
</table>  
</div>
<span class="spacing last"></span>

<!-- WordPress 'wrap' container -->
<div id="tags-contain" class="ui-widget">
  <h2>Dynamic Remarketing Tags</h2>
  <table id="tags" class="ui-widget ui-widget-content">
    <thead>
      <tr class="ui-widget-header">
        <th>TAG / SCRIPT</th>
        <th>PAGE ID</th>
        <th class="nostyle"></th>
      </tr>
    </thead>
    <tbody>
        <td></td>
        <td></td>
        <td></td>
    </tbody>
  </table>
</div>

<!-- open dialog form -->
<button id="create-tag" class="button button-primary">Add Tag to Page</button>
<br /><br />
<p>
<p><strong><a href="https://www.omnileads.nl/omnileads-scripts-tags-manager-wordpress-plugin" title="https://www.omnileads.nl" target="_blank">help and instructions for adding and using dynamic remarketing</a> and items:</strong></p>
    &nbsp;- Lookup the remarketing conversions ID from AdWords<br />
    &nbsp;- How to us the dynamic remarketing conversions ID<br />
</p>

<style>
    label, input, select, p { display:block; margin: 6px; padding: 5px;}
    label { font-weight: bolder; }
    input.text { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:0; border:0; margin-top:25px; }
    h1 { font-size: 1.2em; margin: .6em 0; }
    div#tags-contain { max-width: 665px; width: 99%; margin: 10px 0; }
    div#tags-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
    div#tags-contain table td, div#tags-contain table th { border: 1px solid #eee; padding: 5px; 
                                                           text-align: left; max-width: 340px;  word-wrap:break-word-all;}
    div#tags-contain table th { background-color: #e5e5e5; border-color:  #ccc; }
    div#tags-contain table td { border-color:  #ccc; height: 100%; vertical-align: middle; padding: 4px; overflow-style:hidden; overflow: hidden;}
    div#tags-contain table .nostyle { background-color: #F1F1F1; border-color: #F1F1F1; }
    .ui-dialog .ui-state-error { padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.1; font-weight: bold; font-size: 1.1em; font-style: italic;}
    .edit {
       height: auto;
       vertical-align: auto;
       padding-top: 4px;
       padding-bottom: 4px;
    }
    .spacing .last {
       height: 20px;
    }
    #create-tag{
        margin-top: 6px;
        margin-left: 2px;
    }
    #tags {
        visibility: hidden;
    }
    
    textarea {
        width: 100%;
        margin: 0px;
        height: 80px;
        padding: 12px 20px;
        box-sizing: border-box;
        border: 2px solid #ccc;
        border-radius: 4px;
        background-color: #f8f8f8;
        resize: none;
    }
</style>

<script>
    
    jQuery.noConflict();
    (function( $ ) {
      $(function() {
        ///////////////////////////////////////////////
        // code using $ as alias to jQuery goes here //
        ///////////////////////////////////////////////        
        
        var dialog, form,
        tag_id = $("#tag_id"),
        tag_params = $("#tag_params"),
        tag_page_id = $("#tag_page_id"),
        allFields = $( [] ).add( tag_id ).add( tag_params ).add( tag_page_id ),
        tips = $( ".validateTips" );
        var remarketing_status;
        var remarketing_cid;
        
        
        $.when( remarketingCID(), remarketingStatus() ).done(function(){
            $("#container2").css("visibility", "visible");
            $("#ost_remarketing_enabled").prop( "checked", remarketing_enabled);
            if(remarketing_cid != false){
                remarketing_cid = $("#ost_remarketing_cid").val(remarketing_cid);
            }else{
                $("#ost_remarketing_cid").val('');
            }
        });
        
        /* Make table and visible after ajax call before doc ready */
                    
        $.when(createTable()).done(function(a){
            $("#tags").css("visibility", "visible");
        });
        
        /* Draw table rows with api call for contents */
        
        function createTable(){
            $( "#tags tbody" ).html('');
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_tags',
                'security': '<?php echo self::$ajax_nonce; ?>'
            }; 
            return jQuery.post(ajaxurl, data, function(response) { 
                if(response == ''){ return ; }  
                var response = JSON.parse(response); 
                for (var prop in response) {
                     $( "#tags tbody" ).append( "<tr>" +
                       "<td><textarea rows=\"4\" cols=\"66\" readonly>" + unescape(response[prop].tag_script) + "</textarea></td>" +
                       "<td><div class=\"edit\" id=\"tag_page_id\">" + response[prop].tag_page_id + "</div></td>" +
                         "<td class=\"nostyle\">" + 
                         "&nbsp; <span page_id=\""+response[prop].tag_page_id+"\" class=\"dashicons dashicons-trash\"></span>"  + 
                       "</td>" +
                     "</tr>" 
                   );    
                }
            }); // jquery post
        }
        
        /* Save new dyn remarketing tag api call */
        
        function saveTag(){
                    
            var tag_params = dialog.find( "#tag_params" ),
            tag_conversion_id = dialog.find( "#tag_conversion_id" ),
            tag_page_id = dialog.find( "#tag_page_id" ),
            response = '';
    
            if( tag_page_id.val() == '' ){
                alert('Can not save new tag. Please select a page first.')
                return false;
            }
            if( tag_conversion_id.val() == '' ){
                alert('Can not save new tag. Please fill in a conversion id first.')
                return false;
            }
            if( tag_params.val() == '' ){
                alert('Can not save new tag. Please fill in the tag parameters script first.');
                return false;
            }
            
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_save', 
                'security': '<?php echo self::$ajax_nonce; ?>',
                'tag_params': encodeURIComponent(tag_params.val()), 
                'tag_conversion_id': encodeURIComponent(tag_conversion_id.val()),
                'tag_page_id': encodeURIComponent(tag_page_id.val())
            };  
            jQuery.post(ajaxurl, data, function(response) { // api response 
                response = JSON.parse(response); 
                console.log(response);
                createTable();  
            });
            
            return response;
        }
        
        /* delete dyn remarketing tag ajax call */
        
        function deleteTag(page_id){
            var response = '';
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_delete', 
                'security': '<?php echo self::$ajax_nonce; ?>',
                'tag_page_id': page_id
            };  // api response (json)
            return jQuery.post(ajaxurl, data, function(response) { 
                response = JSON.parse(response); 
                console.log(response);
            }); // jquery post
        }
        

        /* Hook Add Tag butto to open modal with form */

        $( "#create-tag" ).on( "click", function() {
           event.preventDefault();
           location.assign('<?php echo get_admin_url().'admin.php?page='.OST_TEXTDOMAIN.'&tab=adwords&action=newdrmtag';?>');
        });

        $('#tags tbody').on("click",".dashicons-trash", function (event) {
              event.preventDefault();
              var page_id = $(this).attr("page_id");
              var result = confirm("Confirm deletion of Remarketing tag id " + page_id + "?" );
              if (result) { // delete the item
                  $.when(deleteTag(page_id)).done(function(a){
                     createTable(); // redraw table rows
                  });
              }
        });
        
        
        function remarketingCID(){
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_remarketing_cid',
                'security': '<?php echo self::$ajax_nonce; ?>'
            }; 
            return jQuery.post(ajaxurl, data, function(response) { 
                if(response == ''){ return ; }  
                var response = JSON.parse(response); 
                remarketing_cid = response.valueOf();
                console.log('remarketing cid: '+response.valueOf());
            }); // jquery post
        }
        
        
        function remarketingStatus(){
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_remarketing_status',
                'security': '<?php echo self::$ajax_nonce; ?>'
            }; 
            return jQuery.post(ajaxurl, data, function(response) { 
                if(response == ''){ return ; }  
                var response = JSON.parse(response); 
                remarketing_enabled = response.valueOf();
                console.log('remarketing status: '+response.valueOf());
            }); // jquery post
        }
        
        
        function remarketingSave(enabled, cid){
            var data = { 
                'action': 'my_action_drmtags', 
                'category': 'drm_remarketing_save',
                'security': '<?php echo self::$ajax_nonce; ?>',
                'remarketing_enabled': enabled,
                'remarketing_cid': cid
            }; 
            return jQuery.post(ajaxurl, data, function(response) { 
                if(response == ''){ return ; }  
                var response = JSON.parse(response); 
                if(response['error'] != undefined ){
                    var out = response['error'].valueOf();
                }else{
                    var out = response.valueOf();
                }
                remarketing_enabled = response.valueOf();
                console.log('remarketing saved: '+out);
            }); // jquery post
        }
        
        $("#ost_remarketing_enabled").on('change', function() {
            
            var status = $("#ost_remarketing_enabled").prop( "checked");
            
            if(status === false){
                $(this).val('');
            }else{
                $(this).val('1');
            }
            
            $("#lbl_ost_remarketing_cid").html('Saving remarketing status '+status+' and cid...');
            $.when( remarketingSave( $(this).val(), $("#ost_remarketing_cid").val() ) ).done(function(){
                $("#lbl_ost_remarketing_cid").html('Remarketing status and id saved.');
                setTimeout(function(){
                    $("#lbl_ost_remarketing_cid").html('Enable / disable remarketing and conversion id');
                }, 2000);
            });
            
        });
        
        $("#ost_remarketing_cid").on('keyup', function() {
            
             setTimeout(function(){
                }, 1000);
            
            var status = $("#ost_remarketing_enabled").prop( "checked");
            if(status === false){
                $("#ost_remarketing_enabled").val('');
                status = '';
            }else{
                status == '1';
                $("#ost_remarketing_enabled").val('1');
            }
            
            $.when( remarketingSave( status, $("#ost_remarketing_cid").val()) ).done(function(){
                $("#lbl_ost_remarketing_cid").html('conversion id saved');
                setTimeout(function(){
                    $("#lbl_ost_remarketing_cid").html('Enable / disable remarketing and conversion id');
                }, 2000);
            });
            
        });
        
     // -----
    });
    ///////////////////////////////////////////////
    // code using jQuery and no alias goes here  //
    ///////////////////////////////////////////////     
    
    })(jQuery);
</script>
