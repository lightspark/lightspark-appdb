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
if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient Privileges!");
    exit;
}

if(isset($_REQUEST['action']))
{
    /* move this version to the given application */
    $oVersion = new Version($_REQUEST['versionId']);
    $oVersion->update(null, null, null, null, $_REQUEST['appId']);

    /* redirect to the application we just moved this version to */
    redirect(apidb_fullurl("appview.php?appId=".$_REQUEST['appId']));
} else /* or display the webform for making changes */
{
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<?php
    $oVersion = new Version($_REQUEST['versionId']);
    $oApp = new Application($oVersion->iAppId);

    apidb_header("Choose application to move this version under");

    echo "<form method=post action='moveAppVersion.php'>\n";
    echo html_frame_start("Move ".$oApp->sName." ".$oVersion->sName, "90%","",0);
    echo '<input type="hidden" name="appId" value='.$oVersion->iAppId.' />';
    echo '<input type="hidden" name="versionId" value='.$oVersion->iVersionId.' />';


    /* build a table of applications and their versions */
    echo html_table_begin("align=\"center\" style=\"border-collapse: collapse;\"");

    // NOTE: the left join here is expensive and takes some 5x as long as a normal select from appFamily and appVersion would take
    //  although this cheaper select leaves out all applications that lack versions
    $sQuery = "select appName, appFamily.appId, versionName, versionId from appFamily left join appVersion ";
    $sQuery.= "on appVersion.appId = appFamily.appId ORDER BY appFamily.appName, appFamily.appId, appVersion.versionName;";
    $hResult = query_appdb($sQuery);
    $currentAppId = 0;
    while($oRow = mysql_fetch_object($hResult))
    {
        /* if the version ids differ then we should start a row with a new application */
        /* and the version that matches with it */
        if($currentAppId != $oRow->appId)
        {
            $currentAppId = $oRow->appId;
            echo '<tr style="background: #CCDDFF; border: thin solid; font-weight:bold;"><td align="left" style="padding-left:20px;">';
            $url = BASE."appview.php?appId=".$oRow->appId;
            echo '<a href="'.$url.'">'.substr($oRow->appName, 0, 30).'</a></td><td> - '.$oRow->appId.'</td>';
            echo "<td style='padding-left:20px;'><a href='moveAppVersion.php?action=move&versionId=$oVersion->iVersionId&appId=$oRow->appId'>Move here</a></td></tr>";
            echo '<tr style="border-left: thin solid; border-right:thin solid; background: #FAFAD2;"><td style="padding-left:40px;" colspan="3" align="left">'.$oRow->versionName.'</td></tr>';
        } else /* just add another version */
        {
            echo '<tr style="border-left: thin solid; border-right:thin solid; background: #FAFBE2;"><td style="padding-left:40px;" colspan="3" align="left">'.$oRow->versionName.'</td></tr>';
        }
        echo "\n";
    }

    echo html_table_end();
    echo html_frame_end();
    echo "</form>";
    echo html_back_link(1, BASE."appview.php?versionId=".$oVersion->iVersionId);
    apidb_footer();
}
?>
