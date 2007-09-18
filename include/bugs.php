<?php
require_once(BASE."include/util.php");
require_once(BASE."include/application.php");
/******************************************/
/* bug class and related functions */
/******************************************/


/**
 * Bug Link class for handling Bug Links and thumbnails
 */
class Bug
{
    var $iLinkId;

    // parameters necessary to create a new Bug with Bug::create()
    var $iVersionId;
    var $iBug_id;

    // values retrieved from bugzilla
    var $sShort_desc;
    var $sBug_status;
    var $sResolution;
    var $sSubmitTime;
    var $iSubmitterId;
    var $bQueued;

    /**    
     * Constructor, fetches the data and bug objects if $ilinkId is given.
     */
    function bug($iLinkId = null, $oRow = null)
    {
        if(!$iLinkId && !$oRow)
          return;

        if(!$oRow)
        {
            $sQuery = "SELECT * FROM buglinks
                       WHERE linkid = '?'";
            if($hResult = query_parameters($sQuery, $iLinkId))
            {
                $oRow = query_fetch_object($hResult);
            }
        }

        if($oRow)
        {
            $this->iLinkId = $oRow->linkId;
            $this->iBug_id = $oRow->bug_id;
            $this->iVersionId = $oRow->versionId;
            $this->bQueued = ($oRow->queued=="true") ? true : false;
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
                    $oRow = query_fetch_object($hResult);
                    if($oRow)
                    {
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
    function create()
    {
        $oVersion = new Version($this->iVersionId);

        // Security, if we are not an administrator or a maintainer,
        // the Bug must be queued.
        if($this->mustBeQueued())
        {
            $this->bQueued = true;
        } else
        {
            $this->bQueued = false;
        }

        /* lets check for a valid bug id */
        if(!is_numeric($this->iBug_id))
        {
            addmsg($this->iBug_id." is not a valid bug number.", "red");
            return false;
        }

        /* check that bug # exists in bugzilla*/
        $sQuery = "SELECT *
                   FROM bugs 
                   WHERE bug_id = ".$this->iBug_id;
        if(query_num_rows(query_bugzilladb($sQuery, "checking bugzilla")) == 0)
        {
            addmsg("There is no bug in Bugzilla with that bug number.", "red");
            return false;
        }

        /* Check for Duplicates */

        $sQuery = "SELECT *
                   FROM buglinks 
                   WHERE versionId = '?'";
        if($hResult = query_parameters($sQuery, $this->iVersionId))
        {
            while($oRow = query_fetch_object($hResult))
            {
                if($oRow->bug_id == $this->iBug_id)
                {
                   addmsg("The Bug link has already been submitted.", "red");
                   return false;
                }
            }
        }

        /* passed the checks so lets insert the puppy! */
        $hResult = query_parameters("INSERT INTO buglinks (versionId, bug_id, ".
                                    "submitTime, submitterId, queued) ".
                                    "VALUES('?', '?', ?, '?', '?')",
                                    $this->iVersionId, $this->iBug_id,
                                    "NOW()",
                                    $_SESSION['current']->iUserId,
                                    $this->bQueued ? "true":"false");
        if($hResult)
        {
            $this->iLinkId = query_appdb_insert_id();

            $this->SendNotificationMail();

            return true;
        } else
        {
            addmsg("Error while creating a new Bug link.", "red");
            return false;
        }
    }

    /**    
     * Deletes the Bug from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     *
     * Return true if successful, false if an error occurs
     */
    function delete()
    {
        $sQuery = "DELETE FROM buglinks 
                   WHERE linkId = '?'";
        if(!($hResult = query_parameters($sQuery, $this->iLinkId)))
            return false;

        return true;
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
            // we send an e-mail to interested people
            $this->mailSubmitter();
            $this->SendNotificationMail();
            // the Bug has been unqueued
            addmsg("The Bug has been unqueued.", "green");
        }
    }

    //TODO: figure out what we might want to do here but lie and
    // return true until then
    function update()
    {
        return true;
    }

    function mailSubmitter($bRejected=false)
    {
        global $aClean;
        if(!isset($aClean['sReplyText']))
            $aClean['sReplyText'] = "";

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            $sAppName = Version::fullName($this->iVersionId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted Bug Link accepted";
                $sMsg  = "The bug link you submitted between Bug ".$this->iBug_id." and ".  
                        $sAppName." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted Bug Link rejected";
                 $sMsg  = "The bug link you submitted between Bug ".$this->iBug_id." and ".  
                        $sAppName." has been deleted.";
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($bDeleted=false)
    {
        $sAppName = version::fullName($this->iVersionId);
        $oVersion = new version($this->iVersionId);
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = $oVersion->objectMakeUrl()."\n";
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
                $sMsg  = $oVersion->objectMakeUrl()."\n";
                $sMsg .= "This Bug Link has been queued.";
                $sMsg .= "\n";
                addmsg("The Bug Link you submitted will be added to the database after being reviewed.", "green");
            }
        } else // Bug Link deleted.
        {
            $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = $oVersion->objectMakeUrl()."\n";
            addmsg("Bug Link deleted.", "green");
        }

        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
        {
            mail_appdb($sEmail, $sSubject ,$sMsg);
        }
    }

    function objectGetSubmitterId()
    {
        return $this->iSubmitterId;
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        return new mailOptions();
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        /* We don't do this at the moment */
                return array(null, null, null);
    }

    function objectGetChildren()
    {
        return array();
    }

    /* Get a list of bugs submitted by a given user */
    function listSubmittedBy($iUserId, $bQueued = true)
    {
        $hResult = query_parameters("SELECT appFamily.appName, buglinks.versionId, appVersion.versionName, buglinks.submitTime, buglinks.bug_id FROM buglinks, appFamily, appVersion WHERE appFamily.appId = appVersion.appId AND buglinks.versionId = appVersion.versionId AND buglinks.queued = '?' AND buglinks.submitterId = '?' ORDER BY buglinks.versionId", $bQueued ? "true" : "false", $iUserId);

        if(!$hResult || !query_num_rows($hResult))
            return FALSE;

        $sReturn = html_table_begin("width=\"100%\" align=\"center\"");
        $sReturn .= html_tr(array(
            "Version",
            array("Bug #", 'width="50"'),
            array("Status", 'width="80"'),
            array("Resolution", 'width="110"'),
            "Description",
            "Submit time"),
            "color4");

        for($i = 1; $oRow = query_fetch_object($hResult); $i++)
        {
            $oBug = new Bug($oRow->bug_id);
            $sReturn .= html_tr(array(
                version::fullNameUrl($oRow->versionId),
                "<a href=\"".BUGZILLA_ROOT."show_bug.cgi?id=".$oRow->bug_id."\">".$oRow->bug_id."</a>",
                $oBug->sBug_status,
                $oBug->sResolution,
                $oBug->sShort_desc,
                print_date(mysqldatetime_to_unixtimestamp($oRow->submitTime))),
                ($i % 2) ? "color0" : "color1");
        }

        $sReturn .= html_table_end();

        return $sReturn;
    }

    function isOpen()
    {
        return ($this->sBug_status != 'RESOLVED' && $this->sBug_status != 'CLOSED');
    }

    function allowAnonymousSubmissions()
    {
        return false;
    }

    function mustBeQueued()
    {
        if($_SESSION['current']->hasPriv("admin") ||
           $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
           $_SESSION['current']->isSuperMaintainer($oVersion->iAppId))
        {
          return false;
        }

        return true;
    }

    function objectGetId()
    {
        return $this->iLinkId;
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0)
    {
        $sLimit = "";
        
        /* Selecting 0 rows makes no sense, so we assume the user
         wants to select all of them
         after an offset given by iStart */
        if(!$iRows)
          $iRows = bug::objectGetEntriesCount($bQueued, $bRejected);

        $sQueued = objectManager::getQueueString($bQueued, $bRejected);
        $sQuery = "select * from buglinks where queued = '?' LIMIT ?, ?";
        $hResult = query_parameters($sQuery, $sQueued, $iStart, $iRows);
        return $hResult;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        $sQueued = objectManager::getQueueString($bQueued, $bRejected);
        $sQuery = "select count(*) as cnt from buglinks where queued = '?'";
        $hResult = query_parameters($sQuery, $sQueued);
        $oRow = mysql_fetch_object($hResult);
        return $oRow->cnt;
    }

    function objectGetHeader()
    {
        $oTableRow = new TableRow();
        
        $oTableRow->AddTextCell("Bug #");

        $oTableCell = new TableCell("Status");
        $oTableCell->SetAlign("center");
        $oTableRow->AddCell($oTableCell);

        $oTableRow->AddTextCell("Bug Description");

        $oTableCell = new TableCell("Application Name");
        $oTableCell->SetAlign("center");
        $oTableRow->AddCell($oTableCell);

        $oTableCell = new TableCell("Application Description");
        $oTableCell->SetAlign("center");
        $oTableRow->AddCell($oTableCell);

        $oTableCell = new TableCell("Version");
        $oTableCell->SetAlign("center");
        $oTableRow->AddCell($oTableCell);

        return $oTableRow;
    }

    // returns a table row
    function objectGetTableRow()
    {
        $oTableRow = new TableRow();
        
        $oVersion = new version($this->iVersionId);
        $oApp = new application($oVersion->iAppId);

        $oTableCell = new TableCell($this->iBug_id);
        $oTableCell->SetAlign("center");
        $oTableCell->SetCellLink(BUGZILLA_ROOT.'show_bug.cgi?id='.$this->iBug_id);
        $oTableRow->AddCell($oTableCell);

        $oTableCell = new TableCell($this->sBug_status);
        $oTableCell->SetAlign("center");
        $oTableRow->AddCell($oTableCell);

        $oTableRow->AddTextCell($this->sShort_desc);

        $oTableRow->AddTextCell($oApp->objectMakeLink());

        $oTableRow->AddTextCell(util_trim_description($oApp->sDescription));

        $oTableRow->AddTextCell($oVersion->objectMakeLink());

        $oOMTableRow = new OMTableRow($oTableRow);

        // enable the deletion link, the objectManager will check
        // for appropriate permissions before adding the link
        $oOMTableRow->SetRowHasDeleteLink(true);

        return $oOMTableRow;
    }

    function objectMakeUrl()
    {
        $oManager = new objectManager("bug", "View Bug");
        return $oManager->makeUrl("view", $this->objectGetId());
    }

    function objectMakeLink()
    {
        $sLink = "<a href=\"".$this->objectMakeUrl()."\">".
                 $this->sShort_desc."</a>";
        return $sLink;
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
        {
            return true;
        } else if($this->iVersionId)
        {
            if(maintainer::isUserMaintainer($_SESSION['current'],
                                          $this->iVersionId))
            {
                return true;
            }
        }

        return false;
    }

    function objectGetItemsPerPage($bQueued = false)
    {
        $aItemsPerPage = array(25, 50, 100, 200);
        $iDefaultPerPage = 25;
        return array($aItemsPerPage, $iDefaultPerPage);
    }

    function display()
    {
        $oTable = new Table();
        $oTable->SetAlign("center");
        $oTable->SetClass("color0");
        $oTable->SetCellPadding(2);

        $oHeaderRow = $this->objectGetHeader();
        $oHeaderRow->SetClass("color4");
        $oTable->AddRow($oHeaderRow);

        $oDataRow = $this->objectGetTableRow();
        $oDataRow->oTableRow->SetClass("color0");
        $oTable->AddRow($oDataRow);
        
        echo $oTable->GetString();
    }

    // NOTE: we don't have any editing support for this entry at this time
    //       so output the entry and a field to identify the bug id
    function outputEditor()
    {
        $this->display();
        echo '<input type="hidden" name="iBuglinkId" value="'.$this->iBug_id.'" />';
        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" />';
    }

    function getOutputEditorValues($aClean)
    {
       $this->iBug_id = $aClean['iBuglinkId'];
       $this->iVersionId = $aClean['iVersionId'];
    }
}


/*
 * Bug Link functions that are not part of the class
 */

function view_version_bugs($iVersionId = null, $aBuglinkIds)
{
    global $aClean;

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
        echo "<form method=post action='".APPDB_ROOT."objectManager.php'>\n";
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
        $oBuglink = new Bug($iBuglinkId);

        if (
                (!isset($aClean['sAllBugs']) && $oBuglink->isOpen() )
                ||
                isset($aClean['sAllBugs'])
            )
        {
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
                echo "<td align=center>[<a href='objectManager.php?sClass=bug&iId=".$oBuglink->iLinkId."&sAction=delete&sSubmit=Delete&sReturnTo=".$oVersion->objectMakeUrl()."'>delete</a>]</td>\n";
                if ($oBuglink->bQueued)
                {
                    echo "<td align=center>[<a href='objectManager.php?sClass=bug&iId=".$oBuglink->iLinkId."&sAction=edit&sSubmit=Submit&bIsQueue=true&sReturnTo=".$oVersion->objectMakeUrl()."'>OK</a>]</td>\n";
                } else
                {
                    echo "<td align=center>Yes</td>\n";
                }
                
            }
            echo "</tr>\n\n";
    

            $c++;
        }
    }

