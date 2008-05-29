<?php
require("path.php");
require(BASE."include/incl.php");
require_once(BASE."include/comment.php");


Comment::view_comment_body($aClean['iCommentId']);

?>
