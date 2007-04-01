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
    var $sQueued;

     // constructor, fetches the data.
    function testData($iTestingId = null, $oRow = null)
    {
        // we are working on an existing test
        if($iTestingId)
        {
            // We fetch the data related to this test.
            if(!$oRow)
            {
                $sQuery = "SELECT *
                           FROM testResults
                           WHERE testingId = '?'";
                if($hResult = query_parameters($sQuery, $iTestingId))
                    $oRow = mysql_fetch_object($hResult);
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
                $this->sQueued = $oRow->queued;
            }
        }
    }

    // Creates a new Test Results.
    function create()
    {
        // Security, if we are not an administrator or a maintainer the test result must be queued.
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
            $this->sQueued = 'true';
        else
            $this->sQueued = 'false';


        $hResult = query_parameters("INSERT INTO testResults (versionId, whatWorks, whatDoesnt,".
                                    "whatNotTested, testedDate, distributionId, testedRelease,".
                                    "installs, runs, testedRating, comments, submitterId, queued)".
                                    " VALUES('?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?',".
                                    "'?', '?')",
                                    $this->iVersionId, $this->shWhatWorks, $this->shWhatDoesnt,
                                    $this->shWhatNotTested, $this->sTestedDate, $this->iDistributionId,
                                    $this->sTestedRelease, $this->sInstalls, $this->sRuns,
                                    $this->sTestedRating, $this->sComments, $_SESSION['current']->iUserId,
                                    $this->sQueued);
        if($hResult)
        {
            $this->iTestingId = mysql_insert_id();
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
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && !($this->sQueued == 'false')))
        {
            return;
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
                                        comments        = '?'
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
                            $this->iTestingId))
        {
            if(!$bSilent)
                $this->SendNotificationMail();
            return true;
        }
        else
        {
            addmsg("Error while updating test results", "red");
            return false;
        }
    }
    
    // Delete test results.
    function delete($bSilent=false)
    {
        // is the current user allowed to delete this test result? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && !($this->sQueued == 'false')))
        {
            return;
        }
        // now delete the test data 
        $sQuery = "DELETE FROM testResults
                   WHERE testingId = '?' 
                   LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iTestingId)))
        {
            addmsg("Error removing the deleted test data!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        if($this->iSubmitterId && ($this->iSubmitterId != $_SESSION['current']->iUserId))
            $this->mailSubmitter("delete");

        return TRUE;
    }


    // Move Test Data out of the queue.
    function unQueue()
    {
        // is the current user allowed to delete this test data? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") &&
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            return;
        }

        // If we are not in the queue, we can't move the test data out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE testResults SET queued = '?' WHERE testingId = '?'",
                            "false", $this->iTestingId))
        {
            $this->sQueued = 'false';
            // we send an e-mail to interested people
            $this->mailSubmitter("add");
            $this->SendNotificationMail();
        }
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
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE testResults SET queued = '?' WHERE testingId = '?'", 
                            "rejected", $this->iTestingId))
        {
            $this->sQueued = 'rejected';
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

        if(query_parameters("UPDATE testResults SET queued = '?' WHERE testingId = '?'",
                            "true", $this->iTestingId))
        {
            $this->sQueued = 'true';
            // we send an e-mail to interested people
            $this->SendNotificationMail();
        }
    }

    function mailSubmitter($sAction="add")
    {
        global $aClean;

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);

            /* Get the full app/version name to display */
            $sName = version::fullName($this->iVersionId);

            switch($sAction)
            {
            case "add":
                $sSubject =  "Submitted testing data accepted";
                $sMsg  = "The testing data you submitted for '$sName' has been accepted by ".$_SESSION['current']->sRealname.".";
                $sMsg .= APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject =  "Submitted testing data rejected";
                $sMsg  = "The testing data you submitted for '$sName' has been rejected by ".$_SESSION['current']->sRealname.".";
                $sMsg .= APPDB_ROOT."testResults.php?sSub=view&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Reason given:\n";
            break;
            case "delete":
                $sSubject =  "Submitted testing data deleted";
                $sMsg  = "The testing data you submitted for '$sName' has been deleted by ".$_SESSION['current']->sRealname.".";
                $sMsg .= "Reason given:\n";
            break;
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";

            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        global $aClean;

        $oVersion = new Version($this->iVersionId);
        $oApp = new Application($oVersion->iAppId);
        $sBacklink = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";

        switch($sAction)
        {
            case "add":
                if($this->sQueued == "false")
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
            case "delete":
                $sSubject = "Test Results deleted for version ".$oVersion->sName." of ".$oApp->sName." by ".$_SESSION['current']->sRealname;
                // if replyText is set we should report the reason the data was deleted 
                if($aClean['sReplyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['sReplyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("test data deleted.", "green");
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
        echo $this->shWhatWorks;
        echo '<p><b>What does not</b><br />',"\n";
        echo $this->shWhatDoesnt;
        echo '<p><b>What was not tested</b><br />',"\n";
        echo $this->shWhatNotTested;
        echo '<p><b>Additional Comments</b><br />',"\n";
        echo $this->sComments;
    }

    // Show the Test results for a application version
    function ShowVersionsTestingTable($link, $iDisplayLimit)
    {
        global $aClean;

        /* escape input parameters */
        $link = mysql_real_escape_string($link);
        $iDisplayLimit = mysql_real_escape_string($iDisplayLimit);

        $showAll = $aClean['showAll'];

        $sQuery = "SELECT * 
                   FROM testResults
                   WHERE versionId = '?'
                   AND
                   queued = '?'
                   ORDER BY testedDate DESC";
	
        if(!$showAll)
            $sQuery.=" LIMIT 0,".$iDisplayLimit;

        $hResult = query_parameters($sQuery, $this->iVersionId, "false");
        if(!$hResult)
            return;

        $rowsUsed = mysql_num_rows($hResult);

        if($rowsUsed == 0)
             return;
        echo '<p><span class="title">Test Results</span><br />',"\n";
        echo '<table width="100%" border="1" class="historyTable">',"\n";
        echo '<thead class="historyHeader">',"\n";
        echo '<tr>',"\n";
        echo '<td></td>',"\n";
        echo '<td>Distribution</td>',"\n";
        echo '<td>Test date</td>',"\n";
        echo '<td>Wine version</td>',"\n";
        echo '<td>Installs?</td>',"\n";
        echo '<td>Runs?</td>',"\n";
        echo '<td>Rating</td>',"\n";
        echo '<td>Submitter</td>',"\n";
        echo '</tr></thead>',"\n";
        while($oRow = mysql_fetch_object($hResult))
        {
            $oTest = new testData($oRow->testingId);
            $oVersion = new Version($oTest->iVersionId);
            $oApp  = new Application($oVersion->iAppId);
            $oSubmitter = new User($oTest->iSubmitterId);
            $oDistribution = new distribution($oTest->iDistributionId);
            $bgcolor = $oTest->sTestedRating;

            /* if the test we are displaying is this test then */
            /* mark it as the current test */
            if ($oTest->iTestingId == $this->iTestingId)
            {
                echo '<tr class='.$bgcolor.'>',"\n";
                echo '    <td align="center" class="color2"><b>Current</b></td>',"\n";
            } else /* make all non-current rows clickable so clicking on them selects the test as current */
            {
                html_tr_highlight_clickable($link.$oTest->iTestingId, $bgcolor, "", "color2", "underline"); 
                echo '    <td align="center" class="color2">[<a href="'.$link.$oTest->iTestingId;

                if(is_string($showAll))
                    echo '&showAll='.$showAll.'">Show</a>]</td>',"\n";
                else
                    echo '">Show</a>]</td>',"\n";
            }

            echo '    <td>',"\n";
            echo $oDistribution->objectMakeLink()."\n";
            echo '    </td>',"\n";
            echo '    <td>'.date("M d Y", mysqldatetime_to_unixtimestamp($oTest->sTestedDate)).'</td>',"\n";
            echo '    <td>'.$oTest->sTestedRelease.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sInstalls.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sRuns.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sTestedRating.'&nbsp</td>',"\n";
            echo '    <td>'.$oSubmitter->objectMakeLink().'&nbsp</td>',"\n";
            if ($_SESSION['current']->hasAppVersionModifyPermission($oVersion))
            {
                $oObject = new objectManager("testData");
                echo '<td><a href="'.$oObject->makeUrl("edit", $oTest->iTestingId,
                        "Edit Test Results").'">',"\n";
                echo 'Edit</a></td>',"\n";
            }
            echo '</tr>',"\n";
        }

        echo '</table>',"\n";

        echo '<form method=get action="'.$PHP_SELF.'">';
        echo '<input name="iVersionId" type=hidden value="',$this->iVersionId,'" />';
        if($rowsUsed >= $iDisplayLimit && !is_string($showAll))
            echo '<input class="button" name="showAll" type=submit value="Show All Tests" />';

        if(is_string($showAll))
        {
            echo '<input class="button" name="hideAll" type=submit value="Limit to '.$iDisplayLimit.' Tests" />';
        }
        echo '</form>';
    }

    /* retrieve the latest test result for a given version id */
    function getNewestTestIdFromVersionId($iVersionId)
    {
        $sQuery = "SELECT testingId FROM testResults WHERE
                versionId = '?'
                AND
                queued = 'false'
                     ORDER BY testedDate DESC limit 1";
        $hResult = query_parameters($sQuery, $iVersionId);
        if(!$hResult)
            return 0;

        $oRow = mysql_fetch_object($hResult);
        return $oRow->testingId;
    }

    // show the fields for editing
    function outputEditor($sDistribution="", $bNewDist=false)
    {
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
        echo '<tr valign=top><td class="color0"><b>Distribution</b></td class="color0">',"\n";
        if ($bNewDist)
        {
            echo '<td class="color0"><input type=text name="sDistribution" value="'.$sDistribution.'" size="20"></td></tr>',"\n";
            echo '<tr><td class=color0><b></b></td>',"\n";
        }
        echo '<td class=color0>',"\n";
        distribution::make_distribution_list("iDistributionId", $this->iDistributionId);
        echo '</td></tr>',"\n";
        // Version List
        echo '<tr><td class=color1><b>Tested release</b></td><td class=color0>',"\n";
        make_bugzilla_version_list("sTestedRelease", $this->sTestedRelease);
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
        make_maintainer_rating_list("sTestedRating", $this->sTestedRating);
        echo '<a href="'.BASE.'/help/?sTopic=maintainer_ratings" target="_blank">Rating definitions</a></td></tr>',"\n";
        // extra comments
        echo '<tr valign=top><td class="color1"><b>Extra comments</b></td>',"\n";
        echo '<td class="color0"><textarea name="sComments" rows=10 cols=35>';
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
        $this->iTestingId = $aValues['iTestingId'];
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
        $aRating = array("Yes", "No", "Not Installable");
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
        $hResult = query_parameters("SELECT testResults.versionId, testResults.testedDate, testResults.testedRelease, testResults.testedRating, testResults.submitTime, appFamily.appName, appVersion.versionName from testResults, appFamily, appVersion WHERE testResults.versionId = appVersion.versionId AND appVersion.appId = appFamily.appId AND (appFamily.queued = '?' OR appVersion.queued = '?') AND testResults.submitterId = '?' AND testResults.queued = '?' ORDER BY testResults.testingId", "false", "false", $iUserId, $bQueued ? "true" : "false");

        if(!$hResult || !mysql_num_rows($hResult))
            return false;

        $sReturn = html_table_begin("width=\"100%\" align=\"center\"");
        $sReturn .= html_tr(array(
            "Version",
            "Rating",
            "Wine version",
            "Submission Date"),
            "color4");

        for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
            $sReturn .= html_tr(array(
                "<a href=\"".BASE."appview.php?iVersionId=$oRow->versionId\">$oRow->appName: $oRow->versionName</a>",
                $oRow->testedRating,
                $oRow->testedRelease,
                print_date(mysqltimestamp_to_unixtimestamp($oRow->submitTime))),
                $oRow->testedRating);

        $sReturn .= html_table_end();

        return $sReturn;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        $oTest = new testData();
        $sQueued = objectManager::getQueueString($bQueued, $bRejected);
        if($bQueued && !$oTest->canEdit())
        {
            if($oTest->canEditSome())
            {
                $sQuery = "SELECT COUNT(testingId) AS count FROM
                        testResults, appVersion, appMaintainers WHERE
                            testResults.versionId = appVersion.versionId
                            AND
                            appVersion.queued = 'false'
                            AND
                            appMaintainers.userId = '?'
                            AND
                            (
                            appMaintainers.appId = appVersion.appid
                            OR
                            appMaintainers.versionId = appVersion.versionId
                            )
                            AND
                            testResults.queued = '?'";
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                            $sQueued);
            }
        } else
        {
            $sQuery = "SELECT COUNT(testingId) as count FROM testResults,
                    appVersion WHERE
                    appVersion.versionId = testResults.versionId
                    AND
                    appVersion.queued = 'false'
                    AND
                    testResults.queued = '?'";
            $hResult = query_parameters($sQuery, $sQueued);
        }

        if(!$hResult)
            return FALSE;

        if(!$oRow = mysql_fetch_object($hResult))
            return FALSE;

        return $oRow->count;
    }

    function objectGetEntries($bQueued, $bRejected)
    {
        $oTest = new testData();
        $sQueued = objectManager::getQueueString($bQueued, $bRejected);
        if($bQueued && !$oTest->canEdit())
        {
            if($oTest->canEditSome())
            {
                $sQuery = "SELECT testResults.* FROM testResults, appVersion,
                            appMaintainers WHERE
                            testResults.versionId = appVersion.versionId
                            AND
                            appMaintainers.userId = '?'
                            AND
                            appVersion.queued = 'false'
                            AND
                            (
                            appMaintainers.appId = appVersion.appid
                            OR
                            appMaintainers.versionId = appVersion.versionId
                            )
                            AND
                            testResults.queued = '?'";
                $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                            $sQueued);
            }
        } else
        {
            $sQuery = "SELECT testResults.* FROM testResults, appVersion WHERE
                    testResults.versionId = appVersion.versionId
                    AND
                    appVersion.queued = 'false'
                    AND
                    testResults.queued = '?' ORDER by testingId";
            $hResult = query_parameters($sQuery, $sQueued);
        }

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectGetHeader()
    {
        $aCells = array(
                "Submission Date",
                "Submitter",
                "Application",
                "Version",
                "Release",
                "Rating");
        return $aCells;
    }

    function objectGetInstanceFromRow($oRow)
    {
        return new testData($oRow->testingId, $oRow);
    }

    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        $oVersion = new version($this->iVersionId);
        $oApp = new application($oVersion->iAppId);
        $oUser = new user($this->iSubmitterId);
        $aCells = array(
                print_date(mysqltimestamp_to_unixtimestamp($this->sSubmitTime)),
                $oUser->objectMakeLink(),
                $oApp->objectMakeLink(),
                $oVersion->objectMakeLink(),
                $this->sTestedRelease,
                $this->sTestedRating);

        if($this->canEditSome())
            $aCells[] = "[ <a href=\"".$oObject->makeUrl("edit",
                $this->iTestingId)."\">$sEditLinkLabel</a> ]";

        echo html_tr($aCells, $this->sTestedRating);
    }

    function canEditSome()
    {
        if($this->canEdit() || maintainer::isUserMaintainer($_SESSION['current']))
            return TRUE;
        else
            return FALSE;
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
            return TRUE;
        else if($this->iTestingId)
        {
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

    function display()
    {
        /* STUB */
        return TRUE;
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

    function objectDisplayAddItemHelp()
    {
        echo "<p>This is the screen for inputing test information so that others ";
        echo "looking at the database will know \n";
        echo "what was working on a particular release of Wine.</p>\n";
        echo "<p>Please be as detailed as you can.</p>\n";
        echo "<p>If you cannot find your distribution in the list of existing ";
        echo "distributions, please add it in the \n";
        echo "provided field.</p>\n\n";
    }
}

?>
