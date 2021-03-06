<?php
require_once 'config.php';
require_once 'SteamID.php'; // https://github.com/xPaw/SteamID.php

/*

All read functions return an array.

$output[0] being an array containing the replay data.
$output[1] being the time (Returned as 0.0 on a wr-based replay)
$output[2] being the steam64 id of the player (Not returned on a wr-based replay)
$output[3] being a string containing the map name
$output[4] being an int containing the type
$output[5] being an int containing the style
$output[6] being an int containing the preframes


$data[0] = $vPos[0];
$data[1] = $vPos[1];
$data[2] = $vPos[2];
$data[3] = $vAng[0];
$data[4] = $vAng[1];
$data[5] = $buttons;
$data[6] = $flags;
$data[7] = $movetype;
$data[8] = $impulse;
$data[9] = $weapon;

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
		
		// Get the frame count
		$frameCount = sizeOf($data[0]);
		
		// If time is 0.0, calculate time
		if ($time === 0.0) { $time = framecount_to_time($frameCount); }
		
		// Write the replay time
		fwrite($handle, pack('g', $time), 4);
		
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
		
		if ($header[1] === "{SHAVITREPLAYFORMAT}{FINAL}\n") {
			
			if ((int)$header[0] >= 3) {
				// Read map, one character at a time, until you reach the NUL Terminator
				while (false !== ($char = fgetc($handle)) && $char != pack('c', '\0')) {
					$map[] = $char;
				}
			
				// Convert array of characters into a string
				$map = implode($map);
				
				// Read style
				$style = (int)unpack('c', fread($handle, 1))[1];
		
				// Read type
				$type = (int)unpack('c', fread($handle, 1))[1];
				
				// Write preframes (Number of frames with prestrafe in them)
				$preframes = (int)unpack('g', fread($handle, 4))[1];
			}

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
			if ((int)$header[0] >= 2) {
				while ($buffer = fread($handle, 32)) {
					$input[] = str_split($buffer, 4);
				}
				if (!feof($handle)) {
					echo "Error: unexpected fread() fail\n";
				}
			} else if ((int)$header[0] === 1) {
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
				if ((int)$header[0] >= 2) {
					$flags[] = unpack('l', $frame[6])[1];
					$movetype[] = unpack('l', $frame[7])[1];
				}
			}
		} else if ($header[1] === "{SHAVITREPLAYFORMAT}{V2}\n") { // wr-based, no time or player stored
			
			$frameCount = (int)$header[0];
			
			while ($buffer = fread($handle, 24)) {
				$input[] = str_split($buffer, 4);
			}
			if (!feof($handle)) {
				echo "Error: unexpected fread() fail\n";
			}
			
			foreach ($input as $frame) {
				$vPos[0][] = round(unpack('g', $frame[0])[1], 6, PHP_ROUND_HALF_DOWN);
				$vPos[1][] = round(unpack('g', $frame[1])[1], 6, PHP_ROUND_HALF_DOWN);
				$vPos[2][] = round(unpack('g', $frame[2])[1], 6, PHP_ROUND_HALF_DOWN);
				$vAng[0][] = round(unpack('g', $frame[3])[1], 6, PHP_ROUND_HALF_DOWN);
				$vAng[1][] = round(unpack('g', $frame[4])[1], 6, PHP_ROUND_HALF_DOWN);
				$buttons[] = unpack('l', $frame[5])[1];
			}
		} else { // Old plain text format, wr-based, no time or player stored
			// The pointer is currently not at start of the file because we attempted to read the header, so we have to reset it
			rewind($handle);
			
			while (($line = fgets($handle, 320)) !== false) {
				$explodedLine = explode("|", $line);
				
				$vPos[0][] = (float)$explodedLine[0];
				$vPos[1][] = (float)$explodedLine[1];
				$vPos[2][] = (float)$explodedLine[2];
				$vAng[0][] = (float)$explodedLine[3];
				$vAng[1][] = (float)$explodedLine[4];
				$buttons[] = (int)$explodedLine[5];
			}
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
	
	$output[0] = $data;
	
	// Some shavit formats are wr-based and thus have no steamid
	if (isset($steamid)) {
		if ($steamid === "invalid") {
			$steamid = NULL;
		} else {
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
			
			$output[2] = $steamid;
		}
	}
	
	// Some shavit formats are wr-based and thus have no time
	if (isset($time)) {
		$output[1] = $time;
	} else {
		$output[1] = 0.0;
	}
	
	// Shavit final subversion 3 added map, style, and preframes to file.
	if ((int)$header[0] >= 3) {
		$output[3] = $map;
		$output[4] = $type;
		$output[5] = $style;
		$output[6] = $preframes;
	}
	
	return $output;
}

// Used for shavit timer when time is stored in the replay, not wr-based
function write_shavit_final($filename, $data, $steamid, $time, $map, $style, $type, $preframes) {
	
	if ($steamid === "invalid") {
		// Do nothing, input replay was wr-based
	} else {
	
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
	}
	
	/* Always do subversion 3 because of poor design in defining replay type
	// Check if original replay stored flags and movetype
	if (isset($data[6]) && isset($data[7])) {
		$replayVersion = 3;
	} else {
		$replayVersion = 1;
	} */
	$replayVersion = 3;
	
	$handle = @fopen($filename, "wb");
	if ($handle) {
		$header = $replayVersion . ":{SHAVITREPLAYFORMAT}{FINAL}\n";
		fwrite($handle, $header);
		
		// Write map name
		fwrite($handle, $map . "\0");
		
		// Write style
		fwrite($handle, pack('c', (int)$style), 1);
		
		// Write type
		fwrite($handle, pack('c', (int)$type), 1);
		
		// Write preframes (Number of frames with prestrafe in them)
		fwrite($handle, pack('g', $preframes), 4);
		
		// Write the frame count
		$frameCount = sizeOf($data[0]);
		fwrite($handle, pack('l', $frameCount), 4);
		
		// If time is 0.0, calculate time
		if ($time === 0.0) { $time = framecount_to_time($frameCount); }
		
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
			
			// flags
			if (isset($data[6])) {
				fwrite($handle, pack('l', $data[6][$i]), 4);
			} else {
				fwrite($handle, pack('l', 65664), 4); // 65664 = FL_CLIENT|FL_AIMTARGET
			}
			
			// movetype
			if (isset($data[7])) {
				fwrite($handle, pack('l', $data[7][$i]), 4);
			} else {
				fwrite($handle, pack('l', 2), 4); // 2 = MOVETYPE_WALK
			}
			
			/* Always do subversion 3 because of poor design in defining replay type
			// Write flags and movetype only if replayVersion 2 or later
			if ($replayVersion >= 2) {
				fwrite($handle, pack('l', $data[6][$i]), 4); // flags
				fwrite($handle, pack('l', $data[7][$i]), 4); // movetype
			} */
		}
		
		fclose($handle);
	}
}

