Summary: Additional addons for Asterisk: the Open Source Linux PBX
Name: asterisk-addons
Version: 1.4.2
Release: 1%{?lptver}
Epoch: 1
License: GPL
Group: Applications/Internet
URL: http://www.asterisk.org
Source0: http://ftp.digium.com/pub/asterisk/%{name}-%{version}.tar.gz
Patch1: asterisk-addons-1.4.1.uniqueid.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot
Requires: asterisk >= %{version}
BuildRequires: asterisk-devel >= 1.4.5, mysql-devel, zlib-devel

%description
Asterisk is a complete PBX in software. It runs on Linux and provides
all of the features you would expect from a PBX and more. Asterisk
does voice over IP in three protocols, and can interoperate with
almost all standards-based telephony equipment using relatively
inexpensive hardware.
This package contains additional addons for asterisk.

%prep
%setup -q
%patch1 -p1
%ifarch x86_64
perl -pi -e's,^CFLAGS=,CFLAGS=-fPIC ,' format_mp3/Makefile
%endif
perl -pi -e's,/lib/,/%{_lib}/,g' Makefile asterisk-ooh323c/Makefile.* format_mp3/Makefile

%build
%configure
make

cd asterisk-ooh323c
%configure
make

%install
rm -rf %{buildroot}
mkdir -p %{buildroot}%{_libdir}/asterisk/modules/
make install DESTDIR=%{buildroot}

cd asterisk-ooh323c
make install DESTDIR=%{buildroot}

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc doc/cdr_mysql.txt format_mp3/README
%{_libdir}/asterisk/modules/*.so

%changelog
* Tue Dec 26 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.4.0-14
- Update to 1.4.0.

* Wed Dec  6 2006 Alexander Bergolth <leo@leo.wu-wien.ac.at> - 1:1.4.0-13_beta2
- First try with 1.4.0-beta2

* Wed Oct 25 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.2.5-12
- Rebuild w/o Fedora Extras to avoid beta bits.
- Bump epoch to superseed beta bits.

* Thu Oct 19 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.5-11
- Update to 1.2.5.

* Fri Aug 25 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.4-10
- Update to 1.2.4.

* Sat Jun  3 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.3.

* Tue Mar  7 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.2.

* Mon Dec 12 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.1.

* Mon Nov 21 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.0.

* Sat Jul 16 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.9.

* Mon Jun 27 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.8.

* Mon Mar 07 2005 Mark Wormgoor <mark@wormgoor.com>
- Initial version
