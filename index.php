<?php

//if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors','On');
//}

if (!file_exists('settings.php')) {
    header("Location: install.php");
    exit;
}

session_start();

require('settings.php');
require_once('common.php');

if (!isset($CONFIG['quote_list_limit']) || !is_int($CONFIG['quote_list_limit'])) $CONFIG['quote_list_limit'] = 50;
if (!isset($CONFIG['rss_entries']) || ($CONFIG['rss_entries'] < 1)) $CONFIG['rss_entries'] = 15;
if (!isset($CONFIG['min_quote_length'])) $CONFIG['min_quote_length'] = 15;

require('db.php');
require('util_funcs.php');

if (isset($_GET['nolang'])) {
    mk_cookie('nolang', $_GET['nolang']);
    header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
    exit;
}

if (!(isset($_COOKIE['nolang']) && ($_COOKIE['nolang'] == '1')))
    require("language/{$CONFIG['language']}.lng");

require('basecaptcha.php');
require("captcha/{$CONFIG['captcha']}.php");

$CAPTCHA->init_settings($CONFIG['use_captcha']);

require('basetemplate.php');

if (isset($_GET['template']) && preg_match('/^[a-z]+$/', $_GET['template'])) {
    mk_cookie('template', $_GET['template']);
    header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
    exit;
}

if (isset($_COOKIE['template']) && preg_match('/^[a-z]+$/', $_COOKIE['template'])) {
    $t = $_COOKIE['template'];
    $tf = './templates/'.$t.'/'.$t.'.php';
    if (file_exists($tf))
	$CONFIG['template'] = $tf;
}

require($CONFIG['template']);

date_default_timezone_set($CONFIG['timezone']);

if (isset($_COOKIE['lastvisit']) && !isset($_SESSION['lastvisit'])) {
    $_SESSION['lastvisit'] = $_COOKIE['lastvisit'];
}
mk_cookie('lastvisit', time());

set_voteip($CONFIG['secret_salt']);

$db = get_db($CONFIG, $TEMPLATE);

autologin();

$mainmenu = array(array('url' => '?latest', 'id' => 'site_nav_latest', 'txt' => 'menu_latest'),
		  array('url' => '?random2', 'id' => 'site_nav_random2', 'txt' => 'menu_random2'),
		  array('url' => '?bottom', 'id' => 'site_nav_bottom', 'txt' => 'menu_bottom'),
		  array('url' => '?top', 'id' => 'site_nav_top', 'txt' => 'menu_top'));

if (isset($CONFIG['public_queue']) && ($CONFIG['public_queue'] == 1) &&
    isset($CONFIG['moderated_quotes']) && ($CONFIG['moderated_quotes'] == 1)) {
    $mainmenu[] = array('url' => '?queue', 'id' => 'site_nav_queue', 'txt' => 'menu_queue');
}

$mainmenu[] = array('url' => '?search', 'id' => 'site_nav_search', 'txt' => 'menu_search');

if ((isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && isset($_SESSION['logged_in']))
    || !isset($CONFIG['login_required']) || ($CONFIG['login_required'] == 0))
    $mainmenu[] = array('url' => '?add', 'id' => 'site_nav_add', 'txt' => 'menu_contribute');

if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
    if (!isset($_SESSION['logged_in'])) {
	$mainmenu[] = array('url' => '?login', 'id' => 'site_nav_login', 'txt' => 'menu_login');
    } else {
	$mainmenu[] = array('url' => '?logout', 'id' => 'site_nav_logout', 'txt' => 'menu_logout');
    }
}


if (isset($_SESSION['logged_in'])) {
    $adminmenu = array();
    if ($_SESSION['level'] < USER_NORMAL) {
	$adminmenu[] = array('url' => '?adminqueue', 'id' => 'site_admin_nav_queue', 'txt' => 'menu_adminqueue');
	$adminmenu[] = array('url' => '?flag_queue', 'id' => 'site_admin_nav_flagged', 'txt' => 'menu_flagged');
    }
    if ($_SESSION['level'] <= USER_ADMIN) {
	$adminmenu[] = array('url' => '?import', 'id' => 'site_admin_nav_import', 'txt' => 'import_quotes');
	$adminmenu[] = array('url' => '?add_news', 'id' => 'site_admin_nav_add-news', 'txt' => 'menu_addnews');
	$adminmenu[] = array('url' => '?edit_news', 'id' => 'site_admin_nav_edit-news', 'txt' => 'menu_editnews');
    }
    if ($_SESSION['level'] <= USER_SUPERUSER) {
	$adminmenu[] = array('url' => '?users', 'id' => 'site_admin_nav_users', 'txt' => 'menu_users');
	$adminmenu[] = array('url' => '?add_user', 'id' => 'site_admin_nav_add-user', 'txt' => 'menu_adduser');
    }
    $adminmenu[] = array('url' => '?change_pw', 'id' => 'site_admin_nav_change-password', 'txt' => 'menu_changepass');
    $adminmenu[] = array('url' => '?logout', 'id' => 'site_admin_nav_logout', 'txt' => 'menu_logout');
} else $adminmenu = null;

$TEMPLATE->set_menu(0, $mainmenu);
$TEMPLATE->set_menu(1, $adminmenu);


function get_db_stats()
{
    $ret['pending_quotes'] = db_query_singlevalue('SELECT count(id) FROM '.db_tablename('queue'));
    $ret['approved_quotes'] = db_query_singlevalue('SELECT count(id) FROM '.db_tablename('quotes').' WHERE (flag!=3)');
    return $ret;
}

function handle_captcha($type, $func, &$param=null)
{
    global $CAPTCHA, $TEMPLATE;
    switch ($CAPTCHA->check_CAPTCHA($type)) {
    case 0:
	if (is_callable($func)) return call_user_func($func, $param);
	break;
    case 1: $TEMPLATE->add_message(lang('captcha_wronganswer'));
	break;
    case 2: $TEMPLATE->add_message(lang('captcha_wrongid'));
	break;
    default: break;
    }
    return FALSE;
}

function rash_rss()
{
    global $CONFIG, $TEMPLATE;
    $res = db_query('SELECT * FROM '.db_tablename('quotes').' WHERE (flag!=3) ORDER BY id DESC LIMIT ' . $CONFIG['rss_entries']);
    $items = '';
    while($row=$res->fetch()) {
	$title = $CONFIG['rss_url']."/?".$row['id'];
	$desc = mangle_quote_text(htmlspecialchars($row['quote']));
	$items .= $TEMPLATE->rss_feed_item($title, $desc, $title);
    }
    print $TEMPLATE->rss_feed($CONFIG['rss_title'], $CONFIG['rss_desc'], $CONFIG['rss_url'], $items);
}

