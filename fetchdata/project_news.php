#!/usr/bin/php
<?php

//project news


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

$sql1="select update_id from oss_middle where catid=46";
$result1=mysql_query($sql1,$conn1);
$row1=mysql_fetch_array($result1);
$update_id=$row1["update_id"];


$dbconn=pg_connect("host=119.254.229.21 dbname=gforge user=gforge password=d86830ec")
        or die('Could not connect:'.pg_last_error());

if ($group_id != $sys_news_group) {
                $wclause="news_bytes.group_id='$group_id' AND news_bytes.is_approved <> '4'";
        } else {
                $wclause='news_bytes.is_approved=1';
        }

$query="SELECT  groups.group_name,groups.unix_group_name,
                groups.type_id,users.user_name,users.realname,
                news_bytes.forum_id,news_bytes.summary,news_bytes.post_date,news_bytes.details, news_bytes.id
                FROM users,news_bytes,groups
                WHERE $wclause
                AND users.user_id=news_bytes.submitted_by
                AND news_bytes.group_id=groups.group_id
                AND groups.status='A'
		AND news_bytes.id > $update_id 
                ORDER BY post_date DESC";

$result=pg_query($query) or die('Query failed:'.pg_last_error());

$num = 1;
while ($row=db_fetch_array($result)) {

	$newsbyte_id = $row["id"];
        if($num == 1)
        {
                $update_id = $newsbyte_id;
                $num ++;
        }


$newstitle=$row["summary"];
$newsurl=$row["forum_id"];
$newstime=$row["post_date"];

$sql=" INSERT INTO `oss_spaceitems` (`itemid`, `catid`, `uid`, `tid`, `username`, `itemtypeid`, `type`, `subtype`, `subject`, `dateline`, `lastpost`, `viewnum`, `replynum`, `trackbacknum`, `goodrate`, `badrate`, `digest`, `top`, `allowreply`, `hash`, `folder`, `haveattach`, `grade`, `gid`, `gdigest`, `password`, `styletitle`, `picid`, `fromtype`, `fromid`, `hot`, `click_1`, `click_2`, `click_3`, `click_4`, `click_5`, `click_6`, `click_7`, `click_8`, `click_9`, `click_10`, `click_11`, `click_12`, `click_13`, `click_14`, `click_15`, `click_16`, `click_17`, `click_18`, `click_19`, `click_20`, `click_21`, `click_22`, `click_23`, `click_24`, `click_25`, `click_26`, `click_27`, `click_28`, `click_29`, `click_30`, `click_31`, `click_32`) VALUES
(NULL, 46, 41424, 0, 'zhangy', 0, 'news', '', '".$newstitle."', '".$newstime."', '".$newstime."', 13, 0, 0, 0, 0, 0, 0, 1, '', 1, 0, 0, 0, 0, '', '', 0, 'adminpost', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0) ";
$result1=mysql_query($sql,$conn1);


$newID=mysql_insert_id($conn1);

if(!$result1)
{
    echo mysql_error();
}

$sql=" INSERT INTO `oss_spacenews` (`nid`, `itemid`, `message`, `relativetags`, `postip`, `relativeitemids`, `customfieldid`, `customfieldtext`, `includetags`, `newsauthor`, `newsfrom`, `newsfromurl`, `newsurl`, `pageorder`) VALUES
(NULL, '".$newID."', '', '', '172.16.23.52', '', 0, 'a:0:{}', '', '', '', '', 'http://matrix.oss.org.cn/forum/forum.php?forum_id=".$newsurl."', 1)";

$result2=mysql_query($sql,$conn1);

if(!$result2)
{
    echo mysql_error();
}



}

if($num>1)
{
        $sql="UPDATE oss_middle set update_id=$update_id WHERE catid=46";
        $result3=mysql_query($sql,$conn1);
}


pg_free_result($result);

pg_close($dbconn);

mysql_close($conn1);

echo "Project News Work!".date('Y-m-d').":".date('G:i:s')."\n";

?>






