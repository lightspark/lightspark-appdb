<?
    
include("path.php");
require(BASE."include/"."incl.php");
require(BASE."include/"."application.php");

global $current;

if(!$appId) {
    errorpage('Internal Database Access Error');
    exit;
}

if(!$versionId) {
    $versionId = 0;
}

if(!$thread) {
    $thread = 0;
}

opendb();


if($body)
{
    // add comment to db
    
    $hostname = get_remote();
    
    $subject = strip_tags($subject);
    $subject = mysql_escape_string($subject);
    $body1 = mysql_escape_string($body);

    // get current userid
    $userId = (loggedin()) ? $current->userid : 0;

    $result = mysql_query("INSERT INTO appComments VALUES (null, null, $thread, ".
			   "$appId, $versionId, $userId, '$hostname', '$subject', ".
			   "'$body1', 0)");
		
    if (!$result)
    {
        errorpage('Internal Database Access Error',mysql_error());
        exit;
    } else
    {
        $email = getNotifyEmailAddressList($appId, $versionId);
        if($email)
        {
            $fullAppName = "Application: ".lookupAppName($appId)." Version: ".lookupVersionName($appId, $versionId);
            $ms .= apidb_fullurl("appview.php?appId=$appId&versionId=$versionId")."\n";
            $ms .= "\n";
            $ms .= ($current->username ? $current->username : "Anonymous")." added comment to ".$fullAppName."\n";
            $ms .= "\n";
            $ms .= "Subject: ".$subject."\n";
            $ms .= "\n";
            $ms .= $body."\n";
            $ms .= "\n";
            $ms .= STANDARD_NOTIFY_FOOTER;

            mail(stripslashes($email), "[AppDB] ".$fullAppName ,$ms);

        } else
        {
            $email = "no one";
        }
        addmsg("mesage sent to: ".$email, green);

        addmsg("New Comment Posted", "green");
        redirect(apidb_fullurl("appview.php?appId=$appId&versionId=$versionId"));
    }
}
else
{

    apidb_header("Add Comment");

    $mesTitle = "<b>Post New Comment</b>";

    if($thread)
	{
	    $result = mysql_query("SELECT subject,body FROM appComments WHERE commentId = $thread");
	    $ob = mysql_fetch_object($result);
	    if($ob)
		{
		    $mesTitle = "<b>Replying To ...</b> $ob->subject\n";
		    echo html_frame_start($ob->subject,500);
    	            echo htmlify_urls($ob->body), "<br><br>\n";
		    echo html_frame_end();
		}
	}

    echo "<form method=POST action='addcomment.php'>\n";

    echo html_frame_start($mesTitle,500,"",0);
    
    echo '<table width="100%" border=0 cellpadding=0 cellspacing=1>',"\n";
    echo "<tr bgcolor=#E0E0E0><td align=right><b>From:</b>&nbsp;</td>\n";
    echo "	<td>&nbsp;". ($current->username ? $current->username : "Anonymous") ." </td></tr>\n";
    echo "<tr bgcolor=#E0E0E0><td align=right><b>Subject:</b>&nbsp;</td>\n";
    echo "	<td>&nbsp;<input type=text size=35 name=subject value='$subject'> </td></tr>\n";
    echo "<tr bgcolor=#C0C0C0><td colspan=2><textarea name=body cols=70 rows=15 wrap=virtual>$body</textarea></td></tr>\n";
    echo "<tr bgcolor=#C0C0C0><td colspan=2 align=center>\n";
    echo "  <input type=SUBMIT value='Post Comment' class=button>\n";
    echo "  <input type=RESET value='Reset' class=button>\n";
    echo "</td></tr>\n";
    echo "</table>\n";

    echo html_frame_end();

    echo "<input type=HIDDEN name=thread value=$thread>\n";
    echo "<input type=HIDDEN name=appId value=$appId>\n";
    echo "<input type=HIDDEN name=versionId value=$versionId>\n";
    echo "</form><p>&nbsp;</p>\n";

    apidb_footer();

}

?>
