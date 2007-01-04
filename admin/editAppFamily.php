<?php
/**********************************/
/* Edit application family        */
/**********************************/

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/category.php");

if(!is_numeric($aClean['iAppId']))
    util_show_error_page_and_exit("Wrong ID");

if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isSuperMaintainer($aClean['iAppId'])))
    util_show_error_page_and_exit("Insufficient Privileges!");

if(!empty($aClean['sSubmit']))
{
    process_app_version_changes(false);
    util_redirect_and_exit(apidb_fullurl("appview.php?iAppId={$aClean['iAppId']}"));
}
else
// Show the form for editing the Application Family 
{
    $family = new TableVE("edit");


    $oApp = new Application($aClean['iAppId']);
    
    if(!$oApp)
    {
        util_show_error_page_and_exit('Application does not exist');
    }
    
    if($_SESSION['current']->showDebuggingInfos()) { echo "<p align=center><b>appName:</b> $oApp->sName </p>"; }

     apidb_header("Edit Application Family");

    echo "<form method=\"post\" action=\"editAppFamily.php\">\n";

    $oApp->OutputEditor("");

    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">', "\n";
    echo '<tr><td colspan=2 align=center><input type="submit" name=sSubmit value="Update Database"></td></tr>',"\n";
    echo '</table>', "\n";
    echo "</form>";

    echo "<p>";

   // url edit form
    echo '<form enctype="multipart/form-data" action="editAppFamily.php" method="post">',"\n";
    echo '<input type="hidden" name="iAppId" value="'.$oApp->iAppId.'" />';
    echo html_frame_start("Edit URL","90%","",0);
    echo '<table border=0 cellpadding=6 cellspacing=0 width="100%">',"\n";

    $i = 0;
    $hResult = query_parameters("SELECT * FROM appData WHERE appId = '?' AND type = 'url' AND versionId = 0",
                            $oApp->iAppId);
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
            echo '<td class=color3><input size=45% type="text" name="'.$temp1.'" value ="'.stripslashes($oRow->description).'"</td>',"\n";
            echo '<td class=color3><input size=45% type="text" name="'.$temp2.'" value="'.$oRow->url.'"></td></tr>',"\n";
            echo '<input type="hidden" name="'.$temp3.'" value="'.$oRow->id.'" />';
            echo '<input type=hidden name="'.$temp4.'" value="'.stripslashes($oRow->description).'">';
            echo '<input type=hidden name="'.$temp5.'" value="'.$oRow->url.'">',"\n";
            $i++;
        }
    } else
    {
        echo '<tr><td class=color1></td><td class=color1><b>Description</b></td>',"\n";
        echo '<td class=color1><b>URL</b></td></tr>',"\n";
    }
    echo "</td></tr>\n";
    echo "<input type=hidden name='iRows' value='$i'>";

    echo '<tr><td class=color1>New</td><td class=color1><input size=45% type="text" name="sUrlDesc"></td>',"\n";
    echo '<td class=color1><input size=45% name="sUrl" type="text"></td></tr>',"\n";

    echo '<tr><td colspan=3 align=center class=color3><input type="submit" name=sSubmit value="Update URL"></td></tr>',"\n";

    echo '</table>',"\n";
    echo html_frame_end();
    echo "</form>";

    echo html_back_link(1,BASE."appview.php?iAppId=$oApp->iAppId");
}

apidb_footer();
?>
