<?php
function mail_appdb($sEmailList,$sSubject,$sMsg)
{
    $sHeaders  = "MIME-Version: 1.0\r\n";
    $sHeaders .= "From: AppDB <".APPDB_OWNER_EMAIL.">\r\n";
    $sHeaders .= "Reply-to: AppDB <".APPDB_OWNER_EMAIL.">\r\n";
    $sHeaders .= "X-Priority: 3\r\n";
    $sHeaders .= "X-Mailer: ".APPDB_OWNER." mailer\r\n";

    $sMsg  = $sSubject."\r\n---------------------------------------------\r\n".$sMsg;
    $sMsg .= "Best regards.\r\n";
    $sMsg .= "The AppDB team\r\n";
    $sMsg .= APPDB_OWNER_URL."\r\n";
    $sMsg .= "\r\n\r\nIf you don't want to receive any other e-mail, please change your preferences:\r\n";
    $sMsg .= APPDB_ROOT."preferences.php\r\n";

    $bResult = mail(str_replace(" ",",",$sEmailList), "[AppDB] ".$sSubject, $sMsg, $sHeaders, "-f".APPDB_OWNER_EMAIL);
    if($bResult)
        addmsg("Message sent to: ".$sEmailList, "green");
    else 
        addmsg("Error while sending message to: ".$sEmailList, "red");
    return $bResult;
}
?>
