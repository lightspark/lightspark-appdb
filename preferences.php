<?
    
include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin())
{
    errorpage("You must be logged in to edit preferences");
    exit;
}

function build_prefs_list()
{
    global $current;

    opendb();

    $result = mysql_query("SELECT * FROM prefs_list ORDER BY id");
    while($r = mysql_fetch_object($result))
	{
	    //skip admin options
	    //TODO: add a field to prefs_list to flag the user level for the pref
	    if(!havepriv("admin"))
		{
		    if($r->name == "query:mode")
			continue;
		    if($r->name == "sidebar")
			continue;
		    if($r->name == "window:query")
			continue;
		    if($r->name == "query:hide_header")
			continue;
		    if($r->name == "query:hide_sidebar")
			continue;
		    if($r->name == "debug")
			continue;
		}
		
	    $input = html_select("pref_$r->name", explode('|', $r->value_list), 
				 $current->getpref($r->name, $r->def_value));
	    echo html_tr(array("&nbsp; $r->description", $input));
	}
}

function show_user_fields()
{
	global $current;
	$user = new User();
	
	$ext_username = $current->username;
	$ext_realname = $user->lookup_realname($current->userid);
	$ext_email = $user->lookup_email($current->userid);
	
	include(BASE."include/"."form_edit.php");
}

if($HTTP_POST_VARS)
{
    global $ext_username, $ext_password1, $ext_password2, $ext_realname, $ext_email;
    global $current;
    
    $user = new User();
    
    while(list($key, $value) = each($HTTP_POST_VARS))
	{
	    if(!ereg("^pref_(.+)$", $key, $arr))
		continue;
	    $current->setpref($arr[1], $value);
	}
    
    if ($ext_password == $ext_password2)
    {
        $passwd = $ext_password;
    }
    else if ($ext_password)
    {
        addmsg("The Passwords you entered did not match.", "red");
    }
    
    if ($user->update($current->userid, $passwd, $ext_realname, $ext_email))
    {
        addmsg("Preferences Updated", "green");
    }
    else
    {
        addmsg("There was a problem updating your userinfo", "red");
    }
}

apidb_header("User Preferences");

echo "<form method=post action='preferences.php'>\n";
echo html_frame_start("Preferences for $current->username", "80%");
echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");

show_user_fields();
build_prefs_list();

echo html_table_end();
echo html_frame_end();
echo "<br> <div align=center> <input type=submit value='Update'> </div> <br>\n";
echo "</form>\n";


apidb_footer();
?>
