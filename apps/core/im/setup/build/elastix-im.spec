%define modname im

Summary: Elastix IM
Name:    elastix-%{modname}
Version: 2.5.0
Release: 1
License: GPL
Group:   Applications/System
#Source0: %{modname}_%{version}-%{release}.tgz
Source0: %{modname}_%{version}-1.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.2.0-18
Requires: yum
Requires: openfire

%description
Elastix IM

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p                          $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p                          $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv -f setup/openfireWrapper.php   $RPM_BUILD_ROOT/var/www/html/
mv setup/                         $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml                       $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

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
  echo "Delete IM menus"
  elastix-menuremove "%{modname}"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%changelog
* Mon Nov  2 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: OF wrapper: switch all uses of $arrLang to _tr().
  SVN Rev[7309]

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: im: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7239]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: im - Build/elastix-im.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Mon May 27 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- CHANGED: im - Build/elastix-im.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Thu May 23 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: im: hardcode IP and port for openfire redirect instead of picking them
  from GET parameters. Pointed out by Fortify report.
  SVN Rev[5002]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- CHANGED: im - Build/elastix-im.spec: Changed Version and Release in
  specfile according to the current branch.
  SVN Rev[4640]

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-1
- CHANGED: im - Build/elastix-im.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4358]

* Wed Oct 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Framework,Modules: remove temporary file preversion_MODULE.info under
  /usr/share/elastix/module_installer/MODULE_VERSION/ which otherwise prevents
  proper cleanup of /usr/share/elastix/module_installer/MODULE_VERSION/ on
  RPM update. Part of the fix for Elastix bug #1398.
- Framework,Modules: switch as many files and directories as possible under
  /var/www/html to root.root instead of asterisk.asterisk. Partial fix for
  Elastix bug #1399.
- Framework,Modules: clean up specfiles by removing directories under
  /usr/share/elastix/module_installer/MODULE_VERSION/setup/ that wind up empty
  because all of their files get moved to other places.
  SVN Rev[4347]

* Fri Jun 29 2012 Luis Abarca <labarca@palosanto.com> 2.2.0-3
- CHANGED: im - Build/elastix-im.spec: update specfile with latest
  SVN history. Changed release in specfile.

* Fri Jun 15 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Bring openfire dependency here, removed from elastix-system.
  SVN Rev[4007]

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-2
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18

* Tue Nov 22 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-1
- Changed: In spec file changed prereq elastix >= 2.2.0-15
- CHANGED: openfireWrapper.php, now only administrators can use
  this script, this resolves the security hole of entering to
  url https://[ip.elast.ix.server]/openfireWrapper.php
  without any authentication. SVN Rev[3307]

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED: Modules - Trunk: The ereg function was replaced by the
  preg_match function due to that the ereg function was deprecated
  since PHP 5.3.0. SVN Rev[2688]

* Fri Jan 28 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- Initial version.

