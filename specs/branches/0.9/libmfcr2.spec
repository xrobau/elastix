# force _smp_mflags to -j1
%define _smp_mflags -j1

%define prefix /usr
%define version 0.0.3
%define zaptelver 1.4.5.1
%define superver 0.0.2
%define unicallver 0.0.3
%define spandspver 0.0.3
%define release 18

Summary: A library for MFC/R2 signaling on E1 lines
Name: libmfcr2
Version: %{version}
Release: %{release}%{?dist}
License: GPL
Group: System Environment/Libraries
URL: http://www.soft-switch.org/
Source0: http://www.soft-switch.org/downloads/unicall/unicall-0.0.3pre11/%{name}-%{version}.tar.gz
#Patch0: libmfcr2-spandsp-pre28.patch
Patch1: http://www.moythreads.com/unicall/soft-switch/r1b1/patches/libmfcr2.patch
Patch2: libmfcr2-elastix.patch
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires: zaptel-devel = %{zaptelver}
BuildRequires: spandsp-devel = %{spandspver}
BuildRequires: libsupertone-devel = %{superver}
BuildRequires: libunicall-devel = %{unicallver}
BuildRequires: libxml2-devel => 2.6.16
BuildRequires: libtiff-devel >= 3.6.1
Requires: spandsp = %{spandspver}
Requires: libsupertone = %{superver}
Requires: libunicall = %{unicallver}
Requires: libxml2 >= 2.6.16
Requires: libtiff >= 3.6.1

%description
libmfcr2 is a library designed to support MFC/R2 signalling in the ZapTel
environment, but should be easily adaptable to other dumb E1 cards that allow
channelised CAS signaled operation.

It is intended that the library eventually support most national variants of
the MFC/R2 protocol. The application specifies the national variant when
opening a channel.

It can make and receive calls successfully when connected to a Dialogic E1
card running their GlobalCall package, in China and Argentina modes.

%prep
%setup -q -n %{name}-%{version}
#%patch0 -p1
%patch1 -p1
%patch2 -p1

%build
%configure --prefix=/usr

# fix hardcoded protocoldir setting
perl -pi -e's,^protocoldir = /usr/lib/unicall/protocols,protocoldir = \$\{_libdir\}/unicall/protocols,g' Makefile

# fix hardcoded testing of protocoldir
perl -pi -e's,test \-z \"\$\(protocoldir\)\",test \-z \"\$\(DESTDIR\)\$\(protocoldir\)\",g' Makefile

%{__make} %{?_smp_mflags} _libdir=%{_libdir}


%install
%{makeinstall} %{?_smp_mflags} INSTALL_PREFIX=%{buildroot} DESTDIR=%{buildroot} _libdir=%{_libdir}

# remove protocol_mfcr2.la
rm %{buildroot}%{_libdir}/unicall/protocols/protocol_mfcr2.la


%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}


%post
/sbin/ldconfig

# make sure SELinux knows about protocol_mfcr2.so
if [ -x /usr/sbin/sestatus ] && (/usr/sbin/sestatus | grep "SELinux status:" | grep -q "enabled") ; then /sbin/restorecon -v %{_libdir}/unicall/protocols/protocol_mfcr2.so; fi


%postun -p /sbin/ldconfig


%files
%defattr(-,root,root)
%doc COPYING README ChangeLog AUTHORS NEWS
%attr(0755,root,root)	%dir	%{_libdir}/unicall/protocols
%attr(0644,root,root)		%{_libdir}/unicall/protocols/protocol_mfcr2.so

%changelog
* Sat Oct 20 2007 Edgar Landivar <elandivar@palosanto.com> - 0.0.3-18
- First rebuild in the Elastix distro builder.
- Now built against zaptel 1.4
- Moises Silva's patch applied
- Additional patch to fix some incompatibilities in the Moises Silva's patch

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-15
- building against spandsp 0.0.2pre26 did not work. back to spandsp 0.0.3

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-14
- require spandsp 0.0.2pre26 and not spandsp 0.0.3 as said by Steve U.
- make (Build)Requires more exact to prevent future b0rkage

* Wed Jul 18 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-13
- Autobuild: new zaptel version 1.2.19 for (build)requires
- bump to release 13

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-12
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 12

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-11
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 11

* Tue Jun 12 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-10
- Autobuild: new zaptel version 1.2.18 for (build)requires
- bump to release 10

* Tue Apr 24 2007 Laimbock Consulting <asterisk@laimbock.com> -0.0.3-9
- require spandsp 0.0.3

* Fri Mar 23 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-8
- require zaptel 1.2.16

* Tue Mar 6 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-7
- require spandsp 0.0.3-2.pre28
- require zaptel 1.2.15
- sync with Fedora Guide Lines

* Tue Feb 20 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.3-6
- require spandsp-0.0.3-1.pre27

