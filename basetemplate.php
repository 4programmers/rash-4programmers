<?php

abstract class BaseTemplate {

    private $messages = array();
    private $mainmenu = array();
    private $adminmenu = array();
    private $menu_join_str = ' | ';
    private $menu_wrap_str = '<div class="site_nav_lower"><div class="site_nav_lower_linkbar">%s</div></div>';

    function __constructor()
    {
    }

    function add_message($msg)
    {
	$this->messages[] = $msg;
    }

    function get_messages()
    {
	if (count($this->messages) > 0) {
	    $str = '<ul id="page_messages">';
	    foreach ($this->messages as $msg) {
		$str .= '<li>'.$msg;
	    }
	    $str .= '</ul>';
	    $this->messages = array();
	    return $str;
	}
	return '';
    }

    function set_menu($admin,$menudata)
    {
	if ($admin) {
	    $this->adminmenu = $menudata;
	} else {
	    $this->mainmenu = $menudata;
	}
    }

    function set_menu_join_str($str) { $this->menu_join_str = $str; }
    function set_menu_wrap_str($str) { $this->menu_wrap_str = $str; }

    function get_menu($admin = 0)
    {
	if ($admin) { $menudata = $this->adminmenu; } else { $menudata = $this->mainmenu; }
	$str = '';
	if ($menudata) {
	    $arr = array();
	    foreach ($menudata as $m) {
		$arr[] = '<a href="'.$m['url'].'" id="'.$m['id'].'">'.str_replace(' ', '&nbsp;', lang($m['txt'])).'</a>';
	    }
	    $str = join('<span class="sep">'.$this->menu_join_str.'</span>', $arr);
	}
	return sprintf($this->menu_wrap_str, $str);
    }

    function rss_feed_item($title, $desc, $link)
    {
	$str = "<item>\n";
	$str .= "<title>".$title."</title>\n";
	$str .= "<description>".$desc."</description>\n";
	$str .= "<link>".$link."</link>\n";
	$str .= "</item>\n\n";
	return $str;
    }

    function rss_feed($title, $desc, $link, $items)
    {
	$str = "<?xml version=\"1.0\" ?>\n";
	$str .= "<rss version=\"0.92\">\n";
	$str .= "<channel>\n";
	$str .= "<title>".$title."</title>\n";
	$str .= "<description>".$desc."</description>\n";
	$str .= "<link>".$link."</link>\n";
	$str .= $items;
	$str .= "</channel></rss>";
	return $str;
    }


    function printheader($title, $topleft=null, $topright=null)
    {
	$str = '<html><head><title>'.$title.'</title>
<script src="qdb.js" type="text/javascript"></script>
</head><body>';
	if ($topleft) $str .= '<h1>'.$topleft.'</h1>';
	if ($topright) $str .= '<h2>'.$topright.'</h2>';
	if(!isset($_SESSION['logged_in'])){
	    print '<a href="?'.urlargs('admin').'" id="login">'.lang('menu_admin').'</a>';
	} else {
	    print '<span class="logged_in_as">'.sprintf(lang('logged_in_as'), htmlspecialchars($_SESSION['user'])).'</span>';
	}
	$str .= '<div class="menu">'.$this->get_menu().'</div>';
	$amenu = $this->get_menu(1);
	if ($amenu) $str .= '<div class="adminmenu">'.$amenu.'</div>';
	$str .= $this->get_messages();
	return $str;
    }

    function printfooter($db_stats=null)
    {
	return '</body></html>';
    }

    function news_item($news, $date, $id=null, $mode=0)
    {
	if ($mode == 0) {
	    return '<div class="news_entry"><p class="news_date">'.$date.'</p>'.
	    '<div class="news_news"><span class="space">&nbsp;</span>'.$news.'</div></div>';
	} else {
	    $str = '<div class="news_entry"><p class="news_date"><a href="?'.urlargs('edit_news','edit',$id).'">'.$date.'</a></p>'.
		'<div class="news_news">'.$news.'</div></div>';

	    if ($mode == 2) $str = '<div class="hilight_news_entry">'.$str.'</div>';
	    return $str;
	}
    }

    function news_page($news)
    {
	$str = '<div id="news_all">';
	$str .= '<h1 id="news_title">'.lang('news_title').'</h1>';
	$str .= $news;
	$str .= '</div>';
	return $str;
    }

