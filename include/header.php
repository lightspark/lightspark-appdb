<?php
/*********************************/
/* Application Database - Header */
/*********************************/
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>Lightspark <?php echo $title; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta HTTP-EQUIV="Expires" CONTENT="Mon, 06 Jan 1990 00:00:01 GMT">
    <meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <meta name="description" content="Open Source Flash Player.">
    <meta name="keywords" content="flash">
    <meta name="robots" content="index, follow">
    <meta name="copyright" content="Copyright lightspark.sourceforge.net All Rights Reserved.">
    <meta name="language" content="English">
    <meta name="revisit-after" content="1">
    <link rel="stylesheet" href="<?php echo BASE; ?>styles.css" type="text/css" media="screen">
    <script language="JavaScript" src="<?php echo BASE; ?>jquery.js" type="text/javascript"></script>
    <script language="JavaScript" src="<?php echo BASE; ?>utils.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?php echo BASE; ?>apidb.css" type="text/css">
    <link rel="stylesheet" href="<?php echo BASE; ?>application.css" type="text/css">
    <script type="text/javascript" 
    src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.2/prototype.js"></script>
    <script src="<?php echo BASE; ?>scripts.js" type="text/javascript"></script>
    <link title="AppDB" type="application/opensearchdescription+xml" rel="search" href="<?php echo BASE; ?>opensearch.xml">
</head>
<body>

<div id="logo_text"><a href="<?php echo BASE; ?>"><img src="<?php echo BASE; ?>images/lightspark_logo_medium.png" alt="Lightspark" title="Lightspark"></a></div>

<div id="logo_blurb"><?php echo preg_replace("/^ - /", "", $title); ?></div>

<div id="tabs">
    <ul>
        <li><a href="http://lightspark.sourceforge.net/">Lightspark</a></li>
        <li><a href="https://bugs.launchpad.net/lightspark/">Bugs</a></li>
        <li class="s"><a href="http://lightspark.sourceforge.net/appdb/">AppDB</a></li>
        <li><a href="https://github.com/lightspark/lightspark/">GitHub</a></li>
    </ul>
</div>

<div id="main_content">

  <div class="rbox">
  <b class="rtop"><b class="r1"></b><b class="r2"></b><b class="r3"></b><b class="r4"></b></b>
    <div class="content" style="padding: 20px 20px 10px 80px">
    <!-- Start Content -->
