<?php
/********************************************************/
/* code to View and approve new application maintainers */
/********************************************************/

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/category.php");
require_once(BASE."include/maintainer.php");
require_once(BASE."include/application.php");
require_once(BASE."include/mail.php");

$aClean = array(); //array of filtered user input

$aClean['sSub'] = makeSafe( $_REQUEST['sSub'] );
$aClean['iMaintainerId'] = makeSafe( $_REQUEST['iMaintainerId'] );
$aClean['sAdd'] = makeSafe( $_REQUEST['sAdd'] );
$aClean['sReject'] = makeSafe( $_REQUEST['sReject'] );
$aClean['sReplyText'] = makeSafe( $_REQUEST['sReplyText'] );

if(!$_SESSION['current']->hasPriv("admin"))
    util_show_error_page_and_exit("Insufficient privileges.");


if ($aClean['sSub'])
{
    if ($aClean['iMaintainerId'])
    {
        //get data
        $sQuery = "SELECT maintainerId, appId, versionId,".
                     "userId, maintainReason, superMaintainer,".
                     "UNIX_TIMESTAMP(submitTime) as submitTime ".
                     "FROM appMaintainers WHERE maintainerId = '?' AND queued = 'true'";
        $hResult = query_parameters($sQuery, $aClean['iMaintainerId']);
        $oRow = mysql_fetch_object($hResult);
        $oUser = new User($oRow->userId);
        mysql_free_result($hResult);
    }
    else
    {
        //error no Id!
        util_show_error_page_and_exit("<p><b>QueueId Not Found!</b></p>");
    }

    //process according to which request was submitted and optionally the sub flag
    if (!$aClean['sAdd'] && !$aClean['sReject'] && $aClean['iMaintainerId'])
    {
        apidb_header("Admin Maintainer Queue");
        echo '<form name="sQform" action="adminMaintainerQueue.php" method="post" enctype="multipart/form-data">',"\n";

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

        $bFoundMaintainers = false;

        $bFirstDisplay = true; /* if false we need to fix up table rows appropriately */

        $oVersion = new Version($oRow->versionId);
        $aOtherUsers = $oVersion->getMaintainersUserIds();
        if($aOtherUsers)
        {
            $bFoundMaintainers = true;
            while(list($index, $iUserId) = each($aOtherUsers))
            {
                $oUser = new User($iUserId);
                if($bFirstDisplay)
                {
                    echo "<td>".$oUser->sRealname."</td></tr>\n";
                    $bFirstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."</td></tr>\n";
                }
            }
        }

        $aOtherUsers = getSuperMaintainersUserIdsFromAppId($oRow->appId);
        if($aOtherUsers)
        {
            $bFoundMaintainers = true;
            while(list($index, $iUserId) = each($aOtherUsers))
            {
                $oUser = new User($iUserId);
                if($bFirstDisplay)
                {
                    echo "<td>".$oUser->sRealname."*</td></tr>\n";
                    $bFirstDisplay = false;
                } else
                {
                    echo "<tr><td class=\"color0\"></td><td>".$oUser->sRealname."*</td></tr>\n";
                }
            }
        }

        if(!$bFoundMaintainers)
        {
            echo "<td>No other maintainers</td></tr>\n";
        }

        // Show which other apps the user maintains
        echo '<tr valign="top"><td class="color0"><b>This user also maintains these apps:</b></td>',"\n";

        $bFirstDisplay = true;
        $oUser = new User($oRow->userId);
        $aOtherApps = $oUser->getAppsMaintained();
        if($aOtherApps)
        {
            while(list($index, list($iAppIdOther, $iVersionIdOther, $bSuperMaintainerOther)) = each($aOtherApps))
            {
                $oApp = new Application($iAppIdOther);
                $oVersion = new Version($iVersionIdOther);
                if($bFirstDisplay)
                {
                    $bFirstDisplay = false;
                    if($bSuperMaintainerOther)
                        echo "<td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                } else
                {
                    if($bSuperMaintainerOther)
                        echo "<td class=color0></td><td>".$oApp->sName."*</td></tr>\n";
                    else
                        echo "<td class=color0></td><td>".$oApp->sName." ".$oVersion->sName."</td></tr>\n";
                }
            }
        } else
        {
            echo "<td>User maintains no other applications</td></tr>\n";
        }

        $oApp = new Application($oRow->appId);
        $oVersion = new Version($oRow->versionId);

        //app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".$oApp->sName."</td></tr>\n";

        //version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".$oVersion->sName."</td></tr>\n";
         
        //maintainReason
        echo '<tr valign=top><td class=color0><b>Maintainer request reason</b></td>',"\n";
        echo '<td><textarea name="sMaintainReason" rows=10 cols=35>'.$oRow->maintainReason.'</textarea></td></tr>',"\n";

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='sReplyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the users maintainer request here</textarea></td></tr>\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sAdd value=" Add maintainer to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sReject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '</table>',"\n";
        echo '<input type=hidden name="sSub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="iMaintainerId" value="'.$aClean['iMaintainerId'].'" />',"\n";  

        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminMaintainerQueue.php');
        echo "</form>";
        apidb_footer();
        exit;

    }
    else if ($aClean['sAdd'] && $aClean['iMaintainerId'])
    {
        /* create a new user object for the maintainer */
        $oMaintainerUser = new User($oRow->userId);

        /* add the user as a maintainer and return the statusMessage */
        $sStatusMessage = $oMaintainerUser->addAsMaintainer($oRow->appId, $oRow->versionId,
                                                          $oRow->superMaintainer,
                                                          $aClean['iMaintainerId']);
        //done
        addmsg("<p><b>$sStatusMessage</b></p>", 'green');
    }
    else if (($aClean['sReject'] || ($aClean['sSub'] == 'sReject')) && $aClean['iMaintainerId'])
    {
       $sEmail = $oUser->sEmail;
       if ($sEmail)
       {
           $oApp = new Application($oRow->appId);
           $oVersion = new Version($oRow->versionId);
           $sSubject =  "Application Maintainer Request Report";
           $sMsg  = "Your application to be the maintainer of ".$oApp->sName." ".$oVersion->sName." was rejected. ";
           $sMsg .= $aClean['sReplyText'];
           $sMsg .= "";
           $sMsg .= "-The AppDB admins\n";
                
           mail_appdb($sEmail, $sSubject ,$sMsg);
       }

       //delete main item
       $sQuery = "DELETE from appMaintainers where maintainerId = '?'";
       $hResult = query_parameters($sQuery, $aClean['iMaintainerId']);
       if(!$hResult) addmsg("unable to delete selected maintainer application", "red");
       echo html_frame_start("Delete maintainer application",400,"",0);
       if($hResult)
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
    echo '<form name="sQform" action="adminMaintainerQueue.php" method="post" enctype="multipart/form-data">',"\n";

    //get available maintainers
    $sQuery = "SELECT maintainerId, appId, versionId,".
                     "userId, maintainReason,".
                     "superMaintainer,".
                     "submitTime as submitTime ".
                     "FROM appMaintainers WHERE queued='true';";
    $hResult = query_parameters($sQuery);

    if(!$hResult || !mysql_num_rows($hResult))
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
        echo "    <td>Submission Date</td>\n";
        echo "    <td>Application Name</td>\n";
        echo "    <td>Version</td>\n";
        echo "    <td>Super maintainer?</td>\n";
        echo "    <td>Submitter</td>\n";
        echo "    <td>Action</td>\n";
        echo "</tr>\n\n";
        
        $iRowCount = 1;
        while($oRow = mysql_fetch_object($hResult))
        {
            $oUser = new User($oRow->userId);
            $oApp = new Application($oRow->appId);
            $oVersion = new Version($oRow->versionId);
            if ($iRowCount % 2 == 1) { $sBGColor = 'color0'; } else { $sBGColor = 'color1'; }
            echo "<tr class=$sBGColor>\n";
            echo "    <td>".print_date(mysqldatetime_to_unixtimestamp($oRow->submitTime))." &nbsp;</td>\n";
            echo "    <td>".$oApp->sName."</td>\n";

            if($oRow->superMaintainer)
            {
                echo "<td>N/A</td>\n";
                echo "<td>Yes</td>\n";
            } else
            {
  	        echo "<td>".$oVersion->sName." &nbsp;</td>\n";
                echo "<td>No</td>\n";
            }

            echo "    <td><a href=\"mailto:".$oUser->sEmail."\">".$oUser->sRealname."</a></td>\n";
            echo "    <td>[<a href=\"adminMaintainerQueue.php?sSub=view&iMaintainerId=$oRow->maintainerId\">answer</a>]</td>\n";
            echo "</tr>\n\n";
            $iRowCount++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
        echo "</form>";
        apidb_footer();

    }
        
}
?>