function flag_do_inner($row)
{
    global $TEMPLATE;
    if($row['flag'] == 2){
	$TEMPLATE->add_message(lang('flag_previously_flagged'));
    }
    elseif($row['flag'] == 1){
	$TEMPLATE->add_message(lang('flag_currently_flagged'));
    }
    elseif($row['flag'] == 3){
	/* hidden */
    }
    else{
	$TEMPLATE->add_message(lang('flag_quote_flagged'));
	db_query("UPDATE ".db_tablename('quotes')." SET flag = 1 WHERE id = ?", $row['id']);
	$row['flag'] = 1;
    }
    return $row;
}

function flag($quote_num, $method)
{
    global $CONFIG, $TEMPLATE, $CAPTCHA, $db;

    $row = $db->query("SELECT id,flag,quote FROM ".db_tablename('quotes')." WHERE (flag!=3) AND id = ".$db->quote((int)$quote_num)." LIMIT 1")->fetch();

    if ($method == 'verdict') {
	$ret = handle_captcha('flag', 'flag_do_inner', $row);
	if (is_string($ret)) $TEMPLATE->add_message($ret);
    } else {
	if($row['flag'] == 2){
	    $TEMPLATE->add_message(lang('flag_previously_flagged'));
	}
	elseif($row['flag'] == 1){
	    $TEMPLATE->add_message(lang('flag_currently_flagged'));
	}
    }
    print $TEMPLATE->flag_page($quote_num, mangle_quote_text($row['quote']), $row['flag']);
}

function vote($quote_num, $method, $ajaxy=FALSE)
{
    global $db, $TEMPLATE;

    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "SELECT quote_id FROM ".db_tablename('tracking')." WHERE user_ip=".$db->quote($ip).' AND quote_id='.$db->quote((int)$quote_num);
    $qid = $db->query($sql)->fetch();

    if (isset($qid) && $qid['quote_id'] == $quote_num) {
	if ($ajaxy) return 'ALREADY_VOTED';
	$TEMPLATE->add_message(lang('tracking_check_2'));
	return;
    }

    $vote = 0;
    if ($method == "plus") {
	$vote = 1;
	$db->query("UPDATE ".db_tablename('quotes')." SET rating = rating+1 WHERE (flag!=3) AND id = ".$db->quote((int)$quote_num));
    } elseif ($method == "minus") {
	$vote = -1;
	$db->query("UPDATE ".db_tablename('quotes')." SET rating = rating-1 WHERE (flag!=3) AND id = ".$db->quote((int)$quote_num));
    }
    if ($vote != 0) {
	$t = time();
	$res = $db->query("INSERT INTO ".db_tablename('tracking')." (user_ip, quote_id, vote, date) VALUES(".$db->quote($ip).", ".$db->quote($quote_num).", ".$vote.", ".$db->quote($t).")");
	if ($ajaxy) return 'VOTE_OK';
	$TEMPLATE->add_message(lang('tracking_check_1'));
    }
}


function news_page()
{
    global $db, $TEMPLATE, $CONFIG;
    $res = $db->query("SELECT * FROM ".db_tablename('news')." ORDER BY date desc");
    $news = '';
    while ($row = $res->fetch()) {
	$news .= $TEMPLATE->news_item($row['news'], date($CONFIG['news_time_format'], $row['date']));
    }
    print $TEMPLATE->news_page($news);
}

function home_generation()
{
    global $db, $TEMPLATE, $CONFIG;

    $sql = 'SELECT * FROM '.db_tablename('news').' ORDER BY date desc LIMIT 5';

    $stmt = $db->query($sql);

    check_db_res($stmt, $sql);

    $news = '';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$news .= $TEMPLATE->news_item($row['news'], date($CONFIG['news_time_format'], $row['date']));
    }

    print $TEMPLATE->main_page($news);
}

function normalize_quote_line($s)
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z]/', ' ', $s);
    $s = preg_replace('/\b[a-z][a-z]?\b/', '', $s);
    $s = preg_replace('/\b(the|teh)\b/', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = trim($s);
    return $s;
}

function find_maybe_dupes($quotetxt)
{
    global $db, $TEMPLATE, $CONFIG;

    $ret = array();

    $sql = 'SELECT DISTINCT(id) FROM '.db_tablename('quotes').' WHERE quote = ?';
    $stha = $db->prepare($sql);
    $stha->execute(array($quotetxt));
    $row = $stha->fetchAll(PDO::FETCH_NUM);
    foreach ($row as $r) {
	array_push($ret, $r[0]);
    }
    if (count($ret)) return $ret;

    $qarr = preg_split('/\n/', html_entity_decode($quotetxt));

    $sql = 'SELECT DISTINCT(quote_id) FROM '.db_tablename('dupes').' WHERE normalized IN (';

    $lines = array();

    $added = 0;
    foreach ($qarr as $l) {
	$l = normalize_quote_line($l);
	if (!((strlen($l) < 5) || (strpos($l,' ')===FALSE))) {
	    if ($added) $sql .= ', ';
	    $sql .= '?';
	    $added = 1;
	    array_push($lines, $l);
	}
    }

    $sql .= ')';

    $stha = $db->prepare($sql);
    $stha->execute($lines);
    $row = $stha->fetchAll(PDO::FETCH_NUM);

    foreach ($row as $r) {
	array_push($ret, $r[0]);
    }

    return $ret;
}

function populate_dupe_table()
{
    global $db, $TEMPLATE, $CONFIG;

    $sql = 'SELECT * FROM '.db_tablename('quotes').' ORDER BY id asc';
    $stmt = $db->query($sql);
    check_db_res($stmt, $sql);

    $quotedata = $stmt->fetchAll(PDO::FETCH_ASSOC);

    db_query('DELETE FROM '.db_tablename('dupes'));

    $sql = 'INSERT INTO '.db_tablename('dupes').' (normalized, quote_id) VALUES (?, ?)';
    $stha = $db->prepare($sql);

    print 'populating dupe table:';

    for ($i = 0; $i < count($quotedata); $i++) {
	$q = $quotedata[$i];
	$qid = $q['id'];

	$quotetxt = $q['quote'];
	$qarr = preg_split('/\n/', html_entity_decode($quotetxt));
	foreach ($qarr as $l) {
	    $l = normalize_quote_line($l);
	    if (!((strlen($l) < 5) || (strpos($l,' ')===FALSE)))
		$stha->execute(array($l, $qid));
	}
	if (($i % 100) == 0) print '<br>';
	print '.';
    }
    print '<br>done';
}

