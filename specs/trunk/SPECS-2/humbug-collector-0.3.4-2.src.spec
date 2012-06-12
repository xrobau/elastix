#
# spec file for humbug-collector package
#
# Copyright  (c) 2011  Humbug Telecom Labs Ltd.
# 
# Igor Ratgauzer <igor@humbuglabs.org>
#
Name:           humbug-collector
Version:        0.3.4
Release:        3%{?dist}
Summary:        The Humbug analytics collection agent
Group:          Applications/Communications
License:        GPL
Packager:       Igor Ratgauzer <igor@humbuglabs.org>
Vendor:         Humbug Telecom Labs Ltd.
BuildArch:      %{_arch}
URL:            http://www.humbuglabs.org
Source:         humbug-collector-%{version}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}
BuildRequires:	gcc, newt

%description
The Humbug Collector connects your PBX to Humbug Analytics 
for in-depth reporting, alerting and protection from fraud.

%prep
%setup -q

%build
make all
# make config

%install
# make config

%{__rm} -rf %{buildroot}

mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%{__install} -d -m0755 %{buildroot}/etc/humbug
%{__install} -Dp -m0644 humbug_www.conf %{buildroot}/etc/humbug/humbug_www.conf
%{__install} -Dp -m0755 %{name} %{buildroot}%{_bindir}/%{name}
%{__install} -Dp -m0755 index.php %{buildroot}/var/www/html/humbug/index.php
%{__install} -Dp -m0755 json_base.php %{buildroot}/var/www/html/humbug/json_base.php
%{__install} -Dp -m0755 humbug-setup %{buildroot}%{_bindir}/humbug-setup
%{__install} -Dp -m0755 init.d/rc.redhat.humbug %{buildroot}/etc/init.d/%{name}
%{__install} -Dp -m0755 humbug-config.sh %{buildroot}/usr/bin/humbug-config.sh
%{__install} -d -m0755 %{buildroot}/var/run/humbug
%{__install} -d -m0755 %{buildroot}/var/log/humbug

cp init.d/rc.redhat.humbug $RPM_BUILD_ROOT/etc/init.d/humbug-collector
chmod +x $RPM_BUILD_ROOT/etc/init.d/humbug-collector


%post
/usr/bin/humbug-config.sh
/sbin/chkconfig --add humbug-collector
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

%preun
/sbin/chkconfig --del %{name}

%clean
%{__rm} -rf %{buildroot} $RPM_BUILD_DIR/%{name}-%{version}/

%files
%defattr(-,asterisk,asterisk)
%dir /etc/humbug
/etc/init.d/humbug-collector
%config /etc/humbug/humbug_www.conf
/var/www/html/humbug/index.php
/var/www/html/humbug/json_base.php
%{_bindir}/humbug-config.sh
%{_bindir}/humbug-collector
%{_bindir}/humbug-setup
%dir /var/run/humbug
%dir /var/log/humbug
/usr/share/elastix/module_installer/*

%changelog
* Wed May 18 2011 Alberto Santos <asantos@palosanto.com> 0.3.4-3
- Added file menu.xml which is used in the %%install process to create
  a menu for humbug
* Thu Apr 28 2011 Igor Ratgauzer <igor@humbuglabs.org> - 0.3.4-2
- Small bug fix with files permissions
* Wed Apr 27 2011 Igor Ratgauzer <igor@humbuglabs.org> - 0.3.4-1
- First release
