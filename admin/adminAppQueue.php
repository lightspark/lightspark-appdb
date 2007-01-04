<?php
/*************************************/
/* code to View and approve new Apps */
/*************************************/
 
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/tableve.php");
require_once(BASE."include/application.php");
require_once(BASE."include/testData.php");

$aClean = array(); //array of filtered user input

$aClean['iTestingId'] = makeSafe($_REQUEST['iTestingId']);
$aClean['sSub'] = makeSafe($_REQUEST['sSub'] );
$aClean['sAppType'] = makeSafe($_REQUEST['sAppType']);
$aClean['iAppId'] = makeSafe($_REQUEST['iAppId']);
$aClean['iVersionId'] = makeSafe($_REQUEST['iVersionId']);
$aClean['sAppVendorName'] = makeSafe($_REQUEST['sAppVendorName']);
$aClean['iAppVendorId']  = makeSafe($_REQUEST['iAppVendorId']);
$aClean['sAppWebpage'] = makeSafe($_REQUEST['sAppWebpage']);
$aClean['iAppIdMergeTo'] = makeSafe($_REQUEST['iAppIdMergeTo']);
$aClean['sReplyText'] = makeSafe($_REQUEST['sReplyText']);
$aClean['iVersionIdMergeTo'] = makeSafe($_REQUEST['iVersionIdMergeTo']);
$aClean['sDistribution'] = makeSafe($_REQUEST['sDistribution']);

function get_vendor_from_keywords($sKeywords)
{
    $aKeywords = explode(" *** ",$sKeywords);
    $iLastElt = (sizeOf($aKeywords)-1);
    return($aKeywords[$iLastElt]);
}