function reorder_quotes()
{
    global $db, $TEMPLATE, $CONFIG;

    $sql = 'SELECT * FROM '.db_tablename('quotes').' ORDER BY id asc';
    $stmt = $db->query($sql);
    check_db_res($stmt, $sql);

    $quotedata = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = 'SELECT * FROM '.db_tablename('tracking').' WHERE quote_id = ?';
    $sth = $db->prepare($sql);
    for ($i = 0; $i < count($quotedata); $i++) {
	$q = $quotedata[$i];
	$sth->execute(array($q['id']));
	$quotedata[$i]['trackingdata'] = $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    $sql = 'DELETE FROM '.db_tablename('quotes');
    db_query($sql);
    $sql = 'DELETE FROM '.db_tablename('tracking');
    db_query($sql);
    $sql = 'ALTER TABLE '.db_tablename('quotes').' AUTO_INCREMENT=0';
    db_query($sql);
    $sql = 'ALTER TABLE '.db_tablename('tracking').' AUTO_INCREMENT=0';
    db_query($sql);

    $sql = 'INSERT INTO '.db_tablename('quotes').' (quote, rating, flag, date, submitip) VALUES (?, ?, ?, ?, ?)';
    $stha = $db->prepare($sql);

    $sql = 'INSERT INTO '.db_tablename('tracking').' (user_ip, quote_id, vote) VALUES (?, ?, ?)';
    $sthb = $db->prepare($sql);

    print 'Reordering the quotes...<br>';

    for ($i = 0; $i < count($quotedata); $i++) {
	$q = $quotedata[$i];
	unset($q['id']);
	$tracking = $q['trackingdata'];
	unset($q['trackingdata']);
	$stha->execute(array($q['quote'], $q['rating'], $q['flag'], $q['date'], $q['submitip']));
	$qvote = 0;
	foreach ($tracking as $t) {
	    $sthb->execute(array($t['user_ip'], ($i+1), $t['vote']));
	    $qvote += ((int)$t['vote']);
	}
	if ($qvote != $q['rating']) print "Quote $i has wrong rating (is ".$q['rating'].", should be $qvote<br>";
    }
    print '<br>DONE';
}


function get_table_data($sql, $params=NULL)
{
    global $db, $TEMPLATE, $CONFIG;
    $sth = $db->prepare($sql);
    $sth->execute($params);
    $dat = $sth->fetchAll(PDO::FETCH_ASSOC);
    if (count($dat)) {
	$s = '';
	$k = array_keys($dat[0]);
	$s .= '<table class="dbtable">';
	$s .= '<tr><th>'. join('</th><th>', $k).'</th></tr>';
	foreach ($dat as $t) {
	    $s .= '<tr>';
	    foreach ($k as $kk) {
		$s .= '<td>'. $t[$kk].'</td>';
	    }
	    $s .= '</tr>';
	}
	$s .= '</table>';
	return $s;
    }
    return '';
}

function show_quote_voters($quoteid)
{
    print get_table_data('SELECT * FROM '.db_tablename('tracking').' WHERE quote_id = ?', array($quoteid));
}

function show_spam()
{
    print get_table_data('SELECT * FROM '.db_tablename('spamlog'));
}


/************************************************************************
************************************************************************/

function page_numbers($origin, $quote_limit, $page_default, $page_limit)
{
    global $CONFIG, $db;
    $numrows = $db->query("SELECT COUNT(id) AS cnt FROM ".db_tablename('quotes').' WHERE (flag!=3)')->fetch();
    $testrows = $numrows['cnt'];
    $parts = array();

    $pagenum = 0;
    $sep = '<span class="sep">&nbsp;&nbsp;</span>';
    $skipamount = 10;

    do {
	$pagenum++;
        $testrows -= $quote_limit;
    } while ($testrows > 0);

    if(!($page_limit % 2))
	$page_limit += 1;

    if(($page_limit == 1) || ($page_limit < 0) || (!$page_limit))
	$page_limit = 5;

    $page_base = 0;
    do {
	$page_base++;
	$page_limit -= 2;
    } while ($page_limit > 1);

    if ($page_default != 1) {
	array_push($parts, '<a class="first" href="?'.urlargs(strtolower($origin),'1').'">'.lang('page_first').'</a>');
    } else {
	array_push($parts, '<span class="first">'.lang('page_first').'</span>');
    }

    if ($page_default-$skipamount >= 1) {
	array_push($parts, '<a class="skipback" href="?'.urlargs(strtolower($origin),
					     ((($page_default-$skipamount) > 1) ? ($page_default-$skipamount) : (1)))
		   .'">'.-($skipamount).'</a>');
    } else {
	array_push($parts, '<span class="skipback">'.-($skipamount).'</span>');
    }

    if (($page_default - $page_base) > 1) {
	array_push($parts, '<span class="ellipsis">...</span>');
    } else {
	array_push($parts, '<span class="noellipsis"></span>');
    }
    $x = ($page_default - $page_base);

    do {
	if($x > 0)
	    array_push($parts, '<a href="?'.urlargs(strtolower($origin),$x).'">'.$x.'</a>');
	else
	    array_push($parts, '<span class="nopage"></span>');
	$x++;
    } while ($x < $page_default);

    array_push($parts, '<span class="currpage"><b>'.$page_default.'</b></span>');

    $x = ($page_default + 1);

    do {
	if($x <= $pagenum)
	    array_push($parts, '<a href="?'.urlargs(strtolower($origin),$x).'">'.$x.'</a>');
	else
	    array_push($parts, '<span class="nopage"></span>');
	$x++;
    } while ($x < ($page_default + $page_base + 1));

    if (($page_default + $page_base) < $pagenum) {
	array_push($parts, '<span class="ellipsis">...</span>');
    } else {
	array_push($parts, '<span class="noellipsis"></span>');
    }

    if ($page_default+$skipamount <= $pagenum) {
	array_push($parts, '<a class="skipfwd" href="?'.urlargs(strtolower($origin),
				     ((($page_default+$skipamount) < $pagenum) ? ($page_default+$skipamount) : ($pagenum)))
		   .'">+'.$skipamount.'</a>');
    } else {
	array_push($parts, '<span class="skipfwd">+'.$skipamount.'</span>');
    }

    if ($page_default < $pagenum) {
	array_push($parts, '<a class="last" href="?'.urlargs(strtolower($origin),$pagenum).'">'.lang('page_last').'</a>');
    } else {
	array_push($parts, '<span class="last">'.lang('page_last').'</span>');
    }

    return '<div class="quote_pagenums">' . join($sep, $parts) . "</div>\n";
}


function edit_quote_button($quoteid)
{
    global $TEMPLATE;
    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
	return $TEMPLATE->edit_quote_button($quoteid);
    }
    return '';
}

