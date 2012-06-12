Summary: Elastix is a Web based software to administrate a PBX based in open source programs
Name: elastix
Version: 0.9.0
Release: 7 
License: GPL
Group: Applications/System
Source: elastix-0.9.0-7.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: /etc/sudoers
Prereq: httpd, sudo, php, php-sqlite3, php-gd, iaxmodem, hylafax, asterisk, mysql-server, ntp, php-pear, postfix
Prereq: freePBX >= 2.3

%description
Elastix is a Web based software to administrate a PBX based in open source programs

%prep
%setup -n elastix

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/var/www/html
mkdir -p $RPM_BUILD_ROOT/var/www/db
mkdir -p $RPM_BUILD_ROOT/usr/local/elastix
mkdir -p $RPM_BUILD_ROOT/usr/share/pear
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
mkdir -p $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p $RPM_BUILD_ROOT/var/log/iaxmodem
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mkdir -p $RPM_BUILD_ROOT/tmp

mv $RPM_BUILD_DIR/elastix/otherFiles $RPM_BUILD_ROOT/tmp/

mv $RPM_BUILD_DIR/elastix/* $RPM_BUILD_ROOT/var/www/html/
mv $RPM_BUILD_ROOT/tmp/otherFiles/db/* $RPM_BUILD_ROOT/var/www/db/
mv $RPM_BUILD_ROOT/tmp/otherFiles/pear/* $RPM_BUILD_ROOT/usr/share/pear/
mv $RPM_BUILD_ROOT/tmp/otherFiles/crons/sampler.php $RPM_BUILD_ROOT/usr/local/elastix/
mv $RPM_BUILD_ROOT/tmp/otherFiles/crons/elastix.cron $RPM_BUILD_ROOT/etc/cron.d/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/setup.cache $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/sudoers.elastix $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/php.ini $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/httpd.conf $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/CentOS-Base.repo $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/FaxDictionary $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/virtual.db $RPM_BUILD_ROOT/tmp/
mv $RPM_BUILD_ROOT/tmp/otherFiles/configs/elastix.repo $RPM_BUILD_ROOT/etc/yum.repos.d
#mv $RPM_BUILD_ROOT/tmp/otherFiles/hylafax $RPM_BUILD_ROOT/tmp/


#Elimino archivos temporales que sobran
rm -rf $RPM_BUILD_ROOT/tmp/otherFiles

%post
#Creo el archivo /etc/postfix/network_table
touch /etc/postfix/network_table
echo "127.0.0.1/32" >  /etc/postfix/network_table

if [ ! -f /etc/postfix/virtual.db ]
then
   mv /tmp/virtual.db /etc/postfix/virtual.db
   chown root:root /etc/postfix/virtual.db
else
   rm -f /tmp/virtual.db
fi

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
chkconfig --level 345 postfix on
chkconfig --del cups
chkconfig --del gpm

cat /tmp/sudoers.elastix > /etc/sudoers
rm -f /tmp/sudoers.elastix

#Cambio httpd.conf y php.ini
cat /tmp/php.ini > /etc/php.ini
cat /tmp/httpd.conf > /etc/httpd/conf/httpd.conf
rm -f /tmp/php.ini
rm -f /tmp/httpd.conf

#Cambio el contenido del CentOS.repo
cat /tmp/CentOS-Base.repo > /etc/yum.repos.d/CentOS-Base.repo
rm -f /tmp/CentOS-Base.repo

#Agrego archivo para cabecera de Fax
cat /tmp/FaxDictionary > /var/spool/hylafax/etc/FaxDictionary
chown uucp:uucp /var/spool/hylafax/etc/FaxDictionary
rm -f /tmp/FaxDictionary

# Agrego Enlaces para Hylafax
ln -f -s pdf2fax.gs /var/spool/hylafax/bin/pdf2fax
ln -f -s ps2fax.gs /var/spool/hylafax/bin/ps2fax

# Configuraciones adicionales para Visor de Faxes
mkdir -p /var/www/html/faxes
chown -R asterisk.asterisk /var/www/html/faxes
chmod -R 755 /var/www/html/faxes

mkdir -p /var/www/html/modules/faxvisor
chown -R asterisk.asterisk /var/www/html/modules/faxvisor
chmod -R 755 /var/www/html/modules/faxvisor

chmod 777 /var/www/db
chmod 777 /var/www/db/fax.db

#mv /var/spool/hylafax/bin/faxrcvd /var/spool/hylafax/bin/faxrcvd.old
#mv /tmp/hylafax/* /var/spool/hylafax/bin/
#chmod -R 755 /var/spool/hylafax/bin/includes
#chmod -R 755 /var/spool/hylafax/bin/faxrcvd
#chown root:root /var/spool/hylafax/bin/includes
#chown root:root  /var/spool/hylafax/bin/faxrcvd

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
%config(noreplace) /var/www/db/*
%defattr(-, root, root)
/tmp/*
/usr/share/pear
/usr/local/elastix/sampler.php
/etc/cron.d/elastix.cron
/etc/yum.repos.d/*
/var/spool/hylafax/etc/setup.cache
%dir
/var/log/iaxmodem


%changelog
* Tue Oct 30 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-7
  - Add new menus in the help link package elastix-0.9.0-7.
* Mon Oct 29 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-6
  - Changes in freePBX version 2.3 and inteface web freePBX is dual operation correction error.
* Fri Oct 26 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-5
  - Changes in freePBX version 2.3 and inteface web freePBX is dual operation, standar format the version rpms.
* Thu Oct 25 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-4
  - Add Link version Elastix, changes in the module Backup in this version elastix-0.9-4.tar.gz
* Mon Oct 22 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-3
  - Add new modules and better funcionality in this version elastix-0.9-3.tar.gz
* Mon Oct 22 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-2
  - Add new modules, changes order in menus in this version elastix-0.9-2.tar.gz
* Wed Oct 19 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-1
  - Add new modules in this version elastix-0.9-1.tar.gz
* Tue Oct  9 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.0-1
  - Hylafax changes removed. These changes should be made in the hylafax RPM.

