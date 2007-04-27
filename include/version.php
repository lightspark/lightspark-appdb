<?php
/************************************/
/* this class represents an version */
/************************************/

require_once(BASE."include/note.php");
require_once(BASE."include/comment.php");
require_once(BASE."include/url.php");
require_once(BASE."include/screenshot.php");
require_once(BASE."include/bugs.php");
require_once(BASE."include/util.php");
require_once(BASE."include/testData.php");
require_once(BASE."include/downloadurl.php");

define("LICENSE_OPENSOURCE", "Open Source");
define("LICENSE_FREEWARE", "Freeware");
define("LICENSE_SHAREWARE", "Shareware");
define("LICENSE_DEMO", "Demo");
define("LICENSE_RETAIL", "Retail");

/**
 * Version class for handling versions.
 */
class Version {
    var $iVersionId;
    var $iAppId;
    var $sName;
    var $sDescription;
    var $sTestedRelease;
    var $sTestedRating;
    var $sSubmitTime;
    var $iSubmitterId;
    var $sDate;
    var $sQueued;
    var $sLicense;
    var $iMaintainerRequest; /* Temporary variable for version submisson.
                                Indicates whether the user wants to become a 
                                maintainer of the version being submitted.
                                Value denotes type of request. */

    /**
     * constructor, fetches the data.
     */
    function Version($iVersionId = null, $oRow = null)
    {
        // we are working on an existing version
        if(is_numeric($iVersionId))
        {
            /*
             * We fetch the data related to this version.
             */
            if(!$this->iVersionId)
            {
                if(!$oRow)
                {
                    $sQuery = "SELECT *
                        FROM appVersion
                        WHERE versionId = '?'";
                    if($hResult = query_parameters($sQuery, $iVersionId))
                        $oRow = mysql_fetch_object($hResult);
                }

                if($oRow)
                {
                    $this->iVersionId = $iVersionId;
                    $this->iAppId = $oRow->appId;
                    $this->iSubmitterId = $oRow->submitterId;
                    $this->sSubmitTime = $oRow->submitTime;
                    $this->sDate = $oRow->submitTime;
                    $this->sName = $oRow->versionName;
                    $this->sDescription = $oRow->description;
                    $this->sTestedRelease = $oRow->maintainer_release;
                    $this->sTestedRating = $oRow->maintainer_rating;
                    $this->sQueued = $oRow->queued;
                    $this->sLicense = $oRow->license;
                }
            }
        }
    }


    /**
     * Creates a new version.
     */
    function create()
    {
        if(!$_SESSION['current']->canCreateVersion())
            return;

        $this->sQueued = $this->mustBeQueued() ? "true" : "false";

        $hResult = query_parameters("INSERT INTO appVersion
                   (versionName, description, maintainer_release,
                   maintainer_rating, appId, submitterId, queued, license)
                       VALUES ('?', '?', '?', '?', '?', '?', '?', '?')",
                           $this->sName, $this->sDescription, $this->sTestedRelease,
                           $this->sTestedRating, $this->iAppId,
                           $_SESSION['current']->iUserId, $this->sQueued,
                           $this->sLicense);

        if($hResult)
        {
            $this->iVersionId = mysql_insert_id();
            $this->Version($this->iVersionId);
            $this->SendNotificationMail();

            /* Submit maintainer request if asked to */
            if($this->iMaintainerRequest == MAINTAINER_REQUEST)
            {
                $oMaintainer = new Maintainer();
                $oMaintainer->iAppId = $this->iAppId;
                $oMaintainer->iVersionId = $this->iVersionId;
                $oMaintainer->iUserId = $_SESSION['current']->iUserId;
                $oMaintainer->sMaintainReason = "This user submitted the version;". 
                                                "auto-queued.";
                $oMaintainer->bSuperMaintainer = 0;
                $oMaintainer->create();
            }
            return true;
        }
        else
        {
            addmsg("Error while creating a new version", "red");
            return false;
        }
    }


