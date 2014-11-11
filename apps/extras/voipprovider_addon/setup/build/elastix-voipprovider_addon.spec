%define modname voipprovider_addon

Summary: Elastix VoIPProvider 
Name:    elastix-%{modname}
Version: 2.3.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-pbx >= 2.3.0-13

%description
Elastix VoIPProvider

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT%{_localstatedir}/www/html/
mv modules/ $RPM_BUILD_ROOT%{_localstatedir}/www/html/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT%{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT%{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT%{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p %{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/
touch %{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > %{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
pathModule="%{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}"

pathSQLiteDB="%{_localstatedir}/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{modname}.info`

if [ $1 -eq 1 ]; then #install
    elastix-menuremove "voipprovider"
  # The installer database
    elastix-dbprocess "install" "$pathModule/setup/db"
  # migrate database trunk.db to voipprovider.db
    php $pathModule/setup/migrateDB.php
elif [ $1 -eq 2 ]; then #update
   # The update database
      elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi

elastix-menumerge $pathModule/menu.xml

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="%{_datadir}/elastix/module_installer/%{name}-%{version}-%{release}"

if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete Voipprovider menus"
  elastix-menuremove "%{modname}"
fi

%files
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
%{_datadir}/elastix/module_installer/*

%changelog
* Fri Jan 10 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Providers Accounts: For each module listed here the english help file 
  was renamed to en.hlp and a spanish help file called es.hlp was ADDED.
  SVN Rev[6360]

* Fri Aug 03 2012 Alberto Santos <asantos@palosanto.com> 2.3.0-1
- Initial version.
