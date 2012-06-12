%define modname ZoneMinder

Summary: Elastix ZoneMinder
Name:    elastix-%{modname}
Version: 1.25.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
Prereq: elastix >= 2.2.0-7
BuildRequires: mysql-devel
BuildRequires: libjpeg-devel >= 6b-37

Patch0: ZoneMinder-1.25.0_deleteChown.patch
Patch1: ZoneMinder-1.25.0_dbProcessHierarchy.patch
Patch2: ZoneMinder-1.25.0_elastixAuthentication.patch

%description
ZoneMinder

%prep
%setup -n %{modname}

%patch0 -p1
%patch1 -p1
%patch2 -p1

%build
if [ %{_arch} == "x86_64" ] ; then
    LIBDIR="--with-libarch=lib64"
else
    LIBDIR="--with-libarch=lib"
fi
%configure $LIBDIR --with-webdir=/var/www/html/zminder --with-cgidir=/var/www/cgi-bin --with-webuser=asterisk --with-webgroup=asterisk --enable-mmap=no ZM_DB_HOST=localhost ZM_DB_NAME=zm ZM_DB_USER=zoneminder ZM_DB_PASS=zoneminder.elastix.2o11 ZM_SSL_LIB=openssl 
make DESTDIR=%{buildroot} ASTVARRUNDIR=%{_var}/run/asterisk

%install
rm -rf %{buildroot}
mkdir -p    $RPM_BUILD_ROOT/etc/init.d/
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}/
mkdir -p    $RPM_BUILD_ROOT/var/run/zm/
mkdir -p    $RPM_BUILD_ROOT/var/log/zm/
mkdir -p    $RPM_BUILD_ROOT/tmp/zm/
#mv ./* $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}/
mv dbProcess/ $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}/
mv db/zm_create.sql $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}/dbProcess/install/zm/2_zmCreate.sql
make install DESTDIR=%{buildroot} ASTVARRUNDIR=%{_var}/run/zm
chmod +x scripts/zm
mv scripts/zm $RPM_BUILD_ROOT/etc/init.d/
rm -rf $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.8/ZoneMinder/Memory/Mapped.pm 
rm -rf scripts/zmx10.pl.in
rm -rf scripts/zmx10.pl
rm -rf $RPM_BUILD_ROOT/usr/bin/zmx10.pl

%pre
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}/
touch /usr/share/elastix/module_installer/%{name}-%{version}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}/preversion_%{modname}.info
fi

%post
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}"

pathSQLiteDB="/var/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{modname}.info`

if [ $1 -eq 1 ]; then #install
  # install database
    elastix-dbprocess "install" "$pathModule/dbProcess"
elif [ $1 -eq 2 ]; then #update
   # update database
      elastix-dbprocess "update"  "$pathModule/dbProcess" "$preversion"
fi

chmod u+w /var/run/zm
chmod u+w /var/log/zm
chmod u+w /tmp/zm
chkconfig --add zm
chkconfig --level 2345 zm on

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
/etc/init.d/zm
/usr/bin/zma
/usr/bin/zmaudit.pl
/usr/bin/zmc
/usr/bin/zmcontrol.pl
/usr/bin/zmdc.pl
/usr/bin/zmf
/usr/bin/zmfilter.pl
/usr/bin/zmfix
/usr/bin/zmpkg.pl
/usr/bin/zmstreamer
/usr/bin/zmtrack.pl
/usr/bin/zmtrigger.pl
/usr/bin/zmu
/usr/bin/zmupdate.pl
/usr/bin/zmvideo.pl
/usr/bin/zmwatch.pl
%{perl_archlib}/perllocal.pod
/usr/lib/perl5/site_perl/5.8.8/ZoneMinder.pm
/usr/lib/perl5/site_perl/5.8.8/ZoneMinder/*
%{perl_sitearch}/auto/ZoneMinder/.packlist
/usr/share/man/man3/ZoneMinder.3pm.gz
/usr/share/man/man3/ZoneMinder::Base.3pm.gz
/usr/share/man/man3/ZoneMinder::Config.3pm.gz
/usr/share/man/man3/ZoneMinder::ConfigAdmin.3pm.gz
/usr/share/man/man3/ZoneMinder::ConfigData.3pm.gz
/usr/share/man/man3/ZoneMinder::Control.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::AxisV2.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::Ncs370.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::PanasonicIP.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::PelcoD.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::Visca.3pm.gz
/usr/share/man/man3/ZoneMinder::Control::mjpgStreamer.3pm.gz
/usr/share/man/man3/ZoneMinder::Database.3pm.gz
/usr/share/man/man3/ZoneMinder::General.3pm.gz
/usr/share/man/man3/ZoneMinder::Logger.3pm.gz
/usr/share/man/man3/ZoneMinder::Memory.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::File.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::Handle.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::Inet.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::Serial.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::Spawning.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Channel::Unix.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Connection.3pm.gz
/usr/share/man/man3/ZoneMinder::Trigger::Connection::Example.3pm.gz
/usr/src/debug/ZoneMinder/src/*
/var/www/cgi-bin/nph-zms
/var/www/cgi-bin/zms
%defattr(-, asterisk, asterisk)
/etc/zm.conf
/var/www/html/zminder
/var/www/html/zminder/*
/var/run/zm
/tmp/zm
/var/log/zm
/usr/share/elastix/module_installer/elastix-ZoneMinder-1.25.0/*

%changelog
* Fri Sep 30 2011 Alberto Santos <asantos@palosanto.com> 1.25.0-1
- Initial Version
- The files zmx10.pl and Mapped.pm were removed, due to errors of missing dependency
