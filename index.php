<?
	header("Content-type: text/html; charset=UTF-8");

	function dumper($foo){
		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}

	include('head.txt');
?>
<style>
table.table-flex {
	width: auto;
	max-width: auto;
}
span.highlight {
	xbackground-color: #ff9;
}
span.quiet {
	color: #aaa;
}
</style>

<form action="/" method="get" class="well form-inline">
<label>Any string:</label>
<input type="text" name="u" class="span3" />
<input type="submit" value="Explain" class="btn" />
[<a href="/?u=&#x65e5;&#x672c;&#x8a9e;">Demo</a>]
</form>



<?
	if ($_GET['u']){
		$chars = preg_split('/(?<!^)(?!$)/u', $_GET['u']);

		$blocks = array();
		foreach ($chars as $char) $blocks[] = breakdown_char($char);
		#dumper($blocks);
?>
	<table class="table table-striped table-bordered table-condensed table-flex">
<?
	output_block_row('Grapheme', $blocks, 'utf8');
	output_block_row('Codepoint', $blocks, 'cp_hex');

	output_block_row('Hex Entity', $blocks, 'ent_hex');
	output_block_row('Dec Entity', $blocks, 'ent_dec');

	output_block_row('Binary', $blocks, 'cp_bin');
	output_block_row('UTF-8 Binary', $blocks, 'bins');

	output_block_row('UTF-8 Hex Bytes', $blocks, 'hexes');
	output_block_row('UTF-8 Dec Bytes', $blocks, 'bytes');
	output_block_row('UTF-8 C String', $blocks, 'cstr');

	output_block_row('More Info', $blocks, 'fileformat')
?>
	</table>

<?
	}

	function output_block_row($label, $blocks, $key){

		echo '<tr>';
		echo "<td><b>$label</b></td>";
		foreach ($blocks as $block){
			echo "<td>$block[$key]</td>";
		}
		echo "</tr>";
	}

	function breakdown_char($utf8_bytes){

		$bytes = array();
		$hexes = array();
		$chexes = array();
		$bins = array();

		foreach (str_split($utf8_bytes) as $ch){
			$c = ord($ch);
			$h = sprintf('%02X', $c);

			$bytes[] = $c;
			$hexes[] = $h;
			$chexes[] = '\\x'.$h;
			$bins[] = format_bin($c);
		}

		# convert into codepoint
		$codepoint = 0;
		if (count($bytes) == 1) $codepoint = $bytes[0];
		if (count($bytes) == 2) $codepoint = (($bytes[0] & 0x1F) << 6) | ($bytes[1] & 0x3F);
		if (count($bytes) == 3) $codepoint = (($bytes[0] & 0x0F) << 12) | (($bytes[1] & 0x3F) << 6) | ($bytes[2] & 0x3F);
		if (count($bytes) == 4) $codepoint = (($bytes[0] & 0x07) << 18) | (($bytes[1] & 0x3F) << 12) | (($bytes[2] & 0x3F) << 6) | ($bytes[3] & 0x3F);
		if (count($bytes) == 5) $codepoint = (($bytes[0] & 0x03) << 24) | (($bytes[1] & 0x3F) << 18) | (($bytes[2] & 0x3F) << 12) | (($bytes[3] & 0x3F) << 6) | ($bytes[4] & 0x3F);
		if (count($bytes) == 6) $codepoint = (($bytes[0] & 0x01) << 30) | (($bytes[1] & 0x3F) << 24) | (($bytes[2] & 0x3F) << 18) | (($bytes[3] & 0x3F) << 12) | (($bytes[4] & 0x3F) << 6) | ($bytes[5] & 0x3F);

		$hex = sprintf('%X', $codepoint);

		return array(
			'utf8'		=> $utf8_bytes,
			'bytes'		=> implode(' ', $bytes),
			'hexes'		=> implode(' ', $hexes),
			'cstr'		=> implode('', $chexes),
			'bins'		=> implode(' ', $bins),

			'cp_bin'	=> sprintf('%b', $codepoint),
			'codepoint'	=> $codepoint,
			'cp_hex'	=> 'U+'.sprintf('%04X', $codepoint),
			'ent_hex'	=> '&amp;#x'.sprintf('%x', $codepoint).';',
			'ent_dec'	=> '&amp;#'.$codepoint.';',

			'fileformat'	=> "<a href=\"http://www.fileformat.info/info/unicode/char/{$hex}/index.htm\">click</a>",
		);
	}

	function format_bin($c){

		if ($c <= 0x3F) return highlight_bin($c, 0); # 00000000 - 00111111, 00-3F
		if ($c <= 0x7F) return highlight_bin($c, 1); # 01000000 - 01111111, 40-7F
		if ($c <= 0xBF) return highlight_bin($c, 2); # 10000000 - 10111111, 80-BF
		if ($c <= 0xDF) return highlight_bin($c, 3); # 11000000 - 11011111, C0-DF
		if ($c <= 0xEF) return highlight_bin($c, 4); # 11100000 - 11101111, E0-EF
		if ($c <= 0xF7) return highlight_bin($c, 5); # 11110000 - 11110111, F0-F7
		if ($c <= 0xFB) return highlight_bin($c, 6); # 11111000 - 11111011, F8-FB
		if ($c <= 0xFD) return highlight_bin($c, 7); # 11111100 - 11111101, FC-FD
		return highlight_bin($c, 8);
	}

	function highlight_bin($c, $prefix){
		$s = sprintf('%08b', $c);
		return '<span class="quiet">'.substr($s, 0, $prefix).'</span><span class="highlight">'.substr($s, $prefix).'</span>';
	}
?>

<?
	include('foot.txt');
?>