    /**
     * Update version.
     */
    function update($bSilent=false)
    {
        $sWhatChanged = "";

        if(!$_SESSION['current']->hasAppVersionModifyPermission($this))
            return;

        $oVersion = new Version($this->iVersionId);

        if ($this->sName && ($this->sName!=$oVersion->sName))
        {
            if (!query_parameters("UPDATE appVersion SET versionName = '?' WHERE versionId = '?'",
                                  $this->sName, $this->iVersionId))
                return false;
            $sWhatChanged .= "Name was changed from:\n\t'".$oVersion->sName."'\nto:\n\t'".$this->sName."'\n\n";
        }     

        if ($this->sDescription && ($this->sDescription!=$oVersion->sDescription))
        {
            if (!query_parameters("UPDATE appVersion SET description = '?' WHERE versionId = '?'",
                                  $this->sDescription, $this->iVersionId))
                return false;

            if($oVersion->sDescription != "")
                $sWhatChanged .= "Description was changed from\n ".$oVersion->sDescription."\n to \n".$this->sDescription.".\n\n";
            else
                $sWhatChanged .= "Description was changed to \n".$this->sDescription.".\n\n";
        }

        if ($this->sTestedRelease && ($this->sTestedRelease!=$oVersion->sTestedRelease))
        {
            if (!query_parameters("UPDATE appVersion SET maintainer_release = '?' WHERE versionId = '?'",
                                  $this->sTestedRelease, $this->iVersionId))
                return false;

            if($oVersion->sTestedRelease != "")
                $sWhatChanged .= "Last tested release was changed from ".$oVersion->sTestedRelease." to ".$this->sTestedRelease.".\n\n";
            else
                $sWhatChanged .= "Last tested release was changed to ".$this->sTestedRelease.".\n\n";
        }

        if ($this->sTestedRating && ($this->sTestedRating!=$oVersion->sTestedRating))
        {
            if (!query_parameters("UPDATE appVersion SET maintainer_rating = '?' WHERE versionId = '?'",
                                  $this->sTestedRating, $this->iVersionId))
                return false;

            if($this->sTestedRating != "")
                $sWhatChanged .= "Rating was changed from ".$oVersion->sTestedRating." to ".$this->sTestedRating.".\n\n";
            else
                $sWhatChanged .= "Rating was changed to ".$this->sTestedRating.".\n\n";
        }
     
        if ($this->iAppId && ($this->iAppId!=$oVersion->iAppId))
        {
            if (!query_parameters("UPDATE appVersion SET appId = '?' WHERE versionId = '?'",
                                  $this->iAppId, $this->iVersionId))
                return false;
            $oAppBefore = new Application($oVersion->iAppId);
            $oAppAfter = new Application($this->iAppId);
            $sWhatChanged .= "Version was moved from application ".$oAppBefore->sName." to application ".$oAppAfter->sName.".\n\n";
        }

        if ($this->sLicense && ($this->sLicense!=$oVersion->sLicense))
        {
            if(!query_parameters("UPDATE appVersion SET license = '?'
                                  WHERE versionId = '?'",
                                      $this->sLicense, $this->iVersionId))
            return FALSE;

            $sWhatChanged .= "License was changed from $oVersion->sLicense to ".
                             "$this->sLicense.\n\n";
        }

        if($sWhatChanged and !$bSilent)
            $this->SendNotificationMail("edit",$sWhatChanged);
        return true;
    }


