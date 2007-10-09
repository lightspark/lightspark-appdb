<?php
require_once(BASE."include/util.php");
/* max votes per user */
define('MAX_VOTES',3);

class vote
{
    public $iUserId;
    public $iVoteId;
    public $iSlotIndex;
    public $iVersionId;

    public function vote($iVoteId = null, $oRow = null)
    {
        if(!$iVoteId && !$oRow) /* Nothing to do */
            return;

        if(!$oRow)
        {
            $hResult = query_parameters("SELECT * FROM appVotes WHERE id = '?'", $iVoteId);

            $oRow = mysql_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iUserId = $oRow->userId;
            $this->iVoteId = $oRow->id;
            $this->iSlotIndex = $oRow->slot;
            $this->iVersionId = $oRow->versionId;
        }
    }

    public function update()
    {
        /* Check for valid vote slot index */
        if($this->iSlotIndex < 1 || $this->iSlotIndex > MAX_VOTES)
            return;

        /* Avoid pointless votes */
        if(!$this->iVersionId)
            return;

        if(!$this->iVoteId)
        {
            $hResult = query_parameters("INSERT INTO appVotes (versionId,userId,slot) VALUES('?','?','?')",
                                        $this->iVersionId, $_SESSION['current']->iUserId, $this->iSlotIndex);
        } else
        {
            $hResult = query_parameters("UPDATE appVotes SET versionId = '?' WHERE id = '?'", $this->iVersionId, $this->iVoteId);
        }

        if(!$hResult)
            return FALSE;

        return TRUE;
    }

    public function delete()
    {
        /* A vote needs to have a versionId, so if it doesn't that means it is not in the
           database or it was not selected in the vote editor */
        if(!$this->iVersionId)
            return TRUE;

        $hResult = query_parameters("DELETE FROM appVotes WHERE id = '?'", $this->iVoteId);

        if(!$hResult)
            return FALSE;

        return TRUE;
    }
}

class voteManager
{
    private $iUserId;
    private $aVotes;

    public function voteManager($iUserId = null, $oRow = null)
    {
        $this->iUserId = $iUserId;
    }

    public function objectGetCustomVars($sAction)
    {
        switch($sAction)
        {
            case "edit":
                return array("iVersionId");
                break;

            default:
                return null;
        }
    }

    public function outputEditor($aClean = null)
    {
        echo "The following shows your current votes.  Check the boxes next to the apps you wish to replace with a vote for ".version::fullNameLink($aClean['iVersionId'])." or delete.";

        $oTable = new table();
        $this->aVotes = $this->getVotes();

        for($i = 0; $i < MAX_VOTES; $i++)
        {
            $sVersionText = $this->aVotes[$i]->iVersionId ? version::fullNameLink($this->aVotes[$i]->iVersionId) : "No app selected";
            $oTableRow = new tableRow();
            $oTableRow->addTextCell('<input type="checkbox" name="iSlot'.$i.'" value="'.$aClean['iVersionId'].'" />');
            $oTableRow->addTextCell($sVersionText);
            $oTable->addRow($oTableRow);
        }

        echo $oTable->getString();
    }

    public function canEdit()
    {
        if($_SESSION['current']->iUserId == $this->iUserId)
            return TRUE;

        return FALSE;
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        return array(null, null, null); /* No mail */
    }

    public function mustBeQueued()
    {
        return FALSE;
    }

    public function objectGetEntries($bQueued, $bRejected)
    {
        return query_parameters("SELECT * FROM appVotes");
    }

    public function objectGetId()
    {
        return $this->iUserId;
    }

    public function create()
    {
        return TRUE;
    }

    public function delete()
    {
        $bSuccess = TRUE;

        if(!is_array($this->aVotes))
            $this->aVotes = $this->getVotes();

        foreach($this->aVotes as $oVote)
        {
            if(!$oVote->delete())
                $bSuccess = FALSE;
        }

        return $bSuccess;
    }

    public function update()
    {
        foreach($this->aVotes as $oVote)
            $oVote->update();
    }

    public function getOutputEditorValues($aClean)
    {
        $this->aVotes = $this->getVotes();

        for($i = 0; $i < MAX_VOTES; $i++)
            $this->aVotes[$i]->iVersionId = $aClean["iSlot$i"];
    }

    public function objectGetEntriesCount()
    {
        $hResult = query_parameters("SELECT COUNT(id) as count FROM appVotes");

        if(!$hResult)
            return FALSE;

        if(!($oRow = mysql_fetch_object($hResult)))
            return FALSE;

        return $oRow->count;
    }

    public function objectGetSubmitterId()
    {
        return $this->iUserId;
    }

    public function getVotes()
    {
        $aVotes = array();
        $hResult = query_parameters("SELECT * FROM appVotes WHERE userId = '?' ORDER BY slot", $this->iUserId);

        if(!$hResult)
            return $aVotes;

        for($i = 0; $i < MAX_VOTES; $i++)
            $aVotes[$i] = null;

        while($oRow = mysql_fetch_object($hResult))
            $aVotes[$oRow->slot-1] = new vote(null, $oRow);

        for($i = 0; $i < MAX_VOTES; $i++)
        {
            if(!$aVotes[$i])
            {
                $aVotes[$i] = new vote();
                $aVotes[$i]->iSlotIndex = $i+1;
            }
        }

        return $aVotes;
    }
}

