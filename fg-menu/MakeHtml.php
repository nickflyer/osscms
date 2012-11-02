<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>php make html</title>
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
$sql="select type_id, type_name, par_id from sw_type where par_id=0"; //sql语句
$result=mysql_query($sql);
ob_start();

while($row=mysql_fetch_array($result))
{	
		        
			echo  '<li><a href="http://yp.oss.org.cn/software/show_cat.php?cat_id='.$row[type_id].'">'.$row[type_name].'</a><ul>';
			$sql1="select * from sw_type where par_id=".$row[type_id]." and self_level=2";
			$result_sub=mysql_query($sql1,$conn);
			while($row1=mysql_fetch_array($result_sub))
			{	
				echo '<li><a href="http://yp.oss.org.cn/software/show_cat.php?cat_id='.$row1[type_id].'">'.$row1[type_name].'</a></li>';
				
			}
			echo "</ul></li>";
		
}

$content = ob_get_contents();
$fp=fopen("menuContent.html","r"); //只读打开模板 
$str=fread($fp,filesize("menuContent.html"));//读取模板中内容 
$str=str_replace("{content}",$content,$str);//替换内容 
fclose($fp); 

$handle=fopen("menuContent.html","w"); //写入方式打开新闻路径 
fwrite($handle,$str); //把刚才替换的内容写进生成的HTML文件 
fclose($handle); 

?>

</body>
</html>
