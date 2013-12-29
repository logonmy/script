<?php

	header( 'Content-Type: text/html; charset=utf-8' );
	set_time_limit( 0 );
	error_reporting( E_ERROR  );
	
	$opts = array(
					'http'=> array(
    								'method' => "GET",
   									'header' => "Accept-language: zh-cn\r\n" .
              						"User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:11.0) Gecko/20100101 Firefox/11.0\r\n" .
              						"Accept: */*"
								  )		
    			);

	$context = stream_context_create( $opts );

    /* 连接数据库 */
    $con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );


	$query = "select distinct A.package_name, A.app_name from mcp_content_appinfo as A;";
	echo $query."\n";
	$result = mysql_query( $query );
	$i = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$package_name = $row[0];
		$game_name = $row[1];
		//echo $package_name."\n";
	    $query2 = "select * from mcp_game_package_rel where package_name=\"$package_name\";";
		echo $query2."\n";
		$result2 = mysql_query($query2);
		$num_rows = mysql_num_rows($result2);
		echo "num_rows:"."$num_rows"."\n";
		if($num_rows == 0)
		{
			$query3 = "insert into mcp_game_package_rel values(\"\", \"$package_name\", \"$game_name\", \"\");";
			echo $query3."\n";
			mysql_query($query3);
		}
	}
?>
