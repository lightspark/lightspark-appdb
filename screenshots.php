<?

include("path.php");
require(BASE."include/"."incl.php");

global $current;

/*=========================================================================
 *
 * this script expects appId and versionId as arguments
 *
 * OR
 *
 * cmd and imageId
 */


if($cmd)
{
    if(havepriv("admin") || 1) //FIXME should check ownsApp() again
	{
	    if($cmd == "delete")
		{
		    $result = mysql_query("DELETE FROM appData WHERE id = $imageId");
		    if($result)
			addmsg("Image deleted", "green");
		    else
			addmsg("Failed to delete image: ".mysql_error(), "red");
		    redirectref();
		    exit;
		}
	}
    exit;
}

$result = mysql_query("SELECT * FROM appData WHERE type = 'image' AND appId = $appId AND versionId = $versionId");
if(!$result || !mysql_num_rows($result))
{
    errorpage("No Screenshots Found","There are no screenshots currently linked to this application.");
    exit;
}
else
{

    apidb_header("Screenshots");

    echo html_frame_start("Screenshot Gallery",500);

    // display thumbnails
    $c = 1;
    echo "<div align=center><table><tr>\n";
    while($ob = mysql_fetch_object($result))
    {
	//set img tag
	$imgSRC = '<img src="appimage.php?imageId='.$ob->id.'&width=128&height=128" border=0 alt="'.$ob->description.'">';
	
	//get image size
	$size = getimagesize("data/screenshots/".$ob->url);
	
	//generate random tag for popup window
	$randName = generate_passwd(5);
	
	//set image link based on user pref
	$img = '<a href="javascript:openWin(\'appimage.php?imageId='.$ob->id.'\',\''.$randName.'\','.$size[0].','.$size[1].');">'.$imgSRC.'</a>';
	if (loggedin())
	{
	    if ($current->getpref("window:screenshot") == "no")
	    {
	        $img = '<a href="appimage.php?imageId='.$ob->id.'">'.$imgSRC.'</a>';
	    }
	}
	
	//display image
	echo "<td>\n";
	echo html_frame_start(substr(stripslashes($ob->description),0,20),128,"",0);
	echo $img;
	
	//show admin delete link
	if(loggedin() && (havepriv("admin") || $current->ownsApp($appId)))
	{
	    echo "<div align=center>[<a href='screenshots.php?cmd=delete&imageId=$ob->id'>Delete Image</a>]</div>";
	}
	
	echo html_frame_end("&nbsp;");
	echo "</td>\n";
	
	//end row if counter of 3
	if ($c % 3 == 0) { echo "</tr><tr>\n"; }
	
	$c++;
    }
    echo "</tr></table></div><br>\n";


    echo html_frame_end("Click thumbnail to view image in new window.");

    echo html_back_link(1);

    apidb_footer();

}

?>
