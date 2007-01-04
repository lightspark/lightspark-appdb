<?php
/************************/
/* Add Application Note */
/************************/

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/note.php");

//FIXME: get rid of appId references everywhere, as version is enough.
$sQuery = "SELECT appId FROM appVersion WHERE versionId = '?'";
$hResult = query_parameters($sQuery, $aClean['iVersionId']);
$oRow = mysql_fetch_object($hResult);
$appId = $oRow->appId; 

//check for admin privs
if(!$_SESSION['current']->hasPriv("admin") &&
   !$_SESSION['current']->isMaintainer($aClean['iVersionId']) &&
   !$_SESSION['current']->isSuperMaintainer($aClean['iAppId']))
{
    util_show_error_page_and_exit("Insufficient Privileges!");
}

//set link for version
if(is_numeric($aClean['iVersionId']) and !empty($aClean['iVersionId']))
{
    $sVersionLink = "iVersionId={$aClean['iVersionId']}";
}
else 
    exit;

$oNote = new Note();
$oNote->GetOutputEditorValues($aClean);

if($aClean['sSub'] == "Submit")
{
    $oNote->create();
    util_redirect_and_exit(apidb_fullurl("appview.php?".$sVersionLink));
}
else if($aClean['sSub'] == 'Preview' OR empty($aClean['sSubmit']))
{
    // show form
    apidb_header("Application Note");

    if($aClean['sSub'] == 'Preview')
        $oNote->show(true);

    echo "<form method=post action='addAppNote.php'>\n";

    $oNote->OutputEditor();

    echo '<center>';
    echo '<input type="submit" name="sSub" value="Preview">&nbsp',"\n";
    echo '<input type="submit" name="sSub" value="Submit"></td></tr>',"\n";
    echo '</center>';
    
    echo html_back_link(1,BASE."appview.php?".$sVersionLink);
    apidb_footer();
}
?>
