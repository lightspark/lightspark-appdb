<?php
/*************************************/
/* code to View and resubmit Apps    */
/*************************************/
 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/version.php");
require_once(BASE."include/testResults.php");
require_once(BASE."include/distributions.php");

$aClean = array();

$aClean['sSub'] = makeSafe($_REQUEST['sSub']);
$aClean['iTestingId'] = makeSafe($_REQUEST['iTestingId']);

if ($aClean['sSub'])
{
    $oTest = new testData($aClean['iTestingId']);
    $oVersion = new Version($oTest->iVersionId);
    if(!($_SESSION['current']->hasAppVersionModifyPermission($oVersion)))
        util_show_error_page_and_exit("Insufficient privileges.");

    if(($aClean['sSub'] == 'Submit') || ($aClean['sSub'] == 'Save') ||
       ($aClean['sSub'] == 'Reject') || ($aClean['sSub'] == 'Delete'))
    {
        if(is_numeric($aClean['iTestingId']))
        {
            $oTest = new testData($aClean['iTestingId']);
            $oTest->GetOutputEditorValues($_REQUEST);

            if($aClean['sSub'] == 'Submit')        // submit the testing results
            {
                $oTest->update(true);
                $oTest->unQueue();
            } else if($aClean['sSub'] == 'Save')   // save the testing results
            {
                $oTest->update();
            } else if($aClean['sSub'] == 'Reject') // reject testing results
            {
                $oTest->update(true);
                $oTest->Reject();
            } else if($aClean['sSub'] == 'Delete') // delete testing results
            {
                $oTest->delete();
            }

            util_redirect_and_exit($_SERVER['PHP_SELF']);
        }
    }

    if(is_numeric($aClean['iTestingId']))
    {
        $oTest = new testData($aClean['iTestingId']);
    }
    $oVersion = new Version($oTest->iVersionId);
    $oApp = new application($oVersion->iAppId);
    $sVersionInfo = $oApp->sName." ".$oVersion->sName;

    if ($aClean['sSub'] == 'view')
    {
        switch($oTest->sQueued)
        {
        case "true":
            apidb_header("Edit new testing results for ".$sVersionInfo);
            break;
        case "rejected":
            apidb_header("Edit rejected testing results for ".$sVersionInfo);
            break;
        case "false":
            apidb_header("Edit testing results for ".$sVersionInfo);
            break;
        }

        echo '<form name="sQform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";
        // View Testing Details
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        //help
        echo "<div align=center><table width='100%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        switch($oTest->sQueued)
        {
        case "false":
            echo "<p>This Testing result has already been verified and accepted int the database. \n";
            echo "You can edit the entry and the save it back to the database by clicking on save. \n";
            break;
        case "true":
            echo "<p>This Testing result has not yet been and accepted into the database. \n";
            echo "You can edit the entry and ether submit it into the database by clicking on Submit or you can reject it \n";
            echo "for further editing by the submitter by clicking on Reject. \n";
            break;
        case "rejected":
            echo "<p>This Testing result has been rejected and is awaiting further information from the submitter. \n";
            echo "You can edit the entry and ether submit it into the database by clicking on Submit or you can save it \n";
            echo "for further editing by the submitter by clicking on Save. \n";
            break;
        }
        echo "<p>Click delete to remove it entirly from the database. An email will automatically be sent to the\n";
        echo "submitter to let them know the item was deleted.</p>\n\n";        
        echo "</td></tr></table></div>\n\n";    

        echo html_back_link(1, $_SERVER['PHP_SELF']);

        $oTest->OutputEditor();
        echo html_frame_start("Reply text", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
        echo '<td><textarea name="sReplyText" style="width: 100%" cols="80" rows="10"></textarea></td></tr>',"\n";

        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";

        // Submit Buttons
        switch($oTest->sQueued)
        {
        case "false":
            echo '<input name="sSub" type="submit" value="Save" class="button" >&nbsp',"\n";
            echo '<input name="sSub" type="submit" value="Delete" class="button" >',"\n";
            break;
        case "true":
            echo '<input name="sSub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            echo '<input name="sSub" type="submit" value="Reject" class="button" >&nbsp',"\n";
            echo '<input name="sSub" type="submit" value="Delete" class="button" >',"\n";
            break;
        case "rejected":
            echo '<input name="sSub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            echo '<input name="sSub" type="submit" value="Save" class="button" >&nbsp',"\n";
            echo '<input name="sSub" type="submit" value="Delete" class="button" >',"\n";
            break;
        }
        echo '</td></tr>',"\n";
        echo '</table>',"\n";
        echo '</form>',"\n";
        echo html_frame_end();

        echo html_back_link(1, $_SERVER['PHP_SELF']);
    }
    else 
    {
        // error no sub!
        addmsg("Internal Routine Not Found!!", "red");
        util_redirect_and_exit($_SERVER['PHP_SELF']);
    } 
}
else // if ($aClean['sSub']) is not defined, display the Testing results queue page 
{
    $oTest = new TestData();
    apidb_header("Testing Results");

    // Get queued testing results.

    $hResult = $oTest->getTestingQueue("true");
    if(!$hResult)
    {
         //no apps in queue
        echo html_frame_start("Submitted Testing Results","90%");
        echo '<p><b>The Submitted Testing Results Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of test results waiting for submition, rejection or deletion.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can Submit it into \n";
        echo "the AppDB reject it or delete it.<br>\n";
        echo "</td></tr></table></div>\n\n";

        $oTest->ShowListofTests($hResult,"Submitted Testing Results");
    }

    // Get rejected testing results.
    $hResult = $oTest->getTestingQueue("rejected");
    if(!$hResult || !mysql_num_rows($hResult))
    {
        //no rejected test results in queue
        echo html_frame_start("Rejected Testing Results","90%");
        echo '<p><b>The Rejected Testing Results Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of test results that have been rejected for some reason.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can Submit it into \n";
        echo "the AppDB, edit and save it or delete it.<br>\n";
        echo "</td></tr></table></div>\n\n";

        $oTest->ShowListofTests($hResult,"Rejected Testing Results");
    }
}
apidb_footer();       
?>
