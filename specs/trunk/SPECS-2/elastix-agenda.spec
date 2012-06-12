%define modname agenda

Summary: Elastix Module Agenda 
Name:    elastix-%{modname}
Version: 2.2.0
Release: 6
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_%{version}-4.tgz
Source1: calendarEvent.gsm
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: asterisk
Prereq: freePBX >= 2.8.1-1
Prereq: elastix >= 2.2.0-15

%description
Elastix Module Agenda

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

# Copy required sound file
mkdir -p $RPM_BUILD_ROOT/var/lib/asterisk/sounds/custom/
#chown -R asterisk.asterisk /var/lib/asterisk/sounds/custom
cp %{SOURCE1} $RPM_BUILD_ROOT/var/lib/asterisk/sounds/custom/

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
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/var/lib/asterisk/sounds/custom
/var/lib/asterisk/sounds/custom/calendarEvent.gsm
/var/lib/asterisk/sounds/custom/*

%changelog
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
