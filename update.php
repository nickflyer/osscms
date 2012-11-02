<?php
/*
	[SupeSite] (C) 2007-2009 Comsenz Inc.
	$Id: update.php 13515 2009-11-26 08:27:10Z zhaofei $
*/

@define('IN_SUPESITE_UPDATE', TRUE);

if(!@include('./common.php')) {
	exit('请将本文件移到程序根目录再运行!');
}

error_reporting(0);

//不让计划任务执行
$_SGLOBAL['cronnextrun'] = $_SGLOBAL['timestamp']+3600;

//新SQL
$sqlfile = S_ROOT.'./data/install.sql';
if(!file_exists($sqlfile)) {
	show_msg('最新的SQL不存在,请先将最新的数据库结构文件 install.sql 已经上传到 ./data 目录下面后，再运行本升级程序');
}

$lockfile = './data/update.lock';
if(file_exists($lockfile)) {
	show_msg('请您先登录服务器ftp，手工删除 data/update.lock 文件，再次运行本文件进行SupeSite升级。');
}

$PHP_SELF = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];

//提交处理
if(submitcheck('delsubmit')) {
	//删除表
	if(!empty($_POST['deltables'])) {
		foreach ($_POST['deltables'] as $tname => $value) {
			$_SGLOBAL['db']->query("DROP TABLE ".tname($tname));
		}
	}
	//删除字段
	if(!empty($_POST['delcols'])) {
		foreach ($_POST['delcols'] as $tname => $cols) {
			foreach ($cols as $col => $indexs) {
				if($col == 'PRIMARY') {
					$_SGLOBAL['db']->query("ALTER TABLE ".tname($tname)." DROP PRIMARY KEY", 'SILENT');//屏蔽错误
				} elseif($col == 'KEY') {
					foreach ($indexs as $index => $value) {
						$_SGLOBAL['db']->query("ALTER TABLE ".tname($tname)." DROP INDEX `$index`", 'SILENT');//屏蔽错误
					}
				} else {
					$_SGLOBAL['db']->query("ALTER TABLE ".tname($tname)." DROP `$col`");
				}
			}
		}
	}

	show_msg('删除表和字段操作完成了', 'update.php?step=delete');
}

if(empty($_GET['step'])) $_GET['step'] = 'start';

