<?php
/*******************************/
/* preferences and user editor */
/*******************************/

/*
 * application environment
 */     
include("path.php");
include(BASE."include/"."incl.php");

$aClean = array(); //array of filtered user input

$aClean['userId'] = makeSafe($REQUEST['userId']);
$aClean['iLimit'] = makeSafe($REQUEST['iLimit']);
$aClean['sOrderBy'] = makeSafe($REQUEST['sOrderBy']);
$aClean['ext_password'] = makeSafe($REQUEST['ext_password']);
$aClean['ext_password2'] = makeSafe($REQUEST['ext_password2']);
$aClean['ext_email'] = makeSafe($REQUEST['ext_email']);
$aClean['ext_realname'] = makeSafe($REQUEST['ext_realname']);
$aClean['CVSrelease'] = makeSafe($REQUEST['CVSrelease']);
$aClean['ext_hasadmin'] = makeSafe($POST['ext_hasadmin']); 

/* filter all of the preferences */
while(list($key, $value) = each($_REQUEST))
{
    if(ereg("^pref_(.+)$", $key, $arr))
        $aClean[$key] = makeSafe($value);
}




if(!$_SESSION['current']->isLoggedIn())
{
    errorpage("You must be logged in to edit preferences");
    exit;
}

// we come from the administration to edit an user
if($_SESSION['current']->hasPriv("admin") && 
   is_numeric($aClean['userId']) &&
   is_numeric($aClean['iLimit']) &&
   in_array($aClean['sOrderBy'],array("email","realname","created"))
) 
{
    $oUser = new User($aClean['userId']);
} else
{
    $oUser = &$_SESSION['current'];
}


function build_prefs_list()
{
    global $oUser;
    $result = query_appdb("SELECT * FROM prefs_list ORDER BY id");
    while($result && $r = mysql_fetch_object($result))
    {
            //skip admin options
            //TODO: add a field to prefs_list to flag the user level for the pref
            if(!$_SESSION['current']->hasPriv("admin"))
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
                                 $oUser->getpref($r->name, $r->def_value));
            echo html_tr(array("&nbsp; $r->description", $input));
    }
}

function show_user_fields()
{
    global $oUser;

    $ext_realname = $oUser->sRealname;
    $ext_email = $oUser->sEmail;
    $CVSrelease = $oUser->sWineRelease;
    if($oUser->hasPriv("admin"))
        $ext_hasadmin = 'checked="true"';
    else
        $ext_hasadmin = "";
    
    include(BASE."include/form_edit.php");

    echo "<tr><td>&nbsp; Wine version </td><td>";
    make_bugzilla_version_list("CVSrelease", $CVSrelease);
    echo "</td></tr>";
}

if($_POST)
{   
    while(list($key, $value) = each($aClean))
    {
        /* if a parameter lacks 'pref_' at its head it isn't a */
        /* preference so skip over processing it */
        if(!ereg("^pref_(.+)$", $key, $arr))
            continue;
        $oUser->setPref($arr[1], $value);
    }
    
    if ($aClean['ext_password'] == $aClean['ext_password2'])
    {
        $str_passwd = $aClean['ext_password'];
    }
    else if ($aClean['ext_password'])
    {
        addmsg("The Passwords you entered did not match.", "red");
    }
    if ($oUser->update($aClean['ext_email'], $str_passwd, $aClean['ext_realname'], $aClean['CVSrelease']))
    {
        addmsg("Preferences Updated", "green");
        // we were managing an user, let's go back to the admin after updating tha admin status
        if($oUser->iUserId == $aClean['userId'] && $_SESSION['current']->hasPriv("admin"))
        {
            if($aClean['ext_hasadmin']=="on") 
                $oUser->addPriv("admin");
            else 
                $oUser->delPriv("admin");
            redirect(BASE."admin/adminUsers.php?userId=".$oUser->iUserId."&sSearch=".$aClean['sSearch']."&iLimit=".$aClean['iLimit']."&sOrderBy=".$aClean['sOrderBy']."&sSubmit=true");
        }
    }
    else
    {
        addmsg("There was a problem updating your user info", "red");
    }
}

apidb_header("User Preferences");

echo "<form method=\"post\" action=\"preferences.php\">\n";

// if we manage another user we give the parameters to go back to the admin
if($oUser->iUserId == $aClean['userId'])
{
    echo "<input type=\"hidden\" name=\"iLimit\" value=\"".$aClean['iLimit']."\">\n";
    echo "<input type=\"hidden\" name=\"sOrderBy\" value=\"".$aClean['sOrderBy']."\">\n";
    echo "<input type=\"hidden\" name=\"sSearch\" value=\"".$aClean['sSearch']."\">\n";
    echo "<input type=\"hidden\" name=\"userId\" value=\"".$aClean['userId']."\">\n";
}

echo html_frame_start("Preferences for ".$oUser->sRealname, "80%");
echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");

show_user_fields();

// if we don't manage another user
if($oUser->iUserId != $aClean['userId']) build_prefs_list();

echo html_table_end();
echo html_frame_end();
echo "<br /> <div align=center> <input type=\"submit\" value=\"Update\" /> </div> <br />\n";
echo "</form>\n";

apidb_footer();
?>
