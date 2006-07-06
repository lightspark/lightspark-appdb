<?php
require_once(BASE."include/util.php");
/******************************************/
/* bug class and related functions */
/******************************************/


/**
 * Bug Link class for handling Bug Links and thumbnails
 */
class Bug {
    var $iLinkId;
    var $iBug_id;
    var $sShort_desc;
    var $sBug_status;
    var $sResolution;
    var $iVersionId;
    var $iAppId;
    var $sSubmitTime;
    var $iSubmitterId;
    var $bQueued;

    /**    
     * Constructor, fetches the data and bug objects if $ilinkId is given.
     */
    function bug($iLinkId = null)
    {
        // we are working on an existing Bug
        if(is_numeric($iLinkId))
        {
            $sQuery = "SELECT buglinks.*, appVersion.appId AS appId
                       FROM buglinks, appVersion 
                       WHERE buglinks.versionId = appVersion.versionId 
                       AND linkid = '?'";
            if($hResult = query_parameters($sQuery, $iLinkId))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iLinkId = $iLinkId;
                $this->iAppId = $oRow->appId;
                $this->iBug_id = $oRow->bug_id;
                $this->iVersionId = $oRow->versionId;
                $this->bQueued = ($oRow->queued=="true")?true:false;
                $this->sSubmitTime = $oRow->submitTime;
                $this->iSubmitterId = $oRow->submitterId;
                /* lets fill in some blanks */ 
                if ($this->iBug_id)
                {
                    $sQuery = "SELECT *
                              FROM bugs 
                              WHERE bug_id = ".$this->iBug_id;
                    if($hResult = query_bugzilladb($sQuery))
                    {
                        $oRow = mysql_fetch_object($hResult);
                        $this->sShort_desc = $oRow->short_desc;
                        $this->sBug_status = $oRow->bug_status;
                        $this->sResolution = $oRow->resolution;
                    }
                }
            }
        }
    }
 

    /**
     * Creates a new Bug.
     */
    function create($iVersionId = null, $iBug_id = null)
    {
        $oVersion = new Version($iVersionId);
        // Security, if we are not an administrator or a maintainer, the Bug must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($oVersion->iVersionId) || $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $this->bQueued = true;
        } else
        {
            $this->bQueued = false;
        }
        /* lets check for a valid bug id */

        if(!is_numeric($iBug_id))
        {
            addmsg($iBug_id." is not a valid bug number.", "red");
            return false;
        }

        /* check that bug # exists in bugzilla*/

        $sQuery = "SELECT *
                   FROM bugs 
                   WHERE bug_id = ".$iBug_id;
        if(mysql_num_rows(query_bugzilladb($sQuery, "checking bugzilla")) == 0)
        {
            addmsg("There is no bug in Bugzilla with that bug number.", "red");
            return false;
        }

        /* Check for Duplicates */

        $sQuery = "SELECT *
                   FROM buglinks 
                   WHERE versionId = '?'";
        if($hResult = query_parameters($sQuery, $iVersionId))
        {
            while($oRow = mysql_fetch_object($hResult))
            {
                if($oRow->bug_id == $iBug_id)
                {
                   addmsg("The Bug link has already been submitted.", "red");
                   return false;
                }
            }
        }

        /* passed the checks so lets insert the puppy! */

        $hResult = query_parameters("INSERT INTO buglinks (versionId, bug_id, queued, submitterId) ".
                                    "VALUES('?', '?', '?', '?')",
                                    $iVersionId, $iBug_id, $this->bQueued?"true":"false",
                                    $_SESSION['current']->iUserId);
        if($hResult)
        {
            /* The following should work but it does not! */
            $this->iLinkId = mysql_insert_id();
            $this->bug($this->iLinkId);
            /* Start of hack to get around the previous not working */
            $sQuery = "SELECT buglinks.*, appVersion.appId AS appId
                       FROM buglinks, appVersion 
                       WHERE buglinks.versionId = appVersion.versionId 
                       AND buglinks.versionId = '?'
                       AND buglinks.bug_id = '?'";
            if($hResult = query_parameters($sQuery, $iVersionId, $iBug_id))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->bug($oRow->linkId);
            }
            /*End of Hack */

            $this->SendNotificationMail();
            return true;
        }else
        {
            addmsg("Error while creating a new Bug link.", "red");
            return false;
        }
    }


    /**    
     * Deletes the Bug from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM buglinks 
                   WHERE linkId = '?'";
        if($hResult = query_parameters($sQuery, $this->iLinkId))
        {
            if(!$bSilent)
                $this->SendNotificationMail(true);
        }
        if($this->iSubmitterId)
        {
            $this->mailSubmitter(true);
        }

    }


    /**
     * Move Bug out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the Bug out of the queue.
        if(!$this->bQueued)
            return false;

        if(query_parameters("UPDATE buglinks SET queued = '?' WHERE linkId='?'",
                            "false", $this->iLinkId))
        {
            $this->bQueued = false;
            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->SendNotificationMail();
            // the Bug has been unqueued
            addmsg("The Bug has been unqueued.", "green");
        }
    }


    function mailSubmitter($bRejected=false)
    {
        $aClean = array(); //array of filtered user input

        $aClean['replyText'] = makeSafe($_REQUEST['replyText']);
	
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            $sAppName = Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted Bug Link accepted";
                $sMsg  = "The Bug Link you submitted for ".$sAppName." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted Bug Link rejected";
                 $sMsg  = "The Bug Link you submitted for ".$sAppName." has been rejected.";
            }
            $sMsg .= $aClean['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($bDeleted=false)
    {
        $sAppName = Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId);
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This Bug Link has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The Bug Link was successfully added into the database.", "green");
            } else // Bug Link queued.
            {
                $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                $sMsg .= "This Bug Link has been queued.";
                $sMsg .= "\n";
                addmsg("The Bug Link you submitted will be added to the database after being reviewed.", "green");
            }
        } else // Bug Link deleted.
        {
            $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
            addmsg("Bug Link deleted.", "green");
        }

        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
        {
            mail_appdb($sEmail, $sSubject ,$sMsg);
        }
    } 
}


/*
 * Bug Link functions that are not part of the class
 */

