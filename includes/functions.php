<?php

$filename = "bhop_ssj_0_0_0.txt";

$filename2 = "bhop_ssj.replay";

$steamid = "[U:1:156674509]";

//$output = read_btimes2($filename);
//write_shavit_final($filename2, $output[0], $steamid, $output[1]);
//write_shavit_old($filename2, $output[0]);

function read_btimes2($filename) {
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
	
	$output[0] = $data;
	$output[1] = $time;
	$output[2] = $playerID;
	
	return $output;
}

// Used for shavit timer when time is stored in the replay, not wr-based
function write_shavit_final($filename, $data, $steamid, $time) {
	
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
		fwrite($handle, pack('g', $time, 4), 4);
		
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