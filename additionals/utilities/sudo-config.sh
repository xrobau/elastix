#!/bin/bash

# "-------------------------------------------"
# "Configuring sudo"
# "-------------------------------------------"

if ! grep "asterisk ALL = NOPASSWD: /sbin/shutdown" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /sbin/shutdown" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /sbin/shutdown added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /usr/bin/nmap" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /usr/bin/nmap" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /usr/bin/nmap added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /usr/bin/yum" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /usr/bin/yum" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /usr/bin/yum added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /bin/touch" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /bin/touch" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /bin/touch added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /bin/chmod" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /bin/chmod" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /bin/chmod added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /bin/chown" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /bin/chown" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /bin/chown added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /sbin/service" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /sbin/service" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /sbin/service added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /sbin/init" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /sbin/init" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /sbin/init added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /usr/sbin/postmap" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /usr/sbin/postmap" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /usr/sbin/postmap added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /usr/sbin/postfix" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /usr/sbin/postfix" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /usr/sbin/postfix added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /usr/sbin/saslpasswd2" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /usr/sbin/saslpasswd2" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /usr/sbin/saslpasswd2 added to /etc/sudoers"
fi

if ! grep "uucp ALL = NOPASSWD: /bin/chmod" /etc/sudoers >/dev/null 2>&1; then
	echo "uucp ALL = NOPASSWD: /bin/chmod" >> /etc/sudoers
	echo "uucp ALL = NOPASSWD: /bin/chmod added to /etc/sudoers"
fi
