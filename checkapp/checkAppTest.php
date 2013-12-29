<?php
	/*
	  * =====================================================================================
	  *
	  *       Filename:  checkApp.php
	  *
	  *    Description:  每天只执行一次，检查多酷渠道在百度显示情况列表,并将当天的结果入库，
      *                  考虑到每小时还要检查多酷渠道是否有变更,所以将app_id先存放在redis里面，
	  *					 方便updateApp.php获取数据。
	  *
	  *        Version:  2.0
	  *        Created:  02/04/2013 12:14:23 [A/P]M
	  *       Revision:  第一次修改
	  *
	  *         Author:  RD. Zheng,Xie, zhengxie@duoku.com
	  *        Company:  Duoku, Beijing, China
	  *
	  * =====================================================================================
	*/

	header( 'Content-Type: text/html; charset=utf-8' );
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
	$context = stream_context_create( $opts );

	/* 初始化并删除redis,方便每小时检验渠道是否更新 */	
	$redis = new Redis();
    $redis->connect( '127.0.0.1', 6379 );
	$redis->delete( 'duoku_first_redis' );
	$redis->delete( 'no_app_redis' );
	$redis->delete( 'no_duoku_redis' );
	$redis->delete( 'duoku_no_first_redis' );

	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include( 'config.php' );
	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => '/home/zhengxie/checkapp/checkAppTest.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init( $arrLogConfig, 'ui' );

    /* 连接数据库,获得app_id的type，若是108网游，则要过滤app_name的特殊字符 */
    $con = mysql_connect( "10.10.0.149:3306","root","root" );
    if (!$con){
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
	Bingo_Log::notice( '加载data.xml文件完毕' );
	/* 从data.xml获取的推送给百度的应用名称 */
  	$app_names = array();
  	/* 从data.xml获取的推送给百度的应用id */
  	$app_ids = array();
  	/* 当天日期 */
	$now_date = date("Y-m-d H:i:s");
  	/* 百度页面不包含的应用 */
  	$no_app_array = array();
  	/* 没有多酷渠道的应用 */
  	$no_duoku_array = array();
  	/* 多酷排在第一位的应用 */
  	$duoku_first_array = array();
  	/* 多酷排在非第一位的应用 */
  	$duoku_no_first_array = array();
  	
  	$app_names_index = 0;
  	$app_ids_index = 0;
  	$no_app_array_index = 0;
	$no_duoku_array_index = 0;
	$duoku_first_array_index = 0;
	$duoku_no_first_array_index = 0;

	/* 主程序区 */	
	Bingo_Log::notice('主程序开始'  );
 	foreach ($apps as $app)
    {
        $status = $app->getElementsByTagName("status");
		if($status->item(0)->nodeValue != "delete"){
  		    $name = $app->getElementsByTagName("title");
  		    $id = $app->getElementsByTagName("appid");
            $app_names[$app_names_index++] = $name->item(0)->nodeValue;
            $app_ids[$app_ids_index++] = $id->item(0)->nodeValue;
  		}
  	}
	
	$app_ids_index_tmp = 0;
	foreach($app_names as $app_name)
    {
		$app_id = $app_ids[$app_ids_index_tmp++];
        /* 因为百度只展示去掉括号的名字的网游，所以需过滤名字的特殊字符 */
        $query = "select class_id from mcp_content_class_list where c_id = $app_id;";
        $result = mysql_query($query);
        $row = mysql_fetch_array($result);
        $class_id = $row[0];
        if(108 == $class_id){
            $command = "echo '$app_name' | sed 's/（[^）]*）//'g";
            $app_name_tmp = exec($command);
        }else{
			$app_name_tmp = $app_name;
		}
		/* 是否找到该名称的应用*/
		$title_flag = false;
		$app_url = "";
		$dom_app = "";
        
		/* 只搜索第一页 */
		$page = 0;
		$content_url = "http://m.baidu.com/s?word=$app_name_tmp&st=10a081&tn=webmkt&pn=$page&rn=10";
		$page_contents = file_get_contents($content_url);
		$dom = new DomDocument();
		$dom -> loadHTML($page_contents);
		$a_list = $dom -> getElementsByTagName("a");
			
		traverse_a_list($a_list);
	}

	/* 插入数据库 */	
	$check_array_name = array("duoku_first","no_app","no_duoku","duoku_no_first");
	Bingo_Log::notice('插入数据库、更新redis开始'  );
	Bingo_Log::notice('duoku_first_array:' .count($duoku_first_array).'条记录'  );
	insert_db_redis($duoku_first_array, $check_array_name[0]);	
	Bingo_Log::notice('no_app_array:' .count($no_app_array).'条记录'  );
	insert_db_redis($no_app_array, $check_array_name[1]);	
	Bingo_Log::notice('no_duoku_array:' .count($no_duoku_array).'条记录'  );
	insert_db_redis($no_duoku_array, $check_array_name[2]);	
	Bingo_Log::notice('duoku_no_first_array:' .count($duoku_no_first_array).'条记录'  );
	insert_db_redis($duoku_no_first_array, $check_array_name[3]);	
	
	mysql_close($con);
	Bingo_Log::notice('成功插入数据库，成功更新redis'  );
	Bingo_Log::notice('主程序结束' );
	
	/* 函数区 */
	function insert_db_redis(&$id_name_array, &$flag)
	{
        global $redis;
        global $now_date;
    	foreach($id_name_array as $id_name)
        {
			$tmp=explode(",", $id_name);
			if(!strcmp($flag, "duoku_first")){
   				$redis->sAdd('duoku_first_redis' , $tmp[0]);
				$query = "insert into check_baidu_app values('', '$tmp[0]', '$tmp[1]', '1', '1', '1', '$now_date', '');";
			}else if(!strcmp($flag,"no_app")){
   				$redis->sAdd('no_app_redis' , $tmp[0]);
				$query = "insert into check_baidu_app values('', '$tmp[0]', '$tmp[1]', '0', '0', '0', '$now_date', '');";
			}else if(!strcmp($flag,"no_duoku")){
   				$redis->sAdd('no_duoku_redis' , $tmp[0]);
				$query = "insert into check_baidu_app values('', '$tmp[0]', '$tmp[1]', '0', '0', '1', '$now_date', '');";
			}else if(!strcmp($flag,"duoku_no_first")){
   				$redis->sAdd('duoku_no_first_redis' , $tmp[0]);
				$query = "insert into check_baidu_app values('', '$tmp[0]', '$tmp[1]', '0', '1', '1', '$now_date', '');";
			}
       		mysql_query("$query");
   		}
	}	

	function traverse_a_list($a_list)
	{
		global $app_id;
		global $app_name;
		global $title_flag;
		global $app_url;
		global $dom_app;
		global $no_app_array;
		global $no_app_array_index;
	
        /* 默认搜索当前页面的两个应用，解决百度搜索结果顺序不一致的问题，eg：捕鱼达人 */
		$i = 0;
        foreach($a_list as $a_item)
        {
		    $a_class = $a_item->getAttribute("class");
		    if( !strcmp($a_class,"list-a") )
            {
			    $app_url = $a_item -> getAttribute("href");
			    $app_contents = file_get_contents($app_url);
			    $dom_app = new DomDocument();
			    $dom_app -> loadHTML($app_contents);
			    $span_list = $dom_app -> getElementsByTagName("span");
		
			    traverse_span_list($span_list);
			
			    if($title_flag){
				    $a_app_list = $dom_app -> getElementsByTagName("a");
				    traverse_a_app_list($a_app_list);
                       break;
			    }else{
                    if(1 == $i ){
			            $no_app_array[$no_app_array_index++] = "$app_id" . "," . "$app_name";
		                break;
                    }else{
						$i++;
					}
                }
		    }
        }
    }
			
	
	function traverse_span_list($span_list)
	{
		global $app_name;
		global $title_flag;

		foreach($span_list as $span_item){
			$id_item = $span_item ->getAttribute("id");
			if(!strcmp($id_item, "appname")){
				$title_name = trim($span_item->nodeValue);
				if( !strcmp($title_name,$app_name) ){
					$title_flag = true;
					break;
				}
			}
		}
	}
	
	function traverse_a_app_list($a_app_list)
	{
		global $found_flag;
		global $app_id;
		global $app_name;
		global $duoku_first_array;
		global $duoku_no_first_array;
		global $no_duoku_array;

		/* 多酷渠道在第几位 */	
		$i = 0;
		/* 是否有多酷渠道 */
		$found_flag = false;
				
		global $no_duoku_array_index;
		global $duoku_first_array_index;
		global $duoku_no_first_array_index;
		
		foreach($a_app_list as $a_app_item)
        {
			$data_key = $a_app_item -> getAttribute("data-key");
			if(strcmp($data_key,NULL)){
				$channel_name = trim($a_app_item->nodeValue);
				if( (0 == $i) && !strcmp($channel_name,"百度多酷") ){
					$duoku_first_array[$duoku_first_array_index++] = "$app_id" . "," . "$app_name";
					$found_flag = true;				
echo $app_id;
					break;
				}else if( !strcmp($channel_name,"百度多酷") ){
					$duoku_no_first_array[$duoku_no_first_array_index++] = "$app_id" . "," . "$app_name";
					$found_flag = true;				
					break;
				}else{
					$i++;				
				}
			}
		}
		if(!$found_flag){
			$no_duoku_array[$no_duoku_array_index++] = "$app_id" . "," . "$app_name";
		}
	}
?> 
