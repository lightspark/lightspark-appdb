<?php
/*****************************************/
/* this class represents Test results    */
/*****************************************/
require_once(BASE."include/distribution.php");
require_once(BASE."include/util.php");
// Class for handling Test History.

class testData{
    var $iTestingId;
    var $iVersionId;
    var $shWhatWorks;
    var $shWhatDoesnt;
    var $shWhatNotTested;
    var $sTestedRelease;
    var $iDistributionId;
    var $sTestedDate;
    var $sInstalls;
    var $sRuns;
    var $sTestedRating;
    var $sComments;
    var $sSubmitTime;
    var $iSubmitterId;
    private $sState;

     // constructor, fetches the data.
    function testData($iTestingId = null, $oRow = null)
    {
        // we are working on an existing test
        if(!$iTestingId && !$oRow)
            return;

        // We fetch the data related to this test.
        if(!$oRow)
        {
            $sQuery = "SELECT *
                        FROM testResults
                        WHERE testingId = '?'";
            if($hResult = query_parameters($sQuery, $iTestingId))
                $oRow = query_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iTestingId = $oRow->testingId;
            $this->iVersionId = $oRow->versionId;
            $this->shWhatWorks = $oRow->whatWorks;
            $this->shWhatDoesnt = $oRow->whatDoesnt;
            $this->shWhatNotTested = $oRow->whatNotTested;
            $this->sTestedDate = $oRow->testedDate;
            $this->iDistributionId = $oRow->distributionId;
            $this->sTestedRelease = $oRow->testedRelease;
            $this->sInstalls = $oRow->installs;
            $this->sRuns = $oRow->runs;
            $this->sTestedRating = $oRow->testedRating;
            $this->sComments = $oRow->comments;
            $this->sSubmitTime = $oRow->submitTime;
            $this->iSubmitterId = $oRow->submitterId;
            $this->sState = $oRow->state;
        }
    }

    // Creates a new Test Results.
    function create()
    {
        $oVersion = new version($this->iVersionId);
        if($oVersion->objectGetState() != 'accepted')
            $this->sState = 'pending';
        else
            $this->sState = $this->mustBeQueued() ? 'queued' : 'accepted';

        $hResult = query_parameters("INSERT INTO testResults (versionId, whatWorks, whatDoesnt,".
                                    "whatNotTested, testedDate, distributionId, testedRelease,".
                                    "installs, runs, testedRating, comments,".
                                    "submitTime, submitterId, state)".
                                    "VALUES('?', '?', '?', '?', '?', '?', '?',".
                                    "'?', '?', '?', '?',".
                                    "?, '?', '?')",
                                    $this->iVersionId, $this->shWhatWorks,
                                    $this->shWhatDoesnt,
                                    $this->shWhatNotTested, $this->sTestedDate,
                                    $this->iDistributionId,
                                    $this->sTestedRelease, $this->sInstalls,
                                    $this->sRuns,
                                    $this->sTestedRating, $this->sComments,
                                    "NOW()",
                                    $_SESSION['current']->iUserId,
                                    $this->sState);

        if($hResult)
        {
            $this->iTestingId = query_appdb_insert_id();
            $this->testData($this->iTestingId);
            $this->SendNotificationMail();
            return true;
        }
        else
        {
            addmsg("Error while creating test results.", "red");
            return false;
        }
    }