    /**    
     * Deletes the version from the database. 
     * and request the deletion of linked elements.
     */
    function delete($bSilent=false)
    {
        /* We need the versionId to continue */
        if(!$this->iVersionId)
            return;

        /* is the current user allowed to delete this version? */
        if(!$_SESSION['current']->canDeleteVersion($this))
            return false;

        /* fetch notesIds */
        $aNotesIds = array();
        $sQuery = "SELECT noteId
                       FROM appNotes
                       WHERE versionId = '?'";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aNotesIds[] = $oRow->noteId;
            }
        }

        /* remove all of the items this version contains */
        foreach($aNotesIds as $iNoteId)
        {
            $oNote = new Note($iNoteId);
            $oNote->delete($bSilent);
        }


        /* We fetch commentsIds. */
        $aCommentsIds = array();
        $sQuery = "SELECT commentId
                       FROM appComments
                       WHERE versionId = '?'";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aCommentsIds[] = $oRow->commentId;
            }
        }

        foreach($aCommentsIds as $iCommentId)
        {
            $oComment = new Comment($iCommentId);
            $oComment->delete($bSilent);
        }


        /* fetch screenshotsIds and urlsIds  */
        $aScreenshotsIds = array();
        $aUrlsIds = array();
        $sQuery = "SELECT id, type
                       FROM appData
                       WHERE versionId = '?'";
        
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                if($oRow->type="image")
                    $aScreenshotsIds[] = $oRow->id;
                else
                    $aUrlsIds[] = $oRow->id;
            }
        }

        foreach($aScreenshotsIds as $iScreenshotId)
        {
            $oScreenshot = new Screenshot($iScreenshotId);
            $oScreenshot->delete($bSilent);
        }
        foreach($aUrlsIds as $iUrlId)
        {
            $oUrl = new Url($iUrlId);
            $oUrl->delete($bSilent);
        }

        $aBuglinkIds = $this->get_buglink_ids();
        foreach($aBuglinkIds as $iBug_id)
        {
            $oBug = new Bug($iBug_id);
            $oBug->delete($bSilent);
        }




        /* fetch Test Results Ids */
        $aTestingIds = array();
        $sQuery = "SELECT *
                       FROM testResults
                       WHERE versionId = '?'
                       ORDER BY testingId";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aTestingIds[] = $oRow->testingId;
            }
        }

        foreach($aTestingIds as $iTestId)
        {
            $oTest = new testData($iTestId);
            $oTest->delete($bSilent);
        }


        /* fetch monitor Ids */
        $aMonitorIds = array();
        $sQuery = "SELECT *
                       FROM appMonitors
                       WHERE versionId = '?'
                       ORDER BY monitorId";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aMonitorIds[] = $oRow->monitorId;
            }
        }

        foreach($aMonitorIds as $iMonitorId)
        {
            $oMonitor = new Monitor($iMonitorId);
            $oMonitor->delete($bSilent);
        }


        // remove any maintainers for this version so we don't orphan them
        $result = Maintainer::deleteMaintainersForVersion($this);
        if(!$result)
        {
            addmsg("Error removing version maintainers for the deleted version!", "red");
        }

        /* now delete the version */
        $hResult = query_parameters("DELETE FROM appVersion 
                                     WHERE versionId = '?' 
                                     LIMIT 1", $this->iVersionId);
        if(!$hResult)
        {
            addmsg("Error removing the deleted version!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        $this->mailSubmitter("delete");

        return true;
    }


    /**
     * Move version out of the queue.
     */
    function unQueue()
    {
        if(!$_SESSION['current']->canUnQueueVersion($this))
            return;

        // If we are not in the queue, we can't move the version out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE appVersion SET queued = '?' WHERE versionId = '?'",
                            "false", $this->iVersionId))
        {
            $this->sQueued = 'false';
            // we send an e-mail to interested people
            $this->mailSubmitter("add");
            $this->SendNotificationMail();

            /* Unqueue matching maintainer request */
            $hResultMaint = query_parameters("SELECT maintainerId FROM 
            appMaintainers WHERE userId = '?' AND versionId = '?'", 
            $this->iSubmitterId, $this->iVersionId);

            if($hResultMaint && mysql_num_rows($hResultMaint))
            {
                $oMaintainerRow = mysql_fetch_object($hResultMaint);
                $oMaintainer = new Maintainer($oMaintainerRow->maintainerId);
                $oMaintainer->unQueue("OK");
            }
        }
    }

    function Reject($bSilent=false)
    {
        if(!$_SESSION['current']->canRejectVersion($this))
            return;

        // If we are not in the queue, we can't move the version out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE appVersion SET queued = '?' WHERE versionId = '?'",
                            "rejected", $this->iVersionId))
        {
            $this->sQueued = 'rejected';
            // we send an e-mail to interested people
            if(!$bSilent)
            {
                $this->mailSubmitter("reject");
                $this->SendNotificationMail("reject");
            }
            // the version has been unqueued
            addmsg("The version has been rejected.", "green");
        }
    }

    function ReQueue()
    {
        if(!$_SESSION['current']->canRequeueVersion($this))
            return;

        if(query_parameters("UPDATE appVersion SET queued = '?' WHERE versionId = '?'",
                            "true", $this->iVersionId))
        {
            $this->sQueued = 'true';
            // we send an e-mail to interested people
            $this->SendNotificationMail();

            // the version has been unqueued
            addmsg("The version has been re-submitted", "green");
        }
    }

    function mailSubmitter($sAction="add")
    {
        global $aClean; //FIXME: we should pass the sReplyText value in

        // use 'sReplyText' if it is defined, otherwise define the value as an empty string
        if(!isset($aClean['sReplyText']))
            $aClean['sReplyText'] = "";

        if($this->iSubmitterId)
        {
            $oApp = new Application($this->iAppId);
            $oSubmitter = new User($this->iSubmitterId);
            switch($sAction)
            {
            case "add":
                $sSubject = "Submitted version accepted";
                $sMsg  = "The version you submitted (".$oApp->sName." ".$this->sName.") has been accepted by ".$_SESSION['current']->sRealname.".";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject = "Submitted version rejected";
                $sMsg  = "The version you submitted (".$oApp->sName." ".$this->sName.") has been rejected by ".$_SESSION['current']->sRealname.".";
                $sMsg .= "Clicking on the link in this email will allow you to modify and resubmit the version. ";
                $sMsg .= "A link to your queue of applications and versions will also show up on the left hand side of the Appdb site once you have logged in. ";
                $sMsg .= APPDB_ROOT."objectManager.php?sClass=version_queue".
                        "&bIsQueue=true&bIsRejected=true&iId=".$this->iVersionId."&".
                        "sTitle=Edit+Version\n";
            break;
            case "delete":
                $sSubject = "Submitted version deleted";
                $sMsg  = "The version you submitted (".$oApp->sName." ".$this->sName.") has been deleted by ".$_SESSION['current']->sRealname.".";
                $sMsg .= "Reason given:\n";
            break;
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Version Database better for all users.";
        
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        global $aClean;
        
        // use 'sReplyText' if it is defined, otherwise define the value as an empty string
        if(!isset($aClean['sReplyText']))
            $aClean['sReplyText'] = "";

        $oApp = new Application($this->iAppId);
        switch($sAction)
        {
            case "add":
                if($this->sQueued == "false")
                {
                    $sSubject = "Version ".$this->sName." of ".$oApp->sName." added by ".$_SESSION['current']->sRealname;
                    $sMsg  = $this->objectMakeUrl()."\n";
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This version has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                    }
                    if($aClean['sReplyText'])
                    {
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                    }

                    addmsg("The version was successfully added into the database.", "green");
                } else // Version queued.
                {
                    $sSubject = "Version '".$this->sName."' of '".$oApp->sName."' submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This version has been queued.";
                    $sMsg .= "\n";
                    addmsg("The version you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject =  "'".$oApp->sName." ".$this->sName."' has been modified by ".$_SESSION['current']->sRealname;
                $sMsg  .= $this->objectMakeUrl()."\n";
                addmsg("Version modified.", "green");
            break;
            case "delete":
                $sSubject = "Version '".$this->sName."' of '".$oApp->sName."' has been deleted by ".$_SESSION['current']->sRealname;

                // if sReplyText is set we should report the reason the application was deleted 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Version deleted.", "green");
            break;
            case "reject":
                $sSubject = "Version '".$this->sName."' of '".$oApp->sName."' has been rejected by ".$_SESSION['current']->sRealname;
                $sMsg .= APPDB_ROOT."objectManager.php?sClass=version_queue".
                        "&bIsQueue=true&bIsRejected=true&iId=".$this->iVersionId."&".
                        "sTitle=Edit+Version\n";

                // if sReplyText is set we should report the reason the version was rejected 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Version rejected.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 

    function get_buglink_ids()
    {
        /*
         * We fetch Bug linkIds. 
         */
        $aBuglinkIds = array();
        $sQuery = "SELECT *
                       FROM buglinks
                       WHERE versionId = '?'
                       ORDER BY bug_id";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                $aBuglinkIds[] = $oRow->linkId;
            }
        }

        return $aBuglinkIds;
    }

    /* output html and the current versions information for editing */
    /* if $editParentApplication is true that means we need to display fields */
    /* to let the user change the parent application of this version */
    /* otherwise, if $editParentAppliation is false, we leave them out */
    function outputEditor()
    {
        HtmlAreaLoaderScript(array("version_editor"));
        echo html_frame_start("Version Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" />';

        /* Fill in appId value */
        global $aClean;
        if(!$this->iAppId)
            $this->iAppId = $aClean['iAppId'];

        if($this->sQueued == "false" && $this->iVersionId)
        {
            // app parent
            $x = new TableVE("view");
            echo '<tr valign=top><td class=color0><b>Application</b></td>', "\n";
            echo '<td>',"\n";
            $x->make_option_list("iAppId",$this->iAppId,"appFamily","appId","appName");
            echo '</td></tr>',"\n";
        } else
        {
            echo '<input type="hidden" name="iAppId" value="'.$this->iAppId.'" />';
        }

        // version name
        echo '<tr valign=top><td class="color0"><b>Version name</b></td>',"\n";
        echo '<td><input size="20" type="text" name="sVersionName" value="'.$this->sName.'"></td></tr>',"\n";

        // version license
        echo html_tr(array(
            array("<b>License</b>", "class=\"color0\""),
            $this->makeLicenseList()));

        // version description
        echo '<tr valign=top><td class=color0><b>Version description</b></td>',"\n";
        echo '<td><p><textarea cols="80" rows="20" id="version_editor" name="shVersionDescription">',"\n";

        echo $this->sDescription.'</textarea></p></td></tr>',"\n";

        echo '</table>',"\n";

        echo html_frame_end();

        if($this->sQueued == "false" && $this->iVersionId)
        {
            echo html_frame_start("Info", "90%", "", 0);
            echo "<table border=0 cellpadding=2 cellspacing=0>\n";
            echo '<tr><td class="color4">Rating</td><td class="color0">',"\n";
            make_maintainer_rating_list("sMaintainerRating", $this->sTestedRating);
            echo '</td></tr>',"\n";
            echo '<tr><td class=color1>Release</td><td class=color0>',"\n";
            make_bugzilla_version_list("sMaintainerRelease", $this->sTestedRelease);
            echo '</td></tr>',"\n";
            echo html_table_end();
            echo html_frame_end();
        } else
        {
            echo '<input type="hidden" name="sMaintainerRating" value="'.$this->sTestedRating.'" />';
            echo '<input type="hidden" name="sMaintainerRelease" value="'.$this->sTestedRelease.'" />';
        }
    }

    function CheckOutputEditorInput($aValues)
    {
        $errors = "";

        if (empty($aValues['sVersionName']))
            $errors .= "<li>Please enter an application version.</li>\n";

        if (empty($aValues['shVersionDescription']))
            $errors .= "<li>Please enter a version description.</li>\n";

        return $errors;
    }

    /* retrieves values from $aValues that were output by outputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from outputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iAppId = $aValues['iAppId'];
        $this->iVersionId = $aValues['iVersionId'];
        $this->sName = $aValues['sVersionName'];
        $this->sDescription = $aValues['shVersionDescription'];
        $this->sTestedRating = $aValues['sMaintainerRating'];
        $this->sTestedRelease = $aValues['sMaintainerRelease'];
        $this->sLicense = $aValues['sLicense'];
        $this->iMaintainerRequest = $aValues['iMaintainerRequest'];
    }

    function display($iTestingId)
    {
        /* is this user supposed to view this version? */
        if(!$_SESSION['current']->canViewVersion($this))
            util_show_error_page_and_exit("Something went wrong with the application or version id");

        $oApp = new Application($this->iAppId);

        // Oops! application not found or other error. do something
        if(!$oApp->iAppId) 
            util_show_error_page_and_exit('Internal Database Access Error. No App found.');

        // Oops! Version not found or other error. do something
        if(!$this->iVersionId) 
            util_show_error_page_and_exit('Internal Database Access Error. No Version Found.');

        // show Vote Menu
        if($_SESSION['current']->isLoggedIn())
            apidb_sidebar_add("vote_menu");


        // header
        apidb_header("Viewing App- ".$oApp->sName." Version - ".$this->sName);

        // cat
        $oCategory = new Category($oApp->iCatId);
        $oCategory->display($oApp->iAppId, $this->iVersionId);
  
        // set URL
        $appLinkURL = ($oApp->sWebpage) ? "<a href=\"".$oApp->sWebpage."\">".substr(stripslashes($oApp->sWebpage),0,30)."</a>": "&nbsp;";

        // start version display
        echo html_frame_start("","98%","",0);
        echo '<tr><td class="color4" valign="top">',"\n";
        echo '<table width="250" border="0" cellpadding="3" cellspacing="1">',"\n";
        echo "<tr class=\"color0\" valign=\"top\"><td width=\"100\"> <b>Name</b></td><td width=\"100%\">".$oApp->sName."</td>\n";
        echo "<tr class=\"color1\" valign=\"top\"><td><b>Version</b></td><td>".$this->sName."</td></tr>\n";
        echo html_tr(array(
            "<b>License</b>",
            $this->sLicense),
            "color0");

        // main URL
        echo "        <tr class=\"color1\"><td><b>URL</b></td><td>".$appLinkURL."</td></tr>\n";

        // Votes
        echo html_tr(array(
            "<b>Votes</b>",
            vote_count_version_total($this->iVersionId)),
            "color0");

        if($this->sTestedRating != "/" && $this->sTestedRating)
            $sMaintainerColor = $this->sTestedRating;
        else
            $sMaintainerColor = "color0";

        // URLs
        if($sUrls = url::display($this->iVersionId))
            echo $sUrls;

        // rating Area
        echo "<tr class=\"$sMaintainerColor\" valign=\"top\"><td><b>Maintainer&#8217;s Rating</b></td><td>".$this->sTestedRating."</td></tr>\n";
        echo "<tr class=\"$sMaintainerColor\" valign=\"top\"><td><b>Maintainer&#8217;s Version</b></td><td>".$this->sTestedRelease."</td></tr>\n";

        // Download URLs
        if($sDownloadurls = downloadurl::display($this->iVersionId))
            echo $sDownloadurls;

        // image
        $img = Screenshot::get_random_screenshot_img($oApp->iAppId, $this->iVersionId, false);
        echo "<tr><td align=\"center\" colspan=\"2\">$img</td></tr>\n";

        // display all maintainers of this application
        echo "<tr class=\"color0\"><td align=\"left\" colspan=\"2\"><b>Maintainers of this version:</b>\n";
        echo "<table width=\"250\" border=\"0\">";
        $aMaintainers = $this->getMaintainersUserIds();
        if(sizeof($aMaintainers)>0)
        {
            echo "<tr class=\"color0\"><td align=\"left\" colspan=\"2\"><ul>";
            while(list($index, $userIdValue) = each($aMaintainers))
            {
                $oUser = new User($userIdValue);
                echo "<li>".$oUser->objectMakeLink()."</li>";
            }
            echo "</ul></td></tr>\n";
        } else
        {
            echo "<tr class=color0><td align=right colspan=2>";
            echo "No maintainers. Volunteer today!</td></tr>\n";
        }
        echo "</table></td></tr>\n";

        // display the app maintainer button
        echo '<tr><td colspan="2" align="center">'."\n";
        if($_SESSION['current']->isLoggedIn())
        {
            /* is this user a maintainer of this version by virtue of being a super maintainer */
            /* of this app family? */
            if($_SESSION['current']->isSuperMaintainer($oApp->iAppId))
            {
                echo '<form method="post" name="sMessage" action="maintainerdelete.php">'."\n";
                echo "\t".'<input type="submit" value="Remove yourself as a super maintainer" class="button">'."\n";
                echo "\t".'<input type="hidden" name="iSuperMaintainer" value="1">'."\n";
                echo "\t<input type=hidden name=\"iAppId\" value=\"".$oApp->iAppId."\">\n";
                echo "\t<input type=hidden name=\"iVersionId\" value=\"".$this->iVersionId."\">\n";
                echo "</form>\n";
            } else
            {
                /* are we already a maintainer? */
                if($_SESSION['current']->isMaintainer($this->iVersionId)) /* yep */
                {
                    echo '<form method="post" name="sMessage" action="maintainerdelete.php">'."\n";
                    echo "\t".'<input type="submit" value="Remove yourself as a maintainer" class=button>'."\n";
                    echo "\t".'<input type="hidden" name="iSuperMaintainer" value="0">'."\n";
                    echo "\t"."<input type=hidden name=\"iAppId\" value=\"".$oApp->iAppId."\">\n";
                    echo "\t"."<input type=hidden name=\"iVersionId\" value=\"".$this->iVersionId."\">\n";
                    echo "</form>\n";
                } else /* nope */
                {
                    echo '<form method="post" name="sMessage" action="maintainersubmit.php">'."\n";
                    echo "\t".'<input type="submit" value="Be a Maintainer for This Version" class="button" title="Click here to know more about maintainers.">'."\n";
                    echo "\t"."<input type=hidden name=\"iAppId\" value=\"".$oApp->iAppId."\">\n";
                    echo "\t"."<input type=hidden name=\"iVersionId\" value=\"".$this->iVersionId."\">\n";
                    echo "</form>\n";
                    $oMonitor = new Monitor();
                    $oMonitor->find($_SESSION['current']->iUserId, $this->iVersionId);
                    if(!$oMonitor->iMonitorId)
                    {
                        echo '<form method="post" name="sMessage" action='.
                                $this->objectMakeUrl().'&iAppId='.$oApp->iAppId.'>'."\n";
                        echo "\t".'<input type=hidden name="sSub" value="StartMonitoring" />'."\n";
                        echo "\t".'<input type=submit value="Monitor Changes" class="button" />'."\n";
                        echo "</form>\n";
                    }
                }
            }
            
        } else
        {
            echo '<form method="post" name="sMessage" action="account.php">'."\n";
            echo "\t".'<input type="hidden" name="sCmd" value="login">'."\n";
            echo "\t".'<input type=submit value="Log in to become an app maintainer" class="button">'."\n";
            echo '</form>'."\n";
        }
    
        echo "</td></tr>";

        if ($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($this->iVersionId) || $_SESSION['current']->isSuperMaintainer($this->iAppId))
        {
            echo '<tr><td colspan="2" align="center">'."\n";
            echo '<form method="post" name="sMessage" action="admin/editAppVersion.php">'."\n";
            echo "\t".'<input type="hidden" name="iAppId" value="'.$oApp->iAppId.'" />'."\n";
            echo "\t".'<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" />'."\n";
            echo "\t".'<input type=submit value="Edit Version" class="button" />'."\n";
            echo '</form>'."\n";
            $url = BASE."admin/deleteAny.php?sWhat=appVersion&amp;iAppId=".$oApp->iAppId."&amp;iVersionId=".$this->iVersionId."&amp;sConfirmed=yes";
            echo "<form method=\"post\" name=\"sDelete\" action=\"javascript:deleteURL('Are you sure?', '".$url."')\">\n";
            echo "\t".'<input type=submit value="Delete Version" class="button" />'."\n";
            echo '</form>'."\n";
            echo '<form method="post" name="message" action="admin/addAppNote.php">'."\n";
            echo "\t".'<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" />'."\n";
            echo "\t".'<input type="submit" value="Add Note" class="button" />'."\n";
            echo '</form>'."\n";
            echo '<form method=post name=message action=admin/addAppNote.php?iVersionId='.$this->iVersionId.'>'."\n";
            echo "\t".'<input type=hidden name="sNoteTitle" value="HOWTO" />'."\n";
            echo "\t".'<input type=submit value="Add How To" class="button" />'."\n";
            echo '</form>'."\n";
            echo '<form method=post name=message action=admin/addAppNote.php?iVersionId='.$this->iVersionId.'>'."\n";
            echo "\t".'<input type=hidden name="sNoteTitle" value="WARNING" />'."\n";
            echo "\t".'<input type=submit value="Add Warning" class="button" />'."\n";
            echo '</form>';
            echo "</td></tr>";
        }
        $oMonitor = new Monitor();
        $oMonitor->find($_SESSION['current']->iUserId, $this->iVersionId);
        if($oMonitor->iMonitorId)
        {
            echo '<tr><td colspan="2" align="center">'."\n";
            echo '</form>'."\n";
            echo '<form method="post" name="sMessage" action='.
                    $this->objectMakeUrl().'>'."\n";
            echo '<input type=hidden name="sSub" value="StopMonitoring" />'."\n";
            echo '<input type=submit value="Stop Monitoring Version" class="button" />'."\n";
            echo "</form>\n";
            echo "</td></tr>\n";
        } 
        echo "</table>\n";

        // start of the right hand pane in the version display
        echo "<td class=color2 valign=top width='100%'>\n";
        echo "<div class='version_info_pane'>\n";

        /////////////////////////
        // output the description
        echo "<div class='info_container'>\n";

        // output the description title
        echo "\t<div class='title_class'>\n";
        echo "\t\tDescription\n";
        echo "\t</div>\n";

        // output the description
        echo "\t<div class='info_contents'>\n";
        echo "\t\t".$this->sDescription."\n";
        echo "\t</div>\n";

        echo "</div>\n"; // end the 'info_container' div
        // end description
        /////////////////////////


        //////////////////////
        // Show test data
        echo "<div class='info_container'>\n";

        echo "\t<div class='title_class'>\n";
        echo "\t\tSelected test results <small><small>(selected in 'Test Results' table below)</small></small>\n";
        echo "\t</div>\n";
        $oTest = new testData($iTestingId);

        /* if $iTestingId wasn't valid then it won't be valid in $oTest */
        if(!$oTest->iTestingId)
        {
            /* fetch a new test id for this version */
            $iTestingId = testData::getNewestTestIdFromVersionId($this->iVersionId);
            $oTest = new testData($iTestingId);
        }

        echo "<div class='info_contents'>\n";
        $oTest->ShowTestResult();
        echo "</div>\n";

        echo "</div>\n"; // end the 'info_container' div
        // end show test data
        /////////////////////


        //////////////////////////////
        // show the test results table
        if($oTest->iTestingId)
        {
            $oTest->ShowVersionsTestingTable($_SERVER['PHP_SELF']."?iVersionId=".$this->iVersionId."&iTestingId=",
                                             5);
        }
        if($_SESSION['current']->isLoggedIn())
        {
            echo '<form method=post name=sMessage action=objectManager.php?'.
                    'sClass=testData_queue&sAction=add&iVersionId='.$this->iVersionId.
                    '&sTitle=Add+Test+Data>'."\n";
            echo "\t".'<input type=submit value="Add Test Data" class="button" />'."\n";
            echo '</form>'."\n";
        } else
        {
            echo '<form method="post" name="sMessage" action="account.php">'."\n";
            echo "\t".'<input type="hidden" name="sCmd" value="login">'."\n";
            echo "\t".'<input type=submit value="Log in add Test Data" class="button">'."\n";
            echo '</form>'."\n";
        }

        // end show test results table
        /////////////////////////////


        echo "</div>\n"; // end the version info pane, the right hand pane in the
                         // version display

        echo html_frame_end();

        view_version_bugs($this->iVersionId, $this->get_buglink_ids());    

        /* display the notes for the application */
        $hNotes = query_parameters("SELECT noteId FROM appNotes WHERE versionId = '?'",
                                   $this->iVersionId);
    
        while( $oRow = mysql_fetch_object($hNotes) )
        {
            $oNote = new Note($oRow->noteId);
            $oNote->show();
        }
    
        // Comments Section
        Comment::view_app_comments($this->iVersionId);
    }

    function lookup_name($versionId)
    {
        if(!$versionId) return null;
        $result = query_parameters("SELECT versionName FROM appVersion WHERE versionId = '?'",
                                   $versionId);
        if(!$result || mysql_num_rows($result) != 1)
            return null;
        $ob = mysql_fetch_object($result);
        return $ob->versionName;
    }

    function fullName($iVersionId)
    {
        if(!$iVersionId)
            return FALSE;

        $hResult = query_parameters(
            "SELECT appFamily.appName, appVersion.versionName
                FROM appVersion, appFamily WHERE appVersion.appId = appFamily.appId
                AND versionId = '?'",
                    $iVersionId);

        if(!$hResult || !mysql_num_rows($hResult))
            return FALSE;

        $oRow = mysql_fetch_object($hResult);
        return "$oRow->appName $oRow->versionName";
    }

    /* Creates a link to the version labelled with the full application name */
    function fullNameLink($iVersionId)
    {
        $oVersion = new version($iVersionId);
        $sLink = "<a href=\"".$oVersion->objectMakeUrl()."\">".
                $oVersion->fullName($iVersionId)."</a>";
        return $sLink;
    }

    // display the versions
    function display_approved($aVersionsIds)
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
                    $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

                    if($oVersion->sTestedRating && $oVersion->sTestedRating != "/")
                        $sRatingColor = "class=\"$oVersion->sTestedRating\"";
                    else
                        $sRatingColor = "class=\"$bgcolor\"";

                    //display row
                    echo "<tr class=$bgcolor>\n";
                    echo "    <td>".$oVersion->objectMakeLink()."</td>\n";
                    echo "    <td>".util_trim_description($oVersion->sDescription)."</td>\n";
                    echo "    <td $sRatingColor align=center>".$oVersion->sTestedRating."</td>\n";
                    echo "    <td $sRatingColor align=center>".$oVersion->sTestedRelease."</td>\n";
                    echo "    <td align=center>".Comment::get_comment_count_for_versionid($oVersion->iVersionId)."</td>\n";
                    echo "</tr>\n\n";

                    $c++;   
                }
            }
            echo "</table>\n";
            echo html_frame_end("Click the Version Name to view the details of that Version");
        }
    }

    /* returns the maintainers of this version in an array */
    function getMaintainersUserIds()
    {
        $aMaintainers = array();

        /* early out if the versionId isn't valid */
        if($this->iVersionId == 0)
            return $aMaintainers;
    
        $hResult = Maintainer::getMaintainersForAppIdVersionId(null, $this->iVersionId);
        $iCount = 0;
        while($oRow = mysql_fetch_object($hResult))
        {
            $aMaintainers[$iCount] = $oRow->userId;
            $iCount++;
        }

        return $aMaintainers;
    }

    /* List the versions submitted by a user.  Ignore versions for queued applications */
    function listSubmittedBy($iUserId, $bQueued = true)
    {
        $hResult = query_parameters("SELECT appFamily.appName, appVersion.versionName, appVersion.description, appVersion.versionId, appVersion.submitTime FROM appFamily, appVersion WHERE appFamily.appId = appVersion.appId AND appVersion.submitterId = '?' AND appVersion.queued = '?' AND appFamily.queued = '?'", $iUserId, $bQueued ? "true" : "false", "false");

        if(!$hResult || !mysql_num_rows($hResult))
            return false;

        $sResult = html_table_begin("width=\"100%\" align=\"center\"");
        $sResult .= html_tr(array(
            "Name",
            "Description",
            "Submission Date"),
            "color4");

        for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
            $sResult .= html_tr(array(
                version::fullNameLink($oRow->versionId),
                $oRow->description,
                print_date(mysqltimestamp_to_unixtimestamp($oRow->submitTime))),
                ($i % 2) ? "color0" : "color1");

        $sResult .= html_table_end();

        return $sResult;
    }

    function makeLicenseList($sLicense = NULL)
    {
        if(!$sLicense)
            $sLicense = $this->sLicense;

        $sReturn = "<select name=\"sLicense\">\n";
        $sReturn .= "<option value=\"\">Choose . . .</option>\n";
        $aLicense = array(LICENSE_RETAIL, LICENSE_OPENSOURCE, LICENSE_FREEWARE,
                          LICENSE_DEMO, LICENSE_SHAREWARE);
        $iMax = count($aLicense);

        for($i = 0; $i < $iMax; $i++)
        {
            if($aLicense[$i] == $sLicense)
                $sSelected = " selected=\"selected\"";
            else
                $sSelected = "";

                $sReturn .= "<option value=\"$aLicense[$i]\"$sSelected>".
                            "$aLicense[$i]</option>\n";
        }

        $sReturn .= "</select>\n";

        return $sReturn;
    }

    /* In order to prevent MySQL injections.  Returns matched license */
    function checkLicense($sLicense)
    {
        $aLicense = array(LICENSE_RETAIL, LICENSE_OPENSOURCE, LICENSE_FREEWARE,
                          LICENSE_DEMO, LICENSE_SHAREWARE);

        foreach($aLicense as $sElement)
        {
            if($sLicense == $sElement)
                return $sElement;
        }

        return FALSE;
    }

    function objectMakeUrl()
    {
        return APPDB_ROOT."appview.php?iVersionId=$this->iVersionId";
    }

    function objectMakeLink()
    {
        $sLink = "<a href=\"".$this->objectMakeUrl()."\">".
                 $this->sName."</a>";
        return $sLink;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        $sQueued = objectManager::getQueueString($bQueued, $bRejected);

        $oVersion = new version();
        if($bQueued && !$oVersion->canEdit())
        {
            /* Users should see their own rejected entries, but maintainers should
               not be able to see rejected entries for versions they maintain */
            if($bRejected)
                $sQuery = "SELECT COUNT(DISTINCT appVersion.versionId) as count FROM
                        appVersion, appFamily WHERE
                        appFamily.appId = appVersion.appId
                        AND
                        appFamily.queued = 'false'
                        AND
                        appVersion.submitterId = '?'
                        AND
                        appVersion.queued = '?'";
            else
                $sQuery = "SELECT COUNT(DISTINCT appVersion.versionId) as count FROM
                        appVersion, appMaintainers, appFamily WHERE
                        appFamily.appId = appVersion.appId
                        AND
                        appFamily.queued = 'false'
                        AND
                        (
                            (
                                appMaintainers.appId = appVersion.appId
                                AND
                                superMaintainer = '1'
                            )
                            OR
                            (
                                appMaintainers.versionId = appVersion.versionId
                                AND
                                superMaintainer = '0'
                            )
                        )
                        AND
                        appMaintainers.userId = '?'
                        AND
                        appMaintainers.queued = 'false'
                        AND
                        appVersion.queued = '?'";

            $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId, $sQueued);
        } else
        {
            $sQuery = "SELECT COUNT(DISTINCT versionId) as count
                    FROM appVersion, appFamily WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    appFamily.queued = 'false'
                    AND
                    appVersion.queued = '?'";
            $hResult = query_parameters($sQuery, $sQueued);
        }

        if(!$hResult)
            return FALSE;

        if(!$oRow = mysql_fetch_object($hResult))
            return FALSE;

        return $oRow->count;
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;

        else if($this->iVersionId)
        {
            if(maintainer::isUserMaintainer($_SESSION['current'], $this->iVersionId))
                return TRUE;

            if($this->iSubmitterId == $_SESSION['current']->iUserId)
                return TRUE;

            return FALSE;
        }
        else
            return FALSE;
    }

    function mustBeQueued()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return FALSE;

        // if we have a valid iAppId or iVersionId we should
        // check the status of these objects to determine whether
        // we need to queue this version object
        if($this->iVersionId or $this->iAppId)
        {
            // if the user is the super maintainer of the application then
            // they are authorized to unqueue versions of this application
            // so the version doesn't have to be queued
            if($this->iAppId && 
               maintainer::isUserSuperMaintainer($_SESSION['current'], $this->iAppId))
                return FALSE;

            // if the user is a maintainer of this version then
            // this version doesn't have to be queued
            if($this->iVersionId && 
               maintainer::isUserMaintainer($_SESSION['current'], $this->iVersionId))
                return FALSE;

            return TRUE;
        } else
        {
            return TRUE;
        }
    }

    function objectGetHeader()
    {
        $aCells = array(
                "Submission Date",
                "Submitter",
                "Vendor",
                "Application",
                "Version");
        return $aCells;
    }

    function objectGetEntries($bQueued, $bRejected)
    {
        $sQueued = objectManager::getQueueString($bQueued, $bRejected);

        if($bQueued && !$this->canEdit())
        {
            /* Users should see their own rejected entries, but maintainers should
               not be able to see rejected entries for versions they maintain */
            if($bRejected)
                $sQuery = "SELECT appVersion.* FROM
                        appVersion, appFamily WHERE
                        appFamily.appId = appVersion.appId
                        AND
                        appFamily.queued = 'false'
                        AND
                        appVersion.submitterId = '?'
                        AND
                        appVersion.queued = '?'";
            else
                $sQuery = "SELECT appVersion.* FROM
                        appVersion, appMaintainers, appFamily WHERE
                        appFamily.appId = appVersion.appId
                        AND
                        appFamily.queued = 'false'
                        AND
                        (
                            (
                                appMaintainers.appId = appVersion.appId
                                AND
                                superMaintainer = '1'
                            )
                            OR
                            (
                                appMaintainers.versionId = appVersion.versionId
                                AND
                                superMaintainer = '0'
                            )
                        )
                        AND
                        appMaintainers.userId = '?'
                        AND
                        appMaintainers.queued = 'false'
                        AND
                        appVersion.queued = '?'";

            $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId, $sQueued);
        } else
        {
            $sQuery = "SELECT appVersion.*
                    FROM appVersion, appFamily WHERE
                    appFamily.appId = appVersion.appId
                    AND
                    appFamily.queued = 'false'
                    AND
                    appVersion.queued = '?'";
            $hResult = query_parameters($sQuery, $sQueued);
        }

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        $oUser = new user($this->iSubmitterId);
        $oApp = new application($this->iAppId);
        $oVendor = new vendor($oApp->iVendorId);
        $aCells = array(
                print_date(mysqltimestamp_to_unixtimestamp($this->sSubmitTime)),
                $oUser->objectMakeLink(),
                $oVendor->objectMakeLink(),
                $oApp->objectMakeLink(),
                $this->sName);

        if($this->canEdit())
        {
            $aCells[] = "[ <a href=\"".$oObject->makeUrl("edit",
                $this->iVersionId)."\">$sEditLinkLabel</a> ]";
        }

        echo html_tr($aCells, $sClass);
    }

    function objectGetInstanceFromRow($oRow)
    {
        return new version($oRow->versionId, $oRow);
    }

    function objectDisplayQueueProcessingHelp()
    {
        echo "<p>This is the list of versions waiting for your approval, ".
             "or to be rejected.</p>\n";
        echo "<p>To view a submission, click on its name. ".
             "From that page you can edit, delete or approve it into the AppDB.</p>\n";
    }

    function objectMoveChildren($iNewId)
    {
        /* Keep track of how many items we have updated */
        $iCount = 0;

        /* Move test results */
        $sQuery = "SELECT * FROM testResults WHERE versionId = '?'";
        $hResult = query_parameters($sQuery, $this->iVersionId);

        if(!$hResult)
            return FALSE;

        while($oRow = mysql_fetch_object($hResult))
        {
            $oTestData = new testData($oRow->testingId);
            $oTestData->iVersionId = $iNewId;
            if($oTestData->update())
                $iCount++;
            else
                return FALSE;
        }

        /* Move all app data */
        $sQuery = "SELECT * FROM appData WHERE versionId = '?'";
        $hResult = query_parameters($sQuery, $this->iVersionId);

        if(!$hResult)
            return FALSE;

        while($oRow = mysql_fetch_object($hResult))
        {
            $oAppData = new appData($oRow->testingId);
            $oAppData->iVersionId = $iNewId;
            if($oAppData->update(TRUE))
                $iCount++;
            else
                return FALSE;
        }

        /* Return the number of updated objects if everything was successful */
        return $iCount;
    }
}

?>
