<?php

$GLOBALS["ID"] =1; //用来跟踪下拉菜单的ID号
$layer=1; //用来跟踪当前菜单的级数
//连接数据库
$Con=mysql_connect("119.254.229.22","remoteuser","12345678");
mysql_select_db("yp");
//�单
$sql="select * from menu where par_id=0";
$result=mysql_query($sql,$Con);
//如果一级菜单存在则开始菜单的显示
if(mysql_num_rows($result)>0) ShowTreeMenu($Con,$result,$layer,$ID);

//=============================================
//显示树型菜单函数 ShowTreeMenu($con,$result,$layer)
//$con：数据库连接
//$result：需要显示的菜单记录集
//layer：需要显示的菜单的级数

//=============================================
function ShowTreeMenu($Con,$result,$layer)
{
	//取得需要显示的菜单的项目数
	$numrows=mysql_num_rows($result);

	//开始显示菜单，每个�单都用一个表格来表示
	echo "<ul class='erji' id='erji'>";
 	for($rows=0;$rows<$numrows;$rows++)
	{
	//将当前菜单项目的内容导入数组
	$menu=mysql_fetch_array($result);
	//�单项目的�单记录集
	$sql="select type_name type_id from menu where par_id=$menu[type_id]";
	$result_sub=mysql_query($sql,$Con);
	
	echo "<ul class='erji' id='erji'>";
	//如果该菜单项目有�单，则添加JavaScript onClick语句
	if(mysql_num_rows($result_sub)>0)
	{
	echo "<li>".ShowTreeMenu($result_sub )."</li>";
	}
	else
	{
	echo "<li> </li>";
	}
	//如果该菜单项目没有�单，并指定了超级连接地址，则指定为超级连接，
	//�单名称
	if($menu[url]!="")
	echo "<a href='http://yp.oss.org.cn/software/show_cat.php?cat_id=".$menu[type_id].'>$menu[type_name]</a>";
	else
	echo $menu[type_name];
	echo "</li></ul>";

	//如果该菜单项目有�单，则显示�单
	if(mysql_num_rows($result_sub)>0)
	{
	//指定该�单的ID和style，以便和onClick语句相对应
	echo "<ul class='erji' id='erji'>";
	echo "<li>";
	//将级数加1
	$layer++;
	//递归调用ShowTreeMenu()函数，生�单
	ShowTreeMenu($Con,$result_sub,$layer);
	//�单处理完成，返回到递归的上一层，将级数减1
	$layer--;
	echo "</li>";
	echo "</ul>";
	
	}
	//继续显示下一个菜单项目
	

}
