<?php
/***************************************/
/* this class represents Distributions */
/***************************************/
require_once(BASE."include/mail.php");
require_once(BASE."include/util.php");

// Test class for handling Distributions.

class distribution {
    var $iDistributionId;
    var $sName;
    var $sDescription;
    var $sUrl;
    var $sSubmitTime;
    var $iSubmitterId;
    var $sQueued;
    var $aTestingIds;

     // constructor, fetches the data.
    function distribution($iDistributionId = null)
    {
        // we are working on an existing distribution.
        if(is_numeric($iDistributionId))
        {
            // We fetch the data related to this distribution.
            if(!$this->$iDistributionId)
            {
                $sQuery = "SELECT *
                           FROM distributions
                           WHERE distributionId = '?'";
                if($hResult = query_parameters($sQuery, $iDistributionId))
                {
                    $oRow = mysql_fetch_object($hResult);
                    if($oRow)
                    {
                        $this->iDistributionId = $iDistributionId;
                        $this->sName = $oRow->name;
                        $this->sDescription = $oRow->description;
                        $this->sUrl = $oRow->url;
                        $this->sSubmitTime = $oRow->submitTime;
                        $this->iSubmitterId = $oRow->submitterId;
                        $this->sQueued = $oRow->queued;
                    }
                }
            }

            /*
             * We fetch Test Result Ids. 
             */

            if($_SESSION['current']->hasPriv("admin"))
            {
                $sQuery = "SELECT testingId
                             FROM testResults
                             WHERE distributionId = '?' 
                             ORDER BY testedRating;" ;
            } else /* only let users view test results that aren't queued and for apps that */
                   /* aren't queued or versions that aren't queued */
            {
                $sQuery = "SELECT testingId
                             FROM testResults, appFamily, appVersion
                             WHERE testResults.queued = 'false' AND
                                    testResults.versionId = appVersion.versionId AND
                                    appFamily.appId = appVersion.appId AND
                                    appFamily.queued = 'false' AND
                                    appVersion.queued = 'false' AND
                                    distributionId = '?'
                             ORDER BY testedRating;";
            }

            if($hResult = query_parameters($sQuery, $iDistributionId))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aTestingIds[] = $oRow->testingId;
                }
            }
        }
    }

    // Creates a new distribution.
    function create()
    {
        //Let's not create a duplicate 
        $sQuery = "SELECT *
                   FROM distributions
                   WHERE name LIKE '?'";
        $hDuplicate = query_parameters($sQuery, $this->sName);
        if(!mysql_num_rows($hDuplicate) == 0)
        {
            addmsg("There was an existing Distribution called ".$this->sName.".", "red");
            $oRow = mysql_fetch_object($hDuplicate);
            $this->iDistributionId = $oRow->distributionId;
            return false;
        }

        // Security, if we are not an administrator the Distributions must be queued.
        if(!$_SESSION['current']->hasPriv("admin"))
            $this->sQueued = 'true';
        else
            $this->sQueued = 'false';

        $hResult = query_parameters("INSERT INTO distributions (name, url, submitterId, queued) ".
                                    "VALUES ('?', '?', '?', '?')",
                                    $this->sName, $this->sUrl, $_SESSION['current']->iUserId,
                                    $this->sQueued);
        if($hResult)
        {
            $this->iDistributionId = mysql_insert_id();
            $this->distribution($this->iDistributionId);
            $this->SendNotificationMail();
            return true;
        }
        else
        {
            addmsg("Error while creating Distribution.", "red");
            return false;
        }
    }

    // Update Distribution.
    function update()
    {
        // is the current user allowed to update this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin") &&
           !($_SESSION['current']->iUserId == $this->iSubmitterId))
        {
            return;
        }
        if(query_parameters("UPDATE distributions SET name = '?', url = '?' WHERE distributionId = '?'",
                            $this->sName, $this->sUrl, $this->iDistributionId))
        {
            $this->SendNotificationMail("edit");
            return true;
        } else
        {
            addmsg("Error while updating Distribution", "red");
            return false;
        }
    }
    
    // Delete Distributution.
    function delete($bSilent=false)
    {
        // is the current user allowed to delete this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin") &&
           !($_SESSION['current']->iUserId == $this->iSubmitterId))
        {
            return;
        }
        // now delete the Distribution 
        $sQuery = "DELETE FROM distributions
                   WHERE distributionId = '?' 
                   LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iDistributionId)))
        {
            addmsg("Error removing the Distribution!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        $this->mailSubmitter("delete");

        return true;
    }


    // Move Distribution out of the queue.
    function unQueue()
    {
        // is the current user allowed to move this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            return false;
        }

        // If we are not in the queue, we can't move the Distribution out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE distribution SET queued = '?' WHERE distributionId = '?'",
                            "false", $this->iDistributionId))
        {
            $this->sQueued = 'false';
            // we send an e-mail to interested people
            $this->mailSubmitter("unQueue");
            $this->SendNotificationMail();
            return true;
        } else
        {
            addmsg("Error while unqueueing Distribution", "red");
            return false;
        }
    }

    function Reject($bSilent=false)
    {
        // is the current user allowed to reject this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            return false;
        }

        // If we are not in the queue, we can't move the Distribution out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE distribution SET queued = '?' WHERE distributionId = '?'",
                            "rejected", $this->iDistributionId))
        {
            $this->sQueued = 'rejected';
            // we send an e-mail to interested people
            if(!$bSilent)
            {
                $this->mailSubmitter("reject");
                $this->SendNotificationMail("reject");
            }
            // the Distribution data has been rejected
            return true;
        } else
        {
            addmsg("Error while rejecting Distribution", "red");
            return false;
        }
    }

    function ReQueue()
    {
        // is the current user allowed to requeue this data 
        if(!$_SESSION['current']->hasPriv("admin") &&
           !($_SESSION['current']->iUserId == $this->iSubmitterId))
        {
            return false;
        }

        if(query_parameters("UPDATE testResults SET queued = '?' WHERE testingId = '?'",
                            "true", $this->iTestingId))
        {
            if(query_parameters("UPDATE distribution SET queued = '?' WHERE distributionId = '?'",
                                "true", $this->iDistributionId))
            {
                $this->sQueued = 'true';
                // we send an e-mail to interested people
                $this->SendNotificationMail();

                // the test data has been resubmitted
                addmsg("The Distribution has been resubmitted", "green");
                return true;
            }
        }

        /* something has failed if we fell through to this point without */
        /* returning */
        addmsg("Error requeueing Distribution", "red");
        return false;
    }

    function mailSubmitter($sAction="add")
    {
        global $aClean;

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            switch($sAction)
            {
            case "add":
               {
                   $sSubject =  "Submitted Distribution accepted";
                   $sMsg  = "The Distribution you submitted (".$this->sName.") has been accepted.";
               }
            break;
            case "reject":
                {
                    $sSubject =  "Distribution rejected";
                    $sMsg  = "The Distribution you submitted (".$this->sName.") has been rejected.";
                    $sMsg .= APPDB_ROOT."testingData.php?sSub=view&iVersionId=".$this->iVersionId."\n";
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

            break;
            case "delete":
                {
                    $sSubject =  "Submitted Distribution deleted";
                    $sMsg  = "The Distribution you submitted (".$this->sName.") has been deleted.";
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }
            break;
            }
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
        
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        global $aClean;

        switch($sAction)
        {
            case "add":
                if($this->sQueued == "false")
                {
                    $sSubject = "Distribution ".$this->sName." added by ".$_SESSION['current']->sRealname;
                    $sMsg  = APPDB_ROOT."distributionView.php?iDistributionId=".$this->iDistributionId."\n";
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This Distribution has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                    }
                    addmsg("The Distribution was successfully added into the database.", "green");
                } else // test data queued.
                {
                    $sSubject = "Distribution ".$this->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This test data has been queued.";
                    $sMsg .= "\n";
                    addmsg("The Distribution you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject =  "Distribution ".$this->sName." has been modified by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."distributionView.php?iDistributionId=".$this->iDistributionId."\n";
                addmsg("Distribution modified.", "green");
            break;
            case "delete":
                $sSubject = "Distribution ".$this->sName." has been deleted by ".$_SESSION['current']->sRealname;

                // if sReplyText is set we should report the reason the data was deleted 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Distribution deleted.", "green");
            break;
            case "reject":
                $sSubject = "Distribution '".$this->sName." has been rejected by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."distributionView.php?iDistributionId=".$this->iDistributionId."\n";

                 // if sReplyText is set we should report the reason the data was rejected 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Distribution rejected.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, null);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }

    function outputEditor()
    {
        echo html_frame_start("Distribution Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // Name
        echo '<tr valign=top><td class="color1" width="20%"><b>Distribution Name</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sName" value="'.$this->sName.'" size="50"></td></tr>',"\n";
        // Url
        echo '<tr valign=top><td class="color1"><b>Distribution Url</b></td>',"\n";
        echo '<td class="color0"><input type=text name="sUrl" value="'.$this->sUrl.'" size="50"></td></tr>',"\n";

        echo  '<input type="hidden" name="iDistributionId" value="'.$this->iDistributionId.'">',"\n";

        echo "</table>\n";
        echo html_frame_end();
    }

    /* retrieves values from $_REQUEST that were output by outputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from outputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iDistributionId = $aValues['iDistributionId'];
        $this->sName = $aValues['sName'];
        $this->sUrl = $aValues['sUrl'];
    }

    /* Get the total number of Distributions in the database */
    function getNumberOfDistributions($bQueued)
    {
        if($bQueued)
            $hResult = query_parameters("SELECT count(*) as num_dists FROM
                                        distributions WHERE queued='true';");
        else
            $hResult = query_parameters("SELECT count(*) as num_dists FROM
                                        distributions");
        if($hResult)
        {
            $oRow = mysql_fetch_object($hResult);
            return $oRow->num_dists;
        }
        return 0;
    }

    /* Make a dropdown list of distributions */
    function make_distribution_list($varname, $cvalue)
    {
        $sQuery = "SELECT name, distributionId FROM distributions ORDER BY name";
        $hResult = query_parameters($sQuery);
        if(!$hResult) return;

        echo "<select name='$varname'>\n";
        echo "<option value=\"\">Choose ...</option>\n";
        while(list($name, $value) = mysql_fetch_row($hResult))
        {
            if($value == $cvalue)
                echo "<option value=$value selected>$name\n";
            else
                echo "<option value=$value>$name\n";
        }
        echo "</select>\n";
    }

    function objectOutputHeader($sClass = "")
    {
        $aCells = array(
            "Distribution name",
            "Distribution url",
            array("Linked Tests", "align=\"right\""));

        if(distribution::canEdit())
            $aCells[3] = array("Action", "align=\"center\"");

        echo html_tr($aCells, $sClass);
    }

    function objectGetEntries($bQueued)
    {
        if($bQueued)
        {
            if(distribution::canEdit())
            {
                /* Only users with edit privileges are allowed to view queued
                   items, so return NULL in that case */
                $sQuery = "SELECT distributionId FROM distributions
                               WHERE queued = '?' ORDER BY name";
                return query_parameters($sQuery, $bQueued ? "true" : "false");
            } else
                return NULL;
        } else
        {
            $sQuery = "SELECT distributionId FROM distributions
                           WHERE queued = '?' ORDER BY name";
            return query_parameters($sQuery, "false");
        }
    }

    function ObjectGetInstanceFromRow($oRow)
    {
        return new distribution($oRow->distributionId);
    }

    function display($sClass = "")
    {
        $aCells = array(
             "<a href=\"".BASE."distributionView.php?iDistributionId=".
             $this->iDistributionId."\">$this->sName.</a>",
             "<a href=\"$this->sUrl\">$this->sUrl</a>",
             array(sizeof($this->aTestingIds), "align=\"right\""));

        if($this->canEdit())
        {
            if(!sizeof($this->aTestingIds))
            {
                $sDelete = " &nbsp; [<a href='".$_SERVER['PHP_SELF']."?sSub=delete&".
                "iDistributionId=$this->iDistributionId'>delete</a>]";
            }
            $aCells[3] = array(
                "[<a href='".BASE."admin/editDistribution.php?iDistributionId=".
                $this->iDistributionId."'>edit</a>]$sDelete",
                "align=\"center\"");
        }

        echo html_tr($aCells, $sClass);
    }

    // Whether the user has permission to edit distributions
    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;

        return FALSE;
    }
}

?>