/* allows the admin to click on a row and mark the current application as a duplicate */
/* of the selected application */
function outputSearchTableForDuplicateFlagging($currentAppId, $hResult)
{
    if(($hResult == null) || (mysql_num_rows($hResult) == 0))
    {
        // do nothing, we have no results
    } else
    {
        echo html_frame_start("","98%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=1>\n\n";

        $c = 0;
        while($oRow = mysql_fetch_object($hResult))
        {
            //skip if a NONAME
            if ($oRow->appName == "NONAME") { continue; }
		
            //set row color
            $sBgColor = ($c % 2) ? 'color0' : 'color1';
		
            //count versions
            $query = query_parameters("SELECT count(*) as versions FROM appVersion WHERE ".
                                      "appId = '?' AND versionName != 'NONAME'",
                                      $oRow->appId);
            $oVersionCount = mysql_fetch_object($query);

            //display row
            echo "<tr class=$sBgColor>\n";
            /* map the merging of the current app to the app we are displaying in the table */
            echo "    <td>".html_ahref($oRow->appName,"adminAppQueue.php?sSub=duplicate&sAppType=application&iAppId=".$currentAppId."&iAppIdMergeTo=".$oRow->appId)."</td>\n";
            echo "    <td>$oVersionCount->versions versions &nbsp;</td>\n";
            echo "</tr>\n\n";

            $c++;    

            //set row color
            $sBgColor = ($c % 2) ? 'color0' : 'color1';

            /* add the versions to the table */
            $oApp = new Application($oRow->appId);
            foreach($oApp->aVersionsIds as $iVersionId)
            {
                $oVersion = new Version($iVersionId);
                echo "<tr class=$sBgColor><td></td><td>".$oVersion->sName."</td></tr>\n";
            }

            $c++;    
        }

        echo "</table>\n\n";
        echo html_frame_end();
    }
}

function display_move_test_to_versions_table($aVersionsIds,$icurrentVersionId)
{
    if ($aVersionsIds)
    {
        echo html_frame_start("","98%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"1\">\n\n";

        echo "<tr class=color4>\n";
        echo "    <td width=\"80\">Version</td>\n";
        echo "    <td>Description</td>\n";
        echo "    <td width=\"80\">Rating</td>\n";
        echo "    <td width=\"80\">Wine version</td>\n";
        echo "    <td width=\"40\">Comments</td>\n";
        echo "</tr>\n\n";
      
        $c = 0;
        foreach($aVersionsIds as $iVersionId)
        {
            $oVersion = new Version($iVersionId);
            if ($oVersion->sQueued == 'false')
            {
                // set row color
                $sBgColor = ($c % 2 == 0) ? "color0" : "color1";

                //display row
                echo "<tr class=$sBgColor>\n";
                echo "    <td>".html_ahref($oVersion->sName,"adminAppQueue.php?sSub=movetest&sAppType=version&iVersionId=".$icurrentVersionId."&iVersionIdMergeTo=".$oVersion->iVersionId)."</td>\n";

                echo "    <td>".util_trim_description($oVersion->sDescription)."</td>\n";
                echo "    <td align=center>".$oVersion->sTestedRating."</td>\n";
                echo "    <td align=center>".$oVersion->sTestedRelease."</td>\n";
                echo "    <td align=center>".Comment::get_comment_count_for_versionid($oVersion->iVersionId)."</td>\n";
                echo "</tr>\n\n";

                $c++;   
            }
        }
        echo "</table>\n";
        echo html_frame_end("Click the Version Name to view the details of that Version");
    }
}


//deny access if not logged in or not a super maintainer of any applications
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isSuperMaintainer())
    util_show_error_page_and_exit("Insufficient privileges.");

$oTest = new testData($aClean['iTestingId']);

if ($aClean['sSub'])
{
    if($aClean['sAppType'] == 'application')
    {
        /* make sure the user is authorized to view this application request */
        if(!$_SESSION['current']->hasPriv("admin"))
            util_show_error_page_and_exit("Insufficient privileges.");

        $oApp = new Application($aClean['iAppId']);

        /* Check that the application actually exists */
        if(!$oApp->iAppId)
            util_show_error_page_and_exit("There is no application with that ID.");

        // if we are processing a queued application there MUST be an implicitly queued 
        // version to go along with it.  
        $hResult = query_parameters("SELECT versionId from appVersion where appId='?';", $aClean['iAppId']);
        $oVersionRow = mysql_fetch_object($hResult);

        $oVersion = new Version($oVersionRow->versionId);

    } 
    else if($aClean['sAppType'] == 'version')
    {
        /* make sure the user has permission to view this version */
        $oVersion = new Version($aClean['iVersionId']);
        if(!$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
            util_show_error_page_and_exit("Insufficient privileges.");

    } else
    {
        //error no Id!
        addmsg("Application Not Found!", "red");
        util_redirect_and_exit(apidb_fullurl("admin/adminAppQueue.php"));
    }

    // Get the Testing results if they exist

    $hResult = query_parameters("SELECT testingId from testResults where versionId='?'", $oVersion->iVersionId);
    if($hResult)
    {
        $oRow = mysql_fetch_object($hResult);
        $oTest = new testData($oRow->testingId);
    }
    else
    {
        $oTest = new testData();
    }

    if($aClean['sSub'] == 'add')
    {
        $oVersion = new Version($aClean['iVersionId']);
        $oTest = new testData($aClean['iTestingId']);
        $oVersion->GetOutputEditorValues($_REQUEST);
        $oTest->GetOutputEditorValues($_REQUEST);
        if ($aClean['sAppType'] == "application") // application
        {
            $oApp = new Application($aClean['iAppId']);
            $oApp->GetOutputEditorValues($_REQUEST); // load the values from $_REQUEST 
            // add new vendor
            if($aClean['sAppVendorName'] and !$aClean['iAppVendorId'])
            {
                $oVendor = new Vendor();
                $oVendor->create($aClean['sAppVendorName'],$aClean['sAppWebpage']);
                $oApp->iVendorId = $oVendor->iVendorId;
            }
            $oApp->update(true);
            $oApp->unQueue();
        }
        $oVersion->update(true);
        $oVersion->unQueue();
        $oTest->update(true);
        $oTest->unQueue();
        util_redirect_and_exit($_SERVER['PHP_SELF']);
    }
    else if ($aClean['sSub'] == 'duplicate')
    {
        if(is_numeric($aClean['iAppIdMergeTo']))
        {
            /* move this version submission under the existing app */
            $oVersion->iAppId = $aClean['iAppIdMergeTo'];
            $oVersion->update();

            /* delete the appId that is the duplicate */
            $aClean['sReplyText'] = "Your Vesion information was moved to an existing Application";
            $oAppDelete = new Application($oApp->iAppId);
            $oAppDelete->delete();
        }

        /* redirect back to the main page */
        util_redirect_and_exit(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($aClean['sSub'] == 'movetest')
    {
        if(is_numeric($aClean['iVersionIdMergeTo']))
        {
            // move this Test submission under the existing version
            $oTest->iVersionId = $aClean['iVersionIdMergeTo'];
            $oTest->update();

            // delete the Version entry
            $aClean['sReplyText'] = "Your Test results were moved to existing version";
            $oVersion = new Version($aClean['iVersionId']);
            $oVersion->delete();
        }

        // redirect back to the main page
        util_redirect_and_exit(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($aClean['sSub'] == 'Delete')
    {

        if (($aClean['sAppType'] == "application") && is_numeric($aClean['iAppId'])) // application
        {
            // delete the application entry
            $oApp = new Application($aClean['iAppId']);
            $oApp->delete();

        } else if(($aClean['sAppType'] == "version") && is_numeric($aClean['iVersionId']))  // version

        {
            // delete the Version entry
            $oVersion = new Version($aClean['iVersionId']);
            $oVersion->delete();
        }

        util_redirect_and_exit(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($aClean['sSub'] == 'Reject')
    {
        $oVersion = new Version($aClean['iVersionId']);
        $oTest = new testData($aClean['iTestingId']);
        $oVersion->GetOutputEditorValues($_REQUEST);
        $oTest->GetOutputEditorValues($_REQUEST);
        if ($aClean['sAppType'] == "application") // application
        {
            $oApp = new Application($aClean['iAppId']);
            $oApp->GetOutputEditorValues($_REQUEST); // load the values from $_REQUEST 
            $oApp->update(true);
            $oApp->reject();
        }
        $oVersion->update(true);
        $oVersion->reject();
        $oTest->update(true);
        $oTest->reject();
        util_redirect_and_exit($_SERVER['PHP_SELF']);
    }

    //process according to sub flag
    if ($aClean['sSub'] == 'view')
    {
        $x = new TableVE("view");
        apidb_header("Admin App Queue");

        echo '<form name="sQform" action="adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";
        echo '<input type="hidden" name="sSub" value="add">',"\n"; 

        echo html_back_link(1,'adminAppQueue.php');

        if (!$oApp) //app version
        { 
            echo html_frame_start("Potential duplicate versions in the database","90%","",0);
            $oAppForVersion = new Application($oVersion->iAppId);
            Version::display_approved($oAppForVersion->aVersionsIds);
            echo html_frame_end("&nbsp;");

            echo html_frame_start("Move test to version","90%","",0);
            display_move_test_to_versions_table($oAppForVersion->aVersionsIds,$oVersion->iVersionId);
            echo html_frame_end("&nbsp;");

            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the application version waiting to be approved. \n";
            echo "If you approve this application version an email will be sent to the author of the submission.<p>\n";

            echo "<b>App Version</b> This type of application will be nested under the selected application parent.\n";
            echo "<p>Click delete to remove the selected item from the queue an email will automatically be sent to the\n";
            echo "submitter to let him know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    
        } else // application
        {
            echo html_frame_start("Potential duplicate applications in the database", "90%", "", 0);
            perform_search_and_output_results($oApp->sName);
            echo html_frame_end("&nbsp;");

            echo html_frame_start("Delete application as duplicate of this application:","90%","",0);
            echo "Clicking on an entry in this table will delete this application.";
            echo " It will also modify the version";
            echo " application to have an appId of the existing application and keep it queued for processing";
            $hResult = searchForApplication($oApp->sName);
            outputSearchTableForDuplicateFlagging($oApp->iAppId, $hResult);
            $hResult = searchForApplicationFuzzy($oApp->sName, 60);
            outputSearchTableForDuplicateFlagging($oApp->iAppId, $hResult);
            echo html_frame_end("&nbsp;");

            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the application waiting to be approved. \n";
            echo "You need to pick a category before submitting \n";
            echo "it into the database. If you approve this application,\n";
            echo "an email will be sent to the author of the submission.<p>\n";
            echo "<p>Click delete to remove the selected item from the queue. An email will automatically be sent to the\n";
            echo "submitter to let them know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    
    
            /*
             * vendor/alt vendor fields
             * if user selected a predefined vendorId:
             */
            $iVendorId = $oApp->iVendorId;

            /*
             * If not, try for an exact match
             * Use the first match if we found one and clear out the vendor field,
             * otherwise don't pick a vendor
             * N.B. The vendor string is the last word of the keywords field !
             */
            if(!$iVendorId)
            {
                $sVendor = get_vendor_from_keywords($oApp->sKeywords);
                $hResult = query_parameters("SELECT vendorId FROM vendor WHERE vendorname = '?'",
                                            $sVendor);
                if($hResult)
                {
                    $oRow = mysql_fetch_object($hResult);
                    $iVendorId = $oRow->vendorId;
                }
            }
            
            /*
             * try for a partial match
             */
            if(!$iVendorId)
            {
                $hResult = query_parameters("SELECT * from vendor where vendorname like '%?%'", $sVendor);
                if($hResult)
                {
                    $oRow = mysql_fetch_object($hResult);
                    $iVendorId = $oRow->vendorId;
                }
            }

            //vendor field
            if($iVendorId)
            {
                $sVendor = "";
                $oApp->iVendorId = $iVendorId;
            }  
        }

        /* output the appropriate editors depending on whether we are processing an application */
        /* and a version or just a version */
        if($oApp)
        {
            $oApp->OutputEditor($sVendor);
            $oVersion->OutputEditor(false, false);
        } else
        {
            $oVersion->OutputEditor(false, false);
        }
        $oTest->OutputEditor($aClean['sDistribution']);
                                
        echo html_frame_start("Reply text", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
        echo '<td><textarea name="sReplyText" style="width: 100%" cols="80" rows="10"></textarea></td></tr>',"\n";

        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        if ($oApp) //application
        {
            echo '<input type="hidden" name="sAppType" value="application" />';
            echo '<input type=submit value=" Submit App Into Database " class=button>&nbsp',"\n";
        } else // app version
        {
            echo '<input type="hidden" name="sAppType" value="version" />';
            echo '<input type="submit" value=" Submit Version Into Database " class="button">&nbsp',"\n";
        }

        echo '<input name="sSub" type="submit" value="Delete" class="button" />',"\n";
        echo '<input name="sSub" type="submit" value="Reject" class="button" />',"\n";
        echo '</td></tr>',"\n";
        echo '</table>',"\n";
        echo '</form>',"\n";
        echo html_frame_end();
        echo html_back_link(1,'adminAppQueue.php');
    }
    else
    {
        //error no sub!
        addmsg("Internal Routine Not Found!!", "red");
        util_redirect_and_exit(apidb_fullurl("admin/adminAppQueue.php"));
    }
}
else /* if ($aClean['sSub']) is not defined, display the main app queue page */
{
    apidb_header("Admin App Queue");

    // get queued apps that the current user should see
    $hResult = $_SESSION['current']->getAppQueueQuery(true); /* query for the app family */

    if(!$hResult || !mysql_num_rows($hResult))
    {
         //no apps in queue
        echo html_frame_start("Application Queue","90%");
        echo '<p><b>The Application Queue is empty.</b></p>',"\n";
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of applications waiting for your approval, or to be rejected.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can edit, delete or approve it into \n";
        echo "the AppDB .<br>\n";
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        Application::showList($hResult);

    }

     // get queued versions (only versions where application are not queued already)
     $hResult = $_SESSION['current']->getAppQueueQuery(false); /* query for the app version */

     if(!$hResult || !mysql_num_rows($hResult))
     {
         //no apps in queue
         echo html_frame_start("Version Queue","90%");
         echo '<p><b>The Version Queue is empty.</b></p>',"\n";
         echo html_frame_end("&nbsp;");         
     }
     else
     {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of versions waiting for your approval, or to be rejected.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can edit, delete or approve it into \n";
        echo "the AppDB .<br>\n";
        echo "<p>Note that versions linked to application that have not been yet approved are not displayed in this list.</p>\n";
        echo "the AppDB.<br>\n";
        echo "</td></tr></table></div>\n\n";
    
        //show version list
        Version::showList($hResult);

    }
}
apidb_footer();       
?>
