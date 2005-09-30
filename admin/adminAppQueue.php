<?php
/*************************************/
/* code to View and approve new Apps */
/*************************************/
 
include("path.php");
require(BASE."include/incl.php");
require(BASE."include/tableve.php");
require(BASE."include/application.php");
require(BASE."include/mail.php");


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
            echo "    <td>".html_ahref($ob->appName,"adminAppQueue.php?sub=duplicate&appId=".$currentAppId."&appIdMergeTo=".$ob->appId)."</td>\n";
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

if ($_REQUEST['sub'])
{
    if(is_numeric($_REQUEST['appId']))
    {
        /* make sure the user is authorized to view this application request */
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            errorpage("Insufficient privileges.");
            exit;
        }

        $oApp = new Application($_REQUEST['appId']);

        /* if we are processing a queued application there MUST be an implicitly queued */
        /* version to go along with it.  Find this version so we can display its information */
        /* during application processing so the admin can make a better choice about */
        /* whether to accept or reject the overall application */
        $sQuery = "Select versionId from appVersion where appId='".$_REQUEST['appId']."';";
        $hResult = query_appdb($sQuery);
        $oRow = mysql_fetch_object($hResult);

        /* make sure the user has permission to view this version */
        if(!$_SESSION['current']->hasAppVersionModifyPermission($oRow->versionId))
        {
            errorpage("Insufficient privileges.");
            exit;
        }

        $oVersion = new Version($oRow->versionId);
    } elseif(is_numeric($_REQUEST['versionId']))
    {
        /* make sure the user has permission to view this version */
        if(!$_SESSION['current']->hasAppVersionModifyPermission($_REQUEST['versionId']))
        {
            errorpage("Insufficient privileges.");
            exit;
        }

        $oVersion = new Version($_REQUEST['versionId']);
    } else
    {
        //error no Id!
        addmsg("Application Not Found!", "red");
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
            HtmlAreaLoaderScript(array("editor"));
        
            echo html_frame_start("Potential duplicate versions in the database","90%","",0);
            $oApp = new Application($oVersion->iAppId);
            display_versions($oApp->iAppId, $oApp->aVersionsIds);
            echo html_frame_end("&nbsp;");

            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the application version waiting to be approved. \n";
            echo "If you approve this application version an email will be sent to the author of the submission.<p>\n";

            echo "<b>App Version</b> This type of application will be nested under the selected application parent.\n";
            echo "<p>Click delete to remove the selected item from the queue an email will automatically be sent to the\n";
            echo "submitter to let him know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    

            echo html_frame_start("New Version Form",400,"",0);
            echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

            //app parent
            echo '<tr valign=top><td class=color0><b>Application</b></td><td>',"\n";
            $x->make_option_list("appId",$oVersion->iAppId,"appFamily","appId","appName");
            echo '</td></tr>',"\n";

            //version
            echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
            echo '<td><input type=text name="versionName" value="'.$oVersion->sName.'" size="20"></td></tr>',"\n";

            echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
            echo '<td><p style="width:700px"><textarea  cols="80" rows="20" id="editor" name="versionDescription">'.stripslashes($oVersion->sDescription).'</textarea></p></td></tr>',"\n";
        
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="replyText" rows="10" cols="35"></textarea></td></tr>',"\n";
        

            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input type="hidden" name="versionId" value="'.$oVersion->iVersionId.'" />';
            echo '<input type="submit" value=" Submit Version Into Database " class="button">&nbsp',"\n";
            echo '<input name="sub" type=submit value="Delete" class="button">',"\n";
            echo '<input name="sub" type=submit value="Reject" class="button"></td></tr>',"\n";
            echo '</table></form>',"\n";
        } else // application
        {
            HtmlAreaLoaderScript(array("editor", "editor2"));

            echo html_frame_start("Potential duplicate applications in the database","90%","",0);
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
    
            //view application details
            echo html_frame_start("New Application Form",400,"",0);
            echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

            //category
            echo '<tr valign=top><td class="color0>"<b>Category</b></td><td>',"\n";
            $x->make_option_list("catId",$oApp->iCatId,"appCategory","catId","catName");
            echo '</td></tr>',"\n";
                
            //name
            echo '<tr valign=top><td class="color0"><b>App Name</b></td>',"\n";
            echo '<td><input type="text" name="appName" value="'.$oApp->sName.'" size=20></td></tr>',"\n";
         
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
                $sVendor = "";
            echo '<tr valign=top><td class="color0"><b>App Vendor</b></td>',"\n";
            echo '<td><input type=text name="sVendor" value="'.$sVendor.'" size="20"></td>',"\n";
            echo '</tr>',"\n";
            
            echo '<tr valign=top><td class="color0">&nbsp;</td><td>',"\n";
            $x->make_option_list("vendorId", $iVendorId ,"vendor","vendorId","vendorName");
            echo '</td></tr>',"\n";

            //url
            echo '<tr valign=top><td class="color0"><b>App URL</b></td>',"\n";
            echo '<td><input type=text name="webpage" value="'.$oApp->sWebpage.'" size="20"></td></tr>',"\n";
      
            // application desc
            echo '<tr valign=top><td class=color0><b>Application Description</b></td>',"\n";
            echo '<td><p style="width:700px"><textarea  id="editor" cols="80" rows="20" name="applicationDescription">'.stripslashes($oApp->sDescription).'</textarea></p></td></tr>',"\n";

            // version name
            echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
            echo '<td><input type=text name="versionName" value="'.$oVersion->sName.'" size="20"></td></tr>',"\n";

            // version description
            echo '<tr valign=top><td class=color0><b>Version Description</b></td>',"\n";
            echo '<td><p style="width:700px"><textarea  cols="80" rows="20" id="editor2" name="versionDescription">'.stripslashes($oVersion->sDescription).'</textarea></p></td></tr>',"\n";
        
        
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="replyText" rows=10 cols=35></textarea></td></tr>',"\n";

            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input type="hidden" name="appId" value="'.$oApp->iAppId.'" />';
            echo '<input type=submit value=" Submit App Into Database " class=button>&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Delete" class="button" />',"\n";
            echo '<input name="sub" type="submit" value="Reject" class="button" />',"\n";
            echo '</td></tr>',"\n";
            echo '</table></form>',"\n";
        }

        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminAppQueue.php');
    }
    else if ($_REQUEST['sub'] == 'add')
    {
        if (is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId'])) // application
        {
            // add new vendor
            if($sVendor)
            {
                $oVendor = new Vendor();
                $oVendor->create($sVendor);
            }
            
            $oApp = new Application($_REQUEST['appId']);
            $oApp->update($_REQUEST['appName'], $_REQUEST['applicationDescription'], $_REQUEST['keywords'], $_REQUEST['webpage'], $_REQUEST['vendorId'], $_REQUEST['catId']);
            $oApp->unQueue();
        } else if(is_numeric($_REQUEST['versionId']) && is_numeric($_REQUEST['appId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->update($_REQUEST['versionName'], $_REQUEST['versionDescription'],null,null,$_REQUEST['appId']);
            $oVersion->unQueue();
        }
        
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'duplicate')
    {
        if(is_numeric($_REQUEST['appIdMergeTo']))
        {
            /* move this version submission under the existing app */
            $oVersion->update(null, null, null, null, $_REQUEST['appIdMergeTo']);
        
            /* delete the appId that is the duplicate */
            $oApp->delete();
        }

        /* redirect back to the main page */
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'Delete')
    {
        if (is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId'])) // application
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
        } else if(is_numeric($_REQUEST['versionId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->delete();
        }
        
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'Reject')
    {
        if (is_numeric($_REQUEST['appId']) && !is_numeric($_REQUEST['versionId'])) // application
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
        } else if(is_numeric($_REQUEST['versionId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->reject();
        }
        
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
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
        echo html_frame_start("","90%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
               <tr class=color4>
                  <td>Submission Date</td>
                  <td>Submitter</td>
                  <td>Vendor</td>
                  <td>Application</td>
                  <td align=\"center\">Action</td>
               </tr>";
        
        $c = 1;
        while($oRow = mysql_fetch_object($hResult))
        {
            $oApp = new Application($oRow->appId);
            $oSubmitter = new User($oApp->iSubmitterId);
            if($oApp->iVendorId)
            {
                $oVendor = new Vendor($oApp->iVendorId);
                $sVendor = $oVendor->sName;
            } else
            {
                $sVendor = get_vendor_from_keywords($oApp->sKeywords);
            }
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=\"$bgcolor\">\n";
            echo "    <td>".print_date(mysqltimestamp_to_unixtimestamp($oApp->sSubmitTime))."</td>\n";
            echo "    <td>\n";
            echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
            echo $oSubmitter->sRealname;
            echo $oSubmitter->sEmail ? "</a>":"";
            echo "    </td>\n";
            echo "    <td>".$sVendor."</td>\n";
            echo "    <td>".$oApp->sName."</td>\n";
            echo "    <td align=\"center\">[<a href=\"adminAppQueue.php?sub=view&appId=".$oApp->iAppId."\">process</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
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
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
               <tr class=color4>
                  <td>Submission Date</td>
                  <td>Submitter</td>
                  <td>Vendor</td>
                  <td>Application</td>
                  <td>Version</td>
                  <td align=\"center\">Action</td>
               </tr>";
        
        $c = 1;
        while($oRow = mysql_fetch_object($hResult))
        {
            $oVersion = new Version($oRow->versionId);
            $oApp = new Application($oVersion->iAppId);
            $oSubmitter = new User($oVersion->iSubmitterId);
            $oVendor = new Vendor($oApp->iVendorId);
            $sVendor = $oVendor->sName;
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=\"$bgcolor\">\n";
            echo "    <td>".print_date(mysqltimestamp_to_unixtimestamp($oVersion->sSubmitTime))."</td>\n";
            echo "    <td>\n";
            echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
            echo $oSubmitter->sRealname;
            echo $oSubmitter->sEmail ? "</a>":"";
            echo "    </td>\n";
            echo "    <td>".$sVendor."</td>\n";
            echo "    <td>".$oApp->sName."</td>\n";
            echo "    <td>".$oVersion->sName."</td>\n";
            echo "    <td align=\"center\">[<a href=\"adminAppQueue.php?sub=view&versionId=".$oVersion->iVersionId."\">process</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");

    }
}
apidb_footer();       
?>