    function edit_news_form($id, $news)
    {
	return '<div class="edit_news_form"><form action="?'.urlargs('edit_news','update',$id).'" method="post">
  <textarea cols="80" rows="5" name="news" id="edit_news_news">'.$news.'</textarea><br />
  <input type="submit" value="'.lang('preview_news_btn').'" id="edit_preview" name="preview" />
  <input type="submit" value="'.lang('save_news_btn').'" id="edit_news" name="submit" />
  <input type="submit" value="'.lang('delete_news_btn').'" id="delete_news" name="delete" />
  '.lang('delete_news_verify').'<input type="checkbox" name="verify_delete" id="verify_delete"><label for="verify_delete"></label>
  </form></div><p>';
    }

    function edit_news_page($news)
    {
	$str = '<div id="editnews_all">';
	$str .= '<h1 id="editnews_title">'.lang('editnews_title').'</h1>';
	$str .= $news;
	$str .= '</div>';
	return $str;
    }

    function main_page($news)
    {
	$str = '<div id="home_all"><div id="news">'.$news;
	if ($news) {
	    $str .= '<div class="show_all_news"><a href="?'.urlargs('news').'">'.lang('news_show_all').'</a></div>';
	}
	$str .= '</div>';
        $str .= '<div id="home_greeting">'.lang('home_greeting').'</div></div>';
	return $str;
    }

    function add_quote_preview($quotetxt)
    {
	$str = '<div id="add_outputmsg">';
	$str .= '<div id="addw_outputmsg_top">'.lang('preview_outputmsg_top').'</div>';
	$str .= '<div id="add_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="add_outputmsg_bottom">'.lang('preview_outputmsg_bottom').'</div>';
	$str .= '</div>';
	return $str;
    }

    function add_quote_outputmsg($quotetxt)
    {
	$str = '<div id="add_outputmsg">';
	$str .= '<div id="add_outputmsg_top">'.lang('add_outputmsg_top').'</div>';
	$str .= '<div id="add_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="add_outputmsg_bottom">'.lang('add_outputmsg_bottom').'</div>';
	$str .= '</div>';
	return $str;
    }

    function add_quote_page($quotetxt='', $added_quote_html='', $wasadded=null)
    {
	global $CAPTCHA;
	$str = '<div id="add_all">';

	$str .= '<h1 id="add_title">'.lang('add_title').'</h1>';

	$str .= $added_quote_html;

	$str .= '<form action="?'.urlargs('add','submit').'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="add_quote">'.($wasadded ? '' : $quotetxt).'</textarea><br />';
	$str .= $CAPTCHA->get_CAPTCHA('add_quote');
        $str .= '
        <input type="submit" value="'.lang('preview_quote_btn').'" id="add_preview" name="preview" />
        <input type="submit" value="'.lang('add_quote_btn').'" id="add_submit" name="submit" />
        <input type="reset" value="'.lang('add_reset_btn').'" id="add_reset" />
        </form>';

	$str .= '</div>';
	return $str;
    }

    function import_data_page($quotetxt='', $regex=null, $added_quote_html='', $wasadded=null)
    {
	global $CAPTCHA;
	$str = '<div id="import_data_all">';

	$str .= '<h1 id="add_title">'.lang('import_data_title').'</h1>';

	$str .= $added_quote_html;

	$str .= '<form action="?'.urlargs('import','submit').'" method="post">
        <textarea cols="80" rows="25" name="rash_quote" id="add_quote">'.($wasadded ? '' : $quotetxt).'</textarea><br />
	'.lang('import_separator_regex').' <input type="text" name="separator_regex" value="'.($regex ? $regex : '^%\s*$').'"><br />';
	$str .= $CAPTCHA->get_CAPTCHA('add_quote');
        $str .= '
        <input type="submit" value="'.lang('import_quotes_btn').'" id="add_submit" name="submit" />
        <input type="reset" value="'.lang('add_reset_btn').'" id="add_reset" />
        </form>';

	$str .= '</div>';
	return $str;
    }


    function edit_quote_outputmsg($quotetxt)
    {
	$str = '<div id="editquote_outputmsg">';

	$str .= '<div id="editquote_outputmsg_top">'.lang('editquote_outputmsg_top').'</div>';
	$str .= '<div id="editquote_outputmsg_quote">'.$quotetxt.'</div>';
	$str .= '<div id="editquote_outputmsg_bottom">'.lang('editquote_outputmsg_bottom').'</div>';

	$str .= '</div>';
	return $str;
    }

