<?php
      $redis = new Redis();
      $redis->connect( '10.10.0.29', 6382 );
      $redis->select( 3 );
	  $keys = $redis->keys('install*');
	  foreach($keys as $key)
	  {
		  echo $key."\n";
		  $redis->delete( "$key" );
	  }
	  $keys = $redis->keys('lanuch*');
	  foreach($keys as $key)
	  {
		  echo $key."\n";
		  $redis->delete( "$key" );
	  }
?>