function view_version_bugs($iVersionId = null, $aBuglinkIds)
{
    $aClean = array(); //array of filtered user input

    $aClean['buglinkId'] = makeSafe($_REQUEST['buglinkId']);

    $bCanEdit = FALSE;
    $oVersion = new Version($iVersionId);

    // Security, if we are an administrator or a maintainer, we can remove or ok links.
    if(($_SESSION['current']->hasPriv("admin") ||
                 $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
                 $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
    {
        $bCanEdit = TRUE;
    } 
    
    //start format table
    if($_SESSION['current']->isLoggedIn())
    {
        echo "<form method=post action='appview.php?iVersionId=".$iVersionId."'>\n";
    }
    echo html_frame_start("Known bugs","98%",'',0);
    echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"1\">\n\n";
    echo "<tr class=color4>\n";
    echo "    <td align=center width=\"80\">Bug #</td>\n";
    echo "    <td>Description</td>\n";
    echo "    <td align=center width=\"80\">Status</td>\n";
    echo "    <td align=center width=\"80\">Resolution</td>\n";
    echo "    <td align=center width=\"80\">Other Apps affected</td>\n";

    if($bCanEdit == true)
    {
        echo "    <td align=center width=\"80\">delete</td>\n";
        echo "    <td align=center width=\"80\">checked</td>\n";
    }
    echo "</tr>\n\n";

    $c = 0;
    foreach($aBuglinkIds as $iBuglinkId)
    {
        $oBuglink = new bug($iBuglinkId);

        // set row color
        $bgcolor = ($c % 2 == 0) ? "color0" : "color1";

        //display row
        echo "<tr class=$bgcolor>\n";
        echo "<td align=center><a href='".BUGZILLA_ROOT."show_bug.cgi?id=".$oBuglink->iBug_id."'>".$oBuglink->iBug_id."</a></td>\n";
        echo "<td>".$oBuglink->sShort_desc."</td>\n";
        echo "<td align=center>".$oBuglink->sBug_status."</td>","\n";
        echo "<td align=center>".$oBuglink->sResolution."</td>","\n";
        echo "<td align=center><a href='viewbugs.php?bug_id=".$oBuglink->iBug_id."'>View</a></td>\n";
 
        
        if($bCanEdit == true)
        {
            echo "<td align=center>[<a href='appview.php?sSub=delete&iBuglinkId=".$oBuglink->iLinkId."&iVersionId=".$oBuglink->iVersionId."'>delete</a>]</td>\n";
            if ($oBuglink->bQueued)
            {
                echo "<td align=center>[<a href='appview.php?sSub=unqueue&iBuglinkId=".$oBuglink->iLinkId."&iVersionId=".$oBuglink->iVersionId."'>OK</a>]</td>\n";
            } else
            {
                echo "<td align=center>Yes</td>\n";
            }
               
        }
        echo "</tr>\n\n";
 

        $c++;   
    }
    if($_SESSION['current']->isLoggedIn())
    {
        echo '<input type="hidden" name="iVersionId" value="'.$iVersionId.'">',"\n";
        echo '<tr class=color3><td align=center>',"\n";
        echo '<input type="text" name="iBuglinkId" value="'.$aClean['buglinkId'].'" size="8"></td>',"\n";
        echo '<td><input type="submit" name="sSub" value="Submit a new bug link."></td>',"\n";
        echo '<td colspan=6></td></tr></form>',"\n";
    }
    echo '</table>',"\n";
    echo html_frame_end();
}    

?>
