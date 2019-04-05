<?php
require_once 'config.php';
require_once 'SteamID.php'; // https://github.com/xPaw/SteamID.php

/*

All read functions return an array.

$output[0] being an array containing the replay data.
$output[1] being the time
$output[1] being the steam64 id of the player

*/

function read_btimes2($filename, $mysqli) {
	$handle = @fopen($filename, "rb");
	if ($handle) {
		$header = str_split(fread($handle, 8), 4);
		$playerID = unpack('l', $header[0])[1];
		$time = unpack('g', $header[1])[1]; // Don't round time to 6 decimals
		
		while ($buffer = fread($handle, 24)) {
			$input[] = str_split($buffer, 4);
		}
		if (!feof($handle)) {
			echo "Error: unexpected fread() fail\n";
		}
		fclose($handle);
	}

	foreach ($input as $frame) {
		$vPos[0][] = round(unpack('g', $frame[0])[1], 6, PHP_ROUND_HALF_DOWN);
		$vPos[1][] = round(unpack('g', $frame[1])[1], 6, PHP_ROUND_HALF_DOWN);
		$vPos[2][] = round(unpack('g', $frame[2])[1], 6, PHP_ROUND_HALF_DOWN);
		$vAng[0][] = round(unpack('g', $frame[3])[1], 6, PHP_ROUND_HALF_DOWN);
		$vAng[1][] = round(unpack('g', $frame[4])[1], 6, PHP_ROUND_HALF_DOWN);
		$buttons[] = unpack('l', $frame[5])[1];
	}
	
	$data[0] = $vPos[0];
	$data[1] = $vPos[1];
	$data[2] = $vPos[2];
	$data[3] = $vAng[0];
	$data[4] = $vAng[1];
	$data[5] = $buttons;
	
	// Get steamid from PlayerID
	$steamid = btimes2_playerid_to_steamid($playerID, $mysqli);

	// Convert $steamid to steam64
	try
	{
		$s = new SteamID($steamid);
		
			if( $s->GetAccountType() !== SteamID :: TypeIndividual )
			{
				throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
			}
			else if( !$s->IsValid() )
			{
				throw new InvalidArgumentException( 'Invalid SteamID.' );
			}
			
			$s->SetAccountInstance( SteamID :: DesktopInstance );
	}
	catch( InvalidArgumentException $e )
	{
		echo 'Given SteamID could not be parsed: ' . $steamid;
	}
	
	$steamid = $s->ConvertToUInt64() . PHP_EOL;
	
	
	$output[0] = $data;
	$output[1] = $time;
	$output[2] = $steamid;
	
	return $output;
}

function write_btimes2($filename, $data, $steamid, $time, $mysqli) {
	
	$handle = @fopen($filename, "wb");
	if ($handle) {
		
		// Write the playerid
		$playerid = btimes2_steamid_to_playerid($steamid, $mysqli);
		fwrite($handle, pack('l', $playerid), 4);
		
		// Write the replay time
		fwrite($handle, pack('g', $time), 4);
		
		// Get the frame count
		$frameCount = sizeOf($data[0]);
		
		// Write frames
		for ($i = 0; $i < $frameCount; $i++) {
			fwrite($handle, pack('g', $data[0][$i]), 4);
			fwrite($handle, pack('g', $data[1][$i]), 4);
			fwrite($handle, pack('g', $data[2][$i]), 4);
			fwrite($handle, pack('g', $data[3][$i]), 4);
			fwrite($handle, pack('g', $data[4][$i]), 4);
			fwrite($handle, pack('l', $data[5][$i]), 4);
		}
		
		fclose($handle);
	}
}

