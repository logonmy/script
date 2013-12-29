<?php
	/*
	  * =====================================================================================
	  *
	  *       Filename:  updateApkUrl.php
	  *
	  *    Description:  在将应用推送给百度后，百度会根据多酷的下载地址生成百度方的地址，为了
	  *    				 与百度合作（百度为多酷推广，多酷为百度带去流量），此脚本会将百度
      *                  的下载地址入库。
      *                   
      *          Steps:       
      *                  1.从data.xml文件获取状态为非delete的app_id,app_name等信息。
      *                  2.遍历app_name，发送请求给百度，获取xml文件。
      *                  3.获取百度方下载地址。
      *                  4.更新数据库
	  *
	  *        Version:  1.0
	  *        Created:  09/05/2013 12:14:23 [A/P]M
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
	
	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include( 'config.php' );
	$arrLogConfig = array(
                           'ui' => array(
                                        'file' => '/home/zhengxie/updateapkurl/updateApkUrl.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init( $arrLogConfig, 'ui' );

    /* 连接数据库 */
    $con = mysql_connect( "10.10.0.149:3306", "root", "root" );
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
  	
  	/* 从data.xml获取的非delete状态的应用 */
  	$app_infos = array();
  	/* 从data.xml获取的非delete状态的应用id */
  	$app_ids = array();
	/* 下载地址已更新的app信息数组 */
	$updated_app_infos = array();
    /* 日志记录每个应用ID的渠道情况*/
    $log = "";
  	
  	$app_ids_index = 0;

	/* app信息二维数组列名，app名 */
	define( 'NAME', 0);
	/* app信息二维数组列名，link名 */
	define( 'LINK', 1);
	/* app信息二维数组列名，version名 */
	define( 'VERSION', 2);
	/* mcp_duoku_to_baidu_url需要更新的data_id,to_baidu_url */
	define( 'DATAID', 0);
	define( 'URL', 1);

	/* 主程序区 */	
	Bingo_Log::notice( '主程序开始'  );
	$app_ids_index = 0;
    for( $i = 0; $i < $apps -> length; ++$i)
    {
        $app = $apps -> item( $i );
  	    $status = $app -> getElementsByTagName( "status" );
		/* 只查询状态为非delete的app */
		if( $status -> item(0) -> nodeValue != "delete" ){
  		    $name = $app -> getElementsByTagName( "title" ) -> item( 0 ) -> nodeValue;
  		    $id = $app -> getElementsByTagName( "appid" ) -> item( 0 ) -> nodeValue;
			$tmp[NAME] = $name;
			$tmp[LINK] = $packagelink;
			$tmp[VERSION] = $version;
            $app_infos[ $id ] = $tmp ;
			$app_ids[$app_ids_index++] = $id;
        }        
    }
	
    Bingo_Log::notice( "总共有 " . count( $app_ids ) . "个游戏" );

    $dom = new DomDocument();
	/* 逐个发送百度检索地址 */
	$index = 0;
	/* $b = 0; */
	foreach( $app_ids as $app_id )
    {
		$row = array();
		$query = "select MCD.id, MCA.package_name, MCA.version_code from mcp_content_data as MCD 
				  inner join mcp_content_appinfo as MCA 
				  on (MCA.c_id = MCD.c_id) where MCD.c_id = $app_id;";
        $result = mysql_query( $query ) or die("Invalid query: " . mysql_error()) ;
        $row = mysql_fetch_array( $result );
        $package_name = $row[1];
        $version_code = $row[2];
		$content_url = "http://m.baidu.com/api?from=1001195s&token=duokugame&type=app&package=$package_name&versioncode=$version_code&action=search";
		$page_contents = file_get_contents( $content_url );
		$dom -> loadXML( $page_contents );
		$url = $dom -> getElementsByTagName( "download_url" ) -> item(0) -> nodeValue;
		$tmp = array();
		$tmp[DATAID] = $row[0];
		$tmp[URL] = $url;
		$updated_app_infos[$index++] = $tmp;
echo count($updated_app_infos) . "个\n";
echo $url . "\n";
	}
    
	/* 更新数据库 */
	foreach( $updated_app_infos as $updated_app_info )
    {
		$cdata_id = $updated_app_info[DATAID];
		$url = $updated_app_info[URL];
		if(empty($cdata_id)){
			continue;
		}
		$query = "select cdata_id, to_baidu_url from mcp_duoku_to_baidu_url where cdata_id = $cdata_id;";
		$result = mysql_query( $query ) or die("Invalid query: " . mysql_error());
		$num_rows = mysql_num_rows( $result );
		$row = mysql_fetch_array( $result );
		if($num_rows == 0)
		{
			if(!empty($url))
			{
        		$query = "insert into mcp_duoku_to_baidu_url values('', $cdata_id, \"$url\" );"; 
			}else{
        		$query = "insert into mcp_duoku_to_baidu_url values('', $cdata_id, '' );"; 
			}
		}else{ 
			if( $row[1] !== $url){
				$query = "update mcp_duoku_to_baidu_url set to_baidu_url = \"$url\" where cdata_id = $cdata_id";
			}else{
				continue;
			}
		}
echo __LINE__;
echo $query . "\n";
		$result = mysql_query( $query ) or die("Invalid query: " . mysql_error());
	}

    mysql_close($con);

	Bingo_Log::notice('成功更新数据库' );
	Bingo_Log::notice('主程序结束' );

?>
