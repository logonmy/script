<?php
	/*
	* =====================================================================================
	*
	*       Filename:  add_grab_num_redis.php
	*
	*    Description:  将抢号的发号入redis，避免因为mysql读库多线程导致的同时读一个发号的问题。
	*
	*        Version:  1.0
	*        Created:  10/22/2013 12:14:23 [A/P]M
	*       Revision:  none
	*
	*        Author:  RD. Zheng,Xie, zhengxie@duoku.com
	*        Company:  Duoku, Beijing, China
	*
	* =====================================================================================
	*/
	
	set_time_limit(0);
	require_once 'Excel/reader.php';

	$file_path = $argv[1];
	$data = new Spreadsheet_Excel_Reader();
	$data->setOutputEncoding('CP1251');

	$data->setColumnFormat(2, '%d');
	$data->read("$file_path");
    echo $data->sheets[0]['numRows']."\n";

	$redis = new Redis();
    $redis -> connect( '10.10.0.141', 6382 );
    $redis -> select( 3 );


	error_reporting(E_ALL ^ E_NOTICE);

	for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) 
	{
		$grab_id = $data->sheets[0]['cells'][$i][1];
		$grab_num = $data->sheets[0]['cells'][$i][2];
echo $grab_id . ":".$grab_num."\n";
		$key = "grab_id:".$grab_id;
		$redis->sAdd("$key", "$grab_num");
	}

?>
