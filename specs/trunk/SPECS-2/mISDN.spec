# Overview of build options:
#
# --define "kvers kernelver" - Build for kernel <version> instead of the
# current one.
#
# Examples:
#
# To rebuild whole package for the running kernel:
# rpmbuild -ba THIS.SPEC --target "$(uname -m)" 
#
# To rebuild the zaptel kernel modules against a certain installed kernel
# like 2.6.18-1.2798.fc6:
# rpmbuild -ba THIS.SPEC --target "$(uname -m)" \
#	--define "kvers 2.6.18-1.2798.fc6"
#
# if you have problems try this:
# rpmbuild -ba THIS.SPEC --target "$(uname -m)" \
#	--define "kvers 2.6.18-1.2798.fc6"
#
# End of build options.

%{!?kvers:	%{expand: %%define kvers %(uname -r)}}

%if %(echo %{kvers} | grep -c xen)
        %{expand:%%define kxensufix -xen}
%endif

%define is_fedora %(test -f /etc/fedora-release && echo 1 || echo 0)
%define is_rhel %(test -h /etc/redhat-release && echo 0 || echo 1)
%define is_rhel5 %(test -f /etc/redhat-release && cat /etc/redhat-release | egrep -q 'release 5' && echo 1 || echo 0)

%define prefix /usr
%define	_libmoddir /lib/modules
%define	version 1.1.9.1
%define tarballversion 1_1_9.1
%define release 0
%define kmodrelease 1

Summary: The mISDN applications and kernel modules
Name: mISDN
Version: %{version}
Release: %{release}%{?dist}
License: GPL
Group: Applications/System
URL: http://www.misdn.org
Source0: http://www.misdn.org/downloads/releases/%{name}-%{tarballversion}.tar.gz
Source1: mISDN.rules
Source3: mISDN.init
Patch1: misdn-openvox-elastix-2.patch
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Prereq: mISDN-modules, mISDN-modules-xen
BuildRequires: kernel-devel = %{kvers}
Requires: kernel = %{kvers}

%description
The mISDN software package

%package devel
Summary: Development package for %{name}
Group: Development/Libraries
Requires: mISDN = %{version}

%description devel
Use this package for building/developing applications against %{name}.

%package modules%{?kxensufix}
Summary: mISDN kernel modules
Group: System Environment/Kernel
Release: %{kmodrelease}.%(echo %{kvers} | tr - _)%{?dist2}
BuildRequires: kernel-devel = %{kvers}
Requires: kernel = %{kvers}
Requires: mISDN >= %{version}
ExclusiveArch: i586 i686 x86_64 ppc ppc64

%description modules%{?kxensufix}
The mISDN kernel modules. This package is built for kernel %{kvers} (%{_target_cpu}).

%prep
%setup -q -n %{name}-%{tarballversion}
%patch1 -p1

%build
# prevent depmod from being run and disable unsupported scripts
perl -pi -e's,\$\(DEPMOD\),\# \$\(DEPMOD\),g' Makefile
perl -pi -e's,\$\(UPDATE\_MODULES\),\# \$\(UPDATE\_MODULES\),g' Makefile
perl -pi -e's,\$\(MODULES\_UPDATE\),\# \$\(MODULES\_UPDATE\),g' Makefile

# disable hfcmulti on PPC since it won't build
%ifarch ppc
echo "Disabling hfcmulti on PPC..."
perl -pi -e's,^CONFIGS\+\=CONFIG\_MISDN\_HFCMULTI\=m,\#CONFIGS\+\=CONFIG\_MISDN\_HFCMULTI\=m,g' Makefile
%endif

%{__make} clean KVERS="%{kvers}"
%{__make} MODS="/lib/modules/%{kvers}" MISDNDIR=%{_builddir}/%{name}-%{tarballversion}

# fix owner and modes with wich /dev/mISDN is created by the startup script
perl -pi -e's,^DEVNODE_user=.*,DEVNODE_user="asterisk",g' config/mISDN
perl -pi -e's,^DEVNODE_group=.*,DEVNODE_group="asterisk",g' config/mISDN
perl -pi -e's,^DEVNODE_mode=.*,DEVNODE_mode="0644",g' config/mISDN
perl -pi -e'next unless m{<devnode .*/devnode>} ; s,root,asterisk,g ;s,644,660,g' config/mISDN

%install
# first clear out any previous installs to prevent b0rkage
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