function user_can_vote_quote($quoteid)
{
    global $CONFIG, $db;

    $ip = $_SERVER['REMOTE_ADDR'];
    $row = $db->query('SELECT vote FROM '.db_tablename('tracking').' WHERE user_ip='.$db->quote($ip).' AND quote_id='.$db->quote((int)$quoteid))->fetch();

    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
	return 2;

    if (isset($row['vote']) && $row['vote']) return 1;
    return 0;
}


/************************************************************************
************************************************************************/

function quote_generation($query, $origin, $page = 1, $quote_limit = 50, $page_limit = 10)
{
    global $CONFIG, $TEMPLATE, $db;
    $pagenums = '';
    if ($page != -1) {
	if(!$page)
	    $page = 1;
	$pagenums = page_numbers($origin, $quote_limit, $page, $page_limit);
    }
    $up_lim = ($quote_limit * $page);
    $low_lim = $up_lim - $quote_limit;
    if($page != -1){
	$query .= " LIMIT $low_lim,$quote_limit";
    }

    $res = $db->query($query);
    if (!$res) {
	print '<p>Query: '.$query.'<p>';
	print_r($db->errorInfo());
	die();
    }

    $nquotes = 0;
    $inner = '';
    while ($row = $res->fetch()) {
	$nquotes++;
	$canvote = user_can_vote_quote($row['id']);
	$datefmt = date($CONFIG['quote_time_format'], $row['date']);
	$canflag = ($row['flag'] == 0);
	$inner .= $TEMPLATE->quote_iter($row['id'], $row['rating'], mangle_quote_text($row['quote']), $canflag, $canvote, $datefmt);
    }

    if (!$nquotes)
	$TEMPLATE->add_message(lang('no_quote'));

    print $TEMPLATE->quote_list($origin, $pagenums, $inner);
}


function edit_news($method, $id)
{
    global $CONFIG, $TEMPLATE, $db;
    $news = '';

    if ($method == 'edit') {
	$row = $db->query("SELECT * FROM ".db_tablename('news')." WHERE id=".$db->quote((int)$id))->fetch();
	$newstxt = preg_replace('/\<br \/\>/', '', $row['news']);
	$news = $TEMPLATE->edit_news_form($row['id'], $newstxt);
    } else if ($method == 'update') {
	if (isset($_POST['preview'])) {
	    $newstxt = nl2br(trim($_POST['news']));
	    $news = $TEMPLATE->news_item($newstxt, date($CONFIG['news_time_format'], time()));
	    $newstxt = preg_replace('/\<br \/\>/', '', $newstxt);
	    $news .= $TEMPLATE->edit_news_form($id, $newstxt);
	} else if (isset($_POST['delete'])) {
	    if (isset($_POST['verify_delete'])) {
		$db->query("DELETE FROM ".db_tablename('news')." WHERE id=".$db->quote((int)$id));
		$TEMPLATE->add_message(lang('news_item_deleted'));
	    } else {
		$newstxt = trim($_POST['news']);
		$news .= $TEMPLATE->edit_news_form($id, $newstxt);
		$TEMPLATE->add_message(lang('news_item_delete_no_verify'));
	    }
	} else {
	    $newstxt = nl2br(trim($_POST['news']));
	    $db->query("UPDATE ".db_tablename('news')." SET news=".$db->quote($newstxt)." WHERE id=".$db->quote((int)$id));
	    $TEMPLATE->add_message(lang('news_item_saved'));
	    $id = null;
	}
    }

    $res = $db->query("SELECT * FROM ".db_tablename('news')." ORDER BY date DESC");
    while ($row = $res->fetch()) {
	$mode = 1;
	if ($row['id'] == $id) $mode = 2;
	$news .= $TEMPLATE->news_item($row['news'], date($CONFIG['news_time_format'], $row['date']), $row['id'], $mode);
    }

    print $TEMPLATE->edit_news_page($news);
}


function add_news($method)
{
    global $CONFIG, $TEMPLATE, $db;
    $innerhtml = null;
    $rawnews = '';
    if($method == 'submit') {
	$rawnews = trim($_POST['news']);
	$news = nl2br($rawnews);
	if (isset($_POST['preview'])) {
	    $innerhtml = $TEMPLATE->news_item($news, date($CONFIG['news_time_format'], time()));
	} else {
	    $db->query("INSERT INTO ".db_tablename('news')." (news,date) VALUES(".$db->quote($news).", ".time().");");
	    $TEMPLATE->add_message(lang('news_added'));
	    $rawnews = '';
	}
    }

    print $TEMPLATE->add_news_page($innerhtml, htmlspecialchars($rawnews));
}

function user_level_select($selected=USER_MOD, $id='admin_add-user_level')
{
    global $user_levels;

    $str = '<select name="level" size="1" id="'.$id.'">';

    foreach ($user_levels as $key => $val) {
	$str .= '<option value="'.$key.'"';
	if ($key == $selected) $str .= ' selected';
	$str .= '>'.$key.' - '.lang('user_level_'.$val).'</option>';
    }

    $str .= '</select>';
    return $str;
}

function username_exists($name)
{
    global $db;
    $name = strtolower($name);
    $sql = 'SELECT COUNT(1) AS cnt FROM '.db_tablename('users').' WHERE LOWER(user)='.$db->quote($name);
    $res = $db->query($sql);
    check_db_res($res, $sql);
    $ret = $res->fetch();
    if ($ret['cnt'] > 0) return TRUE;
    return FALSE;
}

function check_username($username, $check_exist=1)
{
    global $TEMPLATE;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
	$TEMPLATE->add_message(lang('username_illegal_chars'));
    } else if (strlen($username) < 2) {
	$TEMPLATE->add_message(lang('username_too_short'));
    } else if (strlen($username) > 20) {
	$TEMPLATE->add_message(lang('username_too_long'));
    } else if ($check_exist && username_exists($username)) {
	$TEMPLATE->add_message(lang('username_exists'));
    } else {
	return TRUE;
    }
    return FALSE;
}

