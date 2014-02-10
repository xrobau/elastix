%define modname  subscription-agent
%define softname collectd

Summary: Elastix Subscription Agent  
Name:    %{modname}
Version: 5.0.1
Release: 12
License: GPL
Group:   Applications/System
Source0: %{softname}-%{version}.tgz 
Source1: %{modname}_%{version}-%{release}.tgz
Patch0:  collectd_use_write_http_32bits.patch
Patch1:  collectd_use_write_http_64bits.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: %{_arch} 
Prereq:  curl rrdtool-perl perl-Regexp-Common


%description
Elastix Subscription Agent, based on collectd is a daemon which collects system
performance statistics periodically and provides mechanisms to store the values
in a variety of ways, for example in RRD files. Collectd gathers statistics
about the system it is running on and stores this information. Those statistics
can then be used to find current performance bottlenecks (i.e. performance
analysis) and predict future system load (i.e. capacity planning). Or if you
just want pretty graphs of your private server and are fed up with some
homegrown solution you're at the right place, too.

%prep
%setup   -n %{softname}

if [ %{_arch} == "x86_64" ] ; then
%patch1 -p1
else
%patch0 -p1
fi

%build
%{configure} --enable-write-http --enable-exec
make

%install
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=$RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/etc/rc.d/init.d
mkdir -p $RPM_BUILD_ROOT/var/www/cgi-bin
cp contrib/redhat/init.d-collectd $RPM_BUILD_ROOT/etc/rc.d/init.d/collectd
cp contrib/collection.cgi $RPM_BUILD_ROOT/var/www/cgi-bin
mkdir -p $RPM_BUILD_ROOT/etc/collectd.d
mkdir -p $RPM_BUILD_ROOT/var/lib/collectd
### Clean up docs
find contrib/ -type f -exec %{__chmod} a-x {} \;
rm `find $RPM_BUILD_ROOT -name perllocal.pod`

tar -xvzf %{SOURCE1}
cd %{modname}
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

mkdir -p $RPM_BUILD_ROOT/etc/ssl/certs/
mv setup/etc/ssl/certs/elx_cloud.crt  $RPM_BUILD_ROOT/etc/ssl/certs/

mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/privileged/
mv setup/usr/share/elastix/privileged/collectd $RPM_BUILD_ROOT/usr/share/elastix/privileged/
mv setup/plugins/elastixactivechannels.php     $RPM_BUILD_ROOT%{_libdir}/collectd
chmod +x $RPM_BUILD_ROOT%{_libdir}/collectd/elastixactivechannels.php

pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
mkdir -p    $RPM_BUILD_ROOT/$pathModule
mv setup    $RPM_BUILD_ROOT/$pathModule/
mv menu.xml $RPM_BUILD_ROOT/$pathModule/

%pre
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
mkdir -p $pathModule
touch $pathModule/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > $pathModule/preversion_%{modname}.info
fi

%post
/sbin/chkconfig --add collectd
/sbin/chkconfig collectd on

pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
elastix-menumerge $pathModule/menu.xml
preversion=`cat $pathModule/preversion_%{modname}.info`

if [ $1 -eq 1 ]; then #install
  # The installer database
    elastix-dbprocess "install" "$pathModule/setup/db"
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp    -r $pathModule/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT


%preun
if [ "$1" = 0 ]; then
   /sbin/chkconfig collectd off
   /etc/init.d/collectd stop
   /sbin/chkconfig --del collectd
fi
exit 0


%postun
if [ "$1" -ge 1 ]; then
    /etc/init.d/collectd restart
fi
exit 0


