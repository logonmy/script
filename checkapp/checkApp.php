<?php
	/*
	  * =====================================================================================
	  *
	  *       Filename:  checkApp.php
	  *
	  *    Description:  多酷渠道在百度显示情况列表
	  *
	  *        Version:  1.0
	  *        Created:  02/04/2013 12:14:23 [A/P]M
	  *       Revision:  none
	  *
	  *         Author:  RD. Zheng,Xie, zhengxie@duoku.com
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

	/* 初始化并删除redis,方便每小时检验渠道是否更新 */	
	$redis = new Redis();
    $redis->connect('127.0.0.1',6379);
	$redis->delete('duoku_first_redis');
	$redis->delete('no_app_redis');
	$redis->delete('no_duoku_redis');
	$redis->delete('duoku_no_first_redis');

	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include('config.php');
	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => '/home/zhengxie/checkapp/checkApp.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init($arrLogConfig, 'ui');
	
	/* 加载data.xml文件 */
    $xml = new DOMDocument();
    $file_path = '/home/work/www/bdapp/all/data.xml';
	$file_content = file_get_contents($file_path);
	$xml -> loadXML($file_content);
	$apps = array();
	$apps = $xml->getElementsByTagName("app");
	Bingo_Log::notice(__LINE__ .'  '.'加载data.xml文件完毕'  );
  	
	/* 从data.xml获取的推送给百度的应用名称 */
  	$app_names = array();
  	/* 从data.xml获取的推送给百度的应用id */
  	$app_ids = array();
  	/* 当天日期 */
	$now_date = date("Y-m-d");
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
	Bingo_Log::notice(__LINE__ .'  '.'主程序开始'  );
 	foreach ($apps as $app){
  	    $status = $app->getElementsByTagName("status");
		if($status->item(0)->nodeValue != "delete"){
  		    $name = $app->getElementsByTagName("title");
  		    $id = $app->getElementsByTagName("appid");
            $app_names[$app_names_index++] = $name->item(0)->nodeValue;
            $app_ids[$app_ids_index++] = $id->item(0)->nodeValue;
  		}
  	}
	
	$app_ids_index_tmp = 0;
	foreach($app_names as $app_name){
		/* 是否找到该名称的应用*/
		$title_flag = false;
		$app_url = "";
		$dom_app = "";
		$app_id = $app_ids[$app_ids_index_tmp++];
        
		/* 只搜索第一页 */
		$page = 0;
		$content_url = "http://m.baidu.com/s?word=$app_name&st=10a081&tn=webmkt&pn=$page&rn=10";
		$page_contents = file_get_contents($content_url);
		$dom = new DomDocument();
		$dom -> loadHTML($page_contents);
		$a_list = $dom -> getElementsByTagName("a");
			
		traverse_a_list($a_list);
	}
	Bingo_Log::notice(__LINE__ .'  '.'主程序结束' );

	/* 读写文件 */
	Bingo_Log::notice(__LINE__ .'  '.'写文件开始'  );
	$file_name = "/home/zhengxie/checkapp/checkApp.csv";
	$handle = fopen($file_name,'w'); 
	
	Bingo_Log::notice('duoku_first_array:' .count($duoku_first_array).'条记录'  );
	foreach($duoku_first_array as $duoku_first){
        $duoku_first_tmp="'',".$duoku_first.","."1".","."1".","."1".","."$now_date"."\n";
		$duoku_first_id=explode(",", $duoku_first);
   		$redis->sAdd('duoku_first_redis' , $duoku_first_id[0]);
        fwrite($handle,$duoku_first_tmp);
    }
	
	Bingo_Log::notice('no_app_array:' .count($no_app_array).'条记录'  );
    foreach($no_app_array as $no_app){
        $no_app_tmp="'',".$no_app.","."0".","."0".","."0".","."$now_date"."\n";
		$no_app_id=explode(",", $no_app);
   		$redis->sAdd('no_app_redis' , $no_app_id[0]);
        fwrite($handle,$no_app_tmp);
    }
	
	Bingo_Log::notice('no_duoku_array:' .count($no_duoku_array).'条记录'  );
    foreach($no_duoku_array as $no_duoku){
        $no_duoku_tmp="'',".$no_duoku.","."0".","."0".","."1".","."$now_date"."\n";
		$no_duoku_id=explode(",",$no_duoku);
   		$redis->sAdd('no_duoku_redis' , $no_duoku_id[0]);
        fwrite($handle,$no_duoku_tmp);
    }
	
	Bingo_Log::notice('duoku_no_first_array:' .count($duoku_no_first_array).'条记录'  );
    foreach($duoku_no_first_array as $duoku_no_first){
        $duoku_no_first_tmp="'',".$duoku_no_first.","."0".","."1".","."1".","."$now_date"."\n";
		$duoku_no_first_id=explode(",",$duoku_no_first);
   		$redis->sAdd('duoku_no_first_redis' , $duoku_no_first_id[0]);
        fwrite($handle,$duoku_no_first_tmp);
    }
	
	fclose($handle);
	Bingo_Log::notice(__LINE__ .'  '.'写文件结束'  );
	
	/* 函数区 */
	function traverse_a_list($a_list)
	{
		global $app_id;
		global $app_name;
		global $title_flag;
		global $app_url;
		global $dom_app;
		global $no_app_array;
		global $no_app_array_index;
		
		foreach($a_list as $a_item){
			$a_class = $a_item -> getAttribute("class");
			if( !strcmp($a_class,"list-a") ){
				$app_url = $a_item -> getAttribute("href");
				$app_contents = file_get_contents($app_url);
				$dom_app = new DomDocument();
				$dom_app -> loadHTML($app_contents);
				$span_list = $dom_app -> getElementsByTagName("span");
			
				traverse_span_list($span_list);
				
				if($title_flag){
					$a_app_list = $dom_app -> getElementsByTagName("a");
					traverse_a_app_list($a_app_list);
				}else{
					$no_app_array[$no_app_array_index++] = "$app_id" . "," . "$app_name";
				}
                /* 只搜索当前页面的第一个应用,所以跳出循环 */
				break;
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
		
		foreach($a_app_list as $a_app_item){
			$data_key = $a_app_item -> getAttribute("data-key");
			if(strcmp($data_key,NULL)){
				$channel_name = trim($a_app_item->nodeValue);
				if( (0 == $i) && !strcmp($channel_name,"百度多酷") ){
					$duoku_first_array[$duoku_first_array_index++] = "$app_id" . "," . "$app_name";
					$found_flag = true;				
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
