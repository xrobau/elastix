#!/usr/bin/perl
# 
# CrystalFontz 635 "packet" control program in perl
# by Jason Plumb
# 
# Last modified on 11/24/2007

# CRC function borrowed from the specification and made functional.
#
# Requires the stty application to be in the path.
#

use strict;
use constant CMD_STORE_BOOT => 0x04;

use constant CMD_TYPE_SEND 		=> 00;
use constant CMD_TYPE_RECV 		=> 1 << 6;
use constant CMD_TYPE_REPORT 	=> 2 << 6;
use constant CMD_TYPE_ERROR		=> 3 << 6;

# Cursor types
use constant CURSOR_NONE		=> 0;
use constant CURSOR_BLINK_BLOCK	=> 1;
use constant CURSOR_UNDERSCORE	=> 2;
use constant CURSOR_BLINK_UNDERSCORE => 3;
use constant CURSOR_INV_BLOCK	=> 4;

# TODO: Make the device and default baud params.
my $device = $ARGV[0] or '/dev/ttyUSB0';
set_local_baud($device, 115200);

open FH, "+<" . $device or die "ERROR opening serial port.";
binmode FH, ":raw";

my %commands = (
	'ping' => [\&lcd_ping, 'text_data'],
	'get_hardware' => [\&lcd_get_hardware],
	'clear' => [\&lcd_clear],
	'position' => [\&lcd_position, 'x, y'],
	'reboot' => [\&lcd_reboot],
	'cursor_style' => [\&lcd_cursor_style, 'style[0-4]'],
	'store_state' => [\&lcd_store_state],
	'contrast' => [\&lcd_contrast, 'value[0-255]'],
	'backlight' => [\&lcd_backlight, 'value[0-100]'],
	'pos_data' => [\&lcd_pos_data, 'col, row, string_data'],
	'baud' => [\&lcd_baud, 'rate(115200|19200)'],
	'led' => [\&lcd_led, 'led index, [red|green], state']
);

while(1){
	print "_lcd_> ";
	$_ = <>;
	chomp;
	(/^quit$/ or /^q$/) and last;
	/^$/ and next;
	if(/^\?/ or /^help$/){
		help();
		next;
	}
	my ($func_name, @params) = split /\s+/;
	my ($function, $usage) = get_function($func_name);
	if(not defined($function)){
		print "Unknown command '$func_name' (try 'help')\n";
		next;
	}

	$function->(@params);
}

close FH;

#######################################################################

sub help {
	print "Perl interface to CrystalFontz 635\n";
	print "by jason plumb (jason\@noisybox.net)\n\n";
	print "Commands: \n";
	foreach my $key (sort keys(%commands)){
		printf("    %s\n", (get_function($key))[1]);	
	}
}

sub get_function {
	my $name = shift;

	my $function_ref = $commands{$name}->[0];
	my $usage = $name;
	if(defined($commands{$name}->[1]) && length($commands{$name}->[1])){
		$usage .= ' ' . 
			join ' ', map { "<" . $_ . ">" } 
			split /\s*,\s*/, $commands{$name}->[1];
	}
	
	return ($function_ref, $usage);
}

#######################################################################
# Helper functions...unoriginal and mostly duplicated
#######################################################################

sub lcd_led {
	my ($which_led, $red_or_green, $state) = @_;
	if($which_led > 3 or ($which_led < 0)){
		print "$which_led is an invalid LED (valid is 0-3)\n";
		return;
	}
	my $index = 12 - (2*$which_led);
	if($red_or_green =~ /^r/){
		$index -= 0;
	}
	elsif($red_or_green =~ /^g/){
		$index -= 1;
	}
	else {
		print "Invalid led color/die $red_or_green (specify 'red' or 'green')\n";
		return;
	}
	print "Setting state of $which_led $red_or_green to $state...\n";
	send_packet(0x22, $index, $state);
	my ($type, $length, $data, $crc) = recv_packet();
	print "LED value set.\n";
}

# This is not well tested, the app likely has to be restarted after.
sub lcd_baud {
	my $rate = shift;
	($rate == 19200) and $rate = 0;
	($rate == 115200) and $rate = 1;
	($rate > 1) and print "Invalid baud rate $rate (valid are 19200 and 115200)\n" and return;
	print "Setting baud rate to $rate\n";
	send_packet(0x21, $rate);
	my ($type, $length, $data, $crc) = recv_packet();
	print "LCD rate changed, switching local baud...\n";
	print "Closing device....\n";
	close FH;
	sleep 1;
	print "Setting local baud...\n";
	set_local_baud($device, $rate);
	print "Local baud changed, reopening device...\n";
	open FH, "+<" . $device or die "ERROR opening serial port.";
	binmode FH, ":raw";
	print "Baud changed, try a ping to confirm.\n";
}

