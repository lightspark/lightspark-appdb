<?php
/***********************************************************/
/* this class represents an application incl. all versions */
/***********************************************************/

require(BASE."include/version.php");
require(BASE."include/vendor.php");

/**
 * Application class for handling applications.
 */
class Application {

    var $iAppId;
    var $iVendorId;
    var $iCatId;
    var $sName;
    var $sKeywords;
    var $sDescription;
    var $sWebpage;
    var $bQueued;
    var $iSubmitterId;
    var $aVersionsIds;  // an array that contains the versionId of every version linked to this app.

    /**    
     * constructor, fetches the data.
     */
    function Application($iAppId = null)
    {
        // we are working on an existing application
        if($iAppId)
        {
            /*
             * We fetch application data and versionsIds. 
             */
            $sQuery = "SELECT appFamily.*, appVersion.versionId AS versionId
                       FROM appFamily, appVersion 
                       WHERE appFamily.appId = appVersion.appId 
                       AND appFamily.appId = ".$iAppId;
            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    if(!$this->iAppId)
                    {
                        $this->iAppId = $iAppId;
                        $this->iVendorId = $oRow->vendorId;
                        $this->iCatId = $oRow->catId;
                        $this->iSubmitterId = $oRow->submitterId;
                        $this->sDate = $oRow->submitTime;
                        $this->sName = $oRow->appName;
                        $this->sKeywords = $oRow->keywords;
                        $this->sDescription = $oRow->description;
                        $this->sWebpage = $oRow->webPage;
                        $this->bQueued = $oRow->queued;
                    }
                    $this->aVersionsIds[] = $oRow->versionId;
                }
            }

