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

	$time_type = when_the_hour();

	$total_num = 0;
	//$query = "select c_id, lowest_num, highest_num from mcp_content_qp_activity_person where enable=1 and \"$nowtime\">start_time and \"$nowtime\"<end_time;"; 
	$query = "select c_id, lowest_num, highest_num from mcp_content_qp_activity_person_increment where time_type = $time_type;"; 
	$result = mysql_query( $query );
	while( $row=mysql_fetch_row($result) )
	{
		$c_id = $row[0];
		echo $join_num."\n";
		$lowest_num = $row[1];
		$highest_num = $row[2];
		$join_num_new = rand($lowest_num, $highest_num);
		Bingo_Log::notice("c_id[$c_id], lowest_num[$lowest_num], highest_num[$highest_num], join_num_new[$join_num_new]");
		//$rate *= get_rate();
		//echo $rate."\n";
		//$join_num_new = $lowest_num + $incr;
		$query2 = "update mcp_content_qp_activity_person set num=$join_num_new where c_id=$c_id;"; 
		$result2 = mysql_query( $query2 );
		if( mysql_affected_rows() == 0 ) 
		{
			$query3 = "insert into mcp_content_qp_activity_person values(\"\", \"$c_id\", \"$join_num_new\");";
			$result3 = mysql_query( $query3 );
			Bingo_Log::notice("$query3");
		}
		else{
			Bingo_Log::notice("$query2");
		}
		$total_num += $join_num_new;
	}
	
	$query4 = "update mcp_content_qp_activity_person set num=$total_num where c_id=0;"; 
	$result4 = mysql_query( $query4 );
	Bingo_Log::notice("$query4");

	/*function get_rate()*/
	//{
		//$i = weekday_or_weekend();
		//$j = when_the_hour();
		//if($i === 1 && $j === 1)
			//return 1.3*1.0;
		//if($i === 1 && $j === 0)
			//return 1.3*0.5;
		//if($i === 0 && $j === 1)
			//return 1.0;
		//if($i === 0 && $j === 0)
			//return 1.0*0.5;
	//}

	//[> weekday返回0，weekend返回1 <]
	//function weekday_or_weekend()
	//{
		//$date = getdate();
		//$day = $date[wday];
		////echo "day:".$day."\n";
		//if($day === "0" || $day==="6") {
			//return 1;
		//} else { 
			//return 0;
		//}
	/*}*/

	/* 在[1-6)点返回0，在[6-12)点返回1,在[12-14)点返回2,在[14-19)点返回3,在[19-1)点返回4 */
	function when_the_hour()
	{
		$date = getdate();
		$hours = $date[hours];
		//echo $hours."\n";
		if(0<$hours && $hours<6) {
			return 0;
		}
		else if(5<$hours && $hours<12) {
			return 1;
		}
		else if(11<$hours && $hours<14) {
			return 2;
		}
		else if(13<$hours && $hours<19) {
			return 3;
		}
		else if(18<$hours && $hours<24) {
			return 4;
		}
		else if(0 == $hours) {
			return 4;
		}
	}
