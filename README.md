# Timer Replay Conversion
Used for converting timer replay bot formats.

## Tutorial

1. Upload to a webserver running php version 7.2.0 or greater.
2. Upload the diretory built by your timer in `/addons/sourcemod/data/` to `data_in/`.
3. Visit index.php appending the following to the end of the URL.
`?input_format=INPUT_TIMER_HERE&output_format=OUTPUT_TIMER_HERE&tickrate=TICKRATE_HERE`
Valid timer names are the following: btimes2, btimes183, ofir, shavit.
4. When `Done!` is printed to the page, the conversion is complete! Download your newly converted replays from `data_out/`.

If you are converting to or from bTimes 1.8.3 or 2.0, you will need to grant the converter databse access. Database settings can be found in `includes/config.php`

The first credentials set is used for the input timer's database. The second credentials set is for the output timer's database.

If only one of your timers requires database access, you only need to confiugure the database credentials for that timer.

The tickrate parameter is only required when converting from a wr-based replay format to a replay format that is not wr-based.

## Functions

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Parameters</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>read_btimes2</td>
			<td>File Name, mysqli</td>
			<td>Returns an array containing the data from a bTimes 2.0 replay file.</td>
		</tr>
		<tr>
			<td>write_btimes2</td>
			<td>File Name, Replay Data Array, SteamID, Time, mysqli</td>
			<td>Creates a new replay file from data in the parameters for bTimes 2.0.</td>
		</tr>
		<tr>
			<td>read_shavit</td>
			<td>File Name</td>
			<td>Returns an array containing the data from any format shavit replay file.</td>
		</tr>
		<tr>
			<td>write_shavit_old</td>
			<td>File Name, Replay Data Array</td>
			<td>Creates a new replay file from data in the parameters for shavit's old format.</td>
		</tr>
    <tr>
			<td>write_shavit_final</td>
			<td>File Name, Replay Data Array, SteamID, Time</td>
			<td>Creates a new replay file from data in the parameters for shavit's final format.</td>
		</tr>
		<tr>
			<td>read_ofir</td>
			<td>File Name</td>
			<td>Returns an array containing the data from a replay file for Ofir's Timer.</td>
		</tr>
		<tr>
			<td>write_ofir</td>
			<td>File Name, Replay Data Array</td>
			<td>Creates a new replay file from data in the parameters for Ofir's Timer.</td>
		</tr>
		<tr>
			<td>read_btimes183</td>
			<td>File Name, mysqli</td>
			<td>Returns an array containing the data from a bTimes 1.8.3 replay file.</td>
		</tr>
		<tr>
			<td>write_btimes183</td>
			<td>File Name, Replay Data Array, SteamID, time, mysqli</td>
			<td>Creates a new replay file from data in the parameters for bTimes 1.8.3.</td>
		</tr>
	</tbody>
</table>

## FAQ

[FAQ](FAQ.md)

## License

[MIT](License.md)
