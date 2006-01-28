<?php
/*************************************/
/* code to View and approve new Apps */
/*************************************/
 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");
require_once(BASE."include/testResults.php");


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
        while($ob = mysql_fetch_object($hResult))
        {
            //skip if a NONAME
            if ($ob->appName == "NONAME") { continue; }
		
            //set row color
            $bgcolor = ($c % 2) ? 'color0' : 'color1';
		
            //count versions
            $query = query_appdb("SELECT count(*) as versions FROM appVersion WHERE appId = $ob->appId AND versionName != 'NONAME'");
            $y = mysql_fetch_object($query);

            //display row
            echo "<tr class=$bgcolor>\n";
            /* map the merging of the current app to the app we are displaying in the table */
            echo "    <td>".html_ahref($ob->appName,"adminAppQueue.php?sub=duplicate&apptype=application&appId=".$currentAppId."&appIdMergeTo=".$ob->appId)."</td>\n";
            echo "    <td>$y->versions versions &nbsp;</td>\n";
            echo "</tr>\n\n";

            $c++;    

            //set row color
            $bgcolor = ($c % 2) ? 'color0' : 'color1';

            /* add the versions to the table */
            $oApp = new Application($ob->appId);
            foreach($oApp->aVersionsIds as $iVersionId)
            {
                $oVersion = new Version($iVersionId);
                echo "<tr class=$bgcolor><td></td><td>".$oVersion->sName."</td></tr>\n";
            }

            $c++;    
        }

        echo "</table>\n\n";
        echo html_frame_end();
    }
}

//deny access if not logged in or not a super maintainer of any applications
if(!$_SESSION['current']->hasPriv("admin") && !$_SESSION['current']->isSuperMaintainer())
{
    errorpage("Insufficient privileges.");
    exit;
}
$oTest = new testData($_REQUEST['iTestingId']);

