<?php
	/* 初始化LOG配置 */
	include_once 'Bingo/Log.php';
	include('config.php');
	$arrLogConfig = array(
		'ui' => array(
			'file' => '/home/zhengxie/modify_image/modify_url.log',
			'level' => 0xFF,

		),

	);
	Bingo_Log::init($arrLogConfig, 'ui');

	/* 连接数据库 */
	$con = mysql_connect( "10.10.0.10:4051", "root", "duoku2012" );
	//$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	if ( !$con )
	{
		die( 'Could not connect: ' . mysql_error() );
	}

	$db_selected = mysql_select_db( "MCP", $con );
	mysql_query( "set names utf8" );

	$query ="SELECT url FROM `mcp_content_url_ext` WHERE `url` LIKE '%http://wap.cmread.com/iread/wml/l/readbook.jsp?bid%';";

	$result = mysql_query( $query, $con ) or die( __LINE__ . "Invalid query: " . mysql_error());
	while( $row=mysql_fetch_row($result) )
	{
		$url_before = $row[0];
		echo $url_before."\n";

        $b_id_cmd = "echo \"".$url_before."\" |sed 's/.*bid=\([^&]*\)&cid=\([^&]*\)&nid=\([^&]*\)&cm=\([^&]*\)&fr=\([^&]*\).*/\\1/'";
        $c_id_cmd = "echo \"".$url_before."\" |sed 's/.*bid=\([^&]*\)&cid=\([^&]*\)&nid=\([^&]*\)&cm=\([^&]*\)&fr=\([^&]*\).*/\\2/'";
        $n_id_cmd = "echo \"".$url_before."\" |sed 's/.*bid=\([^&]*\)&cid=\([^&]*\)&nid=\([^&]*\)&cm=\([^&]*\)&fr=\([^&]*\).*/\\3/'";
        $cm_cmd = "echo \"".$url_before."\" |sed 's/.*bid=\([^&]*\)&cid=\([^&]*\)&nid=\([^&]*\)&cm=\([^&]*\)&fr=\([^&]*\).*/\\4/'";
		$fr_cmd = "echo \"".$url_before."\" |sed 's/.*bid=\([^&]*\)&cid=\([^&]*\)&nid=\([^&]*\)&cm=\([^&]*\)&fr=\([^&]*\).*/\\5/'";

		$b_id = exec($b_id_cmd);
		$c_id = exec($c_id_cmd);
		$n_id = exec($n_id_cmd);
		$cm = exec($cm_cmd);
		$fr = exec($fr_cmd);

		$url_after = "http://wap.cmread.com/r/"."$b_id/"."$c_id"."/index.htm?cm="."$cm&"."fr=$fr";
		echo $url_after."\n";

		$query2 = "update mcp_content_url_ext set url=\"$url_after\" where url=\"$url_before\"";
		mysql_query( $query2, $con ) or die( __LINE__ . "Invalid query: " . mysql_error());
		Bingo_Log::notice($query2);
	}

?>

