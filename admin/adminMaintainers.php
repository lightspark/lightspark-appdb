<?php
/*****************************************************************/
/* code to view and maintain the list of application maintainers */
/*****************************************************************/

/*
 * application environment
 */ 
require("path.php");
require(BASE."include/incl.php");

// deny access if not logged in
if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient privileges.");

apidb_header("Admin Maintainers");
echo '<form name="sQform" action="adminMaintainers.php" method="post" enctype="multipart/form-data">',"\n";

if (isset($aClean['sSub']))
{
    if($aClean['sSub'] == 'delete')
    {
        $oMaintainer = new maintainer($aClean['iMaintainerId']);
        $oMaintainer->delete();
        echo html_frame_start("Delete maintainer: ".$aClean['iMaintainerId'],400,"",0);
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
    $sQuery = "SELECT * FROM appMaintainers, user_list where appMaintainers.userId = user_list.userid";
    $sQuery.= " AND queued='false' ORDER BY realname;";
    $hResult = query_parameters($sQuery);

    if(!$hResult || !query_num_rows($hResult))
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
        $oldUserId = 0;
        while($oRow = query_fetch_object($hResult))
        {
            $oUser = new User($oRow->userId);
            $oApp = new application($oRow->appId);
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }

            /* if this is a new user we should print a header that has the aggregate of the applications */
            /* the user super maintains and versions they maintain */
            if($oRow->userId != $oldUserId)
            {
                $style = "border-top:thin solid;border-bottom:thin solid";

                echo "<tr class=color4>\n";
                echo "    <td style=\"$style;border-left:thin solid\">Maintainer summary</td>\n";
                if($oUser->sRealname == "")
                    echo "    <td style=\"$style\"><a href=\"mailto:".$oUser->sEmail."\">&nbsp</a></td>\n";
                else
                    echo "    <td style=\"$style\"><a href=\"mailto:".$oUser->sEmail."\">".$oUser->sRealname."</a></td>\n";

                $count = Maintainer::getMaintainerCountForUser($oUser, true);
                if($count == 0)
                    echo "    <td style=\"$style\">&nbsp</td>\n";
                else if($count <= 1)
                    echo "    <td style=\"$style\">".$count." app</td>\n";
                else
                    echo "    <td style=\"$style\">".$count." apps</td>\n";


                $count = Maintainer::getMaintainerCountForUser($oUser, false);
                if($count == 0)
                    echo "    <td style=\"$style\">&nbsp</td>\n";
                else if($count <= 1)
                    echo "    <td style=\"$style\">".$count." version</td>\n";
                else
                    echo "    <td style=\"$style\">".$count." versions</td>\n";

                echo "    <td align=\"center\" style=\"$style;border-right:thin solid\">&nbsp</td>\n";
                echo "</tr>\n\n";

                $oldUserId = $oRow->userId;
            }

            echo "<tr class=$bgcolor>\n";
            echo "    <td>".print_date(mysqldatetime_to_unixtimestamp($oRow->submitTime))." &nbsp;</td>\n";
            echo "    <td><a href=\"mailto:".$oUser->sEmail."\">".$oUser->sRealname."</a></td>\n";
            echo "    <td>".$oApp->objectMakeLink()."</td>\n";
            if($oRow->superMaintainer)
            {
                echo "    <td>*</td>\n";
            } else
            {
                $oVersion = new version($oRow->versionId);
                echo "    <td>".$oVersion->objectMakeLink()."</td>\n";
            }
            echo "    <td align=\"center\">[<a href='adminMaintainers.php?sSub=delete&iMaintainerId=$oRow->maintainerId'>delete</a>]</td>\n";
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
