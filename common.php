<?php

include ("settings.php");

function LogAction($srcid, $dstid, $logmsg) {

	if (LOG_ACTIONS) {
		$s = date("Y/m/d h:i:s", mktime()) . ">> " . $src . ": " . $logmsg . "\n";
		if ($dstid == LOG_DEST_EMAIL) {
			// send email
			// FIXME: subject can't be adjusted, should use mail() perhaps
			error_log($s, 1, ADMIN_EMAIL);
		} else {					// default to syslog
			// use syslog
			error_log($s, 0);
		}
	}
}

function ValidIpAddress($a) {
	if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $a)) {
		foreach (explode(".", $a) as $val) {
			if (intval($val)>255) {
				return false;
			}
		}
		return true;
	} else {
		return false;
	}
}

// minimal check - could be augmented to screen certain ports
function ValidPort($p) {
	if (preg_match("/^(\d{1,5})$/", $p)) {
		if (intval($p)<65536) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// max is 99
function ValidDuration($d) {
	if (preg_match("/^(\d{1,2})$/", $d)) {
		return true;
	} else {
		return false;
	}
}

// Open/Close/Music
function ValidCommand($c) {
	if (preg_match("/^[OCM]{1}$/", $c)) {
		return true;
	} else {
		return false;
	}
}

?>
