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


$oApp = new Application($_REQUEST['appId']);
$oVersion = new Version($_REQUEST['versionId']);

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
function display_bundle($appId)
{
    $oApp = new Application($appId);
    $result = query_appdb("SELECT appFamily.appId, appName, description FROM appBundle, appFamily ".
                        "WHERE appFamily.queued='false' AND bundleId = $appId AND appBundle.appId = appFamily.appId");
    if(!$result || mysql_num_rows($result) == 0)
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
    while($ob = mysql_fetch_object($result)) {
        //set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //display row
        echo "<tr class=\"$bgcolor\">\n";
        echo "    <td><a href=\"appview.php?appId=$ob->appId\">".stripslashes($ob->appName)."</a></td>\n";
        echo "    <td>".trim_description($oApp->sDescription)."</td>\n";
        echo "</tr>\n\n";

        $c++;
    }

    echo "</table>\n\n";
    echo html_frame_end();
}

/* Show note */
function show_note($sType,$oData){
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

if(!is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId']))
{
    errorpage("Something went wrong with the application or version id");
    exit;
}

if ($_REQUEST['sub'])
{
    if(($_REQUEST['sub'] == 'delete' ) && ($_REQUEST['buglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($_REQUEST['buglinkId']);
            $oBuglink->delete();
            redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
            exit;
        }
 
    }
    if(($_REQUEST['sub'] == 'unqueue' ) && ($_REQUEST['buglinkId']))
    {
        if(($_SESSION['current']->hasPriv("admin") ||
            $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
            $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $oBuglink = new bug($_REQUEST['buglinkId']);
            $oBuglink->unqueue();
            redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
            exit;
        }
 
    }
    if(($_REQUEST['sub'] == 'Submit a new bug link.' ) && ($_REQUEST['buglinkId']))
    {
        $oBuglink = new bug();
        $oBuglink->create($_REQUEST['versionId'],$_REQUEST['buglinkId']);
        redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
        exit;
    }
    if($_REQUEST['sub'] == 'StartMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->create($_SESSION['current']->iUserId,$_REQUEST['appId'],$_REQUEST['versionId']);
        redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
        exit;
    }
    if($_REQUEST['sub'] == 'StopMonitoring')
    {
        $oMonitor = new Monitor();
        $oMonitor->find($_SESSION['current']->iUserId,$_REQUEST['appId'],$_REQUEST['versionId']);
        if($oMonitor->iMonitorId)
        {
            $oMonitor->delete();
        }
        redirect(apidb_fullurl("appview.php?versionId=".$_REQUEST['versionId']));
        exit;
    }

}

/**
 * We want to see an application family (=no version).
 */
if($_REQUEST['appId'])
{
    $oApp = new Application($_REQUEST['appId']);
    $oApp->display();
} else if($_REQUEST['versionId']) // We want to see a particular version.
{
    $oVersion = new Version($_REQUEST['versionId']);
    $oVersion->display();
} else
{
    // Oops! Called with no params, bad llamah!
    errorpage('Page Called with No Params!');
    exit;
}

apidb_footer();
?>
