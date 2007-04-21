<?php

class version_queue
{
    var $oTestDataQueue;
    var $oVersion;
    var $oDownloadUrl;

    function version_queue($iVersionId = null)
    {
        $this->oVersion = new version($iVersionId);
        $iTestingId = null;

        if($iVersionId)
        {
            $iTestingId = testData::getNewestTestIdFromVersionId($iVersionId,
                                                                 $this->oVersion->sQueued);
        }

        $this->oTestDataQueue = new testData_queue($iTestingId);
        $this->oDownloadUrl = new downloadurl();
    }

    function create()
    {
        global $aClean;
        if(!$this->oVersion->create())
            return FALSE;

        $this->oTestDataQueue->oTestData->iVersionId = $this->oVersion->iVersionId;
        $this->oTestDataQueue->create();
        $this->oDownloadUrl->processFormSingle($this->oVersion->iVersionId,
                                               $aClean,
                                               $this->oVersion->canEdit());

        return TRUE;
    }

    function reQueue()
    {
        $this->oVersion->reQueue();
        $this->oTestDataQueue->reQueue();
    }

    function reject()
    {
        $this->oVersion->reject();

        if($this->oDownloadUrl->iId)
            $this->oDownloadUrl->reject();

        $this->oTestDataQueue->reject();
    }

    function update()
    {
        $this->oVersion->update();
        $this->oTestDataQueue->update();
    }

    function delete()
    {
        return $this->oVersion->delete();
    }

    function unQueue()
    {
        $this->oVersion->unQueue();
        $this->oTestDataQueue->unQueue();
    }

    function outputEditor()
    {
        $this->displayMoveTestTable();
        $this->oVersion->outputEditor();

        /* Allow the user to apply as maintainer if this is a new version.
        If it is a new application as well, radio boxes will be displayed
        by the application class instead. */
        if(!$this->oVersion->iVersionId && $this->oVersion->iAppId)
        {
            echo html_frame_start("Become Maintainer", "90%");
            echo "<table>";
            if($this->oVersion->iMaintainerRequest == MAINTAINER_REQUEST)
                $sRequestMaintainerChecked = 'checked="checked"';
            echo html_tr(array(
                array("<b>Become maintainer?</b>", "class=\"color0\""),
                "<input type=\"checkbox\" $sRequestMaintainerChecked".
                "name=\"iMaintainerRequest\" value=\"".MAINTAINER_REQUEST."\" /> ".
                "Check this box to request being a maintainer for this version"),
                "","valign=\"top\"");
            echo "</table>";
            echo html_frame_end();
        }

        echo $this->oDownloadUrl->outputEditorSingle($this->oVersion->iVersionId,
                $aClean);
        $this->oTestDataQueue->outputEditor();
    }

    function getOutputEditorValues($aClean)
    {
        $this->oVersion->getOutputEditorValues($aClean);
        $this->oTestDataQueue->getOutputEditorValues($aClean);
    }

    function checkOutputEditorInput($aClean)
    {
        $sErrors = $this->oVersion->checkOutputEditorInput($aClean);
        $sErrors .= $this->oTestDataQueue->checkOutputEditorInput($aClean);
        return $sErrors;
    }

    function canEdit()
    {
        return $this->oVersion->canEdit();
    }

    function objectDisplayAddItemHelp()
    {
        /* $this->oVersion->displayAddItemHelp(); */
    }

    function objectGetEntries($bQueued, $bRejected)
    {
        return $this->oVersion->objectGetEntries($bQueued, $bRejected);
    }
 
    function objectGetHeader()
    {
        return $this->oVersion->objectGetHeader();
    }

    function objectGetInstanceFromRow($oRow)
    {
        return version::objectGetInstanceFromRow($oRow);
    }

    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        return $this->oVersion->objectOutputTableRow($oObject, $sClass, $sEditLinkLabel);
    }

    function display()
    {
        $this->oVersion->display();
    }

    function objectMakeUrl()
    {
        return TRUE;
    }

    function objectMakeLink()
    {
        return TRUE;
    }

    function displayMoveTestTable()
    {
        $oApp = new application($this->oVersion->iAppId);

        echo html_frame_start("Move test to version","90%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" ".
                "cellspacing=\"1\">\n\n";

        echo html_tr(array(
                array("Version", 'width="80"'),
                "Description",
                array("Rating", 'width="80"'),
                array("Wine version", 'width="80"'),
                array("Comments", 'width="40"'),
                array("Move test results", 'width="80"')
                          ),
                "color4");

        $i = 0;
        foreach($oApp->aVersionsIds as $iVersionId)
        {
            $oVersion = new Version($iVersionId);
            if ($oVersion->sQueued == 'false')
            {
                //display row
                echo html_tr(array(
                        $oVersion->objectMakeLink(),
                        util_trim_description($oVersion->sDescription),
                        array($oVersion->sTestedRating, 'align="center"'),
                        array($oVersion->sTestedRelease, 'align="center"'),
                        array(Comment::get_comment_count_for_versionid(
                            $oVersion->iVersionId), 'align="center"'),
                        html_ahref("Move here",
                          "admin/adminAppQueue.php?sSub=movetest&sAppType=version&".
                          "iVersionId="
                          .$this->oVersion->iVersionId."&iVersionIdMergeTo=".
                          $oVersion->iVersionId)
                                  ),
                            ($i % 2) ? "color0" : "color1");

                $i++;
            }
        }
        echo "</table>\n";
        echo html_frame_end("&nbsp;");
    }

    function objectDisplayQueueProcessingHelp()
    {
        version::objectDisplayQueueProcessingHelp();
    }
}

?>
