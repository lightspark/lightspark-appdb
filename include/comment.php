<?php
/***************************************/
/* comment class and related functions */
/***************************************/


/**
 * Comment class for handling comments
 */
class Comment {
    var $iCommentId;
    var $iParentId;
    var $iAppId;
    var $iVersionId;
    var $sSubject;
    var $sBody;
    var $sDateCreated;
    var $sHostname;
    var $oOwner;


    /**
     * Constructor.
     * If $iCommentId is provided, fetches comment.
     */
    function Comment($iCommentId="")
    {
        if(is_numeric($iCommentId))
        {

            $sQuery = "SELECT appComments.*, appVersion.appId AS appId
                       FROM appComments, appVersion
                       WHERE appComments.versionId = appVersion.versionId 
                       AND commentId = '".$iCommentId."'";
            $hResult = query_appdb($sQuery);
            $oRow = mysql_fetch_object($hResult);
            $this->iCommentId = $oRow->commentId;
            $this->iParentId = $oRow->parentId;
            $this->iAppId = $oRow->appId;
            $this->iVersionId = $oRow->versionId;
            $this->sSubject = $oRow->subject;
            $this->sBody = $oRow->body;
            $this->sDateCreated = $oRow->time;
            $this->sHostname = $oRow->hostname;
            $this->oOwner = new User($oRow->userId);
        }
    }


    /*
     * Creates a new comment.
     * Informs interested people about the creation.
     * Returns true on success, false on failure
     */
    function create($sSubject, $sBody, $iParentId=null, $iVersionId)
    {
        $aInsert = compile_insert_string(array( 'parentId' => $iParentId,
                                                'versionId' => $iVersionId,
                                                'subject' => $sSubject,
                                                'body' => $sBody ));

        $sFields = "({$aInsert['FIELDS']}, `userId`, `time`, `hostname`)";
        $sValues = "({$aInsert['VALUES']}, ".$_SESSION['current']->iUserId.", NOW(), '".get_remote()."')";

        if(query_appdb("INSERT INTO appComments $sFields VALUES $sValues", "Error while creating a new comment."))
        {
            $this->comment(mysql_insert_id());
            $sEmail = get_notify_email_address_list($this->iAppId, $this->iVersionId);
            $sEmail .= $this->oOwner->sEmail." ";
            // fetches e-mails from parent comments
            while($iParentId)
            {
                $oParent = new Comment($iParentId);
                $sEmail .= $oParent->oOwner->sEmail." ";
                $iParentId = $oParent->iParentId;
            }
            if($sEmail)
            {
                $sSubject = "Comment for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." added by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sSubject."\r\n";
                $sMsg .= "\n";
                $sMsg .= $this->sBody."\r\n";
                mail_appdb($sEmail, $sSubject ,$sMsg);
            } 
            addmsg("Comment created.", "green");
            return true;
        }
        else
            return false;
    }


    /**
     * Update comment.
     * FIXME: Informs interested people about the modification.
     * Returns true on success and false on failure.
     */
    function update($sSubject=null, $sBody=null, $iParentId=null, $iVersionId=null)
    {
        if ($iParentId)
        {
            if (!query_appdb("UPDATE appComments SET parentId = '".$iParentId."' WHERE commentId = ".$this->iCommentId))
                return false;
            $this->iParentId = $iParentId;
        }     

        if ($iVersionId)
        {
            if (!query_appdb("UPDATE appComments SET versionId = '".$iVersionId."' WHERE commentId = ".$this->iCommentId))
                return false;
            $this->iVersionId = $iVersionId;
            // FIXME: we need to refetch $this->iAppId.
        }

        if ($sSubject)
        {
            if (!query_appdb("UPDATE appComments SET subject = '".$sSubject."' WHERE commentId = ".$this->iCommentId))
                return false;
            $this->sSubject = $sSubject;
        }

        if ($sBody)
        {
            if (!query_appdb("UPDATE appComments SET body = '".$sBody."' WHERE commentId = ".$this->iCommentId))
                return false;
            $this->sBody = $sBody;
        }
        return true;
    }


