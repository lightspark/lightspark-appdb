<?php
/**********************/
/* preferences editor */
/**********************/

/*
 * application environment
 */     
include("path.php");
include(BASE."include/"."incl.php");

if(!loggedin())
{
    errorpage("You must be logged in to edit preferences");
    exit;
}

// we come from the administration to edit an user
if(havepriv("admin") && 
   is_numeric($_REQUEST['userId']) &&
   is_numeric($_REQUEST['iLimit']) &&
   in_array($_REQUEST['sOrderBy'],array("email","realname","created"))
) 
{
    $iUserId = $_REQUEST['userId'];
} else
{
    $iUserId = $_SESSION['current']->userid;
}


function build_prefs_list()
{
    $result = query_appdb("SELECT * FROM prefs_list ORDER BY id");
    while($result && $r = mysql_fetch_object($result))
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
    global $iUserId;
    $user = new User();

    $ext_realname = $user->lookup_realname($iUserId);
    $ext_email = $user->lookup_email($iUserId);
    $CVSrelease = $user->lookup_CVSrelease($iUserId);
      
    include(BASE."include/"."form_edit.php");

    echo "<tr><td>&nbsp; Wine version </td><td>";
    make_bugzilla_version_list("CVSrelease", $CVSrelease);
    echo "</td></tr>";
}

if($_POST)
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
    if ($user->update($iUserId, $str_passwd, $_REQUEST['ext_realname'], $_REQUEST['ext_email'], $_REQUEST['CVSrelease']))
    {
        addmsg("Preferences Updated", "green");

        // we were managing an user, let's go back to the admin.
        if($iUserId == $_REQUEST['userId'])
        {
            redirect(BASE."admin/adminUsersEdit.php?userId=".$iUserId."&sSearch=".$_REQUEST['sSearch']."&iLimit=".$_REQUEST['iLimit']."&sOrderBy=".$_REQUEST['sOrderBy']."&sSubmit=true");
        }
    }
    else
    {
        addmsg("There was a problem updating your userinfo", "red");
    }
}

apidb_header("User Preferences");

echo "<form method=\"post\" action=\"preferences.php\">\n";

// if we manage another user we give the parameters to go back to the admin
if($iUserId == $_REQUEST['userId'])
{
    echo "<input type=\"hidden\" name=\"iLimit\" value=\"".$_REQUEST['iLimit']."\">\n";
    echo "<input type=\"hidden\" name=\"sOrderBy\" value=\"".$_REQUEST['sOrderBy']."\">\n";
    echo "<input type=\"hidden\" name=\"sSearch\" value=\"".addslashes($_REQUEST['sSearch'])."\">\n";
    echo "<input type=\"hidden\" name=\"userId\" value=\"".$_REQUEST['userId']."\">\n";
}

echo html_frame_start("Preferences for ".lookupRealName($iUserId), "80%");
echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");

show_user_fields();

// if we don't manage another user
if($iUserId != $_REQUEST['userId']) build_prefs_list();

echo html_table_end();
echo html_frame_end();
echo "<br /> <div align=center> <input type=\"submit\" value=\"Update\" /> </div> <br />\n";
echo "</form>\n";


apidb_footer();
?>
