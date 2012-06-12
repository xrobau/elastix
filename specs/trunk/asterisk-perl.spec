%define perl_vendorlib %(eval "`perl -V:installvendorlib`"; echo $installvendorlib)
%define perl_vendorarch %(eval "`perl -V:installvendorarch`"; echo $installvendorarch)

%define rname asterisk-perl
%define prefix /usr
%define asteriskver 1.2.17
%define version 0.09
%define release 6

Summary: A perl agi interface module for asterisk
Name: asterisk-perl
Version: %{version}
Release: %{release}%{?dist}
License: GPL 
Group: Applications/Internet
URL: http://asterisk.gnuinter.net/
Source0: http://asterisk.gnuinter.net/files/asterisk-perl-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildRequires: perl >= 0:5.80
BuildRequires: asterisk-devel >= %{asteriskver}
Requires: perl >= 0:5.80
Requires: asterisk >= %{asteriskver}
BuildArch: noarch

%description
asterisk-perl is a collection of perl modules to be used with the Asterisk
PBX, an open source pbx system developed by Mark Spencer.

%prep
%setup -n %{rname}-%{version}

%build
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}
CFLAGS="%{optflags}" %{__perl} Makefile.PL \
	PREFIX="%{buildroot}%{_prefix}" \
	INSTALLDIRS="vendor"
%{__make} %{?_smp_mflags} all

%install
%{__rm} -rf %{buildroot}
%{__make} install
%{__rm} -rf %{buildroot}%{perl_archlib} %{buildroot}%{perl_vendorarch}

%clean 
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
%doc README CHANGES
%attr(0444,root,root)	%{_mandir}/man3/Asterisk*
%attr(0444,root,root)	%{perl_vendorlib}/*

%changelog
* Thu Mar 22 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.09-6
- Autobuild: new asterisk version 1.2.17 for (build)requires
- bump to release 6

* Wed Mar 7 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.09-5
- fix an rpmlint issue

* Mon Mar 05 2007 Laimbock Consulting <asterisk@laimbock.com> - 0.09-4
- Autobuild: new asterisk version 1.2.16 for (build)requires
- bump to release 4

* Mon Mar 5 2007 Laimbock Consulting <asterisk@laimbock.com> -0.09-3
- remove asterisk version from the rpm name
- bump to release 3

* Tue Oct 31 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.09_1.2.13-1
- update to 0.09

* Wed Aug 21 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.08_1.2.11-1
- tweak specfile a bit

* Mon May 22 2006 Laimbock Consulting <asterisk@laimbock.com> - 0.08-4
- use Dag's perl specfile magic so asterisk-perl builds on FC4/FC5/CentOS/RHEL
- add BuildArch: noarch

