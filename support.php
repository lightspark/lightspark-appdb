<?php
/****************/
/* support page */
/****************/

/*
 * application environment
 */ 
include("path.php");
require(BASE."include/"."incl.php");

apidb_header("Help and Support");

?>
<p><big><b>Who Can Help Me Out?</b></big></p>

<p>
If you have questions, comments on the Application Database, you can contact
us at <a href="mailto:<?php echo APPDB_OWNER_EMAIL;?>"><?php echo APPDB_OWNER_EMAIL;?></a>.
</p>

<p>
If you notice something that seems to be wrong, or busticated, there is a way you can 
help us out.<br />
We also have a <a href="http://bugs.winehq.org/">Bug Tracking Database</a>
where you can register bugs. This is the best way to get problems fixed. You can go directly
to the App DB Bug Database by following this
<a href="http://bugs.winehq.org/buglist.cgi?product=WineHQ+Apps+Database">link</a>.
</p>

<p>
If you need more information on this project, there are plenty of resources.
</p>

<ul>
   <li><a href="<?php echo APPDB_OWNER_URL;?>"><?php echo APPDB_OWNER;?></a></li>
   <li><a href="http://www.codeweavers.com">CodeWeavers Home Page</a></li>
</ul>	
<?php
apidb_footer();
?>