//处理开始
if($_GET['step'] == 'start') {

	show_msg('
	<div id="ready">
	本升级程序会参照最新的SQL文,对您的SupeSite数据库进行升级。<br><br>
	升级前请做好以下前期工作：<br><br>
	<b>第一步：</b><br>
	备份当前的数据库，避免升级失败，造成数据丢失而无法恢复；<br><br>
	<b>第二步：</b><br>
	将程序包 ./upload/ 目录中，除 config.new.php 文件、./install/ 目录以外的其他所有文件，全部上传并覆盖当前程序。<b>特别注意的是，最新数据库结构 ./data/install.sql 文件不要忘记上传，否则会导致升级失败</b>；<br><br>
	<b>第三步：</b><br>
	确认已经将程序包 ./update 目录中最新的 update.php 升级程序上传到服务器程序根目录中<br>
	<br><br>
	<a href="update.php?step=check">已经做好了以上工作，升级开始</a><br><br>
	特别提醒：为了数据安全，升级完毕后，不要忘记删除本升级文件。
	</div>
	');

} elseif ($_GET['step'] == 'check') {
	
	//UCenter_Client
	include_once S_ROOT.'./uc_client/client.php';
	if(!function_exists('uc_check_version')) {
		show_msg('请将SupeSite程序包中最新版本的 ./upload/uc_client 上传至程序根目录覆盖原有目录和文件后，再尝试升级。');
	}

	$uc_root = get_uc_root();
	$return = uc_check_version();
	if (empty($return)) {
		$upgrade_url = 'http://'.$_SERVER['HTTP_HOST'].$PHP_SELF.'?step=sql';
	} else {
		if($return['db'] == '1.5.0') {
			header("Location: update.php?step=sql");//UC升级完成
			exit();
		}
		$upgrade_url = 'http://'.$_SERVER['HTTP_HOST'].$PHP_SELF.'?step=check';
	}
	
	$ucupdate = UC_API."/upgrade/upgrade2.php?action=db&forward=".urlencode($upgrade_url);
	
	show_msg('<b>您的 UCenter 程序还没有升级完成，请如下操作：</b><br>SupeSite支持了最新版本的UCenter，请先升级您的UCenter。<br><br>
		1. <a href="http://download.comsenz.com/UCenter/1.5.0/" target="_blank">点击这里下载对应编码的 UCenter 1.5.0 程序</a><br>
		2. 将解压缩得到的 ./upload 目录下的程序覆盖到已安装的UCenter目录 <b>'.($uc_root ? $uc_root : UC_API).'</b><br>
		&nbsp;&nbsp;&nbsp; (确保其升级程序 <b>./upgrade/upgrade2.php</b> 也已经上传到UCenter的 ./upgrade 目录)<br><br>
		确认完成以上UCenter程序升级操作完成后，您才可以：<br>
		<a href="'.$ucupdate.'" target="_blank">新窗口中访问 upgrade2.php 进行UCenter数据库升级</a><br>
		在打开的新窗口中，如果UCenter升级成功，程序会自动进行下一步的升级。<br>这时，您关闭本窗口即可。
		<br><br>
		如果您无法通过上述UCenter升级步骤，请调查问题后，务必将UCenter正常升级后，再继续本升级程序。<br>或者您可以：<br><a href="update.php?step=sql" style="color:#CCC;">跳过UCenter升级</a>，但这可能会带来一些未知兼容问题。');

} elseif ($_GET['step'] == 'sql') {

	$cachefile = S_ROOT.'./data/system/update_model.cache.php';
	@unlink($cachefile);

	//config.php检测
	$newconfigvalues = array(
		'var' => array('$_SC', '$_SC[\'dbhost\']', '$_SC[\'dbuser\']', '$_SC[\'dbpw\']', '$_SC[\'dbname\']', '$_SC[\'tablepre\']', '$_SC[\'pconnect\']', '$_SC[\'dbcharset\']', '$_SC[\'siteurl\']', 
						'$_SC[\'dbhost_bbs\']', '$_SC[\'dbuser_bbs\']', '$_SC[\'dbpw_bbs\']', '$_SC[\'dbname_bbs\']', '$_SC[\'tablepre_bbs\']', '$_SC[\'pconnect_bbs\']', '$_SC[\'dbcharset_bbs\']', '$_SC[\'bbsver\']', '$_SC[\'bbsurl\']', '$_SC[\'bbsattachurl\']', 
						'$_SC[\'dbhost_uch\']', '$_SC[\'dbuser_uch\']', '$_SC[\'dbpw_uch\']', '$_SC[\'dbname_uch\']', '$_SC[\'tablepre_uch\']', '$_SC[\'pconnect_uch\']', '$_SC[\'dbcharset_uch\']', '$_SC[\'uchurl\']', '$_SC[\'uchattachurl\']', '$_SC[\'uchftpurl\']', 
						'$_SC[\'founder\']', '$_SC[\'dbreport\']', '$_SC[\'cookiepre\']', '$_SC[\'cookiedomain\']', '$_SC[\'cookiepath\']', '$_SC[\'headercharset\']', '$_SC[\'charset\']', '$_SC[\'adminemail\']', '$_SC[\'sendmail_silent\']', '$_SC[\'mailsend\']', 
						'$mailcfg', '$mailcfg[\'maildelimiter\']', '$mailcfg[\'mailusername\']', '$mailcfg[\'server\']', '$mailcfg[\'port\']', '$mailcfg[\'auth\']', '$mailcfg[\'from\']', '$mailcfg[\'auth_username\']', '$mailcfg[\'auth_password\']', '$_SC[\'tplrefresh\']', '$_SC[\'cachegrade\']'),
		'define' => array('UC_CONNECT', 'UC_DBHOST', 'UC_DBUSER', 'UC_DBPW', 'UC_DBNAME', 'UC_DBCHARSET', 'UC_DBTABLEPRE', 'UC_DBCONNECT', 'UC_KEY', 'UC_API', 'UC_CHARSET', 'UC_IP', 'UC_APPID', 'UC_PPP')
	);
	$configcontent = sreadfile(S_ROOT.'/config.php');
	preg_match_all("/([$].*?)[\s\t\n]/i", $configcontent, $configvars);
	preg_match_all("/define\('(UC_.*?)'/i", $configcontent, $configdefines);
	$scarcity = array();
	foreach($newconfigvalues as $key => $val) {
		foreach($val as $value) {
			if(!in_array($value, ($key == 'var' ? $configvars[1] : $configdefines[1]))) $scarcity[] = $value;
		}
	}
	if(!empty($scarcity)) {
		show_msg('当前服务器上“config.php”文件不是最新，需要您对其进行更新。<br/><br/>
				文件中缺少的参数:<br/>'.implode('<br/>', $scarcity).'<br/><br/>
				请参考程序包 ./upload 目录中，config.new.php文件，将现行config.php文件更新后，再运行本升级程序');
	}
	
	//新的SQL
	$sql = sreadfile($sqlfile);
	preg_match_all("/CREATE\s+TABLE\s+supe\_(.+?)\s+\((.+?)\)\s+(TYPE|ENGINE)\=/is", $sql, $matches);
	$newtables = empty($matches[1])?array():$matches[1];
	$newsqls = empty($matches[0])?array():$matches[0];
	if(empty($newtables) || empty($newsqls)) {
		show_msg('最新的SQL不存在,请先将最新的数据库结构文件 install.sql 已经上传到 ./data 目录下面后，再运行本升级程序');
	}

	//升级表
	$i = empty($_GET['i'])?0:intval($_GET['i']);
	if($i>=count($newtables)) {
		//处理完毕
		show_msg('数据库结构升级完毕，进入下一步操作', 'update.php?step=data');
	}
	
	//当前处理表
	$newtable = $newtables[$i];
	$newcols = getcolumn($newsqls[$i]);

	//获取当前SQL
	if(!$query = $_SGLOBAL['db']->query("SHOW CREATE TABLE ".tname($newtable), 'SILENT')) {
		//添加表
		preg_match("/(CREATE TABLE .+?)\s+[TYPE|ENGINE]+\=/is", $newsqls[$i], $maths);
		if(strpos($newtable, 'session')) {
			$type = mysql_get_server_info() > '4.1' ? " ENGINE=MEMORY".(empty($_SC['dbcharset'])?'':" DEFAULT CHARSET=$_SC[dbcharset]" ): " TYPE=HEAP";
		} else {
			$type = mysql_get_server_info() > '4.1' ? " ENGINE=MYISAM".(empty($_SC['dbcharset'])?'':" DEFAULT CHARSET=$_SC[dbcharset]" ): " TYPE=MYISAM";
		}
		$usql = $maths[1].$type;
		$usql = str_replace("CREATE TABLE supe_", 'CREATE TABLE '.$_SC['tablepre'], $usql);
		if(!$_SGLOBAL['db']->query($usql, 'SILENT')) {
			show_msg('['.$i.'/'.count($newtables).']添加表 '.tname($newtable).' 出错,请手工执行以下SQL语句后,再重新运行本升级程序:<br><br>'.shtmlspecialchars($usql));
		} else {
			$msg = '['.$i.'/'.count($newtables).']添加表 '.tname($newtable).' 完成';
		}
	} else {
		$value = $_SGLOBAL['db']->fetch_array($query);
		$oldcols = getcolumn($value['Create Table']);

		//获取升级SQL文
		$updates = array();
		foreach ($newcols as $key => $value) {
			if($key == 'PRIMARY') {
				if($value != $oldcols[$key]) {
					if(!empty($oldcols[$key])) $updates[] = "DROP PRIMARY KEY";
					$updates[] = "ADD PRIMARY KEY $value";
				}
			} elseif ($key == 'KEY') {
				foreach ($value as $subkey => $subvalue) {
					if(!empty($oldcols['KEY'][$subkey])) {
						if($subvalue != $oldcols['KEY'][$subkey]) {
							$updates[] = "DROP INDEX `$subkey`";
							$updates[] = "ADD INDEX `$subkey` $subvalue";
						}
					} else {
						$updates[] = "ADD INDEX `$subkey` $subvalue";
					}
				}
			} else {
				if(!empty($oldcols[$key])) {
					if(str_replace('mediumtext', 'text', $value) != str_replace('mediumtext', 'text', $oldcols[$key])) {
						$updates[] = "CHANGE `$key` `$key` $value";
					}
				} else {
					$updates[] = "ADD `$key` $value";
				}
			}
		}

		//升级处理
		if(!empty($updates)) {
			$usql = "ALTER TABLE ".tname($newtable)." ".implode(', ', $updates);
			if(!$_SGLOBAL['db']->query($usql, 'SILENT')) {
				show_msg('['.$i.'/'.count($newtables).']升级表 '.tname($newtable).' 出错,请手工执行以下升级语句后,再重新运行本升级程序:<br><br><b>升级SQL语句</b>:<div style=\"position:absolute;font-size:11px;font-family:verdana,arial;background:#EBEBEB;padding:0.5em;\">'.shtmlspecialchars($usql)."</div><br><b>Error</b>: ".$_SGLOBAL['db']->error()."<br><b>Errno.</b>: ".$_SGLOBAL['db']->errno());
			} else {
				$msg = '['.$i.'/'.count($newtables).']升级表 '.tname($newtable).' 完成';
			}
		} else {
			$msg = '['.$i.'/'.count($newtables).']检查表 '.tname($newtable).' 完成，不需升级';
		}
	}

	//处理下一个
	$next = '?step=sql&i='.($_GET['i']+1);
	show_msg($msg, $next);

} elseif ($_GET['step'] == 'data') {
	
	if(empty($_GET['op'])) $_GET['op'] = 'setting';
	
	if($_GET['op'] == 'setting') {
		
		$nextop = 'usergroup';
		//7.0->7.5
		$query = $_SGLOBAL['db']->query("SELECT value FROM ".tname('settings')." WHERE variable='commstatus'");
		$havecommstatus = $_SGLOBAL['db']->fetch_array($query);
		if(empty($havecommstatus)) {
			$datas = array(
				"'commstatus', '1'",
				"'commicon', 'logo.gif'",
				"'commdefault', '我也来评论！'",
				"'commorderby', '0'",
				"'commfloornum', '2'",
				"'commshowip', '1'",
				"'commshowlocation', '1'",
				"'commdebate', '0'",
				"'commdivide', '10'",
				"'commviewnum', '50'",
				"'commhidefloor', '0'",
				"'lastposttime', '30'",
				"'makehtml', '0'",
				"'itempost', 'flower'",
				"'post_flower', '0'",
				"'post_egg', '0'",
				"'post_flower_egg', '0'",
				"'perpage', '20'",
				"'prehtml', 'info'"
			);
		}
		$sitekey = mksitekey();
		$datas[] = "'template', 'default'";
		$datas[] = "'sitekey', '$sitekey'";
		
		//添加站点是否开放注册默认值
		$query = $_SGLOBAL['db']->query("SELECT value FROM ".tname('settings')." WHERE variable='allowregister'");
		$haveallowregister = $_SGLOBAL['db']->fetch_array($query);
		if(empty($haveallowregister)) {
			$datas[] = "'allowregister', '1'";
		}

		$_SGLOBAL['db']->query("REPLACE INTO ".tname('settings')." (`variable`, `value`) VALUES (".implode('),(', $datas).")");
		
		show_msg("[数据升级] 系统设置 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		
	} elseif($_GET['op'] == 'usergroup') {
		
		$nextop = 'channel';
		
		$needupdate = 0;
		$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('usergroups')." LIMIT 1");
		if($value = @$_SGLOBAL['db']->fetch_array($query)) {
			if(empty($value['grouptitle'])) {
				$needupdate = 1;	//6.0.1
			} elseif(empty($value['allowview'])) {
				$needupdate = 2;	//7.0
			}
		} else {
			$needupdate = 4;	//重建
		}

		if($needupdate) {
			//usergroups
			$datas = array(
				"'1', '管理员', '-1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'",
				"'2', '游客组', '-1', '1', '0', '1', '0', '0', '1', '1', '0', '0', '0', '1', '0', '0', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'3', '禁止访问', '-1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'4', '禁止发言', '-1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'10', '贵宾VIP', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'11', '受限会员', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '-999999999', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'12', '初级会员', '0', '1', '1', '1', '0', '0', '1', '1', '0', '0', '0', '1', '0', '0', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'13', '中级会员', '0', '1', '1', '1', '1', '0', '1', '1', '1', '0', '0', '1', '0', '1', '1', '1', '0', '300', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'",
				"'14', '高级会员', '0', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1', '1', '1', '1', '0', '800', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'"
			);
			$_SGLOBAL['db']->query("TRUNCATE TABLE ".tname('usergroups'));
			$_SGLOBAL['db']->query("INSERT INTO ".tname('usergroups').
									" (groupid, grouptitle, system, allowview, allowpost, allowcomment, allowgetattach, allowpostattach, allowvote, allowsearch, allowtransfer, allowpushin, allowpushout, allowdirectpost, allowanonymous, allowhideip, allowhidelocation, allowclick, closeignore, explower, managemodpost, manageeditpost, managedelpost, managefolder, managemodcat, manageeditcat, managedelcat, managemodrobot, manageuserobot, manageeditrobot, managedelrobot, managemodrobotmsg, manageundelete, manageadmincp, manageviewlog, managesettings, manageusergroups, manageannouncements, managead, manageblocks, managebbs, managebbsforums, managethreads, manageuchome, managemodels, managechannel, managemember, managehtml, managecache, managewords, manageattachmenttypes, managedatabase, managetpl, managecrons, managecheck, managecss, managefriendlinks, manageprefields, managesitemap, manageitems, managecomments, manageattachments, managetags, managereports, managepolls, managecustomfields, managestyles, managestyletpl, managedelmembers, manageclick, managecredit, managepostnews) ".
									" VALUES (".implode('),(', $datas).")");
		}

		if($needupdate == 1) {

			//修改用户组
			if(discuz_exists()) {
				dbconnect(1);
				$uids = array();
				$query = $_SGLOBAL['db_bbs']->query("SELECT uid FROM ".tname('members', 1)." WHERE adminid='1'");
				while($value = $_SGLOBAL['db']->fetch_array($query)) {
					$uids[] = $value['uid'];
				}
				$_SGLOBAL['db']->query('UPDATE '.tname('members').' SET groupid=\'12\' WHERE uid NOT IN ('.simplode($uids).')');
				$_SGLOBAL['db']->query('UPDATE '.tname('members').' SET groupid=\'1\' WHERE uid IN ('.simplode($uids).')');
			}

		} elseif($needupdate == 2) {
			
			$_SGLOBAL['db']->query('UPDATE '.tname('members').' SET groupid=\'12\' WHERE groupid != \'1\'');

		}
		
		show_msg("[数据升级] 用户组 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		
	} elseif($_GET['op'] == 'channel') {
		
		$nextop = 'cron';
		
		$query = $_SGLOBAL['db']->query('SELECT * FROM '.tname('channels').' WHERE nameid=\'top\'');
		if(!$value = $_SGLOBAL['db']->fetch_array($query)) {
			$datas = array(
				"'top', '排行榜', 'system', '10', '1'"
			);	
			$_SGLOBAL['db']->query("INSERT INTO ".tname('channels')." (`nameid`, `name`, `type`, `displayorder`, `status`) VALUES (".implode('),(', $datas).")");
			//删除多余频道
			deletetable('channels', 'nameid IN (\'link\', \'blog\', \'video\', \'topic\', \'file\', \'image\', \'group\', \'goods\')');
		}
		
		show_msg("[数据升级] 频道 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		
	} elseif($_GET['op'] == 'cron') {
		
		$nextop = 'click';
		//删除多余计划任务
		deletetable('crons', 'filename IN (\'cleanvisitors.php\', \'cleantracks.php\', \'updatespaceviewnum.php\', \'makearchiver.php\')');
		show_msg("[数据升级] 计划任务 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		
	} elseif($_GET['op'] == 'click') {

		$nextop = 'credit';
		//表态
		$query = $_SGLOBAL['db']->query("SELECT groupid FROM ".tname('clickgroup')." LIMIT 1");
		if(!$value = $_SGLOBAL['db']->fetch_array($query)) {
			$datas = array(
				"'1', '心情', 'topmood.jpg', '1', '0', '1', '1', '0', '1', 'spaceitems', '0', '1'",
				"'2', 'Digg', '', '0', '0', '0', '1', '0', '1', 'spaceitems', '0', '1'",
				"'3', '回复打分', '', '0', '0', '0', '1', '0', '1', 'spacecomments', '0', '1'",
				"'4', '事件或人物打分', '', '0', '0', '0', '1', '0', '1', 'spaceitems', '0', '1'",
				"'5', '内容质量打分', '', '0', '0', '0', '1', '0', '1', 'spaceitems', '0', '1'"
			);	
			$_SGLOBAL['db']->query("INSERT INTO ".tname('clickgroup')." (groupid, grouptitle, icon, allowspread, spreadtime, allowtop, status, allowrepeat, allowguest, idtype, mid, system) VALUES (".implode('),(', $datas).")");
		} else {
			deletetable('clickgroup', array('system'=>'1', 'groupid'=>'6'));
			deletetable('clickgroup', array('system'=>'1', 'groupid'=>'7'));
		}
		$query = $_SGLOBAL['db']->query("SELECT clickid FROM ".tname('click')." LIMIT 1");
		if(!$value = $_SGLOBAL['db']->fetch_array($query)) {
			$datas = array(
				"'1', '感动', '19.gif', '0', '1', '0', '1', '', '0'",
				"'2', '同情', '20.gif', '0', '1', '0', '1', '', '0'",
				"'3', '无聊', '09.gif', '0', '1', '0', '1', '', '0'",
				"'4', '愤怒', '02.gif', '0', '1', '0', '1', '', '0'",
				"'5', '搞笑', '08.gif', '0', '1', '0', '1', '', '0'",
				"'6', '难过', '15.gif', '0', '1', '0', '1', '', '0'",
				"'7', '高兴', '12.gif', '0', '1', '0', '1', '', '0'",
				"'8', '路过', '14.gif', '0', '1', '0', '1', '', '0'",
				"'9', '支持', '', '0', '2', '0', '1', '', '1'",
				"'10', '反对', '', '1', '2', '0', '1', '', '1'",
				"'11', '-5', '', '0', '4', '-5', '1', '', '0'",
				"'12', '-4', '', '1', '4', '-4', '1', '', '0'",
				"'13', '-3', '', '2', '4', '-3', '1', '', '0'",
				"'14', '-2', '', '3', '4', '-2', '1', '', '0'",
				"'15', '-1', '', '4', '4', '-1', '1', '', '0'",
				"'16', '0', '', '5', '4', '0', '1', '', '0'",
				"'17', '1', '', '6', '4', '1', '1', '', '0'",
				"'18', '2', '', '7', '4', '2', '1', '', '0'",
				"'19', '3', '', '8', '4', '3', '1', '', '0'",
				"'20', '4', '', '9', '4', '4', '1', '', '0'",
				"'21', '5', '', '10', '4', '5', '1', '', '0'",
				"'22', '-5', '', '0', '5', '-5', '1', '', '0'",
				"'23', '-4', '', '1', '5', '-4', '1', '', '0'",
				"'24', '-3', '', '2', '5', '-3', '1', '', '0'",
				"'25', '-2', '', '3', '5', '-2', '1', '', '0'",
				"'26', '-1', '', '4', '5', '-1', '1', '', '0'",
				"'27', '0', '', '5', '5', '0', '1', '', '0'",
				"'28', '1', '', '6', '5', '1', '1', '', '0'",
				"'29', '2', '', '7', '5', '2', '1', '', '0'",
				"'30', '3', '', '8', '5', '3', '1', '', '0'",
				"'31', '4', '', '9', '5', '4', '1', '', '0'",
				"'32', '5', '', '10', '5', '5', '1', '', '0'",
				"'33', '支持', 'icon8.gif', '0', '3', '0', '1', '', '1'",
				"'34', '反对', 'icon9.gif', '1', '3', '0', '1', '', '1'"
			);	
			$_SGLOBAL['db']->query("INSERT INTO ".tname('click')." (`clickid`, `name`, `icon`, `displayorder`, `groupid`, `score`, `status`, `filename`, `system`) VALUES (".implode('),(', $datas).")");
		} else {
			deletetable('click', array('system'=>'1', 'clickid'=>'35'));
			deletetable('click', array('system'=>'1', 'clickid'=>'36'));
			deletetable('click', array('system'=>'1', 'clickid'=>'37'));
			deletetable('click', array('system'=>'1', 'clickid'=>'38'));
		}
		show_msg("[数据升级] 表态数据 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
	
	} elseif($_GET['op'] == 'credit') {

		$nextop = 'clickdata';
		//积分
		$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('creditrule')." LIMIT 1");
		if(!$value = $_SGLOBAL['db']->fetch_array($query)) {
			$datas = array(
				"'1', '发表信息', 'postinfo', '1', '0', '3', '1', '0', '10', '10'",
				"'2', '评论', 'postcomment', '1', '0', '20', '1', '0', '1', '1'",
				"'3', '上传', 'postattach', '1', '0', '3', '1', '0', '10', '10'",
				"'4', '投票', 'postvote', '1', '0', '10', '1', '0', '1', '1'",
				"'5', '点击', 'postclick', '1', '0', '20', '1', '0', '1', '1'",
				"'6', '设置头像', 'setavatar', '0', '0', '1', '1', '0', '10', '10'",
				"'7', '每天登陆', 'daylogin', '1', '0', '1', '1', '0', '10', '10'",
				"'9', '举报', 'report', '1', '0', '10', '1', '0', '1', '1'",
				"'10', '删除信息', 'delinfo', '4', '0', '0', '2', '0', '10', '10'",
				"'11', '删除评论', 'delcomment', '4', '0', '0', '2', '0', '10', '10'",
				"'12', '搜索', 'seach', '4', '0', '0', '0', '0', '1', '1'",
				"'13', '匿名评论', 'anonymous', '4', '0', '0', '0', '0', '5', '1'",
				"'14', '隐藏ip', 'hideip', '4', '0', '0', '0', '0', '5', '1'",
				"'15', '隐藏位置', 'hidelocation', '4', '0', '0', '0', '0', '5', '1'",
				"'16', '浏览', 'view', '4', '0', '0', '0', '0', '0', '1'",
				"'17', '下载', 'download', '4', '0', '0', '0', '0', '5', '1'"
			);	
			$_SGLOBAL['db']->query("INSERT INTO ".tname('creditrule')." (`rid`, `rulename`, `action`, `cycletype`, `cycletime`, `rewardnum`, `rewardtype`, `norepeat`, `credit`, `experience`) VALUES (".implode('),(', $datas).")");
		}
		show_msg("[数据升级] 积分数据 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		
	} elseif($_GET['op'] == 'clickdata') {

		$nextop = 'comment';
		//表态转换
		$tableinfo = array();
		if($_SGLOBAL['db']->version() > '4.1') {
			$query = $_SGLOBAL['db']->query("SHOW FULL COLUMNS FROM ".tname('spacecomments'), 'SILENT');
		} else {
			$query = $_SGLOBAL['db']->query("SHOW COLUMNS FROM ".tname('spacecomments'), 'SILENT');
		}
		while($field = @$_SGLOBAL['db']->fetch_array($query)) {
			$tableinfo[$field['Field']] = $field;
		}
		
		//6.0.1，7.0 -> 7.5
		if(!empty($tableinfo['rates'])) {	//需要转换
			$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query("SELECT COUNT(*) FROM ".tname('spacecomments')." WHERE rates <> '0'"), 0);
			if($count) {
				$clickidarr = array('-5' => '22', '-4' => '23', '-3' => '24', '-2' => '25', '-1' => '26', '0' => '27', '1' => '28', '2' => '29', '3' => '30', '4' => '31', '5' => '32');	//评分对应的clickid
				$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('spacecomments')." WHERE rates <> '0' ORDER BY itemid, dateline LIMIT 50");
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					//入记录表(1) - 按照分值不同进入items表指定id 累加(50) - 更新热度(50) - 扣除items中的回复数(50) - 删除评论表对应条目(1)
					$value['rates'] = empty($clickidarr[$value['rates']]) ? '0' : $value['rates'];	//修改默认分值
					$setclickuservalue[] = "'$value[authorid]', '$value[author]', '$value[itemid]', 'items', '{$clickidarr[$value['rates']]}', '5', '$value[dateline]', '$value[ip]'";	//uid, username, id, idtype, clickid, groupid, dateline, ip
					$setitemsvalue[$value['itemid']]['click_'.$clickidarr[$value['rates']]] += 1;	//click_id 值
					$setitemsvalue[$value['itemid']]['hot'] += 1;	//热度值 回复数量
					$setitemsvalue[$value['itemid']]['replynum'] += 1;	//热度值 回复数量
					$setcommentsvalue[] = $value['cid'];	//评论表id
				}
				
				$_SGLOBAL['db']->query("INSERT INTO ".tname('clickuser')." (uid, username, id, idtype, clickid, groupid, dateline, ip) VALUES (".implode('),(', $setclickuservalue).")");	//clickuser表插入数据
				foreach ($setitemsvalue as $k => $v) {
					$setvalue = $comma = '';
					foreach ($v as $key => $value) {
						$operator = $key == 'replynum' ? '-' : '+';
						$setvalue .= $comma.$key.'='.$key.$operator.$value;
						$comma = ', ';
					}
					$_SGLOBAL['db']->query("UPDATE ".tname('spaceitems')." SET $setvalue WHERE itemid='$k'", 0);
				}
				$_SGLOBAL['db']->query("DELETE FROM ".tname('spacecomments')." WHERE cid IN (".simplode($setcommentsvalue).")", 0);
				$msg = '[数据升级] 表态转换 数据升级中，还剩'.$count.'条数据...';
				show_msg($msg, 'update.php?step=data&op=clickdata');
			} else {
				show_msg("[数据升级] 表态转换 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
			}
		} else {
			show_msg("[数据升级] 表态转换 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		}

	} elseif($_GET['op'] == 'comment') {
		
		$nextop = 'spacenews';
		//评论转换（引用、状态）
		$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query("SELECT COUNT(*) FROM ".tname('spacecomments')." WHERE `status` = '0'"), 0);
		$itemids = array();
		if($count) {
			$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('spacecomments')." WHERE `status` = '0' LIMIT 50");
			while ($value = $_SGLOBAL['db']->fetch_array($query)) {
				$patternarr = array(
					'/\<blockquote class="xspace-quote"\>/',
					'/\<\/blockquote\>/'
				);
				$replacementarr = array(
					'<div class="quote"><blockquote>',
					'</blockquote></div>',
				);
				$value['message'] = '<div class="new">'.preg_replace($patternarr, $replacementarr, $value['message']).'</div>';
				$value['message'] = saddslashes($value['message']);
				$_SGLOBAL['db']->query("UPDATE ".tname('spacecomments')." SET message='$value[message]', `status`='1' WHERE cid = '$value[cid]'", 0);
				if(empty($value['uid'])) $itemids[$value['cid']] = $value['itemid'];
			}
			if(!empty($itemids)) {
				$query = $_SGLOBAL['db']->query('SELECT * FROM '.tname('spaceitems').' WHERE `itemid` IN ('.simplode($itemids).')');
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					$rsitemids[$value['itemid']] = $value['uid'];
				}
				foreach($itemids as $key => $value) {
					if(!empty($rsitemids[$value])) {
						updatetable('spacecomments', array('uid' => $rsitemids[$value]), array('cid' => $value[$key]));
					}
				}
			}
			$msg = '[数据升级] 评论转换 数据升级中，还剩'.$count.'条数据...';
			show_msg($msg, 'update.php?step=data&op=comment');
		} else {
			show_msg("[数据升级] 评论转换 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		}

	} elseif($_GET['op'] == 'spacenews') {
		
		$nextop = 'model';
		//资讯投稿转换（引用、状态）
		$tableinfo = array();
		if($_SGLOBAL['db']->version() > '4.1') {
			$query = $_SGLOBAL['db']->query("SHOW FULL COLUMNS FROM ".tname('spaceitems'), 'SILENT');
		} else {
			$query = $_SGLOBAL['db']->query("SHOW COLUMNS FROM ".tname('spaceitems'), 'SILENT');
		}
		while($field = @$_SGLOBAL['db']->fetch_array($query)) {
			$tableinfo[$field['Field']] = $field;
		}
		
		if(!empty($tableinfo['folder'])) {	//需要转换
			$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query("SELECT COUNT(*) FROM ".tname('spaceitems')." WHERE `folder` > '1'"), 0);
			if($count) {
				$query = $_SGLOBAL['db']->query('SELECT i.*, ii.* FROM '.tname('spaceitems').' ii LEFT JOIN '.tname('spacenews').' i ON i.itemid=ii.itemid WHERE `folder` > \'1\'');
				$oldid = '';
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					if($value['itemid'] != $oldid) {
						$item['oitemid'] = $oldid = intval($value['itemid']);
						$item['catid'] = intval($value['catid']);
						$item['uid'] = intval($value['uid']);
						$item['username'] = intval($value['username']);
						$item['subject'] = $value['subject'];
						$item['type'] = $value['type'];
						$item['dateline'] = $value['dateline'];
						$item['lastpost'] = $value['lastpost'];
						$item['hash'] = $value['hash'];
						$item['haveattach'] = $value['haveattach'];
						$item['picid'] = $value['picid'];
						$item['fromtype'] = $value['fromtype'];
						$item['fromid'] = $value['fromid'];
						$item['folder'] = $value['folder'];
						$itemmsg['itemid'] = inserttable('postitems', saddslashes($item), 1);
					}
					 
		  			$itemmsg['onid'] = intval($value['onid']);
		  			$itemmsg['message'] = $value['message'];
		  			$itemmsg['relativetags'] = $value['relativetags'];
		  			$itemmsg['postip'] = $value['postip'];
		  			$itemmsg['relativeitemids'] = $value['relativeitemids'];
		  			$itemmsg['customfieldid'] = $value['customfieldid'];
		  			$itemmsg['customfieldtext'] = $value['customfieldtext'];
		  			$itemmsg['includetags'] = $value['includetags'];
		  			$itemmsg['newsauthor'] = $value['newsauthor'];
					$itemmsg['newsfrom'] = $value['newsfrom'];
					$itemmsg['newsfromurl'] = $value['newsfromurl'];
		  			$itemmsg['pageorder'] = intval($value['pageorder']);
					
					inserttable('postmessages', saddslashes($itemmsg));
					deletetable('spaceitems', array('itemid'=>$value['itemid']));
					deletetable('spacenews', array('itemid'=>$value['itemid']));
		
				}
				$msg = '[数据升级] 资讯投稿转换 数据升级中，还剩'.$count.'条数据...';
				show_msg($msg, 'update.php?step=data&op=spacenews');
			} else {
				show_msg("[数据升级] 资讯投稿转换 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
			}
		} else {
			show_msg("[数据升级] 资讯投稿转换 全部结束，进入下一步", 'update.php?step=data&op='.$nextop);
		}
	
	} elseif($_GET['op'] == 'model') {
		
		$nextop = 'end';
		if(empty($_GET['do'])) $_GET['do'] = 'category';

		$cachefile = S_ROOT.'./data/system/update_model.cache.php';
		if(!include_once($cachefile)) {
			model_cache();
		}
		
		if(!empty($_SGLOBAL['updatemodel']['model'])) {

			if($_GET['do'] == 'category') {

				$nextdo = 'comment';
				
				$query = $_SGLOBAL['db']->query("SHOW TABLES LIKE '$_SC[tablepre]%'");
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					$values = array_values($value);
					if(!strexists($values[0], 'cache')) {
						$oldtables[] = $values[0];
					}
				}
				
				//模型分类转换
				$mid = array_slice($_SGLOBAL['updatemodel']['category'], 0, 1);
				if(!empty($mid[0])) {
					$modelarr = $_SGLOBAL['updatemodel']['model'][$mid[0]];
					if(in_array(tname($modelarr['modelname'].'categories'), $oldtables)) {
						$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'categories')), 0);
						if($count) {
							$query = $_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'categories'));
							while ($value = $_SGLOBAL['db']->fetch_array($query)) {
								$setarr = array(
									'name' => $value['name'],
									'note' => $value['note'],
									'displayorder' => $value['displayorder'],
									'url' => $value['url'],
									'subcatid' => $value['subcatid'],
									'type' => $modelarr['modelname']
								);
								$value['newcatid'] = inserttable('categories', saddslashes($setarr), 1);
								updatetable($modelarr['modelname'].'items', array('catid'=>$value['newcatid']), array('catid'=>$value['catid']));
								deletetable($modelarr['modelname'].'categories', array('catid'=>$value['catid']));
								$catarr[$value['catid']] = $value;
							}
							
							foreach($catarr as $value) {
								$setarr = $newsubarr = array();
								if(!empty($value['upid'])) {
									$setarr['upid'] = $catarr[$value['upid']]['newcatid'];
								}
								if($value['catid'] == $value['subcatid']) {
									$setarr['subcatid'] = $value['newcatid'];
								} else {
									$subarr = explode(',', $value['subcatid']);
									foreach($subarr as $val) {
										$newsubarr[] = $catarr[$val]['newcatid'];
									}
									$setarr['subcatid'] = implode(',', $newsubarr);
								}
								updatetable('categories', $setarr, array('catid'=>$value['newcatid']));
							}
							
						}
					}
					unset($_SGLOBAL['updatemodel']['category'][$modelarr['mid']]);
					$text = '$_SGLOBAL[\'updatemodel\']='.arrayeval($_SGLOBAL['updatemodel']).";";
					writefile($cachefile, $text, 'php');
					show_msg('[数据升级] '.$modelarr['modelalias'].'模型分类转换 还剩'.count($_SGLOBAL['updatemodel']['category']).'个模型，进入下一步', 'update.php?step=data&op=model&do=category');
				} else {
					show_msg('[数据升级] 模型分类转换 全部结束，进入下一步', 'update.php?step=data&op=model&do='.$nextdo);
				}

			} elseif($_GET['do'] == 'comment') {
				
				$nextdo = 'folder';
				
				$query = $_SGLOBAL['db']->query("SHOW TABLES LIKE '$_SC[tablepre]%'");
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					$values = array_values($value);
					if(!strexists($values[0], 'cache')) {
						$oldtables[] = $values[0];
					}
				}
				
				//模型评论转换
				$mid = array_slice($_SGLOBAL['updatemodel']['comment'], 0, 1);
				if(!empty($mid[0])) {
					$modelarr = $_SGLOBAL['updatemodel']['model'][$mid[0]];
					if(in_array(tname($modelarr['modelname'].'comments'), $oldtables)) {
						$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'comments').' c, '.tname($modelarr['modelname'].'items').' i WHERE c.itemid = i.itemid'), 0);
						if($count) {
							$query = $_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'comments').' c, '.tname($modelarr['modelname'].'items').' i WHERE c.itemid = i.itemid  LIMIT 30');
							while ($value = $_SGLOBAL['db']->fetch_array($query)) {
								$patternarr = array(
									'/\<blockquote class="xspace-quote"\>/',
									'/\<\/blockquote\>/'
								);
								$replacementarr = array(
									'<div class="quote"><blockquote>',
									'</blockquote></div>',
								);
								$value['message'] = '<div class="new">'.preg_replace($patternarr, $replacementarr, $value['message']).'</div>';
								$setarr = array(
									'itemid' => $value['itemid'],
									'uid' => $value['uid'],
									'authorid' => $value['authorid'],
									'author' => $value['author'],
									'ip' => $value['ip'],
									'dateline' => $value['dateline'],
									'message' => $value['message'],
									'type' => $modelarr['modelname'],
									'status' => '1'
								);
								inserttable('spacecomments', saddslashes($setarr));
								deletetable($modelarr['modelname'].'comments', array('cid'=>$value['cid']));
							}
							show_msg('[数据升级] '.$modelarr['modelalias'].'模型评论转换 还剩'.$count.'条数据，进入下一步', 'update.php?step=data&op=model&do=comment');
						}
					}
					unset($_SGLOBAL['updatemodel']['comment'][$modelarr['mid']]);
					$text = '$_SGLOBAL[\'updatemodel\']='.arrayeval($_SGLOBAL['updatemodel']).";";
					writefile($cachefile, $text, 'php');
					show_msg('[数据升级] '.$modelarr['modelalias'].'模型评论转换 还剩'.count($_SGLOBAL['updatemodel']['comment']).'个模型，进入下一步', 'update.php?step=data&op=model&do=comment');
				} else {
					show_msg('[数据升级] 模型评论转换 全部结束，进入下一步', 'update.php?step=data&op=model&do='.$nextdo);
				}
				
			} elseif($_GET['do'] == 'folder') {
				
				$nextdo = 'hot';
				
				$query = $_SGLOBAL['db']->query("SHOW TABLES LIKE '$_SC[tablepre]%'");
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					$values = array_values($value);
					if(!strexists($values[0], 'cache')) {
						$oldtables[] = $values[0];
					}
				}
				
				//模型投稿转换
				$mid = array_slice($_SGLOBAL['updatemodel']['folder'], 0, 1);
				if(!empty($mid[0])) {
					$modelarr = $_SGLOBAL['updatemodel']['model'][$mid[0]];
					if(in_array(tname($modelarr['modelname'].'folders'), $oldtables)) {

						$count = $_SGLOBAL['db']->result($_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'folders')), 0);
						if($count) {
							$query = $_SGLOBAL['db']->query('SELECT * FROM '.tname($modelarr['modelname'].'folders').' LIMIT 30');
							while ($value = $_SGLOBAL['db']->fetch_array($query)) {
								$setarr = array(
									'mid' => $modelarr['mid'],
									'uid' => $value['uid'],
									'subject' => $value['subject'],
									'message' => $value['message'],
									'dateline' => $value['dateline'],
									'folder' => $value['folder']
								);
								inserttable('modelfolders', saddslashes($setarr));
								deletetable($modelarr['modelname'].'folders', array('itemid'=>$value['itemid']));
							}
							show_msg('[数据升级] '.$modelarr['modelalias'].'模型投稿转换 还剩'.$count.'条数据，进入下一步', 'update.php?step=data&op=model&do=folder');
						}
						
					}
					unset($_SGLOBAL['updatemodel']['folder'][$modelarr['mid']]);
					$text = '$_SGLOBAL[\'updatemodel\']='.arrayeval($_SGLOBAL['updatemodel']).";";
					writefile($cachefile, $text, 'php');
					show_msg('[数据升级] '.$modelarr['modelalias'].'模型投稿转换 还剩'.count($_SGLOBAL['updatemodel']['folder']).'个模型，进入下一步', 'update.php?step=data&op=model&do=folder');
				} else {
					show_msg('[数据升级] 模型投稿转换 全部结束，进入下一步', 'update.php?step=data&op=model&do='.$nextdo);
				}

			} elseif($_GET['do'] == 'hot') {
				
				$nextdo = 'end';
				
				$query = $_SGLOBAL['db']->query("SHOW TABLES LIKE '$_SC[tablepre]%'");
				while ($value = $_SGLOBAL['db']->fetch_array($query)) {
					$values = array_values($value);
					if(!strexists($values[0], 'cache')) {
						$oldtables[] = $values[0];
					}
				}
				
				//模型投稿转换
				$mid = array_slice($_SGLOBAL['updatemodel']['hot'], 0, 1);
				if(!empty($mid[0])) {
					$modelarr = $_SGLOBAL['updatemodel']['model'][$mid[0]];
					if(in_array(tname($modelarr['modelname'].'items'), $oldtables)) {

						$tableinfo = array();
						if($_SGLOBAL['db']->version() > '4.1') {
							$query = $_SGLOBAL['db']->query("SHOW FULL COLUMNS FROM ".tname($modelarr['modelname'].'items'), 'SILENT');
						} else {
							$query = $_SGLOBAL['db']->query("SHOW COLUMNS FROM ".tname($modelarr['modelname'].'items'), 'SILENT');
						}
						while($field = @$_SGLOBAL['db']->fetch_array($query)) {
							$tableinfo[$field['Field']] = $field;
						}

						if(empty($tableinfo['hot'])) {
							@$_SGLOBAL['db']->query('ALTER TABLE '.tname($modelarr['modelname'].'items').' ADD COLUMN hot mediumint(8) unsigned NOT NULL DEFAULT \'0\'');
						}
							
					}
					unset($_SGLOBAL['updatemodel']['hot'][$modelarr['mid']]);
					$text = '$_SGLOBAL[\'updatemodel\']='.arrayeval($_SGLOBAL['updatemodel']).";";
					writefile($cachefile, $text, 'php');
					show_msg('[数据升级] '.$modelarr['modelalias'].'模型字段转换 还剩'.count($_SGLOBAL['updatemodel']['hot']).'个模型，进入下一步', 'update.php?step=data&op=model&do=hot');
				} else {
					show_msg('[数据升级] 模型字段转换 全部结束，进入下一步', 'update.php?step=data&op=model&do='.$nextdo);
				}

			} else {

				//结束
				show_msg('[数据升级] 模型转换 全部结束，进入下一步', 'update.php?step=data&op='.$nextop);
				
			}
			
		}
		
		show_msg('[数据升级] 模型转换 全部结束，进入下一步', 'update.php?step=data&op='.$nextop);

	} else {
		//结束
		$next = 'update.php?step=delete';
		show_msg("数据库数据升级完毕，进入下一步数据库结构清理操作", $next);
	}
							
} elseif ($_GET['step'] == 'delete') {

	//检查需要删除的字段
	//老表集合
	$oldtables = array();
	$query = $_SGLOBAL['db']->query("SHOW TABLES LIKE '$_SC[tablepre]%'");
	while ($value = $_SGLOBAL['db']->fetch_array($query)) {
		$values = array_values($value);
		if(!strexists($values[0], 'cache')) {
			$oldtables[] = $values[0];//分表、缓存
		}
	}

	//新表集合
	$sql = sreadfile($sqlfile);
	preg_match_all("/CREATE\s+TABLE\s+supe\_(.+?)\s+\((.+?)\)\s+(TYPE|ENGINE)\=/is", $sql, $matches);
	$newtables = empty($matches[1])?array():$matches[1];
	$newsqls = empty($matches[0])?array():$matches[0];

	//需要删除的表
	$deltables = array();
	$delcolumns = array();

	//老的有，新的没有
	foreach ($oldtables as $tname) {
		$tname = substr($tname, strlen($_SC['tablepre']));
		if(in_array($tname, $newtables)) {
			//比较字段是否多余
			$query = $_SGLOBAL['db']->query("SHOW CREATE TABLE ".tname($tname));
			$cvalue = $_SGLOBAL['db']->fetch_array($query);
			$oldcolumns = getcolumn($cvalue['Create Table']);

			//新的
			$i = array_search($tname, $newtables);
			$newcolumns = getcolumn($newsqls[$i]);

			//老的有，新的没有的字段
			foreach ($oldcolumns as $colname => $colstruct) {
				if(!strexists($colname, 'field_')) {
					if($colname == 'PRIMARY') {
						//关键字
						if(empty($newcolumns[$colname])) {
							$delcolumns[$tname][] = 'PRIMARY';
						}
					} elseif($colname == 'KEY') {
						//索引
						foreach ($colstruct as $key_index => $key_value) {
							if(empty($newcolumns[$colname][$key_index])) {
								$delcolumns[$tname]['KEY'][$key_index] = $key_value;
							}
						}
					} else {
						//普通字段
						if(empty($newcolumns[$colname])) {
							if(in_array($tname, array('spacecomments', 'spaceitems')) && preg_match("/^click_/i", $colname)) {
								continue;
							}
							$delcolumns[$tname][] = $colname;
						}
					}
				}
			}
		} else {
			if(!preg_match("/(items|message|rates|comments|categories|folders)$/i", $tname)) $deltables[] = $tname;
		}
	}

	//显示
	show_header();
	echo '<form method="post" action="update.php?step=delete">';
	echo '<input type="hidden" name="formhash" value="'.formhash().'">';

	//删除表
	$deltablehtml = '';
	if($deltables) {
		$deltablehtml .= '<table>';
		foreach ($deltables as $tablename) {
			$deltablehtml .= "<tr><td><input type=\"checkbox\" name=\"deltables[$tablename]\" value=\"1\"></td><td>{$_SC['tablepre']}$tablename</td></tr>";
		}
		$deltablehtml .= '</table>';
		echo "<p>以下 数据表 与标准数据库相比是多余的:<br>您可以根据需要自行决定是否删除</p>$deltablehtml";
	}

	//删除字段
	$delcolumnhtml = '';
	if($delcolumns) {
		$delcolumnhtml .= '<table>';
		foreach ($delcolumns as $tablename => $cols) {
			foreach ($cols as $col) {
				if (is_array($col)) {
					foreach ($col as $index => $indexvalue) {
						$delcolumnhtml .= "<tr><td><input type=\"checkbox\" name=\"delcols[$tablename][KEY][$index]\" value=\"1\"></td><td>{$_SC['tablepre']}$tablename</td><td>索引 $index $indexvalue</td></tr>";
					}
				} elseif($col == 'PRIMARY') {
					$delcolumnhtml .= "<tr><td><input type=\"checkbox\" name=\"delcols[$tablename][PRIMARY]\" value=\"1\"></td><td>{$_SC['tablepre']}$tablename</td><td>主键 PRIMARY</td></tr>";
				} else {
					$delcolumnhtml .= "<tr><td><input type=\"checkbox\" name=\"delcols[$tablename][$col]\" value=\"1\"></td><td>{$_SC['tablepre']}$tablename</td><td>字段 $col</td></tr>";
				}
			}
		}
		$delcolumnhtml .= '</table>';

		echo "<p>以下 字段 与标准数据库相比是多余的:<br>您可以根据需要自行决定是否删除</p>$delcolumnhtml";
	}

	if(empty($deltables) && empty($delcolumns)) {
		echo "<p>与标准数据库相比，没有需要删除的数据表和字段</p><a href=\"?step=cache\">请点击进入下一步</a></p>";
	} else {
		echo "<p><font style=\"color:#F00;\">如果您需要将原X-Space转换到UCenter Home，请先转换后再删除这些数据。</font></p><p><input type=\"submit\" name=\"delsubmit\" value=\"提交删除\"></p><p>您也可以忽略多余的表和字段<br><a href=\"?step=cache\">直接进入下一步</a></p>";
	}
	echo '<input type="hidden" name="formhash" value="'.formhash().'"></form>';

	show_footer();
	exit();

} elseif ($_GET['step'] == 'cache') {
		
	//更新缓存
	include_once(S_ROOT.'./function/cache.func.php');

	updatesettingcache();	//系统设置缓存
	updategroupcache();		//用户组缓存
	updateadcache();		//广告缓存
	updatecronscache();		//crons列表
	updatecroncache();		//计划任务
	updatecategorycache();	//分类
	updatecensorcache();	//缓存语言屏蔽
	click_cache();			//缓存表态
	creditrule_cache();		//缓存积分
	postnews_cache;			//缓存信息推送
	model_cache();
	foreach($_SGLOBAL['updatemodel']['cache'] as $value) {
		updatemodel('mid', $value);
	}
	
	if(discuz_exists()) {
		updatebbssetting();	//缓存论坛设置
		updatebbsstyle();	//缓存论坛风格设置
		updatebbsbbcode();	//缓存论坛bbcode/smiles
	}
	
	//写log
	if(@$fp = fopen($lockfile, 'w')) {
		fwrite($fp, 'SupeSite');
		fclose($fp);
	}

	show_msg('升级完成，为了您的数据安全，避免重复升级，请登录FTP删除本文件!');
}


