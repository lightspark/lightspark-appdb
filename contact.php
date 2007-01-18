<?php

require_once("path.php");
require_once(BASE."/include/incl.php");

/**
 *  Page containing a form for sending e-mail
 *
 */

$oUser = new User($_SESSION['current']->iUserId);

/* Restrict error to logged-in users */
if(!$oUser->isLoggedIn())
    util_show_error_page_and_exit("You need to be logged in.");


$oRecipient = new User($aClean['iRecipientId']);

if(!User::exists($oRecipient->sEmail))
    util_show_error_page_and_exit("User not found");

/* Check for errors */
if((!$aClean['sMessage'] || !$aClean['sSubject']) && $aClean['sSubmit'])
{
    $error = "<font color=\"red\">Please enter both a subject and a ".
             "message.</font>";
    $aClean['sSubmit'] = "";
}

/* Display the feedback form if nothing else is specified */
if(!$aClean['sSubmit'])
{
    apidb_header("E-mail $oRecipient->sRealname");
    echo html_frame_start("Send us your suggestions",400,"",0);

    echo $error;
    echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
    echo "<p>E-mail $oRecipient->sRealname.</p>";
    echo html_table_begin("width\"100%\" border=\"0\" cellpadding=\"2\"".
    "cellspacing=\"2\"");
    echo html_tr(array(
        array("Subject", ""),
        "<input type=\"text\" name=\"sSubject\" size=\"71\" />"),
        "color4");
    echo html_tr(array(
        array("Message", "valign=\"top\""),
        "<textarea name=\"sMessage\" rows=\"15\" cols=\"60\"></textarea>"),
        "color4");
    echo html_tr(array(
        "",
        "<input type=\"submit\" value=\"Submit\" name=\"sSubmit\" />")
        );

    echo "<input type=\"hidden\" name=\"iRecipientId\" ".
    "value=\"$oRecipient->iUserId\" />";

    echo html_table_end();
    echo "</form>\n";

    echo html_frame_end("&nbsp;");

} else if ($aClean['sSubject'] && $aClean['sMessage'])
{
    $sMsg = "The following message was sent to you from $oUser->sRealname ";
    $sMsg .= "through the Wine AppDB contact form.\nTo Reply, visit ";
    $sMsg .= APPDB_ROOT."contact.php?iRecipientId=$oUser->iUserId\n\n";
    $sMsg .= $aClean['sMessage'];

    mail_appdb($oRecipient->sEmail, $aClean['sSubject'], $sMsg);

    util_redirect_and_exit(BASE."index.php");
}

?> 
