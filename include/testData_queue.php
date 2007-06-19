<?php

class testData_queue
{
    var $oTestData;
    var $oDistribution;

    function testData_queue($iTestId = null, $oRow = null)
    {
        $this->oTestData = new testData($iTestId, $oRow);
        $this->oDistribution = new distribution($this->oTestData->iDistributionId);
    }

    function create()
    {
        if(!$this->oTestData->iDistributionId)
        {
            $this->oDistribution->create();
            $this->oTestData->iDistributionId = $this->oDistribution->iDistributionId;
        }

        return $this->oTestData->create();
    }

    function delete()
    {
        $bSuccess = $this->oTestData->delete();

        /* We delete the distribution if it has not been approved and is not associated
           with any other testData.  Otherwise we would have to have a distribution
           queue for admins to clean up unused, queued entries */
        $this->oDistribution = new distribution($this->oDistribution->iDistributionId);
        if(!sizeof($this->oDistribution->aTestingIds) &&
           $this->oDistribution->canEdit())
            $this->oDistribution->delete();

        return $bSuccess;
    }

    function reQueue()
    {
        $this->oTestData->reQueue();
        if($this->oDistribution->sQueued == "rejected")
            $this->oDistribution->reQueue();
    }

    function unQueue()
    {
        $this->oTestData->unQueue();

        /* Avoid a misguiding message about the distribution being unqueued */
        if($this->oDistribution->sQueued != "false")
            $this->oDistribution->unQueue();
    }

    function reject()
    {
        $this->oTestData->reject();
    }

    function update()
    {
        $this->oTestData->update();

        /* If the distribution was already un-queued the form for editing it would
           not have been displayed and getOutputEditorValues() wouldn't have
           retrieved a valid sName for the distribution. If sName isn't valid
           we shouldn't update the distribution */
        if($this->oDistribution->sName)
            $this->oDistribution->update();
    }

    function outputEditor()
    {
        $this->oTestData->outputEditor();

        /* If we are processing queued test results with a queued distribution,
           we display some additional help here */
        if($this->oDistribution->iDistributionId &&
                $this->oDistribution->sQueued != "false" && $this->canEdit())
        {
            echo "The user submitted a new distribution, which will be un-queued ".
                "together with the test data unless you select an existing one ".
                "from the list above.";
        }

        /* If the testData is already associated with a distribution and the
           distribution is un-queued, there is no need to display the
           distribution form here */
        if(!$this->oTestData->iDistributionId or 
                $this->oDistribution->sQueued != "false")
        {
            echo html_frame_start("New Distribution", "90%");
            $this->oDistribution->outputEditor();
            echo html_frame_end();
        }
    }

    function getOutputEditorValues($aClean)
    {
        $this->oTestData->getOutputEditorValues($aClean);
        $this->oDistribution->getOutputEditorValues($aClean);
    }

    function checkOutputEditorInput($aClean)
    {
        return $this->oTestData->checkOutputEditorInput($aClean);
    }

    function canEdit()
    {
        return $this->oTestData->canEdit();
    }

    function mustBeQueued()
    {
        return $this->oTestData->mustBeQueued();
    }

    function objectDisplayAddItemHelp()
    {
        $this->oTestData->objectDisplayAddItemHelp();
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0, $sOrderBy = "testingId")
    {
        return $this->oTestData->objectGetEntries($bQueued, $bRejected, $iRows, $iStart, $sOrderBy);
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        return testData::objectGetEntriesCount($bQueued, $bRejected);
    }

    function objectGetHeader()
    {
        return $this->oTestData->objectGetHeader();
    }

    function objectGetTableRow()
    {
        return $this->oTestData->objectGetTableRow();
    }

    function objectDisplayQueueProcessingHelp()
    {
        $oTest = new testData();
        $oTest->objectDisplayQueueProcessingHelp();
    }

    function display()
    {
        return $this->oTestData->display();
    }

    function objectMakeUrl()
    {
        return $this->oTestData->objectMakeUrl();
    }

    function objectMakeLink()
    {
        return $this->oTestData->objectMakeLink();
    }

    function allowAnonymousSubmissions()
    {
        return testData::allowAnonymousSubmissions();
    }

    function objectGetItemsPerPage($bQueued = false)
    {
        return testData::objectGetItemsPerPage($bQueued);
    }

    function objectGetId()
    {
        return $this->oTestData->objectGetId();
    }
}

?>
