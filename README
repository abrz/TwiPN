# TwiPN

TwiPN is a little hack that allows you to control a iptables-based firewall
via a Twilio application running on your server.

The idea is that via an interactive menu or by sending a specially crafted
SMS to your Twilio phone number you instruct the remote server to
change some specific rules of its firewall so that you can gain access.
I use it to open port 22 for ssh access when I am traveling as normally
that port is not accessible from outside the home network. I also created
a command to open a couple of specific ports which allow me to control
my mpd server and listen to the music streamed by it.

You should instantiate twipn.php for an IVR solution and/or twipnsms.php for
access via SMS.


## Usage

The IVR application guides you through the various steps required to input
an IP address, a port number and the time window that you want to set this
configuration for. It requires to enter the IP address as a 12-digit string,
the port number as a 4-digit string and the time window as a 2-digit string.
Pad with 0 wherever necessary. For example, address 11.234.5.67 is entered
as `011234005067`, port 22 is entered as `0022` and 7 minutes as `07`. You first
set the various parameters and then request to activate the configuration.

The SMS solution requires to enter the command first: `O` to open a specific
port to the given IP address; `C` to terminate access; `M` to open the ports
I use for mpd control and for streaming music. After the command comes the
IP address in dot notation, the port, and the time window. Finally, a `C`
can be specified to request a confirmation message to be sent back to you.
The various fields must be separate by a colon (\':\'). As an example to allow
access to port 80 from IP 22.33.44.55 for 30 minutes and get a confirmation
one would text the string `O:22.33.44.55:80:30:C`.

In either solution, once the specified time window expires new accesses to
the specified port from the given IP will no longer be allowed.

## Requirements and Setup

I assume you have port 22 and 8800 open on your router, with some rules which
restrict access to those ports. In my case I have something like:

    ..
    10  ACCEPT     all  --  0.0.0.0/0            0.0.0.0/0           state RELATED,ESTABLISHED 
    11
    12
    13
    14  ACCEPT     tcp  --  192.168.3.0/24       0.0.0.0/0           state NEW tcp dpt:22 
    15  LOGNDROP   tcp  --  100.10.200.20        0.0.0.0/0           state NEW tcp dpt:22 
    16  LOGNDROP   tcp  --  0.0.0.0/0            0.0.0.0/0           state NEW tcp dpt:22 
    17
    18
    19
    20  LOGNKEEP   tcp  --  174.129.0.0/16       0.0.0.0/0           state NEW tcp dpt:8800 
    21  LOGNKEEP   tcp  --  204.236.128.0/17     0.0.0.0/0           state NEW tcp dpt:8800
    22  LOGNKEEP   tcp  --  184.72.0.0/15        0.0.0.0/0           state NEW tcp dpt:8800 
    23  LOGNDROP   tcp  --  0.0.0.0              0.0.0.0/0           state NEW tcp dpt:8880
    24
    25
    ..
    34
    35  ACCEPT     tcp  --  192.168.1.222        0.0.0.0/0           state NEW tcp dpts:6599:6600 
    36  LOGNDROP   all  --  0.0.0.0/0            0.0.0.0/0           

As you can see I restrict port 22 to the LAN and have rule 15 which can be
modified as necessary for a new IP address. Port 8800 is open to a series of
IP addresses used by Amazon Web Services (the infrastructure used by Twilio).
There might be more IP address ranges that Twilio uses, you can run some
experiments to find out.

LOGNDROP and LOGNKEEP are just intermediate targets defined as:

    Chain LOGNDROP (5 references)
    target     prot opt source               destination         
    LOG        tcp  --  0.0.0.0/0            0.0.0.0/0           limit: avg 6/min burst 5 LOG flags 0 level 5 prefix `Denied TCP connection: ' 
    LOG        udp  --  0.0.0.0/0            0.0.0.0/0           limit: avg 6/min burst 5 LOG flags 0 level 5 prefix `Denied UDP connection: ' 
    DROP       all  --  0.0.0.0/0            0.0.0.0/0           
 
    Chain LOGNKEEP (11 references)
    target     prot opt source               destination         
    LOG        tcp  --  0.0.0.0/0            0.0.0.0/0           limit: avg 6/min burst 5 LOG flags 0 level 5 prefix `Accepted TCP connection: ' 
    LOG        udp  --  0.0.0.0/0            0.0.0.0/0           limit: avg 6/min burst 5 LOG flags 0 level 5 prefix `Accepted UDP connection: ' 
    ACCEPT     all  --  0.0.0.0/0            0.0.0.0/0           

Place the php web applications in your web server root (or wherever you
like, as long as it matches the setup in your Twilio account).

I have an Apache server running with a virtual host defined on port 8800
handling exclusively requests coming in from Twilio.

	<VirtualHost *:8800>
		ServerAdmin postmaster@example.org
		ServerName www.example.org

		DocumentRoot /var/www/twipn/
		# so on and so forth

	</VirtualHost>

You will also need to place the iptables_wrapper_script.sh in /usr/local/bin
and make sure the user your web server runs as can execute the script with root
privileges. In Ubuntu, I just added the following lines to /etc/sudoers

	# Apache user can run a wrapper script that ultimately calls iptables
	www-data  your_hostname = NOPASSWD:/usr/local/bin/iptables_wrapper_script.sh

Finally, in order to expire the temporary iptables rule you set up, your
system needs to have \'at\' installed.

That\'s about all I think. Let me know if anything isn\'t clear.
