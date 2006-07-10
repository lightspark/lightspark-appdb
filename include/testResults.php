<?php
/*****************************************/
/* this class represents Testing results */
/*****************************************/
require_once(BASE."include/distributions.php");
require_once(BASE."include/util.php");
// Class for handling Testing History.

class testData{
    var $iTestingId;
    var $iVersionId;
    var $sWhatWorks;
    var $sWhatDoesnt;
    var $sWhatNotTested;
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
    function testData($iTestingId = null)
    {
        // we are working on an existing test
        if(is_numeric($iTestingId))
        {
            // We fetch the data related to this test.
            if(!$this->iTestingId)
            {
                $sQuery = "SELECT *
                           FROM testResults
                           WHERE testingId = '?'";
                if($hResult = query_parameters($sQuery, $iTestingId))
                {
                    $oRow = mysql_fetch_object($hResult);
                    $this->iTestingId = $oRow->testingId;
                    $this->iVersionId = $oRow->versionId;
                    $this->sWhatWorks = $oRow->whatWorks;
                    $this->sWhatDoesnt = $oRow->whatDoesnt;
                    $this->sWhatNotTested = $oRow->whatNotTested;
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
    }

    // Creates a new Test Results.
    function create()
    {
        // Security, if we are not an administrator or an maintainer the test result must be queued.
        $oVersion = new Version($oTest->iVersionId);
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
                                    $this->iVersionId, $this->sWhatWorks, $this->sWhatDoesnt,
                                    $this->sWhatNotTested, $this->sTestedDate, $this->iDistributionId,
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
        // is the current user allowed to update this testing result? 
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
                            $this->sWhatWorks,
                            $this->sWhatDoesnt,
                            $this->sWhatNotTested,
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
    
    // Delete testing results.
    function delete($bSilent=false)
    {
        // is the current user allowed to delete this testing result? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") && 
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion) &&
           !(($_SESSION['current']->iUserId == $this->iSubmitterId) && !($this->sQueued == 'false')))
        {
            return;
        }
        // now delete the testing data 
        $sQuery = "DELETE FROM testResults
                   WHERE testingId = '?' 
                   LIMIT 1";
        if(!($hResult = query_parameters($sQuery, $this->iTestingId)))
        {
            addmsg("Error removing the deleted testing data!", "red");
        }

        if(!$bSilent)
            $this->SendNotificationMail("delete");

        $this->mailSubmitter("delete");
    }


    // Move Testing Data out of the queue.
    function unQueue()
    {
        // is the current user allowed to delete this testing data? 
        $oVersion = new Version($this->iVersionId);
        if(!$_SESSION['current']->hasPriv("admin") &&
           !$_SESSION['current']->hasAppVersionModifyPermission($oVersion))
        {
            return;
        }

        // If we are not in the queue, we can't move the testing data out of the queue.
        if(!$this->sQueued == 'true')
            return false;

        if(query_parameters("UPDATE testResults SET queued = '?' WHERE testingId = '?'",
                            "false", $this->iTestingId))
        {
            $this->sQueued = 'false';
            // we send an e-mail to intersted people
            $this->mailSubmitter("unQueue");
            $this->SendNotificationMail();
        }
    }

    function Reject()
    {
        // is the current user allowed to delete this testing data? 
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
            // we send an e-mail to intersted people
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
            // we send an e-mail to intersted people
            $this->SendNotificationMail();
        }
    }

    function mailSubmitter($sAction="add")
    {

        $aClean = array(); //array of filtered user input

        $aClean = makeSafe($_REQUEST['replyText']);

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            switch($sAction)
            {
            case "add":
                $sSubject =  "Submitted testing data accepted";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been accepted.";
                $sMsg .= APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject =  "Submitted testing data rejected";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been rejected.";
                $sMsg .= APPDB_ROOT."testResults.php?sSub=view&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Reason given:\n";
            break;
            case "delete":
                $sSubject =  "Submitted testing data deleted";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been deleted.";
                $sMsg .= "Reason given:\n";
            break;
            }
            $sMsg .= $aClean['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
        
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        $aClean = array(); //array of filtered user input

        $aClean['replyText'] = makeSafe($_REQUEST['replyText']);

        $oVersion = new Version($this->iVersionId);
        $oApp = new Application($oVersion->iAppId);
        $sBacklink = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";

        switch($sAction)
        {
            case "add":
                if($this->sQueued == "false")
                {
                    $sSubject = "Test Results added to version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg  .= $sBacklink;
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This Testing data has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                    }
                    if($aClean['replyText'])
                    {
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                    }
                    addmsg("The testing data was successfully added into the database.", "green");
                } else // testing data queued.
                {
                    $sSubject = "Test Results submitted for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg  .= $sBacklink;
                    $sMsg .= "This testing data has been queued.";
                    $sMsg .= "\n";
                    addmsg("The testing data you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject = "Test Results modified for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  .= $sBacklink;
                addmsg("testing data modified.", "green");
            break;
            case "delete":
                $sSubject = "Test Results deleted for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                // if replyText is set we should report the reason the data was deleted 
                if($aClean['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("testing data deleted.", "green");
            break;
            case "reject":
                $sSubject = "Test Results rejected for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  .= $sBacklink;
                 // if replyText is set we should report the reason the data was rejected 
                if($aClean['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $aClean['replyText']."\n"; // append the reply text, if there is any 
                }
                addmsg("testing data rejected.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }
 
    function ShowTestResult()
    {
        echo '<p><b>What works</b><br />',"\n";
        echo $this->sWhatWorks;
        echo '<p><b>What does not</b><br />',"\n";
        echo $this->sWhatDoesnt;
        echo '<p><b>What was not tested</b><br />',"\n";
        echo $this->sWhatNotTested;
    }

    // Show the Test results for a application version
    function ShowVersionsTestingTable($link, $iDisplayLimit)
    {
        /* escape input parameters */
        $link = mysql_real_escape_string($link);
        $iDisplayLimit = mysql_real_escape_string($iDisplayLimit);

        $aClean = array(); //array of filtered user input
        $aClean['showAll'] = makeSafe($_REQUEST['showAll']);

        $showAll = $aClean['showAll'];

        $sQuery = "SELECT * 
                   FROM testResults
                   WHERE versionId = '".$this->iVersionId."'
                   ORDER BY testedDate DESC";
	
        if(!$showAll)
            $sQuery.=" LIMIT 0,".$iDisplayLimit;

        $hResult = query_appdb($sQuery);
        if(!$hResult)
            return;

        $rowsUsed = mysql_num_rows($hResult);

        if($rowsUsed == 0)
             return;
        echo '<p><span class="title">Testing Results</span><br />',"\n";
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
        echo '<td>Status</td>',"\n";
        echo '</tr></thead>',"\n";
        while($oRow = mysql_fetch_object($hResult))
        {
            $oTest = new testData($oRow->testingId);
            $oVersion = new version($oTest->iVersionId);
            $oApp  = new application($oVersion->iAppId);
            $oSubmitter = new User($oTest->iSubmitterId);
            $oDistribution = new distribution($oTest->iDistributionId);
            $bgcolor = $oTest->sTestedRating;
            echo '<tr class='.$bgcolor.'>',"\n";

            /* if the test we are displaying is this test then */
            /* mark it as the current test */
            if ($oTest->iTestingId == $this->iTestingId)
            {
                echo '    <td align="center" class="color2"><b>Current</b></td>',"\n";
            } else
            {
                echo '    <td align="center" class="color2">[<a href="'.$link.$oTest->iTestingId;

                if(is_string($showAll))
                    echo '&showAll='.$showAll.'">Show</a>]</td>',"\n";
                else
                    echo '">Show</a>]</td>',"\n";
            }

            switch($oTest->sQueued)
            {
                case "false":
                   $sStatus = "Checked";
                break;
                case "true":
                   $sStatus = "Queued";
                break;
                case "rejected":
                   $sStatus = "Rejected";
                break;
            }

            echo '    <td>',"\n";
            echo '<a href="'.BASE.'distributionView.php?iDistributionId='.$oTest->iDistributionId.'">',"\n";
            echo $oDistribution->sName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td>'.date("M d Y", mysqldatetime_to_unixtimestamp($oTest->sTestedDate)).'</td>',"\n";
            echo '    <td>'.$oTest->sTestedRelease.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sInstalls.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sRuns.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sTestedRating.'&nbsp</td>',"\n";
            echo '    <td>'.$sStatus.'&nbsp</td>',"\n";
            if ($_SESSION['current']->hasAppVersionModifyPermission($oVersion))
            {
                echo '<td><a href="'.BASE.'admin/adminTestResults.php?sSub=view&iTestingId='.$oTest->iTestingId.'">',"\n";
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
    function get_test_for_versionid($iVersionId)
    {
        $sQuery = "SELECT testingId from testResults where versionId = '?' 
                     ORDER BY testedDate DESC limit 1";
        $hResult = query_parameters($sQuery, $iVersionId);
        if(!$hResult)
            return 0;

        $oRow = mysql_fetch_object($hResult);
        return $oRow->testingId;
    }

    // show the fields for editing
    function OutputEditor($sDistribution="", $bNewDist=false)
    {
        HtmlAreaLoaderScript(array("Test1", "Test2", "Test3"));

        echo html_frame_start("Testing Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // What works
        echo '<tr valign=top><td class="color0"><b>What works</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test1" name="sWhatWorks">';
        echo $this->sWhatWorks.'</textarea></p></td></tr>',"\n";
        // What Does not work
        echo '<tr valign=top><td class=color1><b>What does not work</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test2" name="sWhatDoesnt">';
        echo $this->sWhatDoesnt.'</textarea></p></td></tr>',"\n";
        // What was not tested
        echo '<tr valign=top><td class=color0><b>What was not tested</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test3" name="sWhatNotTested">';
        echo $this->sWhatNotTested.'</textarea></p></td></tr>',"\n";
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
        make_distribution_list("iDistributionId", $this->iDistributionId);
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

        echo "</table>\n";

        echo html_frame_end();
    }

    /* $aValues can be $_REQUEST or any array with the values from OutputEditor() */
    function CheckOutputEditorInput($aValues, $sDistribution="")
    {
        $errors = "";
        if (empty($aValues['sWhatWorks']))
            $errors .= "<li>Please enter what worked.</li>\n";

        if (empty($aValues['sWhatDoesnt']))
            $errors .= "<li>Please enter what did not work.</li>\n";

        if (empty($aValues['sWhatNotTested']))
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
        
        return $errors;

    }

    /* retrieves values from $_REQUEST that were output by OutputEditor() */
    /* $aValues can be $_REQUEST or any array with the values from OutputEditor() */
    function GetOutputEditorValues($aValues)
    {
        $this->iTestingId = $aValues['iTestingId'];
        $this->iVersionId = $aValues['iVersionId'];
        $this->sWhatWorks = $aValues['sWhatWorks'];
        $this->sWhatDoesnt = $aValues['sWhatDoesnt'];
        $this->sWhatNotTested = $aValues['sWhatNotTested'];
        $this->sTestedDate = $aValues['sTestedDate'];
        $this->iDistributionId = $aValues['iDistributionId'];
        $this->sTestedRelease = $aValues['sTestedRelease'];
        $this->sInstalls = $aValues['sInstalls'];
        $this->sRuns = $aValues['sRuns'];
        $this->sTestedRating = $aValues['sTestedRating'];
        $this->sComments = $aValues['sComments'];
    }


    function getTestingQueue($sQueued='true')
    {
        if($_SESSION['current']->hasPriv("admin"))
        {
            $hResult = query_parameters("SELECT * 
                                    FROM testResults
                                    WHERE queued = '?'", $sQueued);
            if(!$hResult || mysql_num_rows($hResult) == 0)
                return;
        } else
        {
            $hResult = query_parameters("SELECT * 
                                    FROM testResults
                                    WHERE queued = '?'
                                    AND submitterId = '?'",
                                    $sQueued, $_SESSION['current']->iUserId);
            if(!$hResult || mysql_num_rows($hResult) == 0)
                return;
        }
        return $hResult;
    }

    function ShowListofTests($hResult, $heading="")
    {
        //show applist
        echo html_frame_start($heading,"90%","",0);
        echo "<table width=\"100%\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
               <tr class=color4>
                  <td>Submission Date</td>
                  <td>Submitter</td>
                  <td>Application</td>
                  <td>Version</td>
                  <td>Release</td>
                  <td>Rating</td>
                  <td align=\"center\">Action</td>
               </tr>";

        while($oRow = mysql_fetch_object($hResult))
        {
            $oTest = new testData($oRow->testingId);
            $oVersion = new version($oTest->iVersionId);
            // dont show testing results of versions that are still queued.
            if ($oVersion->sQueued == 'false')
            {
                $oApp  = new application($oVersion->iAppId);
                $oSubmitter = new User($oTest->iSubmitterId);
                $bgcolor = $oTest->sTestedRating;
                echo '<tr class='.$bgcolor.'>',"\n";
                echo "    <td>".print_date(mysqltimestamp_to_unixtimestamp($oTest->sSubmitTime))."</td>\n";
                echo "    <td>\n";
                echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
                echo $oSubmitter->sRealname;
                echo $oSubmitter->sEmail ? "</a>":"";
                echo "    </td>\n";
                echo "    <td>".$oApp->sName."</td>\n";
                echo "    <td>".$oVersion->sName."</td>\n";
                echo "    <td>".$oTest->sTestedRelease."</td>\n";
                echo "    <td>".$oTest->sTestedRating."</td>\n";
                echo "    <td align=\"center\">[<a href=".$_SERVER['PHP_SELF']."?sSub=view&iTestingId=".$oTest->iTestingId.">process</a>]</td>\n";
                echo "</tr>\n\n";
            }
        }
        echo "</table>","\n";
        
        echo html_frame_end();
    }

    /* Get the number of TestResults in the database */
    function getNumberOfQueuedTests()
    {
        $sQuery = "SELECT count(*) as num_tests
               FROM testResults, appVersion
               WHERE appVersion.versionId=testResults.versionId
               and appVersion.queued='false' 
               and testResults.queued='true';";

        $hResult = query_parameters($sQuery);
        if($hResult)
        {
            $oRow = mysql_fetch_object($hResult);
            return $oRow->num_tests;
        }
        return 0;
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
}

?>
