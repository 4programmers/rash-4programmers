<?php
/*
error_reporting(E_ALL);
ini_set('display_errors','On');
*/
include 'util_funcs.php';

$hidelink = 0;


$languages = array('US-english','Finnish');

$captchas = array(array('name'=>'nocaptcha', 'desc'=>'No CAPTCHA'),
		  array('name'=>'42captcha', 'desc'=>'The Ultimate Question CAPTCHA'),
		  array('name'=>'mathcaptcha', 'desc'=>'Math CAPTCHA'),
		  array('name'=>'nhcaptcha', 'desc'=>'NetHack CAPTCHA'));

$captcha_uses = array('flag'=>'Flagging a quote',
		      'add_quote' => 'Adding a quote',
		      'register_user' => 'Registering a normal user');

$templates = array('./templates/bash/bash.php' => 'bash.org lookalike',
		   './templates/rash/rash.php' => 'Rash QMS',
		   './templates/owned/owned.php' => 'i-rox.com owned lookalike',
		   './templates/nhqdb/nhqdb.php' => 'nhqdb');
$def_template = './templates/bash/bash.php';

require 'basetemplate.php';

if (file_exists($def_template)) {
    require $def_template;
} else {
    class TempTemplate extends BaseTemplate  {
    }
    $TEMPLATE = new TempTemplate();
}

$TEMPLATE->printheader('Install Rash Quote Management System');

function remove_quotes($data)
{
    foreach ($data as $k => $v) {
	$v = str_replace("'", "", $v);
	$data[$k] = $v;
    }
    return $data;
}

function mangle_sql($fname, $data)
{
    $sql = file_get_contents($fname);
    foreach ($data as $k => $v) {
	$s = '/\$'.strtoupper($k).'\$/';
	$v = str_replace("'", "", $v);
	$sql = preg_replace($s, $v, $sql);
    }
    return $sql;
}