//正则匹配,获取字段/索引/关键字信息
function getcolumn($creatsql) {

	preg_match("/\((.+)\)/is", $creatsql, $matchs);

	$cols = explode("\n", $matchs[1]);
	$newcols = array();
	foreach ($cols as $value) {
		$value = trim($value);
		if(empty($value)) continue;
		$value = remakesql($value);//特使字符替换
		if(substr($value, -1) == ',') $value = substr($value, 0, -1);//去掉末尾逗号

		$vs = explode(' ', $value);
		$cname = $vs[0];

		if(strtoupper($cname) == 'KEY') {
			$subvalue = trim(substr($value, 3));
			$subvs = explode(' ', $subvalue);
			$subcname = $subvs[0];
			$newcols['KEY'][$subcname] = trim(substr($value, (5+strlen($subcname))));
		} elseif(strtoupper($cname) == 'INDEX') {
			$subvalue = trim(substr($value, 5));
			$subvs = explode(' ', $subvalue);
			$subcname = $subvs[0];
			$newcols['KEY'][$subcname] = trim(substr($value, (7+strlen($subcname))));
		} elseif(strtoupper($cname) == 'PRIMARY') {
			$newcols['PRIMARY'] = trim(substr($value, 11));
		} else {
			$newcols[$cname] = trim(substr($value, strlen($cname)));
		}
	}
	return $newcols;
}

