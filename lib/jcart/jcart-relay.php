<?php
error_reporting(E_ALL|E_STRICT); 
require_once("../../baseinit.php");

//ensure resource Session has/is initialized;
//$application->getBootstrap()->bootstrap('session');
//$application->getBootstrap()->getResource('session');

//require_once("Kutu/Application.php");
Kutu_Application::getResource('session');
Kutu_Application::getResource('db');
	
	
// THIS FILE TAKES INPUT FROM AJAX REQUESTS VIA JQUERY post AND get METHODS, THEN PASSES DATA TO JCART
// RETURNS UPDATED CART HTML BACK TO SUBMITTING PAGE

// INCLUDE JCART BEFORE SESSION START

include 'jcart.php';
	
// START SESSION
//session_start();
Zend_Session::start();

// INITIALIZE JCART AFTER SESSION START
$cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();

// PROCESS INPUT AND RETURN UPDATED CART HTML
$cart->display_cart($jcart);

?>
