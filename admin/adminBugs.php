<?php
/***************************************************/
/* code to view and maintain the list of bug links */
/***************************************************/

/*
 * application environment
 */ 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/bugs.php");

$aClean = array(); //array of filtered user input

$aClean['sSub'] = makeSafe($_REQUEST['sSub']);
$aClean['iBuglinkId'] = makeSafe($_REQUEST['iBuglinkId']);
$aClean['iItemsPerPage'] = makeSafe($_REQUEST['iItemsPerPage']);
$aClean['sQueuedOnly'] = makeSafe($_REQUEST['sQueuedOnly']);
$aClean['iPage'] = makeSafe($_REQUEST['iPage']);


// deny access if not logged in
if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient privileges.");

if ($aClean['sSub'])
{
    if(($aClean['sSub'] == 'delete' ) && ($aClean['iBuglinkId']))
    {
        $oBuglink = new bug($aClean['iBuglinkId']);
        $oBuglink->delete();
    }
    if(($aClean['sSub'] == 'unqueue' ) && ($aClean['iBuglinkId']))
    {
        $oBuglink = new bug($aClean['iBuglinkId']);
        $oBuglink->unqueue();
    }
    util_redirect_and_exit($_SERVER['PHP_SELF']."?iItemsPerPage=".$aClean['iItemsPerPage']."&sQueuedOnly=".$aClean['sQueuedOnly']."&iPage=".$aClean['iPage']);

}

