%define modname agenda

Summary: Elastix Module Agenda
Name:    elastix-%{modname}
Version: 2.5.0
Release: 3
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: asterisk
Prereq: freePBX >= 2.8.1-1
Prereq: elastix-framework >= 2.4.0-14
Requires: php-PHPMailer

# commands: mv
Requires: coreutils

# commands: festival
Requires: festival

%description
Elastix Module Agenda

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
#se crea el directorio address_book_images para contener imagenes de contactos
ls /var/www/address_book_images &>/dev/null
res=$?
if [ $res -ne 0 ]; then
    mkdir /var/www/address_book_images
    chown asterisk.asterisk /var/www/address_book_images
    chmod 755 /var/www/address_book_images
    echo "creando directorio /var/www/address_book_images"
fi

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
rm -f $pathModule/preversion_%{modname}.info

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
  echo "Delete Agenda menus"
  elastix-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*

%changelog
* Sat Dec  3 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Address Book: fixes for phone picker popup under Tenant theme.
  SVN Rev[7799]
- CHANGED: Address Book, Calendar: move implementation of phone picker popup
  window from Calendar to Address Book, where it belongs. This allows the
  picker to be implemented as a modification of the standard report. A redirect
  now exists in Calendar to handle modules (such as Graphic Report) that want
  the picker too and request it to Calendar.
  SVN Rev[7798]
- CHANGED: Address Book, Calendar: different representation of email contact
  search. The new representation more properly represents the operation as a
  query on external contacts instead of a special resource.
  SVN Rev[7797]
- CHANGED: Address Book, Calendar: move implementation of email contact search
  from Calendar Module to Address Book REST, since this requires an address
  book access.
  SVN REV[7796]

* Thu Nov 24 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Added Ukrainian translations. Fix Russian translations.
  SVN Rev[7786]

* Wed Sep 21 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Address Book: add more French translations for module, contributed by
  user danardf. Fixes Elastix bug #2592.
  SVN Rev[7748]

* Thu Dec 31 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Address Book: extend registration status report for list of all
  internal contacts. Tweak so that only one external command is required per
  tech.
  SVN Rev[7411]

* Tue Dec 29 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Address Book: report on registration status of internal contact if
  using SIP or IAX2 technology.
  SVN Rev[7410]

* Fri Nov  6 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: use 204 No Content instead of 205 Reset Content when saving
  event. This fixes the refresh after saving an existing contact.
  SVN Rev[7334]

* Sun Nov  1 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: update phone picker to use rewritten elastixneo theme.
  SVN Rev[7292]

* Sat Oct 31 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Address Book: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7290]

* Fri Oct 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: when invoked as a contact list helper for graphic_report,
  the event_id input tag is absent, but the js code assumed it was present. The
  REST rewrite now displays an error in this case. Fixed by checking for the
  presence of the tag before trying to use its value.
  SVN Rev[7283]

* Thu Oct 29 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: tweaks for tenant theme support by Edgar Landivar.
  SVN Rev[7272]

* Tue Oct 27 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: use /proc file instead of running hostname command.
  SVN Rev[7261]
- CHANGED: Agenda: explicitly spell out previously hidden package requirements
  that provide system commands.
  SVN Rev[7260]

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Agenda: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7233]

* Mon Oct 19 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- Added: Calendar: add Russian translation by user Russian.
  SVN Rev[7195]
- ADDED: Address Book: add Russian translation by user Russian.
  SVN Rev[7194]

* Thu Apr 09 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-3
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump version and release in specfile.

* Thu Apr 02 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: fix regexp that separates friendly name from email address.
  SVN Rev[6951]
- CHANGED: Calendar: remove extraneous explicit font sizing.
  SVN Rev[6950]

* Mon Mar 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: isolate datetime formatting function and make it check for
  the presence of Date.print before falling back to datetimepicker methods.
  SVN Rev[6937]

* Sat Mar 28 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Agenda - jQuery-1.11.2 migration - fix incorrect use of
  attribute instead of property.
  SVN Rev[6923]
- CHANGED: Agenda - jQuery-1.11.2 migration - empty strings are no longer
  considered a valid JSON response by jQuery. Return "null" instead.
  SVN Rev[6922]

* Mon Mar 02 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-2
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump version and release in specfile.
  SVN Rev[6888]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: /apps/core/agenda/modules/calendar/libs changed route of PHPMailer
  library
  SVN Rev[6869]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- ADDED: /apps/core/agenda/setup/built added PHPMailer library
  SVN Rev[6867]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump version and release in specfile.
  SVN Rev[6773]

