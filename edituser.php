<?php
/************************/
/* code to edit an user */
/************************/

/*
 * application evironment
 */    
include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin())
{
    errorpage("You must be logged in to edit preferences");
    exit;
}
if(!havepriv("admin"))
{
    errorpage("You do not have sufficient rights to edit users");
    exit;
}
    $user = new User();
    $result = mysql_query("SELECT stamp, userid, username, realname, ".
			      "created, status, perm FROM user_list WHERE ".
			      "userid = '$userid'", $user->link);
    if(!$result)
    {
        errorpage("You must be logged in to edit preferences");
        exit;
    }


    list($user->stamp, $user->userid, $user->username, $user->realname, 
	 $user->created, $status, $perm) = mysql_fetch_row($result);

    global $ext_username, $ext_password1, $ext_password2, $ext_realname, $ext_email, $ext_hasadmin;

if($_POST)
{
    if ($ext_password == $ext_password2)
    {
        $passwd = $ext_password;
    }
    else if ($ext_password)
    {
        addmsg("The Passwords you entered did not match.", "red");
    }
    
    if ($user->update($userid, $passwd, $ext_realname, $ext_email))
    {
        addmsg("Preferences Updated", "green");
    }
    else
    {
        addmsg("There was a problem updating the user's info", "red");
    }
    if($ext_hasadmin=="on")
        $user->addpriv("admin");
    else
        $user->delpriv("admin");
}

{
    // show form


    apidb_header("Edit User");

    echo "<form method=post action='edituser.php'>\n";
    echo html_frame_start("Data for user ID $userid", "80%");
    echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");
    

   
    $ext_username = $user->lookup_username($userid);
    $ext_realname = $user->lookup_realname($userid);
    $ext_email    = $user->lookup_email($userid);
    if($user->checkpriv("admin"))
        $ext_hasadmin = 'checked="true"';
    else
        $ext_hasadmin = "";
      

?>
    <input type="hidden" name="userid" value="<?php echo $userid; ?>">
    <tr>
        <td> &nbsp; User Name </td>
	<td> <b> <?php echo $ext_username; ?> </b> </td>
    </tr>
    <tr>
	<td> &nbsp; Password </td>
	<td> <input type="password" name="ext_password"> </td>
    </tr>
    <tr>
        <td> &nbsp; Password (again) </td>
	<td> <input type="password" name="ext_password2"> </td>
    </tr>
    <tr>
	<td> &nbsp; Real Name </td>
	<td> <input type="text" name="ext_realname" value="<?php echo $ext_realname; ?>"> </td>
    </tr>
    <tr>
	<td> &nbsp; Email Address </td>
	<td> <input type="text" name="ext_email" value="<?php echo $ext_email; ?>"> </td>
    </tr>
    <tr>
	<td> &nbsp; Administrator </td>
	<td> <input type="checkbox" name="ext_hasadmin" "<?php echo $ext_hasadmin; ?>"> </td>

    </tr>
    <tr>
	<td colspan=2>&nbsp;</td>
    </tr>
<?

    echo html_table_end();
    echo html_frame_end();
    echo "<br> <div align=center> <input type=submit value='Update'> </div> <br>\n";
    echo "</form>\n";
}

apidb_footer();
?>