function register_user_do_inner($row)
{
    global $db, $TEMPLATE;
    $username = $row['username'];
    $password = $row['password'];
    $salt = str_rand();
    $level = USER_NORMAL;
    $db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES(".$db->quote($username).", '".crypt($password, "\$1\$".substr($salt, 0, 8)."\$")."', ".$db->quote((int)$level).", '\$1\$".$salt."\$');");
    /*if (DB::isError($res)) {
	$TEMPLATE->add_message($res->getMessage());
    } else*/ $TEMPLATE->add_message(sprintf(lang('user_added'), htmlspecialchars($username)));

    $row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE user=".$db->quote($username))->fetch();
    set_user_logged($row);
    return $row;
}

function register_user($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	$username = trim($_POST['username']);
	if (check_username($username)) {
	    if ($_POST['verifypassword'] == $_POST['password']) {
		$row = array('username' => $username, 'password' => $_POST['password']);
		$ret = handle_captcha('register_user', 'register_user_do_inner', $row);
		if (is_string($ret)) $TEMPLATE->add_message($ret);
	    } else $TEMPLATE->add_message(lang('password_verification_mismatch'));
	}
    }
    print $TEMPLATE->register_user_page();
}

function add_user($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	$username = trim($_POST['username']);
	if (check_username($username)) {
	    $db->query("INSERT INTO ".db_tablename('users')." (user, password, level, salt) VALUES(".$db->quote($username).", '".crypt($_POST['password'], "\$1\$".substr($_POST['salt'], 0, 8)."\$")."', ".$db->quote((int)$_POST['level']).", '\$1\$".$_POST['salt']."\$');");
	    /*if (DB::isError($res)) {
		$TEMPLATE->add_message($res->getMessage());
		} else*/ $TEMPLATE->add_message(sprintf(lang('user_added'), htmlspecialchars($username)));
	}
    }

    print $TEMPLATE->add_user_page();
}

function change_pw($method, $who)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'update') {
	// created to keep errors at a minimum
	$row['salt'] = 0;

	$row = $db->query("SELECT `password`, salt FROM ".db_tablename('users')." WHERE id=".$db->quote((int)$who))->fetch();
	//$row = $res->fetchRow(DB_FETCHMODE_ASSOC);

	$salt = "\$1\$".str_rand()."\$";
	if ($_POST['new_password'] == '') {
	    $TEMPLATE->add_message(lang('password_empty'));
	} else {
	    if((md5($_POST['old_password']) == $row['password']) || (crypt($_POST['old_password'], $row['salt']) == $row['password'])){
		if($_POST['verify_password'] == $_POST['new_password']){
		    $db->query("UPDATE ".db_tablename('users')." SET `password`='".crypt($_POST['new_password'], $salt)."', salt='".$salt."' WHERE id=".$db->quote((int)$who));
		    $TEMPLATE->add_message(lang('password_updated'));
		} else $TEMPLATE->add_message(lang('password_verification_mismatch'));
	    } else $TEMPLATE->add_message(lang('password_old_mismatch'));
	}
    };

    print $TEMPLATE->change_password_page();
}

function edit_users($method, $who)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'delete') {	// delete a user from users
	if (isset($_POST['verify'])) {
	    $res = db_query("SELECT * FROM ".db_tablename('users'));
	    while ($row = $res->fetch()) {
		if(isset($_POST['d'.$row['id']])){
		    $db->query("DELETE FROM ".db_tablename('users')." WHERE id='{$_POST['d'.$row['id']]}'");
		    $TEMPLATE->add_message(sprintf(lang('user_removed'), htmlspecialchars($row['user'])));
		}
	    }
	}
    } else if ($method == 'update') {	// parse the info from $method == 'edit' into the database
	$user = trim($_POST['user']);
	if (check_username($user, 0)) {
	    $db->query("UPDATE ".db_tablename('users')." SET user=".$db->quote($user).", level=".$db->quote((int)$_POST['level'])." WHERE id=".$db->quote((int)$who));
	    if ($_POST['password']) {
		$salt = "\$1\$".str_rand()."\$";
		$db->query("UPDATE ".db_tablename('users')." SET `password`='".crypt($_POST['password'], $salt)."', salt='".$salt."' WHERE id=".$db->quote((int)$who));
	    }
	}
    } else if ($method == 'edit') {
	$row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE id=".$db->quote((int)$who))->fetch();
	if (isset($row['user']))
	    print $TEMPLATE->edit_user_page_form($row['id'], $who, htmlspecialchars($row['user']), $row['level']);
    }

    $innerhtml = '';

    $res = $db->query("SELECT * FROM ".db_tablename('users')." ORDER BY level asc, user desc");
    while ($row = $res->fetch()) {
	$innerhtml .= $TEMPLATE->edit_user_page_table_row($row['id'], htmlspecialchars($row['user']), htmlspecialchars($row['password']), $row['level'], ($who == $row['id']));
    }
    print $TEMPLATE->edit_user_page_table($innerhtml);
}

function userlogin($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'login') {
	$salt = $db->query("SELECT salt FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username'])))->fetch();

	// if there is no presence of a salt, it is probably md5 since old rash used plain md5
	if(!$salt['salt']){
	    $row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".md5($_POST['rash_password'])."'")->fetch();
	}
	// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
	else{
	    $row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'")->fetch();
	}

	// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
	if(!$row['user']){
	    $TEMPLATE->add_message(lang('login_error'));
	} else {
	    set_user_logged($row);
	}
    }
    print $TEMPLATE->user_login_page();
}

function adminlogin($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'login') {
	$salt = $db->query("SELECT salt FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username'])))->fetch();

	// if there is no presence of a salt, it is probably md5 since old rash used plain md5
	if(!$salt['salt']){
	    $row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".md5($_POST['rash_password'])."'")->fetch();
	}
	// if there is presense of a salt, it is probably new rash passwords, so it is salted md5
	else{
	    $row = $db->query("SELECT * FROM ".db_tablename('users')." WHERE LOWER(user)=".$db->quote(strtolower($_POST['rash_username']))." AND `password` ='".crypt($_POST['rash_password'], $salt['salt'])."'")->fetch();
	}

	// if there is no row returned for the user, the password is expected to be false because of the AND conditional in the query
	if(!$row['user']){
	    $TEMPLATE->add_message(lang('login_error'));
	} else {
	    set_user_logged($row);
	}
    }
    print $TEMPLATE->admin_login_page();
}


