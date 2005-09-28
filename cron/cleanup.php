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

/* users inactive for 6 months that haven't been warned already */
$hUsersToWarn = unwarnedAndInactiveSince(6);
while($oRow = mysql_fetch_object($hUsersToWarn))
{
    $oUser = new User($oRow->userid);
    $oUser->warnForInactivity();
}

/* warned >= 1 month ago */
$hUsersToDelete = warnedSince(1);
while($oRow = mysql_fetch_object($hUsersToDelete))
{
    $oUser = new User($oRow->userid);
    if($oUser->isMaintainer())
        deleteMaintainer($oRow->userid);
    elseif(!$oUser->hasDataAssociated())
        deleteUser($oRow->userid);    
}


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

function deleteMaintainer($iUserId)
{
    $oUser = new User($iUserId);
    $sQuery = "DELETE FROM appMaintainers WHERE userId = $iUserId";
    $hResult = query_appdb($sQuery);
    warnMaintainerDeleted($oUser->sEmail);
    echo "user ".$oUser->sEmail." is not a maintainer anymore.\n";
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
