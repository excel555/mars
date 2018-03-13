<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require('env_config.php');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default_master';
$active_record = TRUE;
$_master_slave_relation = array(
    //  'default_master' => array('default_slave'),
    'default_master'=>array('default_slave'),
);
$db['default_master']['hostname'] = $env_config_db['hostname'];
$db['default_master']['username'] = $env_config_db['username'];
$db['default_master']['password'] = $env_config_db['password'];
$db['default_master']['database'] = $env_config_db['database'];
$db['default_master']['dbdriver'] = 'mysqli';
$db['default_master']['dbprefix'] = 'cb_';
$db['default_master']['pconnect'] = FALSE;
$db['default_master']['db_debug'] = 1;
$db['default_master']['cache_on'] = FALSE;
$db['default_master']['cachedir'] = '';
$db['default_master']['char_set'] = 'utf8';
$db['default_master']['dbcollat'] = 'utf8_general_ci';
$db['default_master']['swap_pre'] = '';
$db['default_master']['autoinit'] = FALSE;
$db['default_master']['stricton'] = FALSE;


$db['default_slave']['hostname'] = $env_config_slave_db['hostname'];
$db['default_slave']['username'] = $env_config_slave_db['username'];
$db['default_slave']['password'] = $env_config_slave_db['password'];
$db['default_slave']['database'] = $env_config_slave_db['database'];
$db['default_slave']['dbdriver'] = 'mysqli';
$db['default_slave']['dbprefix'] = 'cb_';
$db['default_slave']['pconnect'] = FALSE;
$db['default_slave']['db_debug'] = 1;
$db['default_slave']['cache_on'] = FALSE;
$db['default_slave']['cachedir'] = '';
$db['default_slave']['char_set'] = 'utf8';
$db['default_slave']['dbcollat'] = 'utf8_general_ci';
$db['default_slave']['swap_pre'] = '';
$db['default_slave']['autoinit'] = FALSE;
$db['default_slave']['stricton'] = FALSE;



//$db['log']['hostname'] = 'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
//$db['log']['username'] = 'root';
//$db['log']['password'] = '30XkkaR&FNYjkWL7Z';
//$db['log']['database'] = 'db_log';
//$db['log']['dbdriver'] = 'mysqli';
//$db['log']['dbprefix'] = 'tb_';
//$db['log']['pconnect'] = FALSE;
//$db['log']['db_debug'] = FALSE;
//$db['log']['cache_on'] = FALSE;
//$db['log']['cachedir'] = '';
//$db['log']['char_set'] = 'utf8';
//$db['log']['dbcollat'] = 'utf8_general_ci';
//$db['log']['swap_pre'] = '';
//$db['log']['autoinit'] = FALSE;
//$db['log']['stricton'] = FALSE;


//$db['db_log']['hostname'] = 'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
//$db['db_log']['username'] = 'root';
//$db['db_log']['password'] = '30XkkaR&FNYjkWL7Z';
//$db['db_log']['database'] = 'db_log';
//$db['db_log']['dbdriver'] = 'mysqli';
//$db['db_log']['dbprefix'] = 'tb_';
//$db['db_log']['pconnect'] = FALSE;
//$db['db_log']['db_debug'] = FALSE;
//$db['db_log']['cache_on'] = FALSE;
//$db['db_log']['cachedir'] = '';
//$db['db_log']['char_set'] = 'utf8';
//$db['db_log']['dbcollat'] = 'utf8_general_ci';
//$db['db_log']['swap_pre'] = '';
//$db['db_log']['autoinit'] = FALSE;
//$db['db_log']['stricton'] = FALSE;


//$db['sns_db_master']['hostname'] = 'test.czxocis9har7.rds.cn-north-1.amazonaws.com.cn';
//$db['sns_db_master']['username'] = 'root';
//$db['sns_db_master']['password'] = '30XkkaR&FNYjkWL7Z';
//$db['sns_db_master']['database'] = 'sns_db';
//$db['sns_db_master']['dbdriver'] = 'mysqli';
//$db['sns_db_master']['dbprefix'] = 'ttgy_';
//$db['sns_db_master']['pconnect'] = FALSE;
//$db['sns_db_master']['db_debug'] = FALSE;
//$db['sns_db_master']['cache_on'] = FALSE;
//$db['sns_db_master']['cachedir'] = '';
//$db['sns_db_master']['char_set'] = 'utf8';
//$db['sns_db_master']['dbcollat'] = 'utf8_general_ci';
//$db['sns_db_master']['swap_pre'] = '';
//$db['sns_db_master']['autoinit'] = FALSE;
//$db['sns_db_master']['stricton'] = FALSE;


/* End of file database.php */
/* Location: ./application/config/database.php */