    /**
     * Removes the current comment from the database.
     * Informs interested people about the deletion.
     * Returns true on success and false on failure.
     */
    function delete($sReason=null)
    {
        $hResult = query_appdb("DELETE FROM appComments WHERE commentId = '".$this->iCommentId."'");
        if ($hResult)
        {
            /* fixup the child comments so the parentId points to a valid parent comment */
            $hResult = query_appdb("UPDATE appComments set parentId = '".$this->iParentId."' WHERE parentId = '".$this->iCommentId."'");
            $sEmail = get_notify_email_address_list($this->iAppId, $this->iVersionId);
            $sEmail .= $this->oOwner->sEmail;
            if($sEmail)
            {
                $sSubject = "Comment for ".lookup_app_name($this->iAppId)." ".lookup_version_name($this->iVersionId)." deleted by ".$_SESSION['current']->sRealname;
                $sMsg  = APPDB_ROOT."appview.php?versionId=".$this->iVersionId."\n";
                $sMsg .= "\n";
                $sMsg .= "This comment was made on ".substr($this->sDateCreated,0,10)." by ".$this->oOwner->sRealname."\n";
                $sMsg .= "\n";
                $sMsg .= "Subject: ".$this->sSubject."\r\n";
                $sMsg .= "\n";
                $sMsg .= $this->sBody."\r\n";
                $sMsg .= "\n";
                $sMsg .= "Because:\n";
                if($sReason)
                    $sMsg .= $sReason."\n";
                else
                    $sMsg .= "No reason given.\n";
                mail_appdb($sEmail, $sSubject ,$sMsg);
            } 
            addmsg("Comment deleted.", "green");
            return true;
        }
        return false;
    }
}



/*
 * Comment functions that are not part of the class
 */

function forum_lookup_user($iUserId)
{
    if ($iUserId > 0)
    {
        $oUser = new User($iUserId);
        $sMailto = '<a href="mailto:' . $oUser->sEmail . '">' . $oUser->sRealname . '</a>';
    }
    if (!$iUserId || !$oUser->isLoggedIn())
    {
        $sMailto = 'Anonymous';
    }
    return $sMailto;
}

/**
 * display a single comment (in $ob)
 */
function view_app_comment($ob)
{

    echo html_frame_start('','98%');
    echo '<table width="100%" border="0" cellpadding="2" cellspacing="1">',"\n";

    $ob->subject = stripslashes($ob->subject);
    $ob->body = stripslashes($ob->body);

    // message header
    echo "<tr bgcolor=\"#E0E0E0\"><td>\n";
    echo " <b>".$ob->subject."</b><br />\n";
    echo " by  ".forum_lookup_user($ob->userId)." on ".$ob->time."<br />\n";
    echo "</td></tr><tr><td>\n";
    
    // body
    echo htmlify_urls($ob->body), "<br /><br />\n";
    
    // only add RE: once
    if(eregi("RE:", $ob->subject))
	$subject = $ob->subject;
    else
	$subject = "RE: ".$ob->subject;

    // reply post buttons
    echo "	[<a href=\"addcomment.php?appId=$ob->appId&amp;versionId=$ob->versionId\"><small>post new</small></a>] \n";
    echo "	[<a href=\"addcomment.php?appId=$ob->appId&amp;versionId=$ob->versionId&amp;subject=".
	        urlencode("$subject")."&amp;thread=$ob->commentId\"><small>reply to this</small></a>] \n";

    echo "</td></tr>\n";

    // delete message button, for admins
    if ($_SESSION['current']->hasPriv("admin")
     || $_SESSION['current']->isMaintainer($ob->versionId) 
     || $_SESSION['current']->isSuperMaintainer($ob->appId))
    {
        echo "<tr>";
        echo "<td><form method=\"post\" name=\"message\" action=\"".BASE."deletecomment.php\"><input type=\"submit\" value=\"Delete\" class=\"button\">\n";
        echo "<input type=\"hidden\" name=\"commentId\" value=\"$ob->commentId\" />";
        echo "</form>\n";
        echo "</td></tr>";
    }

    echo "</table>\n";

    echo html_frame_end();
        
}


/**
 * grab comments for appId / versionId
 * if parentId is not -1 only comments for that thread are returned
 */
function grab_comments($versionId, $parentId = -1)
{
    $extra = "";
    if($parentId != -1)
        $extra = "AND parentId = $parentId ";

    $qstring = "SELECT from_unixtime(unix_timestamp(appComments.time), \"%W %M %D %Y, %k:%i\") as time, ".
               "appComments.commentId, appComments.parentId, appComments.versionId, appComments.userId, appComments.subject, appComments.body, appVersion.appId ".
               "FROM appComments, appVersion WHERE appComments.versionId = appVersion.versionId AND appComments.versionId = '$versionId' ".
               $extra.
               "ORDER BY appComments.time ASC";
    $result = query_appdb($qstring);

    return $result;
}


/**
 * display nested comments
 * handle is a db result set
 */
function do_display_comments_nested($handle)
{
    while($ob = mysql_fetch_object($handle))
    {
        view_app_comment($ob);
        $result = grab_comments($ob->versionId, $ob->commentId);
        if($result && mysql_num_rows($result))
        {
            echo "<blockquote>\n";
            do_display_comments_nested($result);
            echo "</blockquote>\n";
        }
        }
}


