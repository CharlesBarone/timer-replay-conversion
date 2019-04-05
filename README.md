# Timer Replay Conversion
Used for converting timer replay bot formats.

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

[FAQ](FAQ)

## License

[MIT](License)
