# force _smp_mflags to -j1
%define _smp_mflags -j1

%define prefix /usr
%define version 0.0.2
%define spandspver 0.0.3
%define release 15

Summary: A library for supervisory tone generation and detection
Name: libsupertone
Version: %{version}
Release: %{release}%{?dist}
License: GPL
Group: System Environment/Libraries
URL: http://www.soft-switch.org/
Source0: http://www.soft-switch.org/downloads/unicall/unicall-0.0.3pre9/%{name}-%{version}.tar.gz
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
# Elastix: Commenting this line out for some weird error in the build machine
#          spandsp-devel is installed and with the right version 
BuildRequires: spandsp-devel = %{spandspver}
Requires: spandsp = %{spandspver}

%description
libsupertone is a library for supervisory tone generation and detection

%package devel
Summary: Development package for %{name}
Group: Development/Libraries
Requires: libsupertone = %{version}

%description devel
Use this package for building/developing applications against %{name}.


%prep
%setup -q -n %{name}-%{version}


%build
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%configure --prefix=/usr
%{__make} %{?_smp_mflags}

%install
%{makeinstall} %{?_smp_mflags} INSTALL_PREFIX=%{buildroot}

# remove static libsupertone
rm %{buildroot}%{_libdir}/libsupertone.a
rm %{buildroot}%{_libdir}/libsupertone.la


%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}


%post
/sbin/ldconfig

# make sure SELinux knows about libsupertone.so.0.0.1
if [ -x /usr/sbin/sestatus ] && (/usr/sbin/sestatus | grep "SELinux status:" | grep -q "enabled") ; then /sbin/restorecon -v %{_libdir}/libsupertone.so.0.0.1; fi


%postun -p /sbin/ldconfig


%files
%defattr(-,root,root)
%doc COPYING README ChangeLog AUTHORS NEWS
%attr(0755,root,root)		%{_libdir}/libsupertone.so.0.0.1


%files devel
%doc COPYING README ChangeLog AUTHORS NEWS
%defattr(-,root,root)
				%{_libdir}/libsupertone.so.0
                                %{_libdir}/libsupertone.so
%attr(0644,root,root)           %{_includedir}/libsupertone.h


%changelog
* Sat Oct 20 2007 Edgar Landivar <elandivar@palosanto.com> - 0.0.2-15
- First rebuild in the Elastix distro builder.
- Build against a different spandsp package.

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-11
- building against spandsp 0.0.2pre26 did not work. back to spandsp 0.0.3

* Fri Aug 10 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-10
- require spandsp 0.0.2pre26 and not spandsp 0.0.3 as said by Steve U.
- make (Build)Requires more exact to prevent future b0rkage

* Tue Apr 24 2007 Laimbock Consulting <asterisk@laimbock.com> -0.0.2-9
- require spandsp 0.0.3

* Wed Mar 7 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-8
- fix an rpmlint issue

* Tue Mar 6 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-7
- require spandsp 0.0.3-2.pre28

* Tue Feb 20 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-6
- require spandsp-0.0.3-1.pre27

* Wed Dec 20 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-4
- require spandsp-0.0.2-1.pre26

* Tue Nov 7 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-3
- add smp_mflags again
- force _smp_mflags to -j1

* Tue Oct 3 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-2
- remove smp_mflags from make to prevent compilation failure

* Tue Aug 15 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.0.2-1
- initial spec file

