<?php

class testData_queue
{
    var $oTestData;
    var $oDistribution;

    function testData_queue($iTestId = null)
    {
        $this->oTestData = new testData($iTestId);
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
        return $this->oTestData->delete();
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
        if($this->oDistribution->sQueued != "false" && $this->canEdit())
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
            $this->oDistribution->outputEditor();
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

    function objectGetEntries($bQueued, $bRejected)
    {
        return $this->oTestData->objectGetEntries($bQueued, $bRejected);
    }

    function objectGetHeader()
    {
        return $this->oTestData->objectGetHeader();
    }

    function objectGetInstanceFromRow($oRow)
    {
        return testData::objectGetInstanceFromRow($oRow);
    }

    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        return $this->oTestData->objectOutputTableRow($oObject, $sClass, $sEditLinkLabel);
    }

    function objectDisplayQueueProcessingHelp()
    {
        $oTest = new testData();
        echo "<p>";
        if($oTest->canEdit)
            echo "This is the list of rejected test results, waiting to be resubmitted or deleted.";
        else
            echo "This is the list of your rejected test results.  Here you can make changes to ".
                 "them and resubmit them into the database.";
        echo "</p>\n";
    }

    function display()
    {
        return $this->oTest->display();
    }

    function objectMakeUrl()
    {
        return $this->oTest->objectMakeUrl();
    }

    function objectMakeLink()
    {
        return $this->oTest->objectMakeLink();
    }
}

?>
