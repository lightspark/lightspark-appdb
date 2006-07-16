<?php
/**************************************************/
/* code to submit, view and resubmit Test Results */
/**************************************************/
 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/testData.php");
require_once(BASE."include/distributions.php");

$aClean = array(); //array of filtered user input

$aClean['sSub'] = makeSafe($_REQUEST['sSub']);
$aClean['iTestingId'] = makeSafe($_REQUEST['iTestingId']);
$aClean['iVersionId'] = makeSafe($_REQUEST['iVersionId']);
$aClean['iDistributionId'] = makeSafe($_REQUEST['iDistributionId']);
$aClean['sDistribution'] = makeSafe($_REQUEST['sDistribution']);

//deny access if not logged on
if(!$_SESSION['current']->isLoggedIn())
    util_show_error_page_and_exit("Insufficient privileges to create test results.  Are you sure you are logged in?");


if ($aClean['sSub'])
{
    $oTest = new testData($aClean['iTestingId']);
    if($aClean['iVersionId'])
        $oTest->iVersionId = $aClean['iVersionId'];
    $errors = "";

    // Submit or Resubmit the new testing results
    if (($aClean['sSub'] == 'Submit') || ($aClean['sSub'] == 'Resubmit'))
    {
        $errors = $oTest->CheckOutputEditorInput($_REQUEST);
        $oTest->GetOutputEditorValues($_REQUEST); // retrieve the values from the current $_REQUEST 
        if(empty($errors))
        {
            if(!$aClean['iDistributionId'])
            {
                if(!empty($aClean['sDistribution']) )
                {
                    $oDistribution = new distribution();
                    $oDistribution->sName = $aClean['sDistribution'];
                    $oDistribution->create();
                    $oTest->iDistributionId = $oDistribution->iDistributionId;
                }
            }
            if($aClean['sSub'] == 'Submit')
            {
	        $oTest->create();
            } else if($aClean['sSub'] == 'Resubmit')
            {
                $oTest->update(true);
	        $oTest->ReQueue();
            }
            util_redirect_and_exit($_SERVER['PHP_SELF']);
        } else 
        {
            $aClean['sSub'] = 'view';
        }
    }

    // Delete testing results
    if ($aClean['sSub'] == 'Delete')
    {
        if(is_numeric($aClean['iTestingId']))
        {
            $oTest = new testData($aClean['iTestingId']);
            $oTest->delete();
        }
        
        util_redirect_and_exit($_SERVER['PHP_SELF']);
    }

    // is this an old test?
    if(is_numeric($aClean['iTestingId']))
    {
        // make sure the user has permission to view this testing result
        $oVersion = new Version($oTest->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion)&&
           !(($_SESSION['current']->iUserId == $oTest->iSubmitterId) && !($oTest->sQueued == 'false')))
        {
            util_show_error_page_and_exit("Insufficient privileges.");
        } else
        $oVersion = new version($oTest->iVersionId);
    } else
    { 
        $oTest->iVersionId = $aClean['iVersionId'];
        $oVersion = new version($aClean['iVersionId']);       
        $oTest->sQueued = "new";
    }
    if ($aClean['sSub'] == 'view')
    {
        $oApp = new application($oVersion->iAppId);
        $sVersionInfo = $oApp->sName." ".$oVersion->sName;

        switch($oTest->sQueued)
        {
        case "new":
            apidb_header("Submit new testing results for ".$sVersionInfo);
            $oTest->sTestedDate = date('Y-m-d H:i:s');
            break;
        case "true":
            apidb_header("Edit new testing results for ".$sVersionInfo);
            break;
        case "rejected":
            apidb_header("Resubmit testing results for ".$sVersionInfo);
            break;
        case "False":
            apidb_header("Edit testing results for ".$sVersionInfo);
            break;
        default:
            apidb_header("Edit testing results for ");
        }
        echo '<form name="sQform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";


        //help
        echo "<p>This is the Screen for inputting testing information so that others looking at the database will know \n";
        echo "what was working or a particular release of Wine.</p>\n";
        echo "<p>Please be as detailed as you can.</p>\n";
        echo "<p>If you can not find your distribution on the list of existing Distributions please add it add it in the \n";
        echo "field provided.</p>\n\n";        

        if(!empty($errors))
        {
            echo '<font color="red">',"\n";
            echo '<p class="red"> We found the following errors:</p><ul>'.$errors.'</ul>Please correct them.';
            echo '</font><br />',"\n";
            echo '<p></p>',"\n";
        }
   
        // View Testing Details
        $oTest->OutputEditor($aClean['sDistribution'],true);

        echo '<a href="'.BASE."appview.php?iVersionId=".$oTest->iVersionId.'">Back to Version</a>';

        echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";


        // Submit Buttons
        switch($oTest->sQueued)
        {
        case "new":
            echo '<input name="sSub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            break;
        case "true":
        case "rejected":
        case "False":
             echo '<input name="sSub" type="submit" value="Resubmit" class="button" >&nbsp',"\n";
             echo '<input name="sSub" type="submit" value="Delete" class="button" >',"\n";
             break;
        }
        echo '</td></tr>',"\n";    
        echo "</form>";

        echo html_frame_end("&nbsp;");
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
    apidb_header("Testing Results");

    // Get queued testing results.
    $oTest = new TestData();
    $hResult = $oTest->getTestingQueue("true");

    if(!$hResult)
    {
        // no Tests in queue
        echo html_frame_start("Submitted Testing Results","90%");
        echo '<p><b>The Submitted Testing Results Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        // help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of Test Results waiting for submition, or to be deleted.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can delete or edit and\n";
        echo "re-submit it into the AppDB .<br>\n";
        echo "</td></tr></table></div>\n\n";

        $oTest->ShowListofTests($hResult,"Submitted Testing Results");
    }
    // Get rejected testing results.
    $hResult = $oTest->getTestingQueue("rejected");

    if(!$hResult || !mysql_num_rows($hResult))
    {
        //no Test Results in queue
        echo html_frame_start("Rejected Testing Results","90%");
        echo '<p><b>The Rejected Testng Results Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of Rejected Test Results waiting for re-submition or deletion.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can delete or edit and re-submit it into \n";
        echo "the AppDB .<br>\n";
        echo "</td></tr></table></div>\n\n";

        $oTest->ShowListofTests($hResult,"Rejected Testing Results");
    }
}
apidb_footer();       
?>
