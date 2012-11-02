<?php

//Technology application

$conn=mysql_connect("119.254.229.22","remoteuser","12345678");
$conn1=mysql_connect("localhost","root","csip123234345");
if(!$conn1)
{
   echo mysql_error();
}

mysql_query("set names 'utf8'",$conn);
mysql_select_db("yp",$conn);
$sql="select type_name, type_id from sw_type where par_id=0";
$result=mysql_query($sql,$conn);

while($row=mysql_fetch_array($result))
{

$navtitle=$row["type_name"];
$navurl=$row["type_id"];

mysql_query("set names 'utf8'",$conn1);
mysql_select_db("osscms",$conn1);


$sql=" INSERT INTO `oss_spaceitems` (`itemid`, `catid`, `uid`, `tid`, `username`, `itemtypeid`, `type`, `subtype`, `subject`, `dateline`, `lastpost`, `viewnum`, `replynum`, `trackbacknum`, `goodrate`, `badrate`, `digest`, `top`, `allowreply`, `hash`, `folder`, `haveattach`, `grade`, `gid`, `gdigest`, `password`, `styletitle`, `picid`, `fromtype`, `fromid`, `hot`, `click_1`, `click_2`, `click_3`, `click_4`, `click_5`, `click_6`, `click_7`, `click_8`, `click_9`, `click_10`, `click_11`, `click_12`, `click_13`, `click_14`, `click_15`, `click_16`, `click_17`, `click_18`, `click_19`, `click_20`, `click_21`, `click_22`, `click_23`, `click_24`, `click_25`, `click_26`, `click_27`, `click_28`, `click_29`, `click_30`, `click_31`, `click_32`) VALUES
(NULL, 50, 41424, 0, 'zhangy', 0, 'news', '', '".$navtitle."', 1096084446, 1096084446, 13, 0, 0, 0, 0, 0, 0, 1, '', 1, 0, 0, 0, 0, '', '', 0, 'adminpost', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0) ";
$result1=mysql_query($sql,$conn1);

$newID=mysql_insert_id($conn1);

if(!$result1)
{
    echo mysql_error();
}

$sql=" INSERT INTO `oss_spacenews` (`nid`, `itemid`, `message`, `relativetags`, `postip`, `relativeitemids`, `customfieldid`, `customfieldtext`, `includetags`, `newsauthor`, `newsfrom`, `newsfromurl`, `newsurl`, `pageorder`) VALUES
(NULL, '".$newID."', '', '', '172.16.23.52', '', 0, 'a:0:{}', '', '', '', '', '', 1)";

$result2=mysql_query($sql,$conn1);

if(!$result2)
{
    echo mysql_error();
}

}

mysql_close($conn);
mysql_close($conn1);


?>
