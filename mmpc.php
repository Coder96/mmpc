<?php

	error_reporting(E_ALL ^ E_NOTICE);

$cLastRunlog  = '/opt/mmpc/mmpc_lastrun.log';
$cDownloadlog = '/opt/mmpc/mmpc_download.log';
$cOldFileslog = '/opt/mmpc/mmpc_oldfiles.log';
$cOldFileslogtoadd = '/opt/mmpc/mmpc_oldfilestoadd.log';
$cFeedsFile   = '/opt/mmpc/mmpc_feeds.txt';
$cConfigFile = '/opt/mmpc/mmpc_config.txt';

#echo $_POST["editconfig"];
#echo '<hr>';

ToolBar();

if($_POST["edit"] == 'Save'){
	$writeline = $_POST["editfeedname"]."\t".$_POST["editfeedurl"]."\t".$_POST["editfeeduser"]."\t".$_POST["editfeedpass"]."\n";
	$FH = fopen($cFeedsFile, 'a');
	fwrite($FH, $writeline);
	fclose($FH);
} elseif($_POST["edit"] == 'Update'){
	$newFeeds = '';
	$feeds = file($cFeedsFile);
	
	foreach($feeds as $lineNum => $line){
		$line = rtrim($line);
		if($line == $_POST["line"]){
			$newFeeds = $newFeeds.$_POST["editfeedname"]."\t".$_POST["editfeedurl"]."\t".$_POST["editfeeduser"]."\t".$_POST["editfeedpass"]."\n";
		} else {
			$newFeeds = $newFeeds."$line\n";
		}
	}
	$FH = fopen($cFeedsFile,'w');
	fwrite($FH, $newFeeds);
	fclose($FH);
} elseif($_POST["feed"] == 'Edit'){
	EditFeed('Edit');
} elseif($_POST["feed"] == 'Disable'){
	FeedOnOff(Disable);
} elseif($_POST["feed"] == 'Enable'){
	FeedOnOff();
} elseif($_POST["feed"] == 'Add to old'){
	$feeds = file($cFeedsFile);
	foreach($feeds as $lineNum => $line){
		$line = rtrim($line);
		if($line == $_POST["line"]){
			$feed = explode("\t", $line);
			echo exec("/opt/mmpc/mmpc_oldfilestoadd.pl $feed[1]");
		}
	}
} elseif($_POST["editconfig"] == 'Save'){
	SaveConfig();
}


if($_POST["toolbar"] == 'Last Run Log'){
	ListFile($cLastRunlog);
} elseif($_POST["toolbar"] == 'Downloaded Log'){
	ListFile($cDownloadlog);
} elseif($_POST["toolbar"] == 'Old Files'){
	ListFile($cOldFileslog);
} elseif($_POST["toolbar"] == 'Old Files to add'){
	ListFile($cOldFileslogtoadd);
} elseif($_POST["toolbar"] == 'Add Feed'){
	EditFeed();
	ListFeeds();
} elseif($_POST["toolbar"] == 'Config'){
	ListConfig();
} else {
	ListFeeds();
}


function ToolBar(){

	global $cConfigFile;

	echo '<table><tbody>';
	echo '<tr>';
	echo '<form method=post >';
	echo "<td><input type=submit name=toolbar value='List Feeds'   /></td>";
	echo "<td><input type=submit name=toolbar value='Add Feed' /></td>";
	echo "<td><input type=submit name=toolbar value='Last Run Log' /></td>";
	echo "<td><input type=submit name=toolbar value='Downloaded Log' /></td>";
	echo "<td><input type=submit name=toolbar value='Old Files'    /></td>";
	echo "<td><input type=submit name=toolbar value='Old Files to add' /></td>";
	if(file_exists($cConfigFile)){
		echo "<td><input type=submit name=toolbar value='Config' /></td>";
	}
	echo '</form>';
	echo '</tr>';
	echo '</tbody></table>';
	echo '<hr>';
}

function ListConfig(){
	
	global $cConfigFile;
	
	$cLine = file($cConfigFile);
	
	echo '<table><thead>';
	echo '<tr><th>Description</th><th>Value</th></tr>';
	echo '</thead><tbody><form method=post>';
	foreach ($cLine as $lineNum => $line){
		$line = rtrim($line);
		$line = explode("=", $line);
		$line[1] = str_replace(";","", $line[1]);
		echo "<tr><td>$line[0]</td><td><input type=hidden name=configname[] value='$line[0]' /><input size='50%' type=text name=configval[] value=$line[1] /></td></tr>\n";
	}
	echo "<tr><td></td><td><input type=submit name=editconfig value='Save' /></td></tr>";
	echo '</form></tbody></table>';
}