    function edit_quote_button($quoteid, $queue=0)
    {
<<<<<<< HEAD
	return '<a href="?'.urlargs(($queue ? 'editqueue' : 'edit'),'edit',$quoteid).'" class="quote_edit" title="'.lang('editquote').'"></a>';
=======
	return '<a href="?'.urlargs(($queue ? 'editqueue' : 'edit'),'edit',$quoteid).'" class="quote_edit" title="'.lang('editquote').'">[E]</a>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
    }

    function edit_quote_page($action, $quoteid, $quotetxt, $edited_quote_html='')
    {
	$str = '<div id="editquote_all" class="act_'.$action.'">';

	$str .= '<h1 id="editquote_title">'.lang('editquote_title').'</h1>';

	$str .= $edited_quote_html;

	$str .= '<form action="?'.urlargs($action,'submit', $quoteid).'" method="post">
        <textarea cols="80" rows="5" name="rash_quote" id="edit_quote">'.$quotetxt.'</textarea><br />
        <input type="submit" value="'.lang('edit_quote_btn').'" id="edit_submit" />
        <input type="reset" value="'.lang('edit_reset_btn').'" id="edit_reset" />
        </form>';

	$str .= '</div>';

	return $str;
    }

    function search_quotes_page($fetched, $searchstr)
    {
	$str = '<div class="search_all">';

	if (!$fetched) {
	    $str .= '<h1 id="search_title">'.lang('search_title').'</h1>';
	}

	$str .= '<form method="post" action="?'.urlargs('search','fetch').'">';
	if ($fetched) { $str .= '<input type="submit" name="submit" value="'.lang('search_btn').'" id="search_submit-button">&nbsp;'; }
	$str .= '<input type="text" name="search" size="28" id="search_query-box" value="'.$searchstr.'">&nbsp;';
<<<<<<< HEAD
	if (!$fetched) { $str .= '<input type="submit" name="submit" value="'.lang('search_btn').'" id="search_submit-button">&nbsp;<div>'; }
=======
	if (!$fetched) { $str .= '<input type="submit" name="submit" value="'.lang('search_btn').'" id="search_submit-button">&nbsp;<br />'; }
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
	$str .= lang('search_sort').': <select name="sortby" size="1" id="search_sortby-dropdown">';
	$str .= '<option selected>'.lang('search_opt_rating');
	$str .= '<option>'.lang('search_opt_id');
	$str .= '</select>';

	$str .= '&nbsp;';

	$str .= lang('search_howmany').': <select name="number" size="1" id="search_limit-dropdown">
     <option selected>10
     <option>25
     <option>50
     <option>75
     <option>100
<<<<<<< HEAD
    </select></div>';

	$str .= '</form>';

//	$str .= '</div>';
=======
    </select>';

	$str .= '</form>';

	$str .= '</div>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

	return $str;
    }

    function flag_queue_page_iter($quoteid, $quotetxt)
    {
	return '<tr>
<td class="quote_delete" onclick="javascript:this.firstElementChild.click();">
	<input type="radio" id="qd'.$quoteid.'" name="q'.$quoteid.'" value="d'.$quoteid.'"><label for="qd'.$quoteid.'">'.lang('flag_quote_delete').'</label>
</td>
<td>
<div class="quote_quote">'.$quotetxt.'

</div>
</td>
<td class="quote_unflag" onclick="javascript:this.firstElementChild.click();">
	<input type="radio" id="qu'.$quoteid.'" name="q'.$quoteid.'" value="u'.$quoteid.'"><label for="qu'.$quoteid.'">'.lang('flag_quote_unflag').'</label>
</td>
</tr>';
    }

    function flag_queue_page($inner_html)
    {
<<<<<<< HEAD
	$str = '<div id=admin_flags_all><h1 id="admin_flag_title">'.lang('flag_quote_adminpage_title').'</h1>';
=======
	$str = '<h1 id="admin_flag_title">'.lang('flag_quote_adminpage_title').'</h1>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

	$str .= '<form action="?'.urlargs('flag_queue','judgement').'" method="post">
<table width="100%" class="admin_queue">';

	$str .= $inner_html;

	$str .= '</table>
<input type="submit" value="'.lang('flag_quote_adminpage_submit_btn').'" />
<input type="reset" value="Reset" />
&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="'.lang('flag_quote_adminpage_unflag_all_btn').'" name="unflag_all">
<input type="submit" value="'.lang('flag_quote_adminpage_delete_all_btn').'" name="delete_all">
'.lang('flag_quote_adminpage_verify').'<input type="checkbox" name="do_all" id="do_all"><label for="do_all"></label>
<<<<<<< HEAD
</form></div>';
=======
</form>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

	return $str;
    }

