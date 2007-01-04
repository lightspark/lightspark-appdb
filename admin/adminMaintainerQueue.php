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

/* The initial help text displayed in the admin's response field */
$sReplyTextHelp = "Enter a personalized reason for accepting or rejecting the user's maintainer request here";

/* If the admin's response text has not been modified, it should not be included in the e-mail sent to the rejected/accepted maintainer */
if($aClean['sReplyText'] == $sReplyTextHelp)
    $aClean['sReplyText'] = "";

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
        util_show_error_page_and_exit("<p><b>MaintainerId Not Found!</b></p>");
    }

    //process according to which request was submitted and optionally the sub flag
    if (!$aClean['sAdd'] && !$aClean['sReject'] && $aClean['iMaintainerId'])
    {
        apidb_header("Admin Maintainer Queue");
        echo '<form name="sQform" action="adminMaintainerQueue.php" method="post" enctype="multipart/form-data">',"\n";

        $x = new TableVE("view");

        //help
        Maintainer::ObjectDisplayQueueProcessingHelp();

        $oMaintainer = new maintainer($aClean['iMaintainerId']);
        $oMaintainer->OutputEditor();

        echo "<table border=1 cellpadding=2 cellspacing=0 style='margin-left:auto; margin-right:auto'>\n";

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='sReplyText' rows=10 cols=35>$sReplyTextHelp</textarea></td></tr>\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sAdd value=" Add maintainer to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=sReject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '<input type=hidden name="sSub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="iMaintainerId" value="'.$aClean['iMaintainerId'].'" />',"\n";  

        echo '</table>';

        echo html_back_link(1,'adminMaintainerQueue.php');
        echo "</form>";
        apidb_footer();
        exit;

    }
    else if ($aClean['sAdd'] && $aClean['iMaintainerId'])
    {
        /* create this maintainer object */
        $oMaintainer = new maintainer($aClean['iMaintainerId']);

        $sStatusMessage = $oMaintainer->unQueue($aClean['sReplyText']);

        //done
        addmsg("<p><b>$sStatusMessage</b></p>", 'green');
    }
    else if (($aClean['sReject'] || ($aClean['sSub'] == 'sReject')) && $aClean['iMaintainerId'])
    {
        $oMaintainer = new maintainer($aClean['iMaintainerId']);
        $hResult = $oMaintainer->reject($aClean['sReplyText']);
        
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

    /* retrieve a list of queued entries */
    $hResult = Maintainer::ObjectGetEntries(true);

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
        maintainer::ObjectDisplayQueueProcessingHelp();
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
        
        echo "<tr class=color4>\n";
        maintainer::ObjectOutputHeader();
        echo "    <td>Action</td>\n";
        echo "</tr>\n\n";
        
        $iRowCount = 1;
        while($oRow = mysql_fetch_object($hResult))
        {
            $oMaintainer = Maintainer::ObjectGetObjectFromObjectGetEntriesRow($oRow);

            if ($iRowCount % 2 == 1) { $sBGColor = 'color0'; } else { $sBGColor = 'color1'; }
            echo "<tr class=$sBGColor>\n";
            $oMaintainer->ObjectOutputTableRow();
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
