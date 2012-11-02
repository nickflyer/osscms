#!/usr/bin/php
<?php

//Technology application

$conn=mysql_connect("119.254.229.22","remoteuser","12345678");
$conn1=mysql_connect("localhost","root","csip123234345");
if(!$conn1)
{
   echo mysql_error();
}

mysql_query("set names 'utf8'",$conn1);
mysql_select_db("osscms",$conn1);

$sql1="select update_id from oss_middle where catid=41";
$result1=mysql_query($sql1,$conn1);
$row1=mysql_fetch_array($result1);
$update_id=$row1["update_id"];

mysql_query("set names 'utf8'",$conn);
mysql_select_db("yp",$conn);

$sql="select resource_title, resource_id, unix_timestamp(update_time) from sw_resource where resource_type=68 and resource_id > $update_id order by add_time DESC";
$result=mysql_query($sql,$conn);

$num = 1;
while($row=mysql_fetch_array($result))
{
	$resource_id = $row["resource_id"];
	if($num == 1)
	{
		$update_id = $resource_id;
		$num ++;
	}
	$newstitle=$row["resource_title"];
	$newsurl=$resource_id;
	$newstime=$row["unix_timestamp(update_time)"];
	$newssubject=$row1["subject"];

	$sql="INSERT INTO `oss_spaceitems` (`itemid`, `catid`, `uid`, `tid`, `username`, `itemtypeid`, `type`, `subtype`, `subject`, `dateline`, `lastpost`, `viewnum`, `replynum`, `trackbacknum`, `goodrate`, `badrate`, `digest`, `top`, `allowreply`, `hash`, `folder`, `haveattach`, `grade`, `gid`, `gdigest`, `password`, `styletitle`, `picid`, `fromtype`, `fromid`, `hot`, `click_1`, `click_2`, `click_3`, `click_4`, `click_5`, `click_6`, `click_7`, `click_8`, `click_9`, `click_10`, `click_11`, `click_12`, `click_13`, `click_14`, `click_15`, `click_16`, `click_17`, `click_18`, `click_19`, `click_20`, `click_21`, `click_22`, `click_23`, `click_24`, `click_25`, `click_26`, `click_27`, `click_28`, `click_29`, `click_30`, `click_31`, `click_32`) VALUES
(NULL, 41, 41424, 0, 'zhangy', 0, 'news', '', '".$newstitle."', '".$newstime."', '".$newstime."', 13, 0, 0, 0, 0, 0, 0, 1, '', 1, 0, 0, 0, 0, '', '', 0, 'adminpost', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)";

	$result1=mysql_query($sql,$conn1);

	$newID=mysql_insert_id($conn1);

	if(!$result1)
	{
    		echo mysql_error();
	}

	$sql=" INSERT INTO `oss_spacenews` (`nid`, `itemid`, `message`, `relativetags`, `postip`, `relativeitemids`, `customfieldid`, `customfieldtext`, `includetags`, `newsauthor`, `newsfrom`, `newsfromurl`, `newsurl`, `pageorder`) VALUES
(NULL, '".$newID."', '', '', '172.16.23.52', '', 0, 'a:0:{}', '', '', '', '', 'http://yp.oss.org.cn/blog/show_resource.php?resource_id=".$newsurl."', 1)";


	$result2=mysql_query($sql,$conn1);

	if(!$result2)
	{
		echo mysql_error();
	}

}

if($num>1)
{
	$sql="UPDATE oss_middle set update_id=$update_id WHERE catid=41";
	$result3=mysql_query($sql,$conn1);
}


mysql_close($conn);
mysql_close($conn1);

echo "Technolog Application Works!".date('Y-m-d').":".date('G:i:s')."\n";


?>
