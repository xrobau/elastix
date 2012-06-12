Summary: FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.
Name: freePBX
Version: 2.2.3
Release: 15
License: GPL
Group: Applications/System
Source: freepbx-%{version}-withmodules.tar.gz
Patch0: freepbx-elastix.patch
Patch1: freepbx-2.2.3-elastix-0.8.5.patch
Patch2: freepbx-2.2.3-amportal.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires: asterisk, elastix >= 0.8-5, php
AutoReqProv: no

%description
FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.

%prep
%setup -n freepbx-%{version}
%patch0 -p1
%patch1 -p1
%patch2 -p1

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

# Esta es la nueva ubicacion de los archivos de freepbx
# Ahora sera un modulo de Elastix
mkdir -p $RPM_BUILD_ROOT/var/www/html/modules/pbxadmin

mkdir -p $RPM_BUILD_ROOT/var/www/html/panel
mkdir -p $RPM_BUILD_ROOT/etc
mkdir -p $RPM_BUILD_ROOT/etc/rc.d/
mkdir -p $RPM_BUILD_ROOT/etc/asterisk.elastix
mkdir -p $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/
mkdir -p $RPM_BUILD_ROOT/var/lib/asterisk/bin/
mkdir -p $RPM_BUILD_ROOT/var/lib/asterisk/mohmp3/
mkdir -p $RPM_BUILD_ROOT/var/lib/asterisk/sounds/
mkdir -p $RPM_BUILD_ROOT/usr/sbin/
mkdir -p $RPM_BUILD_ROOT/tmp

mv $RPM_BUILD_DIR/freepbx-%{version}/rc.local $RPM_BUILD_ROOT/tmp
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/* $RPM_BUILD_ROOT/var/www/html/modules/pbxadmin/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs_panel/* $RPM_BUILD_ROOT/var/www/html/panel/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/agi-bin/* $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/bin/* $RPM_BUILD_ROOT/var/lib/asterisk/bin/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/mohmp3/* $RPM_BUILD_ROOT/var/lib/asterisk/mohmp3/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/sbin/* $RPM_BUILD_ROOT/usr/sbin/
mv $RPM_BUILD_DIR/freepbx-%{version}/asterisk.conf $RPM_BUILD_ROOT/etc/asterisk.elastix/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/astetc/* $RPM_BUILD_ROOT/etc/asterisk.elastix/
mv $RPM_BUILD_DIR/freepbx-%{version}/amportal.conf $RPM_BUILD_ROOT/etc/
mv $RPM_BUILD_DIR/freepbx-%{version}/config.php $RPM_BUILD_ROOT/var/www/html/
#mv $RPM_BUILD_DIR/freepbx-%{version}/etc-asterisk/* $RPM_BUILD_ROOT/etc/asterisk.elastix/

rm -rf $RPM_BUILD_ROOT/var/www/html/modules/pbxadmin/modules/zoip/

%post

#Destarizo freepbx-%{version}/amp_conf/bin/retrieve_X_conf_from_mysql.tgz
cd /var/lib/asterisk/bin/
#tar zxf retrieve_X_conf_from_mysql.tgz
#rm -f retrieve_X_conf_from_mysql.tgz
chown asterisk:asterisk *
chmod 755 *
# Hago un link simbolico para mantener compatibilidad
ln -s /var/www/html/modules/pbxadmin /var/www/html/admin


#Cambio permisos en carpetas para que la interfase web pueda manipular archivos
chown -R asterisk.asterisk /var/www/html/modules/pbxadmin/
chmod 755 /etc/amportal.conf
touch /var/www/html/panel/op_buttons_additional.cfg
touch /var/www/html/panel/op_buttons_custom.cfg
touch /var/log/asterisk/cdr-csv/Master.csv
chmod 777  /var/log/asterisk/cdr-csv/Master.csv
mkdir -p /var/lib/asterisk/sounds/custom/
chmod 755 /etc/rc.d/rc.local

# Cambio carpeta de archivos de configuración de Asterisk
#mv /etc/asterisk /etc/asterisk.orig
mv -f /etc/asterisk.elastix/* /etc/asterisk/

# Cambio contenido de "rc.local"
cat /tmp/rc.local > /etc/rc.d/rc.local
rm -f /tmp/rc.local

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/etc/asterisk.elastix/*
/var/www/html/*
/var/lib/asterisk/*
#/var/www/db/freepbx.db
%defattr(-, root, root)
/etc/amportal.conf
/etc/rc.d
/usr/sbin/amportal
/tmp/*

%changelog
* Mon Oct 15 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.2.3-14
  - FIX TO BUG: cant upload wav files.
  - FIX TO BUG: Operator Panel not update!
