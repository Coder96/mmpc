<?php

	error_reporting(E_ALL ^ E_NOTICE);

$cLastRunlog  = '/opt/mmpc/mmpc_lastrun.log';
$cDownloadlog = '/opt/mmpc/mmpc_download.log';
$cOldFileslog = '/opt/mmpc/mmpc_oldfiles.log';
$cOldFileslogtoadd = '/opt/mmpc/mmpc_oldfilestoadd.log';
$cFeedsFile   = '/opt/mmpc/mmpc_feeds.txt';
$cConfigFile = '/opt/mmpc/mmpc_config.txt';
$cOneTimeFile = '/opt/mmpc/mmpc_onetimedl.txt';
$cWebConfig = '/opt/mmpc/mmpc_webconfig.php';
$cLastJobRun    = '/opt/mmpc/mmpc_lastjobrun.log';

$DisplayUrlChars = 30;

if(file_exists($cWebConfig)){
	$config = file($cWebConfig);
	foreach ($config as $lineNum => $line){
		eval($line);
	}
}

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
} elseif($_POST["oneshot"] == 'Add'){
	$writeline = $_POST["editfeedurl"]."\t".$_POST["edittitle"]."\t".$_POST["editsubtitle"]."\t".$_POST["editdescription"]."\n";
	$FH = fopen($cOneTimeFile, 'a');
	fwrite($FH, $writeline);
	fclose($FH);
	OneShot();
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
} elseif($_POST["editconfig"] == 'SaveUI'){
	SaveConfig('UI');
}

if($_POST["toolbar"] == 'Last Run Log'){
	ListFile($cLastRunlog);
} elseif($_POST["toolbar"] == 'Downloaded Log'){
	ListFile($cDownloadlog);
} elseif($_POST["toolbar"] == 'User Job Log'){
	ListFile($cLastJobRun);
} elseif($_POST["toolbar"] == 'Old Files Log'){
	ListFile($cOldFileslog);
} elseif($_POST["toolbar"] == 'Old Files to add'){
	ListFile($cOldFileslogtoadd);
} elseif($_POST["toolbar"] == 'Read Me'){
	ListFile('/opt/mmpc/README');
} elseif($_POST["toolbar"] == 'Add Feed'){
	EditFeed();
	ListFeeds();
} elseif($_POST["toolbar"] == 'System Config'){
	ListConfig();
} elseif($_POST["toolbar"] == 'Web UI Config'){
	ListConfig('UI');
}	elseif($_POST["toolbar"] == 'Oneshot'){
	OneShot();
} else {
	ListFeeds();
}

function ToolBar(){

	global $cConfigFile, $cWebConfig;

	echo '<form method=post >';
	echo "<input type=submit name=toolbar value='List Feeds'       />";
	echo "<input type=submit name=toolbar value='Add Feed'         />";
	echo "<input type=submit name=toolbar value='Oneshot'          />";
	echo "<input type=submit name=toolbar value='Last Run Log'     />";
	echo "<input type=submit name=toolbar value='Downloaded Log'   />";
	echo "<input type=submit name=toolbar value='Old Files Log'    />";
	echo "<input type=submit name=toolbar value='Old Files to add' />";
	echo "<input type=submit name=toolbar value='User Job Log'     />";
	
	if(file_exists($cConfigFile)){
		echo "<input type=submit name=toolbar value='System Config' />";
	}
	if(file_exists($cWebConfig)){
		echo "<input type=submit name=toolbar value='Web UI Config' />";
	}
	echo "<input type=submit name=toolbar value='Read Me' />";
	echo '</form>';
	echo '<hr>';
}

function ListConfig($configType=''){
	
	global $cConfigFile, $cWebConfig;
	
	if($configType == 'UI'){
		$cLine = file($cWebConfig);
	} else {
		$cLine = file($cConfigFile);
	}
	
	echo '<table><thead>';
	echo '<tr><th>Description</th><th>Value</th></tr>';
	echo '</thead><tbody><form method=post>';
	foreach ($cLine as $lineNum => $line){
		$line = rtrim($line);
		$line = explode("=", $line);
		$line[1] = str_replace(";","", $line[1]);
		echo "<tr><td>$line[0]</td><td><input type=hidden name=configname[] value='$line[0]' /><input size='50%' type=text name=configval[] value=$line[1] /></td></tr>\n";
	}
	echo "<tr><td></td><td><input type=submit name=editconfig value='Save".$configType."' /></td></tr>";
	echo '</form></tbody></table>';
}

function ListFeeds(){
	
	global $cFeedsFile, $DisplayUrlChars;
	
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
			$DisabledList = $DisabledList."<td nowrap>$feed[0]</td><td nowrap>";
			$DisabledList = $DisabledList. substr($feed[1],0,$DisplayUrlChars);
			if(strlen($feed[1]) > $DisplayUrlChars){
				$DisabledList = $DisabledList.'...';
			} 
			$DisabledList = $DisabledList."</td><td>$feed[2]</td><td>$feed[3]</td>";
			$DisabledList = $DisabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Add to old' /></form></td></tr>";
		} else {
			$EnabledList = $EnabledList."<tr><td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Disable' /></form></td>";
			$EnabledList = $EnabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Edit' /></form>";
			$EnabledList = $EnabledList."<td nowrap>$feed[0]</td><td nowrap>";
			$EnabledList = $EnabledList. substr($feed[1],0,$DisplayUrlChars);
			if(strlen($feed[1]) > $DisplayUrlChars){
				$EnabledList = $EnabledList.'...';
			} 
			$EnabledList = $EnabledList."</td><td>$feed[2]</td><td>$feed[3]</td>";
			$EnabledList = $EnabledList."<td><form method=post ><input type=hidden name=line value='$line' /><input type=submit name=feed value='Add to old' /></form></td></tr>";
		}
	}	
	echo '<table><thead>';
	echo ("<tr><th></th><th></th><th>Title</th><th>URL</th><th>User name</th><th>Pass word</th><th>Add to old files log</th></tr>");
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
	
	
	echo "
