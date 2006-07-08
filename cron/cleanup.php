#!/usr/bin/php
<?php
##################################################
# this script has to be run once a month by cron #
# it's purpose is to clean the user's table.     #
##################################################

require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/mail.php");

/*
 * Warn users that have been inactive for some number of months
 * If it has been some period of time since the user was warned
 *   the user is deleted if they don't have any pending appdb data
 */

$usersWarned = 0;
$usersUnwarnedWithData = 0; /* users we would normally warn but who have data */
$usersDeleted = 0;
$usersWithData = 0; /* users marked for deletion that have data */

notifyAdminsOfCleanupStart();

/* users inactive for 6 months that haven't been warned already */
$hUsersToWarn = unwarnedAndInactiveSince(6);
if($hUsersToWarn)
{
    while($oRow = mysql_fetch_object($hUsersToWarn))
    {
        $oUser = new User($oRow->userid);

        /* if we get back true the user was warned and flaged as being warned */
        /* if we get back false we didn't warn the user and didn't flag the user as warned */
        /*  because they have data associated with their account */
        if($oUser->warnForInactivity())
        {
            $usersWarned++;
        } else
        {
            $usersUnwarnedWithData++;
        }
    }
}

/* warned >= 1 month ago */
$hUsersToDelete = warnedSince(1);
if($hUsersToDelete)
{
    while($oRow = mysql_fetch_object($hUsersToDelete))
    {
        $oUser = new User($oRow->userid);
        if(!$oUser->hasDataAssociated())
        {
            $usersDeleted++;
            deleteUser($oRow->userid);
        } else
        {
            /* is the user a maintainer?  if so remove their maintainer privilages */
            if($oUser->isMaintainer())
            {
                $oUser->deleteMaintainer();
            }

            $usersWithData++;
        }
    }
}

notifyAdminsOfCleanupExecution($usersWarned, $usersUnwarnedWithData, $usersDeleted, $usersWithData);

/* check to see if there are orphaned versions in the database */
orphanVersionCheck();

/* check and purge any orphaned messages stuck in sessionMessages table */
orphanSessionMessagesCheck();

/* check and purge any expired sessions from the session_list table */
orphanSessionListCheck();




/* Users that are unwarned and inactive since $iMonths */
function unwarnedAndInactiveSince($iMonths)
{
    $sQuery = "SELECT userid FROM user_list WHERE DATE_SUB(CURDATE(),INTERVAL $iMonths MONTH) >= stamp AND inactivity_warned='false'";
    $hResult = query_appdb($sQuery);
    return $hResult;
}

/* users that were warned at least $iMonths ago */
function warnedSince($iMonths)
{
    $sQuery  = "SELECT userid FROM user_list WHERE DATE_SUB(CURDATE(),INTERVAL $iMonths MONTH) >= inactivity_warn_stamp ";
    $sQuery .= "AND inactivity_warned='true'";
    $hResult = query_appdb($sQuery);
    return $hResult;
}

function deleteUser($iUserId)
{
    $oUser = new User($iUserId);
    warnUserDeleted($oUser->sEmail);
    $oUser->delete();
    echo "user ".$oUser->sEmail." deleted.\n";
}

function warnUserDeleted($sEmail)
{
    $sSubject  = "Warning: account removed";
    $sMsg  = "You didn't log in in the past seven months to the AppDB.\r\n";
    $sMsg .= "As you don't have any data associated to your account we have removed it.\r\n";
    $sMsg .= "Please feel free to recreate an account anytime.\r\n";

    mail_appdb($sEmail, $sSubject, $sMsg);
}

function notifyAdminsOfCleanupStart()
{
    $sSubject  = "Cleanup script starting\r\n";
    $sMsg  = "Appdb cleanup cron script started.\r\n";
    $sEmail = User::get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);
}

