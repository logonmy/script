#!/usr/bin/perl

use strict;
use warnings;

my $last_pkgs = 0;
my $this_pkgs = 0;
my $decr_cnt = 0;
my $file = "/home/work/liyijie/process";
my $sms = 0;
my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst);

while(1)
{
    my $cmd_sar = "sar -n DEV 2 1 | grep eth0 | awk -F\" \" '{print \$3}' ";
    $cmd_sar .= " |";
    open(CommandOutput, $cmd_sar);
    while(<CommandOutput>)
    {
        chomp;
		($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
#print $_."\n";
        $last_pkgs = $this_pkgs;
        $this_pkgs = $_;
		$year += 1900;
		$mon += 1;
        print "$year-$mon-$mday $hour:$min:$sec ".$_." ".$decr_cnt."\n";
		last;
    }

	if($this_pkgs < 1000 && $sms == 0)
	{
		$sms = 1;
        `curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.200%3A61002&phone=15210778773&content=10.10.1.61_pkgs_down&function=373" > /dev/null 2>&1`;
        `curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.200%3A61002&phone=18612335012&content=10.10.1.61_pkgs_down&function=373" > /dev/null 2>&1`;
	}
	else
	{
		$sms = 0;
	}	

=head
    if($last_pkgs >= $this_pkgs)
    {
        $decr_cnt++;
    }
    else
    {
        $decr_cnt=0;
    }
=cut

	#print "desc is ".$decr_cnt."\n";
    if($this_pkgs < 1000)
    {
        #`curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.200%3A61002&phone=15210778773&content=10.10.1.60_pkgs_down&function=373" > /dev/null 2>&1`;
        #`curl "http://10.10.0.50/rpc/callRPC.php?servers%5B%5D=10.10.0.200%3A61002&phone=18612335012&content=10.10.1.60_pkgs_down&function=373" > /dev/null 2>&1`;
		
        open FILE,">>", $file;
        my $cmd_top = "top -b -n 1";
        $cmd_top .=" |";
        open(CommandOutput, $cmd_top);
        while(<CommandOutput>)
        {
            chomp;
			print FILE "$year-$mon-$mday $hour:$min:$sec \n";
            print FILE $_." ".$decr_cnt."\n";
        }
        print FILE "\n\n\n";
        close FILE;
    }
    sleep 1;
}