            /*
             * Then we fetch the data related to this application if the first query didn't return anything.
             * This can happen if an application has no version linked to it.
             */
            if(!$this->appId)
            {
                $sQuery = "SELECT *
                           FROM appFamily 
                           WHERE appId = ".$iAppId;
                if($hResult = query_appdb($sQuery))
                {
                    $oRow = mysql_fetch_object($hResult);
                    $this->iAppId = $iAppId;
                    $this->iVendorId = $oRow->vendorId;
                    $this->iCatId = $oRow->catId;
                    $this->iSubmitterId = $oRow->submitterId;
                    $this->sDate = $oRow->submitTime;
                    $this->sName = $oRow->appName;
                    $this->sKeywords = $oRow->keywords;
                    $this->sDescription = $oRow->description;
                    $this->sWebpage = $oRow->webPage;
                    $this->bQueued = $oRow->queued;
                }
            }
        }
    }


    /**
     * Creates a new application.
     */
    function create($sName=null, $sDescription=null, $sKeywords=null, $sWebpage=null, $iVendorId=null, $iCatId=null)
    {

        // Security, if we are not an administrator the application must be queued.
        if(!($_SESSION['current']->hasPriv("admin")))
            $this->bQueued = true;
        else
            $this->bQueued = false;

        $aInsert = compile_insert_string(array( 'appName'    => $sName,
                                                'description'=> $sDescription,
                                                'keywords'   => $sKeywords,
                                                'webPage'    => $sWebpage,
                                                'vendorId'   => $iVendorId,
                                                'catId'      => $iCatId,
                                                'queued'     => $this->bQueued ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appFamily $sFields VALUES $sValues", "Error while creating a new application."))
        {
            $this->iAppId = mysql_insert_id();
            $this->mailSupermaintainers();  // Only administrators will be mailed as no supermaintainers exist for this app.
            $this->application($this->iAppId);
            return true;
        }
        else
            return false;
    }


    /**
     * Update application.
     * FIXME: Informs interested people about the modification.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sDescription=null, $sKeywords=null, $sWebpage=null, $iVendorId=null, $iCatId=null)
    {
        if ($sName)
        {
            if (!query_appdb("UPDATE appFamily SET appName = '".$sName."' WHERE appId = ".$this->iAppId))
                return false;
            $this->sName = $sName;
        }     

        if ($sDescription)
        {
            if (!query_appdb("UPDATE appFamily SET description = '".$sDescription."' WHERE appId = ".$this->iAppId))
                return false;
            $this->sDescription = $sDescription;
        }

        if ($sKeywords)
        {
            if (!query_appdb("UPDATE appFamily SET keywords = '".$sKeywords."' WHERE appId = ".$this->iAppId))
                return false;
            $this->sKeywords = $sKeywords;
        }

        if ($sWebpage)
        {
            if (!query_appdb("UPDATE appFamily SET webPage = '".$sWebpage."' WHERE appId = ".$this->iAppId))
                return false;
            $this->sWebpage = $sWebpage;
        }
     
        if ($iVendorId)
        {
            if (!query_appdb("UPDATE appFamily SET vendorId = '".$iVendorId."' WHERE appId = ".$this->iAppId))
                return false;
            $this->iVendorId = $iVendorId;
        }
        return true;
    }


    /**    
     * Deletes the application from the database. 
     * and request the deletion of linked elements.
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM appFamily 
                   WHERE appId = ".$this->iAppId." 
                   LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            foreach($aVersionsIds as $iVersionId)
            {
                $oVersion = new Version($iVersionId);
                $oVersion->delete($bSilent);
            }
        }
        if(!$bSilent)
            $this->mailSupermaintainers(true);
    }


    /**
     * Move application out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the application out of the queue.
        if(!$this->bQueued)
            return false;

        $sUpdate = compile_insert_string(array('queued'    => "false"));
        if(query_appdb("UPDATE appFamily ".$sUpdate))
        {
            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->mailSupermaintainers();

            // the application has been unqueued
            addmsg("The application has been unqueued.", "green");
        }
    }


    function mailSubmitter($bRejected=false)
    {
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted application accepted";
                $sMsg  = "The application you submitted (".$this->sName.") has been accepted.";
            } else
            {
                 $sSubject =  "Submitted application rejected";
                 $sMsg  = "The application you submitted (".$this->sName.") has been rejected.";
            }
            $sMsg .= $_REQUEST['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function mailSupermaintainers($bDeleted=false)
    {
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Application ".$this->sName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?appId=".$this->iAppId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This application has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The application was successfully added into the database.", "green");
            } else // Application queued.
            {
                $sSubject = "Application ".$this->sName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg .= "This application has been queued.";
                $sMsg .= "\n";
                addmsg("The application you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Application deleted.
        {
            $sSubject = "Application ".$this->sName." deleted by ".$_SESSION['current']->sRealname;
            addmsg("Application deleted.", "green");
        }

        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 

}


/*
 * Application functions that are not part of the class
 */

function lookup_version_name($versionId)
{
    if(!$versionId) return null;
    $result = query_appdb("SELECT versionName FROM appVersion WHERE versionId = $versionId");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->versionName;
}


function lookup_app_name($appId)
{
    if(!$appId) return null;
    $result = query_appdb("SELECT appName FROM appFamily WHERE appId = $appId");
    if(!$result || mysql_num_rows($result) != 1)
        return null;
    $ob = mysql_fetch_object($result);
    return $ob->appName;
}


/**
 * Remove html formatting from description and extract the first part of the description only.
 * This is to be used for search results, application summary tables, etc.
 */ 
function trim_description($sDescription)
{
    // 1) let's take the first line of the description:
    $aDesc = explode("\n",trim($sDescription),2);
    // 2) maybe it's an html description and lines are separated with <br> or </p><p>
    $aDesc = explode("<br>",$aDesc[0],2);
    $aDesc = explode("<br />",$aDesc[0],2);
    $aDesc = explode("</p><p>",$aDesc[0],2);
    $aDesc = explode("</p><p /><p>",$aDesc[0],2);
    return trim(strip_tags($aDesc[0]));
}
?>
