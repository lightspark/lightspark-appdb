<?

/* code to View and approve new Apps */

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");

//deny access if not logged in
if(!loggedin())
{
    errorpage("You need to be logged in to use this page.");
    exit;
}
else if (!havepriv("admin"))
{
    errorpage("You must be an administrator to use this page.");
    exit;
}

apidb_header("Admin App Queue");
echo '<form name="qform" action="adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";

if ($sub)
{
    if ($queueId)
    {
        //get data
        $query = "SELECT * from appQueue where queueId = $queueId;";
        $result = mysql_query($query);
        $ob = mysql_fetch_object($result);
        mysql_free_result($result);
    }
    else
    {
        //error no Id!
        echo html_frame_start("Error","300");
        echo '<p><b>Application Not Found!</b></p>',"\n";
        echo html_frame_end("&nbsp;"); 
    }

    //process according to sub flag
    if ($sub == 'view' && $queueId)
    {
        $x = new TableVE("view");
    
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
        echo "</td></tr></table></div>\n\n";    
    
        //view application details
        echo html_frame_start("New Application Form",400,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        
        //type
        echo '<tr valign=top><td class=color0><b>Type</b></td><td>',"\n";
        echo '<select name=type><option value=app>Application</option><option value=ver>Version</option></select>',"\n";
        echo '</td></tr>',"\n";        
        
        //category

        $query = "select * from appCategory where catId = '$ob->queueCatId';";
        $result = mysql_query($query);
        if($result)
        {
            
            $ob2 = mysql_fetch_object($result);
                        
            echo '<tr valign=top><td class=color0><b>Category</b></td><td>',"\n";
            $x->make_option_list("cat",stripslashes($ob2->catId),"appCategory","catId","catName");
            echo '</td></tr>',"\n";
        } else
        {
            echo '<tr valign=top><td class=color0><b>Category</b></td><td>',"\n";
            $x->make_option_list("cat","","appCategory","catId","catName");
            echo '</td></tr>',"\n";
        }
        //app parent
        echo '<tr valign=top><td class=color0><b>App Parent</b></td><td>',"\n";
        $x->make_option_list("appParent","","appFamily","appId","appName");
        echo '</td></tr>',"\n";
                
        //name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo '<td><input type=text name="queueName" value="'.stripslashes($ob->queueName).'" size=20></td></tr>',"\n";

        //version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo '<td><input type=text name="queueVersion" value="'.stripslashes($ob->queueVersion).'" size=20></td></tr>',"\n";
         
        //vendor/alt vendor fields
        // try for an exact match
        // Use the first match if we found one and clear out the vendor field,
        // otherwise don't pick a vendor
        $query = "select * from vendor where vendorname = '$ob->queueVendor';";
        $result = mysql_query($query);
        $checkvendor = 0;
        if($result)
        {
            $ob2 = mysql_fetch_object($result);
            $checkvendor = $ob2->vendorId;
        }
        if(!$checkvendor)
        {
            // try for a partial match
            $query = "select * from vendor where vendorname like '%$ob->queueVendor%';";
            $result = mysql_query($query);
            if($result)
            {
                $ob2 = mysql_fetch_object($result);
                $checkvendor = $ob2->vendorId;
            }
        }
        if(checkvendor)
        {
            $ob->queueVendor = '';
    
            //vendor field
            echo '<tr valign=top><td class=color0><b>App Vendor</b></td>',"\n";
            echo '<td><input type=text name="queueVendor" value="'.stripslashes($ob->queueVendor).'" size=20></td></tr>',"\n";
            
            echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
            $x->make_option_list("altvendor", $checkvendor ,"vendor","vendorId","vendorName");
            echo '</td></tr>',"\n";
        } else
        {
            //vendor field
            echo '<tr valign=top><td class=color0><b>App Vendor</b></td>',"\n";
            echo '<td><input type=text name="queueVendor" value="'.stripslashes($ob->queueVendor).'" size=20></td></tr>',"\n";
        
            echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
            $x->make_option_list("altvendor","","vendor","vendorId","vendorName");
            echo '</td></tr>',"\n";
        }
    
                
        //url
        echo '<tr valign=top><td class=color0><b>App URL</b></td>',"\n";
        echo '<td><input type=text name="queueURL" value="'.stripslashes($ob->queueURL).'" size=20></td></tr>',"\n";
        
        //desc
        echo '<tr valign=top><td class=color0><b>App Desc</b></td>',"\n";
        echo '<td><textarea name="queueDesc" rows=10 cols=35>'.stripslashes($ob->queueDesc).'</textarea></td></tr>',"\n";
        
        //echo '<tr valign=top><td bgcolor=class=color0><b>Email</b></td>,"\n";
        //echo '<td><input type=text name="queueEmail" value="'.$ob->queueEmail.'" size=20></td></tr>',"\n";
        //echo '<tr valign=top><td bgcolor=class=color0><b>Image</b></td>,"\n";
        //echo '<td><input type=file name="queueImage" value="'.$ob->.'" size=15></td></tr>',"\n";

        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit value=" Submit App Into Database " class=button> </td></tr>',"\n";
        echo '</table>',"\n";
        echo '<input type=hidden name="sub" value="add">',"\n"; 
        echo '<input type=hidden name="queueId" value="'.$queueId.'">',"\n";  

        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminAppQueue.php');
    }
    else if ($sub == 'add' && $queueId)
    {
        //add item to main db
        $statusMessage = "";
        $goodtogo = 0;
        if ($type == 'app')
        {
            //process as application family
            if ($altvendor == 0 && $queueVendor)
            {
                //add new vendor
                mysql_query("INSERT into vendor VALUES (null, '".addslashes($queueVendor)."', '');");
                $altvendor = mysql_insert_id();
            }
            
            $query = "INSERT into appFamily VALUES (null, '".
                                    addslashes($queueName)."', $altvendor, '', '".
                                addslashes($queueDesc)."', '".
                                addslashes($queueURL)."', $cat);"; 
               
            if (mysql_query($query))
            {
                    //get the id of the app just added    
                $appParent = mysql_insert_id();

                //delete queue item
                mysql_query("DELETE from appQueue where queueId = $queueId;");
                                            
                //set ver if not set
                if (!$queueVersion)
                    $queueVersion = '1.0';
                if (!$queueDesc)
                    $queueDesc = 'released version';
                
                $verQuery = "INSERT into appVersion VALUES (null, $appParent, '".
                addslashes($queueVersion)."', '', '".
                addslashes($queueDesc)."', '".
                addslashes($queueURL)."', 0.0, 0.0);";
                
                //Now add a version
                if (mysql_query($verQuery))
                {
                    //successful
                    $statusMessage = "<p>The application $queueName was successfully added into the database</p>\n";
                    $goodtogo = 1;
                }
                else
                {
                    //error
                    $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
                    $statusMessage .= "<p><b>Note:</b> The application family was successfully added.</p>\n";
                }
                
            }
            else
            {
               //error
               $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
            }
        }
        else if ($type == 'ver')
        {
            //process as application version
            if ($appParent)
            {
                    $query = "INSERT into appVersion VALUES (null, $appParent, '".
                addslashes($queueVersion)."', '', '".
                addslashes($queueDesc)."', '".
                addslashes($queueURL)."', 0.0, 0.0);";
                
                if (mysql_query($query))
                {
                    //successful
                    $statusMessage = "<p>The application $queueName was successfully added into the database</p>\n";
                    mysql_query("DELETE from appQueue where queueId = $queueId;");
                    $goodtogo = 1;
                                        
                }
                else
                {
                    //error
                    $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
                }
            }
            else
            {
                $statusMessage = "<p><b>Error<br>You did not pick an application Parent!</b></p>\n";
            }
            
        }
        
        //Send Status Email
        if ($ob->queueEmail && $goodtogo)
        {
                $ms =  "Application Database Status Report\n";
                $ms .= "----------------------------------\n\n";
                $ms .= "Your application ".stripslashes($ob->queueName)." has been entered ";
                $ms .= "into the application database.\n\n";
                $ms .= "Thanks!\n";
                
                mail(stripslashes($ob->queueEmail),'[AppDB] Status Report',$ms);
        }
        
        //done
        echo html_frame_start("Submit Application","300");
        echo "<p><b>$statusMessage</b></p>\n";
        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminAppQueue.php'); 
    }
    else if ($sub == 'delete' && $queueId)
    {
       //delete main item
       $query = "DELETE from appQueue where queueId = $queueId;";
       $result = mysql_query($query);
       echo html_frame_start("Delete Application: $ob->queueName",400,"",0);
       if(!$result)
       {
           //error
           echo "<p>Internal Error: unable to delete selected application!</p>\n";
       }
       else
       {
           //success
           echo "<p>Application was successfully deleted from the Queue.</p>\n";
       }
       echo html_frame_end("&nbsp;");
       echo html_back_link(1,'adminAppQueue.php');
    }
    else
    {
        //error no sub!
        echo html_frame_start("Error","300");
        echo '<p><b>Internal Routine Not Found!</b></p>',"\n";        
        echo html_frame_end("&nbsp;");
        echo html_back_link(1,'adminAppQueue.php'); 
    }
}
else
{
    //get available apps
    $query = "SELECT queueId, queueName, queueVendor,".
                     "queueVersion, queueEmail,".
                     "UNIX_TIMESTAMP(submitTime) as submitTime ".
                     "from appQueue;";
    $result = mysql_query($query);

    if(!$result || !mysql_num_rows($result))
    {
         //no apps in queue
        echo html_frame_start("","90%");
        echo '<p><b>The Application Queue is empty.</b></p>',"\n";
        echo '<p>There is nothing for you to do. Check back later.</p>',"\n";        
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is the list of applications waiting for your approval, or to be annihilated from existence.</p>\n";
        echo "<p>To view a submission, click on its name. From that page you can edit, and approve it into the AppDB.<br>\n";
        echo "Click the delete link to remove the selected item from the queue. An email will automatically be sent to the\n";
        echo "submitter to let them know the item was deleted.</p>\n";        
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
        
        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Vendor</font></td>\n";
        echo "    <td><font color=white>Submitter Email</font></td>\n";
        echo "    <td>&nbsp;</td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($result))
        {
            if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
            echo "<tr class=$bgcolor>\n";
            echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
            echo "    <td><a href='adminAppQueue.php?sub=view&queueId=$ob->queueId'>$ob->queueName</a></td>\n";
            echo "    <td>".stripslashes($ob->queueVersion)." &nbsp;</td>\n";
            echo "    <td>".stripslashes($ob->queueVendor)." &nbsp;</td>\n";
            echo "    <td>".stripslashes($ob->queueEmail)." &nbsp;</td>\n";
            echo "    <td>[<a href='adminAppQueue.php?sub=delete&queueId=$ob->queueId'>delete</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }
        
}

echo "</form>";
apidb_footer();


?>
