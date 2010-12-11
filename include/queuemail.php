<?php

function queuemail($to, $subject, $message, $headers=null, $parameters=null, $justsend=false)
{
    if($justsend)
        return mail($to, $subject, $message, $headers, $parameters);

    $db = new mysqli(APPS_DBHOST, APPS_DBUSER, APPS_DBPASS, APPS_DB);
    if($db->connect_error) return FALSE;

    $stmt = $db->stmt_init();
    if($stmt->prepare("INSERT INTO outbox VALUES(NULL, ?, ?, ?, ?, ?, DEFAULT)"))
    {
        $stmt->bind_param('sssss', $to, $subject, $message, $headers, $parameters);
        $stmt->execute();
        return $stmt->affected_rows == 1;
    }
    else
        return FALSE;

    $db->close();
}

?>