* Thu Jul 29 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-14
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[6665]

* Fri Jul 04 2014 Sergio Broncano <sbroncano@palosanto.com>
- FIXED: Address book: Was fixed the bug #1928.
  SVN Rev[6662]

* Fri Jun 13 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: trunk - core/specs: Update specfile with latest SVN history. Bump
  Release in specfile.
  SVN Rev[6650]

* Fri May 02 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: languages modules were updated.
  SVN Rev[6619]

* Fri Apr 25 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: apps - Build/spec's: Commented some code that actually its not used.
  SVN Rev[6608]

* Wed Apr 09 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-13
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[6600]

* Wed Mar 26 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar - restore old behavior of allowing empty event descriptions.
  Previously this was allowed through the GUI but not through REST/SOAP.
  SVN Rev[6563]
- FIXED: Calendar - in PHP, ('true' == 0) evaluates to TRUE, so callfiles are
  never saved or created. Fixed.
  SVN Rev[6562]
- CHANGED: Calendar - reproduce old behavior of initializing the calendar with
  the server date, instead of using the default of the browser date.
  SVN Rev[6561]

* Tue Mar 25 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- DELETED: Calendar - remove calendarEvent.gsm. This file is no longer used in
  any scenario.
  SVN Rev[6558]
- FIXED: Calendar - do not create callfiles with timestamps in the past. Fixes
  Elastix bug #784.
  SVN Rev[6557]

* Mon Mar 24 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar - complete rewrite. The Calendar module has been rewritten,
  starting with the definition of classes paloSantoCalendar and
  paloSantoCalendarEvent as the single implementation of the Calendar code. The
  core.class.php file now directly delegates to this implementation instead of
  partially implementing functionality duplicated in the old index.php. The new
  core.class.php now has a method for updating an event, which is now exposed
  via SOAP and REST. The Calendar GUI has been rewritten to make exclusive use
  of REST to load and save calendar information. Also, the javascript
  implementation has been restructured to take full advantage of utilities
  provided by jQuery and jQueryUI. All of this adds up to remove almost all the
  implementation code from index.php, which now forwards requests not directly
  related to loading and updating the calendar.
  SVN Rev[6555]

* Sun Feb 16 2014 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: mark some library functions as private
  SVN Rev[6477]

* Tue Jan 14 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-12
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[6379]

* Wed Jan 8 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Calendar, Address Book: For each module listed here the english help
  file was renamed to en.hlp and a spanish help file called es.hlp was ADDED.
  SVN Rev[6340]

* Mon Oct 14 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Agenda: the agenda record field expansion renamed phone to work_phone,
  which breaks the SOAP API. Copy the field back to its old name to keep the
  SOAP compatibility. Fixes Elastix bug #1730.
  SVN Rev[6009]

* Fri Sep 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Agenda: when fetching the internal contact list, an empty recordset (as
  returned from a non-matching search) is a valid scenario and therefore not an
  error.
  SVN Rev[5880]

* Wed Aug 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-11
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5783]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- UPDATE: Correction of some mistakes in the translation file es.lang.
  SVN Rev[5733]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5609]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5608]

* Mon Aug 05 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-10
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5558]

* Thu Aug 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module address_book. Correction of some mistakes in the translation
  files.
  SVN Rev[5494]

* Thu Aug 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module calendar. Correction of some mistakes in the translation
  files.
  SVN Rev[5493]

* Thu Aug 01 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: The "Download iCal" option dissapeared because the switch to
  generic jQueryUI rendered one of its styles invisible. Fixed.
  SVN Rev[5492]

* Thu Jul 18 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-9
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5351]

* Thu Jul 18 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module address_book. Correction of some mistakes in the translation
  files.
  SVN Rev[5348]

* Thu Jul 18 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module calendar. Correction of some mistakes in the translation
  files.
  SVN Rev[5347]

* Thu Jul 18 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module calendar. Correction of some mistakes in the translation
  files.
  SVN Rev[5346]

* Wed Jul 17 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module address_book. Correction of some mistakes in the english
  translation file
  SVN Rev[5327]

* Mon Jul 15 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: FIXED: Calendar: remove *second* bogus compare of translated
  ajax response field to hardcoded untranslated string. Apparently the check
  serves no purpose, and breaks loading of event data in languages other than
  English.
- FIXED: Calendar: remove reference to uninitialized variable.
  SVN Rev[5312]

