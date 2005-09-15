#!/usr/bin/php
<?php
##################################################
# this script has to be run once a month by cron #
# it's purpose is to clean the user's table.     #
##################################################

include("path.php");
include(BASE."include/incl.php");
include(BASE."include/mail.php");

/*
 * Let:
 * 1) you did not log in for six month
 * 2) you don't have any data associated                                               
 * 3) you are a maintainer
 * 4) you receive a warning e-mail
 * 5) you did not log in for seven month
 * 6) your account is deleted
 * 7) you are not a maintainer anymore
 * 
 * The rules are the following:
 * if(1 AND 2) then 4
 * if(1 AND 3) then 4
 * if(5 AND 2) then 6
 * if(5 AND 3) then 7
 */

notifyAdminsOfCleanupExecution();

$hSixMonth = inactiveSince(6);
while($oRow = mysql_fetch_object($hSixMonth))
{
    $oUser = new User($oRow->userid);
    if($oUser->isMaintainer())
        warnMaintainer($oUser->sEmail);
    elseif(!hasDataAssociated($oRow->userid))
        warnUser($oUser->sEmail);    
}

$hSevenMonth = inactiveSince(7);
while($oRow = mysql_fetch_object($hSevenMonth))
{
    $oUser = new User($oRow->userid);
    if($oUser->isMaintainer())
        deleteMaintainer($oRow->userid);
    elseif(!hasDataAssociated($oRow->userid))
        deleteUser($oRow->userid);    
}


function inactiveSince($iMonths)
{
    $sQuery = "SELECT userid FROM user_list WHERE DATE_SUB(CURDATE(),INTERVAL $iMonths MONTH) >= stamp";
    $hResult = query_appdb($sQuery);
    return $hResult;
}

function hasDataAssociated($iUserId)
{
    $sQuery = "SELECT count(userId) as c FROM appComments WHERE userId = $iUserId";
    $hResult = query_appdb($sQuery);
    $ob = mysql_fetch_object($hResult);
    if($ob->c != 0) return true;

    $sQuery = "SELECT count(userId) as c FROM appMaintainers WHERE userId = $iUserId";
    $hResult = query_appdb($sQuery);
    $ob = mysql_fetch_object($hResult);
    if($ob->c != 0) return true;

    $sQuery = "SELECT count(userId) as c FROM appVotes WHERE userId = $iUserId";
    $hResult = query_appdb($sQuery);
    $ob = mysql_fetch_object($hResult);
    if($ob->c != 0) return true;

    return false;
}


function deleteUser($iUserId)
{
    $oUser = new User($iUserId);
    # TO BE UNCOMMENTED WHEN THERE WILL BE LESS INACTIVE USERS IN THE DATABASE
    # warnUserDeleted($oUser->sEmail);
    $oUser->delete();
    echo "user ".$oUser->sEmail." deleted.\n";
}

function deleteMaintainer($iUserId)
{
    $oUser = new User($iUserId);
    $sQuery = "DELETE FROM appMaintainers WHERE userId = $iUserId";
    $hResult = query_appdb($sQuery);
    # TO BE UNCOMMENTED WHEN THERE WILL BE LESS INACTIVE USERS IN THE DATABASE
    # warnMaintainerDeleted($oUser->sEmail);
    echo "user ".$oUser->sEmail." is not a maintainer anymore.\n";
}

function warnUser($sEmail)
{
    $sSubject  = "Warning: inactivity detected";
    $sMsg  = "You didn't log in in the past six month to the AppDB.\r\n";
    $sMsg .= "Please log in or your account will automatically be deleted in one month.\r\n";

    mail_appdb($sEmail, $sSubject, $sMsg);
}

function warnMaintainer($sEmail)
{
    $sSubject  = "Warning: inactivity detected";
    $sMsg  = "You didn't log in in the past six month to the AppDB.\r\n";
    $sMsg .= "As a maintainer we would be pleased to see you once in a while.\r\n";
    $sMsg .= "Please log in or you will lose your maintainer's abilities in one month.\r\n";

    mail_appdb($sEmail, $sSubject, $sMsg);
}

function warnUserDeleted($sEmail)
{
    $sSubject  = "Warning: account removed";
    $sMsg  = "You didn't log in in the past seven month to the AppDB.\r\n";
    $sMsg .= "As you don't have any data associated to your account we have removed it.\r\n";
    $sMsg .= "Please feel free to recreate an account anytime.\r\n";

    mail_appdb($sEmail, $sSubject, $sMsg);
}

function warnMaintainerDeleted($sEmail)
{
    $sSubject  = "Warning: maintainer rights revoked\r\n";
    $sMsg  = "You didn't log in in the past seven month to the AppDB.\r\n";
    $sMsg .= "As a result, you are not a maintainer anymore.\r\n";
    $sMsg .= "Please feel free to enroll again as a maintainer anytime.\r\n";

    mail_appdb($sEmail, $sSubject, $sMsg);
}

/* email all admins that the appdb cleanup script is executing */
/* so we admins have some visibility into the background cleanup */
/* events of the appdb */
function notifyAdminsOfCleanupExecution()
{
    $sSubject  = "Cleanup script running\r\n";
    $sMsg  = "Appdb cleanup cron script is executing.\r\n";
    $sEmail = get_notify_email_address_list(null, null); /* get list admins */
    if($sEmail)
        mail_appdb($sEmail, $sSubject, $sMsg);
}
?>
