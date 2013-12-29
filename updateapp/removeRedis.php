<?php
	/* 初始化并删除redis,方便每小时检验渠道是否更新 */
      $redis = new Redis();
      $redis->connect( '127.0.0.1', 6382 );
      $redis->select( 5 );
      $redis->delete( 'duoku_first_redis' );
      $redis->delete( 'no_app_redis' );
      $redis->delete( 'no_duoku_redis' );
      $redis->delete( 'duoku_no_first_redis' );
?>
