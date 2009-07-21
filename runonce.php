<?php

require_once('path.php');
require_once(BASE.'include/incl.php');

$hFile = fopen('runonce', 'r');

if(filesize('runonce') && fread($hFile, filesize('runonce')) == '1')
{
    fclose($hFile);
    echo "The command has already been run";
    exit;
}

fclose($hFile);

$hResult = mysql_query("ALTER TABLE appNotes ADD appId int not null AFTER versionId");

if($hResult)
    echo "The command was executed successfully";
else
    echo "The command failed, error was: " . mysql_error();

$hFile = fopen('runonce', 'w');
fwrite($hFile, '1');
fclose($hFile);

?>