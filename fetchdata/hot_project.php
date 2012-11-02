#!/usr/bin/php
<?php

//hot project


function db_fetch_array($qhandle) {
        global $sys_db_row_pointer;
        $sys_db_row_pointer[$qhandle]++;
        return @pg_fetch_array($qhandle,($sys_db_row_pointer[$qhandle]-1));
}


$conn1=mysql_connect("localhost","root","csip123234345");
if(!$conn1)
{
   echo mysql_error();
}

mysql_query("set names 'utf8'",$conn1);
mysql_select_db("osscms",$conn1);

$sql="Delete FROM `oss_spaceitems` WHERE `catid`=45";
$result4=mysql_query($sql,$conn1);


$dbconn=pg_connect("host=119.254.229.21 dbname=gforge user=gforge password=d86830ec")
        or die('Could not connect:'.pg_last_error());


$query="select groups.group_id,groups.group_name,groups.unix_group_name,frs_dlstats_grouptotal_vw.downloads, groups.register_time from frs_dlstats_grouptotal_vw,groups where frs_dlstats_grouptotal_vw.group_id=groups.group_id and groups.is_public='1' and groups.status='A' order by downloads DESC";

$result=pg_query($query) or die('Query failed:'.pg_last_error());


while ($row=db_fetch_array($result)) {

$newstitle=$row["group_name"];
$newsurl=$row["unix_group_name"];
$newstime=$row["register_time"];

mysql_query("set names 'utf8'",$conn1);
mysql_select_db("osscms",$conn1);

$sql=" INSERT INTO `oss_spaceitems` (`itemid`, `catid`, `uid`, `tid`, `username`, `itemtypeid`, `type`, `subtype`, `subject`, `dateline`, `lastpost`, `viewnum`, `replynum`, `trackbacknum`, `goodrate`, `badrate`, `digest`, `top`, `allowreply`, `hash`, `folder`, `haveattach`, `grade`, `gid`, `gdigest`, `password`, `styletitle`, `picid`, `fromtype`, `fromid`, `hot`, `click_1`, `click_2`, `click_3`, `click_4`, `click_5`, `click_6`, `click_7`, `click_8`, `click_9`, `click_10`, `click_11`, `click_12`, `click_13`, `click_14`, `click_15`, `click_16`, `click_17`, `click_18`, `click_19`, `click_20`, `click_21`, `click_22`, `click_23`, `click_24`, `click_25`, `click_26`, `click_27`, `click_28`, `click_29`, `click_30`, `click_31`, `click_32`) VALUES
(NULL, 45, 41424, 0, 'zhangy', 0, 'news', '', '".$newstitle."',  '".$newstime."', '".$newstime."' , 13, 0, 0, 0, 0, 0, 0, 1, '', 1, 0, 0, 0, 0, '', '', 0, 'adminpost', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0) ";
$result1=mysql_query($sql,$conn1);


$newID=mysql_insert_id($conn1);

if(!$result1)
{
    echo mysql_error();
}

$sql=" INSERT INTO `oss_spacenews` (`nid`, `itemid`, `message`, `relativetags`, `postip`, `relativeitemids`, `customfieldid`, `customfieldtext`, `includetags`, `newsauthor`, `newsfrom`, `newsfromurl`, `newsurl`, `pageorder`) VALUES
(NULL, '".$newID."', '', '', '172.16.23.52', '', 0, 'a:0:{}', '', '', '', '', 'http://matrix.oss.org.cn/projects/".$newsurl."', 1)";

$result2=mysql_query($sql,$conn1);

if(!$result2)
{
    echo mysql_error();
}



}

pg_free_result($result);

pg_close($dbconn);

mysql_close($conn1);

echo "Hot Project Work!".date('Y-m-d').":".date('G:i:s')."\n";

?> 