* Tue Jun 25 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: remove custom jQueryUI CSS theme. The calendar will now use
  whatever the Elastix Framework chooses as the default jQueryUI theme.
  SVN Rev[5130]

* Mon Jun 24 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-8
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5127]

* Mon Jun 24 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Address Book, Calendar: specify Context for AMI Originate instead of
  a blank field. Fixes Elastix bug #1605.
  SVN Rev[5120]

* Tue Jun 11 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-7
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5083]

* Mon Jun 10 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Address Book: hardcode base URL for REST service because PHP_SELF is
  insecure. Pointed out by Fortify report.
  SVN Rev[5077]
- CHANGED: Calendar: hardcode base URL for REST service because PHP_SELF is
  insecure. Pointed out by Fortify report.
  SVN Rev[5075]
- FIXED: Agenda: discard nonnumeric values of contact ID to prevent manipulation
  of subsequent redirect. Pointed out by Fortify report.
  SVN Rev[5074]

* Tue Jun 04 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Agenda: the output of 'core show channels concise' has changed from
  Asterisk 1.6 to 1.8 and later, and breaks parsing prior to call transfer.
  Fixed. Fixes part of Elastix bug #1570.
  SVN Rev[5053]

* Sun Jun 02 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: remove an useless sleep() call in the method to check whether
  the Festival TTS service is up.
  SVN Rev[5050]

* Thu May 23 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-6
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5009]

* Thu May 23 2013 Bruno Macias <bmacias@palosanto.com>
- FIXED: module address book, validation field picture on rest webservice was
  improved, because dont validation when this was empty
  SVN Rev[5007]

* Thu May 23 2013 Luis Abarca <labarca@palosanto.com>
- CHANGED: agenda - Build/elastix-agenda.spec: It was corrected in changelog a
  date corresponding to release 2.4.0-5
  SVN Rev[5005]

* Thu May 23 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-5
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Bump release in specfile.
  SVN Rev[5004]

* Wed May 22 2013 Bruno Macias <bmacias@palosanto.com>
- UPDATED: module address book, new fields on webservice-rest.
  SVN Rev[5000]

* Wed May 22 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Agenda: remove unnecessary and risky copy of uploaded file. Pointed out
  by Fortify report.
  SVN Rev[4998]

* Wed May 22 2013 Bruno Macias <bmacias@palosanto.com>
- ADDED: module address book, new fields for internal contacts, fields are im
  and department.
  SVN Rev[4996]

* Wed May 22 2013 Bruno Macias <bmacias@palosanto.com>
- UPDATED: module address book, webervice rest was updated for supported new
  fields.
  SVN Rev[4994]

* Fri May 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar: check that event ID is numeric before saving it. Pointed
  out by Fortify report.
  SVN Rev[4975]

* Tue May 14 2013 Bruno Macias <bmacias@palosanto.com> 2.4.0-4
- ADDED: Module address book, New feature, new fields were added to contacts.
  Now both internal and external contacts have the ability to have the same
  group of fields.
  Rest Web Services were updated.
  SVN Rev[4932]

* Mon May 13 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-3
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Changed release in specfile.

* Fri May 10 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: check that notification phone is numeric, and disallow
  newlines on TTS text. Fixes Elastix bug #1549.
  SVN Rev[4912]
- FIXED: Address Book: check that phone number is numeric on contacts CSV
  upload. Fixes Elastix bug #1548.
  SVN Rev[4910]

* Fri May 03 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Calendar: remove bogus compare of translated ajax response field to
  hardcoded untranslated string. Apparently the check serves no purpose, and
  breaks loading of event data in languages other than English.
  SVN Rev[4884]

* Wed Apr 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Agenda: fix broken user filtering in main listing of contacts that
  resulted in private contacts from other users being visible. Fixes Elastix
  bug #1529.
  SVN Rev[4847]

* Mon Apr 15 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4835]

* Wed Feb 27 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: calendar module, help section was updated.
  SVN Rev[4741]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: address_book module, help section was updated.
  SVN Rev[4706]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: calendar module, help section was updated.
  SVN Rev[4704]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- CHANGED: Agenda - Build/elastix-agenda.spec: Changed Version and Release in
  specfile according to the current branch.
  SVN Rev[4635]

* Mon Jan 28 2013 Luis Abarca <labarca@palosanto.com> 2.3.0-10
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4624]

* Wed Jan 23 2013 German Macas <gmacas@palosanto.com>
- FIXED: modules: calendar: Fixed CallerId in calendar event and resize of
  calendar
  SVN Rev[4611]

