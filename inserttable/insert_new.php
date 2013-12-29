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
    header('Content-Type: text/html; charset=utf-8');
    set_time_limit(0);
    error_reporting(E_ERROR);
                
    $opts = array(
                    'http'=> array(
                                    'method'=>"GET",
                                    'header'=>"Accept-language: zh-cn\r\n" .
                                    "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:11.0) Gecko/20100101 Firefox/11.0\r\n" .
                                    "Accept: */*"
                                                                                                                                                                                                                            )    
                );  

    $context = stream_context_create($opts);

	/* 连接数据库 */
  	$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
  	if ( !$con ){
		die( 'Could not connect: ' . mysql_error() );
	}
	$db_selected = mysql_select_db( "MCP", $con );
 	mysql_query( "set names utf8" );

	/* 初始化redis,方便每小时检验渠道是否更新 */
    $redis = new Redis();
    $redis -> connect( '127.0.0.1', 6379 );
    $redis -> select( 3 );

	/* $query = "select package_name from mcp_game_package_rel;"; */
	/* $result = mysql_query( $query ); */
	/* $i = 0; */
	/* while( $row=mysql_fetch_row($result) ) */
	/* { */
		/* $db_package_array[$i++] = $row[0]; */
	/* } */

	$f= fopen("./uniq_app_2013-06-07.txt","r");
	while (!feof($f))
	{
		$line = fgets($f);
		list($name, $package) = explode("\t",$line);
		if( !($redis->sIsMember( "package_key", "$package")) )
		{
			$redis->sAdd("package_key","$package");
			$now_date = date( "Y-m-d H:i:s" );
			$query = " insert into mcp_game_package_rel values('', \"$package\", \"$name\", \"$now_date\");";
			$result = mysql_query( $query );
			if(!$result)
			{
				echo "失败" . "\n";
			}
			echo $query . "\n";
		}
	}

	fclose($f);
	mysql_close($con);

?>
