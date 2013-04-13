#!/usr/bin/perl 

# Includes
use File::Basename;
#use Fcntl ':flock';
use MythTV;

use constant { true => 1 , false => 0 };

#
# lets lock our self so only one instance of the program will run.
#
#open SELF, '</opt/mmpc/mmpc_job.pl' or exit; 
#flock SELF, LOCK_EX | LOCK_NB or exit;
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


open LOG, ">>$workdir/$cLastJobRun" or die $!;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$fDateTime = sprintf("%s-%02s-%02s %02s:%02s:%02s",$year+1900,$mon+1,$mday,$hour,$min,$sec);  
writeLog("mmpc_job Start:$fDateTime");


# Connect to mythbackend
my $Myth = new MythTV({'connect' => 0});
# Connect to the database
my $dbh = $Myth->{'dbh'};

open OneTimeMediaDL, ">>$workdir/$cOneTimeDL" or die $!;

my $iFile		= $ARGV[0];


writeLog($iFile);

$sql = "SELECT title, subtitle, description FROM recorded WHERE basename LIKE '$iFile' ";
$sth=$dbh->prepare($sql);
$sth->execute();
($Tiltle, $SubTitle, $Desc) = $sth->fetchrow_array();



$pos = rindex $Desc, $UrlIdentString;

#print substr($Desc, $pos+length($UrlIdentString))."\n";

$outLine = substr($Desc, $pos+length($UrlIdentString),length($Desc))."\t$Tiltle\t$SubTitle\t".substr($Desc,0,$pos)."\n";

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