* Tue Jan 15 2013 Luis Abarca <labarca@palosanto.com>
- FIXED: Its no more necesary to resize the popups in certain windows of
  elastix environment. Fixes Elastix BUG #1445 - item 8
  SVN Rev[4587]

* Sat Jan 05 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Calendar (trivial): fix javascript warnings in IE6.
  SVN Rev[4550]

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-9
- CHANGED: Agenda - Build/elastix-agenda.spec: Changed release in specfile.
  SVN Rev[4373]

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

* Wed Jun 27 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-8
- CHANGED: Agenda - Build/elastix-agenda.spec: update specfile with latest
  SVN history. Changed release in specfile.

* Fri Jun 8 2012 Alberto Santos <asantos87@palosanto.com>
- CHANGED: Agenda - build/elastix-agenda.spec: Changed specfile, updated with
  the latest information.
  SVN Rev[3981]

* Fri Jun 8 2012 Alberto Santos <asantos87@palosanto.com>
- CHANGED: modules agenda, the daemon elastix-synchronizerd does not need root
  privileges. Changing to asterisk user privileges.
  SVN Rev[3976]

* Fri Jun 8 2012 Alberto Santos <asantos87@palosanto.com>
- ADDED: module agenda, added a new daemon called elastix-synchronizerd which
  handle the contacts and events synchronization.
  SVN Rev[3975]

* Thu Jun 7 2012 Alberto Santos <asantos87@palosanto.com>
- ADDED: module calendar, added new rest resources for events synchronization
  and data integrity verification.
  SVN Rev[3973]

* Thu Jun 7 2012 Alberto Santos <asantos87@palosanto.com>
- ADDED: modules address_book, added new rest resources for synchronitation
  and data verification integrity.
  SVN Rev[3972]

* Mon May 28 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-7
- FIXED: Module - Agenda/Calendar: Fixed bug 1266. In firefox and IE
  don't working action to add or edit event in the calendar. Now this
  bug have been resolved

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-6
- CHANGED: Agenda - Build/elastix-agenda.spec: Changed release in specfile
- ADDED: CHANGED: Agenda - themes/js: Changed javascript3.js to solve bug
  introduce in commit 3908. Didn't work color picker when was created a event.
  SVN Rev[3910]

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-5
- CHANGED: Agenda - Build/elastix-agenda.spec: Changed release in specfile
- ADDED: Calendar - themes/evento.tpl: Added file evento.tpl. File required
  for commit 3488. SVN Rev[3908]

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-4
- CHANGED: Agenda - Build/elastix-agenda.spec: Changed release in specfile

* Thu Apr 26 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: module calenadar, some functional points use a callback with a function
  called "_rechazar_correo_vacio" that was not implemented. Now that function is
  implemented in the class.
  SVN Rev[3890]
- CHANGED: Modules - Calendar: Changed the format of the popup that appear when
  a user want create, view or edit a event.
  SVN Rev[3844]

* Fri Mar 30 2012 Bruno Macias <bmacias@palosanto.com> 2.3.0-3
- CHANGED: In spec file, changed prereq elastix-framework >= 2.3.0-5
- FIXED: modules - SQLs DB: se quita SQL redundante de alter table y nuevos
  registros, esto causaba un error leve en la instalación de el/los modulos.
  SVN Rev[3797]

* Wed Mar 26 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-2
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-3
- CHANGED: Agenda - Calendar/index.php: Changed method to ask if festival if
  running
  SVN Rev[3781]
- CHANGED: Agenda - Calendar/libs/paloSantoCalendar: Changed method to ask if
  festival if running
  SVN Rev[3780]
- CHANGED: Agenda - Calendar/libs/paloSantoCalendar: Added a delay before
  asking is festuval is runnig, It is necesary to show message is festival is
  activate or inactivate
  SVN Rev[3778]
- FIXED: Agenda - Calendar/form.tpl: little change to don't permit interactive
  with the options in one event before give click in edit
  SVN Rev[3775]
- FIXED: Agenda - Calendar - jquery3.javascript: Fixed issue that when a user
  give click on delete button in one event and later in cancel in the pop up
  the event delete any way. Now the action is canceled
  SVN Rev[3774]
- CHANGED: modules - calendar/libs/paloSantoCalendar.class.php: A new function
  getDescUsers was created to capture the description of the users.
  SVN Rev[3759]
- CHANGED: modules - calendar/index.php: The format of the sender field as well
  as the Organizer field, now shown as follows: Real Name (User Name).
  SVN Rev[3758]
