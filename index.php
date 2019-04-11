<?php
require_once('includes/functions.php');

if (isset($_REQUEST['input_format'])) {
	$input = $_REQUEST['input_format'];
}

if (isset($_REQUEST['output_format'])) {
	$output = $_REQUEST['output_format'];
}

if (isset($input) && isset($output)) {
	
	$input_dir = "data_in/";
	$output_dir = "data_out/";
	
	switch($input) {
		case "btimes2":
			$input_dir = $input_dir . "btimes/";
			$input_ext = ".txt";
			break;
		case "btimes183":
			$input_dir = $input_dir . "btimes/";
			$input_ext = ".rec";
			break;
		case "shavit":
			$input_dir = $input_dir . "replaybot/";
			$input_ext = ".replay";
			$input_style_dirs = true;
			break;
		case "ofir":
			$input_dir = $input_dir . "ghostrecords/";
			$input_type_dirs[0] = "main";
			$input_type_dirs[1] = "bonus";
			$input_ext = ".rec";
			$input_style_dirs = true;
			break;
		default:
			exit("Error: Invalid Input timer!");
			break;
	}
	
	switch($output) {
		case "btimes2":
			$output_dir = $output_dir . "btimes/";
			$output_ext = ".txt";
			break;
		case "btimes183":
			$output_dir = $output_dir . "btimes/";
			$output_ext = ".rec";
			break;
		case "shavit":
			$output_dir = $output_dir . "replaybot/";
			$output_ext = ".replay";
			$output_style_dirs = true;
			break;
		case "ofir":
			$output_dir = $output_dir . "ghostrecords/";
			$output_type_dirs[0] = "main";
			$output_type_dirs[1] = "bonus";
			$output_ext = ".rec";
			$output_style_dirs = true;
			break;
		default:
			exit("Error: Invalid Ouput timer!");
			break;
	}
	
	// Index input replays
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input_dir));
	foreach ($iterator as $file) {
		if ($file->isDir()) continue;
		$paths[] = $file->getPathname();
	}
	
	foreach ($paths as $path) {
		$exploded_path = explode('/',$path);
		
		// Grab any info stored in directory names first.
		if (isset($input_type_dirs)) {
			
			switch ($exploded_path[2]) {
				case $input_type_dirs[0]:
					$replay['type'] = 0;
					break;
				case $input_type_dirs[1]:
					$replay['type'] = 1;
					break;
				default:
					exit("Error: Input replay has type other than normal or bonus!");
					break;
			}
			if ($input_style_dirs) {
				$replay['style'] = (int)$exploded_path[3];
				$replay['file'] = $exploded_path[4]; // Filename, type, and style stored.
			} else {
				$replay['file'] = $exploded_path[3]; // Filename and type stored.
			}
			
		} else if ($input_style_dirs) {
			$replay['style'] = (int)$exploded_path[2];
			$replay['file'] = $exploded_path[3]; // Filename and style stored.
		} else {
			$replay['file'] = $exploded_path[2]; // Only Filename stored.
		}
		
		// Store full path for later.
		$replay['path'] = $path;
		$replays[] = $replay;
		$replay = null;
	}
	
	foreach($replays as $replay) {
		// Read input files on timer
		switch($input) {
			case "btimes2":
				// Store input timer.
				$replay['timer'] = $input;
				
				$replay_name = basename($replay['file'], $input_ext);
				$replay_name_exploded = explode('_', $replay_name);
				
				// Read array from the end.
				$replay['tas'] = (int)end($replay_name_exploded);
				$replay['style'] = (int)prev($replay_name_exploded);
				$replay['type'] = (int)prev($replay_name_exploded);
				
				// Get map name.
				$replay['map'] = preg_replace('/_' . $replay['type'] . '_' . $replay['style'] . '_' . $replay['tas'] . '$/', '', $replay_name);

				// Read replay data.
				$readdata = read_btimes2($replay['path'], $mysqli);
				$replay['data'] = $readdata[0];
				$replay['steamid'] = $readdata[2];
				$replay['time'] = $readdata[1];
				
				break;
			case "btimes183":
				// Store input timer.
				$replay['timer'] = $input;
				
				$replay_name = basename($replay['file'], $input_ext);
				$replay_name_exploded = explode('_', $replay_name);
				
				// Read array from the end.
				$replay['style'] = (int)end($replay_name_exploded);
				$replay['type'] = (int)prev($replay_name_exploded);
				
				// Get map name.
				$replay['map'] = preg_replace('/_' . $replay['type'] . '_' . $replay['style'] . '$/', '', $replay_name);
				
				// Read replay data.
				$readdata = read_btimes183($replay['path'], $mysqli);
				$replay['data'] = $readdata[0];
				$replay['steamid'] = $readdata[2];
				$replay['time'] = $readdata[1];
				
				break;
			case "shavit":
				// Store input timer.
				$replay['timer'] = $input;
				
				$replay_name = basename($replay['file'], $input_ext);
				$replay_name_exploded = explode('_', $replay_name);
				
				// Read replay data.
				$readdata = read_shavit($replay['path']);
				$replay['data'] = $readdata[0];
				
				// If the format has steamid, store it
				if(isset($readdata[2])) {
					$replay['steamid'] = $readdata[2];
				}
				
				$replay['time'] = $readdata[1];
				
				// If format final subversion 3 or later
				if (isset($readdata[3])) {
					$replay['map'] = $readdata[3];
					$replay['type'] = $readdata[4];
					$replay['style'] = $readdata[5];
					$replay['preframes'] = $readdata[6];
				} else { // WARNING: If it is older than subversion 3, and the map name ends in _1, the replay will be unintentionally ported as a bonus replay with the wrong map name as well!!!
					// Check for _1 at end of map name. (Is it a bonus replay.)
					if((int)end($replay_name_exploded) == 1) {
						$replay['type'] = 1;
						$replay['map'] = preg_replace('/_' . $replay['type'] . '$/', '', $replay_name);
						// We check later if the mapname is stored inside the bot to verify the name doesn't end in _1. (If the bot is in shavit final format sub-version 3 or greater.)
					} else {
						$replay['type'] = 0;
						$replay['map'] = $replay_name;
					}
				}
				
				break;
			case "ofir":
				// Store input timer.
				$replay['timer'] = $input;
				
				// Get map name.
				$replay['map'] = basename($replay['file'], $input_ext);
				
				// Read replay data.
				$replay['data'] = read_ofir($replay['path'])[0];
				
				break;
			default:
				exit("Error: Invalid Input timer!");
				break;
		}
		
		$replays2[] = $replay;
	}
	
	// Write new replay from contents of $replays2
}
?>