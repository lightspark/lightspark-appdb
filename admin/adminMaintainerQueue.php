<?php
/********************************************************/
/* code to View and approve new application maintainers */
/********************************************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");
require_once(BASE."include/"."maintainer.php");

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

if ($_REQUEST['sub'])
{
    if ($_REQUEST['queueId'])
    {
        //get data
        $query = "SELECT queueId, appId, versionId,".
                     "userId, maintainReason, superMaintainer,".
                     "UNIX_TIMESTAMP(submitTime) as submitTime ".
                     "FROM appMaintainerQueue WHERE queueId = ".$_REQUEST['queueId'].";";
        $result = mysql_query($query);
        $ob = mysql_fetch_object($result);
        mysql_free_result($result);
    }
    else
    {
        //error no Id!
        errorpage("<p><b>QueueId Not Found!</b></p>");
    }

    //process according to which request was submitted and optionally the sub flag
    if (!$_REQUEST['add'] && !$_REQUEST['reject'] && $_REQUEST['queueId'])
    {
        apidb_header("Admin Maintainer Queue");
        echo '<form name="qform" action="adminMaintainerQueue.php" method="post" enctype="multipart/form-data">',"\n";

        $x = new TableVE("view");

        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a maintainer request is rejected.\n";
        echo "Its not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    

        //view application details
        echo html_frame_start("New Maintainer Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // Show the other maintainers of this application, if there are any
        echo '<tr valign=top><td class=color0><b>Other maintainers of this app:</b></td>',"\n";

        $foundMaintainers = false;

        $firstDisplay = true; /* if false we need to fix up table rows appropriately */

        $other_users = getMaintainersUserIdsFromAppIdVersionId($ob->appId, $ob->versionId);
        if($other_users)
        {
            $foundMaintainers = true;
            while(list($index, list($userIdValue)) = each($other_users))
            {
                if($firstDisplay)
                {
                    echo "<td>".lookupUsername($userIdValue)."</td></tr>\n";
                    $firstDisplay = false;
                } else
                {
                    echo "<tr><td class=color0></td><td>".lookupUsername($userIdValue)."</td></tr>\n";
                }
            }
        }

        $other_users = getSuperMaintainersUserIdsFromAppId($ob->appId);
        if($other_users)
        {
            $foundMaintainers = true;
            while(list($index, list($userIdValue)) = each($other_users))
            {
                if($firstDisplay)
                {
                    echo "<td>".lookupUsername($userIdValue)."*</td></tr>\n";
                    $firstDisplay = false;
                } else
                {
                    echo "<tr><td class=color0></td><td>".lookupUsername($userIdValue)."*</td></tr>\n";
                }
            }
        }

        if(!$foundMaintainers)
        {
            echo "<td>No other maintainers</td></tr>\n";
        }

        // Show which other apps the user maintains
        echo '<tr valign=top><td class=color0><b>This user also maintains these apps:</b></td>',"\n";

        $firstDisplay = true;
        $other_apps = getAppsFromUserId($ob->userId);
        if($other_apps)
        {
            while(list($index, list($appIdOther, $versionIdOther, $superMaintainerOther)) = each($other_apps))
            {
                if($firstDisplay)
                {
                    $firstDisplay = false;
                    if($superMaintainerOther)
                        echo "<td>".appIdToName($appIdOther)."*</td></tr>\n";
                    else
                        echo "<td>".appIdToName($appIdOther).versionIdToName($versionIdOther)."</td></tr>\n";
                } else
                {
                    if($superMaintainerOther)
                        echo "<td class=color0></td><td>".appIdToName($appIdOther)."*</td></tr>\n";
                    else
                        echo "<td class=color0></td><td>".appIdToName($appIdOther).versionIdToName($versionIdOther)."</td></tr>\n";
                }
            }
        } else
        {
            echo "<td>User maintains no other applications</td></tr>\n";
        }
        
        //app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".appIdToName($ob->appId)."</td></tr>\n";

        //version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".versionIdToName($ob->versionId)."</td></tr>\n";
         
        //maintainReason
        echo '<tr valign=top><td class=color0><b>Maintainer request reason</b></td>',"\n";
        echo '<td><textarea name="maintainReason" rows=10 cols=35>'.stripslashes($ob->maintainReason).'</textarea></td></tr>',"\n";

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='replyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the users maintainer request here</textarea></td></tr>\n";

        //echo '<tr valign=top><td bgcolor=class=color0><b>Email</b></td>,"\n";
        //echo '<td><input type=text name="queueEmail" value="'.$ob->queueEmail.'" size=20></td></tr>',"\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=add value=" Add maintainer to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=reject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '</table>',"\n";
        echo '<input type=hidden name="sub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="queueId" value="'.$_REQUEST['queueId'].'" />',"\n";  

        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminMaintainerQueue.php');
        echo "</form>";
        apidb_footer();
        exit;

    }
    else if ($_REQUEST['add'] && $_REQUEST['queueId'])
    {
        //add this user, app and version to the database
        $statusMessage = "";
        $goodtogo = 0;

        // insert the new entry into the maintainers list
        $query = "INSERT into appMaintainers VALUES(null,".
                    "$ob->appId,".
                    "$ob->versionId,".
                    "$ob->userId,".
                    "$ob->superMaintainer,".
                    "NOW());";

        if (mysql_query($query))
        {
            $statusMessage = "<p>The maintainer was successfully added into the database</p>\n";

            //delete the item from the queue
            mysql_query("DELETE from appMaintainerQueue where queueId = ".$_REQUEST['queueId'].";");

            $goodtogo = 1; /* set to 1 so we send the response email */
        } else
        {
           //error
           $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        }

        //Send Status Email
        if (lookupEmail($ob->userId) && $goodtogo)
        {
                $ms =  "Application Maintainer Request Report\n";
                $ms .= "----------------------------------\n\n";
                $ms .= "Your application to be the maintainer of ".appIdToName($ob->appId).versionIdToName($ob->versionId)." has been accepted. ";
                $ms .= $_REQUEST['replyText'];
                $ms .= "We appreciate your help in making the Application Database better for all users.\n\n";
                $ms .= "Thanks!\n";
                $ms .= "-The AppDB admins\n";
                
                mail(stripslashes(lookupEmail($ob->userId)),'[AppDB] Maintainer Request Report',$ms);
        }
        
        //done
        addmsg("<p><b>$statusMessage</b></p>", 'green');
    }
    else if (($_REQUEST['reject'] || ($_REQUEST['sub'] == 'reject')) && $_REQUEST['queueId'])
    {
       if (lookupEmail($ob->userId))
       {
           $ms =  "Application Maintainer Request Report\n";
           $ms .= "----------------------------------\n\n";
           $ms .= "Your application to be the maintainer of ".appIdToName($ob->appId).versionIdToName($ob->versionId)." was rejected. ";
           $ms .= $_REQUEST['replyText'];
           $ms .= "";
           $ms .= "-The AppDB admins\n";
                
           mail(stripslashes(lookupEmail($ob->userId)),'[AppDB] Maintainer Request Report',$ms);
       }

       //delete main item
       $query = "DELETE from appMaintainerQueue where queueId = ".$_REQUEST['queueId'].";";
       $result = mysql_query($query);
       echo html_frame_start("Delete maintainer application",400,"",0);
       if(!$result)
       {
           //error
           echo "<p>Internal Error: unable to delete selected maintainer application!</p>\n";
       }
       else
       {
           //success
           echo "<p>Maintainer application was successfully deleted from the Queue.</p>\n";
       }
       echo html_frame_end("&nbsp;");
       echo html_back_link(1,'adminMaintainerQueue.php');
    }
    else
    {
        //error no sub!
        addmsg('<p><b>Internal Routine Not Found!</b></p>', 'red');        
    }
}
/* display the list of all outstanding maintainer requests */
{
    apidb_header("Admin Maintainer Queue");
    echo '<form name="qform" action="adminMaintainerQueue.php" method="post" enctype="multipart/form-data">',"\n";

    //get available maintainers
    $query = "SELECT queueId, appId, versionId,".
                     "userId, maintainReason,".
                     "superMaintainer,".
                     "UNIX_TIMESTAMP(submitTime) as submitTime ".
                     "from appMaintainerQueue;";
    $result = mysql_query($query);

    if(!$result || !mysql_num_rows($result))
    {
         //no apps in queue
        echo html_frame_start("","90%");
        echo '<p><b>The Maintainer Queue is empty.</b></p>',"\n";
        echo '<p>There is nothing for you to do. Check back later.</p>',"\n";        
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is a list of users that are asking to become application maintainers.\n";
        echo "Please read carefully the reasons they give for wanting to be an application maintainer.\n";
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
        
        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Queue Id</font></td>\n";
        echo "    <td><font color=white>Username</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Super maintainer?</font></td>\n";
        echo "    <td><font color=white>Submitter Email</font></td>\n";
        echo "    <td>&nbsp;</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($result))
        {
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
            echo "    <td><a href='adminMaintainerQueue.php?sub=view&queueId=$ob->queueId'>$ob->queueId</a></td>\n";
            echo "    <td>".lookupUsername($ob->userId)."</td>\n";
            echo "    <td>".appIdToName($ob->appId)."</td>\n";

            if($ob->superMaintainer)
            {
                echo "<td>N/A</td>\n";
                echo "<td>Yes</td>\n";
            } else
            {
                echo "<td>".versionIdToName($ob->versionId)." &nbsp;</td>\n";
                echo "<td>No</td>\n";
            }

            echo "    <td>".lookupEmail($ob->userId)." &nbsp;</td>\n";
            echo "    <td>[<a href='adminMaintainerQueue.php?sub=reject&queueId=$ob->queueId'>reject</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
        echo "</form>";
        apidb_footer();

    }
        
}



?>
