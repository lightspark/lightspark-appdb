<?php
/******************************************/
/* screenshot class and related functions */
/******************************************/

require(BASE."include/image.php");
// load the watermark
$watermark = new image("/images/watermark.png");

/**
 * Screenshot class for handling screenshots and thumbnails
 */
class Screenshot {
    var $iScreenshotId;
    var $sDescription;
    var $oScreenshotImage;
    var $oThumbnailImage;
    var $sTable;
    var $sTableId;
    var $userId;
    var $bQueued;
    var $iVersionId;
    var $iAppId;
    var $sDirectory;
    var $sUrl;
    var $iSubmitterId;

    /**    
     * Constructor, fetches the data and image objects if $iScreenshotId is given.
     */
    function Screenshot($iScreenshotId = null,$bQueued = false)
    {
        if($bQueued)
        {
            $this->bQueued = true;
            $this->sTable = appDataQueue;
            $this->sTableId = queueId;
            $this->sDirectory = "queued/screenshots";
        } else
        {
            $this->bQueued = false;
            $this->sTable = appData;          
            $this->sTableId = id;
            $this->sDirectory = "screenshots";
        }

        // we are working on an existing screenshot
        if($iScreenshotId)
        {
            $sQuery = "SELECT ".$this->sTable.".*, appVersion.appId AS appId
                       FROM ".$this->sTable.", appVersion 
                       WHERE ".$this->sTable.".versionId = appVersion.versionId 
                       AND ".$this->sTableId." = ".$iScreenshotId." 
                       AND type = 'image'";
            if($hResult = query_appdb($sQuery))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iScreenshotId = $iScreenshotId;
                $this->sDescription = $oRow->description;
                $this->oScreenshotImage = new Image("/data/".$this->sDirectory."/".$oRow->url);
                $this->oThumbnailImage = new Image("/data/".$this->sDirectory."/thumbnails/".$oRow->url);
                $this->sSubmitTime = $oRow->submitTime;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->sUrl = $oRow->url;
                if(!$this->iSubmitterId && $oRow->userId)
                    $this->iSubmitterId = $oRow->userId;
           }
        }
    }
 

    /**
     * Creates a new screenshot.
     */
    function create($iVersionId = null, $sDescription = null, $hFile = null)
    {

        $aInsert = compile_insert_string(array( 'versionId'    => $iVersionId,
                                                'type'         => "image",
                                                'description'  => $sDescription ));

        // Security, if we are not an administrator or a maintainer, the screenshot must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($_REQUEST['versionId'])))
        {
            $this->bQueued = true;
            $this->sTable = appDataQueue;
            $this->sTableId = queueId;
            $this->iUserId = $userId;
            $this->sDirectory = "queued/screenshots";
            $sFields = "({$aInsert['FIELDS']}, userId)";
            $sValues = "({$aInsert['VALUES']}, '".$_SESSION['current']->iUserId."')";
        } else
        {
            $sFields = "({$aInsert['FIELDS']})";
            $sValues = "({$aInsert['VALUES']})";
        }

        if(query_appdb("INSERT INTO ".$this->sTable." $sFields VALUES $sValues", "Error while creating a new screenshot."))
        {
            $this->iScreenshotId = mysql_insert_id();
            if(!move_uploaded_file($hFile['tmp_name'], "data/".$this->sDirectory."/originals/".$this->iScreenshotId))
            {

                // whoops, moving failed, do something
                addmsg("Unable to move screenshot from ".$hFile['tmp_name']." to data/".$this->sDirectory."/originals/".$this->iScreenshotId, "red");
                $sQuery = "DELETE
                           FROM ".$this->sTable." 
                           WHERE ".$this->sTableId." = '".$this->iScreenshotId."'";
                query_appdb($sQuery);
                return false;
            } else // we managed to copy the file, now we have to process the image
            {   
                $this->sUrl = $this->iScreenshotId;
                $this->generate();
                // we have to update the entry now that we know its name
                $sQuery = "UPDATE ".$this->sTable." 
                           SET url = '".$this->iScreenshotId."' 
                           WHERE ".$this->sTableId." = '".$this->iScreenshotId."'";
                if (!query_appdb($sQuery)) return false;
            }

            $this->screenshot($this->iScreenshotId,$this->bQueued);
            $this->mailMaintainers();
            return true;
        }
        else
            return false;
    }


    /**    
     * Deletes the screenshot from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     */
    function delete($bSilent=false)
    {
        $sQuery = "DELETE FROM ".$this->sTable." 
                   WHERE ".$this->sTableId." = ".$this->iScreenshotId." 
                   AND type = 'image' 
                   LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            $this->oScreenshotImage->delete();
            $this->oThumbnailImage->delete();
            unlink($_SERVER['DOCUMENT_ROOT']."/data/".$this->sDirectory."/originals/".$this->iScreenshotId);
            if(!$bSilent)
                $this->mailMaintainers(true);
        }
        if($this->iSubmitterId)
        {
            $this->mailSubmitter(true);
        }
    }


    /**
     * Move screenshot out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the screenshot out of the queue.
        if(!$this->bQueued)
            return false;
     
        $aInsert = compile_insert_string(array( 'versionId'    => $this->iVersionId,
                                                'type'         => "image",
                                                'description'  => $this->$sDescription ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";
        if(query_appdb("INSERT INTO appData $sFields VALUES $sValues", "Error while unqueueing a screenshot."))
        {
            $iId = mysql_insert_id();

            // we move the content in the live directory
            copy("../data/queued/screenshots/".$this->iScreenshotId, "../data/screenshots/".$iId);
            copy("../data/queued/screenshots/originals/".$this->iScreenshotId, "../data/screenshots/originals/".$iId);
            copy("../data/queued/screenshots/thumbnails/".$this->iScreenshotId, "../data/screenshots/thumbnails/".$iId);

            // now that we know the url of the screenshot we can update the database
            $sQuery = "UPDATE appData 
                       SET url = '".$iId."' 
                       WHERE id = '".$iId."'";
            query_appdb($sQuery);

            // we have to delete the queued entry
            $this->delete(true);
             
            // we fetch the new unqueued entry
            $this->screenshot($iId);

            // we send an e-mail to intersted people
            $this->mailSubmitter();
            $this->mailMaintainers();

            // the screenshot has been unqueued
            addmsg("The screenshot has been unqueued.", "green");
        }
    }


    /**
     * Cleans up the memory.
     */
    function free() 
    {
        if($this->oScreenshotImage)
            $this->oScreenshotImage->destroy();
        if($this->oThumbnailImage)
            $this->oThumbnailImage->destroy();
    }


    /**
     * Sets the screenshot description.
     */
    function setDescription($sDescription)
    {
        $sQuery = "UPDATE ".$this->sTableId." SET description = '".$sDescription."' WHERE ".$this->sTableId." = ".$this->iScreenshotId." AND type = 'image'";   
        if($hResult = query_appdb($sQuery))
            $this->sDescription = $sDescription;
    }

    
    /**
     * This method generates a watermarked screenshot and thumbnail from the original file.
     * Usefull when changing thumbnail, upgrading GD, adding an image, etc.
     */
    function generate() 
    {
        global $watermark;
        // first we will create the thumbnail
        // load the screenshot
        $this->oThumbnailImage  = new Image("/data/".$this->sDirectory."/originals/".$this->sUrl);
        $this->oThumbnailImage->make_thumb(0,0,1,'#000000');
        // store the image
        $this->oThumbnailImage->output_to_file($_SERVER['DOCUMENT_ROOT']."/data/".$this->sDirectory."/thumbnails/".$this->sUrl);
            
        // now we'll process the screenshot image for watermarking
        // load the screenshot
        $this->oScreenshotImage  = new Image("/data/".$this->sDirectory."/originals/".$this->sUrl);
        // resize the image
        $this->oScreenshotImage->make_full();
        // store the resized image
        $this->oScreenshotImage->output_to_file($_SERVER['DOCUMENT_ROOT']."/data/".$this->sDirectory."/".$this->sUrl);
        // reload the resized screenshot
        $this->oScreenshotImage  = new Image("/data/".$this->sDirectory."/".$this->sUrl);

        // add the watermark to the screenshot
        $this->oScreenshotImage->add_watermark($watermark->get_image_resource());
        // store the watermarked image
        $this->oScreenshotImage->output_to_file($_SERVER['DOCUMENT_ROOT']."/data/".$this->sDirectory."/".$this->sUrl);
    }


    function mailSubmitter($bRejected=false)
    {
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted screenshot accepted";
                $sMsg  = "The screenshot you submitted for ".lookup_app_name($this->appId)." ".lookup_version_name($this->versionId)." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted screenshot rejected";
                 $sMsg  = "The screenshot you submitted for ".lookup_app_name($this->appId)." ".lookup_version_name($this->versionId)." has been rejected.";
            }
            $sMsg .= $_REQUEST['replyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function mailMaintainers($bDeleted=false)
    {
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Screenshot for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This screenshot has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The screenshot was successfully added into the database.", "green");
            } else // Screenshot queued.
            {
                $sSubject = "Screenshot for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                $sMsg .= "This screenshot has been queued.";
                $sMsg .= "\n";
                addmsg("The screenshot you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Screenshot deleted.
        {
            $sSubject = "Screenshot for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
            addmsg("Screenshot deleted.", "green");
        }

        $sEmail = get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 
}


/*
 * Screenshot functions that are not part of the class
 */

/**
 * Get a random image for a particular version of an app.
 * If the version is not set, get a random app image 
 */
function get_screenshot_img($iAppId = null, $iVersionId = null) 
{
    // we want a random screenshots for this app
    if($iAppId)
    {
       $hResult = query_appdb("SELECT appData.*, RAND() AS rand 
                               FROM appData, appVersion 
                               WHERE appData.versionId = appVersion.versionId
                               AND appVersion.appId = $iAppId 
                               AND type = 'image' 
                               ORDER BY rand");
    } else if ($iVersionId) // we want a random screenshot for this version
    {
        $hResult = query_appdb("SELECT *, RAND() AS rand 
                                FROM appData 
                                WHERE versionId = $iVersionId 
                                AND type = 'image' 
                                ORDER BY rand");
    }
    if(!$hResult || !mysql_num_rows($hResult))
    {
        $sImgFile = '<img src="'.BASE.'images/no_screenshot.png" alt="No Screenshot" />';
    } else
    {
        $oRow = mysql_fetch_object($hResult);
        $sImgFile = '<img src="appimage.php?thumbnail=true&id='.$oRow->id.'" alt="'.$oRow->description.'" />';
    }
    
    $sImg = html_frame_start("",'128','',2);
    if($iVersionId || mysql_num_rows($hResult))
        $sImg .= "<a href='screenshots.php?appId=$iAppId&versionId=$iVersionId'>$sImgFile</a>";
    else // no link for adding app screenshot as screenshots are linked to versions
        $sImg .= $sImgFile; 
    $sImg .= html_frame_end()."<br />";
    
    return $sImg;
}

function get_screenshots($iAppId = null, $iVersionId = null)
{
    /*
     * We want all screenshots for this app.
     */
    if($iAppId)
    {
        $sQuery = "SELECT appData.*, appVersion.appId as appId
                   FROM appData, appVersion
                   WHERE appVersion.versionId = appData.versionId
                   AND type = 'image'
                   AND appId = ".$iAppId;
    }
    /*
     * We want all screenshots for this version.
     */
    else if ($iVersionId) 
    {
        $sQuery = "SELECT appData.*, appVersion.appId as appId
                   FROM appData, appVersion
                   WHERE appVersion.versionId = appData.versionId
                   AND type = 'image'
                   AND appData.versionId = ".$iVersionId;
    }
    if($sQuery)
    {
        $hResult = query_appdb($sQuery);
        return $hResult;
    }
    return false;
}
?>
