<?

/* code to view and maintain the list of application maintainers */

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");
require(BASE."include/"."maintainer.php");

//deny access if not logged in
if(!loggedin())
{
    errorpage("You need to be logged in to use this page.");
    exit;
} else if (!havepriv("admin"))
{
    errorpage("You must be an administrator to use this page.");
    exit;
}

apidb_header("Admin Maintainers");
echo '<form name="qform" action="adminMaintainers.php" method="post" enctype="multipart/form-data">',"\n";

if ($sub)
{
    if($sub == 'delete')
    {
        $query = "DELETE FROM appMaintainers WHERE maintainerId = $maintainerId;";
        echo "$query";
        $result = mysql_query($query);
        echo html_frame_start("Delete maintainer: $maintainerId",400,"",0);
        if(!$result)
        {
            //error
            echo "<p>Internal Error: unable to delete selected maintainer!</p>\n";
        }
        else
        {
            //success
            echo "<p>Maintainer was successfully deleted</p>\n";
        }
        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminMaintainers.php');
    }
} else
{
    //get available maintainers
    $query = "SELECT maintainerId, appId, versionId,".
        "userId, UNIX_TIMESTAMP(submitTime) as submitTime ".
        "from appMaintainers;";
    $result = mysql_query($query);

    if(!$result || !mysql_num_rows($result))
    {
        //no apps in queue
        echo html_frame_start("","90%");
        echo '<p><b>There are no application maintainers.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";

        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Username</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td></td>\n";
        echo "    <td>&nbsp;</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($result))
        {
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
            echo "    <td>".lookupUsername($ob->userId)."</td>\n";
            echo "    <td>".appIdToName($ob->appId)."</td>\n";
            echo "    <td><a href='".$apidb_root."appview.php?appId=$ob->appId&versionId=$ob->versionId'>".versionIdToName($ob->versionId)."</a>&nbsp;</td>\n";
            echo "    <td>".lookupEmail($ob->userId)." &nbsp;</td>\n";
            echo "    <td>[<a href='adminMaintainers.php?sub=delete&maintainerId=$ob->maintainerId'>delete</a>]</td>\n";
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
