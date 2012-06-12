Summary: An implementation of Primary Rate ISDN
Name: libpri
Version: 1.4.11.3
Release: 0%{?dist}
License: GPLv2+
Group: System Environment/Libraries
URL: http://www.asterisk.org/
Source0: http://downloads.digium.com/pub/telephony/libpri/releases/libpri-%{version}.tar.gz
Patch0: libpri-1.4.11-optimization.patch

BuildRoot:  %{_tmppath}/%{name}-%{version}-root-%(%{__id_u} -n)

%description
libpri is a C implementation of the Primary Rate ISDN specification.
It was based on the Bellcore specification SR-NWT-002343 for National
ISDN.  As of May 12, 2001, it has been tested work with NI-2, Nortel
DMS-100, and Lucent 5E Custom protocols on switches from Nortel and
Lucent.

%package devel
Summary: Development files for libpri
Group: Development/Libraries
Requires: libpri = %{version}-%{release}

%description devel
Development files for libpri.

%prep
%setup0 -q -n %{name}-%{version}
%patch0 -p1
%{__perl} -pi -e 's|\$\(INSTALL_BASE\)/lib|%{_libdir}|g' Makefile

%build
make %{?_smp_mflags}

%install
rm -rf %{buildroot}
make INSTALL_PREFIX=%{buildroot} install
rm %{buildroot}%{_libdir}/libpri.a

%clean
rm -rf %{buildroot}

%post -p /sbin/ldconfig

%postun -p /sbin/ldconfig

%files 
%defattr(-,root,root,-)
%doc LICENSE README
%{_libdir}/libpri.so.*

%files devel
%defattr(-,root,root,-)
%{_includedir}/libpri.h
%{_libdir}/libpri.so

%changelog
* Thu Jul 01 2010 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.11.3-0
- Update to 1.4.11.3 for Elastix

* Tue Jun 08 2010 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.11.2-0
- Update to 1.4.11.2 for Elastix

* Wed Jun 02 2010 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.11.1-0
- Update to 1.4.11.1 for Elastix

* Mon May 31 2010 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.11-0
- Update to 1.4.11 for Elastix

* Wed Oct 21 2009 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.10.2-0
- Update to 1.4.10.2 for Elastix

* Wed Jul 08 2009 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.10.1-0
- Update to 1.4.10.1 for Elastix

* Fri Apr 24 2009 Alex Villacis Lasso <a_villacis@palosanto.com> - 1.4.10-0
- Update to 1.4.10 for Elastix 

* Tue Aug  5 2008 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.7-1
- Update to 1.4.7

* Tue Jul 29 2008 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.6-1
- Update to 1.4.6

* Mon Feb 11 2008 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.3-2
- Rebuild for GCC 4.3

* Thu Dec 20 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.3-1
- Update to 1.4.3.
- Drop upstreamed patch.

* Thu Nov  1 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.2-1
- Update to 1.4.2

* Wed Aug 29 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.1-5
- Bump release.

* Wed Aug 29 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.1-4
- Add patch to define size_t

* Tue Aug 29 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.1-3
- Update license tag.
- Update URL.

* Wed Aug 29 2007 Fedora Release Engineering <rel-eng at fedoraproject dot org> - 1.4.1-2
- Rebuild for selinux ppc32 issue.

* Mon Jul  9 2007 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.1-1
- Update to 1.4.1

* Sat Dec 23 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.0-3
- Update to 1.4.0 final

* Sat Oct 14 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.0-2.beta1
- Fix lib paths for 64 bit systems.

* Sat Oct 14 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.0-1.beta1
- Get rid of pesky "." in -devel summary.
- Remove zaptel-devel BR

* Fri Oct 13 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.0-1.beta1
- devel package needs to Require: main package

* Fri Oct 13 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.4.0-0.beta1
- Update to 1.4.0-beta1

* Fri Jun  2 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.3
- Update to 1.2.3
- Add dist tag to release
- Update source URL

* Wed Jan 18 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.2-1
- Update to 1.2.2.
- Fix the spelling of Paul Komkoff Jr.'s name.

* Fri Jan 13 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.1-4
- Eliminate the libpri-install.patch and other improvements based on suggestions from Paul Komkoff Jr.

* Thu Jan 12 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.1-3
- Fix building on 64 bit systems.

* Thu Jan 12 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.1-2
- Changed buildroot to meet FE packaging guidelines
- Don't forget docs
- Modify %%post so that ldconfig dep will be picked up automatically
- Add %%postun so that ldconfig gets run on uninstall
- Don't package the static library
- Changed $RPM_BUILD_ROOT to %%{buildroot} (yes, I know I was consistent before, but I prefer %%{buildroot})

* Wed Jan 11 2006 Jeffrey C. Ollie <jeff@ocjtech.us> - 1.2.1-1
- First version for Fedora Extras
