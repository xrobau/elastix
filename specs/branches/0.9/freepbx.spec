Summary: FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.
Name: freePBX
Version: 2.3.1
Release: 3
License: GPL
Group: Applications/System
#Source: freepbx-%{version}-withmodules.tar.gz
Source0: freepbx-%{version}.tar.gz
Source1: freepbx-additional-modules.tar.gz
Source2: freepbx-database.tar.gz
Source3: freepbx-rc.local
Source4: freepbx-fake-config.php
Patch0: freepbx-elastix.patch
#Patch1: freepbx-amportal.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires: asterisk, php, /bin/tar, /sbin/service
AutoReqProv: no

%description
FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.

%prep
%setup -n freepbx-%{version}

%patch0 -p1
#%patch1 -p1

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/var/www/html/admin/modules

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

# Copio las fuentes de freePBX en la carpeta temporal pues serviran en caso de actualizacion
cp %{SOURCE0} $RPM_BUILD_ROOT/tmp/

# Copio los archivos binarios de mysql en una carpeta temporal para ser utilizados en el POST
# siempre y cuando se trate de una instalacion de Elastix.
cp %{SOURCE2} $RPM_BUILD_ROOT/tmp/

# Copying some agi scripts needed by freepbx
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/agi-bin/* $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/agi-bin/* $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/

# Copying some asterisk configuration files modified by freepbx
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/astetc/* $RPM_BUILD_ROOT/etc/asterisk.elastix/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/etc/* $RPM_BUILD_ROOT/etc/asterisk.elastix/
mv $RPM_BUILD_DIR/freepbx-%{version}/asterisk.conf $RPM_BUILD_ROOT/etc/asterisk.elastix/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/bin/* $RPM_BUILD_ROOT/var/lib/asterisk/bin/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/bin/* $RPM_BUILD_ROOT/var/lib/asterisk/bin/

# Copying modules
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/* $RPM_BUILD_ROOT/var/www/html/admin/modules/
tar -xvzf %{SOURCE1} -C $RPM_BUILD_ROOT/var/www/html/admin/modules/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/.htaccess $RPM_BUILD_ROOT/var/www/html/admin/modules/
rm -rf $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules

cp %{SOURCE3} $RPM_BUILD_ROOT/tmp/rc.local
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs_panel/* $RPM_BUILD_ROOT/var/www/html/panel/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/mohmp3/* $RPM_BUILD_ROOT/var/lib/asterisk/mohmp3/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/sbin/* $RPM_BUILD_ROOT/usr/sbin/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/sounds/* $RPM_BUILD_ROOT/var/lib/asterisk/sounds/
mv $RPM_BUILD_DIR/freepbx-%{version}/amportal.conf $RPM_BUILD_ROOT/etc/
cp %{SOURCE4} $RPM_BUILD_ROOT/var/www/html/config.php

# Copying images
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/images $RPM_BUILD_ROOT/var/www/html/admin/
# FIXME: Maybe the following should be a slink
cp $RPM_BUILD_ROOT/var/www/html/admin/modules/dashboard/images/* $RPM_BUILD_ROOT/var/www/html/admin/images/
cp $RPM_BUILD_ROOT/var/www/html/admin/modules/recordings/images/* $RPM_BUILD_ROOT/var/www/html/admin/images/

# Copying everything else
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/* $RPM_BUILD_ROOT/var/www/html/admin/

%post

# FIXME: Creo que esto ya no es necesario. Se deberia poder hacer en la seccion files
cd /var/lib/asterisk/bin/
chown asterisk:asterisk *
chmod 755 *

#Cambio permisos en carpetas para que la interfase web pueda manipular archivos
#chown -R asterisk.asterisk /var/www/html/modules/pbxadmin/
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

/sbin/service mysqld status &>/dev/null
res=$?
# La base de datos esta corriendo
if [ $res -eq 0 ]; then
    # La base de datos existe
    if [ -f "/var/lib/mysql/asterisk" ]; then
        # Procedimiento de actualizacion aqui?
        # Por ahora no hago nada pero creo que se deberia invocar al instalador aqui install_amp
        echo "Installing freePBX... "
        tar -xvzf /tmp/freepbx-2.3.1.tar.gz -C /tmp/
        cd /tmp/freepbx-2.3.1/
        echo a | ./install_amp
    # La base de datos NO existe
    else
        # Creo la base de datos
        # Este caso ocurre si el usuario ha desinstalado previamente el RPM de freePBX
        /sbin/service mysqld stop
        echo "Installing Database... "
        tar -xvzf /tmp/freepbx-database.tar.gz -C /var/lib/mysql/
        chown -R mysql.mysql /var/lib/mysql/asterisk 
        /sbin/service mysqld start
    fi

# La base de datos esta apagada
else
    # La base de datos existe
    if [ -f "/var/lib/mysql/asterisk" ]; then
        # Abortar instalacion
        echo "MySQL service down!!!. Please start this service before installing this RPM. Aborting..."
        exit 255
    # La base de datos NO existe
    else
        # Creo la base de datos, incluido el esquema de usuario/permiso
        tar -xvzf /tmp/freepbx-database.tar.gz -C /var/lib/mysql/ 
        echo "Installing Database"
    fi
fi


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
* Mon Oct 29 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-3
  - Added some missed images 

* Mon Oct 29 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-2
  - Update to 2.3.1. 
  - Enhancements in the installation/upgrade process

* Mon Oct 22 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.2.3-16
  - Not loading chan_unicall by default 

* Mon Oct 15 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.2.3-14
  - FIX TO BUG: cant upload wav files.
  - FIX TO BUG: Operator Panel not update!
