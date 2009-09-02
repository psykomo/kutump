<?php
/*
 * phpGACL - Generic Access Control List
 * Copyright (C) 2002 Mike Benoit
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please join the
 * phpGACL mailing list. http://sourceforge.net/mail/?group_id=57103
 *
 * You may contact the author of phpGACL by e-mail at:
 * ipso@snappymail.ca
 *
 * The latest version of phpGACL can be obtained from:
 * http://phpgacl.sourceforge.net/
 *
 */
require_once(dirname(__FILE__).'/../gacl.class.php');
require_once(dirname(__FILE__).'/../gacl_api.class.php');
require_once(dirname(__FILE__).'/gacl_admin_api.class.php');

// Set Kutu environment
require_once("../../../baseinit.php");
require_once('Zend/Loader.php');
require_once('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Kutu_');


$aclConfigs = Kutu_Application::getOption('resources');

Kutu_Application::getResource('session');
Zend_Session::start();

$auth =  Zend_Auth::getInstance();
if(!$auth->hasIdentity())
{
	//$this->_redirect(KUTU_ROOT_URL.'/helper/sso/login'.'?returnTo='.$sReturn);
	die('You Must Login');
}
else
{
	// [TODO] else: check if user has access to admin page
	$username = $auth->getIdentity()->username;
	
	$acl = Kutu_Application::getResource('acl');
	if(!($acl->checkAcl("site",'all','user', $username, false,false)))
	{
		die('You are not AUTHORIZED!');
	}
}

// phpGACL Configuration file.
if ( !isset($config_file) ) {
#	$config_file = '../gacl.ini.php';
	$config_file = dirname(__FILE__).'/../gacl.ini.php';
}

//Values supplied in $gacl_options array overwrite those in the config file.
if ( file_exists($config_file) ) {
	$config = parse_ini_file($config_file);

	if ( is_array($config) ) {
		if ( isset($gacl_options) ) {
			$gacl_options = array_merge($config, $gacl_options);
		} else {
			$gacl_options = $config;
		}
	}
	unset($config);
}

//Override gacl config with the settings provided in Kutu Application.ini
$gacl_options['db_host'] = $aclConfigs['acl']['db']['params']['host'];
$gacl_options['db_user'] = $aclConfigs['acl']['db']['params']['username'];
$gacl_options['db_password'] = $aclConfigs['acl']['db']['params']['password'];
$gacl_options['db_name'] = $aclConfigs['acl']['db']['params']['dbname'];

$gacl_api = new gacl_admin_api($gacl_options);

$gacl = &$gacl_api;

$db = &$gacl->db;

//Setup the Smarty Class.
require_once($gacl_options['smarty_dir'].'/Smarty.class.php');

$smarty = new Smarty;
$smarty->compile_check = TRUE;
$smarty->template_dir = $gacl_options['smarty_template_dir'];
$smarty->compile_dir = $gacl_options['smarty_compile_dir'];

/*
 * Email address used in setup.php, please do not change.
 */
$author_email = 'ipso@snappymail.ca';

/*
 * Don't need to show notices, some of them are pretty lame and people get overly worried when they see them.
 * Mean while I will try to fix most of these. ;) Please submit patches if you find any I may have missed.
 */
error_reporting (E_ALL ^ E_NOTICE);

?>
