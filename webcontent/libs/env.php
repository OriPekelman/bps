<?php
// Start session
session_start();

// Login states
define("BPS_LOGGED_IN", 0);
define("BPS_LOGGED_OUT", -1);
define("BPS_NO_SUCH_USER", -2);
define("BPS_PASSWD_WRONG", -3);
define("BPS_REG_PENDING", -4);

// Turn this off at some point.
ini_set('display_errors', "On");

//Bring in the user's config file
require_once('/home/content/93/4791793/html/config.php');

// Include pear database handler, smarty
ini_set('include_path',$CFG->dirroot."/libs/pear/:".ini_get('include_path'));
require_once $CFG->dirroot."/libs/pear/MDB2.php";
require_once $CFG->dirroot."/libs/Smarty/Smarty.class.php";

// Setup template object
$t = new Smarty;
$t->template_dir = "$CFG->dirroot/templates/";
$t->compile_dir = "$CFG->dirroot/templates_c/";
$t->cache_dir = "$CFG->dirroot/libs/Smarty/cache/";

// Change comment on these when you're done developing to improve performance
$t->force_compile = true;
$t->compile_check = true;
$t->debugging = false;

// Assign any global smarty values here.
$t->assign('wwwroot', $CFG->wwwroot);
$t->assign('shortbase', $CFG->shortbase);

// Connect to the database
$dsn = "$CFG->dbtype://$CFG->dbuser:$CFG->dbpass@$CFG->dbhost/$CFG->dbname";

$db =& MDB2::factory($dsn);
$noDB = false;
$lockout = false;
$DBerr = "";
/*
if (PEAR::isError($db)) {
	$noDB = true;
	$DBerr = $db->getMessage();
} else {
	$exists = $db->connect();
	if (PEAR::isError($exists)) {
		$noDB = true;
		$DBerr = $exists->getMessage();
	} else {
		$db->setFetchMode(MDB2_FETCHMODE_ASSOC);
		$sql = "SELECT lockoutActive lo from DBInfo";
		$res =& $db->query($sql);
		if (PEAR::isError($res))  {
			$noDB = true;
			$DBerr = $db->getMessage();
		} else {
			if(!( $row = $res->fetchRow() )
				|| ( $row['lo'] != FALSE )) {
				$noDB = true;
				$lockout = true;
				$DBerr = "Lockout is enabled...";
			}
 		}
	}
}
if( $noDB ) {
    $t->assign('heading', "Delphi is not currently available...");
		$reason = $lockout?"scheduled maintenance":"a temporary outage";
		$t->assign('message', "<p>The Delphi system is not currently available, due to ".$reason
			.".</p>Please try back again later.</p><p style=\"display:none\">DB error: ".$DBerr."</p>");
    $t->display('error.tpl');
    die();
}

// Determine user's login state
require_once "$CFG->dirroot/modules/auth/checkLogin.php";
require_once "$CFG->dirroot/modules/admin/authUtils.php";
$login_state = checkLogin();
// echo $login_state;

*/
$login_state = BPS_LOGGED_OUT;

// Assign global UI defaults here
$t->assign('page_title', $CFG->page_title_default);

if( $login_state == DELPHI_LOGGED_IN || $login_state == DELPHI_REG_PENDING){
/*
	$details = getUserDetails($_SESSION['username']);
	$t->assign('currentUser_loggedIn', TRUE);
	$t->assign('currentUser_name', $details['username']);
	$t->assign('currentUser_email', $details['email']);
	$t->assign('currentUser_id', $details['id']);
	$_SESSION['id'] = $details['id'];
	$_SESSION['email'] = $details['email'];
	if( userHasRole( $details['id'], 'Admin' ))
		$t->assign('currentUser_isAdmin', TRUE );
	if( userHasRole( $details['id'], 'AuthorizedStaff' ))
		$t->assign('currentUser_isAuthStaff', TRUE );
*/
} else {
	$t->assign('currentUser_loggedIn', FALSE);
	//	Get the name of the file being called
	// $scriptName = end(explode("/", $_SERVER['SCRIPT_NAME']) );
	// if ( $scriptName != "login.php" ) {
	// 	header( 'Location: ' . $CFG->wwwroot . '/modules/auth/login.php' );
	// 	die();
	// }
}


?>
