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
    $aKeywords = explode(" *** ",$keywords);
    $iLastElt = (sizeOf($aKeywords)-1);
    return($aKeywords[$iLastElt]);
}

//deny access if not logged in
if(!$_SESSION['current']->hasPriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}

if ($_REQUEST['sub'])
{
    if(is_numeric($_REQUEST['appId']))
    {
        $oApp = new Application($_REQUEST['appId']);
    } elseif(is_numeric($_REQUEST['versionId']))
    {
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
?>
<link rel="stylesheet" href="./application.css" type="text/css">
<!-- load HTMLArea -->
<script type="text/javascript" src="../htmlarea/htmlarea_loader.js"></script>
<?php
        echo '<form name="qform" action="adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";
        echo '<input type="hidden" name="sub" value="add">',"\n"; 

        if ($oVersion) //app version
        { 
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
            $x->make_option_list("appId",$oVersion->sName,"appFamily","appId","appName");
            echo '</td></tr>',"\n";

            //version
            echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
            echo '<td><input type=text name="versionName" value="'.$oVersion->sName.'" size="20"></td></tr>',"\n";


            echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
            echo '<td><p style="width:700px"><textarea  cols="80" rows="20" id="editor" name="versionDescription">'.$oVersion->sDescription.'</textarea></p></td></tr>',"\n";
        
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="emailtext" rows="10" cols="35"></textarea></td></tr>',"\n";
        

            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input type="hidden" name="versionId" value="'.$oVersion->iVersionId.'" />';
            echo '<input type="submit" value=" Submit Version Into Database " class="button">&nbsp',"\n";
            echo '<input name="sub" type=submit value="Delete" class="button"></td></tr>',"\n";
            echo '</table></form>',"\n";
        } else // application
        { 
    
            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the application waiting to be approved. \n";
            echo "You need to pick a category before submitting \n";
            echo "it into the database. If you approve this application,\n";
            echo "an email will be sent to the author of the submission.<p>\n";
            echo "<p>There are two kinds of applications in this database:</p>\n";
            echo "<ol>\n";
            echo "    <li><b>App Family</b> This is a parent group application, that will have multiple versions under it.<br>\n";
            echo "    To add this submission as a Family, choose 'Application' from the type drop down. Then set the category.\n";
            echo "    The version and app parent fields will be ignored in this type.<br>\n";
            echo "    If the vendor does not exist, leave the vendor drop down unset, and the field will be used.</li><p>\n";
            echo "    <li><b>App Version</b> This type of application will be nested under the selected application parent.\n";
            echo "    The category, name, and vendor fields will be ignored.</li>\n";
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
                $sQuery = "select * from vendor where vendorname like '%$ob->queueVendor%';";
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
      
            //desc
  
            echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
            echo '<td><p style="width:700px"><textarea  cols="80" rows="20" id="editor" name="description">'.$oApp->sDescription.'</textarea></p></td></tr>',"\n";
        
            echo '<tr valign=top><td class="color0"><b>email Text</b></td>',"\n";
            echo '<td><textarea name="emailtext" rows=10 cols=35></textarea></td></tr>',"\n";

            echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
            echo '<input type="hidden" name="appId" value="'.$oApp->iAppId.'" />';
            echo '<input type=submit value=" Submit App Into Database " class=button>&nbsp',"\n";
            echo '<input name="sub" type="submit" value="Delete" class="button" /></td></tr>',"\n";
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
            $oApp->update($_REQUEST['appName'], $_REQUEST['appDescription'], $_REQUEST['keywords'], $_REQUEST['webPage'], $_REQUEST['vendorId'], $_REQUEST['catId']);
            $oApp->unQueue();
        } else if(is_numeric($_REQUEST['versionId']) && is_numeric($_REQUEST['appId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->update($_REQUEST['versionName'], $_REQUEST['versionDescription'],null,null,$_REQUEST['appId']);
            $oVersion->unQueue();
        }
        
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
    }
    else if ($_REQUEST['sub'] == 'Delete')
    {
        if (is_numeric($_REQUEST['appId'])) // application
        {
            $oApp = new Application($_REQUEST['appId']);
            $oApp->delete();
        } else if(is_numeric($_REQUEST['versionId']))  // version
        {
            $oVersion = new Version($_REQUEST['versionId']);
            $oVersion->delete();
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
else
{
    apidb_header("Admin App Queue");
    // get queued apps
    $sQuery = "SELECT appId FROM appFamily WHERE queued = 'true'";
    $hResult = query_appdb($sQuery);

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
            echo "    <td>".date("Y-n-t h:i:sa", $oApp->sSubmitTime)." &nbsp;</td>\n";
            echo "    <td><a href=\"mailto:".$oSubmitter->sEmail."\">".$oSubmitter->sRealname."</a></td>\n";
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
     $sQuery = "SELECT versionId FROM appVersion, appFamily WHERE appFamily.appId = appVersion.appId and appFamily.queued = 'false' AND appVersion.queued = 'true'";
     $hResult = query_appdb($sQuery);

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
            echo "    <td>".date("Y-n-t h:i:sa", $oVersion->sSubmitTime)." &nbsp;</td>\n";
            echo "    <td><a href=\"mailto:".$oSubmitter->sEmail."\">".$oSubmitter->sRealname."</a></td>\n";
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
