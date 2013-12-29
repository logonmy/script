<?php
	/*
	* =====================================================================================
	*
	*       Filename:  add_grab_num_redis.php
	*
	*    Description:  将抢号的发号入redis，避免因为mysql读库多线程导致的同时读一个发号的问题。
	*
	*        Version:  1.0
	*        Created:  10/22/2013 12:14:23 [A/P]M
	*       Revision:  none
	*
	*        Author:  RD. Zheng,Xie, zhengxie@duoku.com
	*        Company:  Duoku, Beijing, China
	*
	* =====================================================================================
	*/
	
	/*set_time_limit(0);*/
	//require_once 'Excel/reader.php';

	//$file_path = $argv[1];
	//$data = new Spreadsheet_Excel_Reader();
	//$data->setOutputEncoding('CP1251');

	//$data->setColumnFormat(2, '%d');
	//$data->read("$file_path");
    /*echo $data->sheets[0]['numRows']."\n";*/
	$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$redis = new Redis();
    $redis -> connect( '10.10.0.29', 6382 );
    $redis -> select( 3 );


	$query = "select num from mcp_content_grab_num where grab_id=970 and is_occupy=0;"; 
	$result = mysql_query( $query );
	$i = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$key = "grab_id:970";
		$grab_num = $row[0];
		echo $grab_num."\n";
		$redis->sAdd("$key", "$grab_num");
	}
?>