    // Update Test Results.
    function update($bSilent=false)
    {
        // is the current user allowed to update this test result? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && $this->sState != 'accepted'))
        {
            return;
        }

        $oOldTest = new testData($this->iTestingId);
        /* Nothing changed */
        if($this == $oOldTest)
            return TRUE;

        /* Provide some feedback as to what was changed.  Not all fields are
           interesting */
        $sWhatChanged = "";
        if($this->shWhatWorks != $oOldTest->shWhatWorks)
        {
            $sWhatChanged .= "What works was changed from\n'$oOldTest->shWhatWorks'\n".
                    "to\n'$this->shWhatWorks'.\n";
        }

        if($this->shWhatDoesnt != $oOldTest->shWhatDoesnt)
        {
            $sWhatChanged .= "What does not work was changed from\n'"
                    .$oOldTest->shWhatDoesnt."'\n to\n'$this->shWhatDoesnt'.\n";
        }

        if($this->shWhatNotTested != $oOldTest->shWhatNotTested)
        {
            $sWhatChanged .= "What was not tested was changed from\n'".
                    $oOldTest->shWhatNotTested."'\nto\n'$this->shWhatNotTested'.\n";
        }

        if($this->sComments != $oOldTest->sComments)
        {
            $sWhatChanged .= "Extra comments was changed from\n'".
                    $oOldTest->sComments."'\nto\n'$this->sComments'.\n";
        }

        if($this->iDistributionId != $oOldTest->iDistributionId)
        {
            $oNewDist = new distribution($this->iDistributionId);
            $oOldDist = new distribution($oOldTest->iDistributionId);
            $sWhatChanged .= "Distribution was changed from $oOldDist->sName ".
                    "to $oNewDist->sName.\n";
        }

        if($this->sInstalls != $oOldTest->sInstalls)
        {
            $sWhatChanged .= "Installs? was changed from $oOldTest->sInstalls to ".
                    "$this->sInstalls.\n";
        }

        if($this->sRuns != $oOldTest->sRuns)
        {
            $sWhatChanged .= "Runs? was changed from $oOldTest->sRuns to ".
                    "$this->sRuns.\n";
        }

        if($this->sTestedRating != $oOldTest->sTestedRating)
        {
            $sWhatChanged .= "Rating was changed from $oOldTest->sTestedRating ".
                    "to $this->sTestedRating.\n";
        }

        if($this->sTestedRelease != $oOldTest->sTestedRelease)
        {
            $sWhatChanged .= "Tested release was changed from ".
                    $oOldTest->sTestedRelease." to $this->sTestedRelease.\n";
        }

        if($this->iVersionId != $oOldTest->iVersionId)
        {
            $sWhatChanged .= 'Moved from '.version::fullName($oOldTest->iVersionId).' to '.version::fullName($this->iVersionId)."\n";
            $oNewVersion = new version($this->iVersionId);
            if($oNewVersion->objectGetState() == 'accepted' && $this->sState == 'pending')
                $this->sState = 'queued';
        }

        if(query_parameters("UPDATE testResults SET 
                                        versionId       = '?',
                                        whatWorks       = '?',
                                        whatDoesnt      = '?',
                                        whatNotTested   = '?',
                                        testedDate      = '?',
                                        distributionId  = '?',
                                        testedRelease   = '?',
                                        installs        = '?',
                                        runs            = '?',
                                        testedRating    = '?',
                                        comments        = '?',
                                        state           = '?'
                                    WHERE testingId = '?'",
                            $this->iVersionId,
                            $this->shWhatWorks,
                            $this->shWhatDoesnt,
                            $this->shWhatNotTested,
                            $this->sTestedDate,
                            $this->iDistributionId,
                            $this->sTestedRelease,
                            $this->sInstalls,
                            $this->sRuns,
                            $this->sTestedRating,
                            $this->sComments,
                            $this->sState,
                            $this->iTestingId))
        {
            if(!$bSilent)
                $this->SendNotificationMail("edit", $sWhatChanged);
            return true;
        }
        else
        {
            addmsg("Error while updating test results", "red");
            return false;
        }
    }

