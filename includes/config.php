<?php
/**
 * These are the database login details for input database
 */  
define("HOST", "localhost");     // The host you want to connect to.
define("USER", "username");    // The database username. 
define("PASSWORD", "password");    // The database password. 
define("DATABASE", "database");    // The database name.

$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

/**
 * These are the database login details for output database
 */  
define("HOST2", "localhost");     // The host you want to connect to.
define("USER2", "username");    // The database username. 
define("PASSWORD2", "password");    // The database password. 
define("DATABASE2", "database");    // The database name.

$mysqli2 = new mysqli(HOST2, USER2, PASSWORD2, DATABASE2);

?>