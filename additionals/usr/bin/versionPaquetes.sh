#!/bin/bash

#versiones de paquete a buscar
# 1.- todos los rpm de elastix
# 2.- vtiger, roundcube, freepbx
# 3.- dahdi, asterisk, hylafax, iaxmoden

echo "RPM:Kernel"
uname -s -r -i

echo "RPM:Elastix"
rpm -q --queryformat "%{name} %{version} %{release}\n" elastix
rpm -qa --queryformat "%{name} %{version} %{release}\n" | grep -i elastix-

echo "RPM:RounCubeMail"
rpm -q --queryformat "%{name} %{version} %{release}\n" RoundCubeMail

echo "RPM:Mail"
rpm -q --queryformat "%{name} %{version} %{release}\n" postfix
rpm -q --queryformat "%{name} %{version} %{release}\n" cyrus-imapd

echo "RPM:IM"
rpm -q --queryformat "%{name} %{version} %{release}\n" openfire

echo "RPM:FreePBX"
rpm -q --queryformat "%{name} %{version} %{release}\n" freePBX

echo "RPM:Asterisk"
rpm -q --queryformat "%{name} %{version} %{release}\n" asterisk
rpm -q --queryformat "%{name} %{version} %{release}\n" asterisk-perl
rpm -q --queryformat "%{name} %{version} %{release}\n" asterisk-addons

echo "RPM:FAX"
rpm -q --queryformat "%{name} %{version} %{release}\n" hylafax
rpm -q --queryformat "%{name} %{version} %{release}\n" iaxmodem

echo "RPM:DRIVERS"
rpm -q --queryformat "%{name} %{version} %{release}\n" dahdi
rpm -q --queryformat "%{name} %{version} %{release}\n" rhino
rpm -q --queryformat "%{name} %{version} %{release}\n" wanpipe-util