- FIXED: modules - calendar/themes/default/js/jquery3.javascript.js: The
  Description's textbox now appears when you click on 'Create new event'.
  SVN Rev[3748]
- NEW: module calendar, added a new resource called "CalendarEvent" for rest
  web services
  SVN Rev[3747]
- CHANGED: module calendar, some functions of core.class.php were adpted for
  compatibility with Elastix rest web services
  SVN Rev[3746]
- NEW: module address_book, added a new resource called "ContactList" for rest
  web services
  SVN Rev[3745]
- CHANGED: module address_book, changed some functions in class core.class.php
  to adapt it with rest web services
  SVN Rev[3744]

* Wed Mar 07 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-1
- CHANGED: address_book index.php add control to applied filters
  SVN Rev[3696]
- CHANGED: calendar index.php add control to applied filter when show phone
  numbers
  SVN Rev[3695]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter when the language is spanish
  SVN Rev[3645]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter
  SVN Rev[3639]
- FIXED: Modules - calendar: if you click on delete button on the pop-up window
  when you can edit a event the event will delete without any confirmation.
  * Bug:0001162
  * Introduced by:
  * Since: Development Monitoring Calendar
  SVN Rev[3634]

* Wed Feb 1 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-12
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-30
- CHANGED: file index.php to fixed the problem with the paged.
  SVN Rev[3611].

* Mon Jan 30 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-11
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-29
- FIXED: Modules - Calendar: A query was doubly parameterized.
  SVN Rev[3596].
- CHANGED: modules - address_book/index.php Little chande in
  message that appear when the current user dont have a extension
  number associated.SVN Rev[3593].


* Sat Jan 28 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-10
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-28
- CHANGED: modules address_book, in function listAddressBook,
  the generation of url is not longer necessary because this
  is now done by the script restful.php. SVN Rev[3579].
- CHANGED: Modules - Calendar: Se revertió los cambios en
  paloSantoCalendar.class.php que se hicieron en el
  commit [3574]. SVN Rev[3547].
- FIXED: Modules - Calendar: Fixed bug, the search filter
  'Phone Directory' doesn't work in Pop Up of Address Book.
  M    calendar/themes/default/filter_adress_book.tpl
  M    calendar/libs/paloSantoCalendar.class.php
  M    calendar/index.php. SVN Rev[3574].
- CHANGED: modules - images: icon image title was changed
  on some modules. SVN Rev[3572].
- FIXED: modules address_book, changed the field_pattern
  from "'%%'" to "%%" in function listAddressBook becuase
  now the address_book querys are parameterized and the
  single quotes are not necessary. SVN Rev[3566].- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-28- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-28- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-28
- CHANGED: modules address_book, added a functional point
  to update a contact. SVN Rev[3564]
- CHANGED: modules - icons: Se cambio de algunos módulos
  los iconos que los representaba. SVN Rev [3563]
- FIXED: modules address_book, all the querys were
  parameterized. SVN Rev[3558].
- NEW: modules - address_book: Se agrega nueva imagen para
  el icono del módulo. SVN Rev[3551].
- UPDATED: modules - popup-grid: Se incluye hoja de estilo
  table.css para la presentación nueva de las
  tablas-grillas. SVN Rev[3550].
- CHANGED: modules - * : Cambios en ciertos mòdulos que
  usan grilla para mostrar ciertas opciones fuera del
  filtro, esto debido al diseño del nuevo filtro. SVN Rev
  [3549].


* Tue Jan 17 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-9
- CHANGED: In spec file changed Prereq elastix to elastix-framework >= 2.2.0-26
- CHANGED: Modules - Agenda/Calendar: Changes in javascript to better the message of pop-up. This changes
  require the commit SVN Rev[3514]. SVN[3517].

* Tue Dec 20 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-8
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-23
- FIXED: Calendar: fix invalid javascript syntax for object
  literal in fullCalendar declaration. SVN Rev[3455]

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-7
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18

* Tue Nov 22 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-6
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-15
- REMOVED: Modules - Calendar: Removed jquery01.blockUI.js
  and moved to the framework. SVN Rev[3337]
- REMOVED: Modules - Agenda: Removed files style4.colorpicker.css
  and jquery02.colorpicker.js in calendar modules because this
  libs are in framework. SVN Rev[3336]

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-5
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-13

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-4
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-12
- CHANGED: Modules - Calendar: Added css property border-radius
  in calendar. SVN Rev[3225]
