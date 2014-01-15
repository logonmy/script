<?php

	/* 初始化LOG配置 */
	include_once '/home/work/webserver/php/lib/php/Bingo/Log.php';
	include('/home/work/webserver/php/lib/php/Bingo/Config.php');

	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => './add_award_redis.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init($arrLogConfig, 'ui');

	$redis = new Redis();
    $redis->connect('10.10.0.141',6382);
	$redis -> select(3);

	$key = "award_probability";
	$redis->delete($key);

	$key3 = "award_prize";
	$redis->delete($key3);

	$key4 = "award_list";
	$redis->delete($key4);

	/* 连接数据库 */
	$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	//$con = mysql_connect( "10.10.0.9:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$nowtime = date("Y-m-d G:i:s");

	//$query = "select id, probability from props_commodity where enable=1 and \"$nowtime\">start_time and \"$nowtime\"<end_time;";
	$query = "select id, probability, type, begin_time, end_time, store_num, sell_num, name, auto_expired_type, is_slyder, func_desc, icon from props_commodity where enable = 1 order by probability asc;";
	$result = mysql_query( $query );
	$probability_begin = 0;
	$probability_end = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$id = $row[0];
		$probability = $row[1];

		$type = $row[2];
		$begin_time = $row[3];
		$end_time = $row[4];
		$store_num = $row[5];
		$sell_num = $row[6];
		if( !isset($sell_num) )
		{
			$sell_num = 0;
		}
		$name = $row[7];
		$auto_expired_type = $row[8];
		$is_slyder = $row[9];
		$func_desc = $row[10];
		$icon = $row[11];

		$pic_name = "";
		$pic_url = "";

		//如果是图片或者音乐，还需要把图片的信息放到redis set 里
		if($type == 4)
		{
			//$key3 = "award_prize";
			//$redis->delete($key3);

			$query3 = "select commodity_id, name, icon, pic_url from props_pic where commodity_id = $id and enable=1";
			$result3 = mysql_query( $query3 );
			while( $row3 = mysql_fetch_row( $result3 ) )
			{
				$award_id = $row3[0];
				$prize_name = $row3[1];
				$icon = $row3[2];
				$prize_url = $row3[3];

				$value = "$award_id,$type,$begin_time,$end_time,$store_num,$sell_num,$name,$prize_name,$icon,$prize_url,$auto_expired_type,$is_slyder";
				$redis->sAdd("$key3", "$value");
				Bingo_Log::notice("set value to [$key3] value:[$value]\n");
			}

			//$exist = $redis->exists($key3);
			//echo $exist."\n";
			/*if( true == $exist ) {*/
				//continue;
			/*}*/
		}


		//获取概率入redis list
		//$probability_begin = $probability_begin + $probability;
		if( 0 != $probability ){
			$probability_begin = $probability_end + 1;
			$probability_end = $probability_begin + $probability - 1;
			//echo "probability_begin:$probability_begin\n";
		}
		$value = "$id,$probability_begin,$probability_end";
		Bingo_Log::notice("id:[$id], probability:[$probability], probability_begin:[$probability_begin], probability_end:[$probability_end]");

		//echo "id:".$id."\nprobability:".$probability."\n";
		if( $probability != 0 )
		{
			$redis->rPush($key, $value);
			Bingo_Log::notice("push value to [$key] value:[$value]\n");
		}

		//获取奖品信息入redis hmset
		$key2 = "award_info_$id";
		/*$exist = $redis->exists($key2);*/
		//if( true == $exist ) {
			//continue;
		/*}*/
		$redis->delete($key2);
		$redis->hMset($key2, array('id'=>$id, 'type'=>$type, 'begin_time'=>$begin_time, 'end_time'=>$end_time, 'store_num'=>$store_num,
			'sell_num'=>$sell_num, 'name'=>$name, 'prize_name'=>$pic_name, 'icon'=>$icon, 'prize_url'=>$pic_url, 'auto_expired_type'=>$auto_expired_type,
			'is_slyder'=>$is_slyder, 'func_desc'=>$func_desc));

		Bingo_Log::notice("hmset value to [$key2] value:{type:[$type], name:[$name], begin_time:[$begin_time], end_time:[$end_time],
			store_num:[$store_num], sell_num:[$sell_num], prize_name:[$pic_name], icon:[$icon], prize_url[$pic_url], auto_expired_type[$auto_expired_type],
			is_slyder[$is_slyder], func_desc[$func_desc]}\n");
		//name是大的分类，比如图片，prize_name是具体的音乐或图片的名称

		// 将奖品id放到redis list
		//$key4 = "award_list";
		//$redis->delete($key4);
		$redis->sAdd( $key4, $key2 );
		Bingo_Log::notice("push value to [$key4] value:[$key2]");

		//如果是兑奖码，还需要把兑奖号码放到redis set里
		if($type == 1)
		{
			$key5 = "award_num_$id";
			$exist = $redis->exists($key5);
			//echo $exist."\n";
			if( true == $exist ) {
				continue;
			}

			$query2 = "select number from props_exchange_code where commodity_id=$id and type=0;";
			$result2 = mysql_query( $query2 );
			//Bingo_Log::notice("$query2");
			while( $row2=mysql_fetch_row($result2) )
			{
				$num = $row2[0];
				$redis->sAdd("$key5", "$num");
				Bingo_Log::notice("set value to [$key5] num:[$num]\n");
			}
		}

	}

?>