    function add_news_page($previewnews, $newstxt)
    {
	$str = '  <div id="admin_add-news_all">
   <h1 id="admin_add-news_title">'.lang('add_news_title').'</h1>';
	if (isset($previewnews)) $str .= '<div class="admin_preview_news">'.$previewnews.'</div>';
	$str .= '<p>'.lang('add_news_help').'
   <form method="post" action="?'.urlargs('add_news','submit').'">
	<textarea cols="80" rows="5" name="news" id="add_news_news">'.$newstxt.'</textarea><br />
        <input type="submit" value="'.lang('preview_news_btn').'" id="add_preview" name="preview" />
        <input type="submit" value="'.lang('add_news_btn').'" id="add_news" name="submit" />
   </form>
  </div>
';
	return $str;
    }

    function register_user_page()
    {
	global $CAPTCHA;
	$str = '  <div id="register-user_all">
   <h1 id="register-user_title">'.lang('register_user_title').'</h1>
   <form method="post" action="?'.urlargs('register','update').'">
   <table>
   <tr><td>'.lang('register_user_username_label').'</td><td><input type="text" name="username" id="register-user_username" /></td></tr>
   <tr><td>'.lang('register_user_password_label').'</td><td><input type="password" name="password" /></td></tr>
   <tr><td>'.lang('register_user_verifypassword_label').'</td><td><input type="password" name="verifypassword" /></td></tr>
   <tr><td></td><td><input type="submit" value="'.lang('register_user_btn').'" id="register-user_submit" /></td></tr>
   </table>'.$CAPTCHA->get_CAPTCHA('register_user').'
   </form>
  </div>
';
	return $str;
    }

    function add_user_page()
    {
	return '  <div id="admin_add-user_all">
   <h1 id="admin_add-user_title">'.lang('add_user_title').'</h1>
   <form method="post" action="?'.urlargs('add_user','update').'">
   <table>
   <tr><td>'.lang('add_user_username_label').'</td><td><input type="text" name="username" id="admin_add-user_username" /></td></tr>
   <tr><td>'.lang('add_user_randomsalt_label').'</td><td><input type="text" name="salt" value="'.str_rand(8,'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789').'" id="admin_add-user_salt" /></td></tr>
   <tr><td>'.lang('add_user_password_label').'</td><td><input type="text" name="password" /></td></tr>
   <tr><td>'.lang('add_user_level_label').'</td><td>'.user_level_select().'</td></tr>
   <tr><td></td><td><input type="submit" value="'.lang('add_user_btn').'" id="admin_add-user_submit" /></td></tr>
   </table>
   </form>
  </div>
';
    }

    function change_password_page()
    {
<<<<<<< HEAD
	return '  <div id=admin_change-pw_all><h1 id="admin_change-pw_title">'.lang('change_password_title').'</h1>
=======
	return '  <h1 id="admin_change-pw_title">'.lang('change_password_title').'</h1>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
  <form action="?'.urlargs('change_pw','update',$_SESSION['userid']).'" method="post">
  <table>
  <tr><td>'.lang('change_password_oldpass').'</td><td><input type="password" name="old_password"></td></tr>
  <tr><td>'.lang('change_password_newpass').'</td><td><input type="password" name="new_password"></td></tr>
  <tr><td>'.lang('change_password_verify').'</td><td><input type="password" name="verify_password"></td></tr>
  <tr><td></td><td><input type="submit" value="'.lang('change_password_submit_btn').'"></td></tr>
  </table>
<<<<<<< HEAD
  </form></div>';
=======
  </form>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
    }