    if($_SESSION['current']->isLoggedIn())
    {
        echo '<input type="hidden" name="iVersionId" value="'.$iVersionId.'">',"\n";
        echo '<tr class=color3><td align=center>',"\n";
        $sBuglinkId = isset($aClean['buglinkId']) ? $aClean['buglinkId'] : '';
        echo '<input type="text" name="iBuglinkId" value="'.$sBuglinkId.'" size="8"></td>',"\n";
        echo '<input type="hidden" name="sSubmit" value="Submit" />',"\n";
        echo '<input type="hidden" name="sClass" value="bug" />',"\n";
        echo '<input type="hidden" name="sReturnTo" value="'.$oVersion->objectMakeUrl().'" />',"\n";
        echo '<td><input type="submit" name="sSub" value="Submit a new bug link."></td>',"\n";
        echo '<td colspan=6></td></tr></form>',"\n";
    }
    echo '</table>',"\n";

    // show only open link
    if ( isset( $aClean['sAllBugs'] ) )
    {
        $sURL = str_replace( '&sAllBugs', '', $_SERVER['REQUEST_URI'] );
        $sLink = '<a href="' . $sURL . '">Show Open Bugs</a>';
    }
    // show all link
    else
    {
        $sURL = $_SERVER['REQUEST_URI'] . '&sAllBugs';
        $sLink = '<a href="' . $sURL . '">Show All Bugs</a>';
    }
    
    echo '<div style="text-align:right;">' . $sLink .'</div>';
    echo html_frame_end();
}

?>
