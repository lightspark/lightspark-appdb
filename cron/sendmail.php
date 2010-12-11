#!/usr/bin/php
<?php
require("../include/config.php");
$db = new mysqli(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS, APPS_DB);
if($db->connect_error) exit;

$stmt = $db->stmt_init();
$stmt2 = $db->stmt_init();
$prep1 = $stmt->prepare("SELECT id,`to`,subject,message,headers,parameters FROM outbox LIMIT 0,1");
$prep2 = $stmt2->prepare("DELETE FROM outbox WHERE id=?");
if($prep1 && $prep2)
{
    print "Sending all mails...\n\n";
    while(TRUE)
    {
        $stmt->execute();
        $stmt->bind_result($id, $to, $subject, $message, $headers, $parameters);
        if(!$stmt->fetch()) break;

        mail($to, $subject, $message, $headers, $parameters);
        print "$id: Sent mail to $to\n";
        $stmt->free_result();
        $stmt->reset();


        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $stmt2->free_result();
        $stmt2->reset();
    }
}

print "\nAll mails sent\n";

$db->close();
?>