    function edit_user_page_form($id, $who, $username, $level)
    {
<<<<<<< HEAD
	return '<div id=edit_user_all><h1 id="edit_user-title">'.lang('edit_user_title').' '.$username.'</h1>
=======
	return '<h1 id="edit_user-title">'.lang('edit_user_title').' '.$username.'</h1>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
  <form action="?'.urlargs('users','update',$id).'" method="post">
  <table>
  <tr><td>'.lang('edit_user_newname').'</td><td><input type="text" value="'.$username.'" name="user"></td></tr>
  <tr><td>'.lang('edit_user_newpass').'</td><td><input type="text" name="password"> '.lang('edit_user_newpass_help').'</td></tr>
  <tr><td>'.lang('edit_user_newlevel').'</td><td>'.user_level_select($level).'</td></tr>
  <tr><td></td><td><input type="submit" value="'.lang('edit_user_submit_btn').'"></td></tr>
  </table>
<<<<<<< HEAD
  </form></div>';
=======
  </form>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

    }

    function edit_user_page_table_row($id, $user, $password, $level, $current=0)
    {
	return '    <tr'.($current ? ' class="curr"' : '').'>
     <td>'.$id.'</td>
     <td><a href="?'.urlargs('users','edit',$id).'">'.$user.'</a></td>
     <td>'.$password.'</td>
     <td>'.$level.'</td>
     <td><input type="checkbox" name="d'.$id.'" value="'.$id.'" id="chkbox_d'.$id.'"/><label for="chkbox_d'.$id.'"></label></td>
    </tr>
';
    }

    function edit_user_page_table($innerhtml)
    {
<<<<<<< HEAD
	$str = '  <div id=admin_users_all><h1 id="admin_users_title">'.lang('users_list_title').'</h1>
=======
	$str = '  <h1 id="admin_users_title">'.lang('users_list_title').'</h1>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
  <form action="?'.urlargs('users','delete').'" method="post">
   <table class="users">
    <tr>
     <th>'.lang('users_list_id').'</th>
     <th>'.lang('users_list_username').'</th>
     <th>'.lang('users_list_pwhash').'</th>
     <th>'.lang('users_list_level').'</th>
     <th>'.lang('users_list_delete').'</th>
    </tr>
';

	$str .= $innerhtml;

	$str .= '  </table>
  <input type="submit" value="'.lang('users_list_submit_btn').'" />&nbsp;'.lang('users_list_verify').' <input type="checkbox" name="verify" value="1" id="verify_submit"/><label for="verify_submit"></label>
<<<<<<< HEAD
 </form></div>
=======
 </form>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
';

	return $str;
    }

    function user_login_page()
    {
    return '<h1 id="login_title">'.lang('login_title').'</h1>'.
	'<div id="admin_all"><p>'.lang('user_login_greeting').'</p>
    <form action="?'.urlargs('login','login').'" method="post">
    <table>
    <tr><td><label for="user_login_username-box">'.lang('login_username').'</label></td><td><input type="text" name="rash_username" size="8" id="user_login_username-box" /></td></tr>
    <tr><td><label for="user_login_password-box">'.lang('login_password').'</label></td><td><input type="password" name="rash_password" size="8" id="user_login_password-box" /></td></tr>
    <tr><td><label for="remember_login_input">'.lang('login_remember').'</label></td><td><input type="checkbox" name="remember_login" id="remember_login_input"><label for="remember_login_input"></label></td></tr>
    <tr><td></td><td><input type="submit" value="'.lang('login_submit_btn').'" id="user_login_submit-button" /></td></tr>
    </table>
    </form></div>';
    }

    function admin_login_page()
    {
    return '<h1 id="login_title">'.lang('login_title').'</h1>'.
	'<div id="admin_all"><p>'.lang('admin_login_greeting').'</p>
    <form action="?'.urlargs('admin','login').'" method="post">
    <table>
    <tr><td><label for="admin_login_username-box">'.lang('login_username').'</label></td><td><input type="text" name="rash_username" size="8" id="admin_login_username-box" /></td></tr>
    <tr><td><label for="admin_login_password-box">'.lang('login_password').'</label></td><td><input type="password" name="rash_password" size="8" id="admin_login_password-box" /></td></tr>
    <tr><td><label for="remember_login_input">'.lang('login_remember').'</label></td><td><input type="checkbox" name="remember_login" id="remember_login_input"><label for="remember_login_input"></label></td></tr>
    <tr><td></td><td><input type="submit" value="'.lang('login_submit_btn').'" id="admin_login_submit-button" /></td></tr>
    </table>
    </form></div>';
    }

    function format_possible_dupes($dupes = NULL)
    {
	$str = '';
	if ($dupes) {
	    $str = ' <span class="maybe_dupe">('.lang('possible_dupe').': ';
	    $arr = array();
	    foreach ($dupes as $d) {
		array_push($arr, '<a href="?'.$d.'">'.$d.'</a>');
	    }
	    $str .= join(', ', $arr).')</span>';
	}
	return $str;
    }