function quote_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'judgement') {
	$x = 0;
	$sth = $db->query("SELECT * FROM ".db_tablename('queue'));
	while ($row = $sth->fetch()) {
	    if (isset($_POST['q'.$row['id']])) {
		$judgement_array[$x] = $_POST['q'.$row['id']];
		$x++;
	    }
	}
	$x = 0;
	while (isset($judgement_array[$x])) {
	    $qid = (int)substr($judgement_array[$x], 1);

	    if(substr($judgement_array[$x], 0, 1) == 'y'){
		$fields = 'quote,rating,flag,date,submitip';

		$sql = "SELECT quote FROM ".db_tablename('queue')." WHERE id =".$db->quote($qid);
		$res = $db->query($sql);
		$tmpdata = $res->fetch(PDO::FETCH_ASSOC);
		$quotetxt = $tmpdata['quote'];

		$sql = "INSERT INTO ".db_tablename('quotes')." ($fields) SELECT $fields FROM ".db_tablename('queue')." WHERE id =".$db->quote($qid);
		db_query($sql);

		$sql = 'SELECT LAST_INSERT_ID() FROM '.db_tablename('quotes');
		$res = $db->query($sql);
		$tmpdata = $res->fetch(PDO::FETCH_NUM);
		$quoteid = $tmpdata[0];

		$qarr = preg_split('/\n/', html_entity_decode($quotetxt));
		$sql = 'INSERT INTO '.db_tablename('dupes').' (normalized, quote_id) VALUES (?, ?)';
		$stha = $db->prepare($sql);
		foreach ($qarr as $l) {
		    $l = normalize_quote_line($l);
		    if (!((strlen($l) < 5) || (strpos($l,' ')===FALSE))) {
			$stha->execute(array($l, $quoteid));
		    }
		}

		$db->query("DELETE FROM ".db_tablename('queue')." WHERE id =".$db->quote($qid));
		$TEMPLATE->add_message(sprintf(lang('quote_accepted'), $quoteid));
	    } else {
		$db->query("DELETE FROM ".db_tablename('queue')." WHERE id =".$db->quote($qid));
		$TEMPLATE->add_message(sprintf(lang('quote_deleted'), $qid));
	    }
	    $x++;
	}
    }

    $sql = 'SELECT * FROM '.db_tablename('queue').' ORDER BY id ASC';
    $res = $db->query($sql);
    check_db_res($res, $sql);

    $innerhtml = '';
    $x = 0;
    while ($row = $res->fetch()) {
	$dupes = find_maybe_dupes($row['quote']);
	$innerhtml .= $TEMPLATE->quote_queue_page_iter($row['id'], mangle_quote_text($row['quote']), $dupes);
	$x++;
    }

    print $TEMPLATE->quote_queue_page($innerhtml);
}

/* The meaning of flags:
    0 = not flagged
    1 = user has flagged the quote for admin attention
    2 = admin has checked the quote, and accepted it
    3 = admin has checked the quote, and "deleted" it
 */

function flag_queue($method)
{
    global $CONFIG, $TEMPLATE, $db;
	if($method == 'judgement'){

	    if (isset($_POST['do_all']) && ($_POST['do_all'] == 'on')) {
		if (isset($_POST['unflag_all'])) {
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=2 WHERE flag=1");
		    $TEMPLATE->add_message(lang('unflagged_all'));
		} else if (isset($_POST['delete_all'])) {
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=3 WHERE flag=1");
		    $TEMPLATE->add_message(lang('deleted_all'));
		}
	    }

	    $x = 0;
	    $res = $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE flag = 1");
	    while($row = $res->fetch()) {
		if (isset($_POST['q'.$row['id']])) {
		    $judgement_array[$x] = $_POST['q'.$row['id']];
		    $x++;
		}
	    }

	    $x = 0;
	    while (isset($judgement_array[$x])) {
		if(substr($judgement_array[$x], 0, 1) == 'u'){
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=2 WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)));
		    $TEMPLATE->add_message(sprintf(lang('quote_unflagged'), substr($judgement_array[$x], 1)));
		}
		if(substr($judgement_array[$x], 0, 1) == 'd'){
		    $db->query("UPDATE ".db_tablename('quotes')." SET flag=3 WHERE id =".$db->quote((int)substr($judgement_array[$x], 1)));
		    $TEMPLATE->add_message(sprintf(lang('quote_deleted'), substr($judgement_array[$x], 1)));
		}
		$x++;
	    }
	}

	$innerhtml = '';

	$x = 0;
	$res = $db->query("SELECT * FROM ".db_tablename('quotes')." WHERE flag=1 ORDER BY id ASC");
	while ($row = $res->fetch()) {
	    $innerhtml .= $TEMPLATE->flag_queue_page_iter($row['id'], mangle_quote_text($row['quote']));
	    $x++;
	}

	print $TEMPLATE->flag_queue_page($innerhtml);
}


// search($method)
// This takes a user to the page where they can put words in to search for
// quotes with those words in it. Pretty simple.
//

function search($method, $searchparam=null)
{
    global $CONFIG, $TEMPLATE, $db;
    if ($method == 'fetch' || isset($searchparam)) {
	$method = 'fetch';

	$search = (isset($_POST['search']) ? $_POST['search'] : $searchparam);

	if (preg_match('/^#[0-9]+$/', trim($search))) {
	    $exactmatch = ' or id='.substr(trim($search), 1);
	} else {
	    $exactmatch = '';
	}

	$sortby = (isset($_POST['sortby']) ? $_POST['sortby'] : 'rating');
	$sortby = preg_replace('/[^a-zA-Z0-9]+/', '', $sortby);

	if ($sortby == 'rating')
	    $how = 'desc';
	else
	    $how = 'asc';

	$limit = (isset($_POST['number']) ? $_POST['number'] : 10);
	if (!preg_match('/^[0-9]+$/', $limit)) $limit = 10;

	$searchx = '%'.$search.'%';

	$query = "SELECT * FROM ".db_tablename('quotes')." WHERE (flag!=3) AND (quote LIKE ".$db->quote($searchx).$exactmatch.") ORDER BY ".$sortby." $how LIMIT ".$limit;

	quote_generation($query, lang('search_results_title'), -1);
    } else $search = '';

    print $TEMPLATE->search_quotes_page(($method == 'fetch'), htmlspecialchars($search));
}