sub lcd_pos_data {
	my ($col, $row, @data) = @_;
	my $data = join ' ', @data;
	print "Placing '$data' at ($col, $row)...\n";
	send_packet(0x1F, $col, $row, ascii_to_data($data));
	my ($type, $length, $data, $crc) = recv_packet();
	print "Data sent.\n";
}

sub lcd_backlight {
	my $val = shift;
	print "Setting backlight brightness to $val...\n";
	send_packet(0x0E, $val);
	my ($type, $length, $data, $crc) = recv_packet();
	print "Backlight brightness set to $val.\n";
}

sub lcd_contrast {
	my $val = shift;
	print "Setting contrast value to $val...\n";
	send_packet(0x0D, $val);
	my ($type, $length, $data, $crc) = recv_packet();
	print "Constrast set to $val.\n";
}

sub lcd_cursor_style {
	my $style = shift;	# must be 0-4
	print "Setting cursor style to $style...\n";
	send_packet(0x0C, $style);
	my ($type, $length, $data, $crc) = recv_packet();
	print "Cursor type set.\n";
}

sub lcd_position {
	my ($col, $row) = @_;
	print "Setting position to col = $col, row = $row\n";
	send_packet(0x0B, $col, $row);
	my ($type, $length, $data, $crc) = recv_packet();
}

sub lcd_ping {
	my $pingdata = join ' ', @_;
	print "Sending '$pingdata' ping packet...\n";
	send_packet(0x00, ascii_to_data($pingdata));
	my ($type, $length, $data, $crc) = recv_packet();
	print "Reply: $data\n";
	return ($type, $length, $data, $crc);
}

sub lcd_get_hardware {
	print "Fetching LCD hardware and firmware version...\n";
	send_packet(0x01);
	my ($type, $length, $data, $crc) = recv_packet();
	print "Reply: $data.\n";
}

sub lcd_store_state {
	print "Storing current state as boot state...\n";
	send_packet(0x04);
	my ($type, $length, $data, $crc) = recv_packet();
	print "State has been saved.";
}

sub lcd_clear {
	print "Clearning LCD screen...\n";
	send_packet(0x06);
	my ($type, $length, $data, $crc) = recv_packet();
	# TODO: Check return/result codes.
	print "LCD screen cleared.\n";
}

sub lcd_reboot {
	print "Rebooting LCD...\n";
	send_packet(0x05, 8, 18, 99);
	my ($type, $length, $data, $crc) = recv_packet();
	# TODO: Check return/result codes.
	print "LCD rebooted";
}

#######################################################################
# Sends the data packet
#######################################################################
sub send_packet {
	my ($type, @data) = @_;
	my $length = scalar @data;
	#my $packet = pack("C*", $type, $length, @data);
	my $packet = pack("C*", $type, $length, @data) . pack("S", compute_crc($type, @data));

	#dump_hex(pack("C*", $type, $length, @data));

	my $rc = syswrite(FH, $packet);
}

#######################################################################
# Receive a packet
#######################################################################
sub recv_packet {
	my ($type, $length, $data, $crc);
	sysread(FH, $type, 1) or die "Couldn't read: $!";
	$type = unpack("C", $type);
	#printf("DEBUG: read type 0x%02X\n", $type);

	sysread(FH, $length, 1) or die "Couldn't read: $!";
	$length = unpack("C", $length);
	#printf("DEBUG: read length 0x%02X\n", $length);

	if($length > 0){
		sysread(FH, $data, $length) or die "Couldn't read: $!";
		#dump_hex($data);
	}
	sysread(FH, $crc, 2);
	return ($type, $length, $data, $crc);
}

