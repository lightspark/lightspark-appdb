<?php
/*****************************************************************/
/* code to view and maintain the list of application maintainers */
/*****************************************************************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/incl.php");

// deny access if not logged in
if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}

apidb_header("Admin Maintainers");
echo '<form name="qform" action="adminMaintainers.php" method="post" enctype="multipart/form-data">',"\n";

if ($_REQUEST['sub'])
{
    if($_REQUEST['sub'] == 'delete')
    {
        $sQuery = "DELETE FROM appMaintainers WHERE maintainerId = ".$_REQUEST['maintainerId'].";";
        echo "$sQuery";
        $hResult = query_appdb($sQuery);
        echo html_frame_start("Delete maintainer: ".$_REQUEST['maintainerId'],400,"",0);
        if($hResult)
        {
            // success
            echo "<p>Maintainer was successfully deleted</p>\n";
        }
        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminMaintainers.php');
    }
} else
{
    // get available maintainers
    $sQuery = "SELECT * FROM appMaintainers;";
    $hResult = query_appdb($sQuery);

    if(!$hResult || !mysql_num_rows($hResult))
    {
        // no apps
        echo html_frame_start("","90%");
        echo '<p><b>There are no application maintainers.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        // show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Maintainer</font></td>\n";
        echo "    <td><font color=white>Application</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td align=\"center\">Action</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($hResult))
        {
            $oUser = new User($ob->userId);
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".print_date(mysqldatetime_to_unixtimestamp($ob->submitTime))." &nbsp;</td>\n";
            echo "    <td><a href=\"mailto:".$oUser->sEmail."\">".$oUser->sRealname."</a></td>\n";
            if($ob->superMaintainer)
            {
                echo "    <td><a href='".BASE."appview.php?appId=$ob->appId'>".lookup_app_name($ob->appId)."</a></td>\n";
                echo "    <td>*</td>\n";
            } else
            {
                echo "    <td><a href='".BASE."appview.php?appId=$ob->appId'>".lookup_app_name($ob->appId)."</a></td>\n";
                echo "    <td><a href='".BASE."appview.php?versionId=$ob->versionId'>".lookup_version_name($ob->versionId)."</a>&nbsp;</td>\n";
            }
            echo "    <td align=\"center\">[<a href='adminMaintainers.php?sub=delete&maintainerId=$ob->maintainerId'>delete</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }
}

echo "</form>";
apidb_footer();
?>