    // Purge test results from the database
    function purge()
    {
        // is the current user allowed to delete this test result? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && !($this->sState == 'accepted')))
        {
            return false;
        }

        // now we delete the data
        $sQuery = "DELETE FROM testResults
                WHERE testingId = '?' 
                LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iTestingId)))
            return false;

        return true;
    }

    // Delete test results.
    function delete()
    {
        // is the current user allowed to delete this test result? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && $this->sState != 'accepted'))
        {
            return false;
        }

        // now we flag the data as deleted
        $sQuery = "UPDATE testResults SET state = 'deleted'
                   WHERE testingId = '?' 
                   LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iTestingId)))
        {
            addmsg("Error removing the deleted test data!", "red");
            return false;
        }

        return true;
    }


    // Move Test Data out of the queue.
    function unQueue()
    {
        // is the current user allowed to delete this test data? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") &&
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            return false;
        }

        // If we are not in the queue, we can't move the test data out of the queue.
        if($this->sState == 'accepted')
            return false;

        if(query_parameters("UPDATE testResults SET state = '?' WHERE testingId = '?'",
                            'accepted', $this->iTestingId))
        {
            $this->sState = 'accepted';
            // we send an e-mail to interested people
            $this->mailSubmitter("add");
            $this->SendNotificationMail();
        } else
        {
          return false;
        }

        return true;
    }

    function Reject()
    {
        // is the current user allowed to delete this test data? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") &&
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            return;
        }

        // If we are not in the queue, we can't move the version out of the queue.
        if($this->sState != 'queued')
            return false;

        if(query_parameters("UPDATE testResults SET state = '?' WHERE testingId = '?'", 
                            'rejected', $this->iTestingId))
        {
            $this->sState = 'rejected';
            // we send an e-mail to interested people
            $this->mailSubmitter("reject");
            $this->SendNotificationMail("reject");
        }
    }

    function ReQueue()
    {
        // is the current user allowed to requeue this data 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") &&
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !$_SESSION['current']->iUserId == $this->iSubmitterId)
        {
            return;
        }

        if(query_parameters("UPDATE testResults SET state = '?' WHERE testingId = '?'",
                            'queued', $this->iTestingId))
        {
            $this->sState = 'queued';
            // we send an e-mail to interested people
            $this->SendNotificationMail();
        }
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        $oOptions = new mailOptions();

        if($sAction == "delete" && $bParentAction)
            $oOptions->bMailOnce = TRUE;

        return $oOptions;
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        $oSubmitter = new User($this->iSubmitterId);
        $sName = version::fullName($this->iVersionId);

        $sMsg = null;
        $sSubject = null;

        if($bMailSubmitter)
        {
            switch($sAction)
            {
                case "delete":
                    $sSubject =  "Submitted test data deleted";
                    if($bParentAction)
                    {
                        $sMsg = "All test data you submitted for '$sName' has ".
                                "been deleted because '$sName' was deleted.";
                    } else
                    {
                        $sMsg  = "The test report you submitted for '$sName' has ".
                                "been deleted.";
                    }
                break;
            }
            $aMailTo = nulL;
        } else
        {
            switch($sAction)
            {
                case "delete":
                    if(!$bParentAction)
                    {
                        $sSubject = "Test Results deleted for $sName by ".
                                    $_SESSION['current']->sRealname;
                        $sMsg = "";
                    }
                break;
            }
            $aMailTo = User::get_notify_email_address_list(null, $this->iVersionId);
        }
        return array($sSubject, $sMsg, $aMailTo);
    }

    function mailSubmitter($sAction="add")
    {
        global $aClean;

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);

            /* Get the full app/version name to display */
            $sName = version::fullName($this->iVersionId);

            $oVersion = new version($this->iVersionId);

            switch($sAction)
            {
            case "add":
                $sSubject =  "Submitted testing data accepted";
                $sMsg  = "The testing data you submitted for '$sName' has been ".
                        "accepted by ".$_SESSION['current']->sRealname.".";
                $sMsg .= $oVersion->objectMakeUrl()."&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject =  "Submitted testing data rejected";
                $sMsg  = "The testing data you submitted for '$sName' has ".
                        "been rejected by ".$_SESSION['current']->sRealname.".";
                $sMsg .= $this->objectMakeUrl()."\n";
                $sMsg .= "Reason given:\n";
            break;
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application ".
                    "Database better for all users.";

            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        global $aClean;

        $oVersion = new Version($this->iVersionId);
        $oApp = new Application($oVersion->iAppId);
        $sBacklink = $oVersion->objectMakeUrl()."&iTestingId=".$this->iTestingId."\n";

        switch($sAction)
        {
            case "add":
                if($this->sState == 'accepted')
                {
                    $sSubject = "Test Results added to version ".$oVersion->sName." of ".$oApp->sName." by ".$_SESSION['current']->sRealname;
                    $sMsg  .= $sBacklink;
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This Test data has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                    }
                    if($aClean['sReplyText'])
                    {
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                    }
                    addmsg("The test data was successfully added into the database.", "green");
                } else // test data queued.
                {
                    $sSubject = "Test Results submitted for version ".$oVersion->sName." of ".$oApp->sName." by ".$_SESSION['current']->sRealname;
                    $sMsg  .= $sBacklink;
                    $sMsg .= "This test data has been queued.";
                    $sMsg .= "\n";
                    addmsg("The test data you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject = "Test Results modified for version ".$oVersion->sName." of ".$oApp->sName." by ".$_SESSION['current']->sRealname;
                $sMsg  .= $sBacklink;
                addmsg("test data modified.", "green");
            break;
            case "reject":
                $sSubject = "Test Results rejected for version ".$oVersion->sName." of ".$oApp->sName." by ".$_SESSION['current']->sRealname;
                $sMsg  .= $sBacklink;
                 // if replyText is set we should report the reason the data was rejected 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }
                addmsg("test data rejected.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }
 
    function ShowTestResult()
    {
        echo '<p><b>What works</b><br />',"\n";
        echo $this->shWhatWorks,"\n";
        echo '<p><br /><b>What does not</b><br />',"\n";
        echo $this->shWhatDoesnt,"\n";
        echo '<p><br /><b>What was not tested</b><br />',"\n";
        echo $this->shWhatNotTested,"\n";
        echo '<p><br /><b>Additional Comments</b><br />',"\n";
        echo $this->sComments,"\n";
    }

    function CreateTestTable()
    {
        echo '<div class="info_container">',"\n";
        echo '<div class="title_class">Test Results</div>',"\n";
        echo '<div class="info_contents">',"\n";

        // create the table
        $oTable = new Table();
        $oTable->SetClass("historyTable");
        $oTable->SetBorder(1);
        $oTable->SetWidth("100%");

        // setup the table header
        $oTableRowHeader = new TableRow();
        $oTableRowHeader->SetClass("historyHeader");
        $oTableRowHeader->AddTextCell("");
        $oTableRowHeader->AddTextCell("Distribution");
        $oTableRowHeader->AddTextCell("Test date");
        $oTableRowHeader->AddTextCell("Wine version");
        $oTableRowHeader->AddTextCell("Installs?");
        $oTableRowHeader->AddTextCell("Runs?");
        $oTableRowHeader->AddTextCell("Rating");
        $oTableRowHeader->AddTextCell("Submitter");
        $oTable->SetHeader($oTableRowHeader);

        return $oTable;
    }

    /* Creates and returns a table row for a test result table */
    function CreateTestTableRow($iCurrentId, $sLink)
    {
        $oVersion = new Version($this->iVersionId);
        $oApp  = new Application($oVersion->iAppId);
        $oSubmitter = new User($this->iSubmitterId);
        $oDistribution = new distribution($this->iDistributionId);
        $bgcolor = $this->sTestedRating;

        // initialize the array ech time we loop
        $oTableRowClick = null;

        $oTableRow = new TableRow();

        /* if the test we are displaying is this test then */
        /* mark it as the current test */
        if ($this->iTestingId == $iCurrentId)
        {
            $sTRClass = $bgcolor;

            $oTableCell = new TableCell("<b>Current</b>");
            $oTableCell->SetAlign("center");
        } else /* make all non-current rows clickable so clicking on them selects the test as current */
        {
            $sTRClass = $bgcolor;

            $oInactiveColor = new color();
            $oInactiveColor->SetColorByName($this->sTestedRating);

            $oHighlightColor = GetHighlightColorFromInactiveColor($oInactiveColor);

            $oTableRowHighlight = new TableRowHighlight($oHighlightColor, $oInactiveColor);

            $sUrl = $sLink.$this->iTestingId;

            $oTableRowClick = new TableRowClick($sUrl);
            $oTableRowClick->SetHighlight($oTableRowHighlight);

            // add the table element indicating that the user can show the row by clicking on it
            $oTableCell = new TableCell("Show");
            $oTableCell->SetCellLink($sUrl);
            $oTableCell->SetAlign("center");
        }

        $oTableRow->AddCell($oTableCell);
        $oTableRow->SetClass($sTRClass);

        $oTableRow->AddTextCell($oDistribution->objectMakeLink());
        $oTableRow->AddTextCell(date("M d Y", mysqldatetime_to_unixtimestamp($this->sTestedDate)));
        $oTableRow->AddTextCell($this->sTestedRelease.'&nbsp');
        $oTableRow->AddTextCell($this->sInstalls.'&nbsp');
        $oTableRow->AddTextCell($this->sRuns.'&nbsp');
        $oTableRow->AddTextCell($this->sTestedRating.'&nbsp');
        $oTableRow->AddTextCell($oSubmitter->objectMakeLink().'&nbsp');
        if ($this->iTestingId && $_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            $oObject = new objectManager("testData");
            $oTableRow->AddTextCell('<a href="'.$oObject->makeUrl("edit", $this->iTestingId,
                                    "Edit Test Results").'">'.
                                    'Edit</a> &nbsp; ',"\n".
                                    '<a href="objectManager.php?sClass=testData&bIsQueue=false&sAction='.
                                    'delete&iId='.$this->iTestingId.'&sTitle=Delete+Test+Results'.
                                    '">Delete</a></td>',"\n");
        }

        // if this is a clickable row, set the appropriate property
        if($oTableRowClick)
            $oTableRow->SetRowClick($oTableRowClick);

        return $oTableRow;
    }

    // Show the Test results for a application version
    function ShowVersionsTestingTable($sLink, $iDisplayLimit)
    {
        global $aClean;

        /* escape input parameters */
        $sLink = query_escape_string($sLink);
        $iDisplayLimit = query_escape_string($iDisplayLimit);

        $bShowAll = ($aClean['bShowAll'] == "true") ? true : false;

        $sQuery = "SELECT * 
                   FROM testResults, ?.versions
                   WHERE versionId = '?'
                   AND
                   versions.value = testResults.testedRelease
                   AND
                   versions.product_id = '?'
                   AND
                   state = '?'
                   ORDER BY versions.id DESC,testedDate DESC";
	
        if(!$bShowAll)
            $sQuery.=" LIMIT 0,".$iDisplayLimit;

        $hResult = query_parameters($sQuery, BUGZILLA_DB, $this->iVersionId, BUGZILLA_PRODUCT_ID, 'accepted');
        if(!$hResult)
            return;

        $rowsUsed = query_num_rows($hResult);

        if($rowsUsed == 0)
             return;

        $oTable = $this->CreateTestTable();

        $iIndex = 0;
        while($oRow = query_fetch_object($hResult))
        {
            $oTest = new testData($oRow->testingId);
            $oTableRow = $oTest->CreateTestTableRow($this->iTestingId, $sLink);
            // add the row to the table
            $oTable->AddRow($oTableRow);

            $iIndex++;
        }

        echo $oTable->GetString();

        echo '<br />',"\n"; // put a space after the test results table and the button

        echo '<form method=get action="objectManager.php">'."\n";

        if($rowsUsed >= $iDisplayLimit && $bShowAll)
        {
            $sShowButtonText = "Limit to $iDisplayLimit Tests";
        } else
        {
            $sShowButtonText = "Show All Tests";
            echo '<input type="hidden" name="bShowAll" value="true" />';
        }

        $oManager = new objectManager("version", null, $this->iVersionId);

        echo $oManager->makeUrlFormData();

        echo "\t".'<input class="button" type=submit value="'.$sShowButtonText.'" />'."\n";

        echo '</form>'."\n";

        echo '</div>',"\n"; // end of the 'info_contents' div
        echo '</div>',"\n"; // end of the 'info_container' div
    }

    /* retrieve the latest test result for a given version id */
    function getNewestTestIdFromVersionId($iVersionId, $sState = 'accepted')
    {
        $sQuery = "SELECT testingId FROM testResults, ?.versions WHERE
                versions.value = testResults.testedRelease
                AND
                versions.product_id = '?'
                AND
                versionId = '?'
                AND
                state = '?'
                     ORDER BY versions.id DESC,testedDate DESC limit 1";

        $hResult = query_parameters($sQuery, BUGZILLA_DB, BUGZILLA_PRODUCT_ID, $iVersionId, $sState);

        if(!$hResult)
            return 0;

        if(!$oRow = query_fetch_object($hResult))
            return 0;

        return $oRow->testingId;
    }

    // show the fields for editing
    function outputEditor()
    {
        global $aClean;

        /* Fill in some values */
        if(!$this->iVersionId)
            $this->iVersionId = $aClean['iVersionId'];
        if(!$this->sTestedDate)
            $this->sTestedDate = date('Y-m-d H:i:s');

        HtmlAreaLoaderScript(array("Test1", "Test2", "Test3"));

        $sName = version::fullName($this->iVersionId);

        echo html_frame_start("Test Form - $sName", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // What works
        echo '<tr valign=top><td class="color0"><b>What works</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test1" name="shWhatWorks">';
        echo $this->shWhatWorks.'</textarea></p></td></tr>',"\n";
        // What Does not work
        echo '<tr valign=top><td class=color1><b>What does not work</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test2" name="shWhatDoesnt">';
        echo $this->shWhatDoesnt.'</textarea></p></td></tr>',"\n";
        // What was not tested
        echo '<tr valign=top><td class=color0><b>What was not tested</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test3" name="shWhatNotTested">';
        echo $this->shWhatNotTested.'</textarea></p></td></tr>',"\n";
        // Date Tested
        echo '<tr valign=top><td class="color1"><b>Date tested </b></td>',"\n";
        echo '<td class="color0"><input type=text name="sTestedDate" value="'.$this->sTestedDate.'" size="20"></td></tr>',"\n";
        echo '<tr valign=top><td class="color1"></td><td class="color0"><p/>YYYY-MM-DD HH:MM:SS</td></tr>',"\n";

        // Distribution
        $oDistribution = new distribution($this->iDistributionId);
        $sDistributionHelp = "";
        if(!$this->iDistributionId || $oDistribution->objectGetState() != 'accepted')
        {
            if(!$this->iDistributionId)
            {
                $sDistributionHelp = "If yours is not on the list, ".
                                     "please add it using the form below.";
            } else
            {
                $sDistributionHelp = "The user added a new distribution; ";
                                     "review it in the form below or replace ".
                                     "it with one from the list.";
            }
            $sDistributionHelp .= "<br />\n";
        }

        echo '<tr valign=top><td class="color0"><b>Distribution</b></td class="color0">',"\n";

        echo '<td class=color0>',"\n";
        echo $sDistributionHelp;
        distribution::make_distribution_list("iDistributionId", $this->iDistributionId);
        echo '</td></tr>',"\n";

        // Version List
        echo '<tr><td class=color1><b>Tested release</b></td><td class=color0>',"\n";
        echo make_bugzilla_version_list("sTestedRelease", $this->sTestedRelease);
        // Give the user some information about our available versions
        echo "<ul>\n";
        echo "<li>If you are testing with a newer release than listed please";
        echo " check back tomorrow, sometimes it takes us a day to update the list.</li>\n";
        echo "<li>If you are testing with an older release than listed please";
        echo " upgrade and test with a newer release.</li>\n";
        echo "<li><b>NOTE: 'CVS' was recently removed as a version because we simply can't track";
        echo " exactly which CVS version was used.<br/> If you haven't tested with one of the";
        echo " listed versions please retest with a recent release and resubmit.</li>\n";
        echo "</ul>\n";
        echo '</td></tr>',"\n";

        // Installs
        echo '<tr><td class=color0><b>Installs?</b></td><td class=color0>',"\n";
        testData::make_Installs_list("sInstalls", $this->sInstalls);
        echo '</td></tr>',"\n";
        // Runs
        echo '<tr><td class=color1><b>Runs?</b></td><td class=color0>',"\n";
        testData::make_Runs_list("sRuns", $this->sRuns);
        echo '</td></tr>',"\n";
        // Rating
        echo '<tr><td class="color0"><b>Rating</b></td><td class="color0">',"\n";
        echo make_maintainer_rating_list("sTestedRating", $this->sTestedRating);
        echo '<a href="'.BASE.'/help/?sTopic=maintainer_ratings" target="_blank">Rating definitions</a></td></tr>',"\n";
        // extra comments
        echo '<tr valign=top><td class="color1"><b>Extra comments</b></td>',"\n";
        echo '<td class="color0"><textarea name="sComments" rows=10 cols=65>';
        echo $this->sComments.'</textarea></td></tr>',"\n";

        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" >';
        echo '<input type="hidden" name="iTestingId" value="'.$this->iTestingId.'" >';
        echo '<input type="hidden" name="iTestDataId" value="'.$this->iTestingId.'" >';

        echo "</table>\n";

        echo html_frame_end();
    }

    /* $aValues can be $aValues or any array with the values from outputEditor() */
    function CheckOutputEditorInput($aValues, $sDistribution="")
    {
        $errors = "";
        if (empty($aValues['shWhatWorks']))
            $errors .= "<li>Please enter what worked.</li>\n";

        if (empty($aValues['shWhatDoesnt']))
            $errors .= "<li>Please enter what did not work.</li>\n";

        if (empty($aValues['shWhatNotTested']))
            $errors .= "<li>Please enter what was not tested.</li>\n";

        if (empty($aValues['sTestedDate']))
            $errors .= "<li>Please enter the date and time when you tested.</li>\n";

        if (empty($aValues['sTestedRelease']))
            $errors .= "<li>Please enter the version of Wine that you tested with.</li>\n";

        // No Distribution entered, and nothing in the list is selected
        if (empty($aValues['sDistribution']) && !$aValues['iDistributionId'])
            $errors .= "<li>Please enter a distribution.</li>\n";

        if (empty($aValues['sInstalls']))
            $errors .= "<li>Please enter whether this application installs or not.</li>\n";

        if (empty($aValues['sRuns']))
            $errors .= "<li>Please enter whether this application runs or not.</li>\n";

        if (empty($aValues['sTestedRating']))
            $errors .= "<li>Please enter a rating based on how well this application runs.</li>\n";

        // Basic checking of rating logic to ensure that the users test results
        // are consistent
        if (($aValues['sRuns'] != "Yes") && ($aValues['sTestedRating'] != GARBAGE_RATING))
            $errors .= "<li>Applications that do not run should be rated &#8216;Garbage&#8217;.</li>\n";

        if (($aValues['sInstalls'] == "No") && ($aValues['sTestedRating'] == PLATINUM_RATING))
            $errors .= "<li>An application can only get a Platinum rating if it installs and runs &#8216;out of the box&#8217;.</li>\n";

        // Basic checking of logic.  Runs? can obviously only be 'Not Installable'
        // if the application does not install
        if (($aValues['sInstalls'] != "No") && ($aValues['sRuns'] == "Not Installable"))
            $errors .= "<li>You can only set Runs? to &#8216;Not Installable&#8217; if the applicatino&#8217;s installer does not work</li>\n";

        return $errors;

    }

    /* retrieves values from $aValues that were output by outputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from outputEditor() */
    function GetOutputEditorValues($aValues)
    {
        if($aValues['iTestingId'])
            $this->iTestingId = $aValues['iTestingId'];

        if($aValues['iVersionId'])
            $this->iVersionId = $aValues['iVersionId'];

        $this->shWhatWorks = $aValues['shWhatWorks'];
        $this->shWhatDoesnt = $aValues['shWhatDoesnt'];
        $this->shWhatNotTested = $aValues['shWhatNotTested'];
        $this->sTestedDate = $aValues['sTestedDate'];
        $this->iDistributionId = $aValues['iDistributionId'];
        $this->sTestedRelease = $aValues['sTestedRelease'];
        $this->sInstalls = $aValues['sInstalls'];
        $this->sRuns = $aValues['sRuns'];
        $this->sTestedRating = $aValues['sTestedRating'];
        $this->sComments = $aValues['sComments'];
    }

    function make_Installs_list($sVarname, $sSelectedValue)
    {
        echo "<select name='$sVarname'>\n";
        echo "<option value=\"\">Choose ...</option>\n";
        $aRating = array("Yes", "No", "N/A");
        $iMax = count($aRating);

        for($i=0; $i < $iMax; $i++)
        {
            if($aRating[$i] == $sSelectedValue)
                echo "<option value='".$aRating[$i]."' selected>".$aRating[$i]."\n";
            else
                echo "<option value='".$aRating[$i]."'>".$aRating[$i]."\n";
        }
        echo "</select>\n";
    }

    function make_Runs_list($sVarname, $sSelectedValue)
    {
        echo "<select name='$sVarname'>\n";
        echo "<option value=\"\">Choose ...</option>\n";
        $aRating = array("Yes", "No", "Not installable");
        $iMax = count($aRating);

        for($i=0; $i < $iMax; $i++)
        {
            if($aRating[$i] == $sSelectedValue)
                echo "<option value='".$aRating[$i]."' selected>".$aRating[$i]."\n";
            else
                echo "<option value='".$aRating[$i]."'>".$aRating[$i]."\n";
        }
        echo "</select>\n";
    }

    /* List test data submitted by a given user.  Ignore test results for queued applications/versions */
    function listSubmittedBy($iUserId, $bQueued = true)
    {
        $hResult = query_parameters("SELECT testResults.versionId, testResults.testedDate, testResults.testedRelease, testResults.testedRating, testResults.submitTime, appFamily.appName, appVersion.versionName from testResults, appFamily, appVersion WHERE testResults.versionId = appVersion.versionId AND appVersion.appId = appFamily.appId  AND testResults.submitterId = '?' AND testResults.state = '?' ORDER BY testResults.testingId", $iUserId, $bQueued ? 'queued' : 'accepted');

        if(!$hResult || !query_num_rows($hResult))
            return false;

        $sReturn = html_table_begin("width=\"100%\" align=\"center\"");
        $sReturn .= html_tr(array(
            "Version",
            "Rating",
            "Wine version",
            "Submission Date"),
            "color4");

        for($i = 1; $oRow = query_fetch_object($hResult); $i++)
            $sReturn .= html_tr(array(
                version::fullNameLink($oRow->versionId),
                $oRow->testedRating,
                $oRow->testedRelease,
                print_date(mysqldatetime_to_unixtimestamp($oRow->submitTime))),
                $oRow->testedRating);

        $sReturn .= html_table_end();

        return $sReturn;
    }

    // return the number of test data entries for a particular version id
    function get_testdata_count_for_versionid($iVersionId)
    {
        $sQuery = "SELECT count(*) as cnt
                   FROM testResults
                   WHERE versionId = '?'
                   AND
                   state = '?';";

        $hResult = query_parameters($sQuery, $iVersionId, 'accepted');

        $oRow = query_fetch_object($hResult);
        return $oRow->cnt;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        $oTest = new testData();
        $sState = objectManager::getStateString($bQueued, $bRejected);
        if($bQueued && !$oTest->canEdit())
        {
            if($bRejected)
            {
                $sQuery = "SELECT COUNT(testingId) AS count FROM
                        testResults WHERE
                        testResults.submitterId = '?'
                        AND
                        testResults.state = '?'";
            } else
            {
                $sQuery = "SELECT COUNT(testingId) AS count FROM
                        testResults, appVersion, appMaintainers WHERE
                            testResults.versionId = appVersion.versionId
                            AND
                            appMaintainers.userId = '?'
                            AND
                            appMaintainers.queued = 'false'
                            AND
                            (
                                (
                                    appMaintainers.superMaintainer = '1'
                                    AND
                                    appMaintainers.appId = appVersion.appid
                                )
                                OR
                                (
                                    appMaintainers.superMaintainer = '0'
                                    AND
                                    appMaintainers.versionId = appVersion.versionId
                                )
                            )
                            AND
                            testResults.state = '?'";
            }

            $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                        $sState);
        } else
        {
            $sQuery = "SELECT COUNT(testingId) as count FROM testResults WHERE
                    testResults.state = '?'";
            $hResult = query_parameters($sQuery, $sState);
        }

        if(!$hResult)
            return FALSE;

        if(!$oRow = query_fetch_object($hResult))
            return FALSE;

        return $oRow->count;
    }

    function objectGetEntries($bQueued, $bRejected, $iRows = 0, $iStart = 0, $sOrderBy = "testingId")
    {
        $oTest = new testData();
        $sState = objectManager::getStateString($bQueued, $bRejected);

        $sLimit = "";

        /* Should we add a limit clause to the query? */
        if($iRows || $iStart)
        {
            $sLimit = " LIMIT ?,?";

            /* Selecting 0 rows makes no sense, so we assume the user wants to select all of them
               after an offset given by iStart */
            if(!$iRows)
                $iRows = testData::objectGetEntriesCount($bQueued, $bRejected);
        }

        if($bQueued && !$oTest->canEdit())
        {
            if($bRejected)
            {
                $sQuery = "SELECT testResults.* FROM testResults WHERE
                        testResults.submitterId = '?'
                        AND
                        testResults.state = '?' ORDER BY ?$sLimit";
            } else
            {
                $sQuery = "SELECT testResults.* FROM testResults, appVersion,
                            appMaintainers WHERE
                            testResults.versionId = appVersion.versionId
                            AND
                            appMaintainers.userId = '?'
                            AND
                            (
                                (
                                    appMaintainers.superMaintainer = '1'
                                    AND
                                    appMaintainers.appId = appVersion.appid
                                )
                                OR
                                (
                                    appMaintainers.superMaintainer = '0'
                                    AND
                                    appMaintainers.versionId = appVersion.versionId
                                )
                            )
                            AND
                            appMaintainers.queued = 'false'
                            AND
                            testResults.state = '?' ORDER BY ?$sLimit";
            }
            if($sLimit)
            {
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                            $sState, $sOrderBy, $iStart, $iRows);
            } else
            {
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                            $sState, $sOrderBy);
            }
        } else
        {
            $sQuery = "SELECT testResults.* FROM testResults WHERE
                    testResults.state = '?' ORDER by ?$sLimit";
            if($sLimit)
                $hResult = query_parameters($sQuery, $sState, $sOrderBy, $iStart, $iRows);
            else
                $hResult = query_parameters($sQuery, $sState, $sOrderBy);
        }

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectGetHeader()
    {
        $oTableRow = new TableRow();
        $oTableRow->AddTextCell("Submission Date");
        $oTableRow->AddTextCell("Submitter");
        $oTableRow->AddTextCell("Application");
        $oTableRow->AddTextCell("Version");
        $oTableRow->AddTextCell("Release");
        $oTableRow->AddTextCell("Has maintainer");
        $oTableRow->AddTextCell("Rating");
        return $oTableRow;
    }

    function objectGetTableRow()
    {
        $oVersion = new version($this->iVersionId);
        $oApp = new application($oVersion->iAppId);
        $oUser = new user($this->iSubmitterId);

        $hMaintainers = maintainer::getMaintainersForAppIdVersionId(null, $this->iVersionId);
        $bHasMaintainer = (query_num_rows($hMaintainers) == 0) ? false : true;

        $oTableRow = new TableRow();
        $oTableRow->AddCell(new TableCell(print_date(mysqldatetime_to_unixtimestamp($this->sSubmitTime))));
        $oTableRow->AddCell(new TableCell($oUser->objectMakeLink()));
        $oTableRow->AddCell(new TableCell($oApp->objectMakeLink()));
        $oTableRow->AddCell(new TableCell($oVersion->objectMakeLink()));
        $oTableRow->AddCell(new TableCell($this->sTestedRelease));
        $oTableRow->AddCell(new TableCell($bHasMaintainer ? "YES" : "no"));
        $oTableRow->AddCell(new TableCell($this->sTestedRating));

        $oTableRow->SetClass($this->sTestedRating);

        $oOMTableRow = new OMTableRow($oTableRow);
        $oOMTableRow->SetHasDeleteLink(true);

        return $oOMTableRow;
    }

    public function objectGetState()
    {
        return $this->sState;
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;
        else if($this->iVersionId)
        {
            if($this->iSubmitterId == $_SESSION['current']->iUserId &&
               $this->sState == 'rejected')
                return TRUE;

            $oVersion = new version($this->iVersionId);
            if($_SESSION['current']->hasAppVersionModifyPermission($oVersion))
                return TRUE;
            else
                return FALSE;
        } else
            return FALSE;
    }

    function objectDisplayQueueProcessingHelp()
    {
        echo "<p>This is the list of test results waiting for submission, ".
             "rejection or deletion.</p>\n";
        echo "<p>To view a submission, click on its name. From that page ".
             "you can submit it into the AppDB, reject it or delete it.</p>\n";
    }

    function objectShowPreview()
    {
        return TRUE;
    }

    function display()
    {
        $this->ShowTestResult();
        $this->iSubmitterId = $_SESSION['current']->iUserId;

        $oTable = $this->CreateTestTable();

        $oTable->AddRow($this->CreateTestTableRow($this->iTestingId, ""));

        echo $oTable->GetString();
    }


    function objectMakeUrl()
    {
        $oObject = new objectManager("testData", "Edit Test Results", $this->iTestingId);
        return $oObject->makeUrl("edit", $this->iTestingId);
    }

    function objectMakeLink()
    {
        /* STUB */
        return TRUE;
    }

    function objectGetChildren($bIncludeDeleted = false)
    {
        /* We have none */
        return array();
    }

    function objectDisplayAddItemHelp()
    {
        echo "<p>This is the screen for inputing test information so that others ";
        echo "looking at the database will know \n";
        echo "what was working on a particular release of Wine.</p>\n";
        echo "<p><b>Please DO NOT include crash or Wine debug output.\n";
        echo " Instead report the crash as a bug in the Wine bugzilla at \n";
        echo "<a href=\"http://bugs.winehq.org\">http://bugs.winehq.org</a>.\n";
        echo "We ask that you use bugzilla because developers do not monitor the AppDB \n";
        echo "for bugs.</b></p>\n"; 
        echo "<p>Please be as detailed as you can but do not paste large \n";
        echo "chunks of output from the terminal. Type out your report \n";
        echo "clearly and in proper English so that it is easily readable.</p>\n";
        echo "<p>If you cannot find your distribution in the list of existing ";
        echo "distributions, please add it in the \n";
        echo "provided field.</p>\n\n";
    }

    function mustBeQueued()
    {
        if($_SESSION['current']->hasPriv("admin"))
        {
            return FALSE;
        } else if($this->iVersionId)
        {
            // if the user can edit the version and the version isn't queued then
            // they can also submit test results without them being queued
            // this is the case where they maintain the version and the version isn't queued
            $oVersion = new version($this->iVersionId);
            if($oVersion->canEdit() && $oVersion->objectGetState() == 'accepted')
                return FALSE;
            else
                return TRUE;
        } else
        {
            return TRUE;
        }
    }

    function allowAnonymousSubmissions()
    {
        return FALSE;
    }

    function objectAllowPurgingRejected()
    {
        return TRUE;
    }

    public function objectGetSubmitTime()
    {
        return mysqltimestamp_to_unixtimestamp($this->sSubmitTime);
    }

    function objectGetItemsPerPage($sState = 'accepted')
    {
        $aItemsPerPage = array(25, 50, 100, 200);
        $iDefaultPerPage = 25;
        return array($aItemsPerPage, $iDefaultPerPage);
    }

    function objectGetId()
    {
        return $this->iTestingId;
    }

    function objectGetSubmitterId()
    {
        return $this->iSubmitterId;
    }
}

?>