#######################################################################
# ascii_to_data
#######################################################################
sub ascii_to_data {
	my $str = shift;
	return map { ord($_) } split(//, $str);
}

#######################################################################
# Dumps a packed string into ascii hex bytes (for debugging)
#######################################################################
sub dump_hex {
	my $val = shift;
	foreach my $b (unpack('C*', $val)){
		printf("0x%02X ", $b);
	}
	print "\n";
}


#######################################################################
# Sets the baud rate (and other options)
#######################################################################
sub set_local_baud {
	my ($device, $rate) = @_;
	# Configure device using stty...
	print "Setting device parameters for $device (baud $rate)\n";
	system("stty $rate -echo -echoe -echok -echoctl -echoke -parodd -ignpar " . 
		"-inpck -istrip raw -parmrk -parenb cs8 -cstopb < $device") 
		and die "Couldn't init device $device ($!)";

}

#######################################################################
# A CRC that injects length of @data after the type.
# This is not a completely generic CRC, careful reusing it!
#######################################################################
sub compute_crc {
	my ($type, @data) = @_;

	my @CRC_LOOKUP =
	(0x00000,0x01189,0x02312,0x0329B,0x04624,0x057AD,0x06536,0x074BF,
	0x08C48,0x09DC1,0x0AF5A,0x0BED3,0x0CA6C,0x0DBE5,0x0E97E,0x0F8F7,
	0x01081,0x00108,0x03393,0x0221A,0x056A5,0x0472C,0x075B7,0x0643E,
	0x09CC9,0x08D40,0x0BFDB,0x0AE52,0x0DAED,0x0CB64,0x0F9FF,0x0E876,
	0x02102,0x0308B,0x00210,0x01399,0x06726,0x076AF,0x04434,0x055BD,
	0x0AD4A,0x0BCC3,0x08E58,0x09FD1,0x0EB6E,0x0FAE7,0x0C87C,0x0D9F5,
	0x03183,0x0200A,0x01291,0x00318,0x077A7,0x0662E,0x054B5,0x0453C,
	0x0BDCB,0x0AC42,0x09ED9,0x08F50,0x0FBEF,0x0EA66,0x0D8FD,0x0C974,
	0x04204,0x0538D,0x06116,0x0709F,0x00420,0x015A9,0x02732,0x036BB,
	0x0CE4C,0x0DFC5,0x0ED5E,0x0FCD7,0x08868,0x099E1,0x0AB7A,0x0BAF3,
	0x05285,0x0430C,0x07197,0x0601E,0x014A1,0x00528,0x037B3,0x0263A,
	0x0DECD,0x0CF44,0x0FDDF,0x0EC56,0x098E9,0x08960,0x0BBFB,0x0AA72,
	0x06306,0x0728F,0x04014,0x0519D,0x02522,0x034AB,0x00630,0x017B9,
	0x0EF4E,0x0FEC7,0x0CC5C,0x0DDD5,0x0A96A,0x0B8E3,0x08A78,0x09BF1,
	0x07387,0x0620E,0x05095,0x0411C,0x035A3,0x0242A,0x016B1,0x00738,
	0x0FFCF,0x0EE46,0x0DCDD,0x0CD54,0x0B9EB,0x0A862,0x09AF9,0x08B70,
	0x08408,0x09581,0x0A71A,0x0B693,0x0C22C,0x0D3A5,0x0E13E,0x0F0B7,
	0x00840,0x019C9,0x02B52,0x03ADB,0x04E64,0x05FED,0x06D76,0x07CFF,
	0x09489,0x08500,0x0B79B,0x0A612,0x0D2AD,0x0C324,0x0F1BF,0x0E036,
	0x018C1,0x00948,0x03BD3,0x02A5A,0x05EE5,0x04F6C,0x07DF7,0x06C7E,
	0x0A50A,0x0B483,0x08618,0x09791,0x0E32E,0x0F2A7,0x0C03C,0x0D1B5,
	0x02942,0x038CB,0x00A50,0x01BD9,0x06F66,0x07EEF,0x04C74,0x05DFD,
	0x0B58B,0x0A402,0x09699,0x08710,0x0F3AF,0x0E226,0x0D0BD,0x0C134,
	0x039C3,0x0284A,0x01AD1,0x00B58,0x07FE7,0x06E6E,0x05CF5,0x04D7C,
	0x0C60C,0x0D785,0x0E51E,0x0F497,0x08028,0x091A1,0x0A33A,0x0B2B3,
	0x04A44,0x05BCD,0x06956,0x078DF,0x00C60,0x01DE9,0x02F72,0x03EFB,
	0x0D68D,0x0C704,0x0F59F,0x0E416,0x090A9,0x08120,0x0B3BB,0x0A232,
	0x05AC5,0x04B4C,0x079D7,0x0685E,0x01CE1,0x00D68,0x03FF3,0x02E7A,
	0x0E70E,0x0F687,0x0C41C,0x0D595,0x0A12A,0x0B0A3,0x08238,0x093B1,
	0x06B46,0x07ACF,0x04854,0x059DD,0x02D62,0x03CEB,0x00E70,0x01FF9,
	0x0F78F,0x0E606,0x0D49D,0x0C514,0x0B1AB,0x0A022,0x092B9,0x08330,
	0x07BC7,0x06A4E,0x058D5,0x0495C,0x03DE3,0x02C6A,0x01EF1,0x00F78);

	my $length = scalar @data;
	my $packet = pack("C*", $type, $length, @data);

	my $crc = 0xFFFF ;

	foreach my $byte (unpack('C*', $packet)){
		$crc = ($crc >> 8) ^ $CRC_LOOKUP[($crc ^ $byte) & 0xFF] ;
	}
	$crc = ($crc & 0xFFFF) ;

	# Invert the result
	return ~$crc;
}