function btimes2_steamid_to_playerid($steamid, $mysqli) {

	// Make sure steamid is in correct format, STEAM_1:
	try
	{
		$s = new SteamID($steamid);
		
			if( $s->GetAccountType() !== SteamID :: TypeIndividual )
			{
				throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
			}
			else if( !$s->IsValid() )
			{
				throw new InvalidArgumentException( 'Invalid SteamID.' );
			}
			
			$s->SetAccountInstance( SteamID :: DesktopInstance );
	}
	catch( InvalidArgumentException $e )
	{
		echo 'Given SteamID could not be parsed: ' . $steamid;
	}
	
	$steamid = $s->RenderSteam2() . PHP_EOL;
	
	
	if ($stmt = $mysqli->prepare("SELECT `PlayerID` FROM `players` WHERE `SteamID` = ? LIMIT 1;")) {
		$stmt->bind_param('s', $steamid);  // Bind $steamid to parameter.
		$stmt->execute();    // Execute the prepared query.
		$stmt->store_result();
		$stmt->bind_result($playerid); // get variables from result.
		$stmt->fetch();

		if ($stmt->num_rows == 1) {
			return $playerid;
		} else {
			return False;
		}
	}
}

function btimes2_playerid_to_steamid($playerid, $mysqli) {

	if ($stmt = $mysqli->prepare("SELECT `SteamID` FROM `players` WHERE `PlayerID` = ? LIMIT 1;")) {
		$stmt->bind_param('i', $playerid);  // Bind $playerid to parameter.
		$stmt->execute();    // Execute the prepared query.
		$stmt->store_result();
		$stmt->bind_result($steamid); // get variables from result.
		$stmt->fetch();

		if ($stmt->num_rows == 1) {
			return $steamid;
		} else {
			return False;
		}
	}
}

function read_shavit($filename) {
	
	$handle = @fopen($filename, "rb");
	if ($handle) {
		$header = explode(":", fgets($handle, 64));
		
		if ($header[1] = "{SHAVITREPLAYFORMAT}{FINAL}") {

			$header2 = str_split(fread($handle, 8), 4);
			$frameCount = unpack('l', $header2[0])[1];
			$time = unpack('g', $header2[1])[1];
			
			// Read steamid, one character at a time, until you reach the NUL Terminator
			while (false !== ($char = fgetc($handle)) && $char != pack('c', '\0')) {
				$steamid[] = $char;
			}
			
			// Convert array of characters into a string
			$steamid = implode($steamid);
			
			// Loop through replay data based on format sub-version
			if ($header[0] === "2") {
				while ($buffer = fread($handle, 32)) {
					$input[] = str_split($buffer, 4);
				}
				if (!feof($handle)) {
					echo "Error: unexpected fread() fail\n";
				}
			} else if ($header[0] === "1") {
				while ($buffer = fread($handle, 24)) {
					$input[] = str_split($buffer, 4);
				}
				if (!feof($handle)) {
					echo "Error: unexpected fread() fail\n";
				}
			}
			
			foreach ($input as $frame) {
				$vPos[0][] = round(unpack('g', $frame[0])[1], 6, PHP_ROUND_HALF_DOWN);
				$vPos[1][] = round(unpack('g', $frame[1])[1], 6, PHP_ROUND_HALF_DOWN);
				$vPos[2][] = round(unpack('g', $frame[2])[1], 6, PHP_ROUND_HALF_DOWN);
				$vAng[0][] = round(unpack('g', $frame[3])[1], 6, PHP_ROUND_HALF_DOWN);
				$vAng[1][] = round(unpack('g', $frame[4])[1], 6, PHP_ROUND_HALF_DOWN);
				$buttons[] = unpack('l', $frame[5])[1];
				
				// Only for format sub-version 2
				if ($header[0] === "2") {
					$flags[] = unpack('l', $frame[6])[1];
					$movetype[] = unpack('l', $frame[7])[1];
				}
			}
		} else if ($header[1] = "{SHAVITREPLAYFORMAT}{V2}") {
			
		} else { // Old Plain text format
			
		}
	}
	fclose($handle);
		
	$data[0] = $vPos[0];
	$data[1] = $vPos[1];
	$data[2] = $vPos[2];
	$data[3] = $vAng[0];
	$data[4] = $vAng[1];
	$data[5] = $buttons;
	
	if (isset($flags)) {
		$data[6] = $flags;
	}
	if (isset($movetype)) {
		$data[7] = $movetype;
	}
	
	// Convert $steamid to steam64
	try
	{
		$s = new SteamID($steamid);
		
			if( $s->GetAccountType() !== SteamID :: TypeIndividual )
			{
				throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
			}
			else if( !$s->IsValid() )
			{
				throw new InvalidArgumentException( 'Invalid SteamID.' );
			}
			
			$s->SetAccountInstance( SteamID :: DesktopInstance );
	}
	catch( InvalidArgumentException $e )
	{
		echo 'Given SteamID could not be parsed: ' . $steamid;
	}
	
	$steamid = $s->ConvertToUInt64() . PHP_EOL;
	
	$output[0] = $data;
	$output[1] = $time;
	$output[2] = $steamid;
	
	return $output;
}

