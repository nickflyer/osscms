<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>php����HTML�ļ�</title>
</head>

<body>
<?php 

$conn=mysql_connect("119.254.229.22","remoteuser","12345678"); //�������ݿ�
if(!$conn)
{
   echo mysql_error();
}
mysql_query("set names 'utf8'"); //�����������
mysql_select_db("yp",$conn); //ѡ�����ݿ�
$sql="select type_id, type_name from sw_type"; //sql���
$result=mysql_query($sql);
ob_start();

while($row=mysql_fetch_array($result))
{	
	echo  '<ul class="erji" id="erji"><li><a href="http://yp.oss.org.cn/software/show_cat.php?cat_id='.$row["type_id"].'">'.$row["type_name"].'</a></li></ul>';
}
$content = ob_get_contents();
$fp=fopen("index.html.php","r"); //ֻ����ģ�� 
$str=fread($fp,filesize("index.html.php"));//��ȡģ�������� 
$str=str_replace("{content}",$content,$str);//�滻���� 
fclose($fp); 

$handle=fopen("index.html.php","w"); //д�뷽ʽ������·�� 
fwrite($handle,$str); //�Ѹղ��滻������д�����ɵ�HTML�ļ� 
fclose($handle); 

/*$content = ob_get_contents();//ȡ��phpҳ�������ȫ������
$fp = fopen("index.html.php", "w");
echo fwrite($fp, $content);
fclose($fp);
*/
?>
</body>
</html>
