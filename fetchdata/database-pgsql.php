<?php
/**
 * PostgreSQL database connection/querying layer
 *
 * ALPHA VERSION - not debugged!!
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 * @version   $Id: database-pgsql.php,v 1.2 2003/01/10 14:44:23 bigdisk Exp $
 */

/**
 * Database connection handle
 *
 * Current row for each result set
 *
 * @var array   $sys_db_row_pointer
 */
$sys_db_row_pointer=array();


/**
 *  db_connect() - Connect to the database
 *  Notice the global vars that must be set up
 *  Sets up a global $conn variable which is used
 *  in other functions in this library
 *
 */
function db_connect() {
        global $sys_dbhost,$sys_dbuser,$sys_dbpasswd,$conn,$sys_dbname;
        $conn = @pg_pconnect("user=$sys_dbuser dbname=$sys_dbname host=$sys_dbhost password=$sys_dbpasswd");
        #return $conn;
}

/**
 *  db_query() - Query the database
 *
 *  @param              string  SQL statement
 *  @param              int             How many rows do you want returned
 *  @param              int             Of matching rows, return only rows starting here
 */
function db_query($qstring,$limit='-1',$offset=0) {
        global $QUERY_COUNT;