/**
 * count the number of votes for appId by userId
 */
function vote_count($iVersionId, $iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        else
            return 0;
    }
    $hResult = query_parameters("SELECT * FROM appVotes WHERE versionId = '?' AND userId = '?'",
                            $iVersionId, $iUserId);
    return query_num_rows($hResult);
}


/**
 * total votes by userId
 */
function vote_count_user_total($iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        else
            return 0;
    }
    $hResult = query_parameters("SELECT * FROM appVotes WHERE userId = '?'", $iUserId);
    return query_num_rows($hResult);
}


/*
 * total votes for versionId
 */
function vote_count_version_total($iVersionId)
{
    $hResult = query_parameters("SELECT * FROM appVotes WHERE versionId = '?'",
                                    $iVersionId);
    return query_num_rows($hResult);
}


/**
 * add a vote for appId
 */
function vote_add($iVersionId, $iSlot, $iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        else
            return;
    }

    if($iSlot > MAX_VOTES)
        return;
    
    vote_remove($iSlot, $iUserId);

    query_parameters("INSERT INTO appVotes (id, time, versionId, userId, slot)
                      VALUES (?, ?, '?', '?', '?')", "null", "NOW()",
                          $iVersionId, $iUserId, $iSlot);
}


/**
 * remove vote for a slot
 */
function vote_remove($iSlot, $iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        else
            return;
    }

    $sQuery = "DELETE FROM appVotes WHERE userId = '?' AND slot = '?'";
    query_parameters($sQuery, $iUserId, $iSlot);
}


function vote_get_user_votes($iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        if(!$iUserId)
            return array();
    }
    $hResult = query_parameters("SELECT * FROM appVotes WHERE userId = '?'", $iUserId);
    if(!$hResult)
        return array();

    $obs = array();
    while($oRow = query_fetch_object($hResult))
        $obs[$oRow->slot] = $oRow;
    return $obs;
}


function vote_menu()
{
    global $aClean;

    $m = new htmlmenu("Votes","updatevote.php");

    $oVersion = new version($aClean['iVersionId']);

    if($oVersion->iObsoleteBy)
    {
        $m->add("This version is marked as obsolete, so you cannot vote for it.");
    } else
    {
        $votes = vote_get_user_votes();

        for($i = 1;$i <= MAX_VOTES; $i++)
        {
            if(isset($votes[$i]))
                $str = Version::fullNameLink($votes[$i]->versionId);
            else
                $str = "No App Selected";

            $m->add("<input type=radio name=iSlot value='$i'> $str");
        }

        $m->addmisc("&nbsp;");

        $m->add("<input type=submit name=sClear value=' Clear Vote   ' class=votebutton>");
        $m->add("<input type=submit name=sVote value='Vote for App' class=votebutton>");

        $m->addmisc("<input type=hidden name=iVersionId value={$aClean['iVersionId']}>");

        $m->add("View Results", BASE."votestats.php");
        $m->add("Voting Help", BASE."help/?sTopic=voting");
    }

    $m->done(1);
}


function vote_update($vars)
{
    if(!$_SESSION['current']->isLoggedIn())
        util_show_error_page_and_exit("You must be logged in to vote");

    $oVersion = new version($vars['iVersionId']);

    if( !is_numeric($vars['iVersionId']) OR !is_numeric($vars['iSlot']))
    {
        if(is_numeric($vars['iVersionId']))
        {
            addmsg("You need to select a voting slot", "red");
            util_redirect_and_exit($oVersion->objectMakeUrl());
        } else
        {
            util_redirect_and_exit(apidb_fullurl("index.php"));
        }

        return;
    }

    if($vars["sVote"])
    {
        addmsg("Registered vote for App #".$vars['iVersionId'], "green");
        vote_add($vars['iVersionId'], $vars['iSlot']);
    } else if($vars['sClear'])
    {
        /* see if we have a vote in this slot, if we don't there is */
        /* little reason to remove it or even mention that we did anything */
        if(is_vote_in_slot($vars['iSlot']))
        {
            vote_remove($vars['iSlot']);
            addmsg("Removed vote for App #".$vars['iVersionId'], "green");
        }
    }

    util_redirect_and_exit($oVersion->objectMakeUrl());
}

// tell us if there is a vote in a given slot so we don't
// display incorrect information to the user or go
// through the trouble of trying to remove a vote that doesn't exist
function is_vote_in_slot($iSlot, $iUserId = null)
{
    if(!$iUserId)
    {
        if($_SESSION['current']->isLoggedIn())
            $iUserId = $_SESSION['current']->iUserId;
        else
            return;
    }

    $sQuery = "SELECT COUNT(*) as count from appVotes WHERE userId = '?' AND slot = '?'";
    if($hResult = query_parameters($sQuery, $iUserId, $iSlot))
    {
        $oRow = query_fetch_object($hResult);        
        if($oRow->count != 0)
            return true;
        else
            return false;
    }
    
    return false;
}

?>
