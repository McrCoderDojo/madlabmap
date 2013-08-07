<?php

// assumes a db handle in $dbh

define( 'MAP_TABLE_NAME', 'map_data' );

function create_db() {
	global $db;
	
// Create table
$sql="
CREATE TABLE map_data(
  id bigint(20) unsigned NOT NULL auto_increment,
  data_type CHAR(30),
  crime_id CHAR(100),
  event_date DATE,
  longitude FLOAT,
  latitude FLOAT,
  location CHAR(100),
  outcome_type CHAR(100),
  PRIMARY KEY (id),
  KEY data_type (data_type),
  KEY longitude (longitude),
  KEY latitude (latitude) )";
	
// Execute query
if ($db->query( $sql ) )  {
  echo "Table created successfully";
} else {
  echo "Error creating table: " ;//. $db->error();
}

}

function import_police_file( $filename ) {
  global $wpdb;

  zed1_debug();
  $srcfilename = './' . $filename;
  zed1_debug( "Looking for $srcfilename" );

  $ret = process_file( $srcfilename, 'process_police_row', true );

  zed1_debug("return from process_file", $ret);

  return $ret;
} // end import_police_file

function process_file( $filepath, $callback = '', $skipfirst = false ) {
  $err_response = '';
  $all_ret = array();
  $count = 0;
  $row = 0;

  $fp = fopen( $filepath, 'r' );
  if ( $fp !== false ) {
	  while ( ($data = fgetcsv( $fp, 0, ',' ) ) !== FALSE ) {
		  $row++;
		  zed1_debug( "row $row =", $data );

		  if ( ( 1 == $row ) && ( 'Y' == $skipfirst ) )
			  continue;

		  if ( is_callable( $callback ) ) {
			  $ret = call_user_func( $callback, $data, $row );

			  if ( isset( $ret['error'] ) ) {
				  $err_response .= '<p>' . $ret['error'] . '</p>';
			  } else if ( isset( $ret['db_error'] ) ) {
				  $err_response .= '<p>' . $ret['db_error'] . '</p>';
			  } else if ( isset( $ret['warning'] ) ) {
			  // ignore
			  } else if ( isset( $ret['ok'] ) ) {
				  ++$count;
			  }
			  $all_ret[] = $ret;
		  }
	  }
	  fclose( $fp );
  }

  $retval = array();
  $retval['error']   = $err_response;
  $retval['count']   = $count;
  //$retval['log']	   = $all_ret;
  unset( $all_ret );
  //error_log( "process_file returning " . var_export( $retval, true ) );
  return $retval;
} // end process_file

function process_police_row( $row, $line ) {
  global $wpdb;

  zed1_debug( $row );
  $ret = array();
  //validation.
  if ( count( $row ) >= 7 ) {
  //update or insert?
	  $update = false;
	  $data						  = array();
	  $data['data_type']			  = 'police';
	  $data['crime_id']			  = trim( $row[ 0] );  // crime id
	  $data['event_date']		  = trim( $row[ 1] ); // month
	  // skip					  = trim( $row[ 2] ); // reported by
	  $data['location']			  = trim( $row[ 3] ); // falls within
	  $data['longitude']		  = floatval( $row[ 4] ); // longittude
	  $data['latitude']			  = floatval( $row[ 5] ); // latitude
	  //skip					  = floatval( $row[ 6] ); // location
	  //skip					  = trim( $row[ 7] ); // lsoa code
	  //skip					  = trim( $row[ 8] ); // lsoa name
	  $data['outcome_type']		  = trim( $row[ 9] ); // outcome type


	  $res = $wpdb->insert( $wpdb->prefix . MAP_TABLE_NAME, $data, get_map_format() );
	  //zed1_debug("result:", $res);

	  if ( false === $res ) {
		  if ( $wp_error ) {
			  $ret = array( 'db_error' => "Could not insert/update MAP record from line $line into the database: " . $wpdb->last_error );
		  } else {
			  $ret = array( 'db_error' => "Unknown DB error with line $line: "  . $wpdb->last_error );
		  }
	  } else {
		  if ( $update )
			  $ret = array( 'ok' => "Processed line $line. crime_id={$data['crime_id']}", 'id' => $data['crime_id'] );
		  else
			  $ret = array( 'ok' => "Processed line $line. crime_id={$data['crime_id']}", 'id' => $data['crime_id'], 'new' => $data['crime_id'] );
	  }
  } else {
	  if ( count( $row ) == 0 )
		  $ret = array( 'warning' => "Skipping empty line $line. " );
	  else
		  $ret = array( 'error' => "Not enough data in line $line. " . count( $row ) . " values detected." );
  }
  zed1_debug("result:", $ret);
  return $ret;
} // end process_police_row


	//	'%d', '%f', '%s'
$map_format = array(
					'%s',	/*[ 0]*/
					'%s',	/*[ 1]*/
					'%s',	/*[ 2]*/
					'%s',	/*[ 3]*/
					'%f',	/*[ 4]*/
					'%f',	/*[ 5]*/
					'%s',	/*[ 6]*/
					'%s',	/*[ 7]*/
				   );

	function get_map_format() {
		global $map_format;
		return $map_format;
	} // end get_result


	
if ( !function_exists( 'zed1_debug' ) ) { // protect from re-definition
/* utility function used by everything */
function zed1_debug( $message= '' ) {
	$trace = debug_backtrace();
	array_shift( $trace ); // discard ourselves
	$caller = array_shift( $trace );
	$func = $caller['function'];
	if ( isset( $caller['class'] ) )
		$func = $caller['class'] . '::' . $func;
	$out = $func . '() ';
	if ( is_scalar( $message ) )
		$out .= $message;
	else
		$out .= ' ' . var_export( $message, true );

	$args = array_slice( func_get_args(), 1 );
	if ( !empty( $args ) )
		foreach ( $args as $arg )
			$out .= ' ' . var_export( $arg, true );

	error_log( $out );
} // end zed1_debug()
}
