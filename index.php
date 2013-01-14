<?
	header("Content-type: text/html; charset=UTF-8");

	function dumper($foo){
		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}

	include('head.txt');
?>

<div class="well">

<form action="/" method="get" class="form-inline">
<label style="width: 90px">Any string:</label>
<input type="text" name="u" value="<?=HtmlSpecialChars($_GET['u'])?>" class="span3" />
<input type="submit" value="Explain" class="btn" />
[<a href="/?u=&#x65e5;&#x672c;&#x8a9e;">Demo</a>]
</form>

<form action="/" method="get" class="form-inline">
<label style="width: 90px">Code point:</label>
<input type="text" name="cp" value="<?=HtmlSpecialChars($_GET['cp'])?>" class="span3" />
<input type="submit" value="Explain" class="btn" />
[<a href="/?cp=2665">Demo</a>]
</form>

<form action="/" method="get" class="form-inline" style="margin-bottom: 0">
<label style="width: 90px">Hex bytes:</label>
<input type="text" name="hex" value="<?=HtmlSpecialChars($_GET['hex'])?>" class="span3" />
<input type="submit" value="Explain" class="btn" />
[<a href="/?hex=E6+97+A3+5F">Demo</a>]
</form>

</div>


<?
	$show_readme = 1;

	if ($_GET['u']){
		process_utf8_bytes($_GET['u']);
		$show_readme = 0;
	}

	if ($_GET['hex']){

		$in = $_GET['hex'];
		$in = str_replace('\\x', ' ', $in);
		$in = str_replace('%', ' ', $in);
		$in = trim($in);

		$bytes = preg_split('!\s+!', $in);
		$buffer = '';
		foreach ($bytes as $byte){
			$buffer .= chr(hexdec($byte));
		}

		process_utf8_bytes($buffer);
		$show_readme = 0;
	}

	if ($_GET['cp']){

		$cp = trim($_GET['cp']);
		if (preg_match('!^\d+$!', $cp)){

			$buffer = unicode_chr($cp).unicode_chr(hexdec($cp));
			process_utf8_bytes($buffer);
			$show_readme = 0;

		}elseif (preg_match('!^[0-9a-fA-F]+$!', $cp)){

			$buffer = unicode_chr(hexdec($cp));
			process_utf8_bytes($buffer);
			$show_readme = 0;
		}
	}


	function process_utf8_bytes($str){
		$chars = preg_split('/(?<!^)(?!$)/u', $str);

		$blocks = array();
		foreach ($chars as $char) $blocks[] = breakdown_char($char);
		output_blocks($blocks);
	}

	function output_blocks($blocks){
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

		if ($c <= 0x7F) return highlight_bin($c, 1); # 00000000 - 01111111, 00-7F
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


	function unicode_chr($v){

		if ($v < 128){
			return chr($v);
		}

		if ($v < 2048){
			return chr(($v >> 6) + 192) . chr(($v & 63) + 128);
		}

		if ($v < 65536){
			return chr(($v >> 12) + 224) . chr((($v >> 6) & 63) + 128) . chr(($v & 63) + 128);
		}

		if ($v < 2097152){
			return chr(($v >> 18) + 240) . chr((($v >> 12) & 63) + 128) . chr((($v >> 6) & 63) + 128) . chr(($v & 63) + 128);
		}

		die("can't create codepoints for $v");
	}
?>

<? if ($show_readme){?>

<div style="margin: 30px 5% 0 5%">

	<p class="leader">Unicodey.com is a bunch of tools for understanding and debugging Unicode strings, specifically the UTF-8 encoding.</p>

	<p>It was made by <a href="http://www.iamcal.com">Cal Henderson</a>, who is sick of doing this stuff by hand.</p>

</div>
<? } ?>

<?
	include('foot.txt');
?>

