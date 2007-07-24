<?php
/**
 * Displays an application or a version.
 *
 * Mandatory parameters:
 *  - iAppId, application identifier
 *               OR
 *  - iVersionId, version identifier
 *
 * Optional parameters:
 *  - sSub, action to perform ("delete", "unqueue", "Submit a new bug link.", "StartMonitoring", "StopMonitoring")
 *  - iBuglinkId, bug identifier to link a bug with a version
 *
 * TODO:
 *  - replace sSub with iAction and replace "delete", "unqueue", etc. with integer constants DELETE, UNQUEUE, etc.
 *  - move and rename display_bundle into its respective modules
 *  - replace require_once with require after checking that it won't break anything
 */

// application environment
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/application.php");
require_once(BASE."include/appdb.php");
require_once(BASE."include/vote.php");
require_once(BASE."include/category.php");
require_once(BASE."include/maintainer.php");
require_once(BASE."include/monitor.php");


/**
 * Displays the SUB apps that belong to this application.
 */
function display_bundle($iAppId)
{
    $oApp = new Application($iAppId);
    $hResult = query_parameters("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
                            "WHERE appFamily.queued='false' AND bundleId = '?' AND appBundle.appId = appFamily.appId",
                            $iAppId);
    if(!$hResult || mysql_num_rows($hResult) == 0)
    {
         return; // do nothing
    }

    echo html_frame_start("","98%","",0);
    echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"1\">\n\n";

    echo "<tr class=\"color4\">\n";
    echo "    <td>Application Name</td>\n";
    echo "    <td>Description</td>\n";
    echo "</tr>\n\n";

    $c = 0;
    while($ob = mysql_fetch_object($hResult))
    {
        $oApp = new application($ob->appId);
        //set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //display row
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td>".$oApp->objectMakeLink()."</td>\n";
        echo "    <td>".util_trim_description($oApp->sDescription)."</td>\n";
        echo "</tr>\n\n";

        $c++;
    }

    echo "</table>\n\n";
    echo html_frame_end();
}

// if both iAppId and iVersionId are empty we have a problem
if(empty($aClean['iAppId']) && empty($aClean['iVersionId']))
    util_show_error_page_and_exit("Something went wrong with the application or version id");

if (isset($aClean['sSub']))
{
    if(($aClean['sSub'] == 'delete' ) && ($aClean['iBuglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new Bug($aClean['iBuglinkId']);
            $oBuglink->delete();
            util_redirect_and_exit($oVersion->objectMakeUrl());
        }
 
    }
    if(($aClean['sSub'] == 'unqueue' ) && ($aClean['iBuglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new Bug($aClean['iBuglinkId']);
            $oBuglink->unqueue();
            util_redirect_and_exit($oVersion->objectMakeUrl());
        }
 
    }
    if(($aClean['sSub'] == 'Submit a new bug link.' ) && ($aClean['iBuglinkId']))
    {
        $oBuglink = new Bug();
        $oBuglink->iVersionId = $aClean['iVersionId'];
        $oBuglink->iBug_id = $aClean['iBuglinkId']; 
        $oBuglink->create();
        util_redirect_and_exit($oVersion->objectMakeUrl());
    }
    if($aClean['sSub'] == 'StartMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->iUserId = $_SESSION['current']->iUserId;
        $oMonitor->iAppId = $aClean['iAppId'];
        $oMonitor->iVersionId = $aClean['iVersionId'];
        $oMonitor->create();
        util_redirect_and_exit($oVersion->objectMakeUrl());
    }
    if($aClean['sSub'] == 'StopMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->find($_SESSION['current']->iUserId, $aClean['iVersionId']);
        if($oMonitor->iMonitorId)
        {
            $oMonitor->delete();
        }
        util_redirect_and_exit($oVersion->objectMakeUrl());
    }

}

/**
 * We want to see an application family (=no version).
 */
if( isset($aClean['iAppId']) )
{
    $oApp = new Application($aClean['iAppId']);
    $oApp->display();
} else if( isset($aClean['iVersionId']) ) // We want to see a particular version.
{
    $oVersion = new Version($aClean['iVersionId']);
    $iTestingId = isset($aClean['iTestingId']) ? $aClean['iTestingId'] : null;
    $oVersion->display($iTestingId);
} else
{
    // Oops! Called with no params, bad llamah!
    util_show_error_page_and_exit('Page Called with No Params!');
}

apidb_footer();
?>
