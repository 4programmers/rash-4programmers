<?php

define('USER_SUPERUSER', 1);
define('USER_ADMIN', 2);
define('USER_MOD', 3);
define('USER_NORMAL', 4);

$user_levels = array(USER_SUPERUSER => 'superuser',
		     USER_ADMIN => 'administrator',
		     USER_MOD => 'moderator',
		     USER_NORMAL => 'user');

