<?php

include("path.php");
include(BASE."include/"."incl.php");
include(BASE."include/"."vote.php");

redirectref();

apidb_header("Testing");

vote_update($HTTP_GET_VARS);

apidb_footer();

?>