%files
%defattr(-, root, root)
/etc/collectd.conf
/etc/ssl/certs/elx_cloud.crt
%{perl_sitelib}/Collectd.pm
%{perl_sitelib}/Collectd/Plugins/*
%{perl_sitelib}/Collectd/Unixsock.pm
%{perl_sitearch}/auto/Collectd/.packlist
/usr/bin/collectd-nagios
/usr/bin/collectdctl
/usr/include/collectd/*
%{_libdir}/collectd/*
%{_libdir}/libcollectdclient.a
%{_libdir}/libcollectdclient.la
%{_libdir}/libcollectdclient.so
%{_libdir}/libcollectdclient.so.0
%{_libdir}/libcollectdclient.so.0.0.0
%{_libdir}/pkgconfig/libcollectdclient.pc
/usr/sbin/collectd
/usr/sbin/collectdmon
/usr/share/collectd/postgresql_default.conf
/usr/share/collectd/types.db
/usr/share/man/man1/collectd-nagios.1.gz
/usr/share/man/man1/collectd.1.gz
/usr/share/man/man1/collectdctl.1.gz
/usr/share/man/man1/collectdmon.1.gz
/usr/share/man/man5/collectd-email.5.gz
/usr/share/man/man5/collectd-exec.5.gz
/usr/share/man/man5/collectd-java.5.gz
/usr/share/man/man5/collectd-perl.5.gz
/usr/share/man/man5/collectd-python.5.gz
/usr/share/man/man5/collectd-snmp.5.gz
/usr/share/man/man5/collectd-threshold.5.gz
/usr/share/man/man5/collectd-unixsock.5.gz
/usr/share/man/man5/collectd.conf.5.gz
/usr/share/man/man5/types.db.5.gz
/usr/share/man/man3/Collectd::Unixsock.3pm.gz
%attr(0755,root,root) /etc/rc.d/init.d/collectd
%attr(0755,root,root) /var/www/cgi-bin/collection.cgi

%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%defattr(755, root, root)
/usr/share/elastix/privileged/*

%changelog
* Mon Sep 10 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 5.0.1-12
- ADDED: Add standard installer.php and db directories to SVN.
- FIXED: Fix misalignment on GUI caused by CSS namespace colission with
  AeroWindow plugin. 
- FIXED: Assign icon Smarty variable for integration with latest theme.
- FIXED: Fix check for installation of collectd when Elastix is already
  registered.

* Fri Mar 23 2012 Bruno Macias <bmacias@palosanto.com> 5.0.1-11
- FIXED: Interval time 10 to 100 and status disk report was 
  re-activated.

* Fri Mar 08 2012 Rocio Mera <rmera@palosanto.com> 5.0.1-10
- CHANCHED: In specs file change the sorces version to 5.0.1-10
 
* Thu Mar 08 2012 Bruno Macias <bmacias@palosanto.com> 5.0.1-9
- UPDATED: update module content.

* Thu Mar 08 2012 Bruno Macias <bmacias@palosanto.com> 5.0.1-8
- CHANGED: now the collecd.conf file is created by build process
  and patch files.

* Wed Mar 07 2012 Bruno Macias <bmacias@palosanto.com> 5.0.1-7
- ADDED: New plugin elastixactivechannels.php was add, and path
  now work it with arch.

* Fri Feb 10 2012 Luis Abarca <labarca@palosanto.com> 5.0.1-6
- ADDED: Some modifications in the modules folder

* Thu Feb 09 2012 Bruno Macias <bmacias@palosanto.com> 5.0.1-5
- CHANGED: Restructure format spec file and change rpm name.

* Wed Feb 01 2012 Luis Abarca <labarca@palosanto.com> 5.0.1-4
- UPDATED: Use a patch called "collectd_use_write_http32.patch" 
  for arch 32bit.

* Mon Jan 23 2012 Luis Abarca <labarca@palosanto.com> 5.0.1-3
- CHANGED: Added collectd_chng_cert.patch

* Sat Jan 07 2012 Eduardo Cueva <ecueva@palosanto.com> 5.0.1-2
- CHANGED: In Spec file remove prereq curl-devel and add 
  rrdtool-perl perl-Regexp-Common as new prereq

* Fri Jan 06 2012 Eduardo Cueva <ecueva@palosanto.com> 5.0.1-1
- Initial version