    function quote_queue_page_iter($quoteid, $quotetxt, $dupes=NULL)
    {
	return '     <tr>
      <td class="quote_no" onclick="javascript:this.firstElementChild.click();">
       <input type="radio" id="qn'.$quoteid.'" name="q'.$quoteid.'" value="n'.$quoteid.'"><label for="qn'.$quoteid.'">'.lang('quote_queue_no').'</label>
      </td>
      <td>'.$this->edit_quote_button($quoteid, 1).$this->format_possible_dupes($dupes).'
        <div class="quote_quote">
		'.$quotetxt.'
        </div>
      </td>
	  <td class="quote_yes" onclick="javascript:this.firstElementChild.click();">
       <input type="radio" id="qy'.$quoteid.'" name="q'.$quoteid.'" value="y'.$quoteid.'" style="text-align: right"><label for="qy'.$quoteid.'">'.lang('quote_queue_yes').'</label>
	  </td>
     </tr>
';

    }

    function quote_queue_page($innerhtml)
    {
<<<<<<< HEAD
	$str = '<div id=admin_queue_all><h1 id="admin_queue_title">'.lang('quote_queue_admin_title').'</h1>';
=======
	$str = '<h1 id="admin_queue_title">'.lang('quote_queue_admin_title').'</h1>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

	$str .= '  <form action="?'.urlargs('adminqueue','judgement').'" method="post">
   <table width="100%" cellspacing="0" class="admin_queue">';

	$str .= $innerhtml;

	$str .= '   </table>
   <input type="submit" value="'.lang('quote_queue_submit_btn').'" />
   <input type="reset" value="'.lang('quote_queue_reset_btn').'" />
<<<<<<< HEAD
  </form></div>
=======
  </form>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
';

	return $str;
    }

