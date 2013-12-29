<?php
	/*
	* =====================================================================================
	*
	*       Filename:  insert.php
	*
	*    Description:  将ip范围段入库
	*
	*        Version:  1.0
	*        Created:  24/04/2013 12:14:23 [A/P]M
	*       Revision:  none
	*
	*        Author:  RD. Zheng,Xie, zhengxie@duoku.com
	*        Company:  Duoku, Beijing, China
	*
	* =====================================================================================
	*/
    

	/* 连接数据库 */
	$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
    //$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
  	if ( !$con ){
		die( 'Could not connect: ' . mysql_error() );
	}
	$db_selected = mysql_select_db( "MCP", $con );
 	mysql_query( "set names utf8" );

	$query = "select * from ip_list;"; 
	$result = mysql_query( $query );
	$i = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$ip_start = $row[1];
		$ip_end = $row[2];
		$province_tag = $row[4];

		$key = "ip_list";
		$value = $ip_start.",".$ip_end.",".$province_tag;

		$redis = new Redis();
		$redis -> connect( '10.10.0.141', 6382 );
		$redis -> select( 3 );

		$redis->lPush("$key", "$value");

	}
	mysql_close($con);

?>
