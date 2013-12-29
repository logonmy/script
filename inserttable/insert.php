<?php
	/*
	* =====================================================================================
	*
	*       Filename:  insert.php
	*
	*    Description:  将游戏包名和游戏名入库
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
      //$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
  	$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
  	if ( !$con ){
		die( 'Could not connect: ' . mysql_error() );
	}
	$db_selected = mysql_select_db( "MCP", $con );
 	mysql_query( "set names utf8" );

	$f= fopen("./app_2013-10-10.txt","r");
	while (!feof($f))
	{
		$line = fgets($f);
		list($name, $package) = explode("\t",$line);
		$query = "select * from mcp_game_package_rel where package_name=\"$package\";";
		$result = mysql_query( $query );
		$num_rows = mysql_num_rows($result);
		//echo $query."\n";
		//echo $num_rows."\n";
		if($num_rows > 0)
		{
			continue;	
		}
		$now_date = date( "Y-m-d H:i:s" );
		$query2 = " insert into mcp_game_package_rel values('', \"$package\", \"$name\", \"$now_date\");";
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
