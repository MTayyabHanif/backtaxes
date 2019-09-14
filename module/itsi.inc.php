<?php

error_reporting(0);

# Clean Variables
if (!function_exists(cleanvars)) {
	function cleanvars($a) {
		foreach($a as $k=>$v) {
			eval('global $$k;');
			eval('$$k = $v;'); 
		}
	}
}

# Progress Meter
if (!function_exists(progress)) {
	function progress($str) {
	global $start, $debug;
	if ($debug) {
		if (is_array($str)) {
			print_arr($str, true);
			print_arr(' ('.number_format(microtime(true) - $start, 2, '.', '').')', true);
		} elseif (is_object($str)) {
			print_arr((array)$str, true);
			print_arr(' ('.number_format(microtime(true) - $start, 2, '.', '').')', true);
		} else {
			print_arr($str.' ('.number_format(microtime(true) - $start, 2, '.', '').')', true);
		}
		flush();
		ob_flush();
		sleep(0.1);
	}}
}

# Set Linebreak
define("br", "<br/>");

# Helper Output
if (!function_exists(helper)) {
	function helper($r) {
	ob_start();
	$data = print_arr($r, false);
	ob_end_clean();	
	return $data;}
}
# Output Array
if (!function_exists(print_arr)) {
	function print_arr($a, $o=true) { 
	if ($o)
		echo highlight_string(print_r($a, true), true).br; 
	else
		return highlight_string(print_r($a, true), true);}
}
# Echo Query String
if (!function_exists(halt_qsa)) {
	function halt_qsa($s, $f) {
	halt(explode("&", $s), $f);}
}
# Echo Table and Exit
if (!function_exists(halt_table)) {
	function halt_table($r, $f='') {
	if (is_object($r))
		halt($r, $f);
	else
		halt(table($r), $f);}
}
# Echo and Exit
if (!function_exists(halt)) {
	function halt($a='Halted', $f='') { 
	if ($f)
		echo "<strong>".$f."</strong>".br;
	if (is_array($a) || is_object($a))  
		echo highlight_string(print_r($a, true), true); 
	else 
		echo $a.br; 
	exit; }
}
# Tabularize Array
if (!function_exists(table)) {
	function table($r) {
	if (is_object($r)) {
		print_arr($r);
		return true;
	}
	echo "
		<style>
			table.halt {
				border-collapse: collapse;
				font-size: 8px;
				border: 2px solid #CCCCCC;
				empty-cells: show;
			}
			table.halt th {
				text-align: center;
				font-weight: bold;
				border: 2px solid #CCCCCC;
				background-color: #CCCCCC;
				color: #333333;
			}
			table.halt td {
				border-collapse: collapse;
				border: 1px solid #CCCCCC;
				padding: 2px;
			}
			.odd {
				background-color: #D5D5D5;			}
			.even {
			
			}
		</style>
	";

	echo '<table class="halt" border="1">';
	echo '<tr><th>ID</th>';
	foreach (array_shift(array_values($r)) as $k=>$v) {
		echo '<th>'.strtoupper($k).'</th>';
	}		
	echo '</tr>';
	$i=0;
	foreach($r as $k=>$v) {
		if (($i++ % 2)==0) 
			echo '<tr class="even">';
		else
			echo '<tr class="odd">';
		echo "<td>".$k."</td>";
		foreach ($v as $vk=>$vv) {
			echo "<td>".round($vv, 6)."</td>";
		}	
		echo '</tr>';
	}
	echo '</table>';}
}

?>