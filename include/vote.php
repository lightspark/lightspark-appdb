<?php

/* max votes per user */
define('MAX_VOTES',3);


/**
 * count the number of votes for appId by userId
 */
function vote_count($appId, $userId = null)
{

    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        else
            return 0;
}
    $result = query_appdb("SELECT * FROM appVotes WHERE appId = $appId AND userId = $userId");
    return mysql_num_rows($result);
}


/**
 * total votes by userId
 */
function vote_count_user_total($userId = null)
{
    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        else
            return 0;
    }
    $result = query_appdb("SELECT * FROM appVotes WHERE userId = $userId");
    return mysql_num_rows($result);
}


/*
 * total votes for appId
 */
function vote_count_app_total($appId)
{
    $result = query_appdb("SELECT * FROM appVotes WHERE appId = $appId");
    return mysql_num_rows($result);
}


/**
 * add a vote for appId
 */
function vote_add($appId, $slot, $userId = null)
{
    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        else
            return;
    }

    if($slot > MAX_VOTES)
        return;
    
    vote_remove($slot, $userId);
    query_appdb("INSERT INTO appVotes VALUES (null, null, $appId, $userId, $slot)");
}


/**
 * remove vote for a slot
 */
function vote_remove($slot, $userId = null)
{
    
    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        else
            return;
    }

    $sQuery="DELETE FROM appVotes WHERE userId = $userId AND slot = $slot";
    query_appdb($sQuery);
}


function vote_get_user_votes($userId = null)
{
    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        if(!$userId)
            return array();
    }
    $result = query_appdb("SELECT * FROM appVotes WHERE userId = $userId");
    if(!$result)
        return array();

    $obs = array();
    while($ob = mysql_fetch_object($result))
        $obs[$ob->slot] = $ob;
    return $obs;
}


function vote_menu()
{
    $m = new htmlmenu("Votes","updatevote.php");
    
    $votes = vote_get_user_votes();

    for($i = 1;$i <= MAX_VOTES; $i++)
    {
        if(isset($votes[$i]))
        {
            $appName = lookup_app_name($votes[$i]->appId);
            $str = "<a href='appview.php?appId=".$votes[$i]->appId."'> $appName</a>";
            $m->add("<input type=radio name=slot value='$i'> ".$str);
        }
        else
            $m->add("<input type=radio name=slot value='$i'> No App Selected");
    }
    
    $m->addmisc("&nbsp;");

    $m->add("<input type=submit name=clear value=' Clear Vote   ' class=votebutton>");
    $m->add("<input type=submit name=vote value='Vote for App' class=votebutton>");
    
    $m->addmisc("<input type=hidden name=appId value={$_REQUEST['appId']}>");
    
    $m->add("View Results", BASE."votestats.php");
    $m->add("Voting Help", BASE."help/?topic=voting");
    
    $m->done(1);    
}


function vote_update($vars)
{
    if(!$_SESSION['current']->isLoggedIn())
    {
        errorpage("You must be logged in to vote");
        return;
    }

    if( !is_numeric($vars['appId']) OR !is_numeric($vars['slot']))
    {
        if(is_numeric($vars['appId']))
           redirect(apidb_fullurl("appview.php?appId=".$vars["appId"]));
        else
            redirect(apidb_fullurl("index.php"));

        return;
    }
    
    if($vars["vote"])
    {
        addmsg("Registered vote for App #".$vars["appId"], "green");
        vote_add($vars["appId"], $vars["slot"]);
    } else if($vars["clear"])
    {
        /* see if we have a vote in this slot, if we don't there is */
        /* little reason to remove it or even mention that we did anything */
        if(is_vote_in_slot($vars["slot"]))
        {
            vote_remove($vars["slot"]);
            addmsg("Removed vote for App #".$vars["appId"], "green");
        }
    }

    redirect(apidb_fullurl("appview.php?appId=".$vars["appId"]));
}

// tell us if there is a vote in a given slot so we don't
// display incorrect information to the user or go
// through the trouble of trying to remove a vote that doesn't exist
function is_vote_in_slot($slot, $userId = null)
{
    if(!$userId)
    {
        if($_SESSION['current']->isLoggedIn())
            $userId = $_SESSION['current']->iUserId;
        else
            return;
    }

    $sQuery="SELECT COUNT(*) as count from appVotes WHERE userId = '".$userId."' AND slot = '".$slot."';";
    if($hResult = query_appdb($sQuery))
    {
        $oRow = mysql_fetch_object($hResult);        
        if($oRow->count != 0)
            return true;
        else
            return false;
    }
    
    return false;
}

?>
