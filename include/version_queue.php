<?php

class version_queue
{
    var $oTestDataQueue;
    var $oVersion;
    var $oDownloadUrl;

    function version_queue($iVersionId = null, $oRow = null)
    {
        $this->oVersion = new version($iVersionId, $oRow);
        $iTestingId = null;
        $iDownloadUrlId = null;

        if(!$iVersionId)
            $iVersionId = $this->oVersion->iVersionId;

        if($iVersionId)
        {
            $iTestingId = testData::getNewestTestIdFromVersionId($iVersionId,
                                                                 $this->oVersion->sQueued);
            /* This illustrates the importance of converting downloadurl completely
               to the objectManager model.  If we don't get  a match searching for
               a queued entry, try finding a rejected one. */
            if(($hResult = appData::getData($iVersionId, "downloadurl",
                                           TRUE, TRUE, FALSE)) ||
               $hResult = appData::getData($iVersionId, "downloadurl",
                                           TRUE, TRUE, TRUE))
            {
                if($oRow = mysql_fetch_object($hResult))
                    $iDownloadUrlId = $oRow->id;
            }
        }

        $this->oTestDataQueue = new testData_queue($iTestingId);
        $this->oDownloadUrl = new downloadurl($iDownloadUrlId);
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
        $this->oDownloadUrl->reQueue();
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
        $this->oDownloadUrl->unQueue();
    }

    function outputEditor()
    {
        global $aClean;

        echo "<div class='default_container'>\n";

        /* Display duplicate list if this is a an existing version */
        if($this->oVersion->iVersionId)
            $this->displayMoveTestTable();

        $this->oVersion->outputEditor();

        /* Allow the user to apply as maintainer if this is a new version.
        If it is a new application as well, radio boxes will be displayed
        by the application class instead. */
        if(!$this->oVersion->iVersionId && $this->oVersion->iAppId &&
           !$_SESSION['current']->isSuperMaintainer($this->oVersion->iAppId))
        {
            echo html_frame_start("Become Maintainer", "90%");
            echo "<div style='padding:5px;' class='color0'>\n";
            echo "<table width='100%' cellpadding=0 cellspacing=0>";
            if($this->oVersion->iMaintainerRequest == MAINTAINER_REQUEST)
                $sRequestMaintainerChecked = 'checked="checked"';
            echo html_tr(array(
                               array("<b>Become maintainer?</b>", ""),
                               array(
                                     "<input type=\"checkbox\" $sRequestMaintainerChecked".
                                     "name=\"iMaintainerRequest\" value=\"".MAINTAINER_REQUEST."\" /> ".
                                     "Check this box to request being a maintainer for this version",
                                     "")
                               ));
            echo "</table>";
            echo "</div\n";
            echo html_frame_end();
        }

        echo $this->oDownloadUrl->outputEditorSingle($this->oVersion->iVersionId,
                $aClean);
        $this->oTestDataQueue->outputEditor();

        echo "</div>\n";
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

    function mustBeQueued()
    {
        return $this->oVersion->mustBeQueued();
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

    function objectGetTableRow()
    {
        return $this->oVersion->objectGetTableRow();
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
                          "objectManager.php?sClass=version_queue&bIsQueue=true&".
                          "sAction=moveChildren&iId=".
                          $this->oVersion->iVersionId."&iNewId=".
                          $oVersion->iVersionId."&sTitle=Version+Queue"),
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

    function objectMoveChildren($iNewId)
    {
        return $this->oVersion->objectMoveChildren($iNewId);
    }

    function allowAnonymousSubmissions()
    {
        return version::allowAnonymousSubmissions();
    }

    function objectGetId()
    {
        return $this->oVersion->objectGetId();
    }
}

?>