if ($_REQUEST['sub'])
{
    if($_REQUEST['apptype'] == 'application')
    {
        /* make sure the user is authorized to view this application request */
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            errorpage("Insufficient privileges.");
            exit;
        }

        $oApp = new Application($_REQUEST['appId']);

        // if we are processing a queued application there MUST be an implicitly queued 
        // version to go along with it.  
        $sQuery = "Select versionId from appVersion where appId='".$_REQUEST['appId']."';";
        $hResult = query_appdb($sQuery);
        $oRow = mysql_fetch_object($hResult);

        $oVersion = new Version($oRow->versionId);

    } 
    else if($_REQUEST['apptype'] == 'version')
    {
        /* make sure the user has permission to view this version */
        $oVersion = new Version($_REQUEST['versionId']);
        if(!$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            errorpage("Insufficient privileges.");
            exit;
        }
    } else
    {
        //error no Id!
        addmsg("Application Not Found!", "red");
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }

    // Get the Testing results if they exist
    $sQuery = "Select testingId from testResults where versionId='".$oVersion->iVersionId."';";
    $hResult = query_appdb($sQuery);
    if($hResult)
    {
        $oRow = mysql_fetch_object($hResult);
        $oTest = new testdata($oRow->testingId);
    }
    else
    {
        $oTest = new testResult();
    }

    if($_REQUEST['sub'] == 'add')
    {
        $oVersion = new Version($_REQUEST['versionId']);
        $oTest = new testData($_REQUEST['iTestingId']);
        $oVersion->GetOutputEditorValues();
        $oTest->GetOutputEditorValues();
        if ($_REQUEST['apptype'] == "application") // application
        {
            $oApp = new Application($_REQUEST['appId']);
            $oApp->GetOutputEditorValues(); // load the values from $_REQUEST 
            // add new vendor
            if($_REQUEST['appVendorName'] and !$_REQUEST['appVendorId'])
            {
                $oVendor = new Vendor();
                $oVendor->create($_REQUEST['appVendorName'],$_REQUEST['appWebpage']);
                $oApp->iVendorId = $oVendor->iVendorId;
            }
            $oApp->update(true);
            $oApp->unQueue();
        }
        $oVersion->update(true);
        $oVersion->unQueue();
        $oTest->update(true);
        $oTest->unQueue();
        redirect($_SERVER['PHP_SELF']);
    }
    else if ($_REQUEST['sub'] == 'duplicate')
    {
        if(is_numeric($_REQUEST['appIdMergeTo']))
        {
            /* move this version submission under the existing app */
            $oVersion->iAppId = $_REQUEST['appIdMergeTo'];
            $oVersion->update();

            /* delete the appId that is the duplicate */
            $oAppDelete = new Application($oApp->iAppId);
            $oAppDelete->delete();
        }

        /* redirect back to the main page */
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'Delete')
    {

        if (($_REQUEST['apptype'] == "application") && is_numeric($_REQUEST['appId'])) // application
        {
            // get the queued versions that refers to the application entry we just removed
            // and delete them as we implicitly added a version entry when adding a new application
            $sQuery = "SELECT versionId FROM appVersion WHERE appVersion.appId = '".$_REQUEST['appId']."' AND appVersion.queued = 'true';";
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
            $oApp = new Application($_REQUEST['appId']);
            $oApp->delete();

        } else if(($_REQUEST['apptype'] == "version") && is_numeric($_REQUEST['versionId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->delete();
        }
        foreach($oVersion->aVersionIds as $iTestingId)
        {
            $oTest = new Version($iTestingId);
            $oTest->delete();
        }

        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'Reject')
    {
        if (($_REQUEST['apptype'] == "application") && is_numeric($_REQUEST['appId'])) // application
        {
            // get the queued versions that refers to the application entry we just removed
            // and delete them as we implicitly added a version entry when adding a new application
            $sQuery = "SELECT versionId FROM appVersion WHERE appVersion.appId = '".$_REQUEST['appId']."' AND appVersion.queued = 'true';";
            $hResult = query_appdb($sQuery);
            if($hResult)
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $oVersion = new Version($oRow->versionId);
                    $oVersion->reject(true);
                }
            }

            // delete the application entry
            $oApp = new Application($_REQUEST['appId']);
            $oApp->reject();
        } else if(($_REQUEST['apptype'] == "version") && is_numeric($_REQUEST['versionId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->reject();

        }
        foreach($oVersion->aVersionIds as $iTestingId)
        {
            $oTest = new Version($iTestingId);
            $oTest->GetOutputEditorValues();
            $oTest->iVersionId = $oVersion->iVersionId;
            $oTest->Update();
            $oTest->reject();
        }

        
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }

    //process according to sub flag
    if ($_REQUEST['sub'] == 'view')
    {
        $x = new TableVE("view");
        apidb_header("Admin App Queue");

        echo '<form name="qform" action="adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";
        echo '<input type="hidden" name="sub" value="add">',"\n"; 

        echo html_back_link(1,'adminAppQueue.php');

        if (!$oApp) //app version
        { 
            echo html_frame_start("Potential duplicate versions in the database","90%","",0);
            $oAppForVersion = new Application($oVersion->iAppId);
            display_approved_versions($oAppForVersion->aVersionsIds);
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
                $sQuery = "SELECT vendorId FROM vendor WHERE vendorname = '".$sVendor."';";
                $hResult = query_appdb($sQuery);
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
                $sQuery = "select * from vendor where vendorname like '%".$sVendor."%';";
                $hResult = query_appdb($sQuery);
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
        $oTest->OutputEditor($_REQUEST['sDistribution']);
                                
        echo html_frame_start("Reply text", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
        echo '<td><textarea name="replyText" style="width: 100%" cols="80" rows="10"></textarea></td></tr>',"\n";

        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        if ($oApp) //application
        {
            echo '<input type="hidden" name="apptype" value="application" />';
            echo '<input type=submit value=" Submit App Into Database " class=button>&nbsp',"\n";
        } else // app version
        {
            echo '<input type="hidden" name="apptype" value="version" />';
            echo '<input type="submit" value=" Submit Version Into Database " class="button">&nbsp',"\n";
        }

        echo '<input name="sub" type="submit" value="Delete" class="button" />',"\n";
        echo '<input name="sub" type="submit" value="Reject" class="button" />',"\n";
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
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
}
else /* if ($_REQUEST['sub']) is not defined, display the main app queue page */
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
        showAppList($hResult);

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
        showVersionList($hResult);

    }
}
apidb_footer();       
?>
