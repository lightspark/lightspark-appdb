<?php

    /* max votes per user */
    $MAX_VOTES = 3;


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
    $result = mysql_query("SELECT * FROM appVotes WHERE appId = $appId AND userId = $userId");
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
    $result = mysql_query("SELECT * FROM appVotes WHERE userId = $userId");
    return mysql_num_rows($result);
}


/*
 * total votes for appId
 */
function vote_count_app_total($appId)
{
    $result = mysql_query("SELECT * FROM appVotes WHERE appId = $appId");
    return mysql_num_rows($result);
}


/**
 * add a vote for appId
 */
function vote_add($appId, $slot, $userId = null)
{
    global $MAX_VOTES;

    if(!$userId)
        {
            if(loggedin())
                $userId = $_SESSION['current']->userid;
            else
                return;
        }

    //if(vote_count_user_total($userId) >= $MAX_VOTES)
    //   return;
    vote_remove($appId, $slot, $userId);
    mysql_query("INSERT INTO appVotes VALUES (null, null, $appId, $userId, $slot)");
}


/**
 * remove vote for appId
 */
function vote_remove($appId, $slot, $userId = null)
{
    

    if(!$userId)
        {
            if(loggedin())
                $userId = $_SESSION['current']->userid;
            else
                return;
        }
    mysql_query("DELETE FROM appVotes WHERE appId = $appId AND userId = $userId AND slot = $slot");
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
    $result = mysql_query("SELECT * FROM appVotes WHERE userId = $userId");
    if(!$result)
        return array();

    $obs = array();
    while($ob = mysql_fetch_object($result))
        $obs[$ob->slot] = $ob;
    return $obs;
}


function vote_menu()
{
    global $appId;

    $m = new htmlmenu("Votes","updatevote.php");
    
    $votes = vote_get_user_votes();

    if($votes[1])
    {
        $str = "<a href='appview.php?appId=".$votes[1]->appId."'> App #".$votes[1]->appId."</a>";
        $m->add("<input type=radio name=slot value='1' selected> ".$str);
    }
    else
        $m->add("<input type=radio name=slot value='1' selected> No App Selected");
    
    if($votes[2])
    {
        $str = "<a href='appview.php?appId=".$votes[2]->appId."'> App #".$votes[2]->appId."</a>";
        $m->add("<input type=radio name=slot value='2'> ".$str);
    }
    else
        $m->add("<input type=radio name=slot value='2'> No App Selected");

    if($votes[3])
    {
            $str = "<a href='appview.php?appId=".$votes[3]->appId."'> App #".$votes[3]->appId."</a>";
	    $m->add("<input type=radio name=slot value='3'> ".$str);
    }
    else
        $m->add("<input type=radio name=slot value='3'> No App Selected");

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
        vote_remove($vars["appId"], $vars["slot"]);
	}
}


?>
