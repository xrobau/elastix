#!/bin/sh

# Check for existence of /etc/init.d/wanrouter
if [ ! -e /etc/init.d/wanrouter ] ; then
        if [ -e /usr/sbin/wanrouter ] ; then
                ln -s /usr/sbin/wanrouter /etc/init.d/wanrouter
		service asterisk stop > /dev/null 2>&1
		service dahdi stop > /dev/null 2>&1
		service wanrouter stop > /dev/null 2>&1
		service wanrouter start > /dev/null 2>&1
		service dahdi start > /dev/null 2>&1
		service asterisk start > /dev/null 2>&1
        fi
fi

IFCONFIG=`which ifconfig 2>/dev/null||echo /sbin/ifconfig`
IPADDR=`LANG=en_US.UTF-8 $IFCONFIG eth0|gawk '/inet addr/{print $2}'|gawk -F: '{print $2}'`
MSJ_NO_IP_DHCP="If you could not get a DHCP IP address please type setup and select \"Network configuration\" to set up a static IP."

echo ""
echo "Welcome to Elastix "
echo "----------------------------------------------------"
echo ""
#echo "For access to the Elastix web GUI use this URL"
echo "To access your Elastix System, using a separate workstation (PC/MAC/Linux)"
echo "Open the Internet Browser using the following URL:"

if [ "$IPADDR" = "" ]; then
   echo "http://<YOUR-IP-HERE>"
   echo "$MSJ_NO_IP_DHCP"
else
   echo "http://$IPADDR"
fi

echo ""