/* email all admins that the appdb cleanup script is executing */
/* so we admins have some visibility into the background cleanup */
/* events of the appdb */
function notifyAdminsOfCleanupExecution($usersWarned, $usersUnwarnedWithData, $usersDeleted, $usersWithData)
{
    $warnedUsers = User::get_inactive_users_pending_deletion();

    $sSubject  = "Cleanup script summary\r\n";
    $sMsg  = "Appdb cleanup cron script executed.\r\n";
    $sMsg .= "Status\r\n";
    $sMsg .= "--------------------------\r\n";
    $sMsg .= "Users warned:".$usersWarned."\r\n";
    $sMsg .= "Users we would warn, but don't because they have data associated:".$usersUnwarnedWithData."\r\n";
    $sMsg .= "Warned users pending deletion:".$warnedUsers."\r\n";
    $sMsg .= "Users deleted:".$usersDeleted."\r\n";
    $sMsg .= "Users pending deletion but have appdb data:".$usersWithData."\r\n";
    $sEmail = User::get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);
}

/* check for and report orphaned versions in the database */
/* we don't report anything if no orphans are found */
function orphanVersionCheck()
{
    $sQuery = "select versionId, versionName from appVersion where appId = 0";
    $hResult = query_appdb($sQuery);
    $found_orphans = false;

    $sMsg = "Found these orphaned versions in the database with\r\n";
    $sMsg.= "this sql command '".$sQuery."'\r\n";

    /* don't report anything if no orphans are found */
    if(mysql_num_rows($hResult) == 0)
        return;

    $sMsg .= "versionId/name\r\n";
    while($oRow = mysql_fetch_object($hResult))
    {
        $sMsg .= $oRow->versionId."/".$oRow->versionName."\r\n";
    }

    $sSubject = "Versions orphaned in the database\r\n";

    $sEmail = User::get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);
}

/* this function checks to see if we have any orphaned session messages */
/* These orphaned messages are an indication that we've put a message into */
/* the system without displaying it and it becomes effectively lost forever */
/* so we'll want to purge them here after reporting how many we have */
function orphanSessionMessagesCheck()
{
    $iSessionMessageDayLimit = 1; /* the number of days a session message must be stuck before being purges */

    /* get a count of the messages older than $iSessionMessageDayLimit */
    $sQuery = "SELECT count(*) as cnt from sessionMessages where TO_DAYS(NOW()) - TO_DAYS(time) > ?";
    $hResult = query_parameters($sQuery, $iSessionMessageDayLimit);

    $oRow = mysql_fetch_object($hResult);
    $iMessages = $oRow->cnt;

    $sMsg = "Found ".$iMessages." that have been orphaned in the sessionMessages table for longer than ".$iSessionMessageDayLimit." days\r\n";
    $sMsg.= " Purging these messages.\r\n";

    $sSubject = "Messages orphaned in sessionMessages\r\n";

    $sEmail = User::get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);

    /* purge the messages older than $iSessionMessageDayLimit */
    $sQuery = "DELETE from sessionMessages where TO_DAYS(NOW()) - TO_DAYS(time) > ?";
    $hResult = query_parameters($sQuery, $iSessionMessageDayLimit);
}

/* this function checks to see if we have any orphaned sessions */
/* sessions need to be expired or the session_list table will grow */
/* by one row each time a user logs */
function orphanSessionListCheck()
{
    /* get a count of the messages older than $iSessionListDayLimit */
    $sQuery = "SELECT count(*) as cnt from session_list where TO_DAYS(NOW()) - TO_DAYS(stamp) > ?";
    $hResult = query_parameters($sQuery, SESSION_DAYS_TO_EXPIRE + 2);

    $oRow = mysql_fetch_object($hResult);
    $iMessages = $oRow->cnt;

    $sMsg = "Found ".$iMessages." sessions that have expired after ".(SESSION_DAYS_TO_EXPIRE + 2)." days\r\n";
    $sMsg.= " Purging these sessions.\r\n";

    $sSubject = "Sessions expired\r\n";

    $sEmail = User::get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);

    /* purge the messages older than $iSessionMessageDayLimit */
    $sQuery = "DELETE from session_list where TO_DAYS(NOW()) - TO_DAYS(stamp) > ?";
    $hResult = query_parameters($sQuery, SESSION_DAYS_TO_EXPIRE + 2);
}