<script>
function FillUrl(){
	//document.getElementById('editfeedurl').value='aaa';
	var sel=document.getElementById('urltype');
	if(sel.options[sel.selectedIndex].value =='Youtube User Uploads'){
		document.getElementById('editfeedurl').value='http://gdata.youtube.com/feeds/api/users/'+ document.getElementById('editfeedtext').value +'/uploads';
	} else if(sel.options[sel.selectedIndex].value =='Justin.tv user archive'){
		document.getElementById('editfeedurl').value='http://api.justin.tv/api/channel/archives/'+ document.getElementById('editfeedtext').value +'.xml?limit=10';
		} else if(sel.options[sel.selectedIndex].value =='Youtube Channel ID'){
		document.getElementById('editfeedurl').value='https://www.youtube.com/feeds/videos.xml?channel_id='+ document.getElementById('editfeedtext').value;
	}
}
</script>
";
	
	
#	echo $_POST["line"];
	echo "<table>";
	echo "<tbody><form method=post>";
	echo '<input type=hidden name="line" value="'. $_POST["line"].'" />';
	echo "<tr><th>Feed Name </th><td><input size='100%' type=text name=editfeedname value='$wName' /></td></tr>";
	echo "<tr><th>Feed URL  </th><td><input size='100%' type=text id=editfeedurl name=editfeedurl  value='$wUrl'  /></td></tr>";
	echo "<tr><th>or        </th><td> </td></tr>";
	echo "<tr><th>Feed Type </th><td>
					<SELECT id=urltype name=urltype>
						<OPTION>Youtube Channel ID</OPTION>
						<OPTION>Youtube User Uploads</OPTION>
						<OPTION>Justin.tv user archive</OPTION>
					</SELECT>
					<input size='70%' type=text id='editfeedtext' name='editfeedtext' />
					<BUTTON type=button ONCLICK=\"FillUrl()\">^</button>
				</td></tr>";
	echo "<tr><th>User Name </th><td><input size='100%' type=text name=editfeeduser value='$wUser' /></td></tr>";
	echo "<tr><th>Password  </th><td><input size='100%' type=text name=editfeedpass value='$wPass' /></td></tr>";
	echo "<tr><td></td><td><input type=submit name=edit value='$Type'/></td></tr>";
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

function SaveConfig($configType=''){

	global $cConfigFile, $cWebConfig;
	
	if($configType == 'UI'){
		$workConfig = $cWebConfig;
	} else {
		$workConfig = $cConfigFile;
	}
	
	$cLine = file($workConfig);
	
	$i = 0;
	$config='';
	foreach ($_POST['configname'] as $name){
#		echo "<br> $name ".  $_POST['configval'][$i];
		if(is_numeric($_POST['configval'][$i])){
			$config = $config . $name . "=". $_POST['configval'][$i].";\n";
		} else {
			if($_POST['configval'][$i] == 'true' or $_POST['configval'][$i] == 'false' ){
				$config = $config . $name . "=". $_POST['configval'][$i].";\n";
			} else {
				$config = $config . $name . "='". $_POST['configval'][$i]."';\n";
			}
		}
		$i++;
	}
#	echo '<code><pre>';
#	echo $config;
	
	$FH = fopen($workConfig,'w');
	fwrite($FH, $config);
	fclose($FH);
}

function OneShot(){
	global $cOneTimeFile, $DisplayUrlChars;
	$list = file($cOneTimeFile);
	
	echo "<table>";
	echo "<tbody><form method=post>";
	echo "<tr><th>Title      </th><td><input size='100%' type=text name=edittitle /></td></tr>";
	echo "<tr><th>Subtitle   </th><td><input size='100%' type=text name=editsubtitle /></td></tr>";
	echo "<tr><th>Description</th><td><input size='100%' type=text id=editfeedurl name=editdescription /></td></tr>";
	echo "<tr><th>URL        </th><td><input size='100%' type=text id=editfeedurl name=editfeedurl /></td></tr>";
	echo "<tr><td></td><td><input type=submit name=oneshot value=Add /></td></tr>";
	echo '</form></tbody></table>';
	echo '<hr>';
	
	echo '<table><thead>';
	echo ("<tr><th>Title</th><th>Subtitle</th><th>Description</th><th>URL</th></tr>");
	echo '</thead><tbody>';
	foreach ($list as $lineNum => $line){
		$line = rtrim($line);
		$item = explode("\t", $line);
		echo "<tr><td nowrap>$item[1]</td><td nowrap>$item[2]</td><td nowrap>$item[3]</td><td nowrap>";
		echo substr($item[0],0,$DisplayUrlChars);
			if(strlen($item[0]) > $DisplayUrlChars){
				echo '...';
			} 
		echo "</td></tr>";
	}
	echo '</tbody></table>';
}

?>