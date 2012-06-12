Summary: FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.
Name: freePBX
Version: 2.8.1
Release: 8 
License: GPL
Group: Applications/System
Source0: freepbx-%{version}.tar.gz
Source1: freepbx-modules-%{version}.1.tgz
#Source2: freepbx-database-%{version}.tar.gz
Source2: freepbx-database-dump-%{version}.1.sql
Source3: freepbx-rc.local
Source4: freepbx-fake-config.php
Source5: musiconhold_additional.conf
Source6: chan_dahdi.conf
#Source8: extensions_override_freepbx.conf

Patch0: freepbx-2.8.0_elastix_files_config.patch
Patch1: freepbx-2.8.1_elastix_bin_freepbx_engine.patch
Patch2: freepbx-2.8.1_elastix_files_config_vmemail.patch
#Patch3: freepbx-2.5.1_dahdi-channels.patch
Patch4: freepbx-2.3.1_agent-login.patch
Patch5: freepbx-2.3.1_conferences.patch
Patch6: freepbx-2.3.1_calendar-event.patch
#Patch7: freepbx-2.5.0_elastix_core_agi_bin_recordingcheck.patch
#Patch8: freepbx-2.5.1_elastix_core_functions.patch
Patch9: freepbx-2.5.0_weather_wakeup.patch
#Patch10: freepbx-2.6.0-replace-zap-dahdi.patch
Patch11: freepbx-2.7.0-remove-fallback-database-user.patch
Patch12: freepbx-2.8.0-rename-moh-mohmp3.patch
Patch13: freepbx-2.7.0-fix-strpos-comparison.patch
Patch14: freepbx-2.8.1_disabled_freepbx_by_elastix.patch
#Patch 15 only apply to freepbx-modules tar (SOURCE1)
Patch15: freepbx-2.8.1_changed_message_faxlicense.patch 
Patch16: freepbx-2.8.1_htaccess.patch
Patch17: freepbx-2.8.1_flash_operator_panel_validation.patch
Patch18: freepbx-2.8.1_call_limit_functions.patch
Patch19: freepbx-2.8.1_call_limit_libfreepbx.patch
Patch20: freepbx-2.8.1_backupFiles.patch

BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
PreReq: asterisk >= 1.6.2.13, /sbin/service, /bin/tar, elastix-firstboot
PreReq: php
Requires: elastix >= 2.2.0-15
AutoReqProv: no
#Obsoletes: /var/www/html/modules/pbxadmin/index.php
#Obsoletes: freePBX < 2.3.1

%description
FreePBX is the most powerful GUI configuration tool for Asterisk. It provides everything that a standard legacy phone system can, plus a huge amount of new features.

%prep
%setup -n freepbx-%{version}

%patch0 -p1
%patch1 -p1
%patch2 -p1
#%patch3 -p1
%patch4 -p1
%patch5 -p1 
%patch6 -p1
#%patch7 -p1
#%patch8 -p1
%patch9 -p1
#%patch10 -p1
%patch11 -p1
%patch12 -p1
%patch14 -p1
%patch16 -p1
%patch17 -p1
%patch18 -p1
%patch19 -p1
%patch20 -p1

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
mkdir -p $RPM_BUILD_ROOT/usr/share/freepbx/tmp/

#Fixed bug, module recordings missed
mkdir -p $RPM_BUILD_ROOT/var/www/html/recordings/

#mkdir -p $RPM_BUILD_ROOT/etc/asterisk

# Copio las fuentes de freePBX en la carpeta temporal pues serviran en caso de actualizacion
#cp %{SOURCE0} $RPM_BUILD_ROOT/usr/share/freepbx/tmp/freepbx-%{version}.tar.gz

# Copio los modulos adicionales de freepbx en la carpeta temporal, esto será utilizado en el
# caso de las actulizaciones para que todos los modulos queden de forma correcta.
#cp %{SOURCE1} $RPM_BUILD_ROOT/usr/share/freepbx/tmp/freepbx-modules-%{version}.4.tar.gz

# El parche 11,13 debe aplicarse también a los tar que se copian en /usr/share
mkdir temp
cd temp

# Se descomprime y parcha FreePBX principal
tar -xzf %{SOURCE0}
cd freepbx-%{version}/
patch -p1 < %{PATCH11}
patch -p1 < %{PATCH13}
patch -p1 < %{PATCH14}
patch -p1 < %{PATCH17}
patch -p1 < %{PATCH18}
patch -p1 < %{PATCH19}
patch -p1 < %{PATCH20}
cd ..
tar -czf freepbx-%{version}.tar.gz freepbx-%{version}/
mv freepbx-%{version}.tar.gz $RPM_BUILD_ROOT/usr/share/freepbx/tmp/
rm -rf freepbx-%{version}/

