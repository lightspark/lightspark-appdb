<?php
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");

if(!is_numeric($aClean['iAppId']) OR !is_numeric($aClean['iVersionId']))
    util_show_error_page_and_exit("Wrong ID");

/* Check for admin privs */
if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient Privileges!");

if(!empty($aClean['sAction']))
{
    /* move this version to the given application */
    $oVersion = new Version($aClean['iVersionId']);
    $oVersion->iAppId = $aClean['iAppId'];
    $oVersion->update();
    $oApp = new application($aClean['iAppId']);

    /* redirect to the application we just moved this version to */
    util_redirect_and_exit($oApp->objectMakeUrl());
} else /* or display the webform for making changes */
{
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<?php
    $oVersion = new Version($aClean['iVersionId']);
    $oApp = new Application($oVersion->iAppId);

    apidb_header("Choose application to move this version under");

    echo "<form method=post action='moveAppVersion.php'>\n";
    echo html_frame_start("Move ".$oApp->sName." ".$oVersion->sName, "90%","",0);
    echo '<input type="hidden" name="iAppId" value="'.$oVersion->iAppId.'" />';
    echo '<input type="hidden" name="iVersionId" value="'.$oVersion->iVersionId.'" />';


    /* build a table of applications and their versions */
    echo html_table_begin("align=\"center\" style=\"border-collapse: collapse;\"");

    // NOTE: the left join here is expensive and takes some 5x as long as a normal select from appFamily and appVersion would take
    //  although this cheaper select leaves out all applications that lack versions
    $sQuery = "select appName, appFamily.appId, versionName, versionId from appFamily left join appVersion ";
    $sQuery.= "on appVersion.appId = appFamily.appId ORDER BY appFamily.appName, appFamily.appId, appVersion.versionName;";
    $hResult = query_parameters($sQuery);
    $currentAppId = 0;
    while($oRow = query_fetch_object($hResult))
    {
        /* if the version ids differ then we should start a row with a new application */
        /* and the version that matches with it */
        if($iCurrentAppId != $oRow->appId)
        {
            $oApp = new application($oRow->appId);
            $iCurrentAppId = $oRow->appId;
            echo '<tr style="background: #CCDDFF; border: thin solid; font-weight:bold;"><td align="left" style="padding-left:20px;">';
            $sUrl = $oApp->objectMakeUrl();
            echo '<a href="'.$sUrl.'">'.substr($oRow->appName, 0, 30).'</a></td><td> - '.$oRow->appId.'</td>';
            echo "<td style='padding-left:20px;'><a href='moveAppVersion.php?sAction=move&iVersionId=$oVersion->iVersionId&iAppId=$oRow->appId'>Move here</a></td></tr>";
            echo '<tr style="border-left: thin solid; border-right:thin solid; background: #FAFBE2;"><td style="padding-left:40px;" colspan="3" align="left">'.$oRow->versionName.'</td></tr>';
        } else /* just add another version */
        {
            echo '<tr style="border-left: thin solid; border-right:thin solid; background: #FAFBE2;"><td style="padding-left:40px;" colspan="3" align="left">'.$oRow->versionName.'</td></tr>';
        }
        echo "\n";
    }

    echo html_table_end();
    echo html_frame_end();
    echo "</form>";
    echo html_back_link(1, $oVersion->objectMakeUrl());
    apidb_footer();
}
?>
