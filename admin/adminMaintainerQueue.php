<?php
/********************************************************/
/* code to View and approve new application maintainers */
/********************************************************/

include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/category.php");
require(BASE."include/maintainer.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");

if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
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
        $result = query_appdb($query);
        $ob = mysql_fetch_object($result);
        $oUser = new User($ob->userId);
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
                $oUser = new User($userIdValue);
                if($firstDisplay)
                {
                    echo "<td>".$oUser->sRealname."</td></tr>\n";
                    $firstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."</td></tr>\n";
                }
            }
        }

        $other_users = getSuperMaintainersUserIdsFromAppId($ob->appId);
        if($other_users)
        {
            $foundMaintainers = true;
            while(list($index, list($userIdValue)) = each($other_users))
            {
                $oUser = new User($userIdValue);
                if($firstDisplay)
                {
                    echo "<td>".$oUser->sRealname."*</td></tr>\n";
                    $firstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."*</td></tr>\n";
                }
            }
        }

        if(!$foundMaintainers)
        {
            echo "<td>No other maintainers</td></tr>\n";
        }

        // Show which other apps the user maintains
        echo '<tr valign="top"><td class="color0"><b>This user also maintains these apps:</b></td>',"\n";

        $firstDisplay = true;
        $other_apps = getAppsFromUserId($ob->userId);
        if($other_apps)
        {
            while(list($index, list($appIdOther, $versionIdOther, $superMaintainerOther)) = each($other_apps))
            {
                $oApp = new Application($appIdOther);
                $oVersion = new Application($versionIdOther);
                if($firstDisplay)
                {
                    $firstDisplay = false;
                    if($superMaintainerOther)
                        echo "<td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                } else
                {
                    if($superMaintainerOther)
                        echo "<td class=color0></td><td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td class=color0></td><td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                }
            }
        } else
        {
            echo "<td>User maintains no other applications</td></tr>\n";
        }

        $oApp = new Application($ob->appId);
        $oVersion = new Application($ob->versionId);

        //app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".$oApp->sName."</td></tr>\n";

        //version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".$oVersion->sName."</td></tr>\n";
         
        //maintainReason
        echo '<tr valign=top><td class=color0><b>Maintainer request reason</b></td>',"\n";
        echo '<td><textarea name="maintainReason" rows=10 cols=35>'.$ob->maintainReason.'</textarea></td></tr>',"\n";

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='replyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the users maintainer request here</textarea></td></tr>\n";

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
        // insert the new entry into the maintainers list
        $query = "INSERT into appMaintainers VALUES(null,".
                    "$ob->appId,".
                    "$ob->versionId,".
                    "$ob->userId,".
                    "$ob->superMaintainer,".
                    "NOW());";

        if (query_appdb($query))
        {
            $statusMessage = "<p>The maintainer was successfully added into the database</p>\n";

            //delete the item from the queue
            query_appdb("DELETE from appMaintainerQueue where queueId = ".$_REQUEST['queueId'].";");
            $oApp = new Application($ob->appId);
            $oVersion = new Version($ob->versionId);
            //Send Status Email
            $sEmail = $oUser->sEmail;
            if ($sEmail)
            {
                $sSubject =  "Application Maintainer Request Report";
                $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." has been accepted. ";
                $sMsg .= $_REQUEST['replyText'];
                $sMsg .= "We appreciate your help in making the Application Database better for all users.\n\n";
                
                mail_appdb($sEmail, $sSubject ,$sMsg);
            }
        
            //done
            addmsg("<p><b>$statusMessage</b></p>", 'green');
        }
    }
    else if (($_REQUEST['reject'] || ($_REQUEST['sub'] == 'reject')) && $_REQUEST['queueId'])
    {
       $sEmail = $oUser->sEmail;
       if ($sEmail)
       {
           $oApp = new Application($ob->appId);
           $oVersion = new Application($ob->versionId);
           $sSubject =  "Application Maintainer Request Report";
           $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." was rejected. ";
           $sMsg .= $_REQUEST['replyText'];
           $sMsg .= "";
           $sMsg .= "-The AppDB admins\n";
                
           mail_appdb($sEmail, $sSubject ,$sMsg);
       }

       //delete main item
       $query = "DELETE from appMaintainerQueue where queueId = ".$_REQUEST['queueId'].";";
       $result = query_appdb($query,"unable to delete selected maintainer application");
       echo html_frame_start("Delete maintainer application",400,"",0);
       if($result)
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
    $result = query_appdb($query);

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
        echo "    <td><font color=white>Name</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Super maintainer?</font></td>\n";
        echo "    <td><font color=white>Submitter Email</font></td>\n";
        echo "    <td>&nbsp;</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($result))
        {
            $oUser = new User($ob->userId);
            $oApp = new Application($ob->appId);
            $oVersion = new Version($ob->versionId);
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
            echo "    <td><a href='adminMaintainerQueue.php?sub=view&queueId=$ob->queueId'>$ob->queueId</a></td>\n";
            echo "    <td>".$oUser->sRealName."</td>\n";
            echo "    <td>".$oApp->sName."</td>\n";

            if($ob->superMaintainer)
            {
                echo "<td>N/A</td>\n";
                echo "<td>Yes</td>\n";
            } else
            {
  	              echo "<td>".$oVersion->sName." &nbsp;</td>\n";
                echo "<td>No</td>\n";
            }

            echo "    <td>".$oUser->sEmail." &nbsp;</td>\n";
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
