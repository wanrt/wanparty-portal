<?php
include_once('functions.inc.php');
$current = Machine::current();
if($current->isActive()){
	$current->disable();
}
	$current->deny();

header( "Location: http://" . $_SERVER['SERVER_ADDR'] );
?>