// Used for shavit timer when only frames are stored in plain text, wr-based. ONLY INCLUDED FOR LEGACY PURPOSES!
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

function read_ofir($filename) {
	
	$handle = @fopen($filename, "rb");
	
	if($handle) {
		while (($line = fgets($handle, 512)) !== false) {
			$explodedLine = explode("|", $line);
			
			$vPos[0][] = (float)$explodedLine[0];
			$vPos[1][] = (float)$explodedLine[1];
			$vPos[2][] = (float)$explodedLine[2];
			$vAng[0][] = (float)$explodedLine[3];
			$vAng[1][] = (float)$explodedLine[4];
			$buttons[] = (int)$explodedLine[5];
			$impulse[] = (int)$explodedLine[6];
			$weapon[] = (int)$explodedLine[7];
		}
	}
	fclose($handle);
	
	$data[0] = $vPos[0];
	$data[1] = $vPos[1];
	$data[2] = $vPos[2];
	$data[3] = $vAng[0];
	$data[4] = $vAng[1];
	$data[5] = $buttons;
	$data[8] = $impulse;
	$data[9] = $weapon;
	
	$output[0] = $data;
	$output[1] = 0.0; // Return 0.0 for time because ofir's timer is wr-based
	
	return $output;
}

function write_ofir($filename, $data) {
	
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
			fwrite($handle, $data[5][$i] . "|");
			if (isset($data[8])) { fwrite($handle, $data[8][$i] . "|"); } else { fwrite($handle, "0|"); }
			if (isset($data[9])) { fwrite($handle, $data[9][$i] . "\n"); } else { fwrite($handle, "0\n"); }
		}
		
		fclose($handle);
	}
}

function read_btimes183($filename, $mysqli) {
	
	$handle = @fopen($filename, "rb");
	
	if($handle) {
		
		$header = explode("|", fgets($handle, 64));
		
		$PlayerID = $header[0];
		$time = (float)$header[1];
		
		while (($line = fgets($handle, 512)) !== false) {
			$explodedLine = explode("|", $line);
			
			$vPos[0][] = (float)$explodedLine[0];
			$vPos[1][] = (float)$explodedLine[1];
			$vPos[2][] = (float)$explodedLine[2];
			$vAng[0][] = (float)$explodedLine[3];
			$vAng[1][] = (float)$explodedLine[4];
			$buttons[] = (int)$explodedLine[5];
		}
	}
	fclose($handle);
	
	$data[0] = $vPos[0];
	$data[1] = $vPos[1];
	$data[2] = $vPos[2];
	$data[3] = $vAng[0];
	$data[4] = $vAng[1];
	$data[5] = $buttons;
	
	// Get steamid from playerid
	$steamid = btimes183_playerid_to_steamid($PlayerID, $mysqli);
	
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

function write_btimes183($filename, $data, $steamid, $time, $mysqli) {
	
	$handle = @fopen($filename, "wb");
	if ($handle) {
		
		//Get playerid from steamid
		$PlayerID = btimes183_steamid_to_playerid($steamid, $mysqli);
		
		// Get the frame count
		$frameCount = sizeOf($data[0]);
		
		// If time is 0.0, calculate time
		if ($time === 0.0) { $time = framecount_to_time($frameCount); }
		
		// Write the header
		fwrite($handle, $PlayerID . "|" . $time . "\n");
		
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

function btimes183_steamid_to_playerid($steamid, $mysqli) {

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

function btimes183_playerid_to_steamid($playerid, $mysqli) {

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

function framecount_to_time($framecount) {
	
	if($_REQUEST['tickrate'] === NULL || !ctype_digit($_REQUEST['tickrate'])) {
		exit('Error: A valid tickrate required for this conversion!');
	}
	
	$time = round((float)$framecount/(float)$_REQUEST['tickrate'], 6, PHP_ROUND_HALF_DOWN);
	
	return $time;
}
?>