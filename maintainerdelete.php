<?php
/*******************************/
/* code to delete a maintainer */
/*******************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/category.php");
require(BASE."include/application.php");

$aClean = array(); //array of filtered user input

$aClean['iAppId'] = makeSafe($_POST['iAppId']);
$aClean['iVersionId'] = makeSafe($_POST['iVersionId']);
$aClean['iConfirmed'] = makeSafe($_POST['iConfirmed']);
$aClean['iSuperMaintainer'] = makeSafe($_POST['iSuperMaintainer']);

if(!$_SESSION['current']->isLoggedIn())
    util_show_error_page("You need to be logged in to resign from being a maintainer.");


if($aClean['iConfirmed'])
{
    $oApp = new Application($aClean['iAppId']);
    if($aClean['iSuperMaintainer'])
    {
        apidb_header("You have resigned as super maintainer of ".$oApp->sName);
        $result = $_SESSION['current']->deleteMaintainer($oApp->iAppId, null); 
    } else
    {
        $oVersion = new Version($aClean['iVersionId']);
        apidb_header("You have resigned as maintainer of ".$oApp->sName." ".$oVersion->sName);
        $result = $_SESSION['current']->deleteMaintainer($oApp->iAppId, $oVersion->iVersionId);
    }
/*   echo html_frame_start("Removing",400,"",0);
*/
    if($result)
    {
        if($aClean['iSuperMaintainer'])
            echo "You were removed as a super maintainer of ".$oApp->sName;
        else
            echo "You were removed as a maintainer of ".$oApp->sName." ".$oVersion->sName;
    }
} else
{
    if($aClean['iSuperMaintainer'])
        apidb_header("Confirm super maintainer resignation of ".$oApp->sName);
    else
        apidb_header("Confirm maintainer resignation of ".$oApp->sName." ".$oVersion->sName);


    echo '<form name="sDeleteMaintainer" action="maintainerdelete.php" method="post" enctype="multipart/form-data">',"\n";

    echo html_frame_start("Confirm",400,"",0);
    echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
    echo "<input type=hidden name='iAppId' value={$aClean['iAppId']}>";
    echo "<input type=hidden name='iVersionId' value={$aClean['iVersionId']}>";
    echo "<input type=hidden name='iSuperMaintainer' value={$aClean['iSuperMaintainer']}>";
    echo "<input type=hidden name='iConfirmed' value=1>";

    if($aClean['iSuperMaintainer'])
    {
        echo "<tr><td>Are you sure that you want to be removed as a super maintainer of this application?</tr></td>\n";
        echo '<tr><td align=center><input type=submit value=" Confirm resignation as supermaintainer " class=button>', "\n";
    } else
    {
        echo "<tr><td>Are you sure that you want to be removed as a maintainer of this application?</tr></td>\n";
        echo '<tr><td align=center><input type=submit value=" Confirm resignation as maintainer " class=button>', "\n";
    }

    echo "</td></tr></table>";
}

echo html_frame_end();

apidb_footer();

?>
