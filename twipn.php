<?php

include ("common.php");

$mylogsrc = LOG_SRC_TWIPN;
$mylogdst = LOG_DST_SYSLOG;

// data must be persisted otherwise it gets lost each time
// the page is reloaded (after the user selects an action)
class FireHole
{
	private $data = array();

	// constructor
	public function __construct() {
		$this->data['cnt'] = 1;
		$this->data['ipaddr'] = '192.168.100.100';
		$this->data['port'] = '22';
		$this->data['duration'] = '11';
	}

	// factory method
	public static function factory() {
		session_start();
		if(isset($_SESSION['conn']) === TRUE) {
			return unserialize($_SESSION['conn']);
		}
		return new FireHole();
	}

	public function __set($property, $value) {
		$this->data[$property] = $value;
	}

	public function __get($property) {
		if (isset($this->data[$property]) === TRUE) {
			return $this->data[$property];
		}
        }

	// save object to session variable
	public function __destruct() {
		$_SESSION['conn'] = serialize($this);
	}
}

$fh = FireHole::factory();

$next = array();
$next['act'] = array('hsl', 'listen', 'update', 'activate', 'music', 'killswitch');
$next['config'] = array('hsl', 'ipaddr', 'port', 'duration');

if (!is_null($_REQUEST['node'])) {
	$node = $_REQUEST['node'];
} else {
	$node = 'nowhere';
}
if (!is_null($_REQUEST['Digits'])) {
	$index = $_REQUEST['Digits'];
} else {
	$index = 0;
}
$baseurl = 'http://'.dirname($_SERVER["SERVER_NAME"].$_SERVER['PHP_SELF']).':'.$_SERVER['SERVER_PORT'].'/twipn.php';

switch ($node) {
	case 'updip':
		$tmp = str_split($index, 3);
		// strip leading zeros in each array element
		foreach ($tmp as &$val) {
			$val = ltrim($val, '0');
		}
		$ip = implode('.', $tmp);
		if (ValidIpAddress($ip) {
			$fh->ipaddr = $ip;
		}
		$dest = 'mainmenu';
		break;
	case 'updpt':
		$p = ltrim($index, '0');
		if (ValidPort($p) {
			$fh->port = $p;
		}
		$dest = 'mainmenu';
		break;
	case 'updtw':
		$d = ltrim($index, '0');
		if (ValidDuration($d) {
			$fh->duration = $d;
		}
		$dest = 'mainmenu';
		break;
	default:
		$iidx = (int) $index;
		// check to make sure index is valid
		if (isset($next[$node]) && count($next[$node]) >= $iidx) {
			$dest = $next[$node][$iidx];
		} else {
			$dest = NULL;
		}
		break;
}

if (!is_null($people[$_REQUEST['From']])) {
	$name = $people[$_REQUEST['From']];
} else {
	$name = NULL;
}

// render TwiML
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php

if (!$name) {
	LogAction($mylogsrc, $mylogdst, "Unkown phone " . $_REQUEST['From'] . " posted a request, no command was executed"); ?>
	<Say>Sorry but at this time access is restricted to known phone numbers</Say>
	<Pause />
	<Hangup />
	</Response>
	<?php 
	exit();
} else {
	if ($fh->cnt == 1) { ?>
		<Say>Welcome, <?php echo $name ?></Say>
		<Pause />
	<?php 
	}
	$fh->cnt = $fh->cnt + 1;
}

switch ($dest) {
	case 'listen': ?>
		<Say>The I P address currently set is <?php echo $fh->ipaddr ?></Say>
		<Say>The port currently set is <?php echo $fh->port ?></Say>
		<Say>The time window currently set is <?php echo $fh->duration ?> minutes</Say>
		<?php break;
	case 'activate': ?>
		<Say>Activating new firewall settings!</Say>
		<?php $output = shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -a $fh->ipaddr $fh->port $fh->duration "); ?>
		<?php LogAction($mylogsrc, $mylogdst, "$name requested to open $fh->ipaddr:$fh->port for $fh->duration minutes"); ?>
		<Pause />
		<Say>Thank you for using Twi P N, goodbye</Say>
		<Hangup />
		<?php break;
	case 'music': ?>
		<Say>Opening the firewall for <?php echo $fh->ipaddr ?>, enjoy the music!</Say>
		<?php $output = shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -m $fh->ipaddr 0 60 "); ?>
		<?php LogAction($mylogsrc, $mylogdst, "$name requested to allow access to the mpd stream and control port"); ?>
		<Pause />
		<Say>Thank you for using Twi P N, goodbye</Say>
		<Hangup />
		<?php break;
	case 'killswitch': ?>
		<Say>Emergency lock!</Say>
		<?php $output = shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -d $fh->ipaddr $fh->port 0 "); ?>
		<?php LogAction($mylogsrc, $mylogdst, "$name requested to close $fh->ipaddr:$fh->port"); ?>
		<Pause />
		<Say>Thank you for using Twi P N, goodbye</Say>
		<Hangup />
		<?php break;
	case 'update': ?>
		<Say>Update menu</Say>
		<Gather action="<?php echo $baseurl . '?node=config'; ?>" numDigits="1">
			<Say>Press 1 to change the address you want to control</Say>
			<Say>Press 2 to change the port number</Say>
			<Say>Press 3 to change the time window</Say>
		</Gather>
		<Say>Sorry, I did not get your input</Say>
		<?php break;
// address is entered as 12 digits, for example 1.12.123.45 becomes 001012123045
	case 'ipaddr': ?>
		<Gather action="<?php echo $baseurl . '?node=updip'; ?>" numDigits="12">
			<Say>Please enter the I P address you want to offer access to</Say>
		</Gather>
		<Say>Sorry, I did not get your input</Say>
		<?php break;
// port is entered as 4 digist, again pad with 0s in front
	case 'port': ?>
		<Gather action="<?php echo $baseurl . '?node=updpt'; ?>" numDigits="4">
			<Say>Please enter the port you want to offer access to</Say>
		</Gather>
		<Say>Sorry, I did not get your input</Say>
		<?php break;
// time window is 2 digits
	case 'duration': ?>
		<Gather action="<?php echo $baseurl . '?node=updtw'; ?>" numDigits="2">
			<Say>Please enter the number of minutes you want to offer access for</Say>
		</Gather>
		<Say>Sorry, I did not get your input</Say>
		<?php break;
	case 'mainmenu': ?>
		<?php break;

	default: ?>
		<Gather action="<?php echo $baseurl . '?node=act'; ?>" numDigits="1" timeout="10">
			<Say>Press 1 to listen to the current settings</Say>
			<Say>Press 2 to update the current settings</Say>
			<Say>Press 3 to activate the current settings</Say>
			<Say>Press 4 to allow currently set I P address access to mpd's stream</Say>
			<Say>Press 5 to immediately close an open port</Say>
		</Gather>
		<Say>Sorry, I did not get your input, goodbye</Say>
		<Hangup />
		<?php break;
}

if ($dest) { ?>
	<Pause />
	<Say>Main Menu</Say>
	<Redirect><?php echo $baseurl ; ?></Redirect>

<?php }

?>

</Response>
