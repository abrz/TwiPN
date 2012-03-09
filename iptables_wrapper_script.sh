#!/bin/sh
TESTING=0
MPDPORTS="6599:6600"
MPDIPRULE=35
IPRULE=15
TIMENOWSEC=$(date +%s)

if [ $# -ne 4 ]; then
	echo "wrong number of argument(s)"
       	exit
fi

case $1 in
	"-a") #echo "activate"
		MODE=1
		shift
		;;
	"-d") #echo "drop"
		MODE=2
		shift
		;;
	"-m") #echo "music"
		MODE=3
		shift
		;;
	*) echo "invalid command switch"
        	exit
		;;
esac

ADDR=$(echo ${1} | awk -F\. '{printf "%d.%d.%d.%d", $1, $2, $3, $4}')

PORT=${2}

if [ $3 -gt 99 ]; then
	MINTOADD=99
else
	MINTOADD=${3}
fi

SECTOADD=$((60 * $MINTOADD))
TIMECLOSESEC=$(($TIMENOWSEC + $SECTOADD))
TIMECLOSE=$(date -d @$TIMECLOSESEC +%R)

case $MODE in
	3)
		if [ $TESTING -eq 1 ]; then
			echo TEST:
			echo TEST: Got request to open port $MPDPORTS to $ADDR for $MINTOADD minutes
			echo TEST: Will be closing port $MPDPORTS at $TIMECLOSE
			echo TEST:
			exit
		else
			/sbin/iptables -R INP-FIREWALL ${MPDIPRULE} -s ${ADDR} -p tcp -m state --state NEW -m tcp --dport ${MPDPORTS} -j LOGNKEEP
			echo "/sbin/iptables -R INP-FIREWALL ${MPDIPRULE} -s ${ADDR} -p tcp -m state --state NEW -m tcp --dport ${MPDPORTS} -j LOGNDROP " > /tmp/atcmd
			sleep 1
			at -f /tmp/atcmd $TIMECLOSE
		fi
		;;
	2)
		if [ $TESTING -eq 1 ]; then
			echo TEST:
			echo TEST: Got request to close port $PORT to $ADDR immediately
			echo TEST:
			exit
		else
			/sbin/iptables -R INP-FIREWALL ${IPRULE} -s ${ADDR} -p tcp -m state --state NEW -m tcp --dport ${PORT} -j LOGNDROP
		fi
		;;
	1)
		if [ $TESTING -eq 1 ]; then
			echo TEST:
			echo TEST: Got request to open port $PORT to $ADDR for $MINTOADD minutes
			echo TEST: Will be closing port $PORT at $TIMECLOSE
			echo TEST:
			exit
		else
			/sbin/iptables -R INP-FIREWALL ${IPRULE} -s ${ADDR} -p tcp -m state --state NEW -m tcp --dport ${PORT} -j LOGNKEEP
			echo "/sbin/iptables -R INP-FIREWALL ${IPRULE} -s ${ADDR} -p tcp -m state --state NEW -m tcp --dport ${PORT} -j LOGNDROP " > /tmp/atcmd
			sleep 1
			at -f /tmp/atcmd $TIMECLOSE
		fi
		;;
	*)
		echo "invalid MODE set"
        	exit
		;;
esac

