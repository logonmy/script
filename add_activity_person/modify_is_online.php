<?php

	/* 初始化LOG配置 */
	include_once '/home/work/webserver/php/lib/php/Bingo/Log.php';
	include('/home/work/webserver/php/lib/php/Bingo/Config.php');

	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => './add_activity_person.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init($arrLogConfig, 'ui');

    /* 连接数据库 */
	//$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$nowtime = date("Y-m-d G:i:s");

	$total_num = 0;
	//$query = "select c_id, lowest_num, highest_num from mcp_content_qp_activity_person where enable=1 and \"$nowtime\">start_time and \"$nowtime\"<end_time;"; 
	$query = "select id, type_id from mcp_content;"; 
	$result = mysql_query( $query );
	while( $row=mysql_fetch_row($result) )
	{
		$id = $row[0];
		$type_id = $row[1];
		if( $type_id == "25" )
		{
			$query2 = "update mcp_content set is_online=1 where id=$id;";
			echo $query2."\n";
			$result2 = mysql_query( $query2 );
		}
		else if( $type_id == "11" )
		{
			$query2 = "update mcp_content set is_online=2 where id=$id;";
			echo $query2."\n";
			$result2 = mysql_query( $query2 );
		}
	}
?>
