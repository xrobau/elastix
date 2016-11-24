%define modname fax

Summary: Elastix Module Fax
Name:    elastix-%{modname}
Version: 4.0.0
Release: 3
License: GPL
Group:   Applications/System
#Source0: %{modname}_%{version}-5.tgz
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.3.0-2
Prereq: iaxmodem, hylafax
# ghostscript supplies eps2eps, ps2pdfwr, gs
Requires: ghostscript
# tiff2pdf supplied by libtiff (CentOS), libtiff-tools (Fedora)
Requires: /usr/bin/tiff2pdf
Requires: php-PHPMailer

%description
Elastix Module Fax

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# Files personalities for hylafax
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/privileged
mv setup/hylafax/bin/faxrcvd-elastix.php      $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mv setup/hylafax/bin/faxrcvd.php              $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mv setup/hylafax/bin/notify-elastix.php       $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mv setup/hylafax/bin/notify.php               $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mv setup/hylafax/bin/elastix-faxevent         $RPM_BUILD_ROOT/var/spool/hylafax/bin/
mv setup/hylafax/etc/FaxDictionary            $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mv setup/hylafax/etc/config                   $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mv setup/hylafax/etc/setup.cache              $RPM_BUILD_ROOT/var/spool/hylafax/etc/
mv setup/usr/share/elastix/privileged/*       $RPM_BUILD_ROOT/usr/share/elastix/privileged
rmdir setup/hylafax/bin setup/hylafax/etc/ setup/hylafax
rmdir setup/usr/share/elastix/privileged setup/usr/share/elastix setup/usr/share setup/usr

chmod    755 $RPM_BUILD_ROOT/var/spool/hylafax/bin/faxrcvd.php
chmod    755 $RPM_BUILD_ROOT/var/spool/hylafax/bin/faxrcvd-elastix.php
chmod    755 $RPM_BUILD_ROOT/var/spool/hylafax/bin/notify.php
chmod    755 $RPM_BUILD_ROOT/var/spool/hylafax/bin/notify-elastix.php

# move main library of FAX.
mkdir -p    $RPM_BUILD_ROOT/var/www/html/libs
mv setup/paloSantoFax.class.php               $RPM_BUILD_ROOT/var/www/html/libs/

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

# new for fax
mkdir -p $RPM_BUILD_ROOT/var/log/iaxmodem
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/bin
mkdir -p $RPM_BUILD_ROOT/var/spool/hylafax/etc
mkdir -p $RPM_BUILD_ROOT/var/www/faxes
mkdir -p $RPM_BUILD_ROOT/var/www/faxes/recvd
mkdir -p $RPM_BUILD_ROOT/var/www/faxes/sent

# ** Fax Visor additional config ** #
chmod 755 $RPM_BUILD_ROOT/var/www/faxes
chmod 775 $RPM_BUILD_ROOT/var/www/faxes/recvd $RPM_BUILD_ROOT/var/www/faxes/sent

%pre
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
# Habilito inicio automático de servicios necesarios
chkconfig --level 2345 hylafax on
chkconfig --level 2345 iaxmodem on

# Agrego Enlaces para Hylafax, ESTO AL PARECER LO HACE EL RPM DE HYLAFAX
ln -f -s pdf2fax.gs /var/spool/hylafax/bin/pdf2fax
ln -f -s ps2fax.gs  /var/spool/hylafax/bin/ps2fax

# Elimino archivos de fax que sobran
rm -f /etc/iaxmodem/iaxmodem-cfg.ttyIAX
rm -f /var/spool/hylafax/etc/config.ttyIAX

for i in `ls /var/spool/hylafax/etc/config.* 2>/dev/null`; do
  if [ "$i" != "/var/spool/hylafax/etc/config.sav" ]; then
    if [ "$i" != "/var/spool/hylafax/etc/config.devid" ]; then
      tilde=`echo $i | grep '~'`
      if [ "$?" -eq "1" ]; then
        if [ ! -L "$i" ]; then
          line="FaxRcvdCmd:              bin/faxrcvd.php"
          grep $line "$i" &>/dev/null
          res=$?
          if [ ! $res -eq 0 ]; then # no exists line
            echo "$line" >> $i
          fi
        fi
      fi
    fi
  fi
done

# Cambio de nombre de carpetas de faxes, esto es desde elastix 1.4
if [ -d "/var/www/html/faxes/recvq" ]; then
        mv /var/www/html/faxes/recvq/* /var/www/faxes/recvd
        rm -rf /var/www/html/faxes/recvq
fi

if [ -d "/var/www/html/faxes/sendq" ]; then
        mv /var/www/html/faxes/sendq/* /var/www/faxes/sent
        rm -rf /var/www/html/faxes/sendq
fi

if [ -d "/var/www/html/faxes" ]; then
        mv /var/www/html/faxes/* /var/www/faxes
fi

# Fix ownership and permission for sudo-less notification scripts
if [ $1 -eq 2 ]; then
	chmod 775 /var/www/faxes/recvd /var/www/faxes/sent
	chown asterisk.uucp /var/www/faxes/recvd /var/www/faxes/sent
fi

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

chmod 666 /var/www/db/fax.db

%clean
rm -rf $RPM_BUILD_ROOT

%preun
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Delete Fax menus"
  elastix-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/var/spool/hylafax/bin/*
/var/spool/hylafax/etc/setup.cache
%defattr(755, root, root)
/usr/share/elastix/privileged/*
%defattr(775, asterisk, uucp)
/var/www/faxes/recvd
/var/www/faxes/sent

%dir
/var/log/iaxmodem
%defattr(-, uucp, uucp)
%config(noreplace) /var/spool/hylafax/etc/FaxDictionary
%config(noreplace) /var/spool/hylafax/etc/config

%changelog
* Thu Nov 24 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Added Ukrainian, Russian translations.
  SVN Rev[7790]

* Fri Apr 22 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Fax Events: check whether /etc/localtime is a symlink and use it as
  an additional way to find out the current timezone.
  SVN Rev[7602]

* Wed Apr 13 2016 Luis Abarca <labarca@palosanto.com> 4.0.0-3
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Version and Release in specfile.

* Mon Nov  2 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Send Fax: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7308]
- CHANGED: Fax New: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7307]
- CHANGED: Fax Master: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7306]
- CHANGED: Fax List: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7305]
- CHANGED: Email Template: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7304]

* Tue Oct 27 2015 Luis Abarca <labarca@palosanto.com> 4.0.0-2
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Version and Release in specfile.

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Fax: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7238]

* Tue Sep 29 2015 Luis Abarca <labarca@palosanto.com> 4.0.0-1
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Version and Release in specfile.

* Mon Mar 02 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-2
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Version and Release in specfile.
  SVN Rev[6888]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: /apps/core/fax/setup/hylafax/bin changed route of PHPMailer library
  SVN Rev[6870]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- ADDED: /apps/core/fax/setup/built added PHPMailer library
  SVN Rev[6868]

* Mon Feb  2 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: New Virtual Fax: display error messages if a fax fails to be created
  or updated.
  SVN Rev[6832]
- FIXED: Virtual Fax List: display a more useful message when fax list in
  database gets out of sync with actual fax devices.
  SVN Rev[6831]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Version and Release in specfile.
  SVN Rev[6773]

* Tue Jan 14 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-4
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Release in specfile.
  SVN Rev[6379]

* Wed Jan 8 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Virtual Fax List, New Virtual Fax, Send Fax, Fax Queue, Fax Master,
  Fax Clients, Fax Viewer, Email Template: For each module listed here the english
  help file was renamed to en.hlp and a spanish help file called es.hlp was ADDED.
  SVN Rev[6344]

* Wed Aug 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-3
- CHANGED: Fax - Build/elastix-fax.spec: Update specfile with latest SVN
  history. Changed Release in specfile.
  SVN Rev[4838]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5624]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5623]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5622]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5621]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5620]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5619]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5618]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5617]

* Fri Aug 02 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction in a sql file to modify a registry in faxviewer.
  SVN Rev[5499]

* Fri Aug 02 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxviewer. Correction of some mistakes in the translation
  files.
  SVN Rev[5498]

* Wed Jul 31 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module sendfax. Correction of some mistakes in the translation
  files.
  SVN Rev[5470]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxviewer. Correction of some mistakes in the translation
  files.
  SVN Rev[5409]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxlist. Correction of some mistakes in the translation
  files.
  SVN Rev[5408]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxnew. Correction of some mistakes in the translation files.
  SVN Rev[5407]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxmaster. Correction of some mistakes in the translation
  files.
  SVN Rev[5406]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxlist. Correction of some mistakes in the translation
  files.
  SVN Rev[5405]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module faxclients. Correction of some mistakes in the translation
  files.
  SVN Rev[5404]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module  email_template. Correction of some mistakes in the
  translation files.
  SVN Rev[5398]

* Wed Jul 17 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Fax: The whole notification script was changed to english language.
  SVN Rev[5336]

* Mon Apr 15 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- CHANGED: Fax - Build/elastix-fax.spec: Changed Version and Release in
  specfile according to the current branch.
  SVN Rev[4838]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_template module, help section was updated.
  SVN Rev[4725]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxviewer module, help section was updated.
  SVN Rev[4723]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxclients module, help section was updated.
  SVN Rev[4722]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: master module, help section was updated.
  SVN Rev[4721]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxqueue module, help section was updated.
  SVN Rev[4720]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxqueue module, help section was updated.
  SVN Rev[4719]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: sendfax module, help section was updated.
  SVN Rev[4718]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxnew module, help section was updated.
  SVN Rev[4717]

* Wed Feb 20 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxlist module, help section was updated.
  SVN Rev[4716]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- CHANGED: Fax - Build/elastix-fax.spec: Changed Version and Release in
  specfile according to the current branch.

* Mon Nov 19 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-7
- CHANGED: Fax - Build/elastix-fax.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4442]

* Fri Nov 09 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Fax: pick up notification email from submitted fax job in addition to
  notification email for outgoing fax device. Fixes Elastix bug #1300.
  SVN Rev[4424]
- CHANGED: Fax Viewer: use LEFT JOIN instead of simple join for fax table. This
  prepares the report for sent notifications where fax device is 'any', as
  placed by WinHylafax.
  SVN Rev[4423]
- CHANGED: Fax: Add support in notification scripts for new placeholder
  {JOB_STATUS} which will be replaced with the final status of the fax job.
  Update default email notification to make use of {JOB STATUS}. Final part of
  fix for Elastix bug #1299.
  SVN Rev[4422]
- CHANGED: Fax Viewer: now that the notification scripts populate the status
  field in the fax database, we can show the information to the user. This
  provides a much-needed feedback on the final status of the fax job. Part of
  fix for Elastix bug #1299.
  SVN Rev[4421]
- FIXED: Send Fax: check whether text to send as fax is entirely ASCII, and
  attempt to convert to ISO-8859-15 if not, before converting to PostScript
  directly. Fixes Elastix bug #446.
  SVN Rev[4419]

* Wed Nov  7 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Send Fax: properly track status of submitted fax job by ID instead of
  assuming that an idle modem means the fax was sent successfully. Part of fix
  for Elastix bug #1299.
  SVN Rev[4416]

* Tue Nov  6 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Fax Queue: reimplement fax job listing on top of getFaxStatus
  SVN Rev[4415]
- CHANGED: Fax: rework getFaxStatus method to provide more information about the
  fax queue, in addition to the modems.
  SVN Rev[4414]
- ADDED: Fax: new module Fax Queue to monitor status of outgoing faxes that
  cannot be immediately dispatched.
  SVN Rev[4413]
- CHANGED: Send Fax: partial cleanup:
  Do not silently ignore failure to submit a fax job, and display error instead.
  Remove useless code that could potentially error out the module.
  Remove needless copy of temporary file followed by manual delete. Use the
  temporary uploaded file directly.
  Remove file type validation based on file extension. It is easy to beat, also
  prevents legitimate text files from being uploaded, and sendfax already has
  to figure out file type in order to apply conversion.
  SVN Rev[4412]
- CHANGED: Fax Queue: implement fax job cancelation. Fix regexp to show running
  jobs, not just stalled ones. Remove stray debugging messages.
  SVN Rev[4410]

* Mon Nov  5 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- ADDED: Fax: new module Fax Queue to monitor status of outgoing faxes that
  cannot be immediately dispatched.
  SVN Rev[4408]
- FIXED: Fax: fix regression in which mail notification placeholders were not
  replaced with the intended values.
  SVN Rev[4402]

* Fri Oct 26 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Fax Viewer: at check status routine, session variable might be
  invalid and trigger PHP warnings. Initialize local copy as array and copy
  session variable only after checking it is too an array.
  SVN Rev[4384]

* Mon Oct 22 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Fax: rewrite fax notification scripts. This achieves the following:
  Improved readability and documentation of the code.
  Unification of notification for sent and received faxes as a single method.
  Removal of unnecessary uses of external commands (grep, sqlite3).
  Use of SQL parameters in database manipulation.
  Removal of some cases where the invocation of an external command could fail
  silently and lead to a missing fax file.
  Reduction of code size, even after including documentation.
  Notification script now logs success/failure messages in preparation for GUI.
  Fixes Elastix bug #1387.
  SVN Rev[4379]

* Thu Oct 18 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Fax: extend faxconfig helper script to detect a systemd environment
  and adapt fax tty initialization to it. Required to set up virtual faxes
  correctly in Raspberry Pi.
  SVN Rev[4375]
- CHANGED: Fax: add Requires: ghostscript, /usr/bin/tiff2pdf to specfile. This
  fixes inability to display received fax in Fedora 17 for Raspberry Pi.
  SVN Rev[4369]

* Wed Oct 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
 - Framework,Modules: remove temporary file preversion_MODULE.info under
  /usr/share/elastix/module_installer/MODULE_VERSION/ which otherwise prevents
  proper cleanup of /usr/share/elastix/module_installer/MODULE_VERSION/ on RPM
  update. Part of the fix for Elastix bug #1398.
  - Framework,Modules: switch as many files and directories as possible under
  /var/www/html to root.root instead of asterisk.asterisk. Partial fix for
  Elastix bug #1399.
  - Framework,Modules: clean up specfiles by removing directories under
  /usr/share/elastix/module_installer/MODULE_VERSION/setup/ that wind up empty
  because all of their files get moved to other places.
  - Endpoint Configurator: install new configurator properly instead of leaving
  it at module_installer/MODULE/setup
  SVN Rev[4354]

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-6
- CHANGED: Fax - Build/elastix-fax.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4348]

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

* Wed Sep 12 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: faxconfig: change the encoding used to identify the
  virtual fax ttys written to /etc/inittab. This raises the maximum number of
  virtual faxes from 255 to 46655.
- CHANGED: faxconfig: update help text to mention new supported actions.
  SVN Rev[4198]

* Wed Jun 27 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-5
- CHANGED: Fax - Build/elastix-fax.spec: update specfile with latest
  SVN history. Changed release in specfile.

* Wed May 30 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Modules - Fax Viewer: relax unnecessarily-restrictive validation type
  on "Company Fax" filter option. Fixes Elastix bug #1281. SVN Rev[3946]
  SVN Rev[3946]

* Mon May 28 2012 German Macas <gmacas@palosanto.com> 2.3.0-4
- CHANGED: modules - sendfax: Add messages of sending fax process with ajax
  on Send Fax application form.
  SVN Rev[3937]

* Wed May 02 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-3
- FIXED: Modules - faxlist: Duplicate name column "Name Caller ID" was fixed.
  SVN Rev[3915]
- ADDED: Build - SPEC's: The spec files were added to the corresponding modules
  and the framework.
  SVN Rev[3851]
  SVN Rev[3833]

* Fri Mar 09 2012 Alberto Santos <asantos@palosanto.com> 2.3.0-2
- CHANGED: In spec file changed prereq elastix-framework >= 2.3.0-2
- FIXED: modules -FAX, se corrige bug que no se muestra lpos faxes
  a utiluzar en en modulo sendfax
  SVN Rev[3728]

* Wed Mar 07 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-1
- FIXED: modules - faxlist: Se corrige bug de pagineo en el modulo de faxlist.
  Tambien se definen correctamente ciertas traducciones.
  SVN Rev[3714]
- CHANGED: faxviewer index.php add control to applied filters
  SVN Rev[3701]
- FIXED: modules - faxmaster/index.php: The email hyphen is now allowed in Fax
  Master Configuration.
  SVN Rev[3679]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter when the language is spanish
  SVN Rev[3645]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter
  SVN Rev[3639]
- FIXED: modules - faxlist/index.php: Problems with the paged are now solved.
  SVN Rev[3628]

* Wed Feb 1 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-8
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-30
- FIXED: modules - faxlist/index.php: Problems with the
  paged are now solved. SVN Rev[3621].

* Fri Jan 27 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-7
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-28
- CHANGED: modules - images: icon image title was changed on
  some modules. SVN Rev[3572].
- CHANGED: modules - icons: Se cambio de algunos módulos los
  iconos que los representaba. SVN Rev[3563].
- CHANGED: modules - * : Cambios en ciertos mòdulos que usan grilla
  para mostrar ciertas opciones fuera del filtro, esto debido al
  diseño del nuevo filtro. SVN Rev[3549].
- UPDATED: modules - *.tpl: Se elimino en los archivos .tpl de ciertos
  módulos una tabla demás en su diseño del filtro que formaba parte
  de la grilla. SVN Rev[3541].

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-6
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18
- CHANGED: In spec file, fix ownership and permissions on
  recvd and sent directories for sudo-less fax notification scripts
- CHANGED: Fax: remove sudo chmod invocations from createFolder.
  With this, uucp no longer requires sudo privileges. For this
  to work, /var/www/faxes/recvd|sent directories need to have
  ownership 0775 asterisk.uucp
  SVN Rev[3376]
- CHANGED: Fax Viewer: fix typo in fax-not-found path when
  deleting faxes.
  SVN Rev[3370]
- FIXED: Send Fax: add -D switch to sendfax invocation to have
  desired behavior of notifying all sent faxes.
  SVN Rev[3369]
- FIXED: Send Fax: escape parameters to shell command for sendfax.
  SVN Rev[3368]
- FIXED: Fax: escape backslash that should be copied literally
  into config file as an escape in a configuration file.
  SVN Rev[3367]
- CHANGED: Fax Viewer: the two instances of fax deletion
  (web interface and SOAP call) need to delete the fax file
  along with the database information. Combine the two operations
  into a single method that also handles the transaction.
  This simplifies the fax API and the deletion logic, handles
  faxes by ID instead of document name, and allows to make the
  database connection object private. SVN Rev[3363]
- CHANGED: Fax Viewer: remove two functions that are no longer
  used. This removes yet another instance of sudo chown.
  SVN Rev[3361]
- CHANGED: Fax Viewer: complete rewrite
     Replace non-standard paging method via xajax with standard
        pagination using paloSantoGrid
     Replace delete operation via xajax with ordinary POST
     Handle file-not-found condition in fax document download
     Use application/pdf instead of application/octec-stream in
        fax document download
     Use _tr for internationalization instead of $arrLang
     Stop calling testFile, since this call no longer works due
        to access-denied on /var/spool/hylafax/docq/
  SVN Rev[3360]
- CHANGED: Fax Viewer: synchronize index.php as much as possible
  between 1.6 and trunk. SVN Rev[3358]
- FIXED: Fax Visor: use SQL query parameters in all database
  operations. SVN Rev[3357]
- FIXED: Fax Viewer (SOAP): handle requests with missing fields
  by assumming NULL. SVN Rev[3356]
- CHANGED: Fax Viewer: remove stray echo left over from debugging.
  SVN Rev[3355]
- CHANGED: Fax Master: make use of 'faxconfig' to set fax master
  email instead of sudo. SVN Rev[3354]
- CHANGED: Fax: add support in privileged helper for refreshing
  FaxMaster setting in /etc/postfix/virtual. SVN Rev[3352]
- CHANGED: Fax Clients: make use of 'faxconfig' to query/set fax
  clients instead of sudo chown. SVN Rev[3348]
- CHANGED: Fax: add support in privileged helper for querying and
  setting hosts allowed to send fax. SVN Rev[3345]
- CHANGED: Fax: make use of 'faxconfig' in fax library instead of
  sudo chown. SVN Rev[3340]

* Tue Nov 22 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-5
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-15
- CHANGED: Fax: use SQL query parameters in all database manipulation
  methods. SVN Rev[3338]
- CHANGED: Fax: removed dead code. SVN Rev[3338]
- FIXED: Modules - Send Fax: Fixed validation of type of files
  pdf, tiff, tif and txt when the name of files is in upper case.
  SVN Rev[3332]
- ADDED: Fax: Introduce 'faxconfig' privileged helper. SVN Rev[3331]
- CHANGED: module faxmaster, added the class "button" to the button
  SVN Rev[3325]
- CHANGED: module faxclients, changed the value and location
  of the button. SVN Rev[3324]
- CHANGED: module faxmaster, the asterisks and word "Required field"
  were removed. SVN Rev[3323]
- CHANGED: Fax: mark internal methods of paloFax class as private
  SVN Rev[3318]
- CHANGED: module faxviewer, changed style of table. SVN Rev[3218]

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-4
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-13

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-3
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-12
- CHANGED: module faxviewer, changed style of table.
  SVN Rev[3218][3187]
- UPDATED: fax modules  templates files support new elastixneo
  theme. SVN Rev[3148]
- UPDATED: sendfax  templates files support new elastixneo theme
  SVN Rev[3145]
- UPDATED: fax new  templates files support new elastixneo theme
  SVN Rev[3144]

* Tue Sep 27 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-2
- CHANGED: module email_template, eliminated asterisks in fields
  and the word "required field" in view mode
  SVN Rev[2996]

* Fri Sep 09 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-1
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-3
- CHANGED: module faxnew, in view mode the asterisks and word
  required were removed
  SVN Rev[2950]
- FIXED: modules - fax: Slow down on Hylafax because a chmod -R 777
  on a huge fax folder. For more details this bug:
  http://bugs.elastix.org/view.php?id=971
  SVN Rev[2944]

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-7
- CHANGED: Modules - Trunk: The ereg function was replaced by
  the preg_match function due to that the ereg function was
  deprecated since PHP 5.3.0. SVN Rev[2688]
- CHANGED: The split function of these modules was replaced by
  the explode function due to that the split function was
  deprecated since PHP 5.3.0. SVN Rev[2650]

* Tue Apr 26 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-6
- CHANGED: module faxviewer, changed class name to core_Fax
  SVN Rev[2578]
- CHANGED: module faxviewer, changed name from puntosF_Fax.class.php
  to core.class.php
  SVN Rev[2570]
- NEW: new scenarios for SOAP in faxviewer
  SVN Rev[2557]
- CHANGED: file db.info, changed installation_force to ignore_backup
  SVN Rev[2491]
- CHANGED: In Spec file, changed the prereq of elastix to 2.0.4-19

* Tue Mar 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-5
- CHANGED: Fax - setup hylafax:  Change the text of email
  notification from sending a Fax. SVN Rev[2459]
- CHANGED: module email_template, changed some information in
  the view according to the bug #744. SVN Rev[2430]
- CHANGED: module faxlist and faxnew, changed the word
  "destination email" to "associated email". SVN Rev[2412][2413]

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-4
- CHANGED:  In Spec file add prerequiste elastix 2.0.4-9

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-3
- CHANGED:   In Spec add lines to support install or update
  proccess by script.sql.
- DELETED:   Databases sqlite were removed to use the new format
  to sql script for administer process install, update and delete
  SVN Rev[2332]
- CHANGED: changed the db.info of fax to the format used in
  elastix-dbprocess. SVN Rev[2316]

* Thu Dec 30 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- FIXED: Framework/Fax: Commits 2088/2089 accidentally reverted
  commit 1697, thus reintroducing the
  unable-to-restart-webserver-after-configuring-fax bug.
  Add the fix again. SVN Rev[2192]

* Mon Dec 20 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- CHANGED:  Spec File has the actions post and install from elastix.spec
  about hylafax.
- CHANGED:  Change includes in files function.php
  (hylafax/bin/include) where the include has a lib phpmailer old,
  now this lib was in /var/www/html/libs. SVN Rev[2104]
- CHANGED: Module faxnew, Fixed Hard to see Bug  (H2C Bug), on
  paloSantoFax.class.php _deleteLinesFromInittab  MUST be called
  using $devId instead $idFax. Code Improvement,
  class paloSantoFax.class.php, a new function called  restartFax()
  was created. www.bugs.elastix.org [#607]. SVN Rev[2088]
- NEW:       additional paloSantoFax.class.php, better organization
  in Spec. SVN Rev[2082]
- CHANGED:   Change path to read or find pdf file sended or
  received in faxviewer, this cahneg was done due to path
  where fax files could be seen by url "http://IPSERVER/faxes"
  SVN Rev[2077]
- NEW:       New folder hylafax. SVN Rev[2074]

* Mon Dec 06 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-19
- CHANGED:   Add new Prereq in spec file about iaxmodem, hylafax
- CHANGED:   massive search and replace of HTML encodings with the
  actual characters. SVN Rev[2002]

* Mon Nov 15 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-18
- FIXED:     SendFax Module, label "Notification Sucessfull" does not
  exist in lang files. SVN Rev[1951]

* Fri Nov 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-17
- REMOVED: removed stray debug code that wrote to /tmp. SVN Rev[1909]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-16
- CHANGED:   Updated the Bulgarian language elastix. SVN Rev[1857]

* Mon Oct 18 2010 Eduardo Cueva <ecueva@palsoanto.com> 2.0.0-15
- CHANGED:   Updated fr.lang. SVN Rev[1825]
- NEW:       New lang file fa.lang (Persian). SVN Rev[1823]

* Mon Sep 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-14
- FIXED:     Corrected the message that say: Fax has been sended correctly, now says: Fax has been sent correctly. SVN Rev[1753], Bug[#518]

* Tue Sep 14 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-13
- CHANGED:   Valid types of extensions to upload files and show message for incorrect files or files sended. Rev[1735]

* Sat Aug 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-12
- CHANGED:   Change help files in send fax, fax viewer and email template. Rev[1679]
-            Show label (types of files supported) in send fax module

* Thu Jun 17 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-11
- Fixed bug where cannot edit a fax information. Link incorrect to faxvisor and not faxviewer

* Mon Mar 19 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-10
- Fixed bug, permission 666 to database fax.db

* Tue Mar 16 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-9
- Defined number order menu.

* Mon Mar 01 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-8
- Update release module.

* Tue Jan 19 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-7
- function getParameter removed in each module.

* Wed Dec 30 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-6
- Fixed bug name module, the name is sendfax and not send_fax.

* Tue Dec 29 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-5
- New module send fax.

* Fri Dec 04 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-4
- Increment released.

* Sat Oct 17 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-3
- Add accion uninstall rpm.
- Rename module faxvisor by faxview.
- Changed of words for a better definition of menus and messages.

* Mon Sep 07 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-2
- New structure menu.xml, add attributes link and order.

* Wed Aug 26 2009 Bruno Macias <bmacias@palosanto.com> 1.0.0-1
- Initial version.