function ListFeeds(){
	
	global $cFeedsFile;
	
	$EnabledList ='';
	$DisabledList = '';
	$feeds = file($cFeedsFile);
	natcasesort($feeds);
	foreach ($feeds as $lineNum => $line){
		$line = rtrim($line);
		$feed = explode("\t", $line);
		if(substr($line,0,1) == '#'){
			$DisabledList = $DisabledList."<tr><td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Enable' /></form></td>";
			$DisabledList = $DisabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Edit' /></form></td>";
			$DisabledList = $DisabledList."<td nowrap>$feed[0]</td><td>".substr($feed[1],0,30)."...</td><td>$feed[2]</td><td>$feed[3]</td>";
			$DisabledList = $DisabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Add to old' /></form></td>";
		} else {
			$EnabledList = $EnabledList."<tr><td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Disable' /></form></td>";
			$EnabledList = $EnabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Edit' /></form>";
			$EnabledList = $EnabledList."<td nowrap>$feed[0]</td><td>".substr($feed[1],0,30)."...</td><td>$feed[2]</td><td>$feed[3]</td>";
			$EnabledList = $EnabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Add to old' /></form></td></tr>";
		}
		
	}	
	echo '<table><thead>';
	echo ("<tr><th></th><th></th><th>Name</th><th>URL</th><th>User name</th><th>Pass word</th><th>Add to old files log</th></tr>");
	echo '</thead><tbody>';
	echo ($EnabledList);
	echo '<tr><td colspan=100%><hr></td></tr>';
	echo ($DisabledList);	
	echo '</tbody></table>';
}

function ListFile($iFile){
	$feeds = file($iFile);
	echo '<code><pre>';
	foreach ($feeds as $lineNum => $line){
		echo $line;
	}
	echo '</pre></code>';
}

function EditFeed($iEdit='New'){
	list($wName, $wUrl, $wUser, $wPass, $junk) = '';
	$Type = 'Save';
 	if($iEdit != 'New'){
		list($wName, $wUrl, $wUser, $wPass, $junk) = explode("\t",$_POST["line"]);
		$Type = 'Update';
	}
	echo $_POST["line"];
	echo "<table>";
	echo "<tbody><form method=post>";
	echo '<input type=hidden name=line value="'. $_POST["line"].'" />';
	echo "<tr><th>Feed Name </th><td><input size='100%' type=text name=editfeedname value='$wName' /></td></tr>";
	echo "<tr><th>Feed URL  </th><td><input size='100%' type=text name=editfeedurl  value='$wUrl'  /></td></tr>";
	echo "<tr><th>User Name </th><td><input size='100%' type=text name=editfeeduser value='$wUser' /></td></tr>";
	echo "<tr><th>Password  </th><td><input size='100%' type=text name=editfeedpass value='$wPass' /></td></tr>";
	echo "<tr><td></td><td><input type=submit name=edit value='$Type' /></td></tr>";
	echo '</form></tbody></table>';
	echo '<hr>';
}

function FeedOnOff($iFlip='E'){

	global $cFeedsFile;

	$newFeeds = '';
	$feeds = file($cFeedsFile);
	
	foreach($feeds as $lineNum => $line){
		$line = rtrim($line);
		if($line == $_POST["line"]){
			if(substr($line,0,1) == '#' and $iFlip == 'E'){
				$newFeeds = $newFeeds.substr($line,1,strlen($line)-1)."\n";
			} elseif(substr($line,0,1) != '#' and $iFlip != 'E') {
				$newFeeds = $newFeeds."#$line\n";
			}
		} else {
			$newFeeds = $newFeeds."$line\n";
		}
	}
	$FH = fopen($cFeedsFile,'w');
	fwrite($FH, $newFeeds);
	fclose($FH);
}

function SaveConfig(){
	global $cConfigFile;
	$i = 0;
	$config='';
	foreach ($_POST['configname'] as $name){
#		echo "<br>$name <br>";
#		echo $_POST['configval'][$i++];
		$config = $config . $name . "='". $_POST['configval'][$i++]."';\n";
	}
#	echo '<code><pre>';
#	echo $config;
	
	$FH = fopen($cConfigFile,'w');
	fwrite($FH, $config);
	fclose($FH);
}

?>