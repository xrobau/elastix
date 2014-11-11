#!/usr/bin/env perl

use strict;
use warnings;
use Env qw($COLLECTD_HOSTNAME $COLLECTD_INTERVAL);

$| = 1;
my $hostname;
my $interval;

if (defined($COLLECTD_HOSTNAME)) {
	$hostname = $COLLECTD_HOSTNAME;
} else {
	$hostname = 'localhost';
}

if (defined($COLLECTD_INTERVAL)) {
	$interval = int($COLLECTD_INTERVAL);
} else {
	$interval = 100;
}

do{
    my $comand = 'asterisk -rx "core show channels" | grep "active" | awk \'{ print $1" "$3 }\' 2>&1';
    my $plugin_name = "elastixcalls";

    my $now = time();
    my @result = `$comand`;

    foreach ( @result ){
        my @value = split " ", $_;
        if($value[1] eq 'call' || $value[1] eq 'calls'){
            printf "PUTVAL \"%s/$plugin_name/gauge-active_calls\" interval=%d $now:%d\n" , $hostname, $interval, $value[0];
        }elsif($value[1] eq 'channel' || $value[1] eq 'channels'){
            printf "PUTVAL \"%s/$plugin_name/gauge-active_channels\" interval=%d $now:%d\n" , $hostname, $interval, $value[0];
        }
    }
    sleep($interval);
}while(1);
