<?php

	/* 初始化LOG配置 */
	include_once '/home/work/webserver/php/lib/php/Bingo/Log.php';
	include('/home/work/webserver/php/lib/php/Bingo/Config.php');

	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => './insert_app_name.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init($arrLogConfig, 'ui');

    /* 连接数据库 */
	$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	//$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$query = "select id, name from mcp_content;";
	$result = mysql_query( $query );
	while( $row=mysql_fetch_row($result) )
	{
		$c_id = $row[0];
		$name = $row[1];
		$query2 = "update mcp_content_news set app_name=\"$name\" where c_id=$c_id;";
		echo $query2."\n";
		$result2 = mysql_query( $query2 );
	}
?>
