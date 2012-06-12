%define modname conferenceroom

Summary: Elastix Conference Room 
Name:    elastix-conferenceroom
Version: 2.2.0
Release: 1
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires: unoconv >= 0.3, ImageMagick >= 6.4.0.10, openoffice.org-headless, openoffice.org-calc, openoffice.org-impress, openoffice.org-draw, openoffice.org-graphicfilter, openoffice.org-writer
Requires(post): /usr/bin/elastix-menumerge
Requires: elastix-pbx >= 2.2.0-6
Prereq: elastix-framework >= 2.2.0-18
Prereq: elastix-red5
Requires: SabreAMF
#Requires: javasqlite
BuildRequires: elastix-red5
BuildRequires: apache-ant-bin
BuildRequires: apache-ivy-bin
BuildRequires: flex-sdk-bin

%description
This module implements a conference room in which a conference host can display
a document for review by a number of guests that log on into a particular 
conference room. This module also provides a document repository for relevant 
documents. This module also enables limited video support via Red5 and a Flash
object embedded in the conference interface.

%prep
%setup -n %{modname}

%build

# Client-side flash support runs here
cd setup/flash-video/client
/opt/apache-ant/bin/ant -DFLEX_HOME=/opt/flex-sdk/

# Server-side flash support runs here
cd ../server
# LANG is required because rpmbuild sets LANG=C, which breaks javac for Spanish
# comments. We need to set it back to UTF-8
LANG=en_US.UTF-8 RED5_HOME=/opt/red5 /opt/apache-ant/bin/ant

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# Additional (module-specific) files that can be handled by RPM
mv setup/conference.php $RPM_BUILD_ROOT/var/www/html/
#mv setup/conferencechat.php $RPM_BUILD_ROOT/var/www/html/
mv setup/themes/ $RPM_BUILD_ROOT/var/www/html/
mv setup/flash-video/client/bin/release/elastix-conference-video.swf $RPM_BUILD_ROOT/var/www/html/modules/conferenceroom_presentation/
mv setup/flash-video/server/dist/elastixConferenceVideo.war setup/
rm -rf setup/flash-video/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

# FIXED BUG process update rpm, database conference was deleted.
mkdir -p    $RPM_BUILD_ROOT/var/www/db/
touch       $RPM_BUILD_ROOT/var/www/db/conferencia.db


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
    rm -rf /var/www/db/conferencia.db
    elastix-dbprocess "install" "$pathModule/setup/db"
elif [ $1 -eq 2 ]; then #update
    if [ "$preversion" = "0.0.0-12" ]; then
       if [ -f "/var/www/db/conferencia.db.rpmsave" ]; then
           echo "Restoring database conferencia.db"
           if [ -f "/var/www/db/conferencia.db" ]; then
               mv /var/www/db/conferencia.db /var/www/db/conferencia.db.bck
           fi
           mv /var/www/db/conferencia.db.rpmsave /var/www/db/conferencia.db
           chown asterisk.asterisk /var/www/db/conferencia.db
       fi
    fi
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi


# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
rm -rf /tmp/new_module

# Install Red5 server support
service elastix-red5 stop
rm -rf /opt/red5/webapps/elastixConferenceVideo/
cp /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/elastixConferenceVideo.war /opt/red5/webapps/
chown asterisk.asterisk /opt/red5/webapps/elastixConferenceVideo.war
service elastix-red5 start

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete WebConf menus"
  elastix-menuremove "conferenceroom"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
%config(noreplace) /var/www/db/conferencia.db

%changelog
* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-1
- CHANGED: In spec file change Prereq elastix to elastix-framework >= 2.2.0-18
- FIXED: fix changelog for already-committed Chinese translation.
- FIXED: Conference Room: Actually return FALSE on error in conferenceroom creation.

* Tue Sep 20 2011 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Merged fix for date range collision in conference phone assignment.
  Fixes Elastix bug #937. 
- CHANGED: Bump minimum version of elastix-pbx, as module requires a new function
  defined in elastix-pbx as part of fix for Elastix bug #937.

* Wed Jun  1 2011 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Bump minimum version of elastix, as module now requires newer framework
  support of paloSantoGrid.

* Sat May 14 2011 Alex Villacis Lasso <a_villacis@palosanto.com>
- ADDED: New Chinese translations for Conference Room interface. Part of fix for
  Elastix bug #876.

* Mon May  2 2011 Alex Villacis Lasso <a_villacis@palosanto.com> 2.0.0-1
- FIXED: Fix leftover hardcoding of local testing IP in flash client.

* Thu Apr 21 2011 Alex Villacis Lasso <a_villacis@palosanto.com> 2.0.0-0
- Include code for client and server video support

* Fri Apr 15 2011 Alex Villacis Lasso <a_villacis@palosanto.com>
- Bump release
- Add requirements for Red5 and SabreAMF for video support

* Fri Apr 08 2011 Bruno Macias <bmacias@palosanto.com> 0.0.0-13
- FIXED: Bug database conference.db, When process update database
   was deleted or rename as confenrece.db.rpmsave because now database 
   process is administration by elatix-dbprocess. Section config in this
   spec rename database by not exits file conference.db in main source.

* Wed Mar 30 2011 Eduardo Cueva <ecueva@palosanto.com> 0.0.0-12
- CHANGED: module elastix-conferenceroom, changed to the new 
  methodology for installing, updating and deleting databases by
  elastix-dbprocess. SVN Rev[542]
- CHANGED: file installer.php. Remove code where was administered 
  the database, now this process will be done by elastix-dbprocess.
  SVN Rev[541]

* Wed Mar 02 2011 Eduardo Cueva <ecueva@palosanto.com> 0.0.0-11
- database conferencia.db, added a field called user in the table
  conferencia
- module conferenceroom_list, now the user can only see and edit the
  conferences created by himself

* Fri Mar 19 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-10
- Section uninstall menu.

* Fri Mar 19 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-9
- Add check for optional POST parameter
- Replace "Conference Room" with "WebConf" in menu
- Update menu.xml to comply with menu ordering guidelines.

* Tue Jan 19 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-8
- Remove declarations of getParameter() from modules, as this is provided
  by the Elastix Framework now. Requires elastix-2.0.0-13 or higher.

* Tue Oct 20 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-7
- Fixed incorrect SQL check for web conferences which were currently active.

* Tue Oct 20 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-6
- Fixed botched update of conference.php in document root.

* Mon Oct 19 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-5
- Web conference rewritten with new look and removal of iframe.
- Integration with elastix-pbx telephone conference. Now requires elastix-pbx.

* Fri Sep 17 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-4
- Changed database structure to implement invited guests instead of anonymous login.
- New module "List Conferences" for administration of existing conferences.
- The "Create Conference" module is now folded within "List Conferences"
- Fixed Presentation and Document Repository appearing in main Elastix menu.
- Creation of conference may also create a parallel voice conference. Requires asterisk-addons.

* Wed Sep 02 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-3
- Implemented basic chat support in conference.
- Clean-up of conference template for Smarty.

* Tue Sep 01 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-2
- Forgot to copy menu.xml into installer folder.
- Fix several typos in temporary folder names.

* Thu Aug 20 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.0-1
- Initial version.
