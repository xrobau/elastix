Summary: Elastix is a Web based software to administrate a PBX based in open source programs
Name: elastix
Version: 0.7
Release: 0
License: GPL
Group: Applications/System
Source: elastix-0.7-0.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: /etc/sudoers
Prereq: httpd, sudo, php, php-pecl-sqlite, php-gd, iaxmodem, hylafax, asterisk, mysql-server, ntp

%description
Elastix is a Web based software to administrate a PBX based in open source programs

%prep
%setup -n elastix_neo

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/var/www/html
mkdir -p $RPM_BUILD_ROOT/var/www/db
mkdir -p $RPM_BUILD_ROOT/usr/local/elastix
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
mkdir -p $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p $RPM_BUILD_ROOT/var/log/iaxmodem
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mkdir -p $RPM_BUILD_ROOT/tmp

mv $RPM_BUILD_DIR/elastix_neo/otherFiles $RPM_BUILD_ROOT/tmp/

mv $RPM_BUILD_DIR/elastix_neo/* $RPM_BUILD_ROOT/var/www/html/
mv $RPM_BUILD_ROOT/tmp/otherFiles/db/* $RPM_BUILD_ROOT/var/www/db/
mv $RPM_BUILD_ROOT/tmp/otherFiles/crons/sampler.php $RPM_BUILD_ROOT/usr/local/elastix/
mv $RPM_BUILD_ROOT/tmp/otherFiles/crons/elastix.cron $RPM_BUILD_ROOT/etc/cron.d/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/setup.cache $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mv $RPM_BUILD_ROOT/tmp/otherFiles/scripts/sudo-config.sh $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/CentOS-Base.repo $RPM_BUILD_ROOT/etc/yum.repos.d
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/elastix.repo $RPM_BUILD_ROOT/etc/yum.repos.d

#Elimino archivos temporales que sobran
rm -rf $RPM_BUILD_ROOT/tmp/otherFiles

%post

#Cambio permisos en carpetas para que la interfase web pueda manipular archivos
chown -R asterisk.asterisk /var/www/html/configs
chown -R asterisk.asterisk /var/www/html/help
chown -R asterisk.asterisk /var/www/html/images
chown -R asterisk.asterisk /var/www/html/libs
chown -R asterisk.asterisk /var/www/html/modules
chown -R asterisk.asterisk /var/www/html/static
chown -R asterisk.asterisk /var/www/html/themes
chown -R asterisk.asterisk /var/www/db

# Cambiar permisos del archivo /etc/sasldb2 a 644 
chmod 644 /etc/sasldb2

#Elimino archivos de fax que sobran
rm -f /etc/iaxmodem/iaxmodem-cfg.ttyIAX
rm -f /var/spool/hylafax/etc/config.ttyIAX

#Habilito inicio automático de servicios necesarios
chkconfig --level 2345 hylafax on
chkconfig --level 2345 iaxmodem on
chkconfig --level 345 ntpd on
chkconfig --level 345 mysqld on
chkconfig --level 345 httpd on
chkconfig --level 345 saslauthd on
chkconfig --level 345 cyrus-imapd on
chkconfig --del cups
chkconfig --del gpm
chkconfig --del cpuspeed

#Agrego líneas de Sudo
chmod +x /tmp/sudo-config.sh
/tmp/sudo-config.sh
rm -f /tmp/sudo-config.sh

# Agrego Enlaces para Hylafax
ln -s pdf2fax.gs /var/spool/hylafax/bin/pdf2fax
ln -s ps2fax.gs /var/spool/hylafax/bin/ps2fax

%pre
# if not exist add the asterisk group
grep -c "^asterisk:" %{_sysconfdir}/group &> /dev/null
if [ $? = 1 ]
then
        echo "   0:adding group asterisk..."
        /usr/sbin/groupadd -r -f asterisk
else
        echo "   0:group asterisk already present"
fi

# ****Agregar el usuario cyrus con el comando saslpasswd2:
echo "palosanto" | /usr/sbin/saslpasswd2 -c cyrus -u example.com

# Modifico usuario asterisk para que tenga "/bin/bash" como shell
/usr/sbin/usermod -c "Asterisk VoIP PBX" -g asterisk -s /bin/bash -d /var/lib/asterisk asterisk

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/html/*
/var/www/db/*
%defattr(-, root, root)
/tmp/sudo-config.sh
/usr/local/elastix/sampler.php
/etc/cron.d/elastix.cron
/etc/yum.repos.d/*
/var/spool/hylafax/etc/setup.cache
%dir
/var/log/iaxmodem
