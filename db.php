<?php

function get_db($CONFIG, $TEMPLATE=NULL)
{
    try {
	$db = new PDO($CONFIG['phptype'].":host=localhost;dbname=".$CONFIG['database'], $CONFIG['username'], $CONFIG['password']);
	return $db;
    } catch (PDOException $dberror) {
	if ($TEMPLATE) $TEMPLATE->printheader('Error');
	print $dberror->getMessage();
	if ($TEMPLATE) $TEMPLATE->printfooter();
	exit;
    }
}

function check_db_res($res, $query=NULL)
{
    global $db;
    if (!$res) {
	if ($query) print '<p>Query: '.$query.'<p>';
	$err = $db->errorInfo();
	print '<p>SQLSTATE: '. $err[0];
	print '<p>Driver error code: '. $err[1];
	print '<p>'.$err[2];
	die();
    }
}

function db_query($sql)
{
    global $db;
    $args = array();
    for ($i = 1; $i < func_num_args(); $i++)
	$args[] = func_get_arg($i);
    $sth = $db->prepare($sql);
    check_db_res($sth, $sql);
    $sth->execute($args);
    return $sth;
}

function db_query_singlevalue($sql)
{
    global $db;
    $args = array();
    for ($i = 1; $i < func_num_args(); $i++)
	$args[] = func_get_arg($i);
    $sth = $db->prepare($sql);
    check_db_res($sth, $sql);
    $sth->execute($args);
    $tmp = $sth->fetch(PDO::FETCH_NUM);
    return $tmp[0];
}