echo "Now in the install section..."
%{makeinstall} INSTALL_PREFIX=%{buildroot} MODS="/lib/modules/%{kvers}" MISDNDIR=%{_builddir}/%{name}-%{tarballversion} 

# remove unwanted dir
%{__rm} -rf %{buildroot}/etc/modules.d

# remove static libs
%{__rm} -f %{buildroot}/%{_libdir}/*.a

# add udev rules
%{__install} -D -m 644 %{SOURCE1} %{buildroot}/etc/udev/rules.d/mISDN.rules

# Install a script to load the modules at boot time
%{__install} -D -m 755 %{SOURCE3} %{buildroot}/etc/init.d/mISDN


%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%pre
# make sure that the the asterisk user/group exists.
# Otherwise udev will make root own the mISDN devices while 
# asterisk should own them

if [ -e /usr/sbin/useradd ] ; then
/usr/sbin/useradd -r -c "Asterisk" \
	-s /sbin/nologin \
	-d %{_localstatedir}/lib/asterisk \
	asterisk 2> /dev/null || :
fi

%preun
if [ $1 = 0 ]; then
	/sbin/service %{name} stop > /dev/null 2>&1
	/sbin/chkconfig --del %{name}
fi || :


%post
# activate the mISDN startup script
/sbin/chkconfig --add %{name}
# turn the mISDN startup script off
/sbin/chkconfig --level 123456 %{name} off


%post modules%{?kxensufix}
# Este modulo estaba causando problemas con tarjetas TDM400. Por ahora lo
# pongo en blacklist. Si el usuario lo necesita lo puede quitar y cargar 
# manualmente
if ! /bin/grep 'netjetpci' /etc/modprobe.d/blacklist >/dev/null 2>&1; then
 echo 'blacklist netjetpci' >> /etc/modprobe.d/blacklist
fi

# make sure the kernel knows about the new mISDN modules
/sbin/depmod -aeF /boot/System.map-%{kvers} %{kvers} > /dev/null || :


%postun


%postun  modules%{?kxensufix}
# make sure the kernel knows the mISDN modules are gone
/sbin/depmod -aF /boot/System.map-%{kvers} %{kvers} &> /dev/null || :


%files
%defattr(-,root,root)
%doc README.misdn-init config/README.mISDN
%attr(0755,root,root)		%{_sbindir}/misdn-init
%attr(0755,root,root)		%{_sbindir}/mISDN
%attr(0755,root,root)		%{_sysconfdir}/init.d/mISDN
%attr(0644,root,root)		%{_sysconfdir}/modprobe.d/mISDN
%attr(0644,root,root)		%{_sysconfdir}/udev/rules.d/mISDN.rules
%attr(0644,root,root)		/usr/lib/%{name}/mISDN.conf*

%files devel
%defattr(-,root,root)
%attr(0644,root,root)		%{_includedir}/linux/isdn_compat.h
%attr(0644,root,root)		%{_includedir}/linux/mISDNif.h
%attr(0644,root,root)		%{_includedir}/linux/mISDNdebugtool.h

%files modules%{?kxensufix}
%defattr(-,root,root)
%attr(0744,root,root)		%{_libmoddir}/%{kvers}/extra/*

%changelog
* Thu Apr 30 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.1.9.1-0
- Update to 1.1.9.1, rebuild for newer dahdi
- Run useradd for asterisk only if /usr/sbin/useradd exists.

* Thu Jan 08 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.1.8-3
- Fix spec macros to properly select kernel version

* Mon Nov 10 2008 PaloSanto Solutions <info@elastix.org> 1.1.8-2
- Update the kernel to 2.6.18-92.1.17.el5.

* Wed Oct 22 2008 PaloSanto Solutions <info@elastix.org> - 1.1.8-1
- Updating to the 1.1.8 version
- Replace patch misdn-openvox-elastix-1.patch by misdn-openvox-elastix-2.patch.

* Mon May 19 2008 PaloSanto Solutions <info@elastix.org> - 1.1.7.2-1
- Updating to the 1.1.7.2 version

* Wed Nov 28 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-22
- Added support for XEN kernel

* Wed Nov 28 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-21
- Module netjetpci blacklisted

* Wed Nov 28 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-20
- Added support for OpenVox B200P and B800P products

* Fri Nov 16 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-19
- Prereq: mISDN-modules

* Wed Oct 31 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-18
- Added patch1, this patch adds support for some openvox cards

* Thu Oct 16 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-17
- Change of source mISDN release of 1.1.5 to 1.1.6

* Thu Sep 6 2007 PaloSanto Solutions <info@elastix.org> - 1.1.5-15
- Just recompiling the package in our servers to include it in the Elastix distro

* Thu Aug 9 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.5-14
- remove callweaver stuff

* Mon Jul 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.5-13
- activate the mISDN init script but turn it off in all runlevels and
-  stop the service and remove init script when uninstalling (Patrick Zwahlen)

* Wed Jul 18 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.5-12
- update to release 1.1.5
- add mISDNdebugtool.h file
- remove any previous buildroot dirs to prevent b0rkage (Luigi Iotti)

* Sat Jul 14 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.4-11
- various enhancements by Luigi Iotti:
- Removed '[[ "{kvers}" = "custom" ]]' test from prep stage: kvers is defined
-  on the cmdline (hence it's value is wanted, whatever it is) or it's
-  equal to uname -r output
- Removed default value assignment to ksrc since it was not used any more;
-  if ksrc is passed explicitly, use it on make command line
- Removed references to kernel since it's a duplicate of kvers
- new improved mISDN.init

* Mon Jun 25 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.4-10
- update to release 1.1.4

* Sat Jun 16 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.2-9
- move back to the 1_1_2 release until 1_1_4 is released and proves stable

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.3-8
- hardcode /usr/lib for the config files (thanks Patrick Zwahlen!)

* Sun May 13 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.3-7
- update to 1.1.3
- add several fixes from Luigi Iotti (thanks!) re SMP (Build)Requires, udev
   rules and (in the mean time) updated mISDN.init

* Tue Apr 24 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.2-6
- update to 1.1.2
- rename version in the rpm filename to 1.1.2 instead of 1_1_2

* Fri Mar 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.1-5
- update to version 1.1.1

* Tue Mar 6 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.0-4
- fix name-version-release of the modules
- clean up specfile

* Fri Feb 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.0-3
- add README from the new mISDN config utility

* Mon Feb 19 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.0-2
- update to 1.1.0
- Swyx patch is merged upstream thus removed
- 2.6.18 patch is removed
- tweak user/group check for asterisk or openpbx

* Mon Feb 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.0.4-3
- add support for Swyx 4-port ISDN cards (thanks Philippe Hybrechts)

* Wed Nov 29 2006 Laimbock Consulting <asterisk@laimbock.com> - 1.0.4-2
- add option --with openpbx to build for openpbx which changes the 
-  ownership of the mISDN kernel devices to openpbx:openpbx
- add devel package
- add checks for the existance of the asterisk:asterisk or openpbx:openpbx
-  user:group and make them if not present so the mISDN kernel modules
-  will have the proper ownership assigned by udev

* Wed Nov 29 2006 Laimbock Consulting <asterisk@laimbock.com> - 1.0.4-1
- update to release 1.0.4
- split out mISDNuser into a separate package

* Wed Nov 15 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20061115-1
- update to cvs from 20061115

* Tue Nov 7 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20061107-2
- disable hfcmulti on PPC
- use dwmw2's udev rules

* Tue Nov 7 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20061107-1
- update to cvs from 20061107
- force _smp_mflags to -j1

* Tue Oct 3 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20061031-1
- update to cvs from 20061031
- fix MISDNDIR in mISDNuser Makefile
- add -fPIC to mISDNuser Makefile
- fix hardcoded LIBDIR=/usr/lib in Makefile

* Wed Sep 13 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060913-1
- update to cvs from 20060913

* Mon Sep 11 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060911-1
- update to cvs from 20060911

* Thu Aug 24 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060824-1
- add code from zaptel.spec so mISDN can be built for other kernels too
- fix hardcoded /usr/lib path in mISDNuser/suppserv
- don't package the static libs

* Wed Aug 16 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060815-4
- set MISDNDIR to %%{builddir}/%%{name} so it builds on CentOS too. For 
-  whatever reason MISDNDIR on CentOS can't be a relative path like ../../mISDN

* Wed Aug 16 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060815-3
- add udev rules so mISDN kernel modules get their device via udev

* Tue Aug 15 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060815-2
- combine mISDN and mISDNuser in one RPM

* Tue Aug 15 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060815-1
- initial specfile