function display_comments_nested($versionId, $threadId)
{
    $result = grab_comments($versionId, $threadId);

    do_display_comments_nested($result);
}


/**
 * display threaded comments
 * handle is a db result set
 */
function do_display_comments_threaded($handle, $is_main)
{
    if (!$is_main)
        echo "<ul>\n";

    while ($ob = mysql_fetch_object($handle))
    {
        if ($is_main)
        {
            view_app_comment($ob);
        } else
        {
            echo '<li><a href="commentview.php?appId='.$ob->appId.'&amp;versionId='.$ob->versionId.'&threadId='.$ob->parentId.'"> '.
            $ob->subject.' </a> by '.forum_lookup_user($ob->userId).' on '.$ob->time.' </li>'."\n";
        }
        
        $result = grab_comments($ob->versionId, $ob->commentId);
        if ($result && mysql_num_rows($result))
        {
            echo "<blockquote>\n";
            do_display_comments_threaded($result, 0);
            echo "</blockquote>\n";
        }
    }
    
    if (!$is_main)
        echo "</ul>\n";
}


function display_comments_threaded($versionId, $threadId = 0)
{
    $result = grab_comments($versionId, $threadId);

    do_display_comments_threaded($result, 1);
}


/**
 * display flat comments
 */
function display_comments_flat($versionId)
{
    $result = grab_comments($versionId);
    if ($result)
    {
        while($ob = mysql_fetch_object($result))
        {
            view_app_comment($ob);
        }
    }
}


function view_app_comments($versionId, $threadId = 0)
{
    // count posts
    $result = query_appdb("SELECT commentId FROM appComments WHERE versionId = $versionId");
    $messageCount = mysql_num_rows($result);
    
    //start comment format table
    echo html_frame_start("","98%",'',0);
    echo '<table width="100%" border="0" cellpadding="1" cellspacing="0">',"\n";
    
    echo '<tr><td bgcolor="#C0C0C0" align="center"><table border="0" cellpadding="0" cellspacing="0"><tr bgcolor="#C0C0C0">',"\n";
    
    // message display mode changer
    if ($_SESSION['current']->isLoggedIn())
    {
    // FIXME we need to change this so not logged in users can change current view as well
        if (isset($_REQUEST['cmode']))
            $_SESSION['current']->setpref("comments:mode", $_REQUEST['cmode']);

            $sel[$_SESSION['current']->getpref("comments:mode")] = 'selected';
            echo '<td><form method="post" name="smode" action="appview.php">',"\n";
            echo "<b>Application Comments</b> $messageCount total comments ";
            echo '<b>Mode</b> <select name="cmode" onchange="document.smode.submit();">',"\n";
            echo '   <option value="flat" '.$sel['flat'].'>Flat</option>',"\n";
            echo '   <option value="threaded" '.$sel['threaded'].'>Threaded</option>',"\n";
            echo '   <option value="nested" '.$sel['nested'].'>Nested</option>',"\n";
            echo '   <option value="off" '.$sel['off'].'>No Comments</option>',"\n";
            echo '</select>',"\n";
            echo '<input type="hidden" name="versionId" value="'.$versionId.'"></form></td>',"\n";
    }
    
    // blank space
    echo '<td> &nbsp; </td>',"\n";
    
    // post new message button
    echo '<td><form method="post" name="message" action="addcomment.php"><input type="submit" value="post new comment" class="button"> ',"\n";
    echo '<input type="hidden" name="versionId" value="'.$versionId.'"></form></td>',"\n";
        
    //end comment format table
    echo '</tr></table></td></tr>',"\n";  
    echo '</table>',"\n";
    echo html_frame_end();
    
    if( $messageCount > 0 )
    {
        echo '<p align="center">The following comments are owned by whoever posted them. WineHQ is not responsible for what they say.</p>'."\n";
    }

    //start comments
    echo '<table width="100%" border="0" cellpadding="2" cellspacing="1"><tr><td>',"\n";
    
    //hide or display depending on pref
    if ($_SESSION['current']->isLoggedIn())
        $mode = $_SESSION['current']->getPref("comments:mode");
    else
        $mode = "flat";

    switch ($mode)
    {
        case "flat":
            display_comments_flat($versionId);
        break;
        case "nested":
            display_comments_nested($versionId, $threadId);
        break;
        case "threaded":
            display_comments_threaded($versionId, $threadId);
        break;
    }

    echo '</td></tr></table>',"\n";
}    
?>