# Se descomprime y parcha módulos a instalar
mkdir temp2
cd temp2
tar -xzf %{SOURCE1}
cd framework/
patch -p2 < %{PATCH11}
patch -p1 < %{PATCH13}
patch -p2 < %{PATCH19}
cd ..
patch -p1 < %{PATCH15}
cd fw_fop/
patch -p2 < %{PATCH17}
cd ..
patch -p5 < %{PATCH18}
tar -czf ../freepbx-modules-%{version}.1.tgz *
cd ..
rm -rf temp2
mv freepbx-modules-%{version}.1.tgz $RPM_BUILD_ROOT/usr/share/freepbx/tmp/

cd ..
rmdir temp


# Copio los archivos binarios de mysql en una carpeta temporal para ser utilizados en el POST
# siempre y cuando se trate de una instalacion de Elastix.
cp %{SOURCE2} $RPM_BUILD_ROOT/usr/share/freepbx/tmp/

# Copying some agi scripts needed by freepbx
cp -ra $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/agi-bin/* $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/
cp -ra $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/agi-bin/* $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/

# Copying some asterisk configuration files modified by freepbx
cp -ra $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/astetc/* $RPM_BUILD_ROOT/etc/asterisk.elastix/
cp %{SOURCE5} $RPM_BUILD_ROOT/etc/asterisk.elastix/
#mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/etc/* $RPM_BUILD_ROOT/etc/asterisk.elastix/
mv $RPM_BUILD_DIR/freepbx-%{version}/asterisk.conf $RPM_BUILD_ROOT/etc/asterisk.elastix/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/bin/* $RPM_BUILD_ROOT/var/lib/asterisk/bin/
#cp -ra $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/core/bin/* $RPM_BUILD_ROOT/var/lib/asterisk/bin/

# Copying modules
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/* $RPM_BUILD_ROOT/var/www/html/admin/modules/
#tar -xvzf %{SOURCE1} -C $RPM_BUILD_ROOT/var/www/html/admin/modules/
#Es mejor usar el freepbx-modules-*.tgz ya que esta parchado, %{SOURCE1} referencia a la fuente sin parches
tar -xvzf $RPM_BUILD_ROOT/usr/share/freepbx/tmp/freepbx-modules-%{version}.1.tgz -C $RPM_BUILD_ROOT/var/www/html/admin/modules/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules/.htaccess $RPM_BUILD_ROOT/var/www/html/admin/modules/
rm -rf $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/modules

cp %{SOURCE3} $RPM_BUILD_ROOT/usr/share/freepbx/tmp/rc.local
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs_panel/* $RPM_BUILD_ROOT/var/www/html/panel/

#mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/mohmp3/* $RPM_BUILD_ROOT/var/lib/asterisk/mohmp3/
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/sbin/* $RPM_BUILD_ROOT/usr/sbin/

mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/sounds/* $RPM_BUILD_ROOT/var/lib/asterisk/sounds/
mv $RPM_BUILD_DIR/freepbx-%{version}/amportal.conf $RPM_BUILD_ROOT/etc/
cp %{SOURCE4} $RPM_BUILD_ROOT/var/www/html/config.php

# Copying images
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/images $RPM_BUILD_ROOT/var/www/html/admin/

# FIXME: Maybe the following should be a slink
cp $RPM_BUILD_ROOT/var/www/html/admin/modules/dashboard/images/* $RPM_BUILD_ROOT/var/www/html/admin/images/
cp $RPM_BUILD_ROOT/var/www/html/admin/modules/recordings/images/* $RPM_BUILD_ROOT/var/www/html/admin/images/
#Fixed bug, module recordins missed.
cp -ra $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/recordings/*  $RPM_BUILD_ROOT/var/www/html/recordings/

# Copying everything else
mv $RPM_BUILD_DIR/freepbx-%{version}/amp_conf/htdocs/admin/* $RPM_BUILD_ROOT/var/www/html/admin/

#put new zapata.conf custom of elastix. 
cp %{SOURCE6} $RPM_BUILD_ROOT/etc/asterisk.elastix/

#put calendarEvent.gsm custom of elastix by module calendar. 
#cp %{SOURCE7} $RPM_BUILD_ROOT/var/lib/asterisk/sounds/

#put extensions_override_freepbx.conf file this fixed bug cdrs duplicated.
#cat %{SOURCE8} > $RPM_BUILD_ROOT/etc/asterisk/extensions_override_freepbx.conf


# Configuration files
touch $RPM_BUILD_ROOT/var/www/html/panel/op_buttons_additional.cfg
touch $RPM_BUILD_ROOT/var/www/html/panel/op_buttons_custom.cfg

# chmod 755 $RPM_BUILD_ROOT/var/www/html/panel/convert_config_pre_14.pl
chmod 755 $RPM_BUILD_ROOT/var/www/html/panel/op_server.pl
chmod 755 $RPM_BUILD_ROOT/var/www/html/panel/safe_opserver

# CDR dump as CSV
mkdir -p $RPM_BUILD_ROOT/var/log/asterisk/cdr-csv/
#chown -R asterisk.asterisk $RPM_BUILD_ROOT/var/log/asterisk/
touch $RPM_BUILD_ROOT/var/log/asterisk/cdr-csv/Master.csv

%pre
# Asegurarse de que este freePBX no sobreescriba una versión actualizada a mano
if [ $1 -eq 2 ] ; then
    MYSQL_ROOTPWD=`grep mysqlrootpwd= /etc/elastix.conf | sed 's/^mysqlrootpwd=//'`
    FREEPBX_CURRVER=`echo "SELECT value FROM asterisk.admin WHERE variable = 'version';" | mysql -s -u root -p$MYSQL_ROOTPWD`
    FREEPBX_NEWVER=%{version}
    php -r "function c(\$a,\$b){while(count(\$a)>0&&count(\$b)>0){\$ax=array_shift(\$a);\$bx=array_shift(\$b);if(\$ax>\$bx)return 1;if(\$ax<\$bx)return -1;}if(count(\$a)>0)return 1;if(count(\$b)>0)return -1;return 0;}exit(c(explode('.', '$FREEPBX_CURRVER'), explode('.', '$FREEPBX_NEWVER'))>0?1:0);"
    res=$?
    if [ $res -eq 1 ] ; then
        echo "FATAL: tried to install FreePBX $FREEPBX_NEWVER, but system has FreePBX $FREEPBX_CURRVER"
        exit 1
    fi
fi

%post

# Cambio contenido de "rc.local"
cat /usr/share/freepbx/tmp/rc.local > /etc/rc.d/rc.local
rm -f /usr/share/freepbx/tmp/rc.local
chmod 755 /etc/rc.d/rc.local

/sbin/service mysqld status &>/dev/null
res=$?
# La base de datos esta corriendo
if [ $res -eq 0 ]; then
    # La base de datos existe
    # NOTA: Es muy importante notar que si la db esta corriendo y la base 'asterisk' existe no necesariamente
    #       tendria q ejecutar el script ./install_amp porque no necesariamente quiero actualizar
    if [ -d "/var/lib/mysql/asterisk" ]; then
        # Procedimiento de actualizacion aqui?
        # Por ahora no hago nada pero creo que se deberia invocar al instalador aqui install_amp
        echo "Installing freePBX... "
        tar -xvzf /usr/share/freepbx/tmp/freepbx-2.8.1.tar.gz -C /usr/share/freepbx/tmp/

	# Se copia los archivos dentro de la carpeta amp_conf/htdocs/admin/modules/
	tar -xvzf /usr/share/freepbx/tmp/freepbx-modules-2.8.1.1.tgz -C /usr/share/freepbx/tmp/freepbx-2.8.1/amp_conf/htdocs/admin/modules/

        cd /usr/share/freepbx/tmp/freepbx-2.8.1/
        echo a | ./install_amp
    
    # La base de datos NO existe
    else
        # Creo la base de datos
        # Este caso ocurre si el usuario ha desinstalado previamente el RPM de freePBX

	elastix_root_password=`grep mysqlrootpwd= /etc/elastix.conf | sed 's/^mysqlrootpwd=//'`
	echo "Installing database from SQL dump..."
	mysql -u root -p$elastix_root_password < /usr/share/freepbx/tmp/freepbx-database-dump-2.8.1.1.sql
	ret=$?
        if [ $ret -ne 0 ] ; then
               	exit $ret
	fi	

	# Ruta a módulos es incorrecta en 64 bits. Se corrige a partir de ruta de Asterisk.
	RUTAREAL=`grep astmoddir /etc/asterisk/asterisk.conf | sed 's|^.* \(/.\+\)$|\1|' -`
	sed --in-place "s|/usr/lib/asterisk/modules|$RUTAREAL|g" /etc/asterisk.elastix/asterisk.conf
	sed --in-place "s|/usr/lib/asterisk/modules|$RUTAREAL|g" /etc/amportal.conf

        # Cambio carpeta de archivos de configuración de Asterisk
        mv -f /etc/asterisk.elastix/* /etc/asterisk/
    fi

# La base de datos esta apagada
else
    # La base de datos existe
    if [ -d "/var/lib/mysql/asterisk" ]; then
        # Abortar instalacion
        echo "MySQL service down!!!. Please start this service before installing this RPM. Aborting..."
        exit 255
    # La base de datos NO existe
    else
        # Creo la base de datos, incluido el esquema de usuario/permiso
        echo "Assumed ISO installation. Delayed database installation until first Elastix boot..."
        cp /usr/share/freepbx/tmp/freepbx-database-dump-2.8.1.1.sql /var/spool/elastix-mysqldbscripts/01-freepbx.sql

        # Ruta a módulos es incorrecta en 64 bits. Se corrige a partir de ruta de Asterisk.
        RUTAREAL=`grep astmoddir /etc/asterisk/asterisk.conf | sed 's|^.* \(/.\+\)$|\1|' -`
        sed --in-place "s|/usr/lib/asterisk/modules|$RUTAREAL|g" /etc/asterisk.elastix/asterisk.conf
        sed --in-place "s|/usr/lib/asterisk/modules|$RUTAREAL|g" /etc/amportal.conf

        # Cambio carpeta de archivos de configuración de Asterisk
        mv -f /etc/asterisk.elastix/* /etc/asterisk/
    fi
fi

# Creo unos links simbolicos para algunos archivos de configuracion.
# Esto solo es necesario en una instalacion desde cero, donde no se ejecute el comando
# ./install_amp, pues este comando ya realiza esta tarea

# FIXME: Estas lineas deberían ser mas inteligentes y crear un ln para cada 
#        archivo encontrado en la carpeta /var/www/html/admin/modules/core/etc/

# Primero reviso si hay archivos de configuracion que sean archivos normales (no symlinks)
# Esto es porque por omision asterisk pone estos archivos y hay que borrarlos
# Ademas en versiones del este rpm menores a 2.4 se ponian archivos regulares en lugar de los
# links a los archivos dentro de freepbx, lo cual estaba mal
if [ -f "/etc/asterisk/extensions.conf" ] ; then
    echo "Backing up old /etc/asterisk/extensions.conf as /etc/asterisk/extensions.conf.old_%{name}-%{version}-%{release}"
    mv /etc/asterisk/extensions.conf /etc/asterisk/extensions.conf.old_%{name}-%{version}-%{release}
fi
if [ -f "/etc/asterisk/iax.conf" ] ; then
    echo "Backing up old /etc/asterisk/iax.conf as /etc/asterisk/iax.conf.old_%{name}-%{version}-%{release}"
    mv /etc/asterisk/iax.conf /etc/asterisk/iax.conf.old_%{name}-%{version}-%{release}
fi
if [ -f "/etc/asterisk/sip.conf" ] ; then
    echo "Backing up old /etc/asterisk/sip.conf as /etc/asterisk/sip.conf.old_%{name}-%{version}-%{release}"
    mv /etc/asterisk/sip.conf /etc/asterisk/sip.conf.old_%{name}-%{version}-%{release}
fi
if [ -f "/var/lib/asterisk/bin/fax-process.pl" ] ; then
    echo "Backing up old /var/lib/asterisk/bin/fax-process.pl as /var/lib/asterisk/bin/fax-process.pl.old_%{name}-%{version}-%{release}"
    mv /var/lib/asterisk/bin/fax-process.pl /var/lib/asterisk/bin/fax-process.pl.old_%{name}-%{version}-%{release}
fi
if [ -f "/etc/asterisk/features.conf" ] ; then
    echo "Backing up old /etc/asterisk/features.conf as /etc/asterisk/features.conf.old_%{name}-%{version}-%{release}"
    mv /etc/asterisk/features.conf /etc/asterisk/features.conf.old_%{name}-%{version}-%{release}
fi

if [ -f "/etc/asterisk/sip_notify.conf" ] ; then
    echo "Backing up old /etc/asterisk/sip_notify.conf as /etc/asterisk/sip_notify.conf.old_%{name}-%{version}-%{release}"
    mv /etc/asterisk/sip_notify.conf /etc/asterisk/sip_notify.conf.old_%{name}-%{version}-%{release}
fi

if [ ! -e "/etc/asterisk/extensions.conf" ]; then
    ln -s /var/www/html/admin/modules/core/etc/extensions.conf /etc/asterisk/extensions.conf
fi
if [ ! -e "/etc/asterisk/iax.conf" ]; then
    ln -s /var/www/html/admin/modules/core/etc/iax.conf /etc/asterisk/iax.conf
fi
if [ ! -e "/etc/asterisk/sip.conf" ]; then
    ln -s /var/www/html/admin/modules/core/etc/sip.conf /etc/asterisk/sip.conf
fi
if [ ! -e "/var/lib/asterisk/bin/fax-process.pl" ]; then
    ln -s /var/www/html/admin/modules/core/bin/fax-process.pl /var/lib/asterisk/bin/fax-process.pl
fi
if [ ! -e "/etc/asterisk/features.conf" ]; then
    ln -s /var/www/html/admin/modules/core/etc/features.conf /etc/asterisk/features.conf
fi
if [ ! -e "/etc/asterisk/sip_notify.conf" ]; then
    ln -s /var/www/html/admin/modules/core/etc/sip_notify.conf /etc/asterisk/sip_notify.conf
fi

# The following files must exist (even if empty) for asterisk 1.6.x to work correctly.
# This does not belong in %%install because these files are dynamically created.
touch /etc/asterisk/manager_additional.conf
touch /etc/asterisk/sip_general_custom.conf
touch /etc/asterisk/sip_nat.conf
touch /etc/asterisk/sip_registrations_custom.conf
touch /etc/asterisk/sip_registrations.conf
touch /etc/asterisk/sip_custom.conf
touch /etc/asterisk/sip_additional.conf
touch /etc/asterisk/sip_custom_post.conf
touch /etc/asterisk/extensions_override_freepbx.conf
touch /etc/asterisk/features_general_additional.conf
touch /etc/asterisk/sip_general_additional.conf
touch /etc/asterisk/queues_general_additional.conf
touch /etc/asterisk/dahdi-channels.conf
touch /etc/asterisk/meetme_additional.conf
touch /etc/asterisk/sip_general_additional.conf
touch /etc/asterisk/iax_general_additional.conf
touch /etc/asterisk/musiconhold_custom.conf
touch /etc/asterisk/extensions_additional.conf
touch /etc/asterisk/features_general_custom.conf
touch /etc/asterisk/queues_custom_general.conf
touch /etc/asterisk/chan_dahdi_additional.conf
touch /etc/asterisk/iax_registrations_custom.conf
touch /etc/asterisk/features_applicationmap_additional.conf
touch /etc/asterisk/queues_custom.conf
touch /etc/asterisk/iax_registrations.conf
touch /etc/asterisk/features_applicationmap_custom.conf
touch /etc/asterisk/queues_additional.conf
touch /etc/asterisk/iax_custom.conf
touch /etc/asterisk/features_featuremap_additional.conf
touch /etc/asterisk/queues_post_custom.conf
touch /etc/asterisk/iax_additional.conf
touch /etc/asterisk/features_featuremap_custom.conf
touch /etc/asterisk/iax_custom_post.conf
touch /etc/asterisk/sip_notify_additional.conf
touch /etc/asterisk/sip_notify_custom.conf

chown -R asterisk.asterisk /etc/asterisk/*

# Algo mas de soporte para cuando se actualiza el freePBX desde su administracion
# Se crea estas carpetas para manejar un error de actualizacion
mkdir -p /var/www/html/_asterisk
mkdir -p /var/www/html/recordings
mkdir -p /var/lib/asterisk/mohmp3/none/
chown asterisk.asterisk /var/www/html/_asterisk 
chown asterisk.asterisk /var/www/html/recordings
chown asterisk.asterisk /var/lib/asterisk/mohmp3/none/

# Fixed bug in FOP when we use DAHDI instead of Zap
#sed -ie "s/Zap/DAHDI/g" /var/lib/asterisk/bin/retrieve_op_conf_from_mysql.pl

# Fix once and for all the issue of recordings/MOH failing because
# of Access Denied errors.
if [ ! -e /var/lib/asterisk/sounds/custom/ ] ; then
    mkdir -p /var/lib/asterisk/sounds/custom/
    chown -R asterisk.asterisk /var/lib/asterisk/sounds/custom/
fi

# Copy any unaccounted files from moh to mohmp3
for i in /var/lib/asterisk/moh/* ; do
    if [ -e $i ] ; then
        BN=`basename "$i"`
        if [ ! -e "/var/lib/asterisk/mohmp3/$BN" ] ; then
            cp $i /var/lib/asterisk/mohmp3/
        fi
    fi
done

# Explicitly set MOHDIR=mohmp3 in amportal.conf
if ! grep -q -s '^MOHDIR' /etc/amportal.conf ; then
    echo 'No MOHDIR directive found in /etc/amportal.conf, setting to mohmp3 ...'
    echo -e "\n\nMOHDIR=mohmp3" >> /etc/amportal.conf
fi
if grep -q -s '^MOHDIR=moh$' /etc/amportal.conf ; then
    echo "Fixing MOHDIR to point to mohmp3 instead of moh in /etc/amportal.conf ..."
    sed -i "s/^MOHDIR=moh$/MOHDIR=mohmp3/" /etc/amportal.conf
fi

# Change moh to mohmp3 on all Asterisk configuration files touched by FreePBX
for i in /etc/asterisk/musiconhold*.conf ; do
    if ! grep -q -s '^directory=/var/lib/asterisk/moh$' $i ; then
        echo "Replacing instances of moh with mohmp3 in $i ..."
        sed -i "s|^directory=/var/lib/asterisk/moh\(/\)\?$|directory=/var/lib/asterisk/mohmp3/|" $i
    fi
done

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/etc/asterisk.elastix/*
/var/www/html/*
/var/log/asterisk/*
%config(noreplace) /var/www/html/panel/op_buttons_additional.cfg
%config(noreplace) /var/www/html/panel/op_buttons_custom.cfg
/var/lib/asterisk/*
%config(noreplace) /var/log/asterisk/cdr-csv/Master.csv
/usr/sbin/fpbx
%defattr(-, root, root)
%config(noreplace) /etc/amportal.conf
/etc/rc.d
/usr/sbin/amportal
/usr/share/freepbx/tmp/*

%changelog
* Wed Nov 16 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-8
- ADDED: In spec file, added patch freepbx-2.8.1_flash_operator_panel_validation.patch
- ADDED: In spec file, added patch freepbx-2.8.1_call_limit_functions.patch
- ADDED: In spec file, added patch freepbx-2.8.1_call_limit_libfreepbx.patch
- ADDED: In spec file, added patch freepbx-2.8.1_backupFiles.patch
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-15

* Tue Nov 01 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-7
- CHANGED: patch freepbx-2.8.1_disabled_freepbx_by_elastix.patch, some changes
  were made to introduce correctly the new theme elastixneo

* Wed Oct 12 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-6
- FIXED: patch freepbx-2.8.0_elastix_files_config.patch had an improperly
  comment

* Mon Oct 10 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-5
- NEW: patch freepbx-2.8.1_htaccess.patch, adds files .htaccess in subfolders
  of folder admin/

* Wed Sep 28 2011 Bruno Macias <bmacias@palosanto.com> 2.8.1-4
- NEW: patch freepbx-2.8.1_elastix_files_config_vmemail.patch, better email  message.
- NEW: patch freepbx-2.8.1_changed_message_faxlicense.patch, patch 15 only apply to 
  freepbx-modules tar (SOURCE1).

* Tue Jul 19 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-3
- CHANGED: patch freepbx-2.8.1_disabled_freepbx_by_elastix.patch, added a
  validation in case the file /var/www/html/modules/sec_advanced_settings/libs/paloSantoChangePassword.class.php does not exist

* Tue Jul 12 2011 Alberto Santos <asantos@palosanto.com> 2.8.1-2
- CHANGED: patch freepbx-2.8.1_disabled_freepbx_by_elastix.patch, the image
  on page elastix_advice.php was not displayed when the theme was not 
  elastixwave.

* Mon Jun 13 2011 Bruno Macias V. <bmacias@palosanto.com> 2.8.1-1
- ADDED: patch freepbx-2.8.0_disabled_freepbx_by_elastix.patch, this patch disabled
  direct access (Non-embedded) to FreePBX. Elastix module "Advanced Security Settings".
- UPDATED: FreePBX version 2.8.1

* Wed May 18 2011 Alberto Santos <asantos@palosanto.com> 2.8.0-3
- CHANGED: patch SOURCE4 was modified

* Thu Mar 31 2011 Eduardo Cueva <ecueva@palosanto.com> 2.8.0-2
- CHANGED:  Bad path with command to create a link to file:
             /etc/asterisk/sip_notify.conf. 

* Sat Dec 22 2010 Eduardo Cueva <ecueva@palosanto.com> 2.8.0-1
- UPDATED:  Update freePBX to 2.8.0.4

* Wed Dec 08 2010 Bruno Macias V. <bmacias@palosanto.com> 2.7.0-10
- FIXED: recordings folder - missing files. Elastix bug #606.
- CHANGED: check for existence of files in moh/ before copying.

* Thu Oct 01 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-9
- Rename moh to mohmp3 to ensure compatibility with Elastix tools (Elastix bug #355).
- Require asterisk >= 1.6.2.13 for part of fix to Elastix bug #355.
- Create symlinks for sip_notify.conf. Attempt to fix Elastix bug #497.
- Fix incorrect comparison in install_amp that prevents some files from being copied.

* Wed Aug 18 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-8
- FIXED: Remove beta from version string - source already is production release.
- ADDED: Add %%prep check to prevent the RPM from overwriting a manually-updated
  FreePBX installation.
- FIXED: Fixed repackaging of freepbx-modules with wrong name.

* Tue Aug 10 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-7beta
- FIXED: better way to handle patched tarballs, should allow restoring unpatched
  tarballs in source RPM.

* Tue Aug 10 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-6beta
- FIXED: remove fallback to credentials from /etc/amportal.conf in case ordinary
  credentials from database failed.

* Thu Jul 29 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-5beta
- FIXED: actually check and create /var/lib/asterisk/sounds/custom/ if it does not
  exist, before trying to change permission on directory.
- FIXED: read MySQL root password from /etc/elastix.conf instead of hardcoding it.

* Mon Jul 26 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.7.0-4beta
- FIXED: force /var/lib/asterisk/sounds/custom/ to be owned by asterisk. Should
  partially fix Elastix bug #403.

* Wed Jun 30 2010 Bruno Macias V. <bmacias@palosanto.com> 2.7.0-3beta
- UPDATED: modules freepbx version 2.7.0 lasted.

* Mon Mar 22 2010 Bruno Macias V. <bmacias@palosanto.com> 2.7.0-2beta
- Cleanup of specfile with more files being tracked as part of the RPM
- Create folder /var/lib/asterisk/mohmp3/none/, is necessary that being defined.

* Fri Mar 19 2010 Bruno Macias V. <bmacias@palosanto.com> 2.7.0-1beta
- Update freepbx 2.7.0
- Update sql, freepbx database  2.7.0
- Patch freepbx-2.5.0_elastix_core_agi_bin_recordingcheck.patch commented, freepbx has deleted the file recordingcheck, it is in test.
- Update manager.conf keyword added for enable originate and other them.
- Commented in this spec line .../admin/modules/core/bin/*, freepbx 2.7.0 not content the bin files.
- Commented in this spec line .../amp_conf/mohmp3/*, freepbx 2.7.0 not content the mohmp3 files.

* Wed Jan 13 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.6.0-4beta
- Remove conflict with previous version of freePBX in order to enable update
  from alpha versions of Elastix 2.

* Fri Jan 08 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.6.0-3beta
- Actually remove sound file that actually belongs to a different package.

* Fri Jan 08 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.6.0-2beta
- Cleanup of specfile with more files being tracked as part of the RPM
- Converted sed in %post into proper patch
- Prereq asterisk to have the asterisk user available
- Convert installation of database to use elastix-firstboot

* Fri Jan 08 2010 Bruno Macias V <bmacias@palosanto.com> 2.6.0-1beta
- Update freepbx to 1.6.0.
- Fixed bug, permission in folder /var/lib/asterisk/sounds/custom for user asterisk.
- Update patch freepbx-2.5.2_elastix_files_config.patch to freepbx-2.6.0_elastix_files_config.patch, section file amportal.conf and section file cdr_mysql.conf

* Tue Oct 20 2009 Bruno Macias V <bmacias@palosanto.com> 2.5.2-2rc
- Fixed bug, forgot change the version in name of files tgz.

* Mon Oct 19 2009 Bruno Macias V <bmacias@palosanto.com> 2.5.2-1rc
- FreePBX Update 2.5.2.
- Update patch freepbx-2.5.1_elastix_files_config.patch to freepbx-2.5.2_elastix_files_config.patch, section file amportal.conf
- Fixed bug [#59] in www.bugs.elastix.org. File recordings/includes/main.conf.php is less.

* Fri Sep 04 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 2.5.1-13rc
- Create empty configuration files that are referenced by installed freePBX files.
  This is required for the asterisk 1.6.x AMI and SIP support to start up correctly.

* Wed Jun 03 2009 Bruno Macias V <bmacias@palosanto.com> 2.5.1-12rc
- Fixed bug when be update freepbx, the script install_amp dont updated all modules because the script use the modules  
  in folder amp_conf/htdocs/admin/modules.

* Wed Feb 18 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 2.5.1-11rc
- Fix incorrect path to fxotune which was changed by dahdi
- Fix hardcoded path to asterisk modules which is incorrect in 64-bit architectures

* Tue Feb 03 2009 Bruno Macias <bmacias@palosanto.com> 2.5.1-10rc
- Fixed bug in FOP when we use DAHDI instead of Zap
 
* Thu Jan 29 2009 Bruno Macias <bmacias@palosanto.com> 2.5.1-9beta
- Changed ZAP to DAHDI (amportal.conf) in patch freepbx-2.5.1_elastix_files_config
- Rename source zapata.conf by chan_dahdi.conf
- Changed zapata-channels by dahdi-channels in patch freepbx-2.5.1_dahdi-channels.patch

* Wed Dec 24 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-8beta
- Not apply source extensions_override_freepbx.conf, this isn't sure.

* Fri Dec 19 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-7beta
- Delete patch 10 and used as source extensions_override_freepbx.conf. This fixed bug cdrs duplicated.

* Fri Nov 28 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-5beta
- Fixed bug of duplicated of CDR register, comments 2 first lines in macro-hangup. 
  Patch10 freepbx-2.5.1_macro_hungup.patch

* Fri Nov 21 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-4beta
- Patch elastix_core_function drop, freepbx implement now the call-limit.

* Thu Nov 13 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-3beta
- Delete old comments in this spec.
- Update source freepbx-database, and rename by freepbx-database-2.5.1.
- Rename source freepbx-additional-modules-2.5.1 by freepbx-modules-2.5.1, because this modules are more updated and this way is better.

* Wed Nov 12 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-2beta
- patch8 is applier, this was changed the version to freepbx-2.5.1_elastix_core_functions.patch.
- update modules freePBX.

* Tue Oct 28 2008 Bruno Macias <bmacias@palosanto.com> 2.5.1-1alpha
- Update to 2.5.1.
- patch8 isn't applier.

* Tue Oct 28 2008 Bruno Macias <bmacias@palosanto.com> 2.5.0-2
- Fixed bug wakeup and wheater, freepbx-2.5.0_weather_wakeup.patch

* Mon Oct 27 2008 Bruno Macias <bmacias@palosanto.com> 2.5.0-1
- Update freePBX 2.5.0
- Replace path /tmp by /usr/share/freepbx/tmp, this is a better practice.
- Replace and update patch freepbx-2.3.1_amportal.patch by freepbx-2.5.0_amportal.patch
- Split patch0 (freepbx-elastix-2.4.0.patch) by:
        patch7: freepbx-2.5.0_elastix_core_agi_bin_recordingcheck.patch
        patch8: freepbx-2.5.0_elastix_core_functions.patch
- Rename and update freepbx-elastix-2.4.0.patch by freepbx-2.5.0_elastix_files_config.patch.
- Rename and update freepbx-2.3.1_amportal.patch by freepbx-2.5.0_elastix_bin_freepbx_engine.patch

* Tue Sep 24 2008 Bruno Macias <bmacias@palosanto.com> 2.4.0-13
- Fixing some problems with symlinks missed (features.conf)
- Creation folder _asterisk  and recordings in /var/www/html, this is for update freePBX

* Mon Aug 04 2008 Edgar Landivar <elandivar@palosanto.com> 2.4.0-12
- Updating to 2.4.0
- Fixing some problems with symlinks missed (iax.conf, sip.conf, extensions.conf, fax-process.pl)

* Thu Jun 26 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1-35
- patch2 comment (future delete), this patch replaced by function add in module call center. Agentlogoff and QueuePause now are functions of call center. 
  This fixed bug of file phpagi-asmanager, update and auto recover.

* Thu Jun 12 2008 Edgar Landivar <elandivar@palosanto.com> 2.3.1-34
- iax.conf, sip.conf and extensions.conf are now symbolic links to the /var/www/html/admin/modules/core/etc/ files.

* Fri Jun 06 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1-33
- Add Source7, this source is un gsm util by new module calendar in elastix. Its path is /var/lib/asterisk/sounds/custom 
- Add patch6 (freepbx-2.3.1_calendar-evet.patch). This patch add in /etc/asterisk/extensions-custom.conf, configuration necessary for call file calendarEvent.gsm of part new module calendar in elastix.

* Wed Apr 09 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1-32
- Add Source6, this content zapata.conf custom by elastix.

* Sat Feb 09 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1-31
- Review patch 4 (freepbx-2.3.1_agent-login.patch) and path 5 (freepbx-2.3.1_conferences.patch ). This patch add in /etc/asterisk/extensions-custom.conf and conferences create the file /etc/asterisk/dbmysql.conf

* Thu Feb 07 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1-30
  - Fixed bug in /var/lib/asterisk/bin/retrieve_op_conf_from_mysql.pl in elastix, replace zapata-auto by zapara-channels, this action make for freepbx-zapata-channels.patch.

* Wed Feb 06 2008 Bruno Macias <bmacias@palosanto.com> 2.3.1.29
  - Add function Agentlogoff and QueuePause, this action make for freepbx-phpagi-asmanager.patch.

* Fri Dec 21 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.3.1.28
  - Fix to bug: Files in the folder /etc/asterisk now they aren't replaced.

* Tue Dec 18 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1.19
  - Including missed musiconhold_additional.conf file

* Tue Dec 18 2007 Bruno Macias <bmacias@palosanto.com> 2.3.1.18
  - Better and fixed permission the file for correct handle fop. This is in post (Start permission handle fop)

* Wed Dec 12 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.3.1-17
  - Permission of archive /var/www/html/panel/op_buttons_additional.cfg which contain the extensions show in the flash operator panel. 

* Fri Dec 7 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-16
  - Unloading this modules by default: res_config_pgsql.so, cdr_pgsql.so and chan_unicall.so

* Fri Nov 30 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-15
  - Minor change in the amportal script to stop asterisk with the command "stop now"  
    instead of "stop gracefully"

* Mon Nov 26 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.3.1-14
  - FIX TO BUG: cant upload wav files, now it's how global var in the pbxadmin module.
  - Add the patch freepbx-2.3.1_amportal.patch

* Mon Nov 19 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-13
  - Some fixes needed in the rc.local file

* Sun Nov 18 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-12
  - Changes to fix the update issue

* Tue Nov 09 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-11
  - Previous changes reverted

* Tue Nov 08 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-10
  - Some minor changes in the header spec

* Tue Nov 06 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-7
  - Added Prereq to require elastix 0.9.0-10

* Mon Nov 01 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-6
  - Minor change in the blacklist/functions.inc.php file. Acording to
    this post http://www.elastix.org/component/option,com_joomlaboard/Itemid,26/func,view/catid,1/id,2731/lang,en/#2731 

* Mon Oct 31 2007 Edgar Landivar <elandivar@palosanto.com> 2.3.1-5
  - freepbx-elastix.patch was modified to change the authentication 
    type to 'database'

* Wed Oct 31 2007 Adonis Figueroa <afigueroa@palosanto.com> 2.3.1-4
  - FIX TO BUG: cant upload wav files.
  - FIX TO BUG: Amportal permissions.

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
