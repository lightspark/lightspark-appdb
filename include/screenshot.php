<?php
/********************************/
/* screenshot related functions */
/********************************/

/**
 * Get a random image for a particular version of an app.
 * If the version is not set, get a random app image 
 */
function get_screenshot_img($appId, $versionId="") 
{
    if($versionId) 
    {
        $result = mysql_query("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND versionId = $versionId AND type = 'image' ORDER BY rand");
    }
    else {
       $result = mysql_query("SELECT *, RAND() AS rand FROM appData WHERE appId = $appId AND type = 'image' ORDER BY rand");
    }
    
    if(!$result || !mysql_num_rows($result))
    {
        $imgFile = "<img src='".BASE."images/no_screenshot.gif' alt='No Screenshot' />";
    }
    else
    {
        $ob = mysql_fetch_object($result);
        $imgFile = "<img src='appimage.php?imageId=$ob->id&width=128&height=128' ".
                   "alt='$ob->description' />";
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
