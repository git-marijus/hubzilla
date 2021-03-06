<?php
/**
 * @file index.php
 *
 * @brief The main entry point to the application.
 *
 * Bootstrap the application, load configuration, load modules, load theme, etc.
 */

/*
 * bootstrap the application
 */
require_once('boot.php');

if(file_exists('.htsite.php'))
	include('.htsite.php');

// our global App object
$a = new App;

/*
 * Load the configuration file which contains our DB credentials.
 * Ignore errors. If the file doesn't exist or is empty, we are running in
 * installation mode.
 */

$a->install = ((file_exists('.htconfig.php') && filesize('.htconfig.php')) ? false : true);

@include('.htconfig.php');

if(! defined('UNO'))
	define('UNO', 0);

$a->timezone = ((x($default_timezone)) ? $default_timezone : 'UTC');
date_default_timezone_set($a->timezone);


/*
 * Try to open the database;
 */

require_once('include/dba/dba_driver.php');

if(! $a->install) {
	$db = dba_factory($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type, $a->install);
	if(! $db->connected) {
		system_unavailable();
	}

	unset($db_host, $db_port, $db_user, $db_pass, $db_data, $db_type);

	/**
	 * Load configs from db. Overwrite configs from .htconfig.php
	 */

	load_config('config');
	load_config('system');
	load_config('feature');

	require_once('include/session.php');
	load_hooks();
	call_hooks('init_1');

}


	$a->language = get_best_language();
	load_translation_table($a->language,$a->install);


/**
 *
 * Important stuff we always need to do.
 *
 * The order of these may be important so use caution if you think they're all
 * intertwingled with no logical order and decide to sort it out. Some of the
 * dependencies have changed, but at least at one time in the recent past - the
 * order was critical to everything working properly
 *
 */

session_start();

/**
 * Language was set earlier, but we can over-ride it in the session.
 * We have to do it here because the session was just now opened.
 */

if(array_key_exists('system_language',$_POST)) {
	if(strlen($_POST['system_language']))
		$_SESSION['language'] = $_POST['system_language'];
	else
		unset($_SESSION['language']);
}
if((x($_SESSION, 'language')) && ($_SESSION['language'] !== $lang)) {
	$a->language = $_SESSION['language'];
	load_translation_table($a->language);
}

if((x($_GET,'zid')) && (! $a->install)) {
	$a->query_string = strip_zids($a->query_string);
	if(! local_channel()) {
		$_SESSION['my_address'] = $_GET['zid'];
		zid_init($a);
	}
}

if((x($_SESSION, 'authenticated')) || (x($_POST, 'auth-params')) || ($a->module === 'login'))
	require('include/auth.php');

if(! x($_SESSION, 'sysmsg'))
	$_SESSION['sysmsg'] = array();

if(! x($_SESSION, 'sysmsg_info'))
	$_SESSION['sysmsg_info'] = array();

/*
 * check_config() is responsible for running update scripts. These automatically
 * update the DB schema whenever we push a new one out. It also checks to see if
 * any plugins have been added or removed and reacts accordingly.
 */


if($a->install) {
	/* Allow an exception for the view module so that pcss will be interpreted during installation */
	if($a->module != 'view')
		$a->module = 'setup';
}
else
	check_config($a);

nav_set_selected('nothing');

$arr = array('app_menu' => $a->get_apps());

call_hooks('app_menu', $arr);

$a->set_apps($arr['app_menu']);

$Router = new Zotlabs\Web\Router($a);

/* initialise content region */

if(! x($a->page, 'content'))
	$a->page['content'] = '';


if(! ($a->module === 'setup')) {
	/* set JS cookie */
	if($_COOKIE['jsAvailable'] != 1) {
		$a->page['content'] .= '<script>document.cookie="jsAvailable=1; path=/"; var jsMatch = /\&JS=1/; if (!jsMatch.exec(location.href)) { location.href = location.href + "&JS=1"; }</script>';
		/* emulate JS cookie if cookies are not accepted */
		if ($_GET['JS'] == 1) {
			$_COOKIE['jsAvailable'] = 1;
		}
	}

	call_hooks('page_content_top', $a->page['content']);
}

$Router->Dispatch($a);


// If you're just visiting, let javascript take you home

if(x($_SESSION, 'visitor_home')) {
	$homebase = $_SESSION['visitor_home'];
} elseif(local_channel()) {
	$homebase = $a->get_baseurl() . '/channel/' . $a->channel['channel_address'];
}

if(isset($homebase)) {
	$a->page['content'] .= '<script>var homebase = "' . $homebase . '";</script>';
}

// now that we've been through the module content, see if the page reported
// a permission problem and if so, a 403 response would seem to be in order.

if(stristr(implode("", $_SESSION['sysmsg']), t('Permission denied'))) {
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 ' . t('Permission denied.'));
}


call_hooks('page_end', $a->page['content']);

construct_page($a);

session_write_close();
exit;