    function quote_upvote_button($quoteid, $canvote, $ajaxy=FALSE)
    {
	$s = ' class="quote_plus" id="quote_plus_'.$quoteid.'"';
	if (!$canvote) {
<<<<<<< HEAD
	    $url = '<a href="?'.urlargs('vote',$quoteid,'plus').'" '.$s.' title="'.lang('upvote').'">▲</a>';
	    if ($ajaxy) {
		return '<script type="text/javascript">
document.write(\'<a href="javascript:ajax_vote('.$quoteid.',1);" '.$s.' title="'.lang('upvote').'">▲</a>\');
=======
	    $url = '<a href="?'.urlargs('vote',$quoteid,'plus').'" '.$s.' title="'.lang('upvote').'">+</a>';
	    if ($ajaxy) {
		return '<script type="text/javascript">
document.write(\'<a href="javascript:ajax_vote('.$quoteid.',1);" '.$s.' title="'.lang('upvote').'">+</a>\');
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
</script><noscript>'.$url.'</noscript>';
	    }
	    return $url;
	}
	return '<span '.$s.' title="'.lang('cannot_vote_'.$canvote).'">+</span>';
    }

    function quote_downvote_button($quoteid, $canvote, $ajaxy=FALSE)
    {
	$s = ' class="quote_minus" id="quote_minus_'.$quoteid.'"';
	if (!$canvote) {
<<<<<<< HEAD
	    $url = '<a href="?'.urlargs('vote',$quoteid,'minus').'" '.$s.' title="'.lang('downvote').'">▼</a>';
	    if ($ajaxy) {
		return '<script type="text/javascript">
document.write(\'<a href="javascript:ajax_vote('.$quoteid.',-1);" '.$s.' title="'.lang('downvote').'">▼</a>\');
=======
	    $url = '<a href="?'.urlargs('vote',$quoteid,'minus').'" '.$s.' title="'.lang('downvote').'">-</a>';
	    if ($ajaxy) {
		return '<script type="text/javascript">
document.write(\'<a href="javascript:ajax_vote('.$quoteid.',-1);" '.$s.' title="'.lang('downvote').'">-</a>\');
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
</script><noscript>'.$url.'</noscript>';
	    }
	    return $url;
	}
	return '<span '.$s.' title="'.lang('cannot_vote_'.$canvote).'">-</span>';
    }

    function quote_flag_button($quoteid, $canflag)
    {
	global $CONFIG;
	if (isset($CONFIG['login_required']) && ($CONFIG['login_required'] == 1) && !isset($_SESSION['logged_in'])) return '';
	if ($CONFIG['auto_flagged_quotes'] == 1) return '';
	if ($canflag)
<<<<<<< HEAD
	    return '<a href="?'.urlargs('flag',$quoteid).'" class="quote_flag" title="'.lang('flagquote').'"></a>';
	return '<span class="quote_flag" title="'.lang('quote_already_flagged').'"></span>';
=======
	    return '<a href="?'.urlargs('flag',$quoteid).'" class="quote_flag" title="'.lang('flagquote').'">X</a>';
	return '<span class="quote_flag" title="'.lang('quote_already_flagged').'">X</span>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
    }

    function quote_iter($quoteid, $rating, $quotetxt, $canflag, $canvote, $date=null)
    {
<<<<<<< HEAD
	$str = '<div class="quote_whole" id="q'.$quoteid.'">';

       $str.= '    <div class="quote_option-bar">


	     '.$this->quote_upvote_button($quoteid, $canvote)
           .' '.'<span class="quote_rating"><span id="quote_rating_'.$quoteid.'">'.$rating.'</span></span>'
           .' '.$this->quote_downvote_button($quoteid, $canvote).'</div>
            <div class="quote_head">
            <a href="?'.urlargs($quoteid).'" class="quote_number">'.lang('quote_nr').' #'.$quoteid.'</a>
	';

        if (isset($date)) {
            $str .= "     <span class=\"quote_date\">" . $date . "</span>\n";
        }

	$str .='</div> <!-- quote_head -->';


	$str .= '
    <div class="quote_quote">
     '.$quotetxt.'
    </div>
    <div class=quote_bottom_bar>
    ';

        $str .= edit_quote_button($quoteid)
	    .' '.$this->quote_flag_button($quoteid, $canflag);


        $str.='</div>
=======
	$str = '<div class="quote_whole" id="q'.$quoteid.'">
    <div class="quote_option-bar">
     <a href="?'.urlargs($quoteid).'" class="quote_number">#'.$quoteid.'</a>'
	    .' '.$this->quote_upvote_button($quoteid, $canvote)
	    .' '.'<span class="quote_rating">(<span id="quote_rating_'.$quoteid.'">'.$rating.'</span>)</span>'
	    .' '.$this->quote_downvote_button($quoteid, $canvote)
	    .' '.$this->quote_flag_button($quoteid, $canflag)
	    .' '.edit_quote_button($quoteid);

	if (isset($date)) {
	    $str .= "     <span class=\"quote_date\">" . $date . "</span>\n";
	}

	$str .= '
    </div>
    <div class="quote_quote">
     '.$quotetxt.'
    </div>
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
   </div>
';
	return $str;
    }

    function quote_list($title, $pagenumbers, $quotes)
    {
<<<<<<< HEAD
	$str = "<div id=main_wrapper>";
=======
	$str = '';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
	if (isset($title))
	    $str .= '<h1 id="quote_origin-name">'.$title.'</h1>';
	$str .= $pagenumbers;
	$str .= '<div id="quote_list">'.$quotes.'</div>';
	$str .= '<p>';
	$str .= $pagenumbers;
<<<<<<< HEAD
	return $str.'</div>';
=======
	return $str;
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
    }

    function flag_page($quoteid, $quotetxt, $flag)
    {
	global $CAPTCHA;

<<<<<<< HEAD
	$str = '<div id=flag_quote_all>';
=======
	$str = '';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442

	$str .= '<h1>'.lang('flag_quote_title').'</h1>';

	$str .= $this->get_messages();
	if ($flag == 0)
	    $str .= '<p>'.lang('flag_quote_explanation');

	$str .= '<div class="quote_quote">'.$quotetxt.'</div>';

	if ($flag == 0) {
	    $str .= '<form action="?'.urlargs('flag',$quoteid, 'verdict').'" method="post">';
	    $str .= $CAPTCHA->get_CAPTCHA('flag');
	    $str .= '<input type="submit" value="'.lang('flag_quote_submit_btn').'" />
   <input type="reset" value="'.lang('flag_quote_reset_btn').'" />
<<<<<<< HEAD
  </form></div>';
=======
  </form>';
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
	}
	return $str;
    }

<<<<<<< HEAD
}
=======
}
>>>>>>> 0fc100197a68d98d974026081f808031e2fea442
