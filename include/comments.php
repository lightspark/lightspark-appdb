<?

/*=========================================================================
 *
 * display a single comment (in $ob)
 *
 */
function view_app_comment($ob)
{    
    $user = new User();
    
    if ($ob->email)
    {
    	$mailto = '<a href="mailto:' . $ob->email . '">' . $ob->username . '</a>';
    }
    else
    {
       $mailto = $ob->username;
    }

    echo html_frame_start('','98%');
    echo '<table width="100%" border=0 cellpadding=2 cellspacing=1">',"\n";

    $ob->subject = stripslashes($ob->subject);
    $ob->body = stripslashes($ob->body);

    // message header
    echo "<tr bgcolor=#E0E0E0><td>\n";
    echo " <b>$ob->subject</b><br>\n";
    echo " by  $mailto on $ob->time<br>\n";
    echo "</td></tr><tr><td>\n";
    
    // body
    echo htmlify_urls($ob->body), "<br><br>\n";
    
    // only add RE: once
    if(eregi("RE:", $ob->subject))
	$subject = $ob->subject;
    else
	$subject = "RE: $ob->subject";

    // reply post buttons
    echo "	[<a href='addcomment.php?appId=$ob->appId&versionId=$ob->versionId'><small>post new</small></a>] \n";
    echo "	[<a href='addcomment.php?appId=$ob->appId&versionId=$ob->versionId&subject=".
	        urlencode("$subject")."&thread=$ob->commentId'><small>reply to this</small></a>] \n";

    echo "</td></tr></table>\n";
    echo html_frame_end();
        
}


/*=========================================================================
 *
 * grab comments for appId / versionId
 * if parentId is not -1 only comments for that thread are returned
 */
function grab_comments($appId, $versionId, $parentId = -1)
{
    $extra = "";
    if($parentId != -1)
	$extra = "AND parentId = $parentId ";

    $qstring = "SELECT from_unixtime(unix_timestamp(time), \"%W %M %D %Y, %k:%i\") as time, ".
        "commentId, parentId, appId, versionId, username, email, subject, body ".
        "FROM appComments, user_list WHERE appComments.userId = user_list.userid ".
        $extra .
	"AND appId = $appId AND versionId = $versionId ".
        "ORDER BY appComments.time ASC";

    $result = mysql_query($qstring);

    return $result;
}

/*=========================================================================
 *
 * grab comments for appId / versionId
 * if parentId is not -1 only comments for that thread are returned
 */
function count_comments($appId, $versionId)
{

    $qstring = "SELECT count(commentId) as hits FROM appComments WHERE appId = $appId AND versionId = $versionId";   
    $result = mysql_query($qstring);
    $ob = mysql_fetch_object($result);
    return $ob->hits;
}

/*=========================================================================
 *
 * display nested comments
 *
 * handle is a db result set
 *
 */
function do_display_comments_nested($handle)
{
    while($ob = mysql_fetch_object($handle))
        {
            view_app_comment($ob);
	    $result = grab_comments($ob->appId, $ob->versionId, $ob->commentId);
	    if($result && mysql_num_rows($result))
		{
		    echo "<blockquote>\n";
		    do_display_comments_nested($result);
		    echo "</blockquote>\n";
		}
        }
}

function display_comments_nested($appId, $versionId, $threadId)
{
    $result = grab_comments($appId, $versionId, $threadId);

    do_display_comments_nested($result);
}


/*=========================================================================
 *
 * display threaded comments
 *
 * handle is a db result set
 *
 */
function do_display_comments_threaded($handle, $is_main)
{
    if(!$is_main)
	echo "<ul>\n";

    while($ob = mysql_fetch_object($handle))
        {
	    if($is_main)
		view_app_comment($ob);
	    else
		echo "<li> <a href='commentview.php?appId=$ob->appId&versionId=$ob->versionId&threadId=$ob->commentId'> ".
		    " $ob->subject </a> by $ob->username on $ob->time </li>\n";
            $result = grab_comments($ob->appId, $ob->versionId, $ob->commentId);
            if($result && mysql_num_rows($result))
                {
                    echo "<blockquote>\n";
                    do_display_comments_threaded($result, 0);
                    echo "</blockquote>\n";
                }
	}
    if(!$is_main)
	echo "</ul>\n";
}

function display_comments_threaded($appId, $versionId, $threadId = 0)
{
    $result = grab_comments($appId, $versionId, $threadId);

    do_display_comments_threaded($result, 1);
}


/*=========================================================================
 *
 * display flat comments
 *
 */
function display_comments_flat($appId, $versionId)
{
    $result = grab_comments($appId, $versionId);

    while($ob = mysql_fetch_object($result))
	{
	    view_app_comment($ob);
	}
}
    

function view_app_comments($appId, $versionId, $threadId = 0)
{
    opendb();

    global $current;
    global $cmode;


    $result = mysql_query("SELECT commentId FROM appComments WHERE appId = $appId AND versionId = $versionId");
    $messageCount = mysql_num_rows($result);
    

    //start comment format table
    echo html_frame_start("","98%",'',0);
    echo '<table width="100%" border=0 cellpadding=1 cellspacing=0">',"\n";
    
    echo '<tr><td bgcolor=#C0C0C0 align=center><table border=0 cellpadding=0 cellspacing=0><tr bgcolor=#C0C0C0>',"\n";
    
    // message display mode changer
    if (loggedin())
    {
	    //FIXME we need to change this so not logged in users can change current view as well
        if ($cmode)
    		$current->setpref("comments:mode", $cmode);
	
        $sel[$current->getpref("comments:mode")] = 'selected';
	    echo '<td><form method=get name=smode action="appview.php">',"\n";
        echo "<b>Application Comments</b> $messageCount total comments ";
	    echo '<b>Mode</b> <select name="cmode" onchange="document.smode.submit();">',"\n";
	    echo '   <option value=flat '.$sel['flat'].'>Flat</option>',"\n";
	    echo '   <option value=threaded '.$sel['threaded'].'>Threaded</option>',"\n";
	    echo '   <option value=nested '.$sel['nested'].'>Nested</option>',"\n";
	    echo '   <option value=off '.$sel['off'].'>No Comments</option>',"\n";
	    echo '</select><input type=hidden name="appId" value="'.$appId.'">',"\n";
	    echo '<input type=hidden name="versionId" value="'.$versionId.'"></form></td>',"\n";
    }
    
    // blank space
    echo '<td> &nbsp; </td>',"\n";
    
    // post new message button
    echo '<td><form method=get name=message action="addcomment.php"><input type=submit value=" post new comment " class=button> ',"\n";
    echo '<input type=hidden name="appId" value="'.$appId.'"><input type=hidden name="versionId" value="'.$versionId.'"></form></td>',"\n";
    
    //end comment format table
    echo '</tr></table></td></tr>',"\n";  
    echo '</table>',"\n";
    echo html_frame_end("The following comments are owned by whoever posted them. CodeWeavers is not responsible for what they say.");

    //start comments
    echo '<table width="100%" border=0 cellpadding=2 cellspacing=1"><tr><td>',"\n";
    
    //hide or display depending on pref
    if (loggedin())
	    $mode = $current->getpref("comments:mode");
    else
	    $mode = "flat";

    switch ($mode)
    {
        case "flat":
	        display_comments_flat($appId, $versionId);
            break;
        case "nested":
	        display_comments_nested($appId, $versionId, $threadId);
            break;
        case "threaded":
	        display_comments_threaded($appId, $versionId, $threadId);
            break;
    }

    echo '</td></tr></table>',"\n";
      
}    


?>
