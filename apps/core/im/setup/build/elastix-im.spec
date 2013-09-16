%define modname im

Summary: Elastix IM 
Name:    elastix-%{modname}
Version: 3.0.0
Release: 3
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_%{version}-1.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 3.0.0-1
Requires: yum
Requires: openfire

%description
Elastix IM

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
#mkdir -p $RPM_BUILD_ROOT/var/www/html/
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/apps/%{name}/
bdir=%{_builddir}/%{modname}
if [ -d $bdir/modules/$FOLDER0/$FOLDER1/web/ ]; then
for FOLDER0 in $(ls -A modules/)
do
		for FOLDER1 in $(ls -A $bdir/modules/$FOLDER0/)
		do
				mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/apps/%{name}/$FOLDER1/
				for FOLFI in $(ls -I "web" $bdir/modules/$FOLDER0/$FOLDER1/)
				do
					if [ -d $bdir/modules/$FOLDER0/$FOLDER1/$FOLFI ]; then
						mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/apps/%{name}/$FOLDER1/$FOLFI
						if [ "$(ls -A $bdir/modules/$FOLDER0/$FOLDER1/$FOLFI)" != "" ]; then
						mv $bdir/modules/$FOLDER0/$FOLDER1/$FOLFI/* $RPM_BUILD_ROOT/usr/share/elastix/apps/%{name}/$FOLDER1/$FOLFI/
						fi
					elif [ -f $bdir/modules/$FOLDER0/$FOLDER1/$FOLFI ]; then
						mv $bdir/modules/$FOLDER0/$FOLDER1/$FOLFI $RPM_BUILD_ROOT/usr/share/elastix/apps/%{name}/$FOLDER1/
					fi
				done
				case "$FOLDER0" in 
					frontend)
						mkdir -p $RPM_BUILD_ROOT/var/www/html/web/apps/$FOLDER1/
						if [ -d $bdir/modules/$FOLDER0/$FOLDER1/web/ ]; then
							mv $bdir/modules/$FOLDER0/$FOLDER1/web/* $RPM_BUILD_ROOT/var/www/html/web/apps/$FOLDER1/
						fi
					;;
					backend)
						mkdir -p $RPM_BUILD_ROOT/var/www/html/admin/web/apps/$FOLDER1/
						if [ -d $bdir/modules/$FOLDER0/$FOLDER1/web/ ]; then
							mv $bdir/modules/$FOLDER0/$FOLDER1/web/* $RPM_BUILD_ROOT/var/www/html/admin/web/apps/$FOLDER1/
						fi	
					;;
				esac
		done
done
fi

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
#%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/usr/share/elastix/apps/*

%changelog
* Fri Sep 13 2013 Luis Abarca <labarca@palosanto.com> 3.0.0-3
- CHANGED: im - Build/elastix-im.spec: Update specfile with latest
  SVN history. Bump Release in specfile.

* Wed Sep 11 2013 Luis Abarca <labarca@palosanto.com> 
- ADDED: im - setup/infomodules.xml/: Within this folder are placed the new xml
  files that will be in charge of creating the menus for each module.
  SVN Rev[5861]

* Wed Sep 11 2013 Luis Abarca <labarca@palosanto.com> 
- CHANGED: im - modules: The modules were relocated under the new scheme that
  differentiates administrator modules and end user modules .
  SVN Rev[5860]

* Mon May 27 2013 Luis Abarca <labarca@palosanto.com> 3.0.0-2
- CHANGED: im - Build/elastix-im.spec: Update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5027]

* Thu May 23 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: im: hardcode IP and port for openfire redirect instead of picking them
  from GET parameters. Pointed out by Fortify report.
  SVN Rev[5002]

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

* Thu Sep 20 2012 Luis Abarca <labarca@palosanto.com> 3.0.0-1
- CHANGED: im - Build/elastix-im.spec: Update specfile with latest
  SVN history. Changed version and release in specfile.
- CHANGED: In spec file changed Prereq elastix-framework to
  elastix-framework >= 3.0.0-1

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

