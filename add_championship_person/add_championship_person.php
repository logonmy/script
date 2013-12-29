<?php

    /* 连接数据库 */
    //$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$nowtime = date("Y-m-d G:i:s");

	$query = "select id, join_num, lowest_num, highest_num from mcp_content_championship where enable=1 and \"$nowtime\">start_time and \"$nowtime\"<end_time;"; 
	$result = mysql_query( $query );
	$i = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$id = $row[0];
		$join_num = $row[1];
		echo $join_num."\n";
		$lowest_num = $row[2];
		$highest_num = $row[3];
		$rate = rand($lowest_num, $highest_num);
		$rate *= get_rate();
		echo $rate."\n";
		$join_num_new = $join_num + $rate;
		$query2 = "update mcp_content_championship set join_num=$join_num_new where id=$id;"; 
		echo $nowtime."  ".$query2."\n\n\n";
		$result2 = mysql_query( $query2 );
		$i++;
	}
	
	function get_rate()
	{
		$i = weekday_or_weekend();
		$j = when_the_hour();
		if($i === 1 && $j === 1)
			return 1.3*1.0;
		if($i === 1 && $j === 0)
			return 1.3*0.5;
		if($i === 0 && $j === 1)
			return 1.0;
		if($i === 0 && $j === 0)
			return 1.0*0.5;
	}

	/* weekday返回0，weekend返回1 */
	function weekday_or_weekend()
	{
		$date = getdate();
		$day = $date[wday];
		//echo "day:".$day."\n";
		if($day === "0" || $day==="6") {
			return 1;
		} else { 
			return 0;
		}
	}

	/* 在0-8点返回0，在8-24点返回1 */
	function when_the_hour()
	{
		$date = getdate();
		$hours = $date[hours];
		//echo $hours."\n";
		if(0<$hours && $hours<8)
		{
			return 0;
		}
		else 
		{
			return 1;
		}
	}
