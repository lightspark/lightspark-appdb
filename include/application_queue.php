<?php

/**
 * Class for submitting/processing applications
 */

class application_queue
{
    var $oVersionQueue;
    var $oApp;
    var $oVendor;

    function application_queue($iAppId = null, $oRow = null)
    {
        $this->oApp = new application($iAppId, $oRow);

        /* If this is an existing application then there must be a version
           accompanying it */
        if($this->oApp->iAppId)
        {
            /* Normal users do not get a aVersionsIds property, so we have to fetch
               the versionId manually.  Normal users only have access to rejected
               applications */
            if($_SESSION['current']->hasPriv("admin"))
            {
                $iVersionId = $this->oApp->aVersionsIds[0];
            } else if($this->oApp->sQueued == "rejected")
            {
                $sQuery = "SELECT versionId FROM appVersion WHERE appId = '?' LIMIT 1";
                $hResult = query_parameters($sQuery, $this->oApp->iAppId);
                if($hResult)
                {
                    if($oRow = query_fetch_object($hResult))
                        $iVersionId = $oRow->versionId;
                }
            }
            $iVendorId = $this->oApp->iVendorId;
        }
        else
        {
            $iVersionId = null;
            $iVendorId = null;
        }

        $this->oVendor = new vendor($iVendorId);

        $this->oVersionQueue = new version_queue($iVersionId);
    }

    function create()
    {
        $bSuccess = TRUE;

        /* Create a new vendor if an existing one was not selected, and
           assign the application to it */
        if(!$this->oApp->iVendorId)
        {
            $this->oVendor->create();
            $this->oApp->iVendorId = $this->oVendor->iVendorId;
        }

        if(!$this->oApp->create())
            $bSuccess = FALSE;

        /* Assign the version to the new application */
        $this->oVersionQueue->oVersion->iAppId = $this->oApp->iAppId;

        if(!$this->oVersionQueue->create())
            $bSuccess = FALSE;

        return $bSuccess;
    }

    function update()
    {
        $bSucess = TRUE;

        /* If the vendor was already un-queued then the edit vendor form would not
           have been displayed, and so the vendor name will not be set.  Thus only
           update the vendor if the name is set */
        if($this->oVendor->sName)
            $this->oVendor->update();

        if(!$this->oApp->update())
            $bSuccess = FALSE;

        if(!$this->oVersionQueue->update())
            $bSuccess = FALSE;

        return $bSuccess;
    }

    function unQueue()
    {
        /* The vendor is not necessarily queued, as it could have existed on
           beforehand */
        if($this->oVendor->sQueued != "false")
            $this->oVendor->unQueue();

        $this->oApp->unQueue();
        $this->oVersionQueue->unQueue();
    }

    function reQueue()
    {
        $this->oApp->reQueue();
        $this->oVersionQueue->reQueue();
    }

    function reject()
    {
        $this->oVersionQueue->reject();
        $this->oApp->reject();
    }

    function delete()
    {
        $bSuccess = TRUE;

        if(!$this->oApp->delete())
            $bSuccess = FALSE;

        /* When deleting a duplicate app in the application queue, the version is moved
           to another app and so when application_queue::delete() is called there is
           no version child to delete, so check if the versionId is valid */
        if($this->oVersionQueue->oVersion->iVersionId)
        {
            if(!$this->oVersionQueue->delete())
                $bSuccess = FALSE;
        }

        return $bSuccess;
    }

    function objectGetChildren()
    {
        return $this->oApp->objectGetChildren();
    }

