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
        if(loggedin())
            $userId = $_SESSION['current']->userid;
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
        if(loggedin())
            $userId = $_SESSION['current']->userid;
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
            if(loggedin())
                $userId = $_SESSION['current']->userid;
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
            if(loggedin())
                $userId = $_SESSION['current']->userid;
            else
                return;
        }
    query_appdb("DELETE FROM appVotes WHERE userId = $userId AND slot = $slot");
}


function vote_get_user_votes($userId = null)
{
    if(!$userId)
    {
        if(loggedin())
            $userId = $_SESSION['current']->userid;
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
            $appName = lookupAppName($votes[$i]->appId);
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


function dump($arr)
{
    while(list($key, $val) = each($arr))
    {
        echo "$key  =>  $val <br>\n";
    }
}


function vote_update($vars)
{
    //FIXME this doesn't work since msgs only work when logged in
    if(!loggedin())
    {
        addmsg("You must be logged in to vote", "red");
        return;
    }

    dump($vars);
    echo "<br>\n";
    
    if( !is_numeric($vars['appId']) OR !is_numeric($vars['slot']))
    {
        addmsg("No application or vote slot selected", "red");
        return;
    }
    
    if($vars["vote"])
	{
	    addmsg("Registered vote for App #".$vars["appId"], "green");
	    vote_add($vars["appId"], $vars["slot"]);
	}
    else
    if($vars["clear"])
	{
	    addmsg("Removed vote for App #".$vars["appId"], "green");
        vote_remove($vars["slot"]);
	}
}


?>
