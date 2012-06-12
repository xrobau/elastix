%define modname developer

Summary: Elastix Module Developer 
Name:    elastix-%{modname}
Version: 2.2.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix >= 2.0.4-1

%description
Elastix Module Developer

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# Additional (module-specific) files that can be handled by RPM
#mkdir -p $RPM_BUILD_ROOT/opt/elastix/
#mv setup/dialer

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%post

# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT

%preun
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete developer menus"
  elastix-menuremove "%{modname}"
fi

%files
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%changelog
* Wed Sep 28 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-1
- FIXED: module load_module, the value of "order" is now
  considered for adding a new menu
  SVN Rev[3013]
- CHANGED: The split function of these modules was replaced
  by the explode function due to that the split function was
  deprecated since PHP 5.3.0.
  SVN Rev[2650]

* Tue Apr 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED: module build_module, missed tag >. SVN Rev[2513]

* Tue Dec 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- CHANGED: Module Developer, change format URL to be a array,
  this in the case of modules type of grid. SVN Rev[2164]
- CHANGED: Module Developer, change array of language $arrLang 
  to the function _tr() and a updating the modules type of grid
  to support new methods of paloSantoGrid.class.php. SVN Rev[2163]
- UPDATED: Updated source of modules type of grid to support export
  in format PDFs, EXCEL y CSV. SVN Rev[1895]

* Sat Aug 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-4
- FIXED:     Change document root by conf variable $arrConf.

* Mon Jun 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-3
- Fixed bug, where the position module install was 0 before the other menus like system,agend, and so on.

* Wed Feb 03 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-2
- Update module.

* Mon Oct 19 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-1
- Initial version.
