<?php
	/* 连接数据库 */
	//$mysqli = new mysqli("10.10.0.149", "root", "root", "MCP", "3306");
	$mysqli = new mysqli("10.10.0.10", "root", "duoku2012", "MCP", "4051");
	if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
	}
	/* change character set to utf8 */
	if (!$mysqli->set_charset("utf8")) {
		    printf("Error loading character set utf8: %s\n", $mysqli->error);
	} else {
		    printf("Current character set: %s\n", $mysqli->character_set_name());
	}

	$con = mysql_connect( "10.10.0.10:4051", "root", "duoku2012" );
	//$con = mysql_connect( "10.10.0.149:3306", "root", "root" );
	if ( !$con )
	{
		die( 'Could not connect: ' . mysql_error() );
	}

	$db_selected = mysql_select_db( "MCP", $con );
	mysql_query( "set names utf8" );

	$query ="select * from mcp_content_news where news_source=1 ;";

	$result = mysql_query( $query, $con ) or die( __LINE__ . "Invalid query: " . mysql_error());
	while( $row=mysql_fetch_row($result) )
	{
		$id = $row[0];
		$c_id = $row[1];
		$content = $row[3];
		echo $id."\n\n\n\n\n\n";
		//$content = '[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic0_resize_watermark.jpg"][BR]　　
		//在100项任务2偷天大厦第2关游戏界面中，我们转身来到了书柜前，点击书柜左边第三格的位置(如上图所示)，这里可有一个沉睡许久的木箱子正等待着我们前去打开它呢!
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic1_resize_watermark.jpg"][BR]　　
		//接着，我们在100项任务2偷天大厦第2关左下角的游戏工具栏中，选取在保险箱中所获取的木钥匙，使用木钥匙打开木箱(如上图所示)。
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic2_resize_watermark.jpg"][BR]　　
		//咔嚓~随着木钥匙插入并转动后，100项任务2偷天大厦第2关游戏中的木箱子被打开，里面呈现出一根较短的棍型物品，点击将它收入囊中(如上图所示)。
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic3_resize_watermark.jpg"][BR]　　
		//如上图所示。嘿~原来是一个手枪的消音器，它可以安装在手枪的枪筒上!发射子弹时，将会造成较小的声音哦!
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic4_resize_watermark.jpg"][BR]　　
		//随后，我们在100项任务2偷天大厦第2关游戏工具栏中选取消音器，使用消音器安装在手枪中(如上图所示)。[BR]
		//[IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic5_resize_watermark.jpg"][BR]　　
		//一把装了消音器的手枪!用它来击杀敌人，所发出的声音将会很轻。[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic6_resize_watermark.jpg"]
		//[BR]　　由此，我们来到房间门前，在100项任务2偷天大厦第2关游戏中的工具栏内，选择安装上消音器的手枪，点击打开房间门(如上图所示)。[BR]
		//[IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic7_resize_watermark.jpg"][BR]　　
		//嘿~100项任务2偷天大厦第2关游戏中的房间门被打开后，一黑一白两类不同的人种，出现在我们眼前，还在犹豫什么?使用手中的武器将这两位倒霉蛋击毙吧!
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic8_resize_watermark.jpg"][BR]　　
		//砰砰~两位倒霉蛋就此倒地不起了，就连叫喊救命的气力都木有喽!眼前正是电梯，我们快点离开才行，被别人发现那就糟糕了!点击打开100项任务2偷天大厦第2关游戏中的电梯门(如上图所示)。
		//[BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113520/pic9_resize_watermark.jpg"][BR]　　
		//如上图所示。100项任务2偷天大厦第2关游戏中的电梯门被我们打开了，你还在等什么?还不赶紧进入![BR][IMG src="http://img.m.duoku.com/preview/wap/ptbus/113/113521/pic10_resize_watermark.jpg"]
		//[BR]　　伟大的特工M16，祝贺您再次成功登入电梯前往下一关场景，继续使用您自己的方式来进行操作吧!';
		if(strpos($content, "IMG"))
		{
			echo "id:".$id .":" ."\n";
            echo $content."\n\n\n\n";
			$output = preg_replace('/\[(IMG).*src=\"(.*)\"\]/U','[${1}]${2}[/${1}]', $content, -1);
			$stmt = $mysqli->prepare("update mcp_content_news set content=? where id=$id");
			echo $output;
			$stmt->bind_param("s", $output);
			$stmt->execute();
			printf("affected %d rows", $stmt->affected_rows);
			$stmt->close();
			//break;
		}
	}

?>

