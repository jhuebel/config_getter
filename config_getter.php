<?php

/*

Copyright (c) 2010, Jason Huebel
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

* Redistributions of source code must retain the above copyright notice,
  this list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/*
	Cisco Config Getter
	config_getter.php

	This script simply cycles through Cisco devices, stored in a database, 
	instructing each device	to upload its running configuration to an existing
	TFTP server.

	The current rate that it cycles through devices is once every 30 seconds.
	No attempt is made to verify that the	device successfully uploaded its
	configuration to the TFTP server. If the device fails to upload its
	configuration	within 30 seconds, an instruction is sent to the device to
	abort the upload.

	Configuration files are named in the following format:

		DEVICENAME-IPADDRESS-TIMESTAMP-config.txt

	Per device SNMP community strings and destination TFTP servers can be set
	within the database. If a community string or TFTP server is not set for
	the device, then the defaults (set below) will be used.

	SNMP community strings should have READ/WRITE access, since Cisco
	configurations are not available to READONLY SNMP connections.

*/

if (!function_exists("snmp2_set"))
{
	echo_die("The function 'snmp2_set' is not available. This script can not function without it.\n\n");
}

// script configuration settings
$cfg = array(
  "sleep_time" => 30 // number of seconds between each device upload
  );

$db = array(
	"host" => "localhost",
	"user" => "config_getter",
	"password" => "",
	"name" => "config_getter"
	);

// random number used for snmp session
$rand = rand(1, 999);

$snmp = array(
	"version" => 2, // v2 is all that's supported at the moment
	"community" => 'readwrite', // should be your read/write community string
	// you should have to change any of the oid_* settings below
	// OID's should end with a dot, unless otherwise noted
	"oid_systemname" => "1.3.6.1.2.1.1.5.0", // does not require trailing dot, only used in snmp2_get's
	"oid_protocol" => "1.3.6.1.4.1.9.9.96.1.1.1.1.2." .$rand,
	"oid_protocol_type" => "i", // Integer
	"oid_protocol_value" => "1", // 1=tftp, 2=ftp, 3=rcp, 4=scp, 5=sftp (only TFTP is currently supported)
	"oid_sourcetype" => "1.3.6.1.4.1.9.9.96.1.1.1.1.3." .$rand,
	"oid_sourcetype_type" => "i", // Integer
	"oid_sourcetype_value" => "4", // 1=networkfile, 2=iosFile, 3=startupConfig, 4=runningConfig, 5=terminal
	"oid_desttype" => "1.3.6.1.4.1.9.9.96.1.1.1.1.4." .$rand,
	"oid_desttype_type" => "i", // Integer
	"oid_desttype_value" => "1", // 1=networkfile, 2=iosFile, 3=startupConfig, 4=runningConfig, 5=terminal
	"oid_serveraddr" => "1.3.6.1.4.1.9.9.96.1.1.1.1.5." .$rand,
	"oid_serveraddr_type" => "a", // IP Address
	"oid_filename" => "1.3.6.1.4.1.9.9.96.1.1.1.1.6." .$rand,
	"oid_filename_type" => "s", // String
	"oid_copystatus" => "1.3.6.1.4.1.9.9.96.1.1.1.1.14." .$rand,
	"oid_copystatus_type" => "i", // Integer
	"oid_copystatus_value" => "1", // 1=active, 2=notInService, 4=createAndGo, 5=createAndWait, 6=destroy
	"oid_copystate" => "1.3.6.1.4.1.9.9.96.1.1.1.1.10." .$rand, // only used in snmp2_get's
	"oid_copystate_type" => "i", // 1=waiting, 2=running, 3=success, 4=failed
	);

$tftp = array(
	"server" => "192.168.1.1" // must be writeable
	);

// NO NEED TO EDIT BELOW THIS LINE

// set error reporting level to 0 (show no errors)
error_reporting(0);

// connect to the database
$link = mysql_connect($db['host'], $db['user'], $db['password'])
	or echo_die("Could not connect to MySQL: " .mysql_error() ."\n\n");
$db = mysql_select_db($db['name'], $link)
	or echo_die("Could not select database '" .$db['name'] ."': " .mysql_error() ."\n\n");

$result = mysql_query("SELECT * FROM devices WHERE enabled = 1", $link)
	or echo_die("Query failed: " .mysql_error() ."\n\n");

if (mysql_num_rows($result) == 0)
{
  echo_die("No devices defined.\n\n");
}

while ($device = mysql_fetch_assoc($result))
{
  // check for per-device settings
  if (trim($device['community']) == "") {
    $device['community'] = trim($snmp['community']);
  }

  if (trim($device['tftp_server']) == "") {
    $device['tftp_server'] = trim($tftp['server']);
  }

  // try getting the system name first, just to see if SNMP is working
  if(snmp2_get($device['ip_address'], $device['community'], $snmp['oid_systemname']))
  {
    // destroy any previous CopyState
//    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_copystatus'], $snmp['oid_copystatus'], $snmp['oid_copystatus_type'], 6)
//      or echo_die("Could not connect to device.");

    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_protocol'], $snmp['oid_protocol_type'], $snmp['oid_protocol_value'])
      or echo_log("Could not connect to device ${device['name']}.\n\n");
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_sourcetype'], $snmp['oid_sourcetype_type'], $snmp['oid_sourcetype_value'])
      or echo_log("Could not connect to device ${device['name']}.\n\n");
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_desttype'], $snmp['oid_desttype_type'], $snmp['oid_desttype_value'])
      or echo_log("Could not connect to device ${device['name']}.\n\n");
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_serveraddr'], $snmp['oid_serveraddr_type'], $tftp['server'])
      or echo_log("Could not connect to device ${device['name']}.\n\n");
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_filename'], $snmp['oid_filename_type'], \
        trim($device['name']) ."-" .trim($device['ip_address']). "-" .time() ."-config.txt")
      or echo_log("Could not connect to device ${device['name']}.\n\n");

    // initiate the copy process
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_copystatus'], $snmp['oid_copystatus_type'], $snmp['oid_copystatus_value'])
      or echo_die("Could not connect to device ${device['name']}.\n\n");

    // sleep for
    sleep($cfg['sleep_time']);

    // stop the copy process, even if it's not done
    snmp2_set($device['ip_address'], $device['community'], $snmp['oid_copystatus'], $snmp['oid_copystatus_type'], "6")
      or echo_die("Could not connect to device ${device['name']}.\n\n");

    mysql_unbuffered_query("UPDATE devices SET last_upload = NOW() WHERE id = " .$device['id'], $link);

		echo_log("uploaded " .$device['name'] ." (" .$device['ip_address'] .") ");

  } else {
    echo_log("'${device['name']}' (${device['id']}) was not available.");
  }
}

mysql_close($link);

exit();

// logs activity
function echo_log($message, $detail = "")
{
	global $link;

	$sql = "INSERT INTO log ( date_added, message, detail ) VALUES ( NOW(), '" .addslashes($message) ."', '" .addslashes($detail) ."' )";
	mysql_unbuffered_query($sql, $link);
}

function echo_die($message)
{
	echo_log($message);
	die($message);
}
