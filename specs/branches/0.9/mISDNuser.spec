# force _smp_mflags to -j1
%define _smp_mflags -j1

%define prefix /usr
%define	_libmoddir /lib/modules
%define version 1.1.6
%define tarballversion 1_1_6
%define release 11

Summary: mISDNuser libraries
Name: mISDNuser
Version: %{version}
Release: %{release}%{?dist}
License: GPL
Group: System Environment/Libraries
URL: http://www.misdn.org
Source0: http://www.misdn.org/downloads/releases/%{name}-%{tarballversion}.tar.gz
Patch0: mISDNuser-1_1_0-soname.patch
Packager: Laimbock Consulting <asterisk@laimbock.com>
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires: mISDN-devel = %{version}
BuildRequires: flex
Requires: mISDN >= %{version}

%description
The mISDNuser libraries

%package devel
Summary:	Development files Modular ISDN stack
Group:		System Environment/Libraries
Requires:	mISDNuser = %{version}

%description devel
mISDN (modular ISDN) is intended to be the new ISDN stack for the
Linux 2.6 kernel, from the maintainer of the existing isdn4linux
code. This package contains the development files for userspace
libraries required to interface to mISDN, needed for compiling
applications which use mISDN directly such as OpenPBX or Asterisk.

%prep
%setup -q -n %{name}-%{tarballversion}

%patch0 -p1

%build
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

# fix location of mISDN dir
perl -pi -e's,/usr/src/mqueue/mISDN,%{_builddir}/mISDN-%{version},' Makefile

# add $RPM_OPT_FLAGS
perl -pi -e's,^CFLAGS\+\= \-D CLOSE\_REPORT\=1,CFLAGS\+\= \-D CLOSE\_REPORT\=1 \$\(RPM\_OPT\_FLAGS\),g' Makefile

# fix hardcoded LIBDIR=/usr/lib in Makefile
perl -pi -e's,^LIBDIR\=\/usr\/lib,LIBDIR\=\$\{_libdir\},g' Makefile
perl -pi -e's,\/usr\/lib,\$\{_libdir\},g' lib/Makefile
perl -pi -e's,\/usr\/lib,\$\{_libdir\},g' i4lnet/Makefile
perl -pi -e's,\/usr\/lib,\$\{_libdir\},g' suppserv/Makefile$

# fix other hardcoded /usr/lib
#grep -rl '/usr/lib' . | xargs perl -pi -e's,/usr/lib,\$\{_libdir\},'

%{__make} clean
make %{?_smp_mflags} _libdir=%{_libdir}

%install
%{makeinstall} %{?_smp_mflags} _libdir=%{_libdir} INSTALL_PREFIX=%{buildroot}

%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%post
/sbin/ldconfig
# make sure SELinux knows about the mISDNuser libs
if [ -x /usr/sbin/sestatus ] && (/usr/sbin/sestatus | grep "SELinux status:" | grep -q "enabled") ; then /sbin/restorecon -v %{_libdir}/libisdnnet.so*; fi
if [ -x /usr/sbin/sestatus ] && (/usr/sbin/sestatus | grep "SELinux status:" | grep -q "enabled") ; then /sbin/restorecon -v %{_libdir}/libmISDN.so*; fi

%postun
/sbin/ldconfig

%files
%defattr(-,root,root)
%doc COPYING.LIB LICENSE
%attr(0755,root,root)		%{_bindir}/*
%attr(0755,root,root)		%{_libdir}/libmISDN.so.0.0.0
%attr(0755,root,root)		%{_libdir}/libisdnnet.so.0.0.0
%attr(0755,root,root)		%{_libdir}/libsuppserv.so.0.0.0

%files devel
%attr(0755,root,root)	%dir	%{_includedir}/mISDNuser
%attr(0644,root,root)		%{_includedir}/mISDNuser/*.h
				%{_libdir}/libmISDN.so
				%{_libdir}/libmISDN.so.0
				%{_libdir}/libisdnnet.so
				%{_libdir}/libisdnnet.so.0
				%{_libdir}/libsuppserv.so
				%{_libdir}/libsuppserv.so.0

%exclude %_libdir/*.a

%changelog
* Thu Oct 16 2007 PaloSanto Solutions <info@elastix.org> - 1.1.6-16
- Change of source mISDN release of 1.1.5 to 1.1.6

* Thu Sep 6 2007 PaloSanto Solutions <info@elastix.org> - 1.1.5-10
- Just recompiling the package in our servers to include it in the Elastix distro

* Wed Jul 18 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.5-9
- update to release 1.1.5
- remove any previous buildroot dirs to prevent b0rkage (Luigi Iotti)

* Sat Jul 14 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.4-8
- fix more hardcoded /usr/lib paths (thanks Rabie van der Merwe)

* Mon Jun 25 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.4-7
- update to release 1.1.4

* Sat Jun 16 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.2-6
- move back to the 1_1_2 release until 1_1_4 is released and proves stable

* Sun May 13 2007 Laimbock Consulting <asterisk@laimbock.com> -1.1.3-5
- update to 1.1.3

* Tue Apr 24 2007 Laimbock Consulting <asterisk@laimbock.com> -1.1.2-4
- update to 1.1.2
- rename version in the rpm filename to 1.1.2 instead of 1_1_2

* Fri Mar 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.1-3
- update to version 1.1.1

* Mon Feb 19 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.0-2
- rework soname patch for 1.1.0
- build it with $(RPM_OPT_FLAGS)

* Wed Feb 14 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.1.0-1
- update to 1.1.0
- manually create .so.0.0.0 links

* Sat Jan 20 2007 Laimbock Consulting <asterisk@laimbock.com> - 1.0.3-2
- force make -j1 so it propagates to the tenovis dir too

* Wed Nov 29 2006 Laimbock Consulting <asterisk@laimbock.com> - 1.0.3-1
- update to release 1.0.3
- put mISDNuser into its own package again
- add $RPM_OPT_FLAGS to the CFLAGS
- create a separate devel package
- add soname versioning (borrowed from dwmw2)

* Wed Jun 14 2006 Laimbock Consulting <asterisk@laimbock.com> - cvs20060815-1
- initial specfile

