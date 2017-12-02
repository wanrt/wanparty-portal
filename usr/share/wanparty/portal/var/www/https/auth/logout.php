<?php
include_once('functions.inc.php');
$current = Machine::current();
$current->disable();
$current->deny();

header( "Location: http://" . $_SERVER['SERVER_ADDR'] );
?>
