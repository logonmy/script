<?php
	/*
	  * =====================================================================================
	  *
	  *       Filename:  updateApp.php
	  *
	  *    Description:  每天将校验结果插入数据库，并且每小时检查多酷渠道在百度是否有变更并更
      *                  新数据库。
      *                  每天凌晨三点，删除redis。凌晨六点至二十二点，运行此脚本，六点第一次
      *                  运行时，因为redis没有记录，则结果将全部插入数据库，此后每个小时，
      *                  会更新数据库，若有新增的记录，则会插入数据库。
      *                   
      *          Steps:       
      *                  1.从data.xml文件获取状态为非delete的app_id,app_name等信息。
      *                  2.遍历app_name，抓取百度页面，获取每个渠道的app信息数组。
      *                  3.将每个渠道的信息数组与redis信息数组相对比，凡是渠道有变化
      *                    的，记录到各自的app变化信息数组。
      *                  4.将每个渠道的变化信息数组与redis信息数组相对比，凡是在另外三个
      *                    redis存在的app，认为是update，则更新数据库，并且，删除更新之前的
      *                    redis的记录，插入更新后的redis；凡是在另外三个redis不存在的app，
      *                    认为是insert，则插入数据库，并且，直接插入更新后的reids。
	  *
	  *        Version:  1.0
	  *        Created:  22/04/2013 12:14:23 [A/P]M
	  *       Revision:  none
	  *
	  *         Author:  RD. Zheng,Xie, zhengxie@duoku.com
	  *        Company:  Duoku, Beijing, China
	  *
	  * =====================================================================================
	*/

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

	/* 要搜索的页数 */
	define( 'PAGE', 0);
	/* 要搜索的当前页数的多少条 */
	define( 'TITLE_NUM', 1);
	
	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include( 'config.php' );
	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => '/home/zhengxie/updateapp/updateApp.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init( $arrLogConfig, 'ui' );
    
    /* 初始化redis,方便每小时检验渠道是否更新 */
    $redis = new Redis();
    $redis -> connect( '127.0.0.1', 6379 );

    /* 连接数据库 */
    $con = mysql_connect( "10.10.0.10:4051", "root", "duoku2012" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	/* 加载data.xml文件 */
    $xml = new DOMDocument();
    $file_path = '/home/work/www/bdapp/all/data.xml';
	$file_content = file_get_contents( $file_path );
	$xml -> loadXML( $file_content );
	$apps = array();
	$apps = $xml->getElementsByTagName( "app" );
	Bingo_Log::notice( '加载data.xml文件完毕'  );
  	
  	/* 从data.xml获取的推送给百度的应用名称 */
  	$app_names = array();
  	/* 从data.xml获取的推送给百度的应用id */
  	$app_ids = array();
  	/* 当天日期时间 */
	$now_date = date( "Y-m-d H:i:s" );
  	/* 百度页面不包含的应用 */
  	$no_app_array = array();
  	/* 没有多酷渠道的应用 */
  	$no_duoku_array = array();
  	/* 多酷排在第一位的应用 */
  	$duoku_first_array = array();
  	/* 多酷排在非第一位的应用 */
  	$duoku_no_first_array = array();
    /* 日志记录每个应用ID的渠道情况*/
    $log = "";
  	
  	$app_names_index = 0;
  	$app_ids_index = 0;
  	$no_app_array_index = 0;
	$no_duoku_array_index = 0;
	$duoku_first_array_index = 0;
	$duoku_no_first_array_index = 0;

	/* 主程序区 */	
	Bingo_Log::notice( '主程序开始'  );
    for( $i = 0; $i < $apps -> length; ++$i)
    {
        $app = $apps -> item( $i );
  	    $status = $app -> getElementsByTagName( "status" );
		/* 只查询状态为非delete的app */
		if( $status -> item(0) -> nodeValue != "delete" ){
  		    /* $name = $app -> getElementsByTagName( "title" ) -> item( 0 ) -> nodeValue; */
  		    $name = "霸三国"; 
  		    /* $id = $app -> getElementsByTagName( "appid" ) -> item( 0 ) -> nodeValue; */
  		    $id = 47766; 
            $app_names[ $app_names_index++ ] = $name ;
            $app_ids[ $app_ids_index++ ] = $id ;
        }        
		break;
    }
	
    Bingo_Log::notice( "总共有 " . count( $app_ids ) . "个游戏" );

    $dom = new DomDocument();
	$app_ids_index_tmp = 0;

	/* 逐个抓取百度页面app */
	foreach( $app_names as $app_name )
    {
		$app_id = $app_ids[ $app_ids_index_tmp++ ];

        $query = "select class_id from mcp_content_class_list where c_id = 47766;";
        $result = mysql_query( $query );
        $row = mysql_fetch_array( $result );
        $class_id = $row[0];

        /* 因为百度只展示去掉括号的名字的网游，所以需过滤括号中的字符 */
        if( 108 == $class_id ){
            $command = "echo '$app_name' | sed 's/（[^）]*）//'g";
            $app_name_tmp = exec( $command );
echo $app_name_tmp;
        }else{
			$app_name_tmp = $app_name;
		}
        echo  $app_id . " ";

        /* 是否找到该名称的应用*/
		$title_flag = 0;
		$app_url = "";
		$dom_app = "";
        
        /* 如果名字有空格，则需要转化 eg:打砖块3 Free+ */
        if( preg_match( "/\s/", $app_name_tmp ) ){
            $app_name_tmp = urlencode( $app_name_tmp );
        }
		
		for( $i = 0; $i <= PAGE; $i++){
			$curr_page = 10 * $i;
			$content_url = "http://m.baidu.com/s?word=霸三国&st=10a081&tn=webmkt&pn=$curr_page&rn=10";
			$page_contents = file_get_contents( $content_url );
			$dom -> loadHTML( $page_contents );
			$a_list = $dom -> getElementsByTagName( "a" );
			traverse_a_list( $a_list );
		}
break;
	}

	/* 新增的多酷排在第一位的应用*/
	$duoku_first_changed = array();
	/* 新增的百度页面不包含的应用 */
    $no_app_changed = array();
	/* 新增的没有多酷渠道的应用 */
    $no_duoku_changed = array();
	/* 新增的多酷排在非第一位的应用 */
    $duoku_no_first_changed = array();
   
    /* 获取变动的应用ID*/
    $channel_name_array = array( "duoku_first", "no_app", "no_duoku", "duoku_no_first" );
    $redis_name_array = array( "duoku_first_redis", "no_app_redis", "no_duoku_redis", "duoku_no_first_redis");
	$y_n_array = array(
					  	"$channel_name_array[0]"=>array( "1","1","1" ),
						"$channel_name_array[1]"=>array( "0","0","0" ), 
						"$channel_name_array[2]"=>array( "0","0","1" ), 
						"$channel_name_array[3]"=>array( "0","1","1" ), 
					  );

    /* 检查每个渠道信息数组，将结果记录到渠道信息变化的信息数组 */
    check_changed( $duoku_first_array, $channel_name_array[0] );
    check_changed( $no_app_array, $channel_name_array[1] );
    check_changed( $no_duoku_array, $channel_name_array[2] );
    check_changed( $duoku_no_first_array, $channel_name_array[3] );
    
    /* 更新数据库和redis*/
    update_db_redis( $duoku_first_changed, $redis_name_array[0] ); 
    update_db_redis( $no_app_changed, $redis_name_array[1] ); 
    update_db_redis( $no_duoku_changed, $redis_name_array[2] ); 
    update_db_redis( $duoku_no_first_changed, $redis_name_array[3] ); 
    
    mysql_close($con);

	Bingo_Log::notice( 'duoku_first_array:' . count( $duoku_first_array ) . '条' );
	Bingo_Log::notice( 'no_app_array:' . count( $no_app_array ) . '条' );
	Bingo_Log::notice( 'no_duoku_array:' . count( $no_duoku_array ) . '条' );
	Bingo_Log::notice( 'duoku_no_first_array:' . count( $duoku_no_first_array ) . '条' );
	Bingo_Log::notice( '每个应用的渠道情况:' . $log );
	Bingo_Log::notice( 'duoku_first_changed:' . count( $duoku_first_changed ) . '条' );
	Bingo_Log::notice( 'no_app_changed:' . count( $no_app_changed ) . '条' );
	Bingo_Log::notice( 'no_duoku_changed:' . count( $no_duoku_changed ) . '条' );
	Bingo_Log::notice( 'duoku_no_first_changed:' . count( $duoku_no_first_changed ) . '条' );

	Bingo_Log::notice('成功更新数据库' );
	Bingo_Log::notice('主程序结束' );

	/* 函数区 */

	/* 检查各个渠道数组是否有变动 */
    function check_changed( &$id_name_array, &$channel_name )
	{
        global $channel_name_array;
		global $redis_name_array;

        if( !strcmp( $channel_name, $channel_name_array[0] ) ){
			add_changed( $id_name_array, $redis_name_array[0]); 
		}
		if( !strcmp($channel_name, $channel_name_array[1] ) ) {
			add_changed( $id_name_array, $redis_name_array[1]);
		}
        if( !strcmp( $channel_name, $channel_name_array[2] ) ){
			add_changed( $id_name_array, $redis_name_array[2]);
		}
        if( !strcmp( $channel_name, $channel_name_array[3] ) ){
			add_changed( $id_name_array, $redis_name_array[3]);
        }
    }
	
	/* 将有变动的记录到各自对应的数组里面 */
	function add_changed( &$id_name_array, &$redis_name )
	{
		global $redis;
		global $redis_name_array;
		global $duoku_first_changed;
		global $no_app_changed;
		global $no_duoku_changed;
		global $duoku_no_first_changed;

		$i = 0;
		foreach( $id_name_array as $id_name )
		{
            $tmp_array = explode(",", $id_name );
            $id = $tmp_array[0];
            if($redis->sIsMember( "$redis_name", "$id")){
            	continue;
			}
			if( !strcmp( $redis_name, $redis_name_array[0] ) ){
            	$duoku_first_changed[$i++] = $id_name;
			}
			if( !strcmp( $redis_name, $redis_name_array[1] ) ){
            	$no_app_changed[$i++] = $id_name;
			}
			if( !strcmp( $redis_name, $redis_name_array[2] ) ){
            	$no_duoku_changed[$i++] = $id_name;
			}
			if( !strcmp( $redis_name, $redis_name_array[3] ) ){
            	$duoku_no_first_changed[$i++] = $id_name;
			}
        }
    }

	/* 获取query，决定是update还是insert，如果是update，要删除原来的redis的记录 */
	function get_query( &$compare_redis_array, &$y_n, &$id_name )
	{
		global $redis;
		global $now_date;
		$tmp_array = explode(",", $id_name);
		$id = $tmp_array[0];
		$name = $tmp_array[1];
        $query = "insert into check_baidu_app values('', '$id', '$name', '$y_n[0]', '$y_n[1]', '$y_n[2]', 
				  										 '$now_date', '$now_date');";
		foreach( $compare_redis_array as $compare_redis )
		{	
			if( $redis -> sIsMember( $compare_redis, $id ) )
			{
			    $query = "update check_baidu_app set
                          baidu_duoku_channel_first = '$y_n[0]', 
						  baidu_has_duoku_channel = '$y_n[1]', 
						  baidu_has_app = '$y_n[2]', 
						  update_date = '$now_date'    
                          where app_id = '$id';";
                /* 注意！一定要删除之前的redis记录!!切记!! */
				$redis -> sRem( "$compare_redis", "$id" );
				break;
			}
        }
	    Bingo_Log::notice( '操作数据库语句:' . $query );
		return $query;
	}
	
	/* 循环处理数据库和redis */
	function update_db_redis( &$id_name_changed, &$redis_name )
	{
		global $redis_name_array;
		global $compare_redis;
		global $y_n_array;
		global $redis;
		global $channel_name_array;

		foreach( $id_name_changed as $id_name)
		{
		    $tmp_array = explode(",", $id_name);
		    $id = $tmp_array[0];
		    $name = $tmp_array[1];

			if( !strcmp( $redis_name, $redis_name_array[0] ) ){
				$compare_redis = array( "$redis_name_array[1]", "$redis_name_array[2]", "$redis_name_array[3]" );
				$query = get_query( $compare_redis, $y_n_array[$channel_name_array[0]], $id_name );
            }
			if( !strcmp( $redis_name, $redis_name_array[1] ) ){
				$compare_redis = array( "$redis_name_array[0]", "$redis_name_array[2]", "$redis_name_array[3]" );
				$query = get_query( $compare_redis, $y_n_array[$channel_name_array[1]], $id_name );
			}
			if( !strcmp( $redis_name, $redis_name_array[2] ) ){
				$compare_redis = array( "$redis_name_array[1]", "$redis_name_array[0]", "$redis_name_array[3]" );
				$query = get_query( $compare_redis, $y_n_array[$channel_name_array[2]], $id_name );
			}
			if( !strcmp( $redis_name, $redis_name_array[3] ) ){
				$compare_redis = array( "$redis_name_array[1]", "$redis_name_array[2]", "$redis_name_array[0]" );
				$query = get_query( $compare_redis, $y_n_array[$channel_name_array[3]], $id_name );
			}

		    mysql_query( $query );
			$redis->sAdd( "$redis_name", "$id" );
		}
	}

	function traverse_a_list( &$a_list )
	{
		global $app_id;
		global $app_name;
		global $title_flag;
		global $app_url;
		global $dom_app;
		global $no_app_array;
		global $no_app_array_index;
        global $log;
	
        /* 默认搜索当前页面的两个应用，解决百度搜索结果顺序不一致的问题，eg：捕鱼达人 */
        $j = 0;
        for( $i = 0; $i < $a_list -> length; ++$i )
        {
            $a_item = $a_list -> item( $i );
            $a_class = $a_item -> getAttribute( "class" );
            if( !strcmp( $a_class, "list-a" ) )
            {
                $count_list_a++;
                $app_url = $a_item -> getAttribute( "href" );
                $app_contents = file_get_contents( $app_url );
                $dom_app = new DomDocument();
                $dom_app -> loadHTML( $app_contents );
                $span_list = $dom_app -> getElementsByTagName( "span" );

                traverse_span_list( $span_list );
                                                        
                if( $title_flag ){
                    $a_app_list = $dom_app -> getElementsByTagName( "a" );
                    traverse_a_app_list( $a_app_list );
                    break;
                }else{
                    /* 只搜索当前页面的前两个应用,所以跳出循环 */
                    if(TITLE_NUM == $j ){
                        break;
                    }else{
                        $j++;
                    }
                }   
            }            
		}
        if( !$title_flag ){
            $log .= $app_id . ":no_app ";
            echo ":no_app " . "\n";
            $no_app_array[ $no_app_array_index++ ] = "$app_id" . "," . "$app_name";
	    }
    }
	
	function traverse_span_list( &$span_list )
	{
		global $app_name;
		global $title_flag;

        for( $i = 0; $i < $span_list -> length; ++$i )
        {
            $span_item = $span_list -> item( $i );
			$id_item = $span_item -> getAttribute( "id" );
			if( !strcmp( $id_item, "appname" ) ){
				$title_name = trim( $span_item -> nodeValue );
echo $title_name;
				if( !strcmp( $title_name, $app_name ) ){
					$title_flag = 1;
					break;
				}
			}
		}
	}
	
	function traverse_a_app_list( &$a_app_list )
	{
		global $found_flag;
		global $app_id;
		global $app_name;
		global $duoku_first_array;
		global $duoku_no_first_array;
		global $no_duoku_array;
        global $log;

		/* 多酷渠道在第几位 */	
		$j = 0;
		/* 是否有多酷渠道 */
		$found_flag = false;
				
		global $no_duoku_array_index;
		global $duoku_first_array_index;
		global $duoku_no_first_array_index;
		
        for( $i = 0; $i < $a_app_list -> length; ++$i)
		{
            $a_app_item = $a_app_list -> item( $i );
			$data_key = $a_app_item -> getAttribute( "data-key" );
			if( strcmp( $data_key, NULL ) ){
				$channel_name = trim( $a_app_item -> nodeValue );
				if( ( 0 == $j ) && !strcmp( $channel_name, "百度多酷" ) ){
                    $log .= $app_id . ":first ";
                    echo ":first " ."\n";
					$duoku_first_array[$duoku_first_array_index++] = "$app_id" . "," . "$app_name";
					$found_flag = true;				
					break;
				}else if( !strcmp( $channel_name, "百度多酷" ) ){
                    $log .=  $app_id . ":no_first ";
                    echo ":no_first " . "\n";
					$duoku_no_first_array[ $duoku_no_first_array_index++ ] = "$app_id" . "," . "$app_name";
					$found_flag = true;				
					break;
				}else{
					$j++;				
				}
			}
		}
		if( !$found_flag ){
            $log .= $app_id . ":no_duoku ";
            echo ":no_duoku " . "\n";
			$no_duoku_array[$no_duoku_array_index++] = "$app_id" . "," . "$app_name";
		}
	}
?>
