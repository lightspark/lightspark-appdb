<?php

/**
 * Functions related to download links
 */

require_once(BASE."include/appData.php");

class downloadurl
{
    var $iId;
    var $iVersionId;
    var $sDescription;
    var $sUrl;
    var $sSubmitTime;
    var $iSubmitterId;
    var $bQueued;

    /* Fetch the data if id is given */
    function downloadurl($iId = NULL)
    {
        $hResult = query_parameters("SELECT id, versionId, description, url,
            submitTime, submitterId, queued FROM appData WHERE id = '?'",
                $this->iId);

        if($hResult && mysql_num_rows($hResult))
        {
            $oRow = mysql_fetch_object($hResult);
            if($oRow)
            {
                $this->iId = $oRow->id;
                $this->iVersionId = $oRow->versionId;
                $this->sDescription = $oRow->description;
                $this->sUrl = $oRow->url;
                $this->sSubmitTime = $oRow->submitTime;
                $this->iSubmitterId = $oRow->submitterId;
                $this->bQueued = ($oRow->queued == "true") ? TRUE : FALSE;
            }
        }
    }

    /* Display download links for a given version */
    function display($iVersionId)
    {
        if(!($hResult = appData::getData($iVersionId, "downloadurl")))
            return FALSE;

        for($i = 0; $oRow = mysql_fetch_object($hResult); $i++)
        {
            $sReturn .= html_tr(array(
                "<b>Free Download</b>",
                "<a href=\"$oRow->url\">$oRow->description</a>"),
                "color1");
        }

        return $sReturn;
    }

    /* Output an editor for Download URL fields */
    function OutputEditor($oVersion, $sFormAction)
    {
        /* Check for correct permissions */
        if(!downloadurl::canEdit($oVersion->iVersionId))
            return FALSE;

        $hResult = appData::getData($oVersion->iVersionId, "downloadurl");

        $sReturn .= html_frame_start("Download URL", "90%", "", 0);
        $sReturn .= "<form method=\"post\" action=\"$sFormAction\">\n";
        $sReturn .= html_table_begin("width=100%");
        $sReturn .= html_tr(array(
            array("<b>Remove</b>", "width=\"90\""),
            "<b>Description</b>",
            "<b>URL</b>"
            ),
            "color0");

            $sReturn .= html_tr(array(
                "&nbsp;",
                "<input type=\"text\" size=\"45\" name=\"".
                "sDescriptionNew\" />",
                "<input type=\"text\" size=\"45\" name=\"sUrlNew\" />"),
                "color4");

        if($hResult)
        {
            for($i = 1; $oRow = mysql_fetch_object($hResult); $i++)
            {
                $sReturn .= html_tr(array(
                    "<input type=\"checkbox\" name=\"bRemove$oRow->id\" ".
                    "value=\"true\" />",
                    "<input type=\"text\" size=\"45\" name=\"".
                    "sDescription$oRow->id\" value=\"$oRow->description\" />",
                    "<input type=\"text\" size=\"45\" name=\"sUrl$oRow->id\" ".
                    "value=\"$oRow->url\" />"),
                    ($i % 2) ? "color0" : "color4");
            }
        }

        $sReturn .= html_table_end();
        $sReturn .= "<div align=\"center\"><input type=\"submit\" value=\"".
                    "Update Download URLs\" name=\"sSubmit\" /></div>\n";
        $sReturn .= "<input type=\"hidden\" name=\"iVersionId\" ".
                    "value=\"$oVersion->iVersionId\" />\n";
        $sReturn .= "<input type=\"hidden\" name=\"iAppId\" ".
                    "value=\"$oVersion->iAppId\" />\n";
        $sReturn .= "</form>\n";
        $sReturn .= html_frame_end("&nbsp;");

        return $sReturn;
    }

    /* Process data from a Download URL form */
    function ProcessForm($aValues)
    {
        /* Check that we are processing a Download URL form */
        if($aValues["sSubmit"] != "Update Download URLs")
            return FALSE;

        /* Check permissions */
        if(!downloadurl::canEdit($aValues["iVersionId"]))
            return FALSE;

        if(!($hResult = query_parameters("SELECT COUNT(*) as num FROM appData 
            WHERE TYPE = '?' AND versionId = '?'",
                "downloadurl", $aValues["iVersionId"])))
            return FALSE;

        if(!($oRow = mysql_fetch_object($hResult)))
            return FALSE;

        $num = $oRow->num;

        /* Update URLs.  Nothing to do if none are present in the database */
        if($num)
        {
            if(!$hResult = appData::getData($aValues["iVersionId"], "downloadurl"))
                return FALSE;

            while($oRow = mysql_fetch_object($hResult))
            {
                /* Remove URL */
                if($aValues["bRemove$oRow->id"])
                {
                    if(!query_parameters("DELETE FROM appData WHERE id = '?'",
                        $oRow->id))
                        return FALSE;

                    $sWhatChangedRemove .= "Removed\nURL: $oRow->url\n".
                    "Description: $oRow->description\n\n";
                }

                /* Change description/URL */
                if(($aValues["sDescription$oRow->id"] != $oRow->description or 
                $aValues["sUrl$oRow->id"] != $oRow->url) && 
                $aValues["sDescription$oRow->id"] && $aValues["sUrl$oRow->id"])
                {
                    if(!query_parameters("UPDATE appData SET description = '?',
                        url = '?' WHERE id = '?'",
                            $aValues["sDescription$oRow->id"],
                            $aValues["sUrl$oRow->id"], $oRow->id))
                        return FALSE;

                    $sWhatChangedModify .= "Modified\nOld URL: $oRow->url\nOld ".
                        "Description: $oRow->description\nNew URL: ".
                        $aValues["sUrl$oRow->id"]."\nNew Description: ".
                        $aValues["sDescription$oRow->id"]."\n\n";
                }
            }
        }

        /* Insert new URL */
        if($aValues["sDescriptionNew"] && $aValues["sUrlNew"])
        {
            if(!query_parameters("INSERT INTO appData (versionId, TYPE, 
            description, url, submitterId, queued)
            VALUES('?', '?', '?', '?', '?', '?')",
                    $aValues["iVersionId"], "downloadurl", 
                    $aValues["sDescriptionNew"], $aValues["sUrlNew"], 
                    $_SESSION["current"]->iUserId, "false"))
                return FALSE;

            $sWhatChanged = "Added\nURL: ".$aValues["sUrlNew"]."\nDescription: ".
                            $aValues["sDescriptionNew"]."\n\n";
        }

        $sWhatChanged .= "$sWhatChangedRemove$sWhatChangedModify";

        if($sWhatChanged && $sEmail =
           User::get_notify_email_address_list($aValues['iVersionId']))
        {
            $oApp = new Application($aValues["iAppId"]);
            $oVersion = new Version($aValues["iVersionId"]);

            $sSubject = "Download URLs for $oApp->sName $oVersion->sName".
            " updated by ".$_SESSION['current']->sRealname;

            $sMsg = APPDB_ROOT."appview.php?iVersionId=".$aValues['iVersionId'];
            $sMsg .= "\n\n";
            $sMsg .= "The following changed were made\n\n";
            $sMsg .= "$sWhatChanged\n\n";

            mail_appdb($sEmail, $sSubject, $sMsg);
        }

        return TRUE;
    }

    function canEdit($iVersionId)
    {
        $oUser = new User($_SESSION['current']->iUserId);

        if($oUser->hasPriv("admin") || maintainer::isUserMaintainer($oUser, 
                                                                    $iVersionId))
        {
            return TRUE;
        } else
        {
            return FALSE;
        }
    }
}

?>
