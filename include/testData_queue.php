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
        $this->oTestData->create();
    }

    function update()
    {
        $this->oTestData->update();
        $this->oDistribution->update();
    }

    function outputEditor()
    {
        $this->oTestData->outputEditor();

        /* If the testData is already associated with a distribution and the distribution is
           un-queued, there is no need to display the distribution form here */
        if(!$this->oTestData->iDistributionId or $this->oDistributionId->sQueued != "false")
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

    function objectDisplayAddItemHelp()
    {
        $this->oTestData->objectDisplayAddItemHelp();
    }
}

?>
