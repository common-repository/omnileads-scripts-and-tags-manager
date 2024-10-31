<h2>Add dynamic remarketing tag to page</h2>
<?php if(isset(self::$active_tab) && self::$active_tab != '') $tab = '&tab='.self::$active_tab; ?>
    <form method="post" action="<?php echo get_admin_url().'admin.php?page='.OST_TEXTDOMAIN.$tab; ?>&action=savedrmtag">
    <input type="hidden" name="action" value="savedrmtag">
    <table class="form-table" style="max-width: 600px;">
        <tr valign="top">
        <tr>
            <td style="width: 24%; max-width: 160px;"><label for="tag_script">Tag / Script</label></td>
            <td style="width: 76%; max-width: 500px;">
                <textarea name="tag_script" rows="6"></textarea>
            </td>
        </tr>
        <tr>
            <td style="width: 24%; max-width: 160px;"><label for="tag_page_id">Page to add tag to</label></td>
            <td style="width: 75%; max-width: 500px;">  
                <select name="tag_page_id" id="tag_page_id">
                  <?php 
                        $list = self::createPageList();
                        if($list){ ?>
                          <option value="">-- select page --</option>
                  <?php } ?>
                  <?php foreach($list as $listInfo){?>
                            <option value="<?php echo $listInfo['ID']?>"><?php echo $listInfo['page_title']; ?></option>
                  <?php }?>
                </select>
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
</form>
