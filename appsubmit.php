<?php
/************************************/
/* code to Submit and Resubmit Apps */
/************************************/
 
include("path.php");
require_once(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/mail.php");
require_once(BASE."include/testResults.php");

$aClean = array(); //array of filtered user input

$aClean['apptype'] = makeSafe($_REQUEST['apptype']);
$aClean['sub'] = makeSafe($_REQUEST['sub']);
$aClean['appId'] = makeSafe($_REQUEST['appId']);
$aClean['versionId'] = makeSafe($_REQUEST['versionId']);
$aClean['iTestingId'] = makeSafe($_REQUEST['iTestingId']);
$aClean['appVendorName'] = makeSafe($_REQUEST['appVendorName']);
$aClean['vendorId'] = makeSafe($_REQUEST['vendorId']);
$aClean['appWebpage'] = makeSafe($_REQUEST['appWebpage']);
$aClean['appKeywords'] = makeSafe($_REQUEST['appKeywords']);
$aClean['iDistributionId'] = makeSafe($_REQUEST['iDistributionId']);
$aClean['sDistribution'] = makeSafe($_REQUEST['sDistribution']);

function get_vendor_from_keywords($sKeywords)
{
    $aKeywords = explode(" *** ",$sKeywords);
    $iLastElt = (sizeOf($aKeywords)-1);
    return($aKeywords[$iLastElt]);
}

function newSubmition($errors)
{
    // show add to queue form
    echo '<form name="newApp" action="appsubmit.php" method="post">'."\n";
    echo "<p>This page is for submitting new applications to be added to the\n";
    echo "database. The application will be reviewed by an AppDB Administrator,\n";
    echo "and you will be notified via e-mail if it is added to the database or rejected.</p>\n";
    echo "<p><h2>Before continuing, please ensure that you have</h2>\n";
    echo "<ul>\n";
    if ($aClean['apptype'] == 1)
    {
        echo " <li>Searched for this application in the database.  Duplicate submissions will be rejected</li>\n";
        echo " <li>Really want to submit an application instead of a new version of an application\n";
        echo "   that is already in the database. If this is the case, browse to the application\n";
        echo "   and click on &#8216;Submit new version&#8217;</li>\n";
    }
    echo " <li>Entered a valid version for this application.  This is the application\n";
    echo "   version, NOT the Wine version (which goes in the testing results section of the template)</li>\n";
    echo " <li>Tested this application under Wine.  There are tens of thousands of applications\n";
    echo "   for Windows, we do not need placeholder entries in the database.  Please enter as complete \n";
    echo "   as possible testing results in the version template provided below</li>\n";
    echo "</ul></p>";
    echo "<p>Please do not forget to mention which Wine version you used, how well it worked\n";
    echo "and if any workarounds were needed.  Having app descriptions just sponsoring the app\n";
    echo "(yes, some vendors want to use the appdb for this) or saying &#8216;I haven&#8217;t tried this app with Wine&#8217; ";
    echo "will not help Wine development or Wine users.</p>\n";
    echo "<b><span style=\"color:red\">Please only submit applications/versions that you have tested.\n";
    echo "Submissions without testing information or not using the provided template will be rejected.\n";
    echo "If you are unable to see the in-browser editors below, please try Firefox, Mozilla or Opera browsers.\n</span></b>";
    echo "<p>After your application has been added, you will be able to submit screenshots for it, post";
    echo " messages in its forums or become a maintainer to help others trying to run the application.</p>";
}
//deny access if not logged on
if(!$_SESSION['current']->isLoggedIn())
{
    errorpage("Insufficient privilages to create application.  Are you sure you are logged in?");
    exit;
}


if ($aClean['sub'])
{
    if($aClean['apptype'] == 'application')
    {
        $oApp = new Application( $aClean['appId']);
        if($oApp->iAppId)
        {
            // if we are processing a queued application there MUST be an implicitly queued 
            // version to go along with it.  Find this version so we can display its information 
            // during application processing so the admin can make a better choice about 
            // whether to accept or reject the overall application 
            $sQuery = "Select versionId from appVersion where appId='".$aClean['appId']."';";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);

            // make sure the user has permission to view this version 
            if(!$_SESSION['current']->hasPriv("admin") && 
               (($oApp->queued=="false")?true:false) &&
               !$_SESSION['current']->isVersionSubmitter($oApp->AppId))
            {
                errorpage("Insufficient privileges.");
                exit;
            }

            $oVersion = new Version($oRow->versionId);
        } else
        {
            $oVersion = new Version();           
        }

    } 
    else if($aClean['apptype'] == 'version')
    {
        $oVersion = new Version($aClean['versionId']);

        // make sure the user has permission to view this version 
        if(!$_SESSION['current']->hasAppVersionModifyPermission($oVersion) && 
           (($oVersion->queued=="false")?true:false) &&
           !$_SESSION['current']->isVersionSubmitter($oVersion->versionId))
        {
            errorpage("Insufficient privileges.");
            exit;
        }
    }
    else
    {
        //error no Id!
        addmsg("Application Not Found!", "red");
        redirect($_SERVER['PHP_SELF']);
    }

    // Get the Testing results if they exist
    $sQuery = "Select testingId from testResults where versionId='".$oVersion->iVersionId."';";
    $hResult = query_appdb($sQuery);
    if($hResult)
    {
        $oRow = mysql_fetch_object($hResult);
        $oTest = new testData($oRow->testingId);
    }
    else
    {
        $oTest = new testData();
    }

    //process according to sub flag
    if ($aClean['sub'] == 'Submit')
    {
        $errors = "";
        $oVersion = new Version($aClean['versionId']);
        $oTest = new testData($aClean['iTestingId']);
        $errors .= $oVersion->CheckOutputEditorInput();
        $errors .= $oTest->CheckOutputEditorInput();
        $oVersion->GetOutputEditorValues();
        $oTest->GetOutputEditorValues();
        if ($aClean['apptype'] == "application") // application
        {
            $oApp = new Application($aClean['appId']);
            $errors .= $oApp->CheckOutputEditorInput();
            $oApp->GetOutputEditorValues(); // load the values from $_REQUEST 

            if(empty($errors))
            {
                if($aClean['appVendorName'])
                {
                    $aClean['vendorId']="";
                    //FIXME: fix this when we fix vendor submission
                    if($_SESSION['current']->hasPriv("admin"))
                    {
                        $oVendor = new Vendor();
                        $oVendor->create($aClean['appVendorName'],$aClean['appWebpage']);
                    }
                }
                //FIXME: remove this when we fix vendor submission
                $oApp->sKeywords = $aClean['appKeywords']." *** ".$aClean['appVendorName'];
                if(is_numeric($oApp->iAppId))
                {
                    $oApp->update();
                    $oApp->ReQueue();
                } else
                {
                    $oApp->create();
                }
                $oVersion->iAppId = $oApp->iAppId;
            }
        }

        if(!empty($errors))
        {
            addmsg("we've got Errors???:".$errors.":");
            $aClean['sub'] = 'view';
        } 
        else
        {
            if(is_numeric($oVersion->iVersionId))
            {
                $oVersion->update();
                $oVersion->ReQueue();
            }
            else
            {
                 $oVersion->create();
            }
            if(!$aClean['iDistributionId'])
            {
                $sDistribution = $aClean['sDistribution'];
                if( !empty($sDistribution) )
                {
                    $oDistribution = new distribution();
                    $oDistribution->sName = $sDistribution;
                    $oDistribution->create();
                    $oTest->iDistributionId = $oDistribution->iDistributionId;
                }
            }
            $oTest->iVersionId = $oVersion->iVersionId;
            if(is_numeric($oTest->iTestingId))
	    {
	        $oTest->update(true);
	        $oTest->ReQueue();
            } else 
            {
                $oTest->create();
            }
            redirect($_SERVER['PHP_SELF']);
        }
    }
    if ($aClean['sub'] == 'Delete')
    {
        if (($aClean['apptype'] == "application") && is_numeric($aClean['appId'])) // application
        {
            // get the queued versions that refers to the application entry we just removed
            // and delete them as we implicitly added a version entry when adding a new application
            $sQuery = "SELECT versionId FROM appVersion WHERE appVersion.appId = '".$aClean['appId']."' AND appVersion.queued = 'rejected';";
            $hResult = query_appdb($sQuery);
            if($hResult)
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $oVersion = new Version($oRow->versionId);
                    $oVersion->delete();
                }
            }

            // delete the application entry
            $oApp = new Application($aClean['appId']);
            $oApp->delete();
        } else if(($aClean['apptype'] == "version") && is_numeric($aClean['versionId']))  // version
        {
            $oVersion = new Version($aClean['versionId']);
            $oVersion->delete();
        }
        
        redirect($_SERVER['PHP_SELF']);
    }
    if ($aClean['sub'] == 'view')
    {
        $x = new TableVE("view");
        apidb_header("Application Queue");

        echo '<form name="qform" action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data">',"\n";
        echo '<input type="hidden" name="sub" value="Submit">',"\n"; 

        echo html_back_link(1,$_SERVER['PHP_SELF']);

        if($aClean['apptype'] == 'application') // application
        {  
            if ($oApp->sName != "")
            {
                echo html_frame_start("Potential duplicate applications in the database","90%","",0);
                perform_search_and_output_results($oApp->sName);
                echo html_frame_end("&nbsp;");
            }
            if(is_numeric($oApp->iAppId))
            {
            
            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the rejected application. \n";
            echo "You need to pick a category before submitting \n";
            echo "it into the database.\n";
            echo "<p>Click delete to remove the selected item from the queue. An e-mail will automatically be sent to the\n";
            echo "submitter to let them know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    
            } else
            {
                newSubmition('');
            }
            // vendor/alt vendor fields
            // if user selected a predefined vendorId:
            $iVendorId = $oApp->iVendorId;

            // If not, try for an exact match
            // Use the first match if we found one and clear out the vendor field,
            // otherwise don't pick a vendor
            // N.B. The vendor string is the last word of the keywords field !
            if(!$iVendorId)
            {
                $sVendor = get_vendor_from_keywords($oApp->sKeywords);
                $sQuery = "SELECT vendorId FROM vendor WHERE vendorname = '".$aClean['appVendorName']."';";
                $hResult = query_appdb($sQuery);
                if($hResult)
                {
                    $oRow = mysql_fetch_object($hResult);
                    $iVendorId = $oRow->vendorId;
                }
            }
            
            // try for a partial match
            if(!$iVendorId)
            {
                $sQuery = "select * from vendor where vendorname like '%".$aClean['appVendorName']."%';";
                $hResult = query_appdb($sQuery);
                if($hResult)
                {
                    $oRow = mysql_fetch_object($hResult);
                    $iVendorId = $oRow->vendorId;
                }
            }
            //vendor field
            if($iVendorId)
                $aClean['appVendorName'] = "";
        } else //app version
        { 
            if(is_numeric($oVersion->iVersionId))
            {
                $oAppForVersion = new Application($oVersion->iAppId);
                echo html_frame_start("Potential duplicate versions in the database for application: ".$oAppForVersion->sName,"90%","",0);
                display_approved_versions($oAppForVersion->aVersionsIds);
                echo html_frame_end("&nbsp;");

                //help
                echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
                echo "<p>This is the full view of the application version that has been Rejected. \n";

                echo "<b>App Version</b> This type of application will be nested under the selected application parent.\n";
                echo "<p>Click delete to remove the selected item from the queue.  An e-mail will automatically be sent to the\n";
                echo "submitter to let him know the item was deleted.</p>\n\n";        
                echo "</td></tr></table></div>\n\n";
            } else
            {
                newSubmition($errors);
            }
        }
        if(!empty($errors))
        {
            echo '<font color="red">',"\n";
            echo '<p class="red"> We found the following errors:</p><ul>'.$errors.'</ul>Please correct them.';
            echo '</font><br />',"\n";
            echo '<p></p>',"\n";
        }
        if(!($oTest->sTestedDate))
            $oTest->sTestedDate = date('Y-m-d H:i:s');

        if($aClean['apptype'] == 'application')
        {
            $oApp->OutputEditor($aClean['appVendorName']);
            $oVersion->OutputEditor(false, false);
        } else
        {
            $oVersion->OutputEditor(false, false);
        }

        $oTest->OutputEditor($aClean['sDistribution'],true);

        echo "<table width='100%' border=0 cellpadding=2 cellspacing=2>\n";

        if($aClean['apptype'] == 'application') // application
        {
            echo '<input type="hidden" name="apptype" value="application" />';
            if(is_numeric($oApp->iAppId))
            {
                echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
                echo '<input type=submit value=" Re-Submit App Into Database " class=button>&nbsp',"\n";
                echo '<input name="sub" type="submit" value="Delete" class="button" />',"\n";
            } else
            {
                echo '<tr valign=top><td class=color3 align=center colspan=2>',"\n";
                echo '<input type=submit value="Submit New Application" class="button"> </td></tr>',"\n";
            }
        } else // version
        {
            echo '<input type="hidden" name="apptype" value="version" />';
            echo '<input type="hidden" name="appId" value="'.$aClean['appId'].'" />';
            if(is_numeric($oVersion->iVersionId))
            {
                echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
                echo '<input type="submit" value="Re-Submit Version Into Database " class="button">&nbsp',"\n";
                echo '<input name="sub" type=submit value="Delete" class="button"></td></tr>',"\n";
            }
            else
            {
                echo '<tr valign=top><td class="color3" align="center" colspan="2">',"\n";
                echo '<input type=submit value="Submit New Version" class="button"> </td></tr>',"\n";	  
            }
        }
        echo '</table></form>',"\n";
        echo html_back_link(1, $_SERVER['PHP_SELF']);
        echo html_frame_end("&nbsp;");
        apidb_footer();
    }
    else 
    {
        // error no sub!
        addmsg("Internal Routine Not Found!!", "red");
        redirect($_SERVER['PHP_SELF']);
    } 
}
else // if ($aClean['sub']) is not defined, display the main app queue page 
{
    apidb_header("Resubmit application");

    // get queued apps that the current user should see
    $hResult = $_SESSION['current']->getAppRejectQueueQuery(true); // query for the app family 

    if(!$hResult || !mysql_num_rows($hResult))
    {
         //no apps in queue
        echo html_frame_start("Application Queue","90%");
        echo '<p><b>The Resubmit Application Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of applications waiting for re-submission, or to be deleted.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can delete or edit and\n";
        echo "re-submit it into the AppDB .<br>\n";
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        showAppList($hResult);
    }

     // get queued versions (only versions where application are not queued already)
     $hResult = $_SESSION['current']->getAppRejectQueueQuery(false); // query for the app version 

     if(!$hResult || !mysql_num_rows($hResult))
     {
         //no apps in queue
         echo html_frame_start("Version Queue","90%");
         echo '<p><b>The Resubmit Version Queue is empty.</b></p>',"\n";
         echo html_frame_end("&nbsp;");         
     }
     else
     {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of versions waiting for re-submission or deletion.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can delete or edit and re-submit it into \n";
        echo "the AppDB .<br>\n";
        echo "<p>Note that versions linked to application that have not been approved yet are not displayed in this list.</p>\n";
        echo "the AppDB.<br>\n";
        echo "</td></tr></table></div>\n\n";
    
        //show Version list
        showVersionList($hResult);
         

    }
    apidb_footer();
}

?>
