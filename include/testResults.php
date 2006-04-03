<?php
/*****************************************/
/* this class represents Testing results */
/*****************************************/
require_once(BASE."include/distributions.php");

// Testing class for handling Testing History.

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
                           WHERE testingId = ".$iTestingId;
                if($hResult = query_appdb($sQuery))
                {
                    $oRow = mysql_fetch_object($hResult);
                    $this->iTestingId = $iTestingId;
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

        $aInsert = compile_insert_string(array( 'versionId'         => $this->iVersionId,
                                                'whatWorks'         => $this->sWhatWorks,
                                                'whatDoesnt'        => $this->sWhatDoesnt,
                                                'whatNotTested'     => $this->sWhatNotTested,
                                                'testedDate'        => $this->sTestedDate,
                                                'distributionId'    => $this->iDistributionId,
                                                'testedRelease'     => $this->sTestedRelease,
                                                'installs'          => $this->sInstalls,
                                                'runs'              => $this->sRuns,
                                                'testedRating'      => $this->sTestedRating,
                                                'comments'          => $this->sComments,
                                                'submitterId'       => $_SESSION['current']->iUserId,
                                                'queued'            => $this->sQueued ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO testResults $sFields VALUES $sValues", "Error while creating test results."))
        {
            $this->iTestingId = mysql_insert_id();
            $this->testData($this->iTestingId);
            $this->SendNotificationMail();
            return true;
        }
        else
            return false;
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

        $sUpdate = compile_update_string(array( 'versionId'         => $this->iVersionId,
                                                'whatWorks'         => $this->sWhatWorks,
                                                'whatDoesnt'        => $this->sWhatDoesnt,
                                                'whatNotTested'     => $this->sWhatNotTested,
                                                'testedDate'        => $this->sTestedDate,
                                                'distributionId'    => $this->iDistributionId,
                                                'testedRelease'     => $this->sTestedRelease,
                                                'installs'          => $this->sInstalls,
                                                'runs'              => $this->sRuns,
                                                'testedRating'      => $this->sTestedRating,
                                                'comments'          => $this->sComments));

        if(query_appdb("UPDATE testResults SET ".$sUpdate." WHERE testingId = ".$this->iTestingId, "Error while updating test results."))
        {
            if(!$bSilent)
                $this->SendNotificationMail();
            return true;
        }
        else
            return false;
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
                   WHERE testingId = ".$this->iTestingId." 
                   LIMIT 1";
        if(!($hResult = query_appdb($sQuery)))
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

        $sUpdate = compile_update_string(array('queued'    => "false"));
        if(query_appdb("UPDATE testResults SET ".$sUpdate." WHERE testingId = ".$this->iTestingId))
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

        $sUpdate = compile_update_string(array('queued'    => "rejected"));
        if(query_appdb("UPDATE testResults SET ".$sUpdate." WHERE testingId = ".$this->iTestingId))
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

        $sUpdate = compile_update_string(array('queued'    => "true"));
        if(query_appdb("UPDATE testResults SET ".$sUpdate." WHERE testingId = ".$this->iTestingId))
        {
            $this->sQueued = 'true';
            // we send an e-mail to intersted people
            $this->SendNotificationMail();
        }
    }

    function mailSubmitter($sAction="add")
    {
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            switch($sAction)
            {
            case "add":
                $sSubject =  "Submitted testing data accepted";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been accepted.";
                $sMsg .= APPDB_ROOT."appview.php?versionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Administrators Responce:\n";
            break;
            case "reject":
                $sSubject =  "Submitted testing data rejected";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been rejected.";
                $sMsg .= APPDB_ROOT."testResults.php?sub=view&iTestingId=".$this->iTestingId."\n";
                $sMsg .= "Reason given:\n";
            break;
            case "delete":
                $sSubject =  "Submitted testing data deleted";
                $sMsg  = "The testing data you submitted (".$oApp->sName." ".$this->sName.") has been deleted.";
                $sMsg .= "Reason given:\n";
            break;
            }
            $sMsg .= $_REQUEST['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
        
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        $oVersion = new Version($this->iVersionId);
        $oApp = new Application($oVersion->iAppId);
        switch($sAction)
        {
            case "add":
                if($this->sQueued == "false")
                {
                    $sSubject = "Test Results added to version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg  = $sMsg .= APPDB_ROOT."appview.php?versionId=".$this->iVersionId."&iTestingId=".$this->iTestingId."\n";
                    if($this->iSubmitterId)
                    {
                        $oSubmitter = new User($this->iSubmitterId);
                        $sMsg .= "This Testing data has been submitted by ".$oSubmitter->sRealname.".";
                        $sMsg .= "\n";
                    }
                    if($_REQUEST['replyText'])
                    {
                        $sMsg .= "Appdb admin reply text:\n";
                        $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                    }
                    addmsg("The testing data was successfully added into the database.", "green");
                } else // testing data queued.
                {
                    $sSubject = "Test Results submitted for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."admin/adminTestResults.php?sub=view&iTestingId=".$this->iTestingId."\n";
                    $sMsg .= "This testing data has been queued.";
                    $sMsg .= "\n";
                    addmsg("The testing data you submitted will be added to the database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject = "Test Results modified for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg .= APPDB_ROOT."admin/adminTestResults.php?sub=view&iTestingId=".$this->iTestingId."\n";
                addmsg("testing data modified.", "green");
            break;
            case "delete":
                $sSubject = "Test Results deleted for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                // if replyText is set we should report the reason the data was deleted 
                if($_REQUEST['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }

                addmsg("testing data deleted.", "green");
            break;
            case "reject":
                $sSubject = "Test Results rejected for version ".$oVersion->sName." of ".$oApp->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg .= APPDB_ROOT."admin/adminTestResults.php?sub=view&iTestingId=".$this->iTestingId."\n";
                 // if replyText is set we should report the reason the data was rejected 
                if($_REQUEST['replyText'])
                {
                    $sMsg .= "Reason given:\n";
                    $sMsg .= $_REQUEST['replyText']."\n"; // append the reply text, if there is any 
                }
                addmsg("testing data rejected.", "green");
            break;
        }
        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }
 
    function ShowTestResult($iCurrentTest,$iVersionId)
    {
        $hResult = query_appdb("SELECT * 
                                FROM testResults
                                WHERE testingId = '".$iCurrentTest."';");
        if(!$hResult || mysql_num_rows($hResult) == 0)
        {
            $hResult = query_appdb("SELECT * 
                                    FROM testResults
                                    WHERE versionId = '".$iVersionId."'
                                    ORDER BY testedDate DESC ;");
            if(!$hResult || mysql_num_rows($hResult) == 0)
                return false;
        }
        $oRow = mysql_fetch_object($hResult);
        echo '<p><b>What works</b><br />',"\n";
        echo $oRow->whatWorks;
        echo '<p><b>What Doesn\'t</b><br />',"\n";
        echo $oRow->whatDoesnt;
        echo '<p><b>What wasn\'t tested</b><br />',"\n";
        echo $oRow->whatNotTested;
        return $oRow->testingId;
    }

    // Show the Test results for a application version
    function ShowVersionsTestingTable($iVersionId, $iCurrentTest, $link, $iDisplayLimit)
    {
        $showAll = $_REQUEST['showAll'];

        $sQuery = "SELECT * 
                   FROM testResults
                   WHERE versionId = '".$iVersionId."'
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

            if ($oTest->iTestingId == $iCurrentTest)
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

            echo '    <td>',"\n";
            echo '<a href="'.BASE.'distributionView.php?iDistributionId='.$oTest->iDistributionId.'">',"\n";
            echo $oDistribution->sName.'</a>',"\n";
            echo '    </td>',"\n";
            echo '    <td>'.date("M d Y", mysqldatetime_to_unixtimestamp($oTest->sTestedDate)).'</td>',"\n";
            echo '    <td>'.$oTest->sTestedRelease.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sInstalls.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sRuns.'&nbsp</td>',"\n";
            echo '    <td>'.$oTest->sTestedRating.'&nbsp</td>',"\n";
            if ($_SESSION['current']->hasAppVersionModifyPermission($oTest->iVersionId))
            {
                echo '<td><a href="'.BASE.'/admin/adminTestResults.php?sub=view&iTestingId='.$oTest->iTestingId.'">',"\n";
                echo 'Edit</a></td>',"\n";
            }
            echo '</tr>',"\n";
        }

        echo '</table>',"\n";

        echo '<form method=get action="'.$PHP_SELF.'">';
        echo '<input name="versionId" type=hidden value="',$iVersionId,'" />';
        if($rowsUsed >= $iDisplayLimit && !is_string($showAll))
            echo '<input class="button" name="showAll" type=submit value="Show All Tests" />';

        if(is_string($showAll))
        {
            echo '<input class="button" name="hideAll" type=submit value="Limit to '.$iDisplayLimit.' Tests" />';
        }
        echo '</form>';
    }

    // show the fields for editing
    function OutputEditor($sDistribution, $bNewDist=false)
    {
        HtmlAreaLoaderScript(array("Test1", "Test2", "Test3"));

        echo html_frame_start("Testing Form", "90%", "", 0);
        echo "<table width='100%' border=0 cellpadding=2 cellspacing=0>\n";

        // What works
        echo '<tr valign=top><td class="color0"><b>What Works</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test1" name="sWhatWorks">';
        echo $this->sWhatWorks.'</textarea></p></td></tr>',"\n";
        // What Does not work
        echo '<tr valign=top><td class=color1><b>What Does not work</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test2" name="sWhatDoesnt">';
        echo $this->sWhatDoesnt.'</textarea></p></td></tr>',"\n";
        // What was not tested
        echo '<tr valign=top><td class=color0><b>What was not tested</b></td>',"\n";
        echo '<td class="color0"><p><textarea cols="80" rows="20" id="Test3" name="sWhatNotTested">';
        echo $this->sWhatNotTested.'</textarea></p></td></tr>',"\n";
        // Date Tested
        echo '<tr valign=top><td class="color1"><b>Date Tested </b></td>',"\n";
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
        echo '<tr><td class=color1><b>Tested Release</b></td><td class=color0>',"\n";
        make_bugzilla_version_list("sTestedRelease", $this->sTestedRelease);
        echo '</td></tr>',"\n";
        // Installs
        echo '<tr><td class=color0><b>Installs?</b></td><td class=color0>',"\n";
        make_Installs_list("sInstalls", $this->sInstalls);
        echo '</td></tr>',"\n";
        // Runs
        echo '<tr><td class=color1><b>Runs?</b></td><td class=color0>',"\n";
        make_Runs_list("sRuns", $this->sRuns);
        echo '</td></tr>',"\n";
        // Rating
        echo '<tr><td class="color0"><b>Rating</b></td><td class="color0">',"\n";
        make_maintainer_rating_list("sTestedRating", $this->sTestedRating);
        echo '</td></tr>',"\n";
        // extra comments
        echo '<tr valign=top><td class="color1"><b>Extra Comments</b></td>',"\n";
        echo '<td class="color0"><textarea name="sComments" rows=10 cols=35>';
        echo $this->sComments.'</textarea></td></tr>',"\n";

        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'" >';
        echo '<input type="hidden" name="iTestingId" value="'.$this->iTestingId.'" >';

        echo "</table>\n";

        echo html_frame_end();
    }
    function CheckOutputEditorInput($sDistribution="")
    {

        $errors = "";
        $sWhatWorks = trim($_REQUEST['sWhatWorks']);
        $sWhatDoesnt = trim($_REQUEST['sWhatDoesnt']);
        $sWhatNotTested = trim($_REQUEST['sWhatNotTested']);
        $sDistribution = trim($_REQUEST['sDistribution']);

        if (empty($sWhatWorks))
            $errors .= "<li>Please enter what worked.</li>\n";

        if (empty($sWhatDoesnt))
            $errors .= "<li>Please enter what did not work.</li>\n";

        if (empty($sWhatNotTested))
            $errors .= "<li>Please enter what was not tested.</li>\n";

        if (empty($_REQUEST['sTestedDate']))
            $errors .= "<li>Please enter the Date and Time that you tested.</li>\n";

        if (empty($_REQUEST['sTestedRelease']))
            $errors .= "<li>Please enter the version of Wine that you tested with.</li>\n";

        // No Distribution entered, and nothing in the list is selected
        if (empty($sDistribution) && !$_REQUEST['iDistributionId'])
            $errors .= "<li>Please enter a Distribution.</li>\n";

        if (empty($_REQUEST['sInstalls']))
            $errors .= "<li>Please enter whether this application installs or not.</li>\n";

        if (empty($_REQUEST['sRuns']))
            $errors .= "<li>Please enter whether this application runs or not.</li>\n";

        if (empty($_REQUEST['sTestedRating']))
            $errors .= "<li>Please enter a Rating based on how well this application runs.</li>\n";
        
        return $errors;

    }

    /* retrieves values from $_REQUEST that were output by OutputEditor() */
    function GetOutputEditorValues()
    {
        if(get_magic_quotes_gpc())
        {
            $this->iTestingId = stripslashes($_REQUEST['iTestingId']);
            $this->iVersionId = stripslashes($_REQUEST['iVersionId']);
            $this->sWhatWorks = stripslashes($_REQUEST['sWhatWorks']);
            $this->sWhatDoesnt = stripslashes($_REQUEST['sWhatDoesnt']);
            $this->sWhatNotTested = stripslashes($_REQUEST['sWhatNotTested']);
            $this->sTestedDate = stripslashes($_REQUEST['sTestedDate']);
            $this->iDistributionId = stripslashes($_REQUEST['iDistributionId']);
            $this->sTestedRelease = stripslashes($_REQUEST['sTestedRelease']);
            $this->sInstalls = stripslashes($_REQUEST['sInstalls']);
            $this->sRuns = stripslashes($_REQUEST['sRuns']);
            $this->sTestedRating = stripslashes($_REQUEST['sTestedRating']);
            $this->sComments = stripslashes($_REQUEST['sComments']);
        } else
        {
            $this->iTestingId = $_REQUEST['iTestingId'];
            $this->iVersionId = $_REQUEST['iVersionId'];
            $this->sWhatWorks = $_REQUEST['sWhatWorks'];
            $this->sWhatDoesnt = $_REQUEST['sWhatDoesnt'];
            $this->sWhatNotTested = $_REQUEST['sWhatNotTested'];
            $this->sTestedDate = $_REQUEST['sTestedDate'];
            $this->iDistributionId = $_REQUEST['iDistributionId'];
            $this->sTestedRelease = $_REQUEST['sTestedRelease'];
            $this->sInstalls = $_REQUEST['sInstalls'];
            $this->sRuns = $_REQUEST['sRuns'];
            $this->sTestedRating = $_REQUEST['sTestedRating'];
            $this->sComments = $_REQUEST['sComments'];
        }
    }


    function getTestingQueue($sQueued='true')
    {
        if($_SESSION['current']->hasPriv("admin"))
        {
            $hResult = query_appdb("SELECT * 
                                    FROM testResults
                                    WHERE queued = '".$sQueued."';");
            if(!$hResult || mysql_num_rows($hResult) == 0)
                return;
        } else
        {
            $hResult = query_appdb("SELECT * 
                                    FROM testResults
                                    WHERE queued = '".$sQueued."'
                                    AND submitterId = ".$_SESSION['current']->iUserId.";");
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
                  <td align=\"center\">Action</td>
               </tr>";
        
        $c = 1;
        while($oRow = mysql_fetch_object($hResult))
        {
            $oTest = new testData($oRow->testingId);
            $oVersion = new version($oTest->iVersionId);
            // dont show testing results of versions that are still queued.
            if ($oVersion->sQueued == 'false')
            {
                $oApp  = new application($oVersion->iAppId);
                $oSubmitter = new User($oTest->iSubmitterId);
                if ($c % 2 == 1) { $bgcolor = 'color0'; } else { $bgcolor = 'color1'; }
                echo "<tr class=\"$bgcolor\">\n";
                echo "    <td>".print_date(mysqltimestamp_to_unixtimestamp($oTest->sSubmitTime))."</td>\n";
                echo "    <td>\n";
                echo $oSubmitter->sEmail ? "<a href=\"mailto:".$oSubmitter->sEmail."\">":"";
                echo $oSubmitter->sRealname;
                echo $oSubmitter->sEmail ? "</a>":"";
                echo "    </td>\n";
                echo "    <td>".$oApp->sName."</td>\n";
                echo "    <td>".$oVersion->sName."</td>\n";
                echo "    <td>".$oTest->sTestedRelease."</td>\n";
                echo "    <td align=\"center\">[<a href=".$_SERVER['PHP_SELF']."?sub=view&iTestingId=".$oTest->iTestingId.">process</a>]</td>\n";
                echo "</tr>\n\n";
                $c++;
            }
        }
        echo "</table>","\n";
        
        echo html_frame_end();

    }
}

/* Get the number of TestResults in the database */
function getNumberOfQueuedTests()
{
    $sQuery = "SELECT count(*) as num_tests
               FROM testResults, appVersion
               WHERE appVersion.versionId=testResults.versionId
               and appVersion.queued='false' 
               and testResults.queued='true';";

    $hResult = query_appdb($sQuery);
    if($hResult)
    {
      $row = mysql_fetch_object($hResult);
      return $row->num_tests;
    }
    return 0;
}

function make_Installs_list($varname, $cvalue)
{
    
    echo "<select name='$varname'>\n";
    echo "<option value=\"\">Choose ...</option>\n";
    $aRating = array("Yes", "No", "N/A");
    $iMax = count($aRating);

    for($i=0; $i < $iMax; $i++)
    {
        if($aRating[$i] == $cvalue)
            echo "<option value='".$aRating[$i]."' selected>".$aRating[$i]."\n";
        else
            echo "<option value='".$aRating[$i]."'>".$aRating[$i]."\n";
    }
    echo "</select>\n";
}

function make_Runs_list($varname, $cvalue)
{
    
    echo "<select name='$varname'>\n";
    echo "<option value=\"\">Choose ...</option>\n";
    $aRating = array("Yes", "No", "Not Installable");
    $iMax = count($aRating);

    for($i=0; $i < $iMax; $i++)
    {
        if($aRating[$i] == $cvalue)
            echo "<option value='".$aRating[$i]."' selected>".$aRating[$i]."\n";
        else
            echo "<option value='".$aRating[$i]."'>".$aRating[$i]."\n";
    }
    echo "</select>\n";
}


?>
