<?php
/******************************************/
/* screenshot class and related functions */
/******************************************/

require(BASE."include/"."image.php");
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

    /**    
     * constructor, fetches the description and creates the Image objects and files if needed.
     */
    function Screenshot($iScreenshotId,$bQueued = false,$iUserId = null,$iAppId = null,$iVersionId = null,$sDescription = null,$hFile = null)
    {
        if($bQueued)
        {
            $this->sTable = appDataQueue;
            $this->sTableId = queueId;
            $this->iUserId = $userId;
            $this->sDirectory = "queued/screenshots";
        } else
        {
            $this->sTable = appData;          
            $this->sTableId = id;
            $this->sDirectory = "screenshots";
        }

        // we are working on an existing screenshot
        if($iScreenshotId)
        {
            $this->iScreenshotId = $iScreenshotId;
            $sQuery = "SELECT * FROM ".$this->sTable." WHERE ".$this->sTableId." = ".$this->iScreenshotId." AND type = 'image'";
            if($hResult = query_appdb($sQuery))
            {
                $oRow = mysql_fetch_object($hResult);
                $this->iScreenshotId = $oRow->id;
                $this->sDescription = $oRow->description;
                $this->oScreenshotImage = new Image("/data/".$this->sDirectory."/".$oRow->url);
                $this->oThumbnailImage = new Image("/data/".$this->sDirectory."/thumbnails/".$oRow->url);
                $this->sSubmitTime = $oRow->submitTime;
                $this->iAppId = $oRow->appId;
                $this->iVersionId = $oRow->versionId;
                $this->sUrl = $oRow->url;
           }
        } else // we are working on a non-existing screenshot
        {
            $this->sDescription = $sDescription;
            if($bQueued)
                $sQuery = "INSERT INTO $this->sTable VALUES (null, ".$iAppId.", ".$iVersionId.", 'image', '".addslashes($this->sDescription)."', '','".$_SESSION['current']->userid."', NOW())";
            else
                $sQuery = "INSERT INTO $this->sTable VALUES (null, ".$iAppId.", ".$iVersionId.", 'image', '".addslashes($this->sDescription)."', '')";
            if (query_appdb($sQuery))
            {
                $this->iScreenshotId = mysql_insert_id();
            }
            else return false;
            if(!rename($hFile['tmp_name'], "data/".$this->sDirectory."/originals/".$this->iScreenshotId))
            {
                // whoops, moving failed, do something
                addmsg("Unable to move screenshot from ".$hFile['tmp_name']." to data/".$this->sDirectory."/originals/".$this->iScreenshotId, "red");
                $sQuery = "DELETE FROM ".$this->sTable." WHERE ".$this->sTableId." = '".$this->iScreenshotId."'";
                query_appdb($sQuery);
                return false;
            } else // we managed to copy the file, now we have to process the image
            {   
                $this->sUrl = $this->iScreenshotId;
                $this->generate();
                // we have to update the entry now that we know its name
                $sQuery = "UPDATE ".$this->sTable." SET url = '".$this->iScreenshotId."' WHERE ".$this->sTableId." = '".$this->iScreenshotId."'";
                if (!query_appdb($sQuery)) return false;
            }
        }
    }

    /**    
     * delete the screenshot from the database 
     * and request it's deletion from the filesystem (including the thumbnail).
     */
    function delete()
    {
        $sQuery = "DELETE FROM ".$this->sTable." WHERE ".$this->sTableId." = ".$this->iScreenshotId." AND type = 'image' LIMIT 1";
        if($hResult = query_appdb($sQuery))
        {
            $this->oScreenshotImage->delete();
            $this->oThumbnailImage->delete();
            unlink($_SERVER['DOCUMENT_ROOT']."/data/".$this->sDirectory."/originals/".$this->iScreenshotId);
        }
    }

    /**
     * clean up the memory
     */
    function free() 
    {
        $this->oScreenshotImage->destroy();
        $this->oThumbnailImage->destroy();
    }

    /**
     * sets the screenshot description.
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

}


/*
 * Screenshot functions that are not part of the class
 */

/**
 * Get a random image for a particular version of an app.
 * If the version is not set, get a random app image 
 */
function get_screenshot_img($appId, $versionId="") 
{
    if($versionId) 
    {
        $result = query_appdb("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND versionId = $versionId AND type = 'image' ORDER BY rand");
    }
    else {
       $result = query_appdb("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND type = 'image' ORDER BY rand");
    }
    
    if(!$result || !mysql_num_rows($result))
    {
        $imgFile = "<img src='".BASE."images/no_screenshot.png' alt='No Screenshot' />";
    }
    else
    {
        $ob = mysql_fetch_object($result);
        $imgFile = "<img src=\"appimage.php?thumbnail=true&id=".$ob->id."\" ".
                   "alt=\"".$ob->description."\" />";
    }
    
    $img = html_frame_start("",'128','',2);
    if($versionId || mysql_num_rows($result))
        $img .= "<a href='screenshots.php?appId=$appId&versionId=$versionId'>$imgFile</a>";
    else // no link for adding app screenshot as screenshots are linked to versions
        $img .= $imgFile; 
    $img .= html_frame_end()."<br />";
    
    return $img;
}
?>
