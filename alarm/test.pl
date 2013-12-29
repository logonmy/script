#!/usr/bin/perl

use strict;
use URI::Escape;

my @urls;
my @turls;
my @teurls;
my @ips;
my @cmds;
my @tcmds;
my @tecmds;

#push @ips, "60";
#push @ips, "61";

#foreach(@ips)
#{
    #push @urls, "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.1.".$_."%3A60002&type_id=25&c_id=&id=&page=&page_size=&user_id=&function=257";
#}

#foreach(@urls)
#{
    #push @cmds, "curl --max-time 3 -s \"".$_."\"";
#}

#$url1 = uri_unescape($url1);
#$url2 = uri_unescape($url2);

#my $phoneno_list="15210778773";
#my $phoneno_list="15210778773";
#my $phoneno1="18612335012";
##my $phone_list2="15210778773";
#my $phone_list2="15210778773";

#my $file = "/home/zhengxie/alarm/log";
#open FILE,">>",$file;

#my $content;
#my $i;

#for($i = 0; $i<2; $i++)
#{
    #$content = "";
    #my $cnt = 0;
    #$cmds[$i] .= " |";
    #open(CommandOutput, $cmds[$i]);
    #while(<CommandOutput>)
    #{
        #if( /"count":(.+),"grab_list".*/)
        #{
            #if($1 >0)
            #{
                #$cnt = 1;
                #last;
            #}
            #else
            #{
                #$content = "10.10.0.".$ips[$i].", Fn:get_grab_list,result:".$1."_$ips[$i]";
                #print FILE $content."\n";
                #$content = uri_escape($content); 
                #last;
            #}
        #}
    #}
    #print FILE "log\n";

    #if($cnt == 0){
        #$content = "Fn:get_grab_list,error:"."_$ips[$i]";
    #}
    #my $len = length($content);
##print "$len\n";
    #if( $len > 0)
    #{
        #`curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.173%3A61002&phone=$phoneno_list&content=$content&function=29"`;
        #`curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.173%3A61002&phone=$phoneno1&content=$content&function=29"`;
    #}
#}
	    my $date = `date +%Y:%m:%d,%T`;
		print $date;

close FILE;
