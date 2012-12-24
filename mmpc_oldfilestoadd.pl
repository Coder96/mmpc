#!/usr/bin/perl 

# Includes
use Fcntl ':flock';
#
# lets lock our self so only one instance of the program will run.
#
open SELF, '</opt/mmpc/mmpc_oldfilestoadd.pl' or exit; 
flock SELF, LOCK_EX | LOCK_NB or exit;

$urlToSearch = $ARGV[0];

my $rsstailPath = 'rsstail';
my $xmlstarletPath = 'xmlstarlet';
my $curlPath = 'curl';

my $workdir = '/opt/mmpc';
my $cOldFilestoAdd = 'mmpc_oldfilestoadd.log';

open OLDFILES, ">>$workdir/$cOldFilestoAdd" or die $!;

if($urlToSearch =~ m/youtube.com/i) { $DownloadType = 1; }
if($urlToSearch =~ /vimeo.com/i){ $DownloadType = 1; }
if($urlToSearch =~ /blip.tv/i){ $DownloadType = 1; }
if($urlToSearch =~ /escapistmagazine.com/i){ $DownloadType = 1; }
if($urlToSearch =~ m/justin.tv/i){  $DownloadType = 2; }

		if($DownloadType == 1){ 
			$command = "$rsstailPath -u '$urlToSearch' -lH1 2>&1";
		} elsif($DownloadType == 2){ 
			$command = "$curlPath -L -s '$urlToSearch' | $xmlstarletPath sel -t -m '/objects/object' -v 'video_file_url' -n";
#		} elsif($DownloadType == 3){ 
		} else {
			$command = "$curlPath -L -s '$urlToSearch' | $xmlstarletPath sel -t -m '/rss/channel/item' -m 'enclosure' -v '\@url' -n";
		}
		$cmdout = qx($command);
		
		if($DownloadType == 1){
			my $mString = '';
			@cmdout = split("\n",$cmdout);
			foreach $line (@cmdout){
				($mkey, $mvalue) = split(/: /,$line);
				if($mkey =~ m/link/i){
					$mString = "$mString$mvalue\n";
				}
			}
			$cmdout = $mString;
		}
		print(OLDFILES $cmdout);
