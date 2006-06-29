<?php
/**********************************/
/* code to display an application */
/**********************************/

/*
 * application environment
 */
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/appdb.php");
require(BASE."include/vote.php");
require(BASE."include/category.php");
require(BASE."include/maintainer.php");
require(BASE."include/mail.php");
require(BASE."include/monitor.php");
require_once(BASE."include/testResults.php");

$aClean = array(); //array of filtered user input

$aClean['appId'] = makeSafe($_REQUEST['appId']);
$aClean['versionId'] = makeSafe($_REQUEST['versionId']);
$aClean['sub'] = makeSafe($_REQUEST['sub']);
$aClean['buglinkId'] = makeSafe($_REQUEST['buglinkId']);

$oApp = new Application($aClean['appId']);
$oVersion = new Version($aClean['versionId']);

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
 * display the SUB apps that belong to this app 
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
        echo "    <td><a href=\"appview.php?appId=$ob->appId\">".stripslashes($ob->appName)."</a></td>\n";
        echo "    <td>".util_trim_description($oApp->sDescription)."</td>\n";
        echo "</tr>\n\n";

        $c++;
    }

    echo "</table>\n\n";
    echo html_frame_end();
}

/* Show note */
function show_note($sType,$oData)
{
    global $oVersion;

    switch($sType)
    {
        case 'WARNING':
        $color = 'red';
        $title = 'Warning';
        break;

        case 'HOWTO';
        $color = 'green';
        $title = 'HOWTO';
        break;

        default:
        
        if(!empty($oData->noteTitle))
            $title = $oData->noteTitle;
        else 
            $title = 'Note';
            
        $color = 'blue';
    }
    
    $s = html_frame_start("","98%",'',0);

    $s .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\">\n";
    $s .= "<tr bgcolor=\"".$color."\" align=\"center\" valign=\"top\"><td><b>".$title."</b></td></tr>\n";
    $s .= "<tr><td class=\"note\">\n";
    $s .= $oData->noteDesc;
    $s .= "</td></tr>\n";

    if ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($oVersion->iVersionId) || $_SESSION['current']->isSuperMaintainer($oVersion->iAppId))
    {
        $s .= "<tr class=\"color1\" align=\"center\" valign=\"top\"><td>";
        $s .= "<form method=\"post\" name=\"message\" action=\"admin/editAppNote.php?noteId={$oData->noteId}\">";
        $s .= '<input type="submit" value="Edit Note" class="button">';
        $s .= '</form></td></tr>';
    }

    $s .= "</table>\n";
    $s .= html_frame_end();

    return $s;
}

if(!is_numeric($aClean['appId']) && !is_numeric($aClean['versionId']))
{
    util_show_error_page("Something went wrong with the application or version id");
    exit;
}

if ($aClean['sub'])
{
    if(($aClean['sub'] == 'delete' ) && ($aClean['buglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($aClean['buglinkId']);
            $oBuglink->delete();
            redirect(apidb_fullurl("appview.php?versionId=".$aClean['versionId']));
            exit;
        }
 
    }
    if(($aClean['sub'] == 'unqueue' ) && ($aClean['buglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($aClean['buglinkId']);
            $oBuglink->unqueue();
            redirect(apidb_fullurl("appview.php?versionId=".$aClean['versionId']));
            exit;
        }
 
    }
    if(($aClean['sub'] == 'Submit a new bug link.' ) && ($aClean['buglinkId']))
    {
        $oBuglink = new bug();
        $oBuglink->create($aClean['versionId'],$aClean['buglinkId']);
        redirect(apidb_fullurl("appview.php?versionId=".$aClean['versionId']));
        exit;
    }
    if($aClean['sub'] == 'StartMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->create($_SESSION['current']->iUserId,$aClean['appId'],$aClean['versionId']);
        redirect(apidb_fullurl("appview.php?versionId=".$aClean['versionId']));
        exit;
    }
    if($aClean['sub'] == 'StopMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->find($_SESSION['current']->iUserId,$aClean['appId'],$aClean['versionId']);
        if($oMonitor->iMonitorId)
        {
            $oMonitor->delete();
        }
        redirect(apidb_fullurl("appview.php?versionId=".$aClean['versionId']));
        exit;
    }

}

/**
 * We want to see an application family (=no version).
 */
if($aClean['appId'])
{
    $oApp = new Application($aClean['appId']);
    $oApp->display();
} else if($aClean['versionId']) // We want to see a particular version.
{
    $oVersion = new Version($aClean['versionId']);
    $oVersion->display();
} else
{
    // Oops! Called with no params, bad llamah!
    util_show_error_page('Page Called with No Params!');
    exit;
}

apidb_footer();
?>
