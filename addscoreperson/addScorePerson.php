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
                                        'file' => '/home/zhengxie/addscoreperson/addscoreperson.log',
                                         'level' => 0xFF,
                                        ),
                         );
    Bingo_Log::init( $arrLogConfig, 'ui' );

	/* 初始化redis,方便每小时检验渠道是否更新 */
    $redis = new Redis();
    $redis -> connect( '10.10.0.19', 6382 );

    /* 连接数据库 */
    $con = mysql_connect( "10.10.0.149:3306", "root", "root" );
    if ( !$con ){
        die( 'Could not connect: ' . mysql_error() );
    }
    $db_selected = mysql_select_db( "MCP", $con );
    mysql_query( "set names utf8" );

	$score_person = array();

	$query = "select MC.id from mcp_content as MC, mcp_content_class_list as MCCL, mcp_content_class as MCC  
		where MC.visible = 1 and MC.enable = 1 and MC.status = 1 and MC.id = MCCL.c_id and MCCL.class_id = MCC.id
		and MCC.id in (108,99,104,100,102,97,98,101);";
	$result = mysql_query( $query );
	$i = 0;
	while( $row=mysql_fetch_row($result) )
	{
		$c_id = $row[0];
		var $score, $person;
		get_star_by_c_id( $c_id, $score, $person );
		$score_person[$i][0] = $c_id;
		$score_person[$i][1] = $score;
		$score_person[$i][2] = $person;
		$i++;
	}
	foreach($score_person as $record)
	{
		$c_id = $record[0];
		$score = $record[1];
		$person = $record[2];
		$query = "select count(*), score, person from average_score_person where c_id = \"$c_id\";"; 
		$result = mysql_query( $query );
		$num_row = mysql_num_rows( $result );
		$row = mysql_fetch_row( $result );
		if(0 == $num_row){
			$query = "insert into average_score_person values('',"$c_id","$score","$person");";
		}else if( $score !== $row[1] || $person !== $row[2] ){
			$query = "update average_score_person set score = $score, person = $person where c_id = \"$c_id\";";
		}
		mysql_query($query);
	}	

	/* 2013.05.28 add by zhengxie 修改用户评分逻辑 beg*/
	function get_star_by_c_id(&$c_id, &$score, &$person)
	{
		$query = "select package_name, version_code from mcp_content_appinfo where c_id=$c_id;";
		$result = mysql_query($query);
		$row = mysql_fetch_row($row);

		$package_name = $row[0];
		$version_code = $row[1];
		$key = $package_name . "_" . $version_code .  "_";
		vector<string> key_v, key_v2;
		double score_person[5] = {0.0,0.0,0.0,0.0,0.0};

		redisContext *c;
		c=redisConnPool::getInstance()->getConnection();
		if(c==NULL){
			printf( "get redis connection failed, [%s:%d]", __FILE__, __LINE__ );
			return 0.0;
		}

		redisReply *reply, *reply2;
		reply = (redisReply*)redisCommand( c, "KEYS Cid:%s*",c_id );
		reply2 = (redisReply*)redisCommand( c, "HKEYS score" );

		redisConnPool::getInstance()->releaseConnection(c);
		if(reply!=NULL)
		{
		   for (uint32_t i = 0; i < reply->elements; ++i) {
				redisReply* childReply = reply->element[i];
				key_v.push_back( childReply->str );
		   }
		   freeReplyObject(reply);
		}else{
		   printf( "get a NULL redis connection, [%s:%d]", __FILE__, __LINE__ );
		   return 0.0;
		}

		if(reply2!=NULL)
		{
		   for (uint32_t i = 0; i < reply2->elements; ++i) {
				redisReply* childReply = reply2->element[i];
				key_v2.push_back( childReply->str );
		   }
		   freeReplyObject(reply2);
		}else{
			printf( "get a NULL redis connection, [%s:%d]", __FILE__, __LINE__ );
			return 0.0;
		}

		for(uint32_t i=0; i<key_v.size(); i++)
		{
			reply = (redisReply*)redisCommand( c, "HGET %s rating", key_v[i].c_str() );
			redisConnPool::getInstance()->releaseConnection(c);
			if( reply->type!=REDIS_REPLY_ERROR )
			{
				score_person[atoi(reply->str)-1]++;
			}else{
				freeReplyObject(reply);
				printf( "get a NULL redis connection, [%s:%d]", __FILE__, __LINE__ );
				return 0.0;
			}
			freeReplyObject(reply);
		}

		for(uint32_t i=0; i<key_v2.size(); i++)
		{
			reply2 = (redisReply*)redisCommand( c, "HGET score %s", key_v2[i].c_str() );
			redisConnPool::getInstance()->releaseConnection(c);
			if( reply->type!=REDIS_REPLY_ERROR )
			{
				score_person[atoi(reply2->str)-1]++;
			}else{
				freeReplyObject(reply2);
				printf( "get a NULL redis connection, [%s:%d]", __FILE__, __LINE__ );
				return 0.0;
			}
			freeReplyObject(reply2);
		}

		double total_person = (double)(score_person[0]+score_person[1]+score_person[2]+score_person[3]+score_person[4]);
		double total_score = (double)(1.0*score_person[0]+2.0*score_person[1]+3.0*score_person[2]+4.0*score_person[3]+5.0*score_person[4]); 
		double star = 0.0;

		if(total_person != 0.0){
			star = total_score / total_person;
		}
		//UB_LOG_DEBUG( "c_id:[%s],star:[%lf]\n", c_id, star);
		return star;
	}
	/* 2013.05.28 add by zhengxie 修改用户评分逻辑 end*/
