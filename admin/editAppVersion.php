<?php
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");

if(!is_numeric($aClean['iAppId']) OR !is_numeric($aClean['iVersionId']))
    util_show_error_page_and_exit("Wrong ID");

/* Check for admin privs */
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isMaintainer($aClean['iVersionId']) && !$_SESSION['current']->isSuperMaintainer($aClean['iAppId']))
    util_show_error_page_and_exit("Insufficient Privileges!");

/* process the changes the user entered into the web form */
if(!empty($aClean['sSubmit']))
{
    process_app_version_changes(true);
    downloadurl::processForm($aClean);
    util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
} else /* or display the webform for making changes */
{

    $oVersion = new Version($aClean['iVersionId']);

    apidb_header("Edit Application Version");

    echo "<form method=post action='editAppVersion.php'>\n";

    if($_SESSION['current']->hasPriv("admin"))
        $oVersion->OutputEditor(true, true); /* false = not allowing the user to modify the parent application */
    else
        $oVersion->OutputEditor(false, true); /* false = not allowing the user to modify the parent application */
        
    echo '<table border=0 cellpadding=2 cellspacing=0 width="100%">',"\n";
    echo '<tr><td colspan=2 align=center class=color2><input type="submit" name="sSubmit" value="Update Database" /></td></tr>',"\n";
    echo html_table_end();

    echo "</form>";

    echo "<br/><br/>\n";

    // url edit form
    echo '<form enctype="multipart/form-data" action="editAppVersion.php" method="post">',"\n";
    echo '<input type="hidden" name="iAppId" value="'.$oVersion->iAppId.'" />';
    echo '<input type="hidden" name="iVersionId" value="'.$oVersion->iVersionId.'" />';
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";
            
    $i = 0;
    $hResult = query_parameters("SELECT * FROM appData WHERE versionId = '?' AND type = 'url'",
                            $oVersion->iVersionId);
    if($hResult && mysql_num_rows($hResult) > 0)
    {
        echo '<tr><td class=color1><b>Delete</b></td><td class=color1>',"\n";
        echo '<b>Description</b></td><td class=color1><b>URL</b></td></tr>',"\n";
        while($oRow = mysql_fetch_object($hResult))
        {
            $temp0 = "adelete[".$i."]";
            $temp1 = "adescription[".$i."]";
            $temp2 = "aURL[".$i."]";
            $temp3 = "aId[".$i."]";
            $temp4 = "aOldDesc[".$i."]";
            $temp5 = "aOldURL[".$i."]";
            echo '<tr><td class=color3><input type="checkbox" name="'.$temp0.'"></td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp1.'" value ="'.stripslashes($oRow->description).'"</td>',"\n";
            echo '<td class=color3><input size="45" type="text" name="'.$temp2.'" value="'.$oRow->url.'"></td></tr>',"\n";
            echo '<input type="hidden" name="'.$temp3.'" value="'.$oRow->id.'" />';
            echo '<input type="hidden" name="'.$temp4.'" value="'.stripslashes($oRow->description).'" />';
            echo '<input type="hidden" name="'.$temp5.'" value="'.$oRow->url.'" />',"\n";
            $i++;
        }
    } else
    {
        echo '<tr><td class="color1"></td><td class="color1"><b>Description</b></td>',"\n";
        echo '<td class=color1><b>URL</b></td></tr>',"\n";
    }
    echo "</td></tr>\n";
    echo "<input type=hidden name='iRows' value='$i'>";
    echo '<tr><td class=color1>New</td><td class=color1><input size="45" type="text" name="sUrlDesc"></td>',"\n";
    echo '<td class=color1><input size=45% name="sUrl" type="text"></td></tr>',"\n";
     
    echo '<tr><td colspan=3 align=center class="color3"><input type="submit" name="sSubmit" value="Update URL"></td></tr>',"\n";
         
    echo '</table>',"\n";
    echo html_frame_end();
    echo "</form>";

    /* Download URL editor */
    echo downloadurl::OutputEditor($oVersion, "editAppVersion.php");

    /* only admins can move versions */
    if($_SESSION['current']->hasPriv("admin"))
    {
        // move version form
        echo '<form enctype="multipart/form-data" action="moveAppVersion.php" method="post">',"\n";
        echo '<input type="hidden" name="iAppId" value="'.$oVersion->iAppId.'" />';
        echo '<input type="hidden" name="iVersionId" value="'.$oVersion->iVersionId.'" />';
        echo html_frame_start("Move version to another application","90%","",0);
        echo '<center><input type="submit" name="sView" value="Move this version"></center>',"\n";
        echo html_frame_end();
    }

    echo html_back_link(1,BASE."appview.php?iVersionId=".$oVersion->iVersionId);
    apidb_footer();
}
?>
