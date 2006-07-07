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
 *  - move and rename display_catpath and display_bundle in their respective modules
 *  - replace require_once with require after checking that it won't break anything
 */

// application environment
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/filter.php");
require(BASE."include/application.php");
require(BASE."include/appdb.php");
require(BASE."include/vote.php");
require(BASE."include/category.php");
require(BASE."include/maintainer.php");
require(BASE."include/mail.php");
require(BASE."include/monitor.php");
require_once(BASE."include/testResults.php");

$oApp = new Application($aClean['iAppId']);
$oVersion = new Version($aClean['iVersionId']);

/**
 * display the full path of the Category we are looking at
 */
function display_catpath($catId, $appId, $versionId = '')
{
    $cat = new Category($catId);

    $catFullPath = make_cat_path($cat->getCategoryPath(), $appId, $versionId);
    echo html_frame_start("",'98%','',2);
    echo "<p><b>Category: ". $catFullPath ."</b><br />\n";
    echo html_frame_end();
}


/**
 * Displays the SUB apps that belong to this application.
 */
function display_bundle($iAppId)
{
    $oApp = new Application($appId);
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
        //set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //display row
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td><a href=\"appview.php?iAppId=$ob->appId\">".stripslashes($ob->appName)."</a></td>\n";
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

if ($aClean['sSub'])
{
    if(($aClean['sSub'] == 'delete' ) && ($aClean['iBuglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($aClean['iBuglinkId']);
            $oBuglink->delete();
            util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
        }
 
    }
    if(($aClean['sSub'] == 'unqueue' ) && ($aClean['iBuglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($aClean['iBuglinkId']);
            $oBuglink->unqueue();
            util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
        }
 
    }
    if(($aClean['sSub'] == 'Submit a new bug link.' ) && ($aClean['iBuglinkId']))
    {
        $oBuglink = new bug();
        $oBuglink->create($aClean['iVersionId'],$aClean['iBuglinkId']);
        util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
    }
    if($aClean['sSub'] == 'StartMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->create($_SESSION['current']->iUserId,$aClean['iAppId'],$aClean['iVersionId']);
        util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
    }
    if($aClean['sSub'] == 'StopMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->find($_SESSION['current']->iUserId,$aClean['iAppId'],$aClean['iVersionId']);
        if($oMonitor->iMonitorId)
        {
            $oMonitor->delete();
        }
        util_redirect_and_exit(apidb_fullurl("appview.php?iVersionId=".$aClean['iVersionId']));
    }

}

/**
 * We want to see an application family (=no version).
 */
if($aClean['iAppId'])
{
    $oApp = new Application($aClean['iAppId']);
    $oApp->display();
} else if($aClean['iVersionId']) // We want to see a particular version.
{
    $oVersion = new Version($aClean['iVersionId']);
    $oVersion->display();
} else
{
    // Oops! Called with no params, bad llamah!
    util_show_error_page_and_exit('Page Called with No Params!');
}

apidb_footer();
?>
