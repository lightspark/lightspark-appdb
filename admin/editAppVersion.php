<?php
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

if(!is_numeric($_REQUEST['appId']) OR !is_numeric($_REQUEST['versionId']))
{
    errorpage("Wrong ID");
    exit;
}

/* Check for admin privs */
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($_REQUEST['versionId']) && !$_SESSION['current']->isSuperMaintainer($_REQUEST['appId']))
{
    errorpage("Insufficient Privileges!");
    exit;
}

/* process the changes the user entered into the web form */
if(isset($_REQUEST['submit']))
{
    process_app_version_changes(true);
    redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
} else /* or display the webform for making changes */
{

    $oVersion = new Version($_REQUEST['versionId']);

    /* if the sDescription is empty, put the default in */
    if(trim(strip_tags($oVersion->sDescription))=="")
        $oVersion->sDescription = GetDefaultVersionDescription();

    apidb_header("Edit Application Version");

    echo "<form method=post action='editAppVersion.php'>\n";

    if($_SESSION['current']->hasPriv("admin"))
        $oVersion->OutputEditor(true, true); /* false = not allowing the user to modify the parent application */
    else
        $oVersion->OutputEditor(false, true); /* false = not allowing the user to modify the parent application */
        
    echo '<table border=0 cellpadding=2 cellspacing=0 width="100%">',"\n";
    echo '<tr><td colspan=2 align=center class=color2><input type="submit" name="submit" value="Update Database" /></td></tr>',"\n";
    echo html_table_end();

    echo "</form>";

    echo "<br/><br/>\n";

    // url edit form
    echo '<form enctype="multipart/form-data" action="editAppVersion.php" method="post">',"\n";
    echo '<input type=hidden name="appId" value='.$oVersion->iAppId.'>';
    echo '<input type=hidden name="versionId" value='.$oVersion->iVersionId.'>';
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
    $i = 0;
    $result = query_appdb("SELECT * FROM appData WHERE versionId = ".$oVersion->iVersionId." AND type = 'url'");
    if($result && mysql_num_rows($result) > 0)
    {
        echo '<tr><td class=color1><b>Delete</b></td><td class=color1>',"\n";
        echo '<b>Description</b></td><td class=color1><b>URL</b></td></tr>',"\n";
        while($ob = mysql_fetch_object($result))
        {
            $temp0 = "adelete[".$i."]";
            $temp1 = "adescription[".$i."]";
            $temp2 = "aURL[".$i."]";
            $temp3 = "aId[".$i."]";
            $temp4 = "aOldDesc[".$i."]";
            $temp5 = "aOldURL[".$i."]";
            echo '<tr><td class=color3><input type="checkbox" name="'.$temp0.'"></td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp1.'" value ="'.stripslashes($ob->description).'"</td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp2.'" value="'.$ob->url.'"></td></tr>',"\n";
            echo '<input type="hidden" name="'.$temp3.'" value="'.$ob->id.'" />';
            echo '<input type="hidden" name="'.$temp4.'" value="'.stripslashes($ob->description).'" />';
            echo '<input type="hidden" name="'.$temp5.'" value="'.$ob->url.'" />',"\n";
            $i++;
        }
    } else
    {
        echo '<tr><td class="color1"></td><td class="color1"><b>Description</b></td>',"\n";
        echo '<td class=color1><b>URL</b></td></tr>',"\n";
    }
    echo "</td></tr>\n";
    echo "<input type=hidden name='rows' value='$i'>";
    echo '<tr><td class=color1>New</td><td class=color1><input size="45" type="text" name="url_desc"></td>',"\n";
    echo '<td class=color1><input size=45% name="url" type="text"></td></tr>',"\n";
     
    echo '<tr><td colspan=3 align=center class="color3"><input type="submit" name="submit" value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();
    echo "</form>";

    /* only admins can move versions */
    if($_SESSION['current']->hasPriv("admin"))
    {
        // move version form
        echo '<form enctype="multipart/form-data" action="moveAppVersion.php" method="post">',"\n";
        echo '<input type=hidden name="appId" value='.$oVersion->iAppId.'>';
        echo '<input type=hidden name="versionId" value='.$oVersion->iVersionId.'>';
        echo html_frame_start("Move version to another application","90%","",0);
        echo '<center><input type="submit" name="view" value="Move this version"></center>',"\n";
        echo html_frame_end();
    }

    echo html_back_link(1,BASE."appview.php?versionId=".$oVersion->iVersionId);
    apidb_footer();
}
?>
