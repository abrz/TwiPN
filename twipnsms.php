<?php

include ("common.php");

$confirm = 'N';
$field_delim = ':';

if (!is_null($people[$_REQUEST['From']])) {
        $name = $people[$_REQUEST['From']];
} else {
        $name = NULL;
}

$txt = $_REQUEST['Body'];

// render TwiML
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
<?php

if ($name) {
        $tmp = explode($field_delim, $txt, 5);
        $command = strtoupper(ltrim($tmp[0], ' '));
        $ipaddr = $tmp[1];
        $port = $tmp[2];
        $duration = $tmp[3];
        if (count($tmp) == 5) {
                $confirm = strtoupper($tmp[4]);
        }

        // sanity check arguments before use
        if (ValidCommand($command) && ValidIpAddress($ipaddr) && ValidPort($port) && ValidDuration($duration)) {
                switch ($command) {
                case 'O':
                        shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -a $ipaddr $port $duration ");
                        $msg = "open $ipaddr:$port for $duration";
                        break;
                case 'C':
                        shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -d $ipaddr $port 0 ");
                        $msg = "close $ipaddr:$port";
                        break;
                case 'M':
                        shell_exec("sudo /usr/local/bin/iptables_wrapper_script.sh -m $ipaddr 0 60 ");
                        $msg = "allow access to the mpd stream and control port";
                        break;
                case default:
                        $msg = "!! bug in command validation function? !!";
                        break;
                }
                if ($confirm == 'C') { // send confirmation SMS ?>
                        <Sms>TwiPN SMS API: Completed request from <?php echo $name ?> to <?php echo $msg ?></Sms>
                        </Response>
                <?php
                } else { ?>
                        </Response>
                <?php
                }
        } else { ?>
                <Sms>TwiPN SMS API: request from <?php echo $name ?> had wrong TwiPN syntax, no command was executed</Sms>
                </Response>
        <?php
        }
} else { ?>
        <Sms>TwiPN SMS API: This phone number is not allowed to execute commands, you may want to update the whitelist</Sms>
        </Response>
<?php
}
?>
