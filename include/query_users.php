<?php
/*************/
/* User List */
/*************/

echo html_frame_start("List Users","400","",0)
?>

<!-- start of users query -->

      <form ACTION="<?php echo $apidb_root; ?>stdquery.php" METHOD="get">
	
      <table width="100%" border=0 cellpadding=0 cellspacing=0>
	
      <tr>
          <td class=color1>Pattern</td>
          <td><input TYPE="TEXT" NAME="searchfor"><br /><small>(leave blank to match all)</small></td>
      </tr>
        
      <tr>
          <td class=color1>Entries Per Page</td>
          <td>
              <select NAME="linesPerPage">
                  <option>100</option>
                  <option>200</option>
                  <option>500</option>
                  <option>ALL</option>
              </select>
          </td>
      </tr>
	
      <tr>
          <td colspan=2 class=color3 align=center><input TYPE="SUBMIT" VALUE="List Users" class=button></td>
      </tr>
	
  </table>
	
  <input TYPE="HIDDEN" NAME="orderby" VALUE="userid">
  <input TYPE="HIDDEN" NAME="searchwhat" VALUE="user_list.username">
  <input TYPE="HIDDEN" NAME="fields[]" VALUE="user_list.userid">
  <input TYPE="HIDDEN" NAME="fields[]" VALUE="user_list.username">
  <input TYPE="HIDDEN" NAME="fields[]" VALUE="user_list.email">
  <input TYPE="HIDDEN" NAME="fields[]" VALUE="user_list.realname">
  <input TYPE="HIDDEN" NAME="fields[]" VALUE="user_list.created">
  </form>

<!-- end of users query -->

<?php

echo html_frame_end();

echo p(),p(),p();

?>
