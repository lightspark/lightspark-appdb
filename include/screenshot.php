<?php
/******************************************/
/* screenshot class and related functions */
/******************************************/

require_once(BASE."include/image.php");
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
    var $bQueued;
    var $iVersionId;
    var $iAppId;
    var $sUrl;
    var $sSubmitTime;
    var $iSubmitterId;

    /**    
     * Constructor, fetches the data and image objects if $iScreenshotId is given.
     */
    function Screenshot($iScreenshotId = null)
    {
        // we are working on an existing screenshot
        if(is_numeric($iScreenshotId))
        {
            $sQuery = "SELECT appData.*, appVersion.appId AS appId
                       FROM appData, appVersion 
                       WHERE appData.versionId = appVersion.versionId 
                       AND id = ".$iScreenshotId." 
                       AND type = 'image'";
            if($hResult = query_appdb($sQuery))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iScreenshotId = $iScreenshotId;
                $this->sDescription = $oRow->description;
                $this->oScreenshotImage = new Image("/data/screenshots/".$oRow->url);
                $this->oThumbnailImage = new Image("/data/screenshots/thumbnails/".$oRow->url);
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->sUrl = $oRow->url;
                $this->bQueued = ($oRow->queued=="true")?true:false;
                $this->sSubmitTime = $oRow->submitTime;
                $this->iSubmitterId = $oRow->submitterId;
           }
        }
    }
 

    /**
     * Creates a new screenshot.
     */
    function create($iVersionId = null, $sDescription = null, $hFile = null)
    {
        $oVersion = new Version($iVersionId);
        // Security, if we are not an administrator or a maintainer, the screenshot must be queued.
        if(!($_SESSION['current']->hasPriv("admin") || $_SESSION['current']->isMaintainer($oVersion->iVersionId) || $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
        {
            $this->bQueued = true;
        } else
        {
            $this->bQueued = false;
        }

        $aInsert = compile_insert_string(array( 'versionId'    => $iVersionId,
                                                'type'         => "image",
                                                'description'  => $sDescription,
                                                'queued'       => $this->bQueued?"true":"false",
                                                'submitterId'  => $_SESSION['current']->iUserId ));
        $sFields = "({$aInsert['FIELDS']})";
        $sValues = "({$aInsert['VALUES']})";

        if(query_appdb("INSERT INTO appData $sFields VALUES $sValues", "Error while creating a new screenshot."))
        {
            $this->iScreenshotId = mysql_insert_id();

            /* make sure we supply the full path to move_uploaded_file() */
            $moveToPath = appdb_fullpath("data/screenshots/originals/").$this->iScreenshotId;
            if(!move_uploaded_file($hFile['tmp_name'], $moveToPath))
            {
                // whoops, moving failed, do something
                addmsg("Unable to move screenshot from '".$hFile['tmp_name']."' to '".$moveToPath."'", "red");
                $sQuery = "DELETE
                           FROM appData 
                           WHERE id = '".$this->iScreenshotId."'";
                query_appdb($sQuery);
                return false;
            } else // we managed to copy the file, now we have to process the image
            {
                $this->sUrl = $this->iScreenshotId;
                if($this->generate())
                {
                    // we have to update the entry now that we know its name
                    $sQuery = "UPDATE appData 
                               SET url = '".$this->iScreenshotId."' 
                               WHERE id = '".$this->iScreenshotId."'";
                    if (!query_appdb($sQuery)) return false;
                } else
                {
                    addmsg("Unable to generate image or thumbnail. The file format might not be recognized. Please use PNG or JPEG only.","red");
                    $sQuery = "DELETE
                               FROM appData 
                               WHERE id = '".$this->iScreenshotId."'";
                    query_appdb($sQuery);
                    return false;
                }
                 
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
        /* the user object should delete the app data entry */
        /* we can perform better permissions checking there */
        if($_SESSION['current']->deleteAppData($this->iScreenshotId))
        {
            $this->oScreenshotImage->delete();
            $this->oThumbnailImage->delete();
            unlink(appdb_fullpath("/data/screenshots/originals/".$this->iScreenshotId));
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

        $sUpdate = compile_update_string(array('queued' => "false"));
        if(query_appdb("UPDATE appData SET ".$sUpdate." WHERE id=".$this->iScreenshotId))
        {
            $this->bQueued = false;
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
        $sQuery = "UPDATE id SET description = '".$sDescription."' WHERE id = ".$this->iScreenshotId." AND type = 'image'";   
        if($hResult = query_appdb($sQuery))
            $this->sDescription = $sDescription;
    }

    
    /**
     * This method generates a watermarked screenshot and thumbnail from the original file.
     * Usefull when changing thumbnail, upgrading GD, adding an image, etc.
     * Return false if an image could not be loaded.
     */
    function generate() 
    {
        global $watermark;
        // first we will create the thumbnail
        // load the screenshot
        $this->oThumbnailImage  = new Image("/data/screenshots/originals/".$this->sUrl);
        if(!$this->oThumbnailImage->isLoaded()) 
        {
            $this->oThumbnailImage->delete(); // if we cannot load the original file we delete it from the filesystem
            return false;
        }
        $this->oThumbnailImage->make_thumb(0,0,1,'#000000');
        // store the image
        $this->oThumbnailImage->output_to_file(appdb_fullpath("/data/screenshots/thumbnails/".$this->sUrl));
            
        // now we'll process the screenshot image for watermarking
        // load the screenshot
        $this->oScreenshotImage  = new Image("/data/screenshots/originals/".$this->sUrl);
        if(!$this->oScreenshotImage->isLoaded()) return false;
        // resize the image
        $this->oScreenshotImage->make_full();
        // store the resized image
        $this->oScreenshotImage->output_to_file(appdb_fullpath("/data/screenshots/".$this->sUrl));
        // reload the resized screenshot
        $this->oScreenshotImage  = new Image("/data/screenshots/".$this->sUrl);
        if(!$this->oScreenshotImage->isLoaded()) return false;

        // add the watermark to the screenshot
        $this->oScreenshotImage->add_watermark($watermark->get_image_resource());
        // store the watermarked image
        $this->oScreenshotImage->output_to_file(appdb_fullpath("/data/screenshots/".$this->sUrl));
         
        return true;
    }


    function mailSubmitter($bRejected=false)
    {
        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted screenshot accepted";
                $sMsg  = "The screenshot you submitted for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted screenshot rejected";
                 $sMsg  = "The screenshot you submitted for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." has been rejected.";
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
    if($iAppId && !$iVersionId)
    {
       $hResult = query_appdb("SELECT appData.*, RAND() AS rand 
                               FROM appData, appVersion 
                               WHERE appData.versionId = appVersion.versionId
                               AND appVersion.appId = $iAppId 
                               AND type = 'image' 
                               AND appData.queued = 'false' 
                               ORDER BY rand");
    } else if ($iVersionId) // we want a random screenshot for this version
    {
        $hResult = query_appdb("SELECT *, RAND() AS rand 
                                FROM appData 
                                WHERE versionId = $iVersionId 
                                AND type = 'image' 
                                AND queued = 'false' 
                                ORDER BY rand");
    }
    if(!$hResult || !mysql_num_rows($hResult))
    {
        $sImgFile = '<center><img src="'.appdb_fullpath("images/no_screenshot.png").'" alt="No Screenshot" /></center>';
    } else
    {
        $oRow = mysql_fetch_object($hResult);
        $sImgFile = '<center><img src="appimage.php?thumbnail=true&amp;id='.$oRow->id.'" alt="'.$oRow->description.'" /></center>';
    }
    
    $sImg = html_frame_start("",'128','',2);

    /* we have screenshots */
    if(mysql_num_rows($hResult))
    {
        if($iVersionId)
            $sImg .= "<a href='screenshots.php?appId=$iAppId&amp;versionId=$iVersionId'>$sImgFile<center>View/Submit&nbsp;Screenshot</center></a>";
        else
            $sImg .= "<a href='screenshots.php?appId=$iAppId&amp;versionId=$iVersionId'>$sImgFile<center>View&nbsp;Screenshot</center></a>";
    } else if($iVersionId) /* we are asking for a specific app version but it has no screenshots */
    {
        $sImg .= "<a href='screenshots.php?appId=$iAppId&amp;versionId=$iVersionId'>$sImgFile<center>Submit&nbsp;Screenshot</center></a>";
    } else /* we have no screenshots and we aren't a specific version, we don't allow adding screenshots for an app */
    {
        $sImg .= $sImgFile; 
    }
    $sImg .= html_frame_end()."<br />";
    
    return $sImg;
}

function get_screenshots($iAppId = null, $iVersionId = null, $bQueued = "false")
{
    /*
     * We want all screenshots for this app.
     */
    if($iAppId && !$iVersionId)
    {
        $sQuery = "SELECT appData.*, appVersion.appId as appId
                   FROM appData, appVersion
                   WHERE appVersion.versionId = appData.versionId
                   AND type = 'image'
                   AND appVersion.appId = ".$iAppId."
                   AND appData.queued = '".$bQueued."'";
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
                   AND appData.versionId = ".$iVersionId."
                   AND appData.queued = '".$bQueued."'";
    }
    if($sQuery)
    {
        $hResult = query_appdb($sQuery);
        return $hResult;
    }
    return false;
}

function get_thumbnail($id)
{
    $oScreenshot = new Screenshot($id);

    // generate random tag for popup window
    $randName = generate_passwd(5);
    // set img tag        
    $imgSRC  = '<img src="'.apidb_fullurl("appimage.php").
               '?thumbnail=true&id='.$id.'" alt="'.$oScreenshot->sDescription.
               '" width="'.$oScreenshot->oThumnailImage->width.
               '" height="'.$oScreenshot->oThumnailImage->height.'">';
    $img = '<a href="'.apidb_fullurl("appimage.php").
           '?id='.$id.
           '" onclick="javascript:openWin(\''.apidb_fullurl("appimage.php").
           '?id='.$id.'\',\''.$randName.'\','.
           $oScreenshot->oScreenshotImage->width.','.
           ($oScreenshot->oScreenshotImage->height+4).
           ');return false;">'.$imgSRC.'</a>';

    // set image link based on user pref
    if ($_SESSION['current']->isLoggedIn())
    {
        if ($_SESSION['current']->getpref("window:screenshot") == "no")
        {
            $img = '<a href="'.apidb_fullurl("appimage.php").
                   '?imageId='.$id.'">'.$imgSRC.'</a>';
        }
    }
    return $img;
}
?>
