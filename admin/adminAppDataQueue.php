<?php
/********************************************************/
/* code to view and approve new application data        */
/********************************************************/

include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."tableve.php");
require(BASE."include/"."category.php");

apidb_header("Admin Application Data Queue");

// deny access if not admin
if(!havepriv("admin"))
{
    errorpage("Insufficient privileges.");
    exit;
}


// shows the list of appdata in queue
if (!$_REQUEST['queueId'])
{
    //get available appData
    $str_query = "SELECT * from appDataQueue;";
    $result = mysql_query($str_query);

    if(!$result || !mysql_num_rows($result))
    {
        //no appData in queue
        echo html_frame_start("","90%");
        echo '<p><b>The App Data Queue is empty.</b></p>',"\n";
        echo '<p>There is nothing for you to do. Check back later.</p>',"\n";        
        echo html_frame_end("&nbsp;");         
    }
    else
    {
        //help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "<p>This is a list of application data submitted by users.\n";
        echo "Please inspect carefully this data before accepting it.\n";
        echo "</td></tr></table></div>\n\n";
    
        //show applist
        echo html_frame_start("","90%","",0);
        echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n\n";
        
        echo "<tr class=color4>\n";
        echo "    <td><font color=white>Submission Date</font></td>\n";
        echo "    <td><font color=white>Queue Id</font></td>\n";
        echo "    <td><font color=white>Username (e-mail)</font></td>\n";
        echo "    <td><font color=white>Application Name</font></td>\n";
        echo "    <td><font color=white>Version</font></td>\n";
        echo "    <td><font color=white>Type</font></td>\n";
        echo "</tr>\n\n";
        
        $c = 1;
        while($ob = mysql_fetch_object($result))
        {   
            if($_SESSION['current']->is_maintainer($ob->queueappId,
                                                   $ob->queueversionId) 
                    || havepriv("admin"))
             {
                if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
                echo "<tr class=$bgcolor>\n";
                echo "    <td>".date("Y-n-t h:i:sa", $ob->submitTime)." &nbsp;</td>\n";
                echo "    <td><a href='adminAppDataQueue.php?queueId=$ob->queueId'>".$ob->queueId."</a></td>\n";
                if($ob->userId)
                    echo "    <td>".lookupUsername($ob->userId)." (".lookupEmail($ob->userId).")</td>\n";
                else
                    echo "    <td>Anonymous</td>\n";
                echo "<td>".appIdToName($ob->appId)."</td>\n";
                echo "<td>".versionIdToName($ob->versionId)."</td>\n";
                echo "<td>".$ob->type."</td>\n";
                echo "</tr>\n\n";
                $c++;
            }
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }
        
} else // shows a particular appdata
{
    if(!(havepriv("admin") ||
             $_SESSION['current']->is_maintainer($obj_row->queueAppId,
                                                 $obj_row->queueVersionId)))
    {
        errorpage("You don't have sufficient privileges to use this page.");
        exit;
    }
    
    $str_request="SELECT * FROM appDataQueue WHERE queueId='".$_REQUEST['queueId']."'";
    $res_result=mysql_query($str_request);
    $obj_row=mysql_fetch_object($res_result);
    
    if(!$_REQUEST['sub']=="inside_form")
    {       
         
        echo '<form name="qform" action="adminAppDataQueue.php" method="post">',"\n";
        // help
        echo "<div align=center><table width='90%' border=0 cellpadding=3 cellspacing=0><tr><td>\n\n";
        echo "Please enter an accurate and personalized reply anytime a submitted scrrenshot is rejected.\n";
        echo "It is not polite to reject someones attempt at trying to help out without explaining why.\n";
        echo "</td></tr></table></div>\n\n";    

        // view application details
        echo html_frame_start("New Application Data Form",600,"",0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
  
        // app name
        echo '<tr valign=top><td class=color0><b>App Name</b></td>',"\n";
        echo "<td>".appIdToName($obj_row->appId)."</td></tr>\n";

        // version
        echo '<tr valign=top><td class=color0><b>App Version</b></td>',"\n";
        echo "<td>".versionIdToName($obj_row->versionId)."</td></tr>\n";
         
        //dataDescription
        echo '<tr valign=top><td class=color0><b>Description</b></td>',"\n";
        echo '<td><textarea name="description" rows=10 cols=35>'.stripslashes($obj_row->description).'</textarea></td></tr>',"\n";
        
        //data
        if($obj_row->type == "image") 
        {
           echo '<tr valign=top><td class=color0><b>Submited image</b></td>',"\n";
           echo '<td>';
           $imgSRC = '<img width="'.APPDB_THUMBNAIL_WIDTH.'" height="'.APPDB_THUMBNAIL_HEIGHT.'" src="screenshotQueue.php?queueId='.$obj_row->queueId.'" />';
           // generate random tag for popup window
           $randName = generate_passwd(5);
           // set image link based on user pref
           $img = '<a href="javascript:openWin(\'screenshotQueue.php?queueId='.$obj_row->queueId.'\',\''.$randName.'\','.APPDB_SCREENSHOT_MAXWIDTH.','.APPDB_SCREENSHOT_MAXHEIGHT.');">'.$imgSRC.'</a>';
           if (loggedin())
           {
               if ($_SESSION['current']->getpref("window:screenshot") == "no")
               { 
                   $img = '<a href="screenshotQueue.php?queueId='.$obj_row->queueId.'">'.$imgSRC.'</a>';
               }
           }
           echo $img;
           echo '</td></tr>',"\n";
        } elseif($obj_row->type == "url")
        {
            echo '<tr valign=top><td class=color0><b>Submitted link</b></td>',"\n";
            echo '<td><textarea name="content" rows=10 cols=35>'.stripslashes($obj_row->url).'</textarea></td></tr>',"\n";
        }

        //email response
        echo '<tr valign=top><td class=color0><b>Email reply</b></td>',"\n";
        echo "<td><textarea name='replyText' rows=10 cols=35>Enter a personalized reason for acceptance or rejection of the submitted application data here</textarea></td></tr>\n";

        /* Add button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=add value=" Add data to this application " class=button /> </td></tr>',"\n";

        /* Reject button */
        echo '<tr valign=top><td class=color3 align=center colspan=2>' ,"\n";
        echo '<input type=submit name=reject value=" Reject this request " class=button /></td></tr>',"\n";

        echo '</table>',"\n";
        echo '<input type=hidden name="sub" value="inside_form" />',"\n"; 
        echo '<input type=hidden name="queueId" value="'.$_REQUEST['queueId'].'" />',"\n";  
        echo '</form>';
    } elseif ($_REQUEST['add']) // we accepted the request
    { 
        $statusMessage = "";
        $goodtogo = 0;
        
        if($obj_row->type == "image")
        { 
            $str_query = "INSERT INTO appData VALUES (null, ".$obj_row->appId.", ".$obj_row->versionId.", 'image', ".
                     "'".addslashes($_REQUEST['description'])."', '')";
            mysql_query($str_query);
            $int_id = mysql_insert_id();
 
            // we move the content in the live directory
            rename("../data/queued/screenshots/".$obj_row->queueId, "../data/screenshots/".$int_id);

            // we have to update the entry now that we know its name
            $str_query = "UPDATE appData SET url = '".$int_id."' WHERE id = '".$int_id."'";
   
        }
        elseif ($obj_row->type == "url") {
             $query = "INSERT INTO appData VALUES (null, ".$obj_row->appId.", ".$obj_row->versionId.", 'url', ".
                     "'".addslashes($_REQUEST['description'])."', '".$obj_row->url."')";
        }

        if(debugging()) addmsg("<p align=center><b>query:</b> $query </p>","green");
    
        if (mysql_query($str_query))
        {
            $statusMessage = "<p>The application data was successfully added into the database</p>\n";

            //delete the item from the queue
            mysql_query("DELETE from appDataQueue where queueId = ".$obj_row->queueId.";");

            $goodtogo = 1; /* set to 1 so we send the response email */
        } else
        {
           //error
           $statusMessage = "<p><b>Database Error!<br>".mysql_error()."</b></p>\n";
        }

        //Send Status Email
        if (lookupEmail($obj_row->userId) && $goodtogo)
        {
                $ms =  "Application Data Request Report\n";
                $ms .= "----------------------------------\n\n";
                $ms .= "Your submission of an application data for ".appIdToName($obj_row->appId).versionIdToName($obj_row->versionId)." has been accepted. ";
                $ms .= $_REQUEST['replyText'];
                $ms .= "We appreciate your help in making the Application Database better for all users.\n\n";
                $ms .= "Thanks!\n";
                $ms .= "-The AppDB admins\n";
                
                mail(stripslashes(lookupEmail($obj_row->userId)),'[AppDB] Application Data Request Report',$ms);
        }
        
        //done
        echo html_frame_start("Submit App Data","600");
        echo "<p><b>$statusMessage</b></p>\n";
    } elseif ($_REQUEST['reject'])
    {
       if (lookupEmail($obj_row->userId))
       {
           $ms =  "Application Data Request Report\n";
           $ms .= "----------------------------------\n\n";
           $ms .= "Your submission of an application data for ".appIdToName($obj_row->appId).versionIdToName($obj_row->versionId)." was rejected. ";
           $ms .= $_REQUEST['replyText'];
           $ms .= "";
           $ms .= "-The AppDB admins\n";
                
           mail(stripslashes(lookupEmail($obj_row->userId)),'[AppDB] Application Data Request Report',$ms);
       }

       //delete main item
       $str_query = "DELETE from appDataQueue where queueId = ".$obj_row->queueId.";";
       unlink("../data/queued/screenshots/".$obj_row->queueId);

       $result = mysql_query($str_query);
       echo html_frame_start("Delete application data submission",400,"",0);
       if(!$result)
       {
           //error
           echo "<p>Internal Error: unable to delete selected maintainer application!</p>\n";
       }
       else
       {
           //success
           echo "<p>Application data  was successfully deleted from the Queue.</p>\n";
       }
    }
    
}
echo html_frame_end("&nbsp;");        
echo html_back_link(1,'adminAppDataQueue.php');
apidb_footer();
?>

 
