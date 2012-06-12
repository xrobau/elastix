%define __os_install_post %{nil}
%define modname fop2

Summary: FOP2
Vendor: asternic.biz
Name: %{modname}
Version: 2
Release: 2.26
License: GPL
Group: Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: %{_arch}
Packager: Nicolas Gudino <nicolas.gudino@asternic.biz>
URL: http://www.fop2.com
Prereq: elastix-pbx >= 2.0.4-24
Prereq: elastix >= 2.0.4-24

%description
This package contains the Flash Operator Panel 2 for Asterisk based PBX

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p                     $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mkdir -p                     $RPM_BUILD_ROOT/var/www/html/admin/modules
mkdir -p                     $RPM_BUILD_ROOT/etc/asterisk
mkdir -p                     $RPM_BUILD_ROOT/etc/rc.d/init.d
mkdir -p                     $RPM_BUILD_ROOT/etc/sysconfig
mkdir -p                     $RPM_BUILD_ROOT/usr/local
mkdir -p                     $RPM_BUILD_ROOT/usr/share/doc
mkdir -p		     $RPM_BUILD_ROOT/var/www/html/modules

mv modules/fop2admin         $RPM_BUILD_ROOT/var/www/html/admin/modules
mv modules/fop2              $RPM_BUILD_ROOT/var/www/html
mv setup/indexfop2.php       $RPM_BUILD_ROOT/var/www/html

mv modules/fop2buttons       $RPM_BUILD_ROOT/var/www/html/modules
mv modules/fop2groups        $RPM_BUILD_ROOT/var/www/html/modules
mv modules/fop2users         $RPM_BUILD_ROOT/var/www/html/modules
mv modules/fop2templates     $RPM_BUILD_ROOT/var/www/html/modules
mv modules/fop2permissions   $RPM_BUILD_ROOT/var/www/html/modules

mv setup/etc/asterisk/*      $RPM_BUILD_ROOT/etc/asterisk
mv setup/etc/rc.d/init.d/*   $RPM_BUILD_ROOT/etc/rc.d/init.d
mv setup/etc/sysconfig/*     $RPM_BUILD_ROOT/etc/sysconfig

mv setup/usr/local/fop2      $RPM_BUILD_ROOT/usr/local
mv setup/usr/share/doc/fop2  $RPM_BUILD_ROOT/usr/share/doc

if [ %{_arch} == "x86_64" ] ; then
        mv setup/fop2_server/64/fop2_server $RPM_BUILD_ROOT/usr/local/fop2
	rm -f setup/fop2_server/32/fop2_server
else
        mv setup/fop2_server/32/fop2_server $RPM_BUILD_ROOT/usr/local/fop2
	rm -f setup/fop2_server/64/fop2_server
fi

mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menufop2.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menufop.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"

# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menufop2.xml

preversion=`cat $pathModule/preversion_%{modname}.info`

if [ $1 -eq 1 ]; then #install
    sed 's/\;listen_port=4445/listen_port=4446/g' /var/www/html/panel/op_server.cfg > /tmp/op_server.cfg && mv -f /tmp/op_server.cfg /var/www/html/panel/op_server.cfg
    sed -i 's/^\/usr\/sbin\/amportal start_fop$/# \/usr\/sbin\/amportal start_fop/g' /etc/rc.local
    killall op_server.pl
    chkconfig --add fop2
    service fop2 start
    /etc/asterisk/fop2/generate_override_contexts.pl -w
  # Removing fop menu
    echo "Delete fop menu"
    elastix-menuremove "fop"
  # The installer database
    elastix-dbprocess "install" "$pathModule/setup/db"
  # installing fop2 on freePBX
    php -q /var/www/html/admin/modules/fop2admin/rpminst.php install
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
    # updating fop2 on freePBX
    php -q /var/www/html/admin/modules/fop2admin/rpminst.php install
fi

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  sed 's/listen_port=4446/\;listen_port=4445/g' /var/www/html/panel/op_server.cfg > /tmp/op_server.cfg &&  mv -f /tmp/op_server.cfg /var/www/html/panel/op_server.cfg
  sed -i 's/^# \/usr\/sbin\/amportal start_fop$/\/usr\/sbin\/amportal start_fop/g' /etc/rc.local
  service fop2 stop
  chkconfig --del fop2
  grep -v extensions_override_fop2 /etc/asterisk/extensions_override_freepbx.conf > /tmp/ext_override.temp && mv -f /tmp/ext_override.temp /etc/asterisk/extensions_override_freepbx.conf
# removing fop2 over freePBX
  echo "Removing FOP2 from freePBX"
  php -q /var/www/html/admin/modules/fop2admin/rpminst.php uninstall

  echo "Delete FOP2 menus"
  elastix-menuremove "%{modname}"

  echo "Restoring FOP"
  elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menufop.xml

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-,asterisk,asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/var/www/html/fop2
/var/www/html/admin/modules/fop2admin
/var/www/html/modules/fop2buttons
/var/www/html/modules/fop2groups
/var/www/html/modules/fop2users
/var/www/html/modules/fop2templates
/var/www/html/modules/fop2permissions

%defattr(644,asterisk,asterisk)
/var/www/html/indexfop2.php
/usr/local/fop2/FOP2Callbacks.pm 
/etc/asterisk/fop2/buttons.cfg.sample
/etc/asterisk/fop2/autobuttons.cfg
/etc/asterisk/fop2/generate_override_contexts.pl
/etc/asterisk/fop2/claveami.sh

%attr(751, asterisk, asterisk) /etc/asterisk/fop2/autoconfig-buttons-freepbx.sh
%attr(751, asterisk, asterisk) /etc/asterisk/fop2/autoconfig-users-freepbx.sh
%attr(751, asterisk, asterisk) /etc/asterisk/fop2/generate_override_contexts.pl
%attr(751, asterisk, asterisk) /etc/asterisk/fop2/claveami.sh
%attr(751, asterisk, asterisk) /usr/local/fop2/fop2_server
%attr(751, asterisk, asterisk) /usr/local/fop2/recording_fop2.pl
%attr(751, asterisk, asterisk) /usr/local/fop2/tovoicemail.pl

%defattr(644,root,root)
/usr/share/doc/fop2/README
/usr/share/doc/fop2/LICENSE

%defattr(644,asterisk,asterisk)
%config /etc/asterisk/fop2/fop2.cfg
%config /etc/sysconfig/fop2

%defattr(-,root,root)
/etc/rc.d/init.d/fop2
/etc/sysconfig/fop2

%changelog
* Tue Nov 08 2011 Alberto Santos <asantos@palosanto.com> 2-2.26
- Fixed javascript fop2.js

* Tue Oct 31 2011 Nicolas Gudino <nicolas.gudino@asternic.biz> 2-2.25
- Upgrade fop2admin to 1.2.9 
- Updated fop2 to latest 2.4

* Wed May 31 2011 Nicolas Gudino <nicolas.gudino@asternic.biz> 2-2.24
- Added new modules fop2groups, fop2permissions.
- Updated fop2 to latest
- Fixed auto login on index.php

* Wed May 18 2011 Alberto Santos <asantos@palosanto.com> 2-2.23
- Added new modules fop2buttons, fop2groups and fop2users.

* Fri Apr 15 2011 Bruno Macias bmacias@palosanto.com> 2-2.22
- Deleted database fo2, now is used module address elastix database.

* Tue Apr 12 2011 Eduardo Cueva <ecueva@palosanto.com> 2-2.21
- Initial Version
