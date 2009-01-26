<?php

/**
 * Class to show an overview of a user's maintainership, including apps maintained
 * and their ratings
 */
class maintainerView
{
    var $iUserId;
    var $bViewingSelf;

    function maintainerView($iUserId = null)
    {
        if(!$iUserId)
            $this->iUserId = $_SESSION['current']->iUserId;
        else
            $this->iUserId = $iUserId;

        if(!$iUserId || $this->iUserId == $_SESSION['current']->iUserId)
            $this->bViewingSelf = true;
        else
            $this->bViewingSelf = false;
    }

    static function objectGetId()
    {
        return $this->iUserId;
    }

    function addVersionRatingInfo($oTableRow, $oVersion)
    {
        $oTableRow->AddTextCell($oVersion->objectMakeLink());

        /* Rating info */
        $oTableCell = new TableCell($oVersion->sTestedRating);
        $oTableCell->SetClass($oVersion->sTestedRating);
        $oTableRow->AddCell($oTableCell);
        $oTableCell = new TableCell($oVersion->sTestedRelease);
        $oTableCell->SetClass($oVersion->sTestedRating);
        $oTableRow->AddCell($oTableCell);

        /* Get test reports submitted by the user */
        $aTestData = testData::getTestResultsForUser($this->iUserId,
                                                     $oVersion->iVersionId);

        if(sizeof($aTestData))
        {
            $oTest = $aTestData[0];
            $sUserRating = $oTest->sTestedRating;
            $sUserRelease = $oTest->sTestedRelease;
        } else
        {
            $sUserRating = '';
            $sUserRelease = '';
        }

        $oTableCell = new TableCell($sUserRating);
        $oTableCell->SetClass($sUserRating);
        $oTableRow->AddCell($oTableCell);
        $oTableCell = new TableCell($sUserRelease);
        $oTableCell->SetClass($sUserRating);
        $oTableRow->AddCell($oTableCell);

        return $oTableRow;
    }

    /* Shows a detailed vis of the user's maintained applications,
       including last tested release & rating */
    function display()
    {
        $oUser = new user($this->iUserId);

        $aMaintainedApps = maintainer::getAppsMaintained($oUser);
        if(!$aMaintainedApps || !sizeof($aMaintainedApps))
        {
            if($this->bViewingSelf)
                echo '<p>You do not maintain any apps</p>';
            else
                echo "<p>{$oUser->sRealname} does not maintain any apps</p>";
            return;
        }

        if($this->bViewingSelf)
            echo '<p>Viewing your maintained apps</p>';
        else
            echo "<p>Viewing {$oUser->sRealname}'s maintained apps</p>";

        $oTable = new Table();
        $oTableRow = new TableRow();
        $oTableRow->setClass('color4');
        $oTable->setCellSpacing(0);
        $oTable->setCellPadding(3);
        $oTableRow->AddTextCell('Application');
        $oTableRow->AddTextCell('Version');
        $oTableRow->AddTextCell('Current Rating');
        $oTableRow->AddTextCell('Current Version');
        $oTableRow->AddTextCell($this->bViewingSelf ? 'Your Rating' : "User's Rating");
        $oTableRow->AddTextCell($this->bViewingSelf ? 'Your Version' : "User's Version");
        $oTable->AddRow($oTableRow);

        $i = 1;
        while(list($iIndex, list($iAppId, $iVersionId, $bSuperMaintainer)) = each($aMaintainedApps))
        {
            $oApp = new application($iAppId);
            $aVersions = array();

            $oTableRow = new TableRow();
            $oTableRow->AddTextCell($oApp->objectMakeLink());

            $oTableRow->SetClass(($i % 2) ? 'color0' : 'color1');
            $i++;

            if($iVersionId)
            {
                $oVersion = new version($iVersionId);
                $oTableRow = maintainerView::addVersionRatingInfo($oTableRow, $oVersion);
                $oTable->AddRow($oTableRow);
            } else
            {
                $aVersions = $oApp->getVersions(true);
                $oTableRow->AddTextCell('*');
                $oTableRow->AddTextCell('');
                $oTableRow->AddTextCell('');
                $oTableRow->AddTextCell('');
                $oTableRow->AddTextCell('');
                $oTable->AddRow($oTableRow);
            }

            foreach($aVersions as $oVersion)
            {
                $oTableRow = new TableRow($oTableRow);
                $oTableRow->AddTextCell('');
                $oTableRow = maintainerView::addVersionRatingInfo($oTableRow, $oVersion);

                $oTableRow->SetClass(($i % 2) ? 'color0' : 'color1');
                $i++;

                $oTable->AddRow($oTableRow);
            }
        }
        echo $oTable->GetString();
    }
}

?>
