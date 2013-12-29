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

	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include( 'config.php' );
	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => '/home/zhengxie/addscoreredis/addscore.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init( $arrLogConfig, 'ui' );
    
    /* 初始化redis,方便每小时检验渠道是否更新 */
    $redis = new Redis();
    $redis -> connect( '127.0.0.1', 6382 );
	$redis -> select(7);

    /* 初始化redis,方便每小时检验渠道是否更新 */
    $redis2 = new Redis();
    $redis2 -> connect( '10.10.0.29', 6382 );
	$redis2 -> select(3);


	/* 获取score键所对应的的hashmap中所有的键 */
	$keys_array = $redis->hkeys('score');
	foreach($keys_array as $key)
	{
		$value = $redis -> hget("score", "$key");
echo $value . "\n";
		$redis2 -> hset("score", "$key", "$value" );
	}
?>
