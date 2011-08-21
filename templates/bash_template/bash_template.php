<?php

class BashTemplate extends BaseTemplate {

function printheader($title, $topleft='QMS', $topright='Quote Management System')
{
    global $lang;
ob_start();
// begin editing after this line ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
 <title><?=$title?></title>
 <meta name="robots" content="noarchive,nofollow" />
 <link rel="alternate" type="application/rss+xml" href="?rss" title="RSS">
 <style type="text/css" media="all">
  @import "./templates/bash_template/style.css";
 </style>
</head>
<body>
 <div id="site_all">
  <div id="site_nav">
   <div id="site_nav_upper">
      <div id="site_nav_upper_qms-long"><?=$topright?>
    </div>
      <div id="site_nav_upper_qms"><?=$topleft?>
    </div>&nbsp;
<?

	if(!$_SESSION['logged_in']){

?>
    <a href="?admin" id="site_nav_admin"><?=$lang['menu_admin']?></a>
<?php

	}
	print '</div>';
	print $this->get_menu();
	print '</div>';
	print $this->get_messages();
}


// printfooter()
// Bottom of the document!
//
function printfooter($dbstats=null)
{
    global $lang;
?>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?

       print $this->get_menu(1);

?>
    </div>
   </div>
   <div id="site_admin_nav_lower">
    <div id="site_admin_nav_lower_infobar">
     <span id="site_admin_nav_lower_infobar_pending">
<?
	  echo $lang['pending_quotes'].": ".$dbstats['pending_quotes'].";\n";
?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?
	  echo $lang['approved_quotes'].": ".$dbstats['approved_quotes']."\n";
?>
     </span>
    </div>
   </div>
  </div>
 </div>
</body>
</html>
<?

}

} // BashTemplate


$TEMPLATE = new BashTemplate;