    function objectGetSubmitterId()
    {
        return $this->oApp->objectGetSubmitterId();
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        return $this->oApp->objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction);
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        return $this->oApp->objectGetMail($sAction, $bMailSubmitter, $bParentAction);
    }

    function outputEditor()
    {
        /* We ask the user for the application name first so as to avoid duplicate
           submissons; a list of potential duplicates is displayed on the next page */
        if(!$this->oApp->sName)
        {
            echo "<div style='margin:auto; width: 500px; border:1px solid; background-color:#eee; padding:2px; '>\n";
            echo "<div style='font-weight:bold; padding:3px;'>\n";
            echo "Application name:\n";
            echo "</div>\n";
            echo "<div style='padding:5px;'>\n";
            echo "<center><input type=\"text\" name=\"sAppName\" style='width:485px;' /></center>\n";
            echo "</div>\n";
            echo "<input type=\"hidden\" name=\"sSub\" value=\"view\" />\n";
            echo "<input type=\"hidden\" name=\"sAppType\" value=\"application\" />\n";
            echo "</div>\n";
        } else
        {
            /* Show potential duplicates */
            echo html_frame_start("Potential duplicate applications in the ".
                    "database","90%","",0);
            $this->displayDuplicates();
            echo html_frame_end("&nbsp;");

            $this->oApp->outputEditor();

            /* We need to accept vendors submitted using the old
               keyword hack.  This should be removed soon */
            if(!$this->oVendor->sName && $this->oApp->iAppId)
            {
                $this->oVendor->sName = get_vendor_from_keywords(
                    $this->oApp->sKeywords);
            }

            /* Display the new vendor form for new applications or if we
               are processing an application and the vendor is queued */
            if(!$this->oApp->iAppId || $this->oVendor->sQueued != "false")
            {
                echo html_frame_start("New Vendor", "90%");
                $this->oVendor->outputEditor();
                echo html_frame_end();
            }

            $this->oVersionQueue->oVersion->outputEditor();

            global $aClean;

            echo $this->oVersionQueue->oDownloadUrl->outputEditorSingle(
                    $this->oVersionQueue->oVersion->iVersionId, $aClean);

            $this->oVersionQueue->oTestDataQueue->outputEditor();
        }
    }

    function getOutputEditorValues($aClean)
    {
        $this->oApp->getOutputEditorValues($aClean);
        $this->oVersionQueue->getOutputEditorValues($aClean);
        $this->oVendor->getOutputEditorValues($aClean);
    }

    function checkOutputEditorInput($aClean)
    {
        /* We want outputEditor() to be called again so we can display the main
           app form.  No erros are displayed since we only return TRUE */
        if($this->oApp->sName && !$aClean['bMainAppForm'])
            return TRUE;

        $sErrors = $this->oApp->checkOutputEditorInput($aClean);
        $sErrors .= $this->oVersionQueue->checkOutputEditorInput($aClean);
        return $sErrors;
    }

    function canEdit()
    {
        return $this->oApp->canEdit();
    }

    function mustBeQueued()
    {
        return $this->oApp->mustBeQueued();
    }

    function displayDuplicates()
    {
        echo "<b>Like matches</b>\n";
        $this->displayDuplicateTable(searchForApplication($this->oApp->sName));
        echo "<br /><b>Fuzzy matches</b>\n";
        $this->displayDuplicateTable(searchForApplicationFuzzy($this->oApp->sName, 60));
    }

    function displayDuplicateTable($hResult)
    {
        /* Exit if the MySQL handle is invalid */
        if($hResult === FALSE)
            return FALSE;

        /* There's no point in displaying an empty table */
        if($hResult === null || (query_num_rows($hResult) == 0))
        {
            echo "No matches.<br />\n";
            return;
        }

        $aHeader =  array(
                "Application name",
                "Description",
                "No. versions"
                          );

        /* We can only move data if the current application already exists, and
           we have admin privileges */
        if($this->oApp->iAppId && $_SESSION['current']->hasPriv("admin"))
        {
            $bCanMove = TRUE;
            $aHeader[] = array("Move data", 'width="80"');
        } else
        {
            $bCanMove = FALSE;
        }

        echo "<table cellpadding='5px'>";
        echo html_tr($aHeader, "color4");

        for($i = 0; $oRow = query_fetch_object($hResult); $i++)
        {
            $oApp = new application($oRow->appId);
            $aCells = array(
                    $oApp->objectMakeLink(),
                    util_trim_description($oApp->sDescription),
                    sizeof($oApp->aVersionsIds)
                                   );

            if($bCanMove)
            {
                $aCells[] = "<a href=\"objectManager.php?sClass=application_queue&".
                        "bIsQueue=true&sAction=moveChildren&iId=".
                        $this->oApp->iAppId."&iNewId=".$oApp->iAppId.
                        "\">Move data</a>";
            }
            echo html_tr($aCells, ($i % 2) ? "color0" : "color1");
        }
        echo "</table>";
    }

    function display()
    {
        $this->oApp->display();
    }

    function objectMakeUrl()
    {
        return TRUE;
    }

    function objectMakeLink()
    {
        return TRUE;
    }

    function objectGetItemsPerPage($bQueued = false)
    {
        return $this->oApp->objectGetItemsPerPage($bQueued);
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        return $this->oApp->objectGetEntriesCount($bQueued, $bRejected);
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0, $sOrderBy = "appId")
    {
        return $this->oApp->objectGetEntries($bQueued, $bRejected, $iRows, $iStart,
                                             $sOrderBy);
    }
 
    function objectGetHeader()
    {
        return $this->oApp->objectGetHeader();
    }

    function objectGetTableRow()
    {
        return $this->oApp->objectGetTableRow();
    }

    function objectMoveChildren($iNewId)
    {
        return $this->oApp->objectMoveChildren($iNewId);
    }

    function objectDisplayQueueProcessingHelp()
    {
        return application::objectDisplayQueueProcessingHelp();
    }

    function objectDisplayAddItemHelp()
    {
        $this->oApp->objectDisplayAddItemHelp();
    }

    function allowAnonymousSubmissions()
    {
        return application::allowAnonymousSubmissions();
    }

    function objectGetId()
    {
        return $this->oApp->objectGetId();
    }
}

?>
