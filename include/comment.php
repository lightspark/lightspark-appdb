<?php
/***************************************/
/* comment class and related functions */
/***************************************/
require_once(BASE."include/user.php");

/**
 * Comment class for handling comments
 */
class Comment {
    var $iCommentId;

    // variables necessary for creating a comment
    var $iParentId;
    var $sSubject;
    var $sBody;
    var $iVersionId;


    var $iAppId;
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
                       AND commentId = '?'";
            $hResult = query_parameters($sQuery, $iCommentId);
            $oRow = query_fetch_object($hResult);
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
    function create()
    {
        $hResult = query_parameters("INSERT INTO appComments
                (parentId, versionId, subject, ".
                                    "body, userId, time, hostname)
                VALUES ('?', '?', '?', '?', '?', ?, '?')",
                                    $this->iParentId, $this->iVersionId,
                                    $this->sSubject, $this->sBody,
                                    $_SESSION['current']->iUserId,
                                    "NOW()", get_remote());

        if($hResult)
        {
            $this->comment(query_appdb_insert_id());
            $sEmail = User::get_notify_email_address_list($this->iAppId, $this->iVersionId);
            $sEmail .= $this->oOwner->sEmail." ";

            // fetches e-mails from parent comments, all parents are notified that a
            // comment was added to the thread
            $iParentId = $this->iParentId;
            while($iParentId)
            {
                $oParent = new Comment($iParentId);
                $sEmail .= $oParent->oOwner->sEmail." ";
                $iParentId = $oParent->iParentId;
            }
            if($sEmail)
            {
                $aEmailArray = explode(" ", $sEmail);      /* break the long email string into parts by spaces */
                $aEmailArray = array_unique($aEmailArray); /* remove duplicates */

                /* build the single string of all emails up */
                $sEmail = "";
                foreach($aEmailArray as $key=>$value)
                {
                    $sEmail.="$value ";
                }

                $sSubject = "Comment for '".Application::lookup_name($this->iAppId)." ".Version::lookup_name($this->iVersionId)."' added by ".$_SESSION['current']->sRealname;
                $sMsg  = "To reply to this email please use the link provided below.\n";
                $sMsg .= "DO NOT reply via your email client as it will not reach the person who wrote the comment\n";
                $sMsg .= $this->objectMakeUrl()."\n";
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
        {
            addmsg("Error while creating a new comment", "red");
            return false;
        }
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
            if (!query_parameters("UPDATE appComments SET parentId = '?' WHERE commentId = '?'",
                                  $iParentId, $this->iCommentId))
                return false;
            $this->iParentId = $iParentId;
        }     

        if ($iVersionId)
        {
            if (!query_parameters("UPDATE appComments SET versionId = '?' WHERE commentId = '?'",
                                  $iVersionId, $this->iCommentId))
                return false;
            $this->iVersionId = $iVersionId;
            // FIXME: we need to refetch $this->iAppId.
        }

        if ($sSubject)
        {
            if (!query_parameters("UPDATE appComments SET subject = '?' WHERE commentId = '?'",
                                  $sSubject, $this->iCommentId))
                return false;
            $this->sSubject = $sSubject;
        }

        if ($sBody)
        {
            if (!query_parameters("UPDATE appComments SET body = '?' WHERE commentId = '?'",
                                  $sBody, $this->iCommentId))
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
    function delete($bSilent = false)
    {
        $hResult = query_parameters("DELETE FROM appComments WHERE commentId = '?'", $this->iCommentId);
        if ($hResult)
        {
            /* fixup the child comments so the parentId points to a valid parent comment */
            $hResult = query_parameters("UPDATE appComments set parentId = '?' WHERE parentId = '?'",
                                        $this->iParentId, $this->iCommentId);

            if(!$bSilent)
            {
                $this->SendNotificationMail("delete");
            }

            return true;
        } else
        {
            addmsg("Error removing the deleted comment!", "red");
        }

        return false;
    }

    function SendNotificationMail($sAction="add", $sMsg = null)
    {
        global $aClean;
        
        // use 'sReplyText' if it is defined, otherwise define the value as an empty string
        if(!isset($aClean['sReplyText']))
            $aClean['sReplyText'] = "";

        $oApp = new Application($this->iAppId);
        switch($sAction)
        {
            case "delete":
              $sSubject = "Comment for '".version::fullName($this->iVersionId)."' deleted by ".$_SESSION['current']->sRealname;
              $oVersion = new version($this->iVersionId);
              $sMsg  = $oVersion->objectMakeUrl()."\n";
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

              addmsg("Comment deleted.", "green");

              break;
        } 

        $sEmail = User::get_notify_email_address_list($this->iAppId, $this->iVersionId);
        $sEmail .= $this->oOwner->sEmail;
        if($sEmail)
          mail_appdb($sEmail, $sSubject, $sMsg);
    }

    function get_comment_count_for_versionid($iVersionId)
    {
        $sQuery = "SELECT count(*) as cnt from appComments where versionId = '?'";
        $hResult = query_parameters($sQuery, $iVersionId);
        if(!$hResult) return 0;
        
        $oRow = query_fetch_object($hResult);
        return $oRow->cnt;
    }

    /**
     * class static functions
     */

    /**
     * display a single comment (in $oRow)
     */
    function view_app_comment($oRow)
    {
        echo html_frame_start('','98%');
        echo '<table width="100%" border="0" cellpadding="2" cellspacing="1">',"\n";

        // message header
        echo "<tr bgcolor=\"#E0E0E0\"><td><a name=Comment-".$oRow->commentId."></a>\n";
        echo " <b>".$oRow->subject."</b><br />\n";
        echo " by  ".forum_lookup_user($oRow->userId)." on ".$oRow->time."<br />\n";
        echo "</td></tr><tr><td>\n";
    
        // body
        echo htmlify_urls($oRow->body), "<br /><br />\n";
    
        // only add RE: once
        if(eregi("RE:", $oRow->subject))
            $subject = $oRow->subject;
        else
            $subject = "RE: ".$oRow->subject;

        // reply post buttons
        echo "	[<a href=\"addcomment.php?iAppId=$oRow->appId&amp;iVersionId=$oRow->versionId\"><small>post new</small></a>] \n";
        echo "	[<a href=\"addcomment.php?iAppId=$oRow->appId&amp;iVersionId=$oRow->versionId&amp;sSubject=".
            urlencode("$subject")."&amp;iThread=$oRow->commentId\"><small>reply to this</small></a>] \n";

        echo "</td></tr>\n";

        // delete message button, for admins
        if ($_SESSION['current']->hasPriv("admin")
            || $_SESSION['current']->isMaintainer($oRow->versionId) 
            || $_SESSION['current']->isSuperMaintainer($oRow->appId))
        {
            echo "<tr>";
            echo "<td><form method=\"post\" name=\"sMessage\" action=\"".BASE."deletecomment.php\"><input type=\"submit\" value=\"Delete\" class=\"button\">\n";
            echo "<input type=\"hidden\" name=\"iCommentId\" value=\"$oRow->commentId\" />";
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
    function grab_comments($iVersionId, $iParentId = null)
    {
        /* TODO: remove the logging when we figure out where the */
        /* invalid $iVersionId is coming */
        /* if $iVersionId is invalid we should log where we came from */
        /* so we can debug the problem */
        if($iVersionId == "")
        {
            error_log::logBackTrace("logging iVersionId oddity");
            return NULL;
        }

        /* escape input so we can use query_appdb() without concern */
        $iVersionId = query_escape_string($iVersionId);
        $iParentId = query_escape_string($iParentId);

        $sExtra = "";

        /* NOTE: we must compare against NULL here because $iParentId of 0 is valid */
        if($iParentId != NULL)
            $sExtra = "AND parentId = '".$iParentId."' ";

        $sQuery = "SELECT from_unixtime(unix_timestamp(appComments.time), \"%W %M %D %Y, %k:%i\") as time, ".
            "appComments.commentId, appComments.parentId, appComments.versionId, appComments.userId, appComments.subject, appComments.body, appVersion.appId ".
            "FROM appComments, appVersion WHERE appComments.versionId = appVersion.versionId AND appComments.versionId = '".$iVersionId."' ".
            $sExtra.
            "ORDER BY appComments.time ASC";
        $hResult = query_appdb($sQuery);

        return $hResult;
    }

    /**
     * display nested comments
     * handle is a db result set
     */
    function do_display_comments_nested($hResult)
    {
        while($oRow = query_fetch_object($hResult))
        {
            Comment::view_app_comment($oRow);
            $hResult2 = Comment::grab_comments($oRow->versionId, $oRow->commentId);
            if($hResult && query_num_rows($hResult2))
            {
                echo "<blockquote>\n";
                Comment::do_display_comments_nested($hResult2);
                echo "</blockquote>\n";
            }
        }
    }

    function display_comments_nested($versionId, $threadId)
    {
        $hResult = Comment::grab_comments($versionId, $threadId);
        Comment::do_display_comments_nested($hResult);
    }

    /**
     * display threaded comments
     * handle is a db result set
     */
    function do_display_comments_threaded($hResult, $is_main)
    {
        if (!$is_main)
            echo "<ul>\n";
        
        while ($oRow = query_fetch_object($hResult))
        {
            if ($is_main)
            {
                Comment::view_app_comment($oRow);
            } else
            {
                echo '<li><a href="commentview.php?iAppId='.$oRow->appId.'&amp;iVersionId='.$oRow->versionId.'&iThreadId='.$oRow->parentId.'"> '.
                    $oRow->subject.' </a> by '.forum_lookup_user($oRow->userId).' on '.$oRow->time.' </li>'."\n";
            }
        
            $hResult2 = Comment::grab_comments($oRow->versionId, $oRow->commentId);
            if ($hResult2 && query_num_rows($hResult2))
            {
                echo "<blockquote>\n";
                Comment::do_display_comments_threaded($hResult2, 0);
                echo "</blockquote>\n";
            }
        }
    
        if (!$is_main)
            echo "</ul>\n";
    }

    function objectGetChildren()
    {
        return array();
    }

    function display_comments_threaded($versionId, $threadId = 0)
    {
        $hResult = Comment::grab_comments($versionId, $threadId);

        Comment::do_display_comments_threaded($hResult, 1);
    }

    /**
     * display flat comments
     */
    function display_comments_flat($versionId)
    {
        $hResult = Comment::grab_comments($versionId);
        if ($hResult)
        {
            while($oRow = query_fetch_object($hResult))
            {
                Comment::view_app_comment($oRow);
            }
        }
    }

    function view_app_comments($versionId, $threadId = 0)
    {
        global $aClean;

        // count posts
        $hResult = query_parameters("SELECT commentId FROM appComments WHERE versionId = '?'", $versionId);
        $messageCount = query_num_rows($hResult);
    
        //start comment format table
        echo html_frame_start("","98%",'',0);
        echo '<table width="100%" border="0" cellpadding="1" cellspacing="0">',"\n";
    
        echo '<tr><td bgcolor="#C0C0C0" align="center"><table border="0" cellpadding="0" cellspacing="0"><tr bgcolor="#C0C0C0">',"\n";
    
        // message display mode changer
        if ($_SESSION['current']->isLoggedIn())
        {
            // FIXME we need to change this so not logged in users can change current view as well
            if (!empty($aClean['sCmode']))
                $_SESSION['current']->setPref("comments:mode", $aClean['sCmode']);

            $oVersion = new version($versionId);
            $sel[$_SESSION['current']->getPref("comments:mode", "threaded")] = 'selected';
            echo '<td><form method="post" name="sMode" action="'.
                    $oVersion->objectMakeUrl().'">',"\n";
            echo "<b>Application Comments</b> $messageCount total comments ";
            echo '<b>Mode</b> <select name="sCmode" onchange="document.sMode.submit();">',"\n";
            echo '   <option value="flat" '.$sel['flat'].'>Flat</option>',"\n";
            echo '   <option value="threaded" '.$sel['threaded'].'>Threaded</option>',"\n";
            echo '   <option value="nested" '.$sel['nested'].'>Nested</option>',"\n";
            echo '   <option value="off" '.$sel['off'].'>No Comments</option>',"\n";
            echo '</select>',"\n";
            echo '</form></td>',"\n";
        }
    
        // blank space
        echo '<td> &nbsp; </td>',"\n";
    
        // post new message button
        echo '<td><form method="post" name="sMessage" action="addcomment.php"><input type="submit" value="post new comment" class="button"> ',"\n";
        echo '<input type="hidden" name="iVersionId" value="'.$versionId.'"></form></td>',"\n";
        
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
            $mode = $_SESSION['current']->getPref("comments:mode", "threaded");
        else
            $mode = "threaded"; /* default non-logged in users to threaded comment display mode */

        if ( isset($aClean['sMode']) && $aClean['sMode']=="nested")
            $mode = "nested";

        switch ($mode)
        {
        case "flat":
            Comment::display_comments_flat($versionId);
            break;
        case "nested":
            Comment::display_comments_nested($versionId, $threadId);
            break;
        case "threaded":
            Comment::display_comments_threaded($versionId, $threadId);
            break;
        }

        echo '</td></tr></table>',"\n";
    }

    function objectMakeUrl()
    {
        $oVersion = new version($this->iVersionId);
        $sUrl = $oVersion->objectMakeUrl()."#Comment-".$this->iCommentId;
        return $sUrl;
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
        if($_SESSION['current']->isLoggedIn())
            $sMailto = '<a href="'.BASE.'contact.php?iRecipientId='.
                    $oUser->iUserId.'">' .$oUser->sRealname . '</a>';
        else
            $sMailto = $oUser->sRealname;
    }
    if (!$iUserId || !$oUser->isLoggedIn())
    {
        $sMailto = 'Anonymous';
    }
    return $sMailto;
}

?>
