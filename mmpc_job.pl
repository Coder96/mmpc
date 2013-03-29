#!/usr/bin/perl 

# Includes
use File::Basename;
use Fcntl ':flock';

use constant { true => 1 , false => 0 };

#
# lets lock our self so only one instance of the program will run.
#
open SELF, '</opt/mmpc/mmpc_job.pl' or exit; 
flock SELF, LOCK_EX | LOCK_NB or exit;
#
# Global vars
#

my $configFile = 'mmpc_config.txt';
my $UrlIdentString = '[DownLoadURL]';

my $workdir			= '/opt/mmpc';
my $cOneTimeDL	= 'mmpc_onetimedl.txt';
my $cLastJobRun	= 'mmpc_lastjobrun.log';

my $debug = true;

#
# Load config file options
#
open(CONFIG, "$workdir/$configFile");
@lines = <CONFIG>;
foreach $line (@lines){
	eval($line);
}
close(CONFIG);

unless(-e "$workdir/$cLastJobRun"){
	system("touch $workdir/$cLastJobRun");
	system("chmod a+w $workdir/$cLastJobRun");
}
open LOG, ">>$workdir/$cLastJobRun" or die $!;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$fDateTime = sprintf("%s-%02s-%02s %02s:%02s:%02s",$year+1900,$mon+1,$mday,$hour,$min,$sec);  
writeLog("mmpc_job Start:$fDateTime");

open OneTimeMediaDL, ">>$workdir/$cOneTimeDL" or die $!;

open LOG, ">$workdir/$cLastRun" or die $!;

my $iTitle		= $ARGV[0];
my $iSubTitle	= $ARGV[1];
my $iDescrip	= $ARGV[2];

$pos = rindex $iDescrip, $UrlIdentString;

print substr($iDescrip, $pos+length($UrlIdentString))."\n";

$outLine = substr($iDescrip, $pos+length($UrlIdentString),length($iDescrip))."\t$iTitle\t$iSubTitle\t".substr($iDescrip,0,80)."\n";

print OneTimeMediaDL $outLine;

close(OneTimeMediaDL);

sub writeLog($){
	my ($string) = @_;
	print(LOG "$string\n");      
}

sub writeDebugLog($){
	my ($string) = @_;
	if($debug){
		print(LOG "$string\n");      
	}
}

# Perl trim function to remove whitespace from the start and end of the string
sub trim($){
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}
# Left trim function to remove leading whitespace
sub ltrim($){
	my $string = shift;
	$string =~ s/^\s+//;
	return $string;
}
# Right trim function to remove trailing whitespace
sub rtrim($){
	my $string = shift;
	$string =~ s/\s+$//;
	return $string;
}
