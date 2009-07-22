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
            $oTableRow->addTextCell('<input type="checkbox" name="iSlot'.$i.'" value="'.$aClean['iVersionId'].'">');
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

    function purge()
    {
        return $this->delete();
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
 * used by admins to check whether votes for a version are legit
 */
class voteInspector
{
    private $iVersionId;

    function voteInspector($iVersionId = null)
    {
        if(is_numeric($iVersionId))
            $this->iVersionId = $iVersionId;
    }

    public function objectGetId()
    {
        return $this->iVersionId;
    }

    public function objectGetState()
    {
        return 'accepted';
    }

    public function canEdit()
    {
        return $_SESSION['current']->hasPriv('admin');
    }

    public function outputEditor()
    {
        $oVersion = new version($this->iVersionId);

        echo 'Inspecting votes for ' . version::fullNameLink($this->iVersionId);
        echo '<br /><br />';

        $hResult = query_parameters("SELECT userId, COUNT(userId) as count FROM appVotes WHERE
                                     versionId = '?'
                                     GROUP BY userId", $this->iVersionId);

        if(!$hResult)
        {
            echo 'Failed to get list of votes';
            return;
        }

        if(mysql_num_rows($hResult) == 0)
        {
            echo 'There are no votes for this version';
            return;
        }

        $oTable = new Table();
        $oTable->setCellPadding(3);

        $oTableRow = new TableRow();
        $oTableRow->setClass('color4');
        $oTableRow->AddTextCell('User');
        $oTableRow->AddTextCell('ID');
        $oTableRow->AddTextCell('Created');
        $oTableRow->AddTextCell('Votes');
        $oTableRow->AddTextCell('Privileges');
        $oTable->AddRow($oTableRow);

        for($i = 0; $oRow = mysql_fetch_object($hResult); $i++)
        {
            $oVoter = new user($oRow->userId);
            $oTableRow = new TableRow();
            $oTableRow->setClass(($i % 2) ? 'color0' : 'color1');
            $oTableRow->AddTextCell($oVoter->objectMakeLink());
            $oTableRow->AddTextCell($oVoter->iUserId);
            $oTableRow->AddTextCell($oVoter->sDateCreated);
            $oTableRow->AddTextCell($oRow->count);

            $sPrivs = '';
            if($oVoter->hasPriv('admin'))
                $sPrivs .= 'Admin<br />';

            if($oVoter->isMaintainer($this->iVersionId))
                $sPrivs .= 'Maintainer of this version<br />';

            if($oVoter->isMaintainer())
            {
                $oM = new objectManager('maintainerView', 'View maintainership info');
                $sPrivs .= '<a href="'.$oM->makeUrl('view',$oVoter->iUserId).'">Maintainer (other entries)</a><br />';
            }

            $oTableRow->AddTextCell($sPrivs);
            $oTable->AddRow($oTableRow);
        }

        echo $oTable->getString();
    }

    public function getOutputEditorValues($aValues)
    {
    
    }

    public function create()
    {
        return true;
    }

    public function update()
    {
        return true;
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

/*
 * total votes for versionId
 */
function vote_count_version_total($iVersionId)
{
    $hResult = query_parameters("SELECT * FROM appVotes WHERE versionId = '?'",
                                    $iVersionId);
    return query_num_rows($hResult);
}

?>