//整理sql文
function remakesql($value) {
	$value = trim(preg_replace("/\s+/", ' ', $value));//空格标准化
	$value = str_replace(array('`',', ', ' ,', '( ' ,' )'), array('', ',', ',','(',')'), $value);//去掉无用符号
	$value = preg_replace('/(text NOT NULL) default \'\'/i',"\\1", $value);//去掉无用符号
	return $value;
}

//显示
function show_msg($message, $url_forward='') {
	global $_SGLOBAL;

	obclean();

	if($url_forward) {
		$_SGLOBAL['extrahead'] = '<meta http-equiv="refresh" content="1; url='.$url_forward.'">';
		$message = "<a href=\"$url_forward\">$message(跳转中...)</a>";
	} else {
		$_SGLOBAL['extrahead'] = '';
	}

	show_header();
	print<<<END
	<table>
	<tr><td>$message</td></tr>
	</table>
END;
	show_footer();
	exit();
}


//页面头部
function show_header() {
	global $_SGLOBAL, $_SC;

	$nowarr = array($_GET['step'] => ' class="current"');

	if(empty($_SGLOBAL['extrahead'])) $_SGLOBAL['extrahead'] = '';

	print<<<END
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	$_SGLOBAL[extrahead]
	<title> SupeSite 数据库升级程序 </title>
	<style type="text/css">
	* {font-size:12px; font-family: Verdana, Arial, Helvetica, sans-serif; line-height: 1.5em; word-break: break-all; }
	body { text-align:center; margin: 0; padding: 0; background: #F5FBFF; }
	.bodydiv { margin: 40px auto 0; width:720px; text-align:left; border: solid #86B9D6; border-width: 5px 1px 1px; background: #FFF; }
	h1 { font-size: 18px; margin: 1px 0 0; line-height: 50px; height: 50px; background: #E8F7FC; color: #5086A5; padding-left: 10px; }
	#menu {width: 100%; margin: 10px auto; text-align: center; }
	#menu td { height: 30px; line-height: 30px; color: #999; border-bottom: 3px solid #EEE; }
	.current { font-weight: bold; color: #090 !important; border-bottom-color: #F90 !important; }
	.showtable { width:100%; border: solid; border-color:#86B9D6 #B2C9D3 #B2C9D3; border-width: 3px 1px 1px; margin: 10px auto; background: #F5FCFF; }
	.showtable td { padding: 3px; }
	.showtable strong { color: #5086A5; }
	.datatable { width: 100%; margin: 10px auto 25px; }
	.datatable td { padding: 5px 0; border-bottom: 1px solid #EEE; }
	input { border: 1px solid #B2C9D3; padding: 5px; background: #F5FCFF; }
	.button { margin: 10px auto 20px; width: 100%; }
	.button td { text-align: center; }
	.button input, .button button { border: solid; border-color:#F90; border-width: 1px 1px 3px; padding: 5px 10px; color: #090; background: #FFFAF0; cursor: pointer; }
	#footer { font-size: 10px; line-height: 40px; background: #E8F7FC; text-align: center; height: 38px; overflow: hidden; color: #5086A5; margin-top: 20px; }
	</style>
	</head>
	<body>
	<div class="bodydiv">
	<h1>SupeSite 数据库升级工具</h1>
	<div style="width:90%;margin:0 auto;">
	<table id="menu">
	<tr>
	<td{$nowarr[start]}>升级开始</td>
	<td{$nowarr[check]}>UC检测</td>
	<td{$nowarr[sql]}>数据库结构添加/升级</td>
	<td{$nowarr[data]}>数据库数据升级</td>
	<td{$nowarr[delete]}>数据库结构删除</td>
	<td{$nowarr[cache]}>升级完成</td>
	</tr>
	</table>
	<br>
END;
}

//页面顶部
function show_footer() {
	print<<<END
	</div>
	<div id="footer">&copy; Comsenz Inc. 2001-2009 http://www.supesite.com</div>
	</div>
	<br>
	</body>
	</html>
END;
}

function get_uc_root() {
	$uc_root = '';
	$uc = parse_url(UC_API);
	if($uc['host'] == $_SERVER['HTTP_HOST']) {
		$php_self_len = strlen($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
		$uc_root = substr(__FILE__, 0, -$php_self_len).$uc['path'];
	}
	return $uc_root;
}

function model_cache() {
	global $_SGLOBAL;

	$_SGLOBAL['updatemodel'] = array('model' => array(), 'category' => array(), 'comment' => array(), 'folder' => array());
	$query = $_SGLOBAL['db']->query("SELECT * FROM ".tname('models')." m, ".tname('channels')." c WHERE m.modelname = c.nameid");
	while ($value = $_SGLOBAL['db']->fetch_array($query)) {
		$_SGLOBAL['updatemodel']['model'][$value['mid']] = $value;
		$_SGLOBAL['updatemodel']['category'][$value['mid']] = $value['mid'];
		$_SGLOBAL['updatemodel']['comment'][$value['mid']] = $value['mid'];
		$_SGLOBAL['updatemodel']['folder'][$value['mid']] = $value['mid'];
		$_SGLOBAL['updatemodel']['hot'][$value['mid']] = $value['mid'];
		$_SGLOBAL['updatemodel']['cache'][$value['mid']] = $value['mid'];
	}
	$cachefile = S_ROOT.'./data/system/update_model.cache.php';
	$text = '$_SGLOBAL[\'updatemodel\']='.arrayeval($_SGLOBAL['updatemodel']).";";
	writefile($cachefile, $text, 'php');
}
?>