<?php
/**
 * These are the database login details
 */  
define("HOST", "localhost");     // The host you want to connect to.
define("USER", "username");    // The database username. 
define("PASSWORD", "password");    // The database password. 
define("DATABASE", "database");    // The database name.
 
define("SECURE", TRUE);    // FOR DEVELOPMENT ONLY!!!!

$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);

?>