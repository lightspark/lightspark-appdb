<?php
/***********************************************************/
/* this class represents an application incl. all versions */
/***********************************************************/

require_once(BASE."include/version.php");
require_once(BASE."include/vendor.php");
require_once(BASE."include/url.php");
require_once(BASE."include/util.php");
require_once(BASE."include/mail.php");

/**
 * Application class for handling applications.
 */
class Application {

    var $iAppId;
    var $iVendorId;
    var $iCatId;
    var $sName;
    var $sKeywords;
    var $sDescription;
    var $sWebpage;
    var $sQueued;
    var $sSubmitTime;
    var $iSubmitterId;
    var $aVersionsIds;  // an array that contains the versionId of every version linked to this app.

    /**    
     * constructor, fetches the data.
     */
    function Application($iAppId = null)
    {
        // we are working on an existing application
        if(is_numeric($iAppId))
        {
            /* fetch this applications information */
            $sQuery = "SELECT *
                       FROM appFamily 
                       WHERE appId = '?'";
            if($hResult = query_parameters($sQuery, $iAppId))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iAppId = $iAppId;
                $this->iVendorId = $oRow->vendorId;
                $this->iCatId = $oRow->catId;
                $this->iSubmitterId = $oRow->submitterId;
                $this->sSubmitTime = $oRow->submitTime;
                $this->sDate = $oRow->submitTime;
                $this->sName = $oRow->appName;
                $this->sKeywords = $oRow->keywords;
                $this->sDescription = $oRow->description;
                $this->sWebpage = $oRow->webPage;
                $this->sQueued = $oRow->queued;
            }

            /* fetch versions of this application, if there are any */
            $this->aVersionsIds = array();

            /* only admins can view all versions */
            //FIXME: it would be nice to move this permission into the user class as well as keep it generic
            if($_SESSION['current']->hasPriv("admin"))
            {
                $sQuery = "SELECT versionId FROM appVersion WHERE
                            appId = '?'";
            } else
            {
                $sQuery = "SELECT versionId FROM appVersion WHERE
                            queued = 'false' AND
                            appId = '?'";
            }
            if($hResult = query_parameters($sQuery, $this->iAppId))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aVersionsIds[] = $oRow->versionId;
                }
            }
        }
    }


    /**
     * Creates a new application.
     */
    function create()
    {
        if(!$_SESSION['current']->canCreateApplication())
            return;

        if($_SESSION['current']->appCreatedMustBeQueued())
            $this->sQueued = 'true';
        else
            $this->sQueued = 'false';

        $hResult = query_parameters("INSERT INTO appFamily (appName, description, keywords, ".
                                    "webPage, vendorId, catId, submitterId, queued) VALUES (".
                                    "'?', '?', '?', '?', '?', '?', '?', '?')",
                                    $this->sName, $this->sDescription, $this->sKeywords,
                                    $this->sWebpage, $this->iVendorId, $this->iCatId,
                                    $_SESSION['current']->iUserId, $this->sQueued);
        if($hResult)
        {
            $this->iAppId = mysql_insert_id();
            $this->application($this->iAppId);
            $this->SendNotificationMail();  // Only administrators will be mailed as no supermaintainers exist for this app.
            return true;
        } else
        {
            addmsg("Error while creating a new application.", "red");
            return false;
        }
    }


    /**
     * Update application.
     * Returns true on success and false on failure.
     */
    function update($bSilent=false)
    {
        $sWhatChanged = "";

        /* if the user doesn't have permission to modify this application, don't let them */
        if(!$_SESSION['current']->canModifyApplication($this))
            return;

        /* create an instance of ourselves so we can see what has changed */
        $oApp = new Application($this->iAppId);

        if ($this->sName && ($this->sName!=$oApp->sName))
        {
            if (!query_parameters("UPDATE appFamily SET appName = '?' WHERE appId = '?'",
                                  $this->sName, $this->iAppId))
                return false;
            $sWhatChanged .= "Name was changed from ".$oApp->sName." to ".$this->sName.".\n\n";
        }     

        if ($this->sDescription && ($this->sDescription!=$oApp->sDescription))
        {
            if (!query_parameters("UPDATE appFamily SET description = '?' WHERE appId = '?'",
                                  $this->sDescription, $this->iAppId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$oApp->sDescription."\n to \n".$this->sDescription.".\n\n";
        }

        if ($this->sKeywords && ($this->sKeywords!=$oApp->sKeywords))
        {
            if (!query_parameters("UPDATE appFamily SET keywords = '?' WHERE appId = '?'",
                                  $this->sKeywords, $this->iAppId))
                return false;
            $sWhatChanged .= "Keywords were changed from\n ".$oApp->sKeywords."\n to \n".$this->sKeywords.".\n\n";
        }

        if ($this->sWebpage && ($this->sWebpage!=$oApp->sWebpage))
        {
            if (!query_parameters("UPDATE appFamily SET webPage = '?' WHERE appId = '?'",
                                  $this->sWebpage, $this->iAppId))
                return false;
            $sWhatChanged .= "Web page was changed from ".$oApp->sWebpage." to ".$this->sWebpage.".\n\n";
        }
     
        if ($this->iVendorId && ($this->iVendorId!=$oApp->iVendorId))
        {
            if (!query_parameters("UPDATE appFamily SET vendorId = '?' WHERE appId = '?'",
                                  $this->iVendorId, $this->iAppId))
                return false;
            $oVendorBefore = new Vendor($oApp->iVendorId);
            $oVendorAfter = new Vendor($this->iVendorId);
            $sWhatChanged .= "Vendor was changed from ".$oVendorBefore->sName." to ".$oVendorAfter->sName.".\n\n";
        }

        if ($this->iCatId && ($this->iCatId!=$oApp->iCatId))
        {
            if (!query_parameters("UPDATE appFamily SET catId = '?' WHERE appId = '?'",
                                  $this->iCatId, $this->iAppId))
                return false;
            $oCatBefore = new Category($oApp->iCatId);
            $oCatAfter = new Category($this->iCatId);
            $sWhatChanged .= "Vendor was changed from ".$oCatBefore->sName." to ".$oCatAfter->sName.".\n\n";
        }
        if($sWhatChanged and !$bSilent)
            $this->SendNotificationMail("edit",$sWhatChanged);
        return true;
    }

    /**    
     * Deletes the application from the database. 
     * and request the deletion of linked elements.
     */
    function delete($bSilent=false)
    {
        /* make sure the current user has the appropriate permission to delete
           this application */
        if(!$_SESSION['current']->canDeleteApplication($this))
            return false;

        foreach($this->aVersionsIds as $iVersionId)
        {
            $oVersion = new Version($iVersionId);
            $oVersion->delete($bSilent);
        }


        /* fetch urlsIds */
        $aUrlsIds = array();
        $sQuery = "SELECT id
                       FROM appData
                       WHERE type = 'url'
                       AND appId = '?'";

        if($hResult = query_parameters($sQuery, $this->iAppId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aUrlsIds[] = $oRow->id;
            }
        }

        foreach($aUrlsIds as $iUrlId)
        {
            $oUrl = new Url($iUrlId);
            $oUrl->delete($bSilent);
        }

        // remove any supermaintainers for this application so we don't orphan them
        $sQuery = "DELETE from appMaintainers WHERE appId='?'";
        if(!($hResult = query_parameters($sQuery, $this->iAppId)))
        {
            addmsg("Error removing app maintainers for the deleted application!", "red");
        }

        $sQuery = "DELETE FROM appFamily 
                   WHERE appId = '?' 
                   LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iAppId)))
        {
            addmsg("Error deleting application!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        return true;
    }


    /**
     * Move application out of the queue.
     */
    function unQueue()
    {
        if(!$_SESSION['current']->canUnQueueApplication())
            return;

        if(query_parameters("UPDATE appFamily SET queued = '?', keywords = '?' WHERE appId = '?'",
                            "false",  str_replace(" *** ","",$this->sKeywords), $this->iAppId))
        {
            $this->sQueued = 'false';
            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->SendNotificationMail();
        }
    }

    function Reject()
    {
        if(!$_SESSION['current']->canRejectApplication($this))
            return;

        // If we are not in the queue, we can't move the application out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE appFamily SET queued = '?' WHERE appId = '?'",
                            "rejected", $this->iAppId))
        {
            $this->sQueued = 'rejected';
            // we send an e-mail to intersted people
            $this->mailSubmitter("reject");
            $this->SendNotificationMail("reject");

            // the application has been rejectedd
            addmsg("The application has been rejected.", "green");
        }
    }
    function ReQueue()
    {
        if(!$_SESSION['current']->canRequeueApplication($this))
            return false;

        if(query_parameters("UPDATE appFamily SET queued = '?' WHERE appId = '?'",
                            "true", $this->iAppId))
        {
            $this->sQueued = 'true';
            // we send an e-mail to intersted people
            $this->SendNotificationMail();

            // the application has been re-queued
            addmsg("The application has been re-queued.", "green");
        }
    }

    function mailSubmitter($sAction="add")
    {
        $aClean = array(); //array of filtered user input

        $aClean['replyText'] = makeSafe($_REQUEST['replyText']);	

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            switch($sAction)
            {
            case "add":
                $sSubject =  "Submitted application accepted";
                $sMsg  = "The application you submitted (".$oApp->sName." ".$this->sName.") has been accepted.";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject =  "Submitted application rejected";
                $sMsg  = "The application you submitted (".$oApp->sName." ".$this->sName.") has been rejected.";
                $sMsg .= "Clicking on the link in this email will allow you to modify and resubmit the application. ";
                $sMsg .= "A link to your queue of applications and versions will also show up on the left hand side of the Appdb site once you have logged in. ";
                $sMsg .= APPDB_ROOT."appsubmit.php?sub=view&apptype=application&appId=".$this->iAppId."\n";
                $sMsg .= "Reason given:\n";
            break;
            case "delete":
                $sSubject =  "Submitted application deleted";
                $sMsg  = "The application you submitted (".$oApp->sName." ".$this->sName.") has been deleted.";
                $sMsg .= "Reason given:\n";
            break;

            $sMsg .= $aClean['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
            }
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        $aClean = array(); //array of filtered user input

        $aClean['replyText'] = makeSafe($_REQUEST['replyText']);	

        switch($sAction)
        {
            case "add":
                if($this->sQueued == 'false') // Has been accepted.
                {
                    $sSubject = $this->sName." has been added by ".$_SESSION['current']->sRealname;
                    $sMsg  = APPDB_ROOT."appview.php?iAppId=".$this->iAppId."\n";
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This application has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                    }
                    if($aClean['replyText'])
                    {
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                    }

                    addmsg("The application was successfully added into the database.", "green");
                } else
                {
                    $sSubject = $this->sName." has been submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This application has been queued.";
                    $sMsg .= "\n";
                    addmsg("The application you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject =  $this->sName." has been modified by ".$_SESSION['current']->sRealname;
                $sMsg  .= APPDB_ROOT."appview.php?iAppId=".$this->iAppId."\n";
                addmsg("Application modified.", "green");
            break;
            case "delete":
                $sSubject = $this->sName." has been deleted by ".$_SESSION['current']->sRealname;

                // if replyText is set we should report the reason the application was deleted 
                if($aClean['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Application deleted.", "green");
            break;
            case "reject":
                $sSubject = $this->sName." has been rejected by ".$_SESSION['current']->sRealname;
                $sMsg .= APPDB_ROOT."appsubmit.php?sAppType=application&sSub=view&iAppId=".$this->iAppId."\n";

                // if replyText is set we should report the reason the application was rejected 
                if($aClean['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Application rejected.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list($this->iAppId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 


    /* output a html table and this applications values to the fields for editing */
    function OutputEditor($sVendorName)
    {
        HtmlAreaLoaderScript(array("app_editor"));

        echo '<input type="hidden" name="iAppId" value="'.$this->iAppId.'">';

        echo html_frame_start("Application Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";
        echo '<tr valign=top><td class="color0"><b>Application name</b></td>',"\n";
        echo '<td><input size="20" type="text" name="sAppName" value="'.$this->sName.'"></td></tr>',"\n";

        // app Category
        $w = new TableVE("view");
        echo '<tr valign=top><td class="color0"><b>Category</b></td><td>',"\n";
        $w->make_option_list("iAppCatId", $this->iCatId,"appCategory","catId","catName");
        echo '</td></tr>',"\n";

        // vendor name
        echo '<tr valign=top><td class="color0"><b>Vendor</b></td>',"\n";
        echo '<td><input size="20" type=text name="sAppVendorName" value="'.$sVendorName.'"></td></tr>',"\n";

        // alt vendor
        $x = new TableVE("view");
        echo '<tr valign=top><td class="color0">&nbsp;</td><td>',"\n";
        $x->make_option_list("iAppVendorId", $this->iVendorId,"vendor","vendorId","vendorName");
        echo '</td></tr>',"\n";

        // url
        echo '<tr valign=top><td class="color0"><b>URL</b></td>',"\n";
        echo '<td><input size="20" type=text name="sAppWebpage" value="'.$this->sWebpage.'"></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Keywords</b></td>',"\n";
        echo '<td><input size="90%" type="text" name="sAppKeywords" value="'.$this->sKeywords.'"></td></tr>',"\n";

        echo '<tr valign=top><td class="color0"><b>Application description</b></td>',"\n";
        echo '<td><p><textarea cols="80" rows="20" id="app_editor" name="shAppDescription">';

        echo $this->sDescription.'</textarea></p></td></tr>',"\n";

        echo "</table>\n";

        echo html_frame_end();
    }

    function CheckOutputEditorInput($aValues)
    {
        $errors = "";

        if (empty($aValues['iAppCatId']))
            $errors .= "<li>Please enter a category for your application.</li>\n";

        if (strlen($aValues['sAppName']) > 200 )
            $errors .= "<li>Your application name is too long.</li>\n";

        if (empty($aValues['sAppName']))
            $errors .= "<li>Please enter an application name.</li>\n";

        // No vendor entered, and nothing in the list is selected
        if (empty($aValues['sAppVendorName']) && !$aValues['iAppVendorId'])
            $errors .= "<li>Please enter a vendor.</li>\n";

        if (empty($aValues['shAppDescription']))
            $errors .= "<li>Please enter a description of your application.</li>\n";

        return $errors;
    }

    /* retrieves values from $_REQUEST that were output by OutputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from OutputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iAppId = $aValues['iAppId'];
        $this->sName = $aValues['sAppName'];
        $this->sDescription = $aValues['shAppDescription'];
        $this->iCatId = $aValues['iAppCatId'];
        $this->iVendorId = $aValues['iAppVendorId'];
        $this->sWebpage = $aValues['sAppWebpage'];
        $this->sKeywords = $aValues['sAppKeywords'];
    }

    /* display this application */
    function display()
    {
        /* is this user supposed to view this version? */
        if(!$_SESSION['current']->canViewApplication($this))
            util_show_error_page_and_exit("Something went wrong with the application or version id");

        // show Vote Menu
        if($_SESSION['current']->isLoggedIn())
            apidb_sidebar_add("vote_menu");

        // header
        apidb_header("Viewing App - ".$this->sName);

        // cat display
        display_catpath($this->iCatId, $this->iAppId);

        // set Vendor
        $oVendor = new Vendor($this->iVendorId);

        // set URL
        $appLinkURL = ($this->sWebpage) ? "<a href=\"".$this->sWebpage."\">".substr(stripslashes($this->sWebpage),0,30)."</a>": "&nbsp;";
  
        // start display application
        echo html_frame_start("","98%","",0);
        echo "<tr><td class=color4 valign=top>\n";
        echo "  <table>\n";
        echo "    <tr><td>\n";

        echo '      <table width="250" border="0" cellpadding="3" cellspacing="1">',"\n";
        echo "        <tr class=color0 valign=top><td width=\"100\"><b>Name</b></td><td width='100%'> ".$this->sName." </td>\n";
        echo "        <tr class=\"color1\"><td><b>Vendor</b></td><td> ".
            "        <a href='vendorview.php?iVendorId=$oVendor->iVendorId'> ".$oVendor->sName." </a> &nbsp;\n";
        echo "        <tr class=\"color0\"><td><b>Votes</b></td><td> ";
        echo vote_count_app_total($this->iAppId);
        echo "        </td></tr>\n";
    
        // main URL
        echo "        <tr class=\"color1\"><td><b>URL</b></td><td>".$appLinkURL."</td></tr>\n";

        // optional links
        $result = query_parameters("SELECT * FROM appData WHERE appId = '?' AND versionID = 0 AND type = 'url'",
                                   $this->iAppId);
        if($result && mysql_num_rows($result) > 0)
        {
            echo "        <tr class=\"color1\"><td> <b>Links</b></td><td>\n";
            while($oRow = mysql_fetch_object($result))
            {
                echo "        <a href='$oRow->url'>".substr(stripslashes($oRow->description),0,30)."</a> <br />\n";
            }
            echo "        </td></tr>\n";
        }

        // image
        $img = get_screenshot_img($this->iAppId);
        echo "<tr><td align=\"center\" colspan=\"2\">$img</td></tr>\n";
    
        echo "      </table>\n"; /* close of name/vendor/bugs/url table */

        echo "    </td></tr>\n";
        echo "    <tr><td>\n";

        // Display all supermaintainers maintainers of this application
        echo "      <table class=\"color4\" width=\"250\" border=\"1\">\n";
        echo "        <tr><td align=\"left\"><b>Super maintainers:</b></td></tr>\n";
        $other_maintainers = getSuperMaintainersUserIdsFromAppId($this->iAppId);
        if($other_maintainers)
        {
            echo "        <tr><td align=\"left\"><ul>\n";
            while(list($index, $userIdValue) = each($other_maintainers))
            {
                $oUser = new User($userIdValue);
                echo "        <li>".$oUser->sRealname."</li>\n";
            }
            echo "</ul></td></tr>\n";
        } else
        {
            echo "        <tr><td align=right>No maintainers.Volunteer today!</td></tr>\n";
        }

        // Display the app maintainer button
        echo '        <tr><td align="center">';
        if($_SESSION['current']->isLoggedIn())
        {
            /* are we already a maintainer? */
            if($_SESSION['current']->isSuperMaintainer($this->iAppId)) /* yep */
            {
                echo '        <form method="post" name="sMessage" action="maintainerdelete.php"><input type=submit value="Remove yourself as a super maintainer" class="button">';
            } else /* nope */
            {
                echo '        <form method="post" name="sMessage" action="maintainersubmit.php"><input type="submit" value="Be a super maintainer of this app" class="button" title="Click here to know more about super maintainers.">';
            }

            echo "        <input type=\"hidden\" name=\"iAppId\" value=\"".$this->iAppId."\">";
            echo "        <input type=\"hidden\" name=\"iSuperMaintainer\" value=\"1\">"; /* set superMaintainer to 1 because we are at the appFamily level */
            echo "        </form>";
            
            if($_SESSION['current']->isSuperMaintainer($this->iAppId) || $_SESSION['current']->hasPriv("admin"))
            {
                echo '        <form method="post" name="sEdit" action="admin/editAppFamily.php"><input type="hidden" name="iAppId" value="'.$this->iAppId.'"><input type="submit" value="Edit Application" class="button"></form>';
            }
            if($_SESSION['current']->isLoggedIn())
            {
                echo '<form method="post" name="sMessage" action="appsubmit.php?iAppId='.$this->iAppId.'&amp;sAppType=version&amp;sub=view">';
                echo '<input type=submit value="Submit new version" class="button">';
                echo '</form>';
            }
            if($_SESSION['current']->hasPriv("admin"))
            {
                $url = BASE."admin/deleteAny.php?sWhat=appFamily&amp;iAppId=".$this->iAppId."&amp;sConfirmed=yes";
                echo "        <form method=\"post\" name=\"sEdit\" action=\"javascript:deleteURL('Are you sure?', '".$url."')\"><input type=\"submit\" value=\"Delete App\" class=\"button\"></form>";
                echo '        <form method="post" name="sEdit" action="admin/editBundle.php"><input type="hidden" name="iBundleId" value="'.$this->iAppId.'"><input type="submit" value="Edit Bundle" class="button"></form>';
            }
        } else
        {
            echo '<form method="post" action="account.php?sCmd=login"><input type="submit" value="Log in to become a super maintainer" class="button"></form>';
        }
        echo "        </td></tr>\n";
        echo "      </table>\n"; /* close of super maintainers table */
        echo "    </td></tr>\n";
        echo "  </table>\n"; /* close the table that contains the whole left hand side of the upper table */

        // description
        echo "  <td class=color2 valign=top width='100%'>\n";
        echo "    <table width='100%' border=0><tr><td width='100%' valign=top><span class=\"title\">Description</span>\n";
        echo $this->sDescription;
        echo "    </td></tr></table>\n";
        echo html_frame_end("For more details and user comments, view the versions of this application.");

        // display versions
        Version::display_approved($this->aVersionsIds);

        // display bundle
        display_bundle($this->iAppId);
    }

    function lookup_name($appId)
    {
        if(!$appId) return null;
        $result = query_parameters("SELECT appName FROM appFamily WHERE appId = '?'",
                                   $appId);
        if(!$result || mysql_num_rows($result) != 1)
            return null;
        $ob = mysql_fetch_object($result);
        return $ob->appName;
    }

    function showList($hResult)
    {
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
            echo "    <td align=\"center\">[<a href=".$_SERVER['PHP_SELF']."?sAppType=application&sSub=view&iAppId=".$oApp->iAppId.">process</a>]</td>\n";
            echo "</tr>\n\n";
            $c++;
        }
        echo "</table>\n\n";
        echo html_frame_end("&nbsp;");
    }
}

?>
