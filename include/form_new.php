<?

/*
 * New Account Form
 *
 */


echo '<form method="post" action="account.php">',"\n";
echo html_frame_start("Create New Application DB Account","400","",0)

?>

<!-- start of new account form -->
    <table border=0 width="100%" cellspacing=0 cellpadding=20>
      <tr>
	<td class=color1> User Name </td>
	<td class=color0> <input type="text" name="ext_username" value='<?=$ext_username?>'> </td>
      </tr>
      <tr>
	<td class=color1> Password </td>
	<td class=color0> <input type="password" name="ext_password"> </td>
      </tr>
      <tr>
	<td class=color1> Password (again) </td>
	<td class=color0> <input type="password" name="ext_password2"> </td>
      </tr>
      <tr>
	<td class=color1> Real Name </td>
	<td class=color0> <input type="text" name="ext_realname" value='<?=$ext_realname?>'> </td>
      </tr>
      <tr>
	<td class=color1> Email Address </td>
	<td class=color0> <input type="text" name="ext_email" value='<?=$ext_email?>'> </td>
      </tr>
      
      <tr>
	<td colspan=2 align=center class=color3>
	  <input type="submit" name="create" value=" Create Account " class=button>
	</td>
      </tr>    
  </table>
<!-- end of new account form -->

<?

echo html_frame_end("&nbsp;");
echo '<input type="hidden" name="cmd" value="do_new">',"\n";
echo '</form>',"\n";

echo p(),p(),p();

?>
