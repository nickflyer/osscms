<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>php生成HTML文件</title>
</head>

<body>
<?php 

$conn=mysql_connect("119.254.229.22","remoteuser","12345678"); //连接数据库
if(!$conn)
{
   echo mysql_error();
}
mysql_query("set names 'utf8'"); //解决中文乱码
mysql_select_db("yp",$conn); //选择数据库
$sql="select type_id, type_name from sw_type"; //sql语句
$result=mysql_query($sql);
ob_start();

while($row=mysql_fetch_array($result))
{	
	echo  '<ul class="erji" id="erji"><li><a href="http://yp.oss.org.cn/software/show_cat.php?cat_id='.$row["type_id"].'">'.$row["type_name"].'</a></li></ul>';
}
$content = ob_get_contents();
$fp=fopen("index.html.php","r"); //只读打开模板 
$str=fread($fp,filesize("index.html.php"));//读取模板中内容 
$str=str_replace("{content}",$content,$str);//替换内容 
fclose($fp); 

$handle=fopen("index.html.php","w"); //写入方式打开新闻路径 
fwrite($handle,$str); //把刚才替换的内容写进生成的HTML文件 
fclose($handle); 

/*$content = ob_get_contents();//取得php页面输出的全部内容
$fp = fopen("index.html.php", "w");
echo fwrite($fp, $content);
fclose($fp);
*/
?>
</body>
</html>
