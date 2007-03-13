<?php
/******************************************/
/* screenshot class and related functions */
/******************************************/

require_once(BASE."include/util.php");
require_once(BASE."include/image.php");

// load the watermark
$watermark = new Image("/images/watermark.png");

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
            $hResult = query_parameters("SELECT appData.*, appVersion.appId AS appId
                       FROM appData, appVersion 
                       WHERE appData.versionId = appVersion.versionId 
                       AND id = '?'
                       AND type = 'screenshot'", $iScreenshotId);
            if($hResult)
            {
                $oRow = mysql_fetch_object($hResult);
                if($oRow)
                {
                    $this->iScreenshotId = $iScreenshotId;
                    $this->sDescription = $oRow->description;
                    $this->iAppId = $oRow->appId;
                    $this->iVersionId = $oRow->versionId;
                    $this->sUrl = $oRow->url;
                    $this->bQueued = ($oRow->queued=="true")?true:false;
                    $this->sSubmitTime = $oRow->submitTime;
                    $this->iSubmitterId = $oRow->submitterId;
                }
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

        $hResult = query_parameters("INSERT INTO appData (versionId, type, description, queued, submitterId) ".
                                    "VALUES('?', '?', '?', '?', '?')",
                                    $iVersionId, "screenshot", $sDescription, $this->bQueued?"true":"false",
                                    $_SESSION['current']->iUserId);
        if($hResult)
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
                           WHERE id = '?'";
                query_parameters($sQuery, $this->iScreenshotId);
                return false;
            } else // we managed to copy the file, now we have to process the image
            {
                $this->sUrl = $this->iScreenshotId;
                if($this->generate())
                {
                    // we have to update the entry now that we know its name
                    $sQuery = "UPDATE appData 
                               SET url = '?' 
                               WHERE id = '?'";
                    if (!query_parameters($sQuery, $this->iScreenshotId, $this->iScreenshotId)) return false;
                } else
                {
                    addmsg("Unable to generate image or thumbnail. The file format might not be recognized. Please use PNG or JPEG only.","red");
                    $sQuery = "DELETE
                               FROM appData 
                               WHERE id = '?'";
                    query_parameters($sQuery, $this->iScreenshotId);
                    return false;
                }
            }

            $this->screenshot($this->iScreenshotId,$this->bQueued);
            $this->mailMaintainers();
            return true;
        }
        else
        {
            addmsg("Error while creating a new screenshot.", "red");
            return false;
        }
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
            /* make sure the screenshot and thumbnail is loaded */
            /* up before we try to delete them */
            $this->load_image(true);
            $this->load_image(false);

            $this->oScreenshotImage->delete();
            $this->oThumbnailImage->delete();
            unlink(appdb_fullpath("/data/screenshots/originals/".$this->iScreenshotId));
            if(!$bSilent)
                $this->mailMaintainers(true);
        }
        if($this->iSubmitterId && ($this->iSubmitterId != $_SESSION['current']->iUserId))
        {
            $this->mailSubmitter(true);
        }
    }

    function reject()
    {
        $this->delete();
    }

    /**
     * Move screenshot out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the screenshot out of the queue.
        if(!$this->bQueued)
            return false;

        if(query_parameters("UPDATE appData SET queued = '?' WHERE id='?'",
                            "false", $this->iScreenshotId))
        {
            $this->bQueued = false;
            // we send an e-mail to interested people
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
        if($hResult = query_parameters("UPDATE id SET description = '?' WHERE id = '?' AND type = 'screenshot'",
                                       $sDescription, $this->iScreenshotId))
            $this->sDescription = $sDescription;
    }

    
    /**
     * This method generates a watermarked screenshot and thumbnail from the original file.
     * Useful when changing thumbnail, upgrading GD, adding an image, etc.
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

    /* ensure that either the thumbnail or screenshot */
    /* has been loaded into memory */
    function load_image($bThumbnail)
    {
        if($bThumbnail)
        {
            /* if we haven't loaded the thumbnail up yet, do so */
            if(!$this->oThumbnailImage)
                $this->oThumbnailImage = new Image("/data/screenshots/thumbnails/".$this->sUrl);
        } else
        {
            /* if we haven't loaded the screenshot up yet, do so */
            if(!$this->oScreenshotImage)
                $this->oScreenshotImage = new Image("/data/screenshots/".$this->sUrl);
        }
    }

    /* output the thumbnail if $bThumbnail or the full screenshot if !$bThumbnail */
    /* NOTE: use this instead of calling through to this classes oScreenshot or */
    /*       oThumbnail objects directly to their output_*() functions */
    function output_screenshot($bThumbnail)
    {
        $this->load_image($bThumbnail);

        if($bThumbnail)
        {
            if($this->oThumbnailImage)
                $this->oThumbnailImage->output_to_browser(1);
        } else
        {
            if($this->oScreenshotImage)
                $this->oScreenshotImage->output_to_browser(1);
        }
    }

    /* Accessor functions for the screenshot and thumbnail images that this */
    /* screenshot object encapsulates */
    /* NOTE: DO NOT call like $oScreenshot->oScreenshotImage->get_width(), there is NO */
    /*       guarantee that oScreenshotImage will be valid */
    function get_screenshot_width()
    {
        $this->load_image(false);
        return $this->oScreenshotImage->get_width();
    }

    function get_screenshot_height()
    {
        $this->load_image(false);
        return $this->oScreenshotImage->get_height();
    }

    function get_thumbnail_width()
    {
        $this->load_image(true);
        return $this->oThumbnailImage->get_width();
    }

    function get_thumbnail_height()
    {
        $this->load_image(true);
        return $this->oThumbnailImage->get_height();
    }


    function mailSubmitter($bRejected=false)
    {
        global $aClean;

        if($this->iSubmitterId)
        {
            $sAppName = Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId);
            $oSubmitter = new User($this->iSubmitterId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted screenshot accepted";
                $sMsg  = "The screenshot you submitted for ".$sAppName." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted screenshot rejected";
                 $sMsg  = "The screenshot you submitted for ".$sAppName." has been rejected.";
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function mailMaintainers($bDeleted=false)
    {
        $sAppName = Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId);
        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Screenshot for ".$sAppName." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This screenshot has been submitted by ".$oSubmitter->sRealname.".";
                    $sMsg .= "\n";
                }
                addmsg("The screenshot was successfully added into the database.", "green");
            } else // Screenshot queued.
            {
                $sSubject = "Screenshot for ".$sAppName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
                $sMsg .= "This screenshot has been queued.";
                $sMsg .= "\n";
                addmsg("The screenshot you submitted will be added to the database database after being reviewed.", "green");
            }
        } else // Screenshot deleted.
        {
            $sSubject = "Screenshot for ".$sAppName." deleted by ".$_SESSION['current']->sRealname;
            $sMsg  = APPDB_ROOT."appview.php?iVersionId=".$this->iVersionId."\n";
            addmsg("Screenshot deleted.", "green");
        }

        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
            mail_appdb($sEmail, $sSubject ,$sMsg);
    } 

    function get_zoomicon_overlay()
    {
        /* if the user is using mozilla or firefox show the zoom icon over images */
        /* otherwise because IE doesn't support transparent PNGs or proper css we have to */
        /* skip it for IE */
        if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") === false)
        {
            $sZoomIcon = '<img class="zoom_overlay" src="'.BASE.'images/xmag_32.png">';
        }
        else
            $sZoomIcon = "";

        return $sZoomIcon;
    }

    /**
     * Get a random image for a particular version of an app.
     * If the version is not set, get a random app image 
     *
     * $bFormatting == false turns off all extranious formatting applied to the returned image html
     */
    function get_random_screenshot_img($iAppId = null, $iVersionId = null,
                                       $bFormatting = true) 
    {
        // we want a random screenshots for this app
        if($iAppId && !$iVersionId)
        {
            $hResult = query_parameters("SELECT appData.id, appData.description, RAND() AS rand 
                               FROM appData, appVersion 
                               WHERE appData.versionId = appVersion.versionId
                               AND appVersion.appId = '?' 
                               AND type = 'screenshot' 
                               AND appData.queued = 'false' 
                               ORDER BY rand", $iAppId);
        } else if ($iVersionId) // we want a random screenshot for this version
        {
            $hResult = query_parameters("SELECT id, description, RAND() AS rand 
                                FROM appData 
                                WHERE versionId = '?' 
                                AND type = 'screenshot' 
                                AND queued = 'false' 
                                ORDER BY rand", $iVersionId);
        }

        if($bFormatting)
            $sImgFile .= '<center>';

        if(!$hResult || !mysql_num_rows($hResult))
        {
            $sImgFile.= '<img src="images/no_screenshot.png" alt="No Screenshot" />';
        } else
        {
            $oRow = mysql_fetch_object($hResult);
            $sImgFile.= '<img src="appimage.php?bThumbnail=true&amp;iId='.$oRow->id.'" alt="'.$oRow->description.'" />';
        }

        if($bFormatting)
            $sImgFile .= '</center>';

        if($bFormatting)
            $sImg = html_frame_start("",'128','',2);

        /* retrieve the url for the zoom icon overlay */
        $sZoomIcon = Screenshot::get_zoomicon_overlay();

        /* we have screenshots */
        if(mysql_num_rows($hResult))
        {
            if($iVersionId)
                $sImg .= "<a href='screenshots.php?iAppId=$iAppId&amp;iVersionId=$iVersionId'>".$sImgFile.$sZoomIcon."<center>View/Submit&nbsp;Screenshot</center></a>";
            else
                $sImg .= "<a href='screenshots.php?iAppId=$iAppId&amp;iVersionId=$iVersionId'>".$sImgFile.$sZoomIcon."<center>View&nbsp;Screenshot</center></a>";
        } else if($iVersionId) /* we are asking for a specific app version but it has no screenshots */
        {
            $sImg .= "<a href='screenshots.php?iAppId=$iAppId&amp;iVersionId=$iVersionId'>".$sImgFile.$sZoomIcon."<center>Submit&nbsp;Screenshot</center></a>";
        } else /* we have no screenshots and we aren't a specific version, we don't allow adding screenshots for an app */
        {
            $sImg .= $sImgFile.$sZoomIcon; 
        }

        if($bFormatting)
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
            $hResult = query_parameters("SELECT appData.*, appVersion.appId as appId
                                 FROM appData, appVersion
                                 WHERE appVersion.versionId = appData.versionId
                                 AND type = 'screenshot'
                                 AND appVersion.appId = '?'
                                 AND appData.queued = '?'", $iAppId, $bQueued);
        }
        /*
         * We want all screenshots for this version.
         */
        else if ($iVersionId) 
        {
            $hResult = query_parameters("SELECT appData.*, appVersion.appId as appId
                                 FROM appData, appVersion
                                 WHERE appVersion.versionId = appData.versionId
                                 AND type = 'screenshot'
                                 AND appData.versionId = '?'
                                 AND appData.queued = '?'", $iVersionId, $bQueued);
        } else
        {
            return false;
        }

        return $hResult;
    }

    function get_thumbnail_img()
    {
        // generate random tag for popup window
        $sRandName = User::generate_passwd(5);
        // set img tag        
        $shImgSRC  = '<img src="'.apidb_fullurl("appimage.php").
            '?bThumbnail=true&iId='.$this->iScreenshotId.'" alt="'.$this->sDescription.
            '" width="'.$this->get_thumbnail_width().
            '" height="'.$this->get_thumbnail_height().'">';
        $shImg = '<a href="'.apidb_fullurl("appimage.php").
            '?iId='.$this->iScreenshotId.
            '" onclick="javascript:openWin(\''.apidb_fullurl("appimage.php").
            '?iId='.$this->iScreenshotId.'\',\''.$sRandName.'\','.
            ($this->get_screenshot_width() + 20).','.
            ($this->get_screenshot_height() + 6).
            ');return false;">'.$shImgSRC.Screenshot::get_zoomicon_overlay().'</a>';

        // set image link based on user pref
        if ($_SESSION['current']->isLoggedIn())
        {
            if ($_SESSION['current']->getpref("window:screenshot") == "no")
            {
                $shImg = '<a href="'.apidb_fullurl("appimage.php").
                    '?iImageId='.$this->iScreenshotId.'">'.$shImgSRC.'</a>';
            }
        }
        return $shImg;
    }

    function objectGetEntries($bQueued, $iRows = 0, $iStart = 0)
    {
        return appData::objectGetEntries($bQueued, $iRows, $iStart, "screenshot");
    }

    function objectOutputHeader($sClass)
    {
        return appData::objectOutputHeader($sClass, "screenshot");
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin") ||
           maintainer::isUserMaintainer($_SESSION['current']))
            return TRUE;
        else
            return FALSE;
    }

    function objectGetInstanceFromRow($oRow)
    {
        return new appData($oRow->id, $oRow);
    }

    /* arg1 = OM object, arg2 = CSS style, arg3 = text for edit link */
    function objectOutputTableRow($oObject, $sClass, $sEditLinkLabel)
    {
        $oAppData = new AppData();
        $oAppData->objectOutputTableRow($oObject, $sClass, $sEditLinkLabel);
    }

    function objectDisplayQueueProcessingHelp()
    {
        return appData::objectDisplayQueueProcessingHelp();
    }

    function outputEditor()
    {
        $oAppData = new appData($this->iScreenshotId);
        $oAppData->outputEditorGeneric();

        echo '<tr valign=top><td class=color0><b>Submited screenshot</b></td>',"\n";
        echo '<td>';
        $imgSRC = '<img width="'.$this->get_thumbnail_width().'" height="'.
                $this->get_thumbnail_height().'" src="'.BASE.
                'appimage.php?bQueued=true&iId='.$this->iScreenshotId.'" />';
        // generate random tag for popup window
        $randName = User::generate_passwd(5);
        // set image link based on user pref
        $img = '<a href="javascript:openWin(\''.BASE.'appimage.php?bQueued=true&iId='.
                $this->iScreenshotId.'\',\''.$randName.'\','.$this->get_screenshot_width()
                .','.($this->get_screenshot_height()+4).');">'.$imgSRC.'</a>';
        if ($_SESSION['current']->isLoggedIn())
        {
            if ($_SESSION['current']->getpref("window:screenshot") == "no")
            {
                $img = '<a href="'.BASE.'appimage.php?bQueued=true&iId='.
                        $this->iScreenshotId.'">'.$imgSRC.'</a>';
            }
        }
        echo $img;
        echo '</td></tr>',"\n";
        echo '<input type="hidden" name="iScreenshotId" value="'.
                $this->iScreenshotId.'" />';
        echo html_frame_end();
    }

    function getOutputEditorValues($aClean)
    {
        /* STUB: No update possible, reply text fetched from $aClean */
        return TRUE;
    }

    function update()
    {
        /* STUB: No updating possible at the moment */
        return TRUE;
    }

    function objectHideDelete()
    {
        return TRUE;
    }

    function getDefaultReply()
    {
        return appData::getDefaultReply();
    }
}

?>
