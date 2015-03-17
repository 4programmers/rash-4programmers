<?php

class BashTemplate extends BaseTemplate {

function printheader($title, $topleft='QMS', $topright='Quote Management System')
{
ob_start();
header('Content-type: text/html; charset=utf-8');
// begin editing after this line ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
 <title><?=$title?></title>
 <meta name="robots" content="noarchive,nofollow" />
 <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
 <link rel="alternate" type="application/rss+xml" href="?rss" title="RSS">
 <style type="text/css" media="all">
  @import "./templates/bash/style.css";
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
<?php

if(!isset($_SESSION['logged_in'])){
    print '<a href="?'.urlargs('admin').'" id="site_nav_admin">'.lang('menu_admin').'</a>';
} else {
    print sprintf(lang('logged_in_as'), htmlspecialchars($_SESSION['user']));
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
?>
  <div id="site_admin_nav">
   <div id="site_admin_nav_upper">
    <div id="site_admin_nav_upper_linkbar">
<?=$this->get_menu(1);?>
    </div>
   </div>
   <div id="site_admin_nav_lower">
    <div id="site_admin_nav_lower_infobar">
     <span id="site_admin_nav_lower_infobar_pending">
<?=lang('pending_quotes').": ".$dbstats['pending_quotes'].";\n";?>
     </span>
     <span id="site_admin_nav_lower_infobar_approved">
<?=lang('approved_quotes').": ".$dbstats['approved_quotes']."\n";?>
     </span>
    </div>
   </div>
  </div>
 </div>
</body>
</html>
<?php

}

} // BashTemplate


$TEMPLATE = new BashTemplate;