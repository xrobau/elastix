%define modname developer

Summary: Elastix Module Developer
Name:    elastix-%{modname}
Version: 2.5.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_%{version}-1.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.5.0-6

%description
Elastix Module Developer

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/privileged
rm -rf setup/build/
mv setup/usr/share/elastix/privileged/*  $RPM_BUILD_ROOT/usr/share/elastix/privileged
rmdir setup/usr/share/elastix/privileged setup/usr/share/elastix setup/usr/share setup/usr

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
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
%defattr(755, root, root)
/usr/share/elastix/privileged/*

%changelog
* Sun Aug 14 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: elastix-menuhack: implement xmlexport option that outputs a menu.xml
  document from a list of menu options.
  SVN Rev[7705]

* Wed Jul  6 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: elastix-menuhack: allow ELASTIX_ROOT to specify location of base
  Elastix for command-line tools.
  SVN Rev[7647]
- CHANGED: elastix-menuhack: use standard Elastix framework definitions for
  databases instead of hardcoding the paths.
  SVN Rev[7646]
- FIXED: elastix-menuhack: fix incorrect group definition passed to method
  Installer::addResourceMembership().
  SVN Rev[7645]

* Mon Jun 27 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- ADDED: Developer: add elastix-menuhack utility.
  SVN Rev[7641]
- CHANGED: Developer: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7640]

* Fri Apr 22 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: check whether /etc/localtime is a symlink and use it as an additional
  way to find out the current timezone.
  SVN Rev[7599]

* Thu Apr 09 2015 Bruno Macias Velasco <bmacias@elastix.com> 2.5.0-1
- CHANGED: Build Module was changed, index_form.s and index_grid.s reduce
  lines language handler.
  FIXED: Delete Module was fixed, new elastix-framework obsolete xajax lib
  module was changed to use jquery action.

* Fri Jan 10 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Build Module, Delete Module, Language Admin: For each module
  listed here the english help file was renamed to en.hlp and a spanish
  help file called es.hlp was ADDED.
  SVN Rev[6358]

* Wed Aug  8 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Language Admin: (jbriones) relax strict validation on language
  translation in order to allow an empty arrLangModule.
  SVN Rev[5590]

* Wed Jul 31 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 2.3.0-5
- Bump version for release.
- FIXED: Delete Module: fix constructor call for paloSantoNavigation.
  SVN Rev[5455]

* Tue Jul 30 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Build Module: put Prereq for updated version of paloSantoNavigation,
  and fix constructor call.
  SVN Rev[5454]

* Thu Nov 29 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 2.3.0-4
- CHANGED: Bump version for release.
- DELETED: Removed Load Module functionality, as now modules should be installed
  via RPM packages.
  SVN Rev[4468]
- CHANGED: Language Admin: extend develbuilder to save a list of translations
  from a XML specification. Use this to reimplement the Save All functionality.
  SVN Rev[4461]

* Wed Nov 28 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Language Admin: extend develbuilder to add a single translation to
  a language file. Use this to reimplement the Add Translation functionality.
  SVN Rev[4459]

* Tue Nov 27 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Language Admin: extend develbuilder to create files for new
  languages. Use this to reimplement the creation of a new language. Also,
  remove some dead code.
  SVN Rev[4458]

* Fri Nov 23 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- DELETED: Remove useless and vulnerable webservice support.
  SVN Rev[4457]

* Thu Nov 22 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Delete Module: replace direct 'rm -rf' with calls to privileged
  script, which has been extended to remove a single module directory.
  SVN Rev[4455]

* Fri Nov 16 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Build Module: rewrite to enclose the creation of the module inside a
  transaction. The rewrite also achieved a net reduction of code size.
  SVN Rev[4440]
- FIXED: Developer: check that form element exists before traversing its fields.
  SVN Rev[4439]
- CHANGED: Build Module: create new privileged script develbuilder and
  reimplement module creation using this script. Required to cope with recent
  security changes that switch ownership of Elastix GUI to root. As a side
  effect, we get a net reduction of code size and some improvement in
  readability.
- CHANGED: Developer: switched module ownership to root.root.
  SVN Rev[4438]

* Wed Jul 11 2012 Alberto Santos <asantos@palosanto.com> 2.3.0-3
- CHANGED: In spec file, changed prereq elastix-framework >= 2.3.0-6
- FIXED: module language_admin, words with a key that has spaces were
  not able to change the value. To fix this problem, a new hidden input
  was added to the form which contains the key of the word
  SVN Rev[4061]
- FIXED: module language_admin, fixed mantis bug #1317, number of
  pages was not displayed and also keys with the character '_' were
  not able to change
  SVN Rev[4053]

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-2
- CHANGED: extras module build_module, changed the use from xajax to the
  framework function "requestrequest"
  SVN Rev[3877]
- ADDED: Setup - build: Added a folder for svn restructuration.
  SVN Rev[3860]

* Wed Mar 07 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- CHANGED: In spec file changed prereq elastix-framework >= 2.3.0-1
- CHANGED: language_admin index.php add control to applied filters
  SVN Rev[3717]
- UPDATED:
  SVN Rev[3549]
- CHANGED: Modules - Extras: Added support for the new grid layout.
  SVN Rev[3547]

* Tue Jan 17 2012 Alberto Santos <asantos@palosanto.com> 2.2.0-3
- CHANGED: In spec file changed prereq elastix-framework >= 2.2.0-25
- FIXED: modules extras delete_modules, when trying to delete
  second or third modules level, the combos "Level 2" and
  "Level 3" are empty. This bug was introduced due to the
  improves in index.php for filtering menu.
  SVN Rev[3534]
- FIXED: modules extras build_module, when trying to create third
  modules level, the combo "Level 2 Parent" was empty. This bug was
  introduced due to the improves in index.php for filtering menu.
  FIXED: modules extras build_module, this module was adapted to
  the new standard in Elastix 2.2.0 in which the title and icon
  of the module are handled by the framework
  SVN Rev[3533]

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-2
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18-
- CHANGED: module load_module, now the module title is handled by
  the framework. SVN Rev[3288]
- CHANGED: module language_admin, now the module title is handled
  by the framework. SVN Rev[3287]
- CHANGED: module delete_module, now the module title is handled
  by the framework. SVN Rev[3286]
- CHANGED: module build_module, now the module title is handled
  by the framework. SVN Rev[3285]

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
