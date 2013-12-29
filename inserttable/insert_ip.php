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

	$f= fopen("./bj.txt","r");
	while (!feof($f))
	{
		$line = fgets($f);
		list($ip_start, $ip_end, $ip_num) = explode("\t",$line);
		$ip_start = trim($ip_start);
		$ip_end = trim($ip_end);
		$ip_num = trim($ip_num);
		$query = "select * from ip_list where ip_start=\"$ip_start\";";
		$result = mysql_query( $query );
		$num_rows = mysql_num_rows($result);
		//echo $query."\n";
		//echo $num_rows."\n";
		if($num_rows > 0)
		{
			continue;	
		}
		$now_date = date( "Y-m-d H:i:s" );
		$query2 = " insert into ip_list values('', \"$ip_start\", \"$ip_end\", \"$ip_num\", \"1\");";
		$result2 = mysql_query( $query2 );
		if(!$result2)
		{
			echo "失败" . "\n";
		}
		echo $query2 . "\n";
	}

	fclose($f);
	mysql_close($con);

?>
