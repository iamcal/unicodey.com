<?php
	#
	# this tool is for building transliteration maps
	#


	# Latin Extended-B
	$start = 0x0180;
	$end = 0x024F;

	header("Content-type: text/plain");

	$fh = fopen('UnicodeData.txt', 'r');
	while (($line = fgets($fh, 4096)) !== false){

		$fields = explode(';', $line);
		$cp = hexdec($fields[0]);

		if ($cp < $start) continue;
		if ($cp > $end) continue;

		$name = $fields[1];
		$decomp = $fields[5];

		$ok = 0;

		list($first) = explode(' ', $decomp);
		if (strlen($first)){
			$first = hexdec($first);
			if ($first >= 0x41 && $first <= 0x5a) $ok = 1;
			if ($first >= 0x61 && $first <= 0x7a) $ok = 1;
		}

		$bytes = unicode_chr($cp);
		$utf8 = '';
		foreach (str_split($bytes) as $byte) $utf8 .= '\\x'.sprintf('%02x', ord($byte));

		if ($ok){

			$ch = chr($first);
			echo "\t\t\"{$utf8}\" => '{$ch}', # {$name}\n";
		}else{
			echo "\t\t\"{$utf8}\" => '?', # {$name}\n";
		}
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