- UPDATED: fax new  templates files support new elastixneo theme
  SVN Rev[3144]
- UPDATED: address book templates files support new elastixneo
  theme. SVN Rev[3140]
- UPDATED: calendar templates files support new elastixneo theme
  SVN Rev[3134]

* Fri Oct 07 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-3
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-8
- FIXED: module address_book, added an id of "filter_value" to
  the filter text box, also the event onkeypress was removed
  from this text box
  SVN Rev[3034]

* Tue Sep 27 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-2
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-5
- CHANGED: changed the password "elastix456" of AMI to the
  password set in /etc/elastix.conf
  SVN Rev[2995]

* Fri Sep 09 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-1
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-3
- FIXED: Agenda - Calendar: Fixed bug where events were showed in
  dashboard module but the links to access the events were wrong,
  so, this bug was solved adding support to view events by load
  javascript with the popup when in url (by get) have a variable
  id and the date of event to change the calendar.
  SVN Rev[2955]
- CHANGED: module recordings, changed the location of module
  recordings, now it is in PBX->tools
  SVN Rev[2953]
- CHANGED: module address_book, in view mode the asterisks and
  word required were removed
  SVN Rev[2947]
- FIXED: Agenda - Calendar: Fixed bug where calendar popup appear
  with style "position:fixed" and users cannot see the opcions of
  "Notify Guests by Email" if "Configure a phone call reminder" is opened
  SVN Rev [2935]


* Fri Jul 29 2011 Eduardo Cueva <ecueva@palsoanto.com> 2.0.4-12
- CHANGED: Agenda - Calendar:  Show message after to create an
  event because there are a load page as event and in a remote
  test there is not a feedback to the user. SVN Rev[2852]

* Tue Jul 28 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-11
- FIXED: Modules - Calendar: Fixed problem with text to speach
  by characters like ",". SVN Rev[2839][2836]
- CHANGED: module address_book, when the user does not have an
  extension associated, a link appear to assign one extension.
  SVN Rev[2796]
- CHANGED: module recordings, when the user does not have an
  extension associated, a link appear to assign one extension.
  SVN Rev[2792]

* Wed Jun 29 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-10
- CHANGED: module recordings, changed informative message according
  to bug #906. SVN Rev[2759]
- CHANGED: module address_book, changed informative message according
  to bug #906. SVN Rev[2758]
- CHANGED: module calendar, changed informative message according
  to bug #903. SVN Rev[2757]

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-9
- CHANGED: In spec file change prereq freepbx >= 2.8.1-1 and
  elastix >= 2.0.4-24
- CHANGED: Agenda/Recordings: replace direct use of paloConfig on
  /etc/amportal.conf with call to generarDSNSistema(). SVN Rev[2676]
- CHANGED: Agenda/AddressBook: replace direct use of paloConfig
  on /etc/amportal.conf with call to generarDSNSistema(). The object
  is still used to get access to the AMI credentials. SVN Rev[2673]
- CHANGED: Agenda/Calendar: replace direct use of paloConfig on
  /etc/amportal.conf with call to generarDSNSistema(). SVN Rev[2670]
- CHANGED: The split function of these modules was replaced by the
  explode function due to that the split function was deprecated since
  PHP 5.3.0. SVN Rev[2650]

* Tue Apr 26 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-8
- FIXED: Agenda - calendar: Fixed bug where appear a div at the bottom
  of the big calendar
  SVN Rev[2584]
- CHANGED: module calendar, changed class name to core_Calendar
  SVN Rev[2576]
- CHANGED: module address_book, changed class name to core_AddressBook
  SVN Rev[2575]
- CHANGED: module calendar, changed name from puntosF_Calendar.class.php
  to core.class.php
  SVN Rev[2568]
- CHANGED: module address_book, changed the name from
  puntosF_AddressBook.class.php to core.class.php
  SVN Rev[2567]
- NEW: new scenarios for SOAP in address_book and calendar
  SVN Rev[2556]
- FIXED: agenda - calendar: Fixed functionality of TTS
  SVN Rev[2554]
- CHANGED: module address_book, new grid field called picture
  SVN Rev[2540]
- CHANGED: file db.info, changed installation_force to ignore_backup
  SVN Rev[2489]
- CHANGED: Agenda - calendar : Clean the textarea when do an action
  to create a new event because this field was never clean it
  SVN Rev[2470]
- CHANGED: elastix-agenda.spec, changed prereq elastix to 2.0.4-19

