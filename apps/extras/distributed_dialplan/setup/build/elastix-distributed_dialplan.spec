%define modname distributed_dialplan

Summary: Elastix Module Distributed Dial Plan
Name:    elastix-%{modname}
Version: 2.3.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_%{version}-1.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.3.0-9
Requires: php-PHPMailer

%description
Elastix Module Distributed Dial Plan

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p                        $RPM_BUILD_ROOT/var/www/html/
mkdir -p                        $RPM_BUILD_ROOT/var/www/html/elastixConnection
mv setup/elastixConnection      $RPM_BUILD_ROOT/var/www/html/
mv modules/                     $RPM_BUILD_ROOT/var/www/html/

# Additional (module-specific) files that can be handled by RPM
#mkdir -p $RPM_BUILD_ROOT/opt/elastix/
#mv setup/dialer

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi


%post
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"

# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

pathSQLiteDB="/var/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{modname}.info`

if [ $1 -eq 1 ]; then #install
  # The installer database
    elastix-dbprocess "install" "$pathModule/setup/db"
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete distributed dial plan menus"
  elastix-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%{_datadir}/elastix/module_installer/*
%{_localstatedir}/www/html/modules/*
%{_localstatedir}/www/html/elastixConnection/*

%changelog
* Wed Mar  9 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Peers Information: complete rewrite. This rewrite tries to encapsulate
  the required operations into a single library file instead of having logic
  spread across index.php. Error conditions are much better reported. Module
  code has been reorganized. Asterisk is no longer reloaded every time the
  peer report is shown, fixes Elastix bug #1654.
  SVN Rev[7514]

* Sun Apr 19 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- REMOVED: Peers Information - remove private copy of jQuery - framework has a
  newer version.
  SVN Rev[]

* Fri Feb 27 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Distributed Dialplan: fix include path for system PHPMailer.
  SVN Rev[6881]

* Tue Feb 24 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Distributed Dialplan: fix file packaging section so all files are owned
  by root, and package does not directly own /var/www/html/modules. Fixes Elastix
  bug #2018.
  SVN Rev[6872]

* Fri Jan 10 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Remote Servers, Server Key, Company Information: For each module listed here the english help file was renamed to en.hlp and a spanish help file called es.hlp was ADDED.
  SVN Rev[6368]

* Thu Oct 3 2013 Jose Briones <jbriones@elastix.com>
- UPDATED: Module peer_information. Translation support for a message was added
  SVN Rev[5969]

* Wed May 2 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-9
- CHANGED: modules - distributed_dialplan: Add jquery functions, error
  corrections,  and change of terms to Performance optimization of Distributed
  Dial Plan Module.
  SVN Rev[3912]
- ADDED: Setup - build: Added a folder for svn restructuration.
  SVN Rev[3861]
- CHANGED: module peers_information, now the module title is handled by the
  framework
  SVN Rev[3283]

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-2
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18
- CHANGED: module peers_information, now the module title is
  handled by the framework. SVN Rev[3283]
- CHANGED: module password_connection, now the module title is
  handled by the framework. SVN Rev[3282]
- CHANGED: module general_information, now the module title is
  handled by the framework. SVN Rev[3281]

* Wed Sep 28 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-1
- CHANGED: module peers_information, changed some labels
  in the module
  SVN Rev[3010]
- CHANGED: module password_connection, changed some labels
  in the module
  SVN Rev[3009]
- CHANGED: module general_information, changed label
  "locality" to "City"
  SVN Rev[3007]
- CHANGED: module peers_information, deleted field
  "mac address" in the grid
  SVN Rev[2989]

* Fri Jul 15 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED: In Spec file change prereq elastix >= 2.0.4-28
- FIXED: Modules - Distributed DialPlan:  Error trying to
  establish connection by bad response in request.php in
  response was "acept" and it must be "accept". SVN Rev[2834]
- CHANGED:  Modules - distributed_dialplan: In xml menu.xml add
  tag of permissions and the file installer.php was improved
  because the files db is administered by dbprocess. SVN Rev[2833]
- ADDED: Modules - extras: Module Distributed dialplan was add
  folder elastixconnection in setup/db/delete for elimination
  of database. SVN Rev[2832]
- NEW:  Modules - Distributed DialPlan: Support to sql files to
  install, update and delete operations on elastixconnection.db
  SVN Rev[2829]
- CHANGED: Modules - Distributed DialPlan: In Peers Information
  Module was applied some changes with improvements about status
  of connection of peers and modify the lang files. SVN Rev[2828]
- ADDED:   Modules - Distributed DialPlan: Add help files in
  Peers Information Module. SVN Rev[2828]
- CHANGED:  MODULES - Distributed Dialplan:  Add help files in
  password connection module. SVN Rev[2827]
- CHANGED:  Modules - Distributed DialPlan:  Add help files in
  modules General Information. SVN Rev[2826]
- FIXED:  EXTRAS - distributed_dialplan:
  File dundi_peers_custom_elastix.conf was created with errors
  as 'include'='priv'. SVN Rev[2735]
- CHANGED: The split function of these modules was replaced by
  the explode function due to that the split function was
  deprecated since PHP 5.3.0. SVN Rev[2650]
- CHANGED: Distributed Dialplan: remove stray text from protocol
  output. SVN Rev[2151]

* Tue Dec 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- NEW:  New module password_connection to generate secret keyword
  and send by email to the diferents admisntrators of other server
  for sharing dialplan. SVN Rev[2143]
- FIXED: Change modules peerInformation about distributed dialplan,
  the changes solve some security bugs. SVN Rev[2108]

* Mon Jun 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-3
- Initial version.
