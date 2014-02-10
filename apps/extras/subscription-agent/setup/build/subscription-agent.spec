%define modname  subscription-agent
%define softname collectd

Summary: Elastix Subscription Agent  
Name:    %{modname}
Version: 5.3.0
Release: 5
License: GPL
Group:   Applications/System
Source0: %{softname}_%{version}.tar.gz
Source1: %{modname}_%{version}-%{release}.tar.gz
Patch0:  collectd_config_file_i386.patch
Patch1:  collectd_config_file_x86_64.patch
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
%setup -n %{softname}

if [ %{_arch} == "i386" ] ; then
%patch0 -p1
else
%patch1 -p1
fi

%build
%{configure} --enable-write-http --enable-exec --without-python
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
mkdir -p $RPM_BUILD_ROOT%{_libdir}/collectd/plugins/
mv setup/plugins/elastixcalls.pl     $RPM_BUILD_ROOT%{_libdir}/collectd/plugins/
chmod +x $RPM_BUILD_ROOT%{_libdir}/collectd/plugins/elastixcalls.pl

pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
mkdir -p    $RPM_BUILD_ROOT/$pathModule
mv setup    $RPM_BUILD_ROOT/$pathModule/
mv menu.xml $RPM_BUILD_ROOT/$pathModule/

%pre
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
mkdir -p $pathModule
touch $pathModule/preversion_%{modname}.info
if [ $1 -eq 2 ]; then #UPDATE
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > $pathModule/preversion_%{modname}.info
    
    # IF service is runnig stop this
    /sbin/service collectd status &>/dev/null
    is_collectd_run=$?
    if [ $is_collectd_run -eq 0  ]; then
        echo "Stopping collectd"    
	/sbin/service collectd stop
    fi    

    # If exist /etc/collectd.conf made a backup of this
    if [ -f /etc/collectd.conf ]; then
    	mv -f /etc/collectd.conf /etc/collectd.conf.bkp
    fi    
fi

%post
/sbin/chkconfig --add collectd
/sbin/chkconfig --level 235 collectd on

pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
elastix-menumerge $pathModule/menu.xml
preversion=`cat $pathModule/preversion_%{modname}.info`

# The installer and update script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp    -r $pathModule/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

if [ $1 -eq 1 ]; then #install
  # The installer database
    elastix-dbprocess "install" "$pathModule/setup/db"
    php /tmp/new_module/%{modname}/setup/installer.php
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
    # run script that parse file /etc/collectd.conf.bkp to see if these 
    # file have setting the elastix serverkey. If it is set it will be 
    # writen in the file /etc/collectd.conf
    chmod 744 $pathModule/setup/update.php
    php $pathModule/setup/update.php
fi
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT


%preun
if [ $1 -eq 0 ]; then
   /sbin/chkconfig collectd off
   /etc/init.d/collectd stop
   /sbin/chkconfig --del collectd
  echo "Delete ElxCloud menu"
  elastix-menuremove elxcloud
fi
exit 0

%postun
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
/usr/bin/collectd-tg
/usr/include/collectd/*
%{_libdir}/collectd/*
%{_libdir}/collectd/plugins/*
%{_libdir}/libcollectdclient.a
%{_libdir}/libcollectdclient.la
%{_libdir}/libcollectdclient.so
%{_libdir}/libcollectdclient.so.1
%{_libdir}/libcollectdclient.so.1.0.0
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
/usr/share/man/man1/collectd-tg.1.gz
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
* Fri Aug 09 2013 Rocio Mera <remera@palosanto.com> 5.3.0-5
- CHANGED: In spec file was changed version to 5.3.0-5
- CHANGED: Was made in the patch file to eliminate the 
  restriction in plugin disk. This was made becase can exist
  many kind of disk in the system
- CHANGED: Was made change in plugin elastixcalls.pl. This 
  changes fix a problem that when just one call or channel
  was active the name of plugin was sending a wroon plugin 
  name
* Mon Jul 22 2013 Rocio Mera <rmera@palosanto.com>
- CHANGED: In spec file was changed version to 5.3.0-4
- CHANGED: Was made change in function that check collectd
  service status in monule setup_monitor

* Wed Jun 26 2013 Rocio Mera <rmera@palosanto.com>
- CHANGED: In spec file was changed version to 5.3.0-3
- CHANGED: Patch was changed in order to plugin disk
  take samples from disk with dm-[digit] name

* Wed Jun 05 2013 Rocio Mera <rmera@palosanto.com>
- CHANGED: In spec file was changed version to 5.3.0-2
- CHANGED: Patch was changed in order to plugin write_http 
  don't verfify the certify at the moment to send samples to 
  cloud.elastix.org

* Thu May 16 2013 Rocio Mera <rmera@palosanto.com>
- UPDATED: Was updated collectd version to the latest available version
  that is 5.3.0
- CHANGED: In spec file was changed version to 5.3.0-1

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
