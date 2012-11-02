<?php
include 'class.mysqldb.php';
error_reporting(E_ALL|E_NOTICE);

$beforedays = "-70 day";
$curdate = time();	
$earlydate	= strtotime( $beforedays, $curdate );

$src_dbhost     = '219.235.232.23';
$src_dbuser     = 'root';
$src_dbpass     = '123456';
$src_dbport     = 3306;
$src_dbname 	= 'ucenter';

$dst_dbhost     = '219.235.232.23';
$dst_dbuser     = 'root';
$dst_dbpass     = '123456';
$dst_dbport     =  3306;
$dst_dbname 	= 'osscms';

/***

  $salt = substr(uniqid(rand()), -6);
  $password = md5(md5($password).$salt);
  $strSQL = sprintf("INSERT INTO uc_members SET username=%s, password=%s, email=%s, regip=%s, regdate=%d salt=%s",);
  $uid = $this->db->insert_id();
  $strSQL = sprintf("INSERT INTO uc_memberfields SET uid = %d", $uid);


$userInfo = array(
      'uid' => $newuid,
      'username' => $username,
      'groupid' => 2,
      'password' => md5($pass),
      'dateline' => $curtimestamp,
      'updatetime' => $curtimestamp,
      'lastlogin' => $curtimestamp,
      'ip' => '172.16.23.88'
);
$strSQL = sprintf("
		REPLACE INTO oss_members (uid,username,groupid,password,dateline,updatetime,lastlogin,ip) 
		VALUES (%d,%s,%d,%s,%d,%d,%d,%s)",
				
};	

***/
$curtimestamp = time();
//$pass = $uid | $curtimestamp;
$dst_db = new MySQLDatabase();
$dst_db->dbconnect($dst_dbhost, $dst_dbuser, $dst_dbpass, 'uchome');

$userlist = array();
$src_db = new MySQLDataBase();
$src_db->dbconnect($src_dbhost, $src_dbuser, $src_dbpass, $src_dbname);

$strSQL = sprintf("SELECT uid, username,password,email,regip, regdate FROM uc_members ");
$result = $src_db->query($strSQL);
while ( $myrow = $src_db->fetchArray($result) ) 
{
	/*
	$user['uid'] 		= $myrow['uid'];
	$user['username'] 	= $myrow['username'];
	$user['password']	= $myrow['password'];
	$user['email']		= $myrow['email'];
	$user['regip']		= $myrow['regip'];
	$user['regdate']	= $myrow['regdate'];
	*/
	/*
	foreach($myrow as $key => $value)
		$user[$key] = $value;
	$userlist[]  = $user;
	*/
	$strSQL = sprintf("REPLACE INTO oss_members (uid,username,groupid,password,dateline,updatetime,lastlogin,ip) 
				VALUES (%d,%s,%d,%s,%d,%d,%d,%s)",
				$myrow['uid'],	
				$dst_db->quoteString($myrow['username']), 
				2,
				$dst_db->quoteString($myrow['password']),
				$myrow['regdate'], 
				$myrow['regdate'], 
				$myrow['regdate'],
				$dst_db->quoteString($myrow['regip'])
		
	);
	echo $strSQL.";\n";
//	$dst_db->query($strSQL);
		
}

$src_db->freeRecordSet($result);

unset($src_db);
unset($dst_db);

?>

