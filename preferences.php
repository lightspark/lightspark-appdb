<?php
    
include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin())
{
    errorpage("You must be logged in to edit preferences");
    exit;
}

function build_prefs_list()
{
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
                                 $_SESSION['current']->getpref($r->name, $r->def_value));
            echo html_tr(array("&nbsp; $r->description", $input));
        }
}

function show_user_fields()
{
        
        $user = new User();
        
        $ext_username = $_SESSION['current']->username;
        $ext_realname = $user->lookup_realname($_SESSION['current']->userid);
        $ext_email = $user->lookup_email($_SESSION['current']->userid);
        
        include(BASE."include/"."form_edit.php");
        $version = "unspecified";
        echo "<tr><td>&nbsp; wine version </td><td>";
        make_bugzilla_version_list("version", $version);
        echo "</td></tr>";

}

if($_REQUEST)
{   
    $user = new User();
    
    while(list($key, $value) = each($_REQUEST))
        {
            if(!ereg("^pref_(.+)$", $key, $arr))
                continue;
            $_SESSION['current']->setpref($arr[1], $value);
        }
    
    if ($_REQUEST['ext_password'] == $_REQUEST['ext_password2'])
    {
        $str_passwd = $_REQUEST['ext_password'];
    }
    else if ($_REQUEST['ext_password'])
    {
        addmsg("The Passwords you entered did not match.", "red");
    }
    
    if ($user->update($_SESSION['current']->userid, $str_passwd, $_REQUEST['ext_realname'], $_REQUEST['ext_email']))
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
echo html_frame_start("Preferences for ".$_SESSION['current']->username, "80%");
echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");

show_user_fields();
build_prefs_list();

echo html_table_end();
echo html_frame_end();
echo "<br /> <div align=center> <input type=submit value='Update'> </div> <br />\n";
echo "</form>\n";


apidb_footer();
?>
