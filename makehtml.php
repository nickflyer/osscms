<?php

error_reporting(E_ALL);

$conn= new mysqli("localhost","root","csip123234345")
or die('连接数据库失败：'.mysql_error());
$result=$conn->query('set names utf8'); //用何种编码向数据库传递信息
if (!$result)
{
echo mysql_error();
}

$result = $conn->query("INSERT INTO `oss_spaceitems` (`itemid`, `catid`, `uid`, `tid`, `username`, `itemtypeid`, `type`, `subtype`, `subject`, `dateline`, `lastpost`, `viewnum`, `replynum`, `trackbacknum`, `goodrate`, `badrate`, `digest`, `top`, `allowreply`, `hash`, `folder`, `haveattach`, `grade`, `gid`, `gdigest`, `password`, `styletitle`, `picid`, `fromtype`, `fromid`, `hot`, `click_1`, `click_2`, `click_3`, `click_4`, `click_5`, `click_6`, `click_7`, `click_8`, `click_9`, `click_10`, `click_11`, `click_12`, `click_13`, `click_14`, `click_15`, `click_16`, `click_17`, `click_18`, `click_19`, `click_20`, `click_21`, `click_22`, `click_23`, `click_24`, `click_25`, `click_26`, `click_27`, `click_28`, `click_29`, `click_30`, `click_31`, `click_32`) VALUES
(NULL, 41, 5903, 0, 'doudou', 0, 'news', '', '11111111', 1096084446, 1096084446, 13, 0, 0, 0, 0, 0, 0, 1, '', 1, 0, 0, 0, 0, '', '', 0, 'adminpost', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)");
if (!$result)
{
echo mysql_error();
}
$resource_id = $conn->insert_id; //获得刚插入记录的自动增加ID的值

echo "dddddddddddddddddddddddddddddddd".$resource_id;

$result = $conn->query("INSERT INTO `oss_spacenews` (`nid`, `itemid`, `message`, `relativetags`, `postip`, `relativeitemids`, `customfieldid`, `customfieldtext`, `includetags`, `newsauthor`, `newsfrom`, `newsfromurl`, `newsurl`, `pageorder`) VALUES
(NULL, '$resource_id', '', '', '172.16.23.52', '', 0, 'a:0:{}', '', '', '', '', '', 1)");

?>
