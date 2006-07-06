<?php
/**
 * User's role and preferences editor.
 *
 * Optional parameters:
 *  - iUserId, user identifier (when an administrator edits another user)
 *  - iLimit
 *  - sOrderBy
 *  - sUserPassword, new password
 *  - sUserPassword2, new password confirmation
 *  - sUserEmail, e-mail address
 *  - sUserRealname, user's real name
 *  - sWineRelease, user's Wine release
 *  - sHasAdmin, "on" if user is an administrator
 * 
 * TODO:
 *  - rename sHasAdmin with bIsAdmin
 *  - document iLimit and sOrderBy
 *  - replace sOrderBy with iOrderBy and use constants for each accepted value
 *  - add a field to prefs_list to flag the user level for the pref
 *  - move and rename functions in their respective modules
 */

// application environment
include("path.php");
include(BASE."include/incl.php");

$aClean = array(); //array of filtered user input

$aClean['iUserId'] = makeSafe($_REQUEST['iUserId']);
$aClean['iLimit'] = makeSafe($_REQUEST['iLimit']);
$aClean['sOrderBy'] = makeSafe($_REQUEST['sOrderBy']);
$aClean['sUserPassword'] = makeSafe($_REQUEST['sUserPassword']);
$aClean['sUserPassword2'] = makeSafe($_REQUEST['sUserPassword2']);
$aClean['sUserEmail'] = makeSafe($_REQUEST['sUserEmail']);
$aClean['sUserRealname'] = makeSafe($_REQUEST['sUserRealname']);
$aClean['sWineRelease'] = makeSafe($_REQUEST['sWineRelease']);
$aClean['sHasAdmin'] = makeSafe($_POST['sHasAdmin']); 

/* filter all of the preferences */
while(list($key, $value) = each($_REQUEST))
{
    if(ereg("^pref_(.+)$", $key, $arr))
        $aClean[$key] = makeSafe($value);
}




if(!$_SESSION['current']->isLoggedIn())
    util_show_error_page("You must be logged in to edit preferences");

// we come from the administration to edit an user
if($_SESSION['current']->hasPriv("admin") && 
   is_numeric($aClean['iUserId']) &&
   is_numeric($aClean['iLimit']) &&
   in_array($aClean['sOrderBy'],array("email","realname","created"))
) 
{
    $oUser = new User($aClean['iUserId']);
} else
{
    $oUser = &$_SESSION['current'];
}


function build_prefs_list()
{
    global $oUser;
    $hResult = query_parameters("SELECT * FROM prefs_list ORDER BY id");
    while($hResult && $r = mysql_fetch_object($hResult))
    {
            // skip admin options
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

    $sUserRealname = $oUser->sRealname;
    $sUserEmail = $oUser->sEmail;
    $sWineRelease = $oUser->sWineRelease;
    if($oUser->hasPriv("admin"))
        $sHasAdmin = 'checked="true"';
    else
        $sHasAdmin = "";
    
    include(BASE."include/form_edit.php");

    echo "<tr><td>&nbsp; Wine version </td><td>";
    make_bugzilla_version_list("sWineRelease", $sWineRelease);
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
    
    /* make sure the user enters the same password twice */
    if ($aClean['sUserPassword'] == $aClean['sUserPassword2'])
    {
        $str_passwd = $aClean['sUserPassword'];
    }
    else if ($aClean['sUserPassword'])
    {
        addmsg("The Passwords you entered did not match.", "red");
    }

    /* update user data fields */
    $oUser->sEmail = $aClean['sUserEmail'];
    $oUser->sRealname = $aClean['sUserRealname'];
    $oUser->sWineRelease = $aClean['sWineRelease'];

    /* if the password was empty in both cases then skip updating the users password */
    if($str_passwd != "")
    {
        if(!$oUser->update_password($str_passwd))
            addmsg("Failed to update password", "red");
    }

    if ($oUser->update() == SUCCESS)
    {
        addmsg("Preferences Updated", "green");
        // we were managing an user, let's go back to the admin after updating tha admin status
        if($oUser->iUserId == $aClean['iUserId'] && $_SESSION['current']->hasPriv("admin"))
        {
            if($aClean['sHasAdmin']=="on") 
                $oUser->addPriv("admin");
            else 
                $oUser->delPriv("admin");
            redirect(BASE."admin/adminUsers.php?iUserId=".$oUser->iUserId."&sSearch=".$aClean['sSearch']."&iLimit=".$aClean['iLimit']."&sOrderBy=".$aClean['sOrderBy']."&sSubmit=true");
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
if($oUser->iUserId == $aClean['iUserId'])
{
    echo "<input type=\"hidden\" name=\"iLimit\" value=\"".$aClean['iLimit']."\">\n";
    echo "<input type=\"hidden\" name=\"sOrderBy\" value=\"".$aClean['sOrderBy']."\">\n";
    echo "<input type=\"hidden\" name=\"sSearch\" value=\"".$aClean['sSearch']."\">\n";
    echo "<input type=\"hidden\" name=\"iUserId\" value=\"".$aClean['iUserId']."\">\n";
}

echo html_frame_start("Preferences for ".$oUser->sRealname, "80%");
echo html_table_begin("width='100%' border=0 align=left cellspacing=0 class='box-body'");

show_user_fields();

// if we don't manage another user
if($oUser->iUserId != $aClean['iUserId']) build_prefs_list();

echo html_table_end();
echo html_frame_end();
echo "<br /> <div align=center> <input type=\"submit\" value=\"Update\" /> </div> <br />\n";
echo "</form>\n";

apidb_footer();
?>