If (isset($_POST['submit'])) {
    if (file_exists('settings.php')){
	die("settings.php already exists.");
    }
    if (!isset($_POST['template'])) {
	header('Location: install.php');
	exit;
    }
    $data = array('template' => "'".$_POST['template']."'",
		  'phptype' => "'".$_POST['phptype']."'",
		  'hostspec' => "'".$_POST['hostspec']."'",
		  'port' => "''",
		  'socket' => "''",
		  'database' => "'".$_POST['database']."'",
		  'username' => "'".$_POST['username']."'",
		  'password' => "'".$_POST['password']."'",
		  'db_table_prefix' => "'".$_POST['db_table_prefix']."'",
		  'site_short_title' => "'".$_POST['site_short_title']."'",
		  'site_long_title' => "'".$_POST['site_long_title']."'",
		  'prefix_short_title' => (($_POST['prefix_short_title'] == 'on') ? 1 : 0),
		  'rss_url' => "'".preg_replace('/\/$/','',$_POST['rss_url'])."'",
		  'rss_title' => "'".$_POST['rss_title']."'",
		  'rss_desc' => "'".$_POST['rss_desc']."'",
		  'rss_entries' => (!isset($_POST['rss_entries']) || ($_POST['rss_entries'] < 1)) ? 15 : $_POST['rss_entries'],
		  'secret_salt' => "'".$_POST['secret_salt']."'",
		  'language' => "'".$_POST['language']."'",
		  'captcha' => "'".$_POST['captcha']."'",
		  'use_captcha' => "array(".(isset($_POST['use_captcha']) ? ("'".implode("'=>1, '", $_POST['use_captcha'])."'=>1"): '').")",
		  'spam_regex' => "'".$_POST['spam_regex']."'",
		  'auto_block_spam_ip' => $_POST['auto_block_spam_ip'],
		  'spam_expire_time' => $_POST['spam_expire_time'],
		  'admin_email' => "'".$_POST['admin_email']."'",
		  'quote_limit' => $_POST['quote_limit'],
		  'page_limit' => $_POST['page_limit'],
		  'quote_list_limit' => $_POST['quote_list_limit'],
		  'min_latest' => $_POST['min_latest'],
		  'min_quote_length' => $_POST['min_quote_length'],
		  'moderated_quotes' => ((isset($_POST['moderated_quotes']) && ($_POST['moderated_quotes'] == 'on')) ? 1 : 0),
		  'login_required' => ((isset($_POST['login_required']) && ($_POST['login_required'] == 'on')) ? 1 : 0),
		  'auto_flagged_quotes' => (($_POST['auto_flagged_quotes'] == 'on') ? 0 : 1),
		  'public_queue' => ((isset($_POST['public_queue']) && ($_POST['public_queue'] == 'on')) ? 0 : 1),
		  'timezone' => "'".$_POST['timezone']."'",
		  'news_time_format' => "'".$_POST['news_time_format']."'",
		  'quote_time_format' => "'".$_POST['quote_time_format']."'",
		  'GET_SEPARATOR' => "ini_get('arg_separator.output')",
		  'GET_SEPARATOR_HTML' => 'htmlspecialchars($CONFIG[\'GET_SEPARATOR\'], ENT_QUOTES)');
    if (!write_settings('settings.php', $data)) {
	die("Sorry, cannot write settings.php");
    }

    if (!file_exists('settings.php')){
	die("settings.php does not exist.");
    }

    $salt = str_rand();

    $sqldata = array_merge($data, array(
					'QUOTETABLE' => db_tablename('quotes'),
					'QUEUETABLE' => db_tablename('queue'),
					'USERSTABLE' => db_tablename('users'),
					'TRACKINGTABLE' => db_tablename('tracking'),
					'NEWSTABLE' => db_tablename('news'),
					'SPAMTABLE' => db_tablename('spamlog'),
					'DUPETABLE' => db_tablename('dupes'),
					'ADMINUSER' => "'".$_POST['adminuser']."'",
					'ADMINPASS' => "'\\$1".crypt($_POST['adminpass'], "$1$".substr($salt, 0, 8)."$")."'",
					'ADMINSALT' => '\'\\$1\\$'.$salt.'\$\''
					));

    $sql = mangle_sql('install.sql', $sqldata);

    print '<pre>'.$sql.'</pre>';

    $CONFIG = remove_quotes($data);
    include 'db.php';
    $db = get_db($CONFIG);
    if ($db) {
	db_query($sql);
	$db = null;
    } else {
	print '<p>Sorry, cannot access the database. You may need to do the commands manually.';
    }
} else {
    if(!file_exists('settings.php')){

	if (!write_settings('settings.php', null)) {
	    die('Cannot write settings.');
	}
	@unlink('settings.php');

	function mk_rss_url()
	{
	    return 'http://'.$_SERVER['SERVER_NAME'] . preg_replace('/\/install.php$/', '', $_SERVER['REQUEST_URI']);
	}

	$hidelink = 1;

?>
<h2>Install</h2>
<form action="./install.php" method="post">
<table>
 <tr>
  <td>Template</td>
  <td><select name="template"><?php foreach ($templates as $k=>$v) { echo '<option value="'.$k.'">'.$v; } ?></select>
 </tr>
 <tr>
	<td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>DB Type
  <td><input type="text" name="phptype" value="mysql">
 </tr>
 <tr>
  <td>DB Hostname
  <td><input type="text" name="hostspec" value="localhost">
 </tr>
 <tr>
  <td>DB Database
  <td><input type="text" name="database" value="rash">(which database to use)
 </tr>
 <tr>
  <td>DB Username
  <td><input type="text" name="username" value="username">
 </tr>
 <tr>
  <td>DB Password
  <td><input type="password" name="password" value="password">
 </tr>
 <tr>
  <td>DB table prefix
  <td><input type="text" name="db_table_prefix" value="rash">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Admin Username
  <td><input type="text" name="adminuser" value="admin"> (Leave empty to not create one)
 </tr>
 <tr>
  <td>Admin Password
  <td><input type="password" name="adminpass" value="password">
 </tr>
 <tr>
  <td>Admin EMail
  <td><input type="text" name="admin_email" value="qdb@<?=$_SERVER['SERVER_NAME'];?>">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Secret Salt
  <td><input type="text" name="secret_salt" value="<?=str_rand();?>"> (Used to encrypt some things)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Site Language
  <td><select name="language"><?php foreach($languages as $l) { echo '<option value="'.$l.'">'.$l; } ?></select>
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Site Short Title
  <td><input type="text" name="site_short_title" value="QMS">
 </tr>
 <tr>
  <td>Prefix Title
  <td><input type="checkbox" name="prefix_short_title" checked> (Prefix "<em>Site Short Title</em>: " to page titles?)
 </tr>
 <tr>
  <td>Site Long Title
  <td><input type="text" name="site_long_title" value="Quote Management System" size="40">
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>RSS URL
  <td><input type="text" name="rss_url" value="<?=mk_rss_url();?>" size="40">
 </tr>
 <tr>
  <td>RSS Title
  <td><input type="text" name="rss_title" value="Rash QDB">
 </tr>
 <tr>
  <td>RSS Description
  <td><input type="text" name="rss_desc" value="Quote Database for the IRC channel" size="40">
 </tr>
 <tr>
  <td>RSS Entries
  <td><input type="text" name="rss_entries" value="15" size="4"> (number of quotes shown in RSS feed)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Quote limit
  <td><input type="text" name="quote_limit" value="10" size="4"> (number of quotes shown per page when browsing)
 </tr>
 <tr>
  <td>Page limit
  <td><input type="text" name="page_limit" value="5" size="4"> (how many page numbers shown when browsing)
 </tr>
 <tr>
  <td>Quote List limit
  <td><input type="text" name="quote_list_limit" value="50" size="4"> (how many quotes are shown in non-browse pages, eg. ?top)
 </tr>
 <tr>
  <td>Min Latest Quotes
  <td><input type="text" name="min_latest" value="3" size="4"> (minimum number of quotes shown in ?latest)
 </tr>
 <tr>
  <td>Min Quote Length
  <td><input type="text" name="min_quote_length" value="15" size="4"> (Minimum acceptable quote length, in characters)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Moderated
  <td><input type="checkbox" name="moderated_quotes" checked> Do quotes need to be accepted by a moderator?
 </tr>
 <tr>
  <td>Public Queue
  <td><input type="checkbox" name="public_queue" checked> Can users view and vote quotes in the moderation queue?
 </tr>
 <tr>
  <td>Quote flagging
  <td><input type="checkbox" name="auto_flagged_quotes" checked> Can users flag quotes for admin attention?
 </tr>
 <tr>
  <td>CAPTCHA
  <td><select name="captcha"><?php foreach($captchas as $c) { echo '<option value="'.$c['name'].'">'.$c['desc']; } ?></select>
 </tr>
 <tr>
  <td>Use CAPTCHA For
  <td><?php foreach ($captcha_uses as $k=>$v) { echo '<input type="checkbox" name="use_captcha[]" value="'.$k.'" checked>'.$v.'<br>'; } ?>
 </tr>
 <tr>
  <td>Spam Regex
       <td><input type="text" name="spam_regex" value=""> (Any submitted quote matching this regex will go to spam table)
 </tr>
 <tr>
  <td>Autoblock spam by IP
       <td><input type="text" name="auto_block_spam_ip" value="1"> (Quote adding is automatically blocked, if it is submitted from IP address appearing this many times in the spamlog)
 </tr>
 <tr>
  <td>Spam TTL
       <td><input type="text" name="spam_expire_time" value="0"> (Spam is removed from spamlog after this many seconds. 0=never)
 </tr>
 <tr>
  <td>User Login required
  <td><input type="checkbox" name="login_required"> Do users need to register and login before voting/adding/flagging?
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>Timezone
  <td><input type="text" name="timezone" value="America/New_York"> (See <a href="http://www.php.net/manual/en/timezones.php">list of supported timezones</a>)
 </tr>
 <tr>
  <td>News time format
				 <td><input type="text" name="news_time_format" value="Y-m-d"> (example: <em><?=date("Y-m-d");?></em>, See <a href="http://php.net/manual/en/function.date.php">list of date format characters</a>)
 </tr>
 <tr>
  <td>Quote time format
  <td><input type="text" name="quote_time_format" value="F j, Y"> (example: <em><?=date("F j, Y");?></em>)
 </tr>
 <tr>
  <td>&nbsp;</td><td>&nbsp;</td>
 </tr>
 <tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="Submit" name="submit">
 </tr>
 </table>
 </form>
<?php
    } else {
	print "<p>settings.php already exists.";
    }
}
if (!$hidelink)
    print '<p><a href="./">QDB main page</a></p>';
$TEMPLATE->printfooter();
