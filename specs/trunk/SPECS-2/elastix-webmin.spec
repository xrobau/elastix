%define modname webmin

Summary: Elastix Extra Module Webmin 
Name:    elastix-%{modname}
Version: 2.0.0
Release: 3
License: GPL
Group:   Applications/System
Source: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: webmin
Prereq: elastix >= 2.0

%description
Elastix Extra Module Webmin

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv webminWrapper.php $RPM_BUILD_ROOT/var/www/html/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%post

# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

# Turn off service webmin, defautl
/sbin/service  webmin stop
/sbin/chkconfig --level 2345 webmin off


%clean
rm -rf $RPM_BUILD_ROOT

%preun
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete Webmin menu"
  elastix-menuremove "webmin"
fi

%files
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%changelog

* Tue Jan 26 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-3
- fixed order menu, default in position fourth.

* Tue Jan 26 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-2
- Fixed name parent menu.
- Turn off service module, default.

* Tue Jan 26 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-1
- New module extra webmin.

