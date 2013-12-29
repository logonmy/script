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
	
	//require_once 'Excel/reader.php';
	//require 'lib/PHPExcel.php';
require 'lib/PHPExcel.php'; 

$input_file = $argv[1]; 
$objPHPExcel = PHPExcel_IOFactory::load($input_file); 

//读取规则 
$sheet_read_arr = array(); 
$sheet_read_arr["sheet1"] = array("A","B","C","D","F"); 
//$sheet_read_arr["sheet2"] = array("A","B","C","D","F"); 

// 循环所有的页 
foreach ($sheet_read_arr as $key => $val) 
{ 
	$currentSheet = $objPHPExcel->getSheetByName($key);// 通过页名称取得当前页 
	$row_num = $currentSheet->getHighestRow();// 当前页行数 
	echo $row_num."\n";

	// 循环从第二行开始，第一行往往是表头 
	for ($i = 1; $i <= $row_num; $i++) 
	{ 
		$cell_values = array(); 
		foreach ($val as $cell_val) 
		{ 
			$address = $cell_val . $i;// 单元格坐标 

			// 读取单元格内容 
			$cell_values = $currentSheet->getCell($address)->getFormattedValue(); 
			echo $cell_values."\n";
		} 

		// 看看数据 
		//print_r($cell_values); 
	} 
} 

   /* $file_path = $argv[1];*/
	//$data = new Spreadsheet_Excel_Reader();
	//$data->setOutputEncoding('CP1251');

	//$data->setColumnFormat(2, '%d');
	//$data->read("$file_path");
    //echo $data->sheets[0]['numRows']."\n";

	//$redis = new Redis();
    //$redis -> connect( '10.10.0.141', 6382 );
    //$redis -> select( 3 );


	//error_reporting(E_ALL ^ E_NOTICE);

	//for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) 
	//{
		//$grab_id = $data->sheets[0]['cells'][$i][1];
		//$grab_num = $data->sheets[0]['cells'][$i][2];
//echo $grab_id . ":".$grab_num."\n";
		//$key = "grab_id:".$grab_id;
		/*$redis->sAdd("$key", "$grab_num");*/
	//}

?>
