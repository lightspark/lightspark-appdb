<?php
/***********************************************************/
/* this class represents an application incl. all versions */
/***********************************************************/

require_once(BASE."include/version.php");
require_once(BASE."include/vendor.php");
require_once(BASE."include/url.php");

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
    var $sSubmitTime;
    var $iSubmitterId;
    var $aVersionsIds;  // an array that contains the versionId of every version linked to this app.
    var $aUrlsIds;            // an array that contains the screenshotId of every url linked to this version

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
                       AND appFamily.appId = ".$iAppId." ORDER BY versionName";
            if($hResult = query_appdb($sQuery))
            {
                $this->aVersionsIds = array();
                while($oRow = mysql_fetch_object($hResult))
                {
                    if(!$this->iAppId)
                    {
                        $this->iAppId = $iAppId;
                        $this->iVendorId = $oRow->vendorId;
                        $this->iCatId = $oRow->catId;
                        $this->iSubmitterId = $oRow->submitterId;
                        $this->sSubmitTime = $oRow->submitTime;
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

            /*
             * We fetch urlsIds. 
             */
            $this->aUrlsIds = array();
            $sQuery = "SELECT id
                       FROM appData
                       WHERE type = 'url'
                       AND appId = ".$iAppId;

            if($hResult = query_appdb($sQuery))
            {
                while($oRow = mysql_fetch_object($hResult))
                {
                    $this->aUrlsIds[] = $oRow->id;
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
                                                'submitterId'=> $_SESSION['current']->iUserId,
                                                'queued'     => $this->bQueued ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appFamily $sFields VALUES $sValues", "Error while creating a new application."))
        {
            $this->iAppId = mysql_insert_id();
            $this->application($this->iAppId);
            $this->mailSupermaintainers();  // Only administrators will be mailed as no supermaintainers exist for this app.
            return true;
        }
        else
            return false;
    }


    /**
     * Update application.
     * Returns true on success and false on failure.
     */
    function update($sName=null, $sDescription=null, $sKeywords=null, $sWebpage=null, $iVendorId=null, $iCatId=null)
    {
        $sWhatChanged = "";

        if ($sName && $sName!=$this->sName)
        {
            $sUpdate = compile_update_string(array('appName'    => sName));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $sWhatChanged .= "Name was changed from ".$this->sName." to ".$sName.".\n\n";
            $this->sName = $sName;
        }     

        if ($sDescription && $sDescription!=$this->sDescription)
        {
            $sUpdate = compile_update_string(array('description'    => $sDescription));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $sWhatChanged .= "Description was changed from\n ".$this->sDescription."\n to \n".$sDescription.".\n\n";
            $this->sDescription = $sDescription;
        }

        if ($sKeywords && $sKeywords!=$this->sKeywords)
        {
            $sUpdate = compile_update_string(array('keywords'    => $sKeywords));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $sWhatChanged .= "Keywords were changed from\n ".$this->sKeywords."\n to \n".$sKeywords.".\n\n";
            $this->sKeywords = $sKeywords;
        }

        if ($sWebpage && $sWebpage!=$this->sWebpage)
        {
            $sUpdate = compile_update_string(array('webPage'    => $sWebpage));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $sWhatChanged .= "Web page was changed from ".$this->sWebpage." to ".$sWebpage.".\n\n";
            $this->sWebpage = $sWebpage;
        }
     
        if ($iVendorId && $iVendorId!=$this->iVendorId)
        {
            $sUpdate = compile_update_string(array('vendorId'    => $iVendorId));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $oVendorBefore = new Vendor($this->iVendorId);
            $oVendorAfter = new Vendor($iVendorId);
            $sWhatChanged .= "Vendor was changed from ".$oVendorBefore->sName." to ".$oVendorBefore->sName.".\n\n";
            $this->iVendorId = $iVendorId;
        }

        if ($iCatId && $iCatId!=$this->iCatId)
        {
            $sUpdate = compile_update_string(array('catId'    => $iCatId));
            if (!query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
                return false;
            $oCatBefore = new Category($this->iCatId);
            $oCatAfter = new Category($iCatId);
            $sWhatChanged .= "Vendor was changed from ".$oCatBefore->sName." to ".$oCatAfter->sName.".\n\n";
            $this->iCatId = $iCatId;
        }
        if($sWhatChanged)
            $this->mailSupermaintainers("edit",$sWhatChanged);
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
            foreach($this->aVersionsIds as $iVersionId)
            {
                $oVersion = new Version($iVersionId);
                $oVersion->delete($bSilent);
            }
            foreach($this->aUrlsIds as $iUrlId)
            {
                $oUrl = new Url($iUrlId);
                $oUrl->delete($bSilent);
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

        $sUpdate = compile_update_string(array('queued'  => "false",
                                               'keywords'=> str_replace(" *** ","",$this->sKeywords) ));
        if(query_appdb("UPDATE appFamily SET ".$sUpdate." WHERE appId = ".$this->iAppId))
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

 
    function mailSupermaintainers($sAction="add",$sMsg=null)
    {
        switch($sAction)
        {
            case "add":
                if(!$this->bQueued)
                {
                    $sSubject = $this->sName." has been added by ".$_SESSION['current']->sRealname;
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
                    $sSubject = $this->sName." has been submitted by ".$_SESSION['current']->sRealname;
                    $sMsg .= "This application has been queued.";
                    $sMsg .= "\n";
                    addmsg("The application you submitted will be added to the database database after being reviewed.", "green");
                }
            break;
            case "edit":
                $sSubject =  $this->sName." has been modified by ".$_SESSION['current']->sRealname;
                addmsg("Application modified.", "green");
            break;
            case "delete":
                $sSubject = $this->sName." has been deleted by ".$_SESSION['current']->sRealname;
                addmsg("Application deleted.", "green");
            break;
        }
        $sEmail = get_notify_email_address_list($this->iAppId);
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
