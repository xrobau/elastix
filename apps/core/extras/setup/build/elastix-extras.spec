%define modname extras

Summary: Elastix Extras
Name:    elastix-%{modname}
Version: 2.5.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_2.0.4-4.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.2.0-18

%description
Elastix EXTRA

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p                   $RPM_BUILD_ROOT/var/www/html/
mv modules/                $RPM_BUILD_ROOT/var/www/html/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p                   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/                  $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml                $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

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
  echo "Delete Extras menus"
  elastix-menuremove "%{modname}"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%changelog
* Thu Nov 24 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Added Ukrainian, Russian translations.
  SVN Rev[7789]

* Fri Nov  6 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Extras: replace "Manufacturer" with "Developer" everywhere.
  SVN Rev[7336]

* Mon Nov  2 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Extras: replace hand-coded translation loading with
  load_language_module().
  SVN Rev[7303]

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: extras: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7237]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: extras - Build/elastix-extras.spec: update specfile with latest
  SVN history. Bumped version and release in specfile.

* Mon May 26 2014 Bruno Macias <bmacias@palosanto.com> 2.4.0-5
- DELETED: extras - framed menu, vtigerCRM was deleted. Now VtigerCRM is a
  Addons.

* Wed Apr 09 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-4
- CHANGED: extras - Build/elastix-extras.spec: update specfile with latest
  SVN history. Bumped release in specfile.

* Mon Apr 07 2014 Luis Abarca <labarca@palosanto.com>
- REMOVED: extras - elastix-extras.spec: Due to SVN commit 5723, the static
  folder is not part of this module anymore, making unnecessary the creation
  and its corresponding directory change in the spec file. Uncommenting the
  source0 %{modname}_%{version}-%{release}.tgz.
  SVN Rev[6569]

* Wed Jan 29 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- DELETED: xmlservices: Remove unused xmlservices directory. This code is Cisco
  specific, has a very poor implementation and exposes the external addressbook
  without authentication. This functionality is now better implemented in the
  new Endpoint Configurator.
- CHANGED: remove unexplained yum dependency.
  SVN Rev[6448]

* Tue Jan 14 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-3
- CHANGED: extras - Build/elastix-extras.spec: update specfile with latest
  SVN history. Bumped release in specfile.
  SVN Rev[6379]

* Wed Jan 8 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Softphones, Fax Utilities, Instant Messaging: For each module listed here the english help file was renamed to en.hlp and a spanish help file called es.hlp was ADDED.
  SVN Rev[6350]

* Wed Aug 28 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Instant Messaging: fix references to uninitialized variables.
  SVN Rev[5811]

* Wed Aug 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- CHANGED: extras - Build/elastix-extras.spec: update specfile with latest
  SVN history. Bumped release in specfile.
  SVN Rev[5786]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- REMOVED: Module Downloads, Help files with wrong names were deleted
  SVN Rev[5729]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: The names of the Downloads module's help files were changed.
  SVN Rev[5725]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- ADDED: extras modules, Static pages on Donwloads menu, were added as modules.
  SVN Rev[5723]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- ADDED: extras modules, Static pages on Donwloads menu, were added as modules.
  SVN Rev[5722]

* Mon Aug 12 2013 Jose Briones <jbriones@palosanto.com>
- UPDATE: Correction of some mistakes in the description.
  SVN Rev[5719]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- CHANGED: extras - Build/elastix-extras.spec: Changed Version and Release in
  specfile according to the current branch.

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-1
- CHANGED: extras - Build/elastix-extras.spec: update specfile with latest
  SVN history. Changed release in specfile.

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

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-1
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-4
- CHANGED: The split function of these modules was replaced by
  the explode function due to that the split function was
  deprecated since PHP 5.3.0. SVN Rev[2650]
- FIXED: a2b menus in extras/menu.xml, deleted It is not usefull.
  SVN Rev[2499]

* Tue Apr 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-3
- FIXED: a2b menus in extras/menu.xml, deleted It is not usefull.
  SVN Rev[2499]

* Tue Mar 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED: the information showed ih those static files had
  some changes according to the bug #779. SVN Rev[2406]
- CHANGED:  menu.xml: all modules; new attribute "desc" into
  tag "group" for add a description of group. SVN Rev[2299]
- CHANGED:  menu.xml in all modules was changed to support new
  tag "permissions" where it has all permissions of group per
  module. SVN Rev[2294]

* Fri Jan 28 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- Initial version.

