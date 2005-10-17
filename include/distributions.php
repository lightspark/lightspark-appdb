<?php
/***************************************/
/* this class represents Distributions */
/***************************************/
require_once(BASE."include/mail.php");

// Testing class for handling Distributions.

class distribution{
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
                           WHERE distributionId = ".$iDistributionId;
                if($hResult = query_appdb($sQuery))
                {
                    $oRow = mysql_fetch_object($hResult);
                    $this->iDistributionId = $iDistributionId;
                    $this->sName = $oRow->name;
                    $this->sDescription = $oRow->description;
                    $this->sUrl = $oRow->url;
                    $this->sSubmitTime = $oRow->submitTime;
                    $this->iSubmitterId = $oRow->submitterId;
                    $this->sQueued = $oRow->queued;
                }
            }

            /*
             * We fetch Test Result Ids. 
             */
            $sQuery = "SELECT testingId
                       FROM testResults
                       WHERE distributionId = ".$iDistributionId;
            if($hResult = query_appdb($sQuery))
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
                   WHERE name LIKE '".$this->sName."'";
        $hDuplicate = query_appdb($sQuery, "checking distributions");
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

        $aInsert = compile_insert_string(array( 'name'              => $this->sName,
                                                'url'               => $this->sUrl,
                                                'submitterId'       => $_SESSION['current']->iUserId,
                                                'queued'            => $this->sQueued ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO distributions $sFields VALUES $sValues", "Error while creating Distribution."))
        {
            $this->iDistributionId = mysql_insert_id();
            $this->distribution($this->iDistributionId);
            $this->SendNotificationMail();
            return true;
        }
        else
            return false;
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
        $sUpdate = compile_update_string(array( 'name'              => $this->sName,
                                                'url'               => $this->sUrl ));
        if(query_appdb("UPDATE distributions SET ".$sUpdate." WHERE distributionId = ".$this->iDistributionId, "Error while updating Distribution."))
        {
            $this->SendNotificationMail("edit");
            return true;
        }
        else
            return false;
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
                   WHERE distributionId = ".$this->iDistributionId." 
                   LIMIT 1";
        if(!($hResult = query_appdb($sQuery)))
        {
            addmsg("Error removing the Distribution!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        $this->mailSubmitter("delete");
    }


    // Move Distribution out of the queue.
    function unQueue()
    {
        // is the current user allowed to move this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            return;
        }

        // If we are not in the queue, we can't move the Distribution out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        $sUpdate = compile_update_string(array('queued'    => "false"));
        if(query_appdb("UPDATE distribution SET ".$sUpdate." WHERE distributionId = ".$this->iDistributionId, "Error while unqueuing Distribution."))
        {
            $this->sQueued = 'false';
            // we send an e-mail to intersted people
            $this->mailSubmitter("unQueue");
            $this->SendNotificationMail();
        }
    }

    function Reject($bSilent=false)
    {
        // is the current user allowed to reject this Distribution? 
        if(!$_SESSION['current']->hasPriv("admin"))
        {
            return;
        }

        // If we are not in the queue, we can't move the Distribution out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        $sUpdate = compile_update_string(array('queued'    => "rejected"));
        if(query_appdb("UPDATE distribution SET ".$sUpdate." WHERE distributionId = ".$this->iDistributionId, "Error while rejecting Distribution."))
        {
            $this->sQueued = 'rejected';
            // we send an e-mail to intersted people
            if(!$bSilent)
            {
                $this->mailSubmitter("reject");
                $this->SendNotificationMail("reject");
            }
            // the Distribution data has been rejected
        }
    }

    function ReQueue()
    {
        // is the current user allowed to requeue this data 
        if(!$_SESSION['current']->hasPriv("admin") &&
           !($_SESSION['current']->iUserId == $this->iSubmitterId))
        {
            return;
        }

        $sUpdate = compile_update_string(array('queued'    => "true"));
        if(query_appdb("UPDATE testResults SET ".$sUpdate." WHERE testingId = ".$this->iTestingId))
        if(query_appdb("UPDATE distribution SET ".$sUpdate." WHERE distributionId = ".$this->iDistributionId, "Error while requeueing Distribution."))
        {
            $this->sQueued = 'true';
            // we send an e-mail to intersted people
            $this->SendNotificationMail();

            // the testing data has been resubmitted
            addmsg("The Distribution has been resubmitted", "green");
        }
    }

    function mailSubmitter($sAction="add")
    {
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
                    $sMsg .= APPDB_ROOT."testingData.php?sub=view&versionId=".$this->iVersionId."\n";
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }

            break;
            case "delete":
                {
                    $sSubject =  "Submitted Distribution deleted";
                    $sMsg  = "The Distribution you submitted (".$this->sName.") has been deleted.";
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }
            break;
            }
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
        
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
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
                        $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                    }
                    addmsg("The Distribution was successfully added into the database.", "green");
                } else // testing data queued.
                {
                    $sSubject = "Distribution ".$this->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This testing data has been queued.";
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

                // if replyText is set we should report the reason the data was deleted 
                if($_REQUEST['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Distribution deleted.", "green");
            break;
            case "reject":
                $sSubject = "Distribution '".$this->sName." has been rejected by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."distributionView.php?iDistributionId=".$this->iDistributionId."\n";

                 // if replyText is set we should report the reason the data was rejected 
                if($_REQUEST['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("Distribution rejected.", "green");
            break;
        }
        $sEmail = get_notify_email_address_list(null, null);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }

    function OutputEditor()
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

    /* retrieves values from $_REQUEST that were output by OutputEditor() */
    function GetOutputEditorValues()
    {
        if(get_magic_quotes_gpc())
        {
            $this->iDistributionId = stripslashes($_REQUEST['iDistributionId']);
            $this->sName = stripslashes($_REQUEST['sName']);
            $this->sUrl = stripslashes($_REQUEST['sUrl']);
        } else
        {
            $this->iDistributionId = $_REQUEST['iDistributionId'];
            $this->sName = $_REQUEST['sName'];
            $this->sUrl = $_REQUEST['sUrl'];
        }
    }

}

/* Make a dropdown list of distributions */
function make_distribution_list($varname, $cvalue)
{
    $query = "SELECT name, distributionId FROM distributions ORDER BY name";

    $result = query_appdb($query);
    if(!$result) return;

    echo "<select name='$varname'>\n";
    echo "<option value=\"\">Choose ...</option>\n";
    while(list($name, $value) = mysql_fetch_row($result))
    {
        if($value == $cvalue)
            echo "<option value=$value selected>$name\n";
        else
            echo "<option value=$value>$name\n";
    }
    echo "</select>\n";
}
/* Get the total number of Distributions in the database */
function getNumberOfDistributions()
{
    $hResult = query_appdb("SELECT count(*) as num_dists FROM distributions");
    if($hResult)
    {
      $row = mysql_fetch_object($hResult);
      return $row->num_dists;
    }
    return 0;
}

/* Get the number of Queued Distributions in the database */
function getNumberOfQueuedDistributions()
{
    $hResult = query_appdb("SELECT count(*) as num_dists FROM distributions WHERE queued='true';");
    if($hResult)
    {
      $row = mysql_fetch_object($hResult);
      return $row->num_dists;
    }
    return 0;
}

?>
