<?

/* code to View and approve new Apps */

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."application.php");

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
        addmsg("Application Not Found!", "red");
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
        exit;
    }

    //process according to sub flag
    if ($sub == 'view' && $queueId)
    {
        $x = new TableVE("view");
        apidb_header("Admin App Queue");
        echo '<form name="qform" action="adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";

        echo '<input type=hidden name="sub" value="add">',"\n"; 
        echo '<input type=hidden name="queueId" value="'.$queueId.'">',"\n";  

        If ($ob->queueCatId == -1) //app version
        { 
            //help
            echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
            echo "<p>This is the full view of the application waiting to be approved. \n";
            echo "If you approve this application,\n";
            echo "an email will be sent to the author of the submission.<p>\n";

            echo "      <b>App Version</b> This type of application will be nested under the selected application parent.\n";
            echo "<p>Click delete to remove the selected item from the queue. An email will automatically be sent to the\n";
            echo "submitter to let them know the item was deleted.</p>\n\n";        
            echo "</td></tr></table></div>\n\n";    

            echo '<input type=hidden name=type value="ver">',"\n"; 

            echo html_frame_start("New Application Form",400,"",0);
            echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

            //app parent
            echo '<tr valign=top><td class=color0><b>App Parent</b></td><td>',"\n";
            $x->make_option_list("appParent",stripslashes($ob->queueName),"appFamily","appId","appName");
            echo '</td></tr>',"\n";

            //version
            echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
            echo '<td><input type=text name="queueVersion" value="'.stripslashes($ob->queueVersion).'" size=20></td></tr>',"\n";

        }
        else
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
            echo '<td><input type=text name="queueVersion" value="'.stripslashes($ob->queueVersion).'" size=20></td>',"\n";
            echo '</tr>',"\n";
         
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
                echo '<td><input type=text name="queueVendor" value="'.stripslashes($ob->queueVendor).'" size=20></td>',"\n";
                echo '</tr>',"\n";
            
                echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
                $x->make_option_list("altvendor", $checkvendor ,"vendor","vendorId","vendorName");
                echo '</td></tr>',"\n";
            } else
            {
                //vendor field
                echo '<tr valign=top><td class=color0><b>App Vendor</b></td>',"\n";
                echo '<td><input type=text name="queueVendor" value="'.stripslashes($ob->queueVendor).'" size=20></td>',"\n";
                echo '</tr>',"\n";
        
                echo '<tr valign=top><td class=color0>&nbsp;</td><td>',"\n";
                $x->make_option_list("altvendor","","vendor","vendorId","vendorName");
                echo '</td></tr>',"\n";
            }
        }
        //url
        echo '<tr valign=top><td class=color0><b>App URL</b></td>',"\n";
        echo '<td><input type=text name="queueURL" value="'.stripslashes($ob->queueURL).'" size=20></td></tr>',"\n";
        
        //desc
        echo '<tr valign=top><td class=color0><b>App Desc</b></td>',"\n";
        echo '<td><textarea name="queueDesc" rows=10 cols=35>'.stripslashes($ob->queueDesc).'</textarea></td></tr>',"\n";
        
        //email message text
        if ($ob->queueEmail)
        {
            echo '<tr valign=top><td class=color0><b>email Text</b></td>',"\n";
            echo '<td><textarea name="emailtext" rows=10 cols=35></textarea></td></tr>',"\n";
        }
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit value=" Submit App Into Database " class=button>&nbsp',"\n";
        echo '<input name="sub" type=submit value="Delete" class=button> </td></tr>',"\n";
        echo '</table>',"\n";

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
                    $appVersion = mysql_insert_id();
                    addmsg("The application $queueName was successfully added into the database", "green");
                    $goodtogo = 1;
                }
                else
                {
                    //error
                    $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
                    $statusMessage .= "<p><b>Note:</b> The application family was successfully added.</p>\n";
                    addmsg($statusMessage, "red");
                }
                
            }
            else
            {
               //error
               $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
               addmsg($statusMessage, "red");
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
                    $appVersion = mysql_insert_id();
                    $statusMessage = "<p>The application $queueName was successfully added into the database</p>\n";
                    addmsg($statusMessage,Green);
                    mysql_query("DELETE from appQueue where queueId = $queueId;");
                    $goodtogo = 1;
                                        
                }
                else
                {
                    //error
                    $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
                    addmsg($statusMessage,red);
                }
            }
            else
            {
                addmsg("You did not pick an application Parent!",red);
                redirect(apidb_fullurl("admin/adminAppQueue.php?cat=view&queueId=$queueId"));
                exit;

            }
            
        }
        
        //Send Status Email
        if ($ob->queueEmail && $goodtogo)
        {
            $fullAppName = lookupAppName($appParent)." Version: ".lookupVersionName($appParent, $appVersion);
             
            $ms =  "Application Database Status Report\n";
            $ms .= "----------------------------------\n\n";
            $ms .= "Your application: ".$fullAppName." has been entered ";
            $ms .= "into the application database.\n\n";
            $ms .= APPDB_ROOT."appView.php?appId=$appParent&versionId=$appVersion"."\n\n";
            $ms .= "Thanks!\n\n";
            $ms .= $emailtext;

            mail(stripslashes($ob->queueEmail),'[AppDB] Status Report',$ms);
        }
        if ($goodtogo)
        {
            $email = getNotifyEmailAddressList($appParent, $appVersion);
            if($email)
            {
                $fullAppName = "Application: ".lookupAppName($appParent).
                    " Version: ".lookupVersionName($appParent, $appVersion);
                $ms = APPDB_ROOT."appview.php?appId=$appParent&versionId=$appVersion"."\n\n";
                $ms .= "New Application added to database:\n\n";
                $ms .= $fullAppName."\n\n";
                $ms .= STANDARD_NOTIFY_FOOTER;
    
                mail(stripslashes($email), "[AppDB] NEW ".$fullAppName ,$ms);
    
            } else
            {
                $email = "no one";
            }
            addmsg("mesage sent to: ".$email, green);

        }
        //done
        addmsg("<a href=".apidb_fullurl("appview.php")."?appId=".$appParent."&versionId=".$appVersion.">Veiw App</a>", "green");
        redirect(apidb_fullurl("admin/adminAppQueue.php"));
        exit;
    }
    else if ($sub == 'Delete' && $queueId)
    {
        //delete main item
        $query = "DELETE from appQueue where queueId = $queueId;";
        $result = mysql_query($query);
        if(!$result)
        {
            //error
            addmsg("Internal Error: unable to delete selected application!", "red");
            redirect(apidb_fullurl("admin/adminAppQueue.php?appId=$appId&versionId=$versionId"));
        }
        else
        {   
            //Send Status Email
            if ($ob->queueEmail)
            {
                if($ob->queueCatId == -1) //app version
                {
                    $fullAppName = lookupAppName($appParent)." Version: ".$ob->queueVersion;
                } else
                {
                    $fullAppName = $ob->queueName." Version: ".$ob->queueVersion;
                }
            
                $ms =  "Application Database Status Report\n";
                $ms .= "----------------------------------\n\n";
                $ms .= "Your application: ".$fullAppName." has not been entered ";
                $ms .= "into the application database.\n\n";
                $ms .= "Sorry!\n\n";
                $ms .= $emailtext;

                mail(stripslashes($ob->queueEmail),'[AppDB] Status Report',$ms);
            }
            //success
            addmsg("Application was successfully deleted from the Queue.", "green");
            redirect(apidb_fullurl("admin/adminAppQueue.php?appId=$appId&versionId=$versionId"));
        }
    }
    else
    {
        //error no sub!
        addmsg("Internal Routine Not Found!!", "red");
        redirect(apidb_fullurl("admin/adminAppQueue.php"));

    }
    exit;
}
else
{
    apidb_header("Admin App Queue");
    echo '<form name="qform" action="admin/adminAppQueue.php" method="post" enctype="multipart/form-data">',"\n";

    //get available apps
    $query = "SELECT queueId, queueName, queueVendor,".
                     "queueVersion, queueEmail, queueCatId,".
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
        echo "<p>To view a submission, click on its name. From that page you can edit, delete or approve it into \n";
        echo "the AppDB .<br>\n";
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
            if ($ob->queueCatId == -1)
            {
                $query2 = "select * from appFamily where appId = '$ob->queueName';";
                $result2 = mysql_query($query2);
                if($result2)
                {
                    $ob2 = mysql_fetch_object($result2);
                    echo "    <td><a href='adminAppQueue.php?sub=view&queueId=$ob->queueId'>$ob2->appName</a></td>\n";
                } else
                {
                    echo "    <td><a href='adminAppQueue.php?sub=view&queueId=$ob->queueId'>App not found</a></td>\n";
                }
            } else
            {
                echo "    <td><a href='adminAppQueue.php?sub=view&queueId=$ob->queueId'>$ob->queueName</a></td>\n";
            }
            echo "    <td>".stripslashes($ob->queueVersion)." &nbsp;</td>\n";
            echo "    <td>".stripslashes($ob->queueVendor)." &nbsp;</td>\n";
            echo "    <td>".stripslashes($ob->queueEmail)." &nbsp;</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");

    }
    echo "</form>";
    apidb_footer();
       
}

?>
