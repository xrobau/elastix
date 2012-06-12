# force _smp_mflags to -j1
%define _smp_mflags -j1

%define prefix /usr
%define version 0.0.3
%define zaptelver 1.4.5.1
%define superver 0.0.2
%define spandspver 0.0.3
%define release 19

Summary: An abstration layer for telephony signalling
Name: libunicall
Version: %{version}
Release: %{release}%{?dist}
License: GPL
Group: System Environment/Libraries
URL: http://www.soft-switch.org/
Source0: http://www.soft-switch.org/downloads/unicall/unicall-0.0.3pre9/%{name}-%{version}.tar.gz
Patch0: libunicall-0.0.3-no_testcall.patch
Patch1: http://www.moythreads.com/unicall/soft-switch/r1b1/patches/libunicall.patch
Packager: Laimbock Consulting <asterisk@laimbock.com>
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires: zaptel-devel = %{zaptelver}
BuildRequires: spandsp-devel = %{spandspver}
BuildRequires: libsupertone-devel = %{superver}
Requires: spandsp = %{spandspver}
Requires: libsupertone = %{superver}

%description
libunicall is an abstration layer for telephony signalling.

%package devel
Summary: Development package for %{name}
Group: Development/Libraries
Requires: libunicall = %{version}

%description devel
Use this package for building/developing applications against %{name}.

%prep
%setup -q -n %{name}-%{version}
%patch0 -p1
%patch1 -p1

%ifarch x86_64 ppc64
perl -pi -e's,lib/unicall,lib64/unicall,g' Makefile.am
perl -pi -e's,lib/unicall,lib64/unicall,g' Makefile.in
%endif

# fix some stuff so it compiles against spandsp-0.0.3
perl -pi -e's,dtmf_get,dtmf_rx_get,g' testcall.c
perl -pi -e's,dtmf_put,dtmf_tx_put,g' testcall.c

%build
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%configure --prefix=/usr
%{__make}


%install
%{makeinstall} INSTALL_PREFIX=%{buildroot}

# remove static libunicall
rm %{buildroot}%{_libdir}/libunicall.a
rm %{buildroot}%{_libdir}/libunicall.la


%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}


%post
/sbin/ldconfig

# make sure SELinux knows about libunicall.so.0.0.1
if [ -x /usr/sbin/sestatus ] && (/usr/sbin/sestatus | grep "SELinux status:" | grep -q "enabled") ; then /sbin/restorecon -v %{_libdir}/libunicall.so.0.0.1; fi


%postun -p /sbin/ldconfig


%files
%defattr(-,root,root)
%doc COPYING README ChangeLog AUTHORS NEWS
%attr(0755,root,root)		%{_libdir}/libunicall.so.0.0.1


%files devel
%doc COPYING README ChangeLog AUTHORS NEWS
%defattr(-,root,root)
				%{_libdir}/libunicall.so.0
                                %{_libdir}/libunicall.so
%attr(0644,root,root)           %{_includedir}/unicall.h
%attr(0755,root,root)	%dir	%{_includedir}/unicall
%attr(0644,root,root)           %{_includedir}/unicall/*

%changelog
* Sat Oct 20 2007 Edgar Landivar <elandivar@palosanto.com> - 0.0.3-19
- First rebuild in the Elastix distro builder.
- Moises Silva's patch applied.

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-16
- building against spandsp 0.0.2pre26 did not work. back to spandsp 0.0.3
- create libunicall-0.0.3-no_testcall.patch that removes all testcall stuff
-  from the Makefile to prevent an undefined symbol in chan_unicall

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-15
- require spandsp 0.0.2pre26 and not spandsp 0.0.3 as said by Steve U.
- remove spandsp 0.0.3 compilation fixes
- make (Build)Requires more exact to prevent future b0rkage

* Wed Jul 18 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-14
- Autobuild: new zaptel version 1.2.19 for (build)requires
- bump to release 14

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-13
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 13

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-12
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 12

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-11
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 11

* Tue Apr 24 2007 Laimbock Consulting <asterisk@laimbock.com> -0.0.3-10
- require spandsp 0.0.3

* Fri Mar 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-9
- require zaptel 1.2.16

* Wed Mar 7 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-8
- fix an rpmlint issue

* Tue Mar 6 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-7
- require spandsp 0.0.3-2.pre28
- require zaptel 1.2.15

* Tue Feb 20 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-6
- require spandsp-0.0.3-1.pre27

* Tue Feb 20 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-4
- require spandsp-0.0.2-1.pre26