{
    apidb_header("Administer Bugs");

    $pageRange = 10;
    $ItemsPerPage = 10;
    $currentPage = 1;
    $QueuedOnly = empty($aClean['sQueuedOnly'])? NULL: $aClean['sQueuedOnly'];
    $BugLinks = ($QueuedOnly == 'on')?getNumberOfQueuedBugLinks():getNumberOfBugLinks();
    if($aClean['iItemsPerPage'])
        $ItemsPerPage = $aClean['iItemsPerPage'];

    if($aClean['iPage'])
        $currentPage = $aClean['iPage'];

    $ItemsPerPage = min($ItemsPerPage,100);
    $totalPages = max(ceil($BugLinks/$ItemsPerPage),1);
    $currentPage = min($currentPage,$totalPages);
    $offset = (($currentPage-1) * $ItemsPerPage);

    /* display page selection links */
    echo '<form method="get" name="sMessage" action="'.$_SERVER['PHP_SELF'].'">',"\n";
    echo '<center>',"\n";
    echo '<b>Page '.$currentPage.' of '.$totalPages.'</b><br />',"\n";
    display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?iItemsPerPage=".$ItemsPerPage."&sQueuedOnly=".$QueuedOnly);
    echo '<br />',"\n";
    echo '<br />',"\n";

    /* display the option to choose how many comments per-page to display */
    echo '<input type=hidden name=iPage value='.$currentPage.'>';

    echo '<b>Number of Bug Links per page: </b>';
    echo '<select name="iItemsPerPage">';

    $ItemsPerPageArray = array(2 ,10, 20, 50, 100);
    foreach($ItemsPerPageArray as $i => $value)
    {
        if($ItemsPerPageArray[$i] == $ItemsPerPage)
            echo '<option value='.$ItemsPerPageArray[$i].' SELECTED>'.$ItemsPerPageArray[$i],"\n";
        else
            echo '<option value='.$ItemsPerPageArray[$i].'>'.$ItemsPerPageArray[$i],"\n";
    }
    echo '</select>',"\n";

    echo '<br />',"\n";
    echo '<b>View queued links only: </b><input type=checkbox name="sQueuedOnly" '.($QueuedOnly == "on"?" CHECKED":"").'>',"\n";
    echo '<br />',"\n";
    echo '<input type=submit value="Refresh">',"\n";

    echo '</center>',"\n";
    echo '</form>',"\n";

    echo '<table width=100% border=0 cellpadding=3 cellspacing=1>',"\n";
    echo '<tr class=color4>',"\n";
    echo '    <td align=center width="40">Bug #</td>',"\n";
    echo '    <td align=center width="60">Status</td>',"\n";
    echo '    <td>Bug Description</td>',"\n";
    echo '    <td width=80>Application Name</td>',"\n";
    echo '    <td>Aplication Description</td>',"\n";
    echo '    <td width=40>version</td>',"\n";
    echo '    <td align=center width="40">delete</td>',"\n";
    echo '    <td align=center width="40">checked</td>',"\n";
    echo '</tr>',"\n";
    if ($QueuedOnly == 'on')
    {
        $sWhere =  "WHERE appFamily.appId = appVersion.appId
                    AND buglinks.versionId = appVersion.versionId
                    AND buglinks.bug_id = ".BUGZILLA_DB.".bugs.bug_id
                    AND buglinks.queued = 'true'";
    } else
    {
        $sWhere =  "WHERE appFamily.appId = appVersion.appId
                    AND buglinks.versionId = appVersion.versionId
                    AND buglinks.bug_id = ".BUGZILLA_DB.".bugs.bug_id";
    }
    $sQuery = "SELECT appFamily.description as appDescription, 
               appFamily.appName as appName, appVersion.*, 
               buglinks.versionId as versionId, 
               buglinks.bug_id as bug_id, 
               buglinks.linkId as linkId, 
               buglinks.queued as queued, 
               bugs.* 
               FROM appFamily, appVersion, buglinks, bugs.bugs
               ".$sWhere."
               ORDER BY buglinks.bug_id, appName, versionName
               LIMIT ".mysql_real_escape_string($offset).", ".mysql_real_escape_string($ItemsPerPage).";";

    $c = 0;

    if($hResult = query_parameters($sQuery))
    {
        while($oRow = mysql_fetch_object($hResult))
        {
            // set row color
            $bgcolor = ($c % 2 == 0) ? "color0" : "color1";
            echo '<tr class='.$bgcolor.'>',"\n";
            echo '    <td align=center>',"\n";
            echo '    <a href="'.BUGZILLA_ROOT.'show_bug.cgi?id='.$oRow->bug_id.'">'.$oRow->bug_id.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td align=center>'.$oRow->bug_status.'</td>',"\n";
            echo '    <td>'.$oRow->short_desc.'</td>',"\n";
            echo '    <td>',"\n";
            echo '    <a href="'.apidb_fullurl('appview.php?iAppId='.$oRow->appId).'">'.$oRow->appName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td>'.$oRow->appDescription.'</td>',"\n";
            echo '    <td>',"\n";
            echo '    <a href="'.apidb_fullurl('appview.php?iVersionId='.$oRow->versionId).'">'.$oRow->versionName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td align=center>[<a href="adminBugs.php?sSub=delete',"\n";
            echo          '&iBuglinkId='.$oRow->linkId,"\n";
            echo          '&sQueuedOnly='.$QueuedOnly,"\n";
            echo          '&iItemsPerPage='.$ItemsPerPage,"\n";
            echo          '&iPage='.$currentPage,"\n";
            echo          '">delete</a>]</td>',"\n";
            $bQueued = ($oRow->queued=="true")?true:false;
            if ($bQueued)
            {
                echo '<td align=center>[<a href="adminBugs.php?sSub=unqueue',"\n";
                echo      '&iBuglinkId='.$oRow->linkId,"\n";
                echo      '&sQueuedOnly='.$QueuedOnly,"\n";
                echo      '&iItemsPerPage='.$ItemsPerPage,"\n";
                echo      '&iPage='.$currentPage,"\n";
                echo '">OK</a>]</td>',"\n";
            } else
            {
                echo '<td align=center>Yes</td>',"\n";
            }
            echo '</tr>',"\n";
            $c++;
        }
    }

    echo "</table>","\n";
    echo "<center>","\n";
    display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?iItemsPerPage=".$ItemsPerPage."&sQueuedOnly=".$QueuedOnly);
    echo "</center>","\n";

    apidb_footer();
}
?>