function edit_quote($action, $method, $quoteid)
{
    global $CONFIG, $TEMPLATE, $db;

    if (!isset($_SESSION['logged_in']) || ($_SESSION['level'] > USER_ADMIN)) return;

    if ($action == 'editqueue') $table = 'queue';
    else $table = 'quotes';

    $innerhtml = '';

    if ($method == 'submit') {

	$quotxt = htmlspecialchars(trim($_POST["rash_quote"]));

	$innerhtml = $TEMPLATE->edit_quote_outputmsg(mangle_quote_text($quotxt));

	$db->query("UPDATE ".db_tablename($table)." SET quote=".$db->quote($quotxt)." WHERE id=".$db->quote($quoteid));
    } else {
	$tmp = $db->query("SELECT quote FROM ".db_tablename($table)." WHERE id=".$db->quote($quoteid))->fetch();
	$quotxt = $tmp['quote'];
    }

    print $TEMPLATE->edit_quote_page($action, $quoteid, $quotxt, $innerhtml);
}

function add_quote_do_inner()
{
    global $CONFIG, $TEMPLATE, $db;
    $flag = (isset($CONFIG['auto_flagged_quotes']) && ($CONFIG['auto_flagged_quotes'] == 1)) ? 2 : 0;
    $spamre = (isset($CONFIG['spam_regex']) && $CONFIG['spam_regex'] != '') ? $CONFIG['spam_regex'] : NULL;
    $quotxt = htmlspecialchars(trim($_POST["rash_quote"]));
    $innerhtml = $TEMPLATE->add_quote_outputmsg(mangle_quote_text($quotxt));
    $t = time();
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($spamre && preg_match('/'.$spamre.'/', $quotxt)) {
	$table = 'spamlog';
	if (isset($CONFIG['spam_expire_time']) && ($CONFIG['spam_expire_time'] > 0)) {
	    $sql = 'DELETE FROM '.db_tablename('spamlog').' WHERE date<'.$db->quote($t + $CONFIG['spam_expire_time']);
	    db_query($sql);
	}
    } elseif ($CONFIG['moderated_quotes']) {
	$table = 'queue';
    } else {
	$table = 'quotes';
    }

    if (isset($CONFIG['auto_block_spam_ip']) && ($CONFIG['auto_block_spam_ip'] > 0)) {
	$sql = 'SELECT COUNT(*) FROM '.db_tablename('spamlog').' WHERE submitip='.$db->quote($ip);
	$cnt = db_query_singlevalue($sql);
	if ($cnt >= $CONFIG['auto_block_spam_ip']) return $innerhtml;
    }

    $db->query("INSERT INTO ".db_tablename($table)." (quote, submitip, date) VALUES(".$db->quote($quotxt).", ".$db->quote($ip).", ".$t.")");
    return $innerhtml;
}

function add_quote($method)
{
    global $CONFIG, $TEMPLATE, $CAPTCHA, $db;

    $innerhtml = '';
    $quotxt = '';
    $added = 0;

    if ($method == 'submit') {
	$quotxt = htmlspecialchars(trim($_POST["rash_quote"]));
	if (strlen($quotxt) < $CONFIG['min_quote_length']) {
	    $TEMPLATE->add_message(lang('add_quote_short'));
	} else {
	    if (isset($_POST['preview'])) {
		$innerhtml = $TEMPLATE->add_quote_preview(mangle_quote_text($quotxt));
	    } else {
		$ret = handle_captcha('add_quote', 'add_quote_do_inner');
		if (is_string($ret)) $TEMPLATE->add_message($ret);
		$added = 1;
	    }
	}
    }

    print $TEMPLATE->add_quote_page($quotxt, $innerhtml, $added);
}

function import_quotes_do_inner()
{
    global $CONFIG, $TEMPLATE, $db;
    $flag = (isset($CONFIG['auto_flagged_quotes']) && ($CONFIG['auto_flagged_quotes'] == 1)) ? 2 : 0;
    $spamre = (isset($CONFIG['spam_regex']) && $CONFIG['spam_regex'] != '') ? $CONFIG['spam_regex'] : NULL;
    $sep = html_entity_decode($_POST['separator_regex']);
    $quotes = preg_split("/".$sep."/m", html_entity_decode(trim($_POST['rash_quote'])));

    foreach ($quotes as $quotxt) {
	$quotxt = htmlspecialchars(trim($quotxt));
	if (!(strlen($quotxt) < $CONFIG['min_quote_length'])) {
	    $t = time();
	    $ip = $_SERVER['REMOTE_ADDR'];
	    if ($CONFIG['moderated_quotes']) {
		$table = 'queue';
	    } else {
		$table = 'quotes';
	    }
	    $db->query("INSERT INTO ".db_tablename($table)." (quote, submitip, date) VALUES(".$db->quote($quotxt).", ".$db->quote($ip).", ".$t.")");
	}
    }
    return '';
}

function import_quotes($method)
{
    global $CONFIG, $TEMPLATE, $CAPTCHA, $db;
    $innerhtml = '';
    $added = 0;
    $qpost = '';
    $regex = NULL;
    if ($method == 'submit') {
	$sep = html_entity_decode($_POST['separator_regex']);
	$quotes = preg_split("/".$sep."/m", html_entity_decode(trim($_POST['rash_quote'])));

	$nquotes = count($quotes);

	if ($nquotes < 2) {
	    $TEMPLATE->add_message(lang('import_quote_check_separator'));
	    $qpost = $_POST['rash_quote'];
	    $regex = $_POST['separator_regex'];
	} else {
	    $ret = handle_captcha('import_quotes', 'import_quotes_do_inner');
	    if (is_string($ret)) $TEMPLATE->add_message($ret);
	    $added++;
	}
    }
    print $TEMPLATE->import_data_page($qpost, $regex, $innerhtml, $added);
}




$page[1] = 0;
$page[2] = 0;
$page = explode($CONFIG['GET_SEPARATOR'], $_SERVER['QUERY_STRING']);


if(!($page[0] === 'rss' || $page[0] === 'ajaxvote'))
    print $TEMPLATE->printheader(title($page[0]), $CONFIG['site_short_title'], $CONFIG['site_long_title']);

$page[1] = (isset($page[1]) ? $page[1] : null);
$page[2] = (isset($page[2]) ? $page[2] : null);

if (preg_match('/=/', $page[0])) {
    $tmppage = explode('=', $page[0], 2);
    $page[0] = trim($tmppage[0]);
    $pageparam = trim($tmppage[1]);
} else $pageparam = null;