* Tue Mar 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-7
- CHANGED:  Agenda - calendar: Change the style.css in calendar
  where buttons to change the view fo calendar (Month, week, day)
  don't appear the border right of each button, this only occur
  with the buttons "month" and "week". For see more information
  check the ticket "http://bugs.elastix.org/view.php?id=739"
  SVN Rev[2456]
- CHANGED:  Agenda - Calendar:  Changes in styles and to
  attach ical file, now the function AddStringAttachment from
  PHPMAILER attach the file icals as part of html. SVN Rev[2405]
- CHANGED:  agenda - calendar:
          - clear the code
          - remove the action loading to show the form
            to create a new windows.
          - Add 5 minutes more by defaul in end date
          - Add lib gcal.js to get google
            calendar(non-functional for now)
  SVN Rev[2399]

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-6
- CHANGED:  In Spec file add prerequiste elastix 2.0.4-9

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-5
- CHANGED:   In Spec add lines to support install or update
  proccess by script.sql.
- DELETED:   Databases sqlite were removed to use the new
  format to sql script for administer process install, update
  and delete. SVN Rev[2332]
- ADD:  addons, agenda, reports. Add folders to contain sql
  scrips to update, install or delete. SVN Rev[2321]

* Thu Feb 03 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-4
- CHANGED:  menu.xml to support new tag "permissions" where has
  all permissions of group per module and new attribute "desc"
  into tag  "group" for add a description of group.
  SVN Rev[2294][2299]
- CHANGED:  Agenda - Address_book: change icons and add text to
  know when a contact is private, public or public and not
  editable. SVN Rev[2265]
- CHANGED:  All calendar in module calendar start on Monday
  before date in events start on Monday but the others calendars
  start on Sunday. Task[478] SVN Rev[2230]

* Thu Dec 30 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-3
- FIXED:  Fixed bug in recording where any file can be uploaded
  for security must be wav, gsm and wav49. SVN Rev[2188]

* Wed Dec 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED:  Hide image loading gif in calendar when you create
  a new event or view one it appear top of box. SVN Rev[2181]

* Wed Dec 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- FIXED:  Fixed bug in installer.php of agenda when if not exist
  field column "color" in calendar.db this file is created but
  in console print error "SQL error: near "#3366": syntax error"
  SVN Rev[2171]

* Mon Dec 20 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-26
- CHANGED:  Change path reference about phpMailer.lib to send mails
  SVN Rev[2100]
- CHANGED:  changes applied for support calendar with color by
  calendars's events. [#411] SVN Rev[2091]
* Mon Dec 06 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-25
- ADD:     New Prereq asterisk and freePbx in spec file.
- CHANGED: massive search and replace of HTML encodings with the actual
  characters. SVN Rev[2002]
- FIXED:   Calendar: Fix failure to remove call files. Previous commits
  replaced a system() call with an unlink() but did not take into
  account that a shell glob was being relied upon. SVN Rev[2000]
- FIXED:   Calendar: Actually send an email when deleting an event with
  e-mail notifications. SVN Rev[2000]
- CHANGED: Address Book: stop assigning template variable "url" directly,
  and remove nested <form> tags. SVN Rev[1997]
- FIXED:   Calendar: Allow browsing of public contacts from external
  phonebook.SVN Rev[1995]
	   Calendar: stop assigning template variable "url" directly,
  and remove nested <form> tag. SVN Rev[1994]

* Fri Nov 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-24
- FIXED:  Fixed some bug about calendar module, and some function was
  improved like show list email in notification emails and so on.
  SVN Rev[1945]
- ADDED:  New javascript to show a box with a legend say "loading data"
  the lib is jquery01.blockUI.js in calendar. SVN Rev[1945]
- FIXED: revert htmlspecialchars() escaping when displaying full external
  contact information. The paloForm::fetchForm method does this already
  since commit 1911, so the data gets doubly-escaped. Assumes commit 1911
  is already applied in system. It is in Address_book. SVN Rev[1912]

* Fri Oct 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-23
- FIXED: Fixed to show script in address_book of calendar, this was solved
  using htmlspecialchars function in PHP. SVN Rev[1878]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-22
- CHANGED:  New Parameters en Calendar "reminderTimer" it is used to create
  .call files to reminder 10, 30 or 60 minutes before to start the event
  SVN Rev[1858]
- FIXED:    Fixed some bug about the information of events per user, where
  other user can be view, edit or delete the event only knowing the id_event
  SVN Rev[1858]
