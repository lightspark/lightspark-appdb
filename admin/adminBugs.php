<?php
/***************************************************/
/* code to view and maintain the list of bug links */
/***************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

$aClean = array(); //array of filtered user input

$aClean['sub'] = makeSafe($_REQUEST['sub']);
$aClean['buglinkId'] = makeSafe($_REQUEST['buglinkId']);
$aClean['ItemsPerPage'] = makeSafe($_REQUEST['ItemsPerPage']);
$aClean['QueuedOnly'] = makeSafe($_REQUEST['QueuedOnly']);
$aClean['page'] = makeSafe($_REQUEST['page']);


// deny access if not logged in
if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}

if ($aClean['sub'])
{
    if(($aClean['sub'] == 'delete' ) && ($aClean['buglinkId']))
    {
        $oBuglink = new bug($aClean['buglinkId']);
        $oBuglink->delete();
    }
    if(($aClean['sub'] == 'unqueue' ) && ($aClean['buglinkId']))
    {
        $oBuglink = new bug($aClean['buglinkId']);
        $oBuglink->unqueue();
    }
    redirect($_SERVER['PHP_SELF']."?ItemsPerPage=".$aClean['ItemsPerPage']."&QueuedOnly=".$aClean['QueuedOnly']."&page=".$aClean['page']);
    exit;
}

{
    apidb_header("Administer Bugs");

    $pageRange = 10;
    $ItemsPerPage = 10;
    $currentPage = 1;
    $QueuedOnly = empty($aClean['QueuedOnly'])? NULL: $aClean['QueuedOnly'];
    $BugLinks = ($QueuedOnly == 'on')?getNumberOfQueuedBugLinks():getNumberOfBugLinks();
    if($aClean['ItemsPerPage'])
        $ItemsPerPage = $aClean['ItemsPerPage'];

    if($aClean['page'])
        $currentPage = $aClean['page'];

    $ItemsPerPage = min($ItemsPerPage,100);
    $totalPages = max(ceil($BugLinks/$ItemsPerPage),1);
    $currentPage = min($currentPage,$totalPages);
    $offset = (($currentPage-1) * $ItemsPerPage);

    /* display page selection links */
    echo '<form method="get" name="message" action="'.$_SERVER['PHP_SELF'].'">',"\n";
    echo '<center>',"\n";
    echo '<b>Page '.$currentPage.' of '.$totalPages.'</b><br />',"\n";
    display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?ItemsPerPage=".$ItemsPerPage."&QueuedOnly=".$QueuedOnly);
    echo '<br />',"\n";
    echo '<br />',"\n";

    /* display the option to choose how many comments per-page to display */
    echo '<input type=hidden name=page value='.$currentPage.'>';

    echo '<b>Number of Bug Links per page: </b>';
    echo '<select name="ItemsPerPage">';

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
    echo '<b>View queued links only: </b><input type=checkbox name="QueuedOnly" '.($QueuedOnly == "on"?" CHECKED":"").'>',"\n";
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
            echo '    <a href="'.apidb_fullurl('appview.php?appId='.$oRow->appId).'">'.$oRow->appName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td>'.$oRow->appDescription.'</td>',"\n";
            echo '    <td>',"\n";
            echo '    <a href="'.apidb_fullurl('appview.php?versionId='.$oRow->versionId).'">'.$oRow->versionName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td align=center>[<a href="adminBugs.php?sub=delete',"\n";
            echo          '&buglinkId='.$oRow->linkId,"\n";
            echo          '&QueuedOnly='.$QueuedOnly,"\n";
            echo          '&ItemsPerPage='.$ItemsPerPage,"\n";
            echo          '&page='.$currentPage,"\n";
            echo          '">delete</a>]</td>',"\n";
            $bQueued = ($oRow->queued=="true")?true:false;
            if ($bQueued)
            {
                echo '<td align=center>[<a href="adminBugs.php?sub=unqueue',"\n";
                echo      '&buglinkId='.$oRow->linkId,"\n";
                echo      '&QueuedOnly='.$QueuedOnly,"\n";
                echo      '&ItemsPerPage='.$ItemsPerPage,"\n";
                echo      '&page='.$currentPage,"\n";
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
    display_page_range($currentPage, $pageRange, $totalPages, $_SERVER['PHP_SELF']."?ItemsPerPage=".$ItemsPerPage."&QueuedOnly=".$QueuedOnly);
    echo "</center>","\n";

    apidb_footer();
}
?>
