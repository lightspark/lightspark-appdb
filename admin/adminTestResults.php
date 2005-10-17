<?php
/*************************************/
/* code to View and resubmit Apps    */
/*************************************/
 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");
require_once(BASE."include/testResults.php");
require_once(BASE."include/distributions.php");



if ($_REQUEST['sub'])
{
    if (!($_SESSION['current']->hasPriv("admin")) && 
        !($_SESSION['current']->hasAppVersionModifyPermission($_REQUEST['iVersionId'])))
    {
        errorpage("Insufficient privileges.");
        exit;
    }

    if(($_REQUEST['sub'] == 'Submit') || ($_REQUEST['sub'] == 'Save') ||
       ($_REQUEST['sub'] == 'Reject') || ($_REQUEST['sub'] == 'Delete'))
    {
        if(is_numeric($_REQUEST['iTestingId']))
        {
            $oTest = new testData($_REQUEST['iTestingId']);
            $oTest->GetOutputEditorValues();

            if($_REQUEST['sub'] == 'Submit')        // submit the testing results
            {
                $oTest->update(true);
                $oTest->unQueue();
            } else if($_REQUEST['sub'] == 'Save')   // save the testing results
            {
                $oTest->update();
            } else if($_REQUEST['sub'] == 'Reject') // reject testing results
            {
                $oTest->update(true);
                $oTest->Reject();
            } else if($_REQUEST['sub'] == 'Delete') // delete testing results
            {
                $oTest->delete();
            }

            redirect($_SERVER['PHP_SELF']);
        }
    }

    if(is_numeric($_REQUEST['iTestingId']))
    {
        $oTest = new testData($_REQUEST['iTestingId']);
    }

    if ($_REQUEST['sub'] == 'view')
    {
        switch($oTest->sQueued)
        {
        case "new":
            apidb_header("Submit new testing results");
            $_REQUEST['sTestedDate'] = date('Y-m-d H:i:s');
            break;
        case "true":
        case "rejected":
            apidb_header("Edit new testing results");
            break;
        case "False":
            apidb_header("Edit testing results");
            break;
        }
        echo '<form name="qform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";
        // View Testing Details
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
/*
            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the rejected application. \n";
            echo "You need to pick a category before submitting \n";
            echo "it into the database.\n";
            echo "<p>Click delete to remove the selected item from the queue. An email will automatically be sent to the\n";
            echo "submitter to let them know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    
*/    
        $oTest->OutputEditor();

        echo '<a href="'.$_SERVER['PHP_SELF'].'">Back</a>';

        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";

        // Submit Buttons
        switch($oTest->sQueued)
        {
        case "false":
            echo '<input name="sub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Delete" class="button" >',"\n";
            break;
        case "true":
            echo '<input name="sub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Reject" class="button" >&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Delete" class="button" >',"\n";
            break;
        case "rejected":
            echo '<input name="sub" type="submit" value="Submit" class="button" >&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Save" class="button" >&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Delete" class="button" >',"\n";
            break;
        }
        echo '</td></tr>',"\n";
        echo '</form>',"\n";

        echo html_frame_end("&nbsp;");
    }
    else 
    {
        // error no sub!
        addmsg("Internal Routine Not Found!!", "red");
        redirect($_SERVER['PHP_SELF']);
    } 
}
else // if ($_REQUEST['sub']) is not defined, display the Testing results queue page 
{
    $oTest = new TestData();
    apidb_header("Testing Results");

    // Get queued testing results.

    $hResult = $oTest->getTestingQueue("true");
    if(!$hResult)
    {
         //no apps in queue
        echo html_frame_start("Submitted Testing Results","90%");
        echo '<p><b>The Submitted Testng Results Queue is empty.</b></p>',"\n";
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
        echo '<p><b>The Rejected Testng Results Queue is empty.</b></p>',"\n";
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