$limit = get_number_limit($pageparam, 1, $CONFIG['quote_list_limit']);

$voteable = '';
if ($page[1] === 'voteable' && isset($_SESSION['voteip']))
    $voteable = ' AND q.id NOT IN (SELECT t.quote_id FROM '.db_tablename('tracking').' t WHERE t.quote_id=q.id AND t.user_ip='.$db->quote($_SESSION['voteip']).') ';

if ($page[0] === '') {
    $page[0] = 'latest';
}

switch($page[0])
{
	case 'add':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    add_quote($page[1]);
	    break;
	case 'import':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		import_quotes($page[1]);
	    break;
	case 'edit_news':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
		edit_news($page[1], $page[2]);
	    }
	    break;
	case 'add_news':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN)) {
		add_news($page[1]);
	    }
	    break;
	case 'add_user':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_SUPERUSER)) {
		add_user($page[1]);
	    }
	    break;
	case 'register':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
		register_user($page[1]);
	    }
	    break;
	case 'login':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1)) {
		if (isset($_SESSION['logged_in'])) {
		    header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
		} else {
		    userlogin($page[1]);
		}
	    } else {
		header('Location: http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
	    }
	    break;
	case 'admin':
		if (isset($_SESSION['logged_in'])) {
		    /* already logged in */
		} else {
		    adminlogin($page[1]);
		}
		break;
	case 'bottom':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.rating < 0 ".$voteable." ORDER BY q.rating ASC LIMIT ".$limit;
	    quote_generation($query, lang('bottom_title'), -1);
	    break;
	case 'browse':
		$query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) ".$voteable." ORDER BY q.id ASC ";
		quote_generation($query, lang('browse_title'), $page[1], $CONFIG['quote_limit'], $CONFIG['page_limit']);
		break;
	case 'change_pw':
	    if (isset($_SESSION['logged_in']))
		change_pw($page[1], $page[2]);
	    break;
	case 'flag':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    flag($page[1], $page[2]);
	    break;
	case 'flag_queue':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] < USER_NORMAL))
		flag_queue($page[1]);
	    break;
	case 'latest':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) ".$voteable." ORDER BY q.id DESC";
	    if (isset($_SESSION['lastvisit'])) {
		$nlatest = $db->query("SELECT count(1) FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.date>=".$db->quote($_SESSION['lastvisit']).$voteable)->fetch();
		if (($nlatest >= $CONFIG['min_latest']) && ($nlatest <= $CONFIG['quote_list_limit'])) {
		    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.date>=".$db->quote($_SESSION['lastvisit']).$voteable." ORDER BY q.id DESC";
		}
	    }
	    quote_generation($query, lang('latest_title'), $page[1], $CONFIG['quote_limit'], $CONFIG['page_limit']);
	    break;
	case 'logout':
	    set_user_logout();
	    break;
	case 'adminqueue':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] < USER_NORMAL))
		quote_queue($page[1]);
	    break;
	case 'queue':
	    if (isset($CONFIG['public_queue']) && ($CONFIG['public_queue'] == 1)) {
		$query = "SELECT q.* FROM ".db_tablename('queue')." q WHERE TRUE ".$voteable." ORDER BY rand() LIMIT ".$limit;
		quote_generation($query, lang('quote_queue_title'), -1);
	    }
	    break;
	case 'random1':
	    $limit = 1;
	    /* fallthrough */
	case 'random':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) ".$voteable." ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random_title'), -1);
	    break;
	case 'random2':
	case 'randomplus':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.rating>0 ".$voteable." ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random2_title'), -1);
	    break;
	case 'random3':
	case 'random0':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.rating=0 ".$voteable." ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random3_title'), -1);
	    break;
	case 'random4':
	case 'randomminus':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.rating<0 ".$voteable." ORDER BY rand() LIMIT ".$limit;
	    quote_generation($query, lang('random4_title'), -1);
	    break;
	case 'rss':
	    rash_rss();
	    break;
	case 'search':
	    search($page[1], $pageparam);
	    break;
	case 'top':
	    $query = "SELECT q.* FROM ".db_tablename('quotes')." q WHERE (q.flag!=3) AND q.rating > 0 ".$voteable." ORDER BY q.rating DESC LIMIT ".$limit;
	    quote_generation($query, lang('top_title'), -1);
	    break;
	case 'edit':
	case 'editqueue':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		edit_quote($page[0], $page[1], $page[2]);
	    break;
	case 'users':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_SUPERUSER))
		edit_users($page[1], $page[2]);
	    break;
	case 'ajaxvote':
	case 'vote':
	    if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in']))
		break;
	    vote($page[1], $page[2], ($page[0] === 'ajaxvote'));
	    break;
	case 'voters':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		show_quote_voters($page[1]);
	    break;
	case 'reorder':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_SUPERUSER))
		reorder_quotes();
	    break;
	case 'spam':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		show_spam();
	    break;
	case 'populate_dupe_table':
	    if (isset($_SESSION['logged_in']) && ($_SESSION['level'] <= USER_ADMIN))
		populate_dupe_table();
	    break;
	case 'news':
	    news_page();
	    break;
	default:
	    if (preg_match('/^[0-9]+(&[0-9]+)*$/', $_SERVER['QUERY_STRING'])) {
		$idlist = explode('&', $_SERVER['QUERY_STRING']);
		if (count($idlist) < 11) {
		    $ids = array();
		    $order = array();
		    $idx = 0;
		    foreach ($idlist as $id) {
			$ids[] = 'id='.$db->quote((int)$id);
			$order[] = 'WHEN '.$db->quote((int)$id).' THEN '.$idx.' ';
			$idx++;
		    }
		    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE (flag!=3) AND (".implode(' or ', $ids).") ORDER BY CASE id ".implode($order)." END";
		    if ($idx > 1) $title = lang('selected_quotes');
		    else $title = "#${_SERVER['QUERY_STRING']}";
		} else {
		    $query = "SELECT * FROM ".db_tablename('quotes')." WHERE (flag!=3) AND id=".$db->quote((int)$idlist[0]);
		    $title = "#${idlist[0]}";
		}
		quote_generation($query, $title, -1);
	    } else if ($_SERVER['QUERY_STRING']) {
		search('search', urldecode($_SERVER['QUERY_STRING']));
	    } else {
		home_generation();
	    }

}
if(!($page[0] === 'rss' || $page[0] === 'ajaxvote'))
    print $TEMPLATE->printfooter(get_db_stats());

$db = null;
