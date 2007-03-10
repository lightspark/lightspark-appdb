<?php
/***************************************/
/* Monitor class and related functions */
/***************************************/


/**
 * Monitor class for handling Monitors
 */
class Monitor {
    var $iMonitorId;
    var $iAppId;
    var $iVersionId;
    var $iUserId;

    /**
     * Constructor.
     * If $iMonitorId is provided, fetches Monitor.
     */
    function Monitor($iMonitorId="")
    {
        if($iMonitorId)
        {
            $sQuery = "SELECT *
                       FROM appMonitors
                       WHERE monitorId = '".$iMonitorId."'";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);
            if($oRow)
            {
                $this->iMonitorId = $oRow->monitorId;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->iUserId = $oRow->userId;
            }
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
            $oRow = mysql_fetch_object($hResult);
            $this->iMonitorId = $oRow->monitorId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->iUserId = $oRow->userId;
        }
    }

    /*
     * Creates a new Monitor.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create($iUserId, $iAppId=0, $iVersionId=0)
    {
        /* Check for duplicate entries */
        $oMonitor = new monitor();
        $oMonitor->find($iUserId, $iVersionId);
        if($oMonitor->iVersionId)
            return FALSE;

        $hResult = query_parameters("INSERT INTO appMonitors (versionId, appId, userId) ".
                                    "VALUES ('?', '?', '?')",
                                    $iVersionId, $iAppId, $iUserId);

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
    }


    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        /* set $aAppName appropriately */
        if(isset($this->iVersionId))
            $sAppName = Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId);
        else
            $sAppName = Application::lookup_name($this->iAppId);

        switch($sAction)
        {
            case "add":
                if (isset($this->iVersionId))
                {
                    $sSubject = "Monitor for ".$sAppName;
                    $sSubject .= " added: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                    addmsg("You will now receive an email whenever changes are made to this Application version.", "green");
                } else
                {
                    $sSubject = "Monitor for ".$sAppName." added: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?iAppId=".$this->iAppid."\n";
                    addmsg("You will now receive an email whenever changes are made to this Application.", "green");
                } 
            break;
            case "delete":
                if (isset($this->iVersionId))
                {
                    $sSubject = "Monitor for ".$sAppName;
                    $sSubject .= " removed: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                    addmsg("You will no longer receive an email whenever changes are made to this Application version.", "green");
                } else
                {
                    $sSubject = "Monitor for ".$sAppName." removed: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?iAppId=".$this->iAppid."\n";
                    addmsg("You will no longer receive an email whenever changes are made to this Application.", "green");
                } 
            break;
        }
        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
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
