<?php
define('DB_NAME', 'max_db');
define('DB_USER', 'max_user');
define('DB_PASSWORD', 'max_db_YRS_2013');
define('DB_HOST', 'localhost');
@ini_set('display_errors',1);

$dbh = mysql_connect( DB_HOST, DB_USER, DB_PASSWORD );
if ( !$dbh ) {
	print( "error connecting to the database" );
}
if ( !mysql_select_db( DB_NAME, $dbh ) ) {
	print( "error connecting to the database" );
}
//include_once( 'createdb.php' );

