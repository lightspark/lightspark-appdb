<?php
/***************************************/
/* Monitor class and related functions */
/***************************************/


/**
 * Monitor class for handling Monitors
 */
class Monitor {
    var $iMonitorId;

    // variables necessary for creating a monitor
    var $iAppId;
    var $iVersionId;
    var $iUserId;

    /**
     * Constructor.
     * If $iMonitorId is provided, fetches Monitor.
     */
    function Monitor($iMonitorId="", $oRow = null)
    {
        if(!$iMonitorId && !$oRow)
            return;

        if(!$oRow)
        {
            $sQuery = "SELECT *
                       FROM appMonitors
                       WHERE monitorId = '".$iMonitorId."'";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);
        }

        if($oRow)
        {
            $this->iMonitorId = $oRow->monitorId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->iUserId = $oRow->userId;
        }
    }

    function find($iUserId, $iVersionId=0)
    {
        if($iUserId && $iVersionId)
        {
            $sQuery = "SELECT *
                          FROM appMonitors
                          WHERE userId = '".$iUserId."'
                          AND versionId = '".$iVersionId."'";
            $hResult = query_appdb($sQuery);
            if( $oRow = mysql_fetch_object($hResult) )
            {
                $this->iMonitorId = $oRow->monitorId;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->iUserId = $oRow->userId;
            }
        }
    }

    /*
     * Creates a new Monitor.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create()
    {
        /* Check for duplicate entries */
        $oMonitor = new monitor();
        $oMonitor->find($this->iUserId, $this->iVersionId);
        if($oMonitor->iVersionId)
            return FALSE;

        // create the new monitor entry
        $hResult = query_parameters("INSERT INTO appMonitors (versionId, appId,".
                                    "submitTime, userId) ".
                                    "VALUES ('?', '?', ?, '?')",
                                    $this->iVersionId, $this->iAppId,
                                    "NOW()", $this->iUserId);

        if($hResult)
        {
            $this->Monitor(mysql_insert_id());
            $sWhatChanged = "New monitor\n\n";
            $this->SendNotificationMail("add", $sWhatChanged);
            return true;
        } else
        {
            addmsg("Error while creating a new Monitor.", "red");
            return false;
        }
    }


   /**
     * Removes the current Monitor from the database.
     * Informs interested people about the deletion.
     */
    function delete($bSilent=false)
    {
        $hResult = query_parameters("DELETE FROM appMonitors WHERE monitorId = '?'", $this->iMonitorId);
        if(!$bSilent)
            $this->SendNotificationMail("delete");

        if(!$hResult)
            return FALSE;

        return TRUE;
    }

    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        /* Set variables depending on whether it is an application or version monitor */
        if(isset($this->iVersionId))
        {
            $oVersion = new version($this->iVersionId);
            $sAppName = version::fullName($this->iVersionId);
            $sUrl = $oVersion->objectMakeUrl();
            $sVersion = " version";
        } else
        {
            $oApp = new application($this->iAppId);
            $sAppName = Application::lookup_name($this->iAppId);
            $sUrl = $oApp->objectMakeUrl();
        }

        switch($sAction)
        {
            case "add":
                $sSubject = "Monitor for ".$sAppName;
                $sSubject .= " added: ".$_SESSION['current']->sRealname;
                $sMsg .= "$sUrl\n";
                addmsg("You will now receive an email whenever changes are made ".
                "to this application$sVersion.", "green");
            break;
            case "delete":
                $sSubject = "Monitor for ".$sAppName;
                $sSubject .= " removed: ".$_SESSION['current']->sRealname;
                $sMsg .= "$sUrl\n";
                addmsg("You will no longer receive an email whenever changes ".
                "are made to this application$sVersion.", "green");
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin") ||
           ($this->iUserId == $_SESSION['current']->iUserId))
            return TRUE;

        return FALSE;
    }

    function mustBeQueued()
    {
        return FALSE;
    }

    /* Stub */
    function display()
    {
        return "";
    }

    /* Stub */
    function getOutputEditorValues()
    {
        return FALSE;
    }

    /* Stub */
    function objectGetHeader()
    {
        return null;
    }

    function objectGetId()
    {
        return $this->iMonitorId;
    }

        /* Stub */
    function objectGetTableRow()
    {
        return null;
    }

    /* Stub */
    function objectMakeLink()
    {
        return "";
    }

    /* Stub */
    function objectMakeUrl()
    {
        return "";
    }

    /* Stub */
    function outputEditor()
    {
        return "";
    }

    function objectGetEntries($bQueued, $bRejected)
    {
        $hResult = query_parameters("SELECT * FROM appMonitors");

        if(!$hResult)
            return FALSE;

        return $hResult;
    }

    function objectGetEntriesCount($bQueued, $bRejected)
    {
        $hResult = query_parameters("SELECT COUNT(DISTINCT monitorId) as count
                                    FROM appMonitors");

        if(!$hResult)
            return FALSE;

        $oRow = mysql_fetch_object($hResult);

        if(!$oRow)
            return FALSE;

        return $oRow->count;
    }

    function allowAnonymousSubmissions()
    {
        /* That makes no sense */
        return FALSE;
    }

    /* Retrieve the user's monitored versions */
    function getVersionsMonitored($oUser)
    {
         $hResult = query_parameters("SELECT appId, versionId FROM appMonitors WHERE userId = '?'", $oUser->iUserId);

         if(!$hResult || mysql_num_rows($hResult) == 0)
             return NULL;

         $aVersionsMonitored = array();

         for($i = 0; $oRow = mysql_fetch_object($hResult); $i++)
             $aVersionsMonitored[$i] = array($oRow->appId, $oRow->versionId);

         return $aVersionsMonitored;
    }
}
?>
