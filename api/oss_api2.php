<?php
require_once 'class.mysqldb.php';

function cutText($string, $start, $sublen, $code = 'UTF-8')     
{     
	if($code == 'UTF-8')     
	{ 
       $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/"; 	   
	   preg_match_all($pa, $string, $t_string);     

		if(count($t_string[0]) - $start > $sublen) 
			return join('', array_slice($t_string[0], $start, $sublen))."...";     

		return join('', array_slice($t_string[0], $start, $sublen));     
	}     
	else     
	{     
		$start = $start*2;     
		$sublen = $sublen*2;     
		$strlen = strlen($string);     
		$tmpstr = '';     

		for($i=0; $i<$strlen; $i++)     
		{     
			if($i>=$start && $i<($start+$sublen))     
			{     
				if(ord(substr($string, $i, 1))>129)     
				{     
					$tmpstr.= substr($string, $i, 2);     
				}     
				else     
				{     
					$tmpstr.= substr($string, $i, 1);     
				}     
			}     
			if(ord(substr($string, $i, 1))>129) $i++;     
		}   

		if(strlen($tmpstr)<$strlen ) $tmpstr.= "...";     
		return $tmpstr;     
	}     
}     

$dbhost     = '119.254.229.23';
$dbuser     = 'root';
$dbpass     = '123456';
$dbname     = 'osscms';
$dbport     =  3306;
$dbinst = new MySQLDatabase();

$dbinst->dbconnect($dbhost, $dbuser, $dbpass, $dbname);
$dbinst->query("SET NAMES utf8");
$divstr = "<html><html xmlns=\"http://www.w3.org/1999/xhtml\"><head>  
<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
<title></title>
<link href=\"http://soft.csip.org.cn/css/style.css\" rel=\"stylesheet\" type=\"text/css\" >
<link href=\"http://soft.csip.org.cn/css/layout.css\" rel=\"stylesheet\"  type=\"text/css\" >
<style type=\"text/css\" >
body {
	color:#333333;
	font-family:\"宋体\" arial;
	font-size:12px;
	font-size-adjust:none;
	font-style:normal;
	font-variant:normal;
	font-weight:normal;
}

.m03_a_bt a {
	width:280px;
	line-height:24px;
	font-size: 12px;
}
.nd {font-size:12px;}
.m03_a_bt a:link, .m03_a_bt a:visited { font-size:12px; color: #333333; text-decoration: none;}
.m03_a_bt a:hover { font-size:12px; color:#f00;	}

</style>
</head>
<body>";

$divstr .= "<div> <table class=\"m03_a_bt\" width=\"290\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
$strSQL = sprintf("SELECT itemid, subject FROM oss_spaceitems WHERE folder=1 AND catid=27 ORDER BY lastpost DESC limit 0, 7");
$result = $dbinst->query($strSQL);
while ( $myrow = $dbinst->fetchArray($result) ) 
{
	$news_url    	= "http://oss.org.cn/?action-viewnews-itemid-".$myrow['itemid'];
	$news_subject  = cutText($myrow['subject'], 0, 30, 'UTF-8')."...";
	$divstr .= "<tr><td width=\"290\" ><a href=\"$news_url\" target=\"_blank\">$news_subject</a></td></tr>";

}
$dbinst->freeRecordSet($result);
$divstr .= "</table></div></body></html>";

echo $divstr;

exit();

?>