- FIXED:    In address_book the view of report in external contact was escaped
  for html using htmlspecialchars function, it is for avoid security bugs
  SVN Rev[1858]
- CHANGED:  Add changes to add new field reminderTimer in installer.php(agenda)
  SVN Rev[1858]
- CHANGED:  Updated the Bulgarian language. SVN Rev[1857].
- CHANGED:  News changes in address_book, support to add pictures of contacts
  and user can see others contacs if they are public. [#346]. SVN Rev[1852]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-21
- CHANGED: Create a new directory address_book_images to content images of all
  contacts in address_book.

* Mon Oct 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-20
- FIXED:   function getIdUser was removed of paloAdressBook.class.php because
  already exists in paloSantoACL. SVN Rev[1848]
- FIXED:   Fixed security bug in recording module, where was possible execute
  commands in the text field of recording name because it use  function exec in php
  to move the files with the text in text field. [#553] SVN Rev[1835]
  Lost recording after be recorded, it happened because the tmp path was wrong and
  the correct path is /var/spool/asterisk/tmp SVN Rev[1835]
- FIXED:   Put option rawmode=yes in queryString in download audio file and set path
  file destination in the header response. [#552] SVN Rev[1834]
- ADDED:   Add es.lang SVN Rev[1834]
- CHANGED: Updated fa.lang. SVN Rev[1825]

* Tue Oct 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-19
- ADDED:   New fa.lang file (Persian). SVN Rev[1793]

* Fri Aug 20 2010 Eduardo Cueva D. <ecueva@palosanto.com> 2.0.0-18
- CHANGED: Change label "here" to "Aqui" in en.lang (calendar modules). Rev[1717]
- CHANGED: Change some validation of dates and validation about Reminder configure call and notify by email. Rev[1716]
- FIXED:   Bug fixed in calendar modules where all events were showed for all users. Rev[1714]

* Wed Aug 18 2010 Eduardo Cueva D. <ecueva@palosanto.com> 2.0.0-17
- CHANGED:    Change calendar module, translate en.lang and fix some logic in mini calendar and fullcalendar. Rev[1709]

* Tue Aug 17 2010 Eduardo Cueva D. <ecueva@palosanto.com> 2.0.0-16
- CHANGED:    Change in calendar module when no exist a recording in the action new event, the field Email to notify appear although section Notify Guest by email was inactive. Rev[1698]
- CHANGED:    Module calendar was improved. interaction mini calendar with nig calendar. Rev[1707]

* Thu Aug 12 2010 Eduardo Cueva D. <ecueva@palosanto.com> 2.0.0-15
- CHANGED: Change the help file in calendar module. Rev[1693]
- CHANGED: Modulo calendar was improved in styles and javascripts. Rev[1691]

* Sat Aug 07 2010 Eduardo Cueva D. <ecueva@palosanto.com> 2.0.0-14
- CHANGED:  Change the help of calendar module.

* Thu Jul 29 2010 Alex Villacis Lasso <a_villacis.palosanto.com> 2.0.0-13
- FIXED: Not only /var/lib/asterisk/sounds/custom/calendarEvent.gsm must
  be listed in files section, /var/lib/asterisk/sounds/custom/ must be
  listed too. Required for asterisk.asterisk ownership to be extended to
  directory.

* Wed Jul 14 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-12
- NEW: Address book support blind tranfer.

* Mon Jun 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-11
- CHANGED: Calendar_ Fold functionality for phone number display into main index.php, delete phone_numbers.php, and adjust template accordingly. This places phone number display under ACL control.

* Mon Jun 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-10
- CHANGED: The file cvs to download in address book is generate in the same index.php and do not call a external file download_csv.php.

* Thu Apr 15 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-9
- Create file call for make to call agended.
- Be Improved the look the message  email event.
- Implementation of protocol for make call.

* Tue Mar 16 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-8
- Defined number order menu.
- Fixed minor bug in module calendar.
- Download ical file from calendar.

* Mon Mar 01 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-7
- Re-write code module calendar, now module support jquery. Version module beta1.

* Tue Jan 19 2010 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-6
- Function getParameter removed in module agenda.

* Fri Jan 08 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 2.0.0-5
- Add calendarEvent.gsm as tracked file, no changes in code.

* Fri Dec 04 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-4
- Increment release.

* Mon Oct 19 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-3
- Add accion uninstall rpm.

* Mon Sep 07 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-2
- New structure menu.xml, add attributes link and order.

* Wed Aug 26 2009 Bruno Macias <bmacias@palosanto.com> 1.0.0-1
- Initial version.