// Used for shavit timer when time is stored in the replay, not wr-based
function write_shavit_final($filename, $data, $steamid, $time) {
	
	// Verify that $steamid is in [U:1:#######] format
	try
	{
		$s = new SteamID($steamid);
		
			if( $s->GetAccountType() !== SteamID :: TypeIndividual )
			{
				throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
			}
			else if( !$s->IsValid() )
			{
				throw new InvalidArgumentException( 'Invalid SteamID.' );
			}
			
			$s->SetAccountInstance( SteamID :: DesktopInstance );
	}
	catch( InvalidArgumentException $e )
	{
		echo 'Given SteamID could not be parsed: ' . $steamid;
	}
	
	$steamid = $s->RenderSteam3() . PHP_EOL;
	
	// Check if original replay stored flags and movetype
	if (sizeof($data) > 6) {
		$replayVersion = 2;
	} else {
		$replayVersion = 1;
	}
	
	$handle = @fopen($filename, "wb");
	if ($handle) {
		$header = $replayVersion . ":{SHAVITREPLAYFORMAT}{FINAL}\n";
		fwrite($handle, $header);
		
		// Write the frame count
		$frameCount = sizeOf($data[0]);
		fwrite($handle, pack('l', $frameCount, 4), 4);
		
		// Write the replay time
		fwrite($handle, pack('g', $time), 4);
		
		// Write SteamID in [U:1:#######] format with null terminator
		fwrite($handle, $steamid . "\0");
		
		// Write frames
		for ($i = 0; $i < $frameCount; $i++) {
			fwrite($handle, pack('g', $data[0][$i]), 4);
			fwrite($handle, pack('g', $data[1][$i]), 4);
			fwrite($handle, pack('g', $data[2][$i]), 4);
			fwrite($handle, pack('g', $data[3][$i]), 4);
			fwrite($handle, pack('g', $data[4][$i]), 4);
			fwrite($handle, pack('l', $data[5][$i]), 4);
			
			
			// Write flags and movetype only if replayVersion 2 or later
			if ($replayVersion >= 2) {
				fwrite($handle, pack('l', $data[6][$i]), 4); // flags
				fwrite($handle, pack('l', $data[7][$i]), 4); // movetype
			}
		}
		
		fclose($handle);
	}
}

// Used for shavit timer when only frames are stored in plain text, wr-based
function write_shavit_old($filename, $data) {
	
	$handle = @fopen($filename, "wb");
	if ($handle) {
		
		// Get the frame count
		$frameCount = sizeOf($data[0]);
		
		// Write frames
		for ($i = 0; $i < $frameCount; $i++) {
			fwrite($handle, $data[0][$i] . "|");
			fwrite($handle, $data[1][$i] . "|");
			fwrite($handle, $data[2][$i] . "|");
			fwrite($handle, $data[3][$i] . "|");
			fwrite($handle, $data[4][$i] . "|");
			fwrite($handle, $data[5][$i] . "\n");
		}
		
		fclose($handle);
	}
}

?>