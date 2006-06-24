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
            $this->iMonitorId = $oRow->monitorId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->iUserId = $oRow->userId;

        }
    }

    function find($iUserId, $iAppId=0, $iVersionId=0)
    {
        if($iUserId)
        {
            if($iVersionId)
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
    }

    /*
     * Creates a new Monitor.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create($iUserId, $iAppId=0, $iVersionId=0)
    {
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
        $hResult = query_appdb("DELETE FROM appMonitors WHERE monitorId = '".$this->iMonitorId."'");
        if(!$bSilent)
            $this->SendNotificationMail("delete");
    }


    function SendNotificationMail($sAction="add",$sMsg=null)
    {
        switch($sAction)
        {
            case "add":
                if (isset($this->iVersionId))
                {
                    $oVersion = new Version($this->iVersionId);
                    $sSubject = "Monitor for ".lookup_app_name($oVersion->iAppId)." ".lookup_version_name($this->iVersionId);
                    $sSubject .= " added: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                    addmsg("You will now recieve an email whenever changes are made to this Application version.", "green");
                } else
                {
                    $sSubject = "Monitor for ".lookup_app_name($this->iAppId)." added: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?appId=".$this->iAppid."\n";
                    addmsg("You will now recieve an email whenever changes are made to this Application.", "green");
                } 
            break;
            case "delete":
                if (isset($this->iVersionId))
                {
                    $oVersion = new Version($this->iVersionId);
                    $sSubject = "Monitor for ".lookup_app_name($oVersion->iAppId)." ".lookup_version_name($this->iVersionId);
                    $sSubject .= " removed: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                    addmsg("You will no longer recieve an email whenever changes are made to this Application version.", "green");
                } else
                {
                    $sSubject = "Monitor for ".lookup_app_name($this->iAppId)." removed: ".$_SESSION['current']->sRealname;
                    $sMsg .= APPDB_ROOT."appview.php?appId=".$this->iAppid."\n";
                    addmsg("You will no longer recieve an email whenever changes are made to this Application.", "green");
                } 
            break;
        }
        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}
?>
