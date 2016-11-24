%define modname email_admin

Summary: Elastix Module Email
Name:    elastix-%{modname}
Version: 2.5.0
Release: 6
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
#Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix-framework >= 2.5.0-16
Prereq: RoundCubeMail
Prereq: php-imap
Prereq: postfix, spamassassin, cyrus-imapd
Requires: mailman >= 2.1.9
Requires: php-jpgraph
Requires: php-PHPMailer

# sieveshell requires at least plain authentication method
Requires: cyrus-sasl-plain

Requires: /usr/sbin/saslpasswd2

Prereq: /usr/sbin/saslauthd

%description
Elastix Module Email

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# ** /etc path ** #
mkdir -p    $RPM_BUILD_ROOT/etc/postfix
mkdir -p    $RPM_BUILD_ROOT/usr/local/bin/
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mkdir -p    $RPM_BUILD_ROOT/var/www/html/libs/
mkdir -p    $RPM_BUILD_ROOT/etc/cron.d/
mkdir -p    $RPM_BUILD_ROOT/usr/local/elastix/
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/privileged
mkdir -p    $RPM_BUILD_ROOT/var/www/

# ** libs ** #
mv setup/paloSantoEmail.class.php        $RPM_BUILD_ROOT/var/www/html/libs/
mv setup/cyradm.php                      $RPM_BUILD_ROOT/var/www/html/libs/
mv setup/checkSpamFolder.php             $RPM_BUILD_ROOT/var/www/
mv setup/deleteSpam.php                  $RPM_BUILD_ROOT/var/www/
mv setup/disable_vacations.php           $RPM_BUILD_ROOT/var/www/
mv setup/stats/postfix_stats.cron        $RPM_BUILD_ROOT/etc/cron.d/
mv setup/stats/postfix_stats.php         $RPM_BUILD_ROOT/usr/local/elastix/
mv setup/usr/share/elastix/privileged/*  $RPM_BUILD_ROOT/usr/share/elastix/privileged
rmdir setup/stats

# ** dando los permisos a los archivos que usara postfix stats
chmod 644 $RPM_BUILD_ROOT/usr/local/elastix/postfix_stats.php

# ** dando permisos de ejecucion ** #
chmod +x $RPM_BUILD_ROOT/var/www/checkSpamFolder.php
chmod +x $RPM_BUILD_ROOT/var/www/deleteSpam.php
chmod +x $RPM_BUILD_ROOT/var/www/disable_vacations.php
chmod +x $RPM_BUILD_ROOT/usr/share/elastix/privileged/*

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mv modules/ $RPM_BUILD_ROOT/var/www/html/

# Additional (module-specific) files that can be handled by RPM
#mkdir -p $RPM_BUILD_ROOT/opt/elastix/
#mv setup/dialer

# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.

# ** postfix config ** #
mv setup/etc/postfix/virtual.db               $RPM_BUILD_ROOT/usr/share/elastix/

# Remplazo archivos de Postfix y Cyrus
mv setup/etc/imapd.conf.elastix               $RPM_BUILD_ROOT/etc/
mv setup/etc/postfix/main.cf.elastix          $RPM_BUILD_ROOT/etc/postfix/
mv setup/etc/cyrus.conf.elastix               $RPM_BUILD_ROOT/etc/

# ** /usr/local/ files ** #
mv setup/usr/local/bin/spamfilter.sh          $RPM_BUILD_ROOT/usr/local/bin/

rmdir setup/etc/postfix setup/etc
rmdir setup/usr/share/elastix/privileged setup/usr/share/elastix setup/usr/share
rmdir setup/usr/local/bin setup/usr/local setup/usr

mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
# ****Agregar el usuario cyrus con el comando saslpasswd2:
#echo "palosanto" | /usr/sbin/saslpasswd2 -c cyrus -u example.com

mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{modname}.info
fi

%post
# Habilito inicio automático de servicios necesarios
chkconfig --level 345 saslauthd on
chkconfig --level 345 cyrus-imapd on
chkconfig --level 345 postfix on

# Cambiar permisos del archivo /etc/sasldb2 a 644
#chmod 644 /etc/sasldb2


# Creo el archivo /etc/postfix/network_table if not exixts
if [ ! -f "/etc/postfix/network_table" ]; then
    touch /etc/postfix/network_table
    echo "127.0.0.1/32" >  /etc/postfix/network_table
fi

# Verifo si existe virtual.db para previa installation
if [ ! -f /etc/postfix/virtual.db ]; then
   mv /usr/share/elastix/virtual.db /etc/postfix/virtual.db
   chown root:root /etc/postfix/virtual.db
else
   rm -f /usr/share/elastix/virtual.db
fi

# TODO: TAREA DE POST-INSTALACIÓN
# Cambio archivos de Postfix e Imapd con los de Elastix
# Only replace main.cf on install  and user spamfilter create
if [ $1 -eq 1 ]; then
    mv /etc/imapd.conf /etc/imapd.conf.orig
    cp /etc/imapd.conf.elastix /etc/imapd.conf

    mv /etc/postfix/main.cf  /etc/postfix/main.cf.orig
    cp /etc/postfix/main.cf.elastix /etc/postfix/main.cf

    mv /etc/cyrus.conf /etc/cyrus.conf.orig
    cp /etc/cyrus.conf.elastix /etc/cyrus.conf

    # Create the user spamfilter
    /usr/sbin/useradd spamfilter
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

# add string localhost first in /etc/hosts
if [ -f /etc/hosts  ] ; then
   sed -ie '/127.0.0.1/s/[\t| ]localhost[^\.]/ /g'  /etc/hosts  # busca el patron 127.0.0.1 y reemplaza [\t| ]localhost[^\.] por " "
   sed -ie 's/127.0.0.1/127.0.0.1\tlocalhost/'  /etc/hosts      # reemplaza 127.0.0.1 por 127.0.0.1\tlocalhost
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
  echo "Delete Email menus"
  elastix-menuremove "%{modname}"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%files
%defattr(-, root, root)
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/usr/local/bin/spamfilter.sh
/etc/imapd.conf.elastix
/etc/postfix/main.cf.elastix
/etc/cyrus.conf.elastix
/usr/share/elastix/virtual.db
/var/www/checkSpamFolder.php
/var/www/deleteSpam.php
/usr/local/elastix/postfix_stats.php
/var/www/disable_vacations.php
%defattr(644, root, root)
/etc/cron.d/postfix_stats.cron
%defattr(755, root, root)
/usr/share/elastix/privileged/*

%changelog
* Thu Nov 24 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Added Ukrainian, Russian translations.
  SVN Rev[7787]

* Mon Aug 22 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-6
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Thu Aug 18 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Email Accounts: unconditionally allow mailbox reconstruction. It does
  not make much sense for an user who is capable of creating and removing
  accounts to be denied mailbox reconstruction if not an admin.
  SVN Rev[7724]

* Wed Aug 17 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Vacations: update menu.xml with custom privilege setanyemail.
  SVN Rev[7723]
- CHANGED: Vacations: use hasModulePrivilege(setanyemail) to test whether user
  is allowed to change vacation status for any account. Update required version
  of framework to match. Part of fix for Elastix bug #1100.
  SVN Rev[7722]
- FIXED: Vacations: fix visualization of popup on recent versions of themes.
  Also fix date and day interval update for changed datepicker.
  SVN Rev[7721]

* Tue Aug 16 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-5
- CHANGED: Vacations: remove dead code, mark some methods as private.
  SVN Rev[7719]
- CHANGED: Vacations,Antispam: move two methods from paloSantoAntispam that are
  used only in the vacations module to paloSantoVacations. This removes the
  dependency and simplifies the code.
  SVN Rev[7718]
- CHANGED: Antispam: remove useless include of paloSantoVacations class.
  SVN Rev[7717]
- CHANGED: Vacations: simplify the disable_vacations.php cron job by using the
  new updateVacationMessageAll method of paloSantoVacations. Load default
  timezone for Elastix 4.
  SVN Rev[7716]
- CHANGED: Vacations: create method setMessageAccount to set the vacation
  message regardless of previous content, and method updateVacationMessageAccount
  to evaluate the current vacation state based on current date. This allows
  factorization of code in vacations module.
  SVN Rev[7714]
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Tue Aug 16 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Vacations: at some point, the sieve API now requires the sievescript to
  be deactivated before being removed.
  SVN Rev[7711]

* Sun Aug 14 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Vacations: replace handcoded and potentially buggy load of webmail
  profile properties with the use of paloACL::getUserProfile. Change minimum
  versions of elastix-framework to match.
  SVN Rev[7708]

* Tue Aug  9 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- ADDED: User List/Webmail: the assignment of web mail credentials to an user is
  properly an aspect of email_admin, not the core framework. As part of an
  userlist module rewrite, split off webmail assignment into a plugin. The
  userlist/plugins/webmail directory is a noop in previous versions of the
  framework but will be automatically picked up by the rewritten module.
  SVN Rev[7687]

* Fri Apr 22 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Postfix Stats: check whether /etc/localtime is a symlink and use it as
  an additional way to find out the current timezone.
  SVN Rev[7601]

* Thu Feb 25 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-4
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Mon Jan 18 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Email_admin: post-installation requires saslauthd service.
  SVN Rev[7422]

* Mon Dec 21 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Email Accounts: saslpasswd2 is required for Email Accounts.
  SVN Rev[7403]

* Fri Dec 18 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Email Admin, Email Accounts, Email Relay: add and improve Russian
  translation. Supplied by user Russian.
  SVN Rev[7402]

* Wed Dec 16 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Remote SMTP: display SMTP base error along with high level error on
  failure to check SMTP information.
  SVN Rev[7401]

* Mon Nov  9 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Vacations: implement substitution of {END_DATE} in Subject line. Add
  empty line between MIME headers and body in order to fix failure of body to
  be displayed when sent in Elastix 4.
  SVN Rev[7347]

* Mon Nov  2 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: disable_vacations.php: remove unused $arrLang.
  SVN Rev[7302]
- CHANGED: Vacations: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7300]
- CHANGED: Remote SMTP: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7299]
- CHANGED: Email Stats: replace hand-coded translation loading with
  load_language_module().
  SVN Rev[7298]
- CHANGED: Email Relay: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7297]
- CHANGED: Email List: replace hand-coded translation loading with
  load_language_module().
  SVN Rev[7296]
- CHANGED: Email Domains: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7295]
- CHANGED: Email Accounts: switch all uses of $arrLang to _tr() and replace
  hand-coded translation loading with load_language_module().
  SVN Rev[7294]
- CHANGED: Antispam: switch all uses of $arrLang to _tr() and replace hand-coded
  translation loading with load_language_module().
  SVN Rev[7293]

* Fri Oct 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Vacations: cyrus-sasl-plain is required for Vacations.
  SVN Rev[7285]

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Email_admin: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7235]

* Fri Sep 25 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-3
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Sun Mar 29 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Remote SMTP - jQuery-1.11.2 migration: fix incorrect use of attribute
  instead of property.
  SVN Rev[6926]
- CHANGED: Antispam - jQuery-1.11.2 migration: fix incorrect use of attribute
  instead of property.
  SVN Rev[6925]

* Thu Mar 12 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Antispam: do not depend on output from "service spamassassin status"
  command, and instead check for return code. Fixes Elastix bug #2196.
  SVN Rev[6905]

* Mon Mar 02 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-2
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.
  SVN Rev[6884]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- ADDED: /core/reports/setup/build/ added library php-PHPMailer
  SVN Rev[6863]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- ADDED: /core/email_admin/setup/build/ added library php-jpgraph
  SVN Rev[6861]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: core/email_admin/modules/email_stats/lang: language
  SVN Rev[6860]

* Wed Feb 18 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: the PHPMailer directory change of var/www/html/libs to
  usr/share/php/PHPMailer/class.smtp.php
  SVN Rev[6855]

* Mon Nov 24 2014 Bruno Macias <bmacias@palosanto.com>
- FIXED: module email_admin, when service is off, port number, user and
  password are not saved. Keep defaults as port number 25 and user -
  password is null. bugs.elastix.org[#2025].
  SVN Rev[6778]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Wed Jun 04 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: modules - Classes, Libraries and Indexes: Because in the new php 5.3
  packages were depreciated many functions, the equivalent functions are
  updated in the files that use to have the menctioned functions.
  SVN Rev[6638]

* Tue Jan 14 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-6
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6379]

* Wed Jan 8 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Domains, Accounts, Relay, Antispam, Remote SMTP, Email list, Email stats,
  Vacations: For each module listed here the english help file was renamed to en.hlp
  and a spanish help file called es.hlp was ADDED.
  SVN Rev[6342]

* Fri Jan 03 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Antispam, Remote SMTP: update jquery.ibutton.js to 1.0.03, fix
  potential incompatibilities with jQuery 1.9+
  SVN Rev[6329]

* Wed Aug 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-5
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5785]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- UPDATE: Correction of some mistakes in the translation file es.lang.
  SVN Rev[5736]

* Mon Aug 12 2013 Jose Briones <jbriones@palosanto.com>
- UPDATE: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5718]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
  ADD: Added translation file fr.lang.
  SVN Rev[5616]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
  ADD: Added translation file fr.lang.
  SVN Rev[5615]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
  ADD: Added translation file fr.lang.
  SVN Rev[5614]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5613]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5612]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5611]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5610]

* Tue Aug 06 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Added support to translate some error messages.
  SVN Rev[5570]

* Mon Aug 05 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-4
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5559]

* Fri Aug 02 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Email Stats: fix mispackaging of crontab file that results in crond
  refusing to run mail traffic sampler. Fixes Elastix bug #1635.
  SVN Rev[5511]

* Fri Aug 02 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module vacations. Correction of some mistakes in the translation
  files.
  SVN Rev[5497]

* Thu Aug 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module remote_smtp. Correction of some mistakes in the translation
  files.
  SVN Rev[5496]

* Thu Aug 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module email_accounts. Correction of some mistakes in the
  translation files.
  SVN Rev[5495]

* Wed Jul 31 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module vacations. Correction of some mistakes in the translation
  files.
  SVN Rev[5476]

* Wed Jul 31 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module remote_smtp. Correction of some mistakes in the translation
  files.
  SVN Rev[5456]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module  email_stats. Correction of some mistakes in the translation
  files.
  SVN Rev[5397]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module  email_list. Correction of some mistakes in the translation
  files.
  SVN Rev[5396]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module  email_domains. Correction of some mistakes in the
  translation files.
  SVN Rev[5395]

* Wed Jul 24 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module email_accounts. Correction of some mistakes in the
  translation files.
  SVN Rev[5394]

* Wed Jul 17 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module antispam. Correction of some mistakes in the translation to
  english.
  SVN Rev[5337]

* Mon May 27 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-3
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5012]

* Tue May 21 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Remote SMTP: use SQL parameters for status update, and validate it.
  SVN Rev[4983]

* Fri May 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Vacations: add shell escaping to fix potential code injection
  vulnerabilities in vacation script configuration. Pointed out by Fortify
  report.
  SVN Rev[4973]

* Mon Apr 15 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4837]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: vacations module, help section was updated.
  SVN Rev[4715]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_stats module, help section was updated.
  SVN Rev[4714]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_list module, help section was updated.
  SVN Rev[4713]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: remote_smtp module, help section was updated.
  SVN Rev[4712]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: antispam module, help section was updated.
  SVN Rev[4711]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_relay module, help section was updated.
  SVN Rev[4709]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_accounts module, help section was updated.
  SVN Rev[4708]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: email_domains module, help section was updated.
  SVN Rev[4707]

* Tue Jan 29 2013 Rocio Mera <rmera@palosanto.com>
- FIXED: Apps - Email_Admin: Was fixed in module email_account bug 1461 item 4.
  SVN Rev[4654]

* Tue Jan 29 2013 Rocio Mera <rmera@palosanto.com>
- CHANGED: Apps - Email_Admin: Was made changes in the module email_account in
  order to better interaction at moment to create a new email account
  SVN Rev[4648]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- CHANGED: firstboot - Build/elastix-firstboot.spec: Changed Version and Release in
  specfile according to the current branch.
  SVN Rev[4636]

* Mon Jan 28 2013 Luis Abarca <labarca@palosanto.com> 2.3.0-12
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4626]

* Wed Jan 16 2013 German Macas <gmacas@palosanto.com>
- CHANGE: modules - packages - festival -antispam: Change grid view and add
  option to Update packages in Package module - Fixed bug in StickyNote
  checkbox in festival and antispam modules
  SVN Rev[4588]

* Tue Jan 15 2013 Luis Abarca <labarca@palosanto.com>
- FIXED: Its no more necesary to resize the popups in certain windows of
  elastix environment. Fixes Elastix BUG #1445 - item 8
  SVN Rev[4587]

* Sat Jan 12 2013 Luis Abarca <labarca@palosanto.com>
- FIXED: The behavior of the checkbox in the sticky-notes its now normal,
  showing the checkbox instead of the ON-OFF slider button. Fixes Elastix BUG
  #1424 - item 3
  SVN Rev[4582]

* Tue Dec 04 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-11
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4497]

* Fri Nov 30 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Email Accounts: do not display the "Export Accounts" link until an
  email domain is selected. Fixes part 7 of Elastix bug #1416.
  SVN Rev[4481]

* Thu Nov 22 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Email Accounts: do not attempt to erase an account in case of errors
  while creating or updating an account. Account creation was affected if new
  account name collided with an existing one.
  SVN Rev[4454]
- FIXED: Email_admin: (regression) privileged script for domain creation would
  not add the key virtual_mailbox_domains if the key was missing in main.cf.
  SVN Rev[4452]

* Mon Nov 19 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-10
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4441]

* Fri Nov 09 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Email_admin: comment out statement that logs every single IMAP
  command, inherited from the Stickgate project.
  SVN Rev[4420]

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-9
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4356]

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

* Tue Oct  9 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Email Relay: create new helper script 'relayconfig' and use it to
  reimplement the modification of the email relay list. This removes the last
  four sudo chown instances in the Elastix core packages.
  SVN Rev[4336]
- CHANGED: Email List: extend helper script 'mailman_config' to add action to
  check main.cf, and use it to reimplement the check. This removes one indirect
  use of sudo chown via escribir_configuracion.
  SVN Rev[4334]
- CHANGED: SMTP Relay: create new helper script 'remotesmtp' and use it to
  reimplement administration of SMTP relay. This removes six instances of sudo
  chown, two uses of sudo to restart services, and one indirect use of sudo
  chown via escribir_configuracion.
  SVN Rev[4333]
- CHANGED: SMTP Relay: mark some methods as private, and some cleanup.
  SVN Rev[4332]

* Fri Oct  5 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Antispam: extend spamconfig helper to refresh spam filter on request
  and reimplement checkSpamFolder.php on top of this. This unifies the
  implementation of the spam script refresh and simplifies the antispam API.
  SVN Rev[4328]
- CHANGED: Antispam: reimplement GUI operations via the spamconfig helper
  module. This manages to remove a large amount of instances of sudo chown.
  SVN Rev[4327]
- CHANGED: Antispam: the spam removal interval is empty if antispam cronfile
  does not exist. Take advantage of this to simplify display logic and remove
  one sudo chown.
  SVN Rev[4326]
- ADDED: New helper script 'spamconfig'. This script enables and disables spam
  filters for the Antispam module.
  SVN Rev[4325]

* Thu Sep 20 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Antispam: methods uploadScriptSieve and deleteScriptSieve are used
  only when enabling spam filtering, so the calls to the methods are now
  integrated to activateSpamFilter. Now the two methods can become private.
  SVN Rev[4219]

* Tue Sep 18 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Antispam: mark some methods in paloSantoAntispam as private. Comment
  out what appears to be dead code. Replace tabs with spaces.
  SVN Rev[4218]

* Mon Sep 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Accounts: reimplement creation and update of email accounts via the
  'email_account' privileged script. This achieves the following:
  The paloEmail API gets cleaned up and becomes more consistent.
  Removes the need for granting sudo access to saslpasswd2.
  Removes the need for granting sudo access to postmap (though one unprivileged
  use remains in remote_smtp).
  Removes some uses of sudo chown, and some indirect uses via paloConfig.
- CHANGED: Accounts: fix remaining methods in paloEmail to use SQL parameters.
  SVN Rev[4216]
- CHANGED: Accounts: extend email_account helper script to change the password
  of a single mail account. Rework invocation of saslpasswd2 to use popen
  instead of echo via the shell.
  SVN Rev[4215]

* Fri Sep 14 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Accounts: reimplement account removal using the email_account helper
  script. Temporarily rename methods that use conflicting names.
  SVN Rev[4214]
- CHANGED: Accounts: introduce methods getAccountQuota() and setAccountQuota()
  in paloSantoEmail, use them to clean up quota management.
- CHANGED: Accounts: replace tabs with spaces and clean up indentation.
  SVN Rev[4212]
- CHANGED: Domains: Clean up and simplify domain manipulation API and usage.
- CHANGED: Domains: use SQL placeholders on domain account listing.
  SVN Rev[4211]

* Thu Sep 13 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Accounts: extend email_account helper script to add and remove
  individual mail accounts.
  SVN Rev[4210]

* Wed Sep 12 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Domains: extend email_account helper script to add and remove
  mail domains. Domain operations are now reimplemented on top of the extended
  email_account helper. This removes some uses of paloSantoConfig (indirect
  sudo chown) and removes the need to grant sudo access to /usr/sbin/postfix.
  SVN Rev[4199]

* Tue Sep 11 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Email Admin: remove one method that is not used anywhere, and make
  a few other methods private in paloSantoEmail.
  SVN Rev[4196]

* Wed Sep 05 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Email Accounts: remove privilege escalation vulnerability on privileged
  script for mailbox rebuild.
  SVN Rev[4182]

* Fri Aug 24 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-8
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.

* Thu Aug 09 2012 German Macas <gmacas@palosanto.com>
- FIXED: modules - antispam - festival - sec_advanced_setting - remote_smtp:
  Fixed graphic bug in ON/OFF Button.
  SVN Rev[4101]

* Wed Jun 27 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-7
- CHANGED: Email_admin - Build/elastix-email_admin.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4021]

* Mon Jun 25 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Email List: Fix XSS vulnerability.
  SVN REV[4011]

* Mon Jun 11 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Remote SMTP: only check authentication on activation, not deactivation.
  SVN REV[3987]

* Thu Jun 07 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Postfix Stats: the cron job should explicitly set the default timezone.
  SVN REV[3966]

* Fri Apr 13 2012 Luis Abarca <labarca@palosanto.com>
- ADDED: Build - SPEC's: The spec files were added to the corresponding modules
  and the framework.
  SVN REV[3849]

* Mon Apr 02 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-6
- CHANGED: Email_Admin - Remote_Smtp: Changed in index.php and remote.js to
  show other as default option in smpt_server
  SVN REV[3820] - [3819]

- FIXED: Email_Admin - AntiSpam : Changed in file index.php and javascript.js
  to fixed bug 1219
  SVN Rev[3813]
- FIXED: Email_Admin - AntiSpam : Changed in file index.php and javascript.js
  to fixed bug 1219
  SVN Rev[3812]

* Wed Mar 28 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-5
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-5
- FIXED: Modules - Email_Admin: Fixed bad arg in priviled script email_account
  SVN Rev[3793]

* Tue Mar 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-4
- ADDED: email_admin - privileged/email_account: Script that repair mailbox
  when cyrus file have been corrupted
  Svn Rev[3784]

* Mon Mar 26 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-3
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-3
- ADDED: Email_Admin - Email_Accounts/lang/en.lag: added traduction for 'The
  password must not be empty' in english and spanish
  SVN Rev[3769]
- FIXED: Email_Admin - Email_Accounts/index.php: Fixed problem that when a user
  edit one email account the password change in spite of user don't edit the
  password
  SVN Rev[3768]
- CHANGED: Email_Admin - Accounts/index.php: Added a new funstion
  reconstruct_mailbox, now the administrator user is able repair mailbox whose
  file were damage
  SVN Rev[3761]
- ADDED
  SVN Rev[3760]


* Fri Mar 09 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-2
- CHANGED: In spec file, changed prereq elastix-framework >= 2.3.0-2
- CHANGED: Modules - Email_List: Add filter controls in the reports
  SVN Rev[3734]
- CHANGED: Modules - Email_List: Add filter controls in the reports and now
  dyplay in the grid the action export_account y return
  SVN Rev[3733]

* Wed Mar 07 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.3.0-1
- CHANGED: email_list index.php add control to applied filters
  SVN Rev[3700]
- CHANGED: email_accounts index.php add control to applied filters
  SVN Rev[3699]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter
  SVN Rev[3639].

* Wed Feb 1 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-14
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-30
- CHANGED: to fixed the problem with the paged. SVN Rev[3613].

* Fri Jan 27 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-13
- CHANGED: In spec file changed Prereq elastix to elastix-framework >= 2.2.0-28
- CHANGED: modules - images: icon image title was changed on some modules.
  SVN Rev[3572].
- CHANGED: modules - trunk/core/email_admin/modules/email_accounts/themes
  /default/accounts_filter.tpl: Se modifico el archivo accounts_filter.tpl
  para corregirproblema del caracter '~' que aprecia de mas dentro del
  filtro. SVN Rev[3569].
- CHANGED: modules - vacation: Se cambia mensaje default que aparece al
  activar las vacaciones. SVN Rev[3567].
- CHANGED: modules - icons: Se cambio de algunos módulos los iconos que
  los representaba. SVN Rev[3563].
- UPDATED: modules - popup-grid: Se incluye hoja de estilo table.css para
  la presentación nueva de las tablas-grillas. SVN Rev [3550].
- CHANGED: modules - * : Cambios en ciertos mòdulos que usan grilla para
  mostrar ciertas opciones fuera del filtro, esto debido al diseño del
  nuevo filtro. SVN Rev[3549].
- UPDATED: modules - *.tpl: Se elimino en los archivos .tpl de ciertos
  módulos que tenian una tabla demás en su diseño de filtro que formaba
  parte de la grilla. SVN Rev [3541].

* Tue Jan 17 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-12
- CHANGED: In spec file changed Prereq elastix to elastix-framework >= 2.2.0-26
- ADDED: modules - antispam/libs/paloSantoAntispam.class.php: Al iniciar y
  apagar el servicio de antispam se agrego la acción de que el servicio
  sea tambien agregado o quitado en chkconfig para que cumpla los
  efectos adecuados al reiciar el servidor. SVN Rev[3501].

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-11
- CHANGED: In spec file changed Prereq elastix to
  elastix-framework >= 2.2.0-18

* Tue Nov 22 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-10
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-15
- CHANGED: module email_accounts, the asterisks and word
  "Required field" were removed when option "File Upload" is
  activated. SVN Rev[3326]
- CHANGED: module email_domains, the asterisks and word
  "Requiered field" were removed from the form. SVN Rev[3321]
- CHANGED: module email_accounts, in spanish changed the word
  "quota" to "cuota". SVN Rev[3292]
- CHANGED: module email_accounts, now the administrator can
  change the quota of an account without changing the password
  SVN Rev[3291]
- CHANGED: library paloSantoEmail.class.php, added a function
  called "accountExists" that determines if an account given
  exists or not. SVN Rev[3290]
- FIXED: Email List: remove nested <form> tag, again in
  membership report. SVN Rev[3275]
- FIXED: Email List: remove nested <form> tag. SVN Rev[3274]

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-9
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-13

* Sat Oct 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-8
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-12
- FIXED: Modules - Vacations: Fixed template to get data by user
  account (popup) in theme elastixNeo. SVN Rev[3196]
- CHANGED: module vacations, the module title is now handled by
  the framework. SVN Rev[3176]
- CHANGED: module email_stats, the module title is now handled
  by the framework. SVN Rev[3143]
- CHANGED: module email_list, the module title is now handled
  by the framework. SVN Rev[3141]
- CHANGED: module remote_smtp, the module title is now handled
  by the framework. SVN Rev[3139]
- CHANGED: module antispam, the module title is now handled by
  the framework. SVN Rev[3138]
- CHANGED: module email_relay, the module title is now handled
  by the framework. SVN Rev[3135]
- CHANGED: module email_accounts, the module title is now handled
  by the framework. SVN Rev[3133]
- CHANGED: module email_domains, the module title is now handled
  by the framework. SVN Rev[3132]

* Tue Sep 27 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-7
- CHANGED: module email_accounts, use of function fgetcsv to
  parse the csv file
  SVN Rev[2991]

* Fri Sep 09 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-6
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-3
- CHANGED: module email_accounts, in view mode the asterisks
  and word required were removed
  SVN Rev[2949]
- CHANGED: module email_domains, in view mode the asterisks
  and word required were removed
  SVN Rev[2948]
- FIXED: main.cf.elastix, commented line inet_protocols = all
  due to warnings in log /var/log/maillog
  SVN Rev[2938]
- CHANGED: script postfix_stats.php, now the output is redirected
  to the log /var/log/elastix/postfix_stats.log
  SVN Rev[2936]
- CHANGED: Email_admin - Vacations - Antispam: Change labels of
  body of message to replay in a state "vacations". Add a new
  parameter into the body of message, this parameter is called "{END_DATE}".
  SVN Rev[2931]

* Tue Aug 30 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-5
- FIXED: Email Admin - Vacations: Fixed bug where registers was
  duplicated sometimes, this error was produced by updating of
  status vacations per account. SVN Rev[2924]

* Mon Aug 29 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-4
- CHANGED: In spec file, changed prereq elastix >= 2.2.0-2
- CHANGED: In spec file, file /etc/hosts is modified, the word
  localhost have to go after 127.0.0.1
- CHANGED: lib paloSantoEmail.class.php, added the function
  escapeshellarg for the arguments of exec
  SVN Rev[2914]
- ADDED: module email_accounts, added csv exportation and importation
  SVN Rev[2913]
- CHANGED: Email_admin - Vacations: Changed the image to help file.
  ADDED:   Email_admin - Vacations: Added script 3_2.2.0-3_2.2.0-4.sql
  for updating email.db
  SVN Rev[2909]
- NEW: Email_admin - Vacations: Added new scripts to administer
  vacations by dates
  SVN Rev[2908]
- CHANGED: Email_Admin - Vacations: Add new functionality on vacations
  modules. Now users can be setting your vacations with anticipation.
  SVN Rev[2907]
- CHANGED: module email_list, added exportion for members of a list
  SVN Rev[2895]
- CHANGED: email_admin - setup: Add new function "getListByDomain"
  to paloSantoEmail.class.php for knowing the email lists asigned
  a domains
  SVN Rev[2893]
- FIXED: Modules - email_domains: Add messages of error when an user
  does an action to delete a domain if it has email accounts or email
  lists created.
  SVN Rev[2891]
- FIXED: Modules - email_account: When a new account have to be created
  and if the user does not selected a domain the next screen is the form
  to create a new account instead of to show a message "You must select
  a domain to create an account".
  SVN Rev[2884]

* Fri Aug 05 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-3
- CHANGED: module email_list, deleted unnecessary include
  SVN Rev[2878]
- CHANGED: module email_list, changed some labels according to bug #740
  SVN Rev[2877]
- CHANGED: module email_list, incremented the space between label
  and input on forms form_member.tpl and form.tpl
  SVN Rev[2874]

* Wed Aug 03 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-2
- FIXED: module email_list, the report was displaying the members
  of all lists. Now only displays the members of the selected list
  SVN Rev[2867]

* Tue Aug 02 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-1
- FIXED: mailman_config, fixed security holes
  SVN Rev[2860]
- CHANGED: module email_admin, in file default.conf.php was a
  wrong module name
  SVN Rev[2859]
- FIXED: module email_list, the module email_list was totally
  overwritten
  SVN Rev[2858]
- CHANGED: In Spec file changed prereq elastix >= 2.2.0-1
- ADDED: In Spec file added requires mailman >= 2.1.9

* Fri Jul 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-17
- FIXED: Modules - Antispam: Fixed bug where spams were not deleted
  due to the command store do not receive the ids separate by ",".
  Other reason was the amount of spam in one line and the request
  sent by socket support a limit of size in bytes. SVN Rev[2846]

* Thu Jun 30 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-16
- CHANGED: In Spec file changed prereq elastix >= 2.0.4-27
- CHANGED: postfix_stats.cron, changed name of cron to "postfix_stats.php"
  SVN Rev[2769]

* Fri Jun 24 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-15
- CHANGED: In Spec file change prereq elastix >= 2.0.4-25
- CHANGED: Email_admin - Vacations: Change label about
  translations (en.lang, es.lang). SVN Rev[2752][2753]
- CHANGED: Email_admin - Email_Domain: Better organization in
  structure of code. This commit require SVN Rev[2738]
  SVN Rev[2740][2751]
- CHANGED: Email_admin - Email_Account: Better organization in
  structure of code. This commit require SVN Rev[2738].
  SVN Rev[2739]
- CHANGED: EMAIL_ADMIN - Setup: Add new functions
  in paloSantoEmail.class.php. This commit require SVN Rev[2737]
  SVN Rev[2738]

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-14
- NEW:     Email_admin - Vacations: New module in email to configure
  the automatic replies when a user is not available. SVN Rev[2718]
- CHANGED: Email_admin - Antispam: Changes in Antispam to support
  the new modules Vacations. SVN Rev[2718]
- NEW: new module email_stats. SVN Rev[2703]
- CHANGED: Modules - Trunk: The ereg function was replaced by the
  preg_match function due to that the ereg function was deprecated
  since PHP 5.3.0. SVN Rev[2688]
- CHANGED: The split function of these modules was replaced by the
  explode function due to that the split function was deprecated
  since PHP 5.3.0. SVN Rev[2650][2668]
- CHANGED: Email_admin - Remote_smtp: Add validation if the server
  is "GMAIL, HOTMAIL or YAHOO" required add the account with password,
  but "OTHER"  is not necessary add account and password, so the user
  has the option to add or not an account whit the server is "OTHER".
  SVN Rev[2625]

* Wed Apr 27 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-13
- CHANGED: file db.info, changed installation_force to ignore_backup
  SVN Rev[2490]
- CHANGED: In Spec file, changed prereq of elastix to 2.0.4-19

* Wed Apr 06 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0-4-12
- FIXED:   email_admin - remote_smtp:  Fixed the event to add a
  relay smtp host where if the host needs a certificate ,this
  allow to send emails through but the mail server cannot
  receive mails. SVN Rev[2517]

* Tue Apr 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-11
- CHANGED:  email_admin - antispam: Changes translation lang
  files. SVN Rev[2509]
- FIXED: Module Remote SMTP, bad definition language Ex.
  SVN Rev[2505]
- CHANGED: Email - Antispam: Changed sieve script to improve
  Spam filtering. SVN Rev[2503]
- CHANGED: Module antispam, replace "start service" to
  "restart service". SVN Rev[2500]

* Tue Mar 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-10
- CHANGED: Fixed usability bug:
  "http://bugs.elastix.org/view.php?id=799" where password fields
  (password and re-type password) must be join. SVN Rev[2468]
- CHANGED: Email - Remote SMTP:  Add label as example:
  username@domain.com and change the image of help.
  Change labels user (Email Account) password (Email Account)
  to Username and Password. SVN Rev[2467]

* Mon Mar 28 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-9
- CHANGED: Email - Antispam: Change el name of button "Update"
  to "Save". SVN Rev[2466]
- CHANGED: New look and styles in Remote SMTP. Add functionality
  to show the commons Mail servers like GMAIL, HOTMAIL, YAHOO.
  SVN Rev[2465]
- FIXED:   Fix bug "http://bugs.elastix.org/view.php?id=800".
  Form don't have any required values. However appear a
  legend "* Required field", this legend must be removed.
  SVN Rev[2464]
- CHANGED: Change the styles of remote smtp module and the way
  to create certificates was changed by executing a
  command "/etc/pki/tls/certs/make-dummy-cert" to create a
  new certicate. SVN Rev[2460]

* Thu Mar 24 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-8
- FIXED:  modules - email_admin: Fixed bug where Spam folder
  per user was never "subscribe".
  For more information "http://bugs.elastix.org/view.php?id=792"
  SVN Rev[2455]

* Sat Mar 19 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-7
- CHANGED: In spec file move files to execute the action to
  to remove Spam and create Spam folders per email accounts.
- CHANGED: In spec file change prereq elastix >= 2.0.4-13
- CHANGED: email_admin - antispam:  Added Help  entry of
  the Antispam module and the automatic periodic deletion
  of messages inside each account's Spam folder. SVN Rev[2441]
- CHANGED: Misspelling of the word mailman, changed from
  mailmam to mailman.  SVN Rev[2409]
- CHANGED:  New functionality of Antispam.
        - Automatically create Spam folder to each email account
        - Improved Spam filtering through the use of Sieve Service
        - Improved performance
  SVN Rev[2396]

* Tue Mar 01 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-6
- CHANGED: In spec file change prereq elastix >= 2.0.4-10
- CHANGED: In spec file removed lines to change password of
  cyrus user, now firstboot has the job to do it.

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-5
- CHANGED:  In Spec file add prerequiste elastix 2.0.4-9

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-4
- CHANGED:   In Spec add lines to support install or update
  proccess by script.sql.
- DELETED:   Databases sqlite were removed to use the new
  format to sql script for administer process install, update
  and delete. SVN Rev[2332]
- ADDED: module antispam, added a new field to change the
  rewrite header in the file local.cf. SVN Rev[2330]
- FIXED: module remote_smtp, fixed spelling mistake, the
  word autentification was replaced by aunthentication.
  SVN Rev[2326]
- CHANGED: module antispam, the configuration files are
  created only in the action activate spam filter, also
  changed the error messages. SVN Rev[2323]
- ADDED: Module antispam, added the exec command service
  spamassassin start and stop for the activation or
  desactivation of the antispam service. SVN Rev[2319]
- CHANGED: changed the db.info of fax to the format used in
  elastix-dbprocess. SVN Rev[2316]
- ADDED: added the folders update, delete and install, and the
  sql script for the installation, also db.info has the
  correct format used in elastix-dbprocess. SVN Rev[2315]

* Thu Feb 03 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-3
- CHANGED:  menu.xml to support new tag "permissions" where has
  all permissions of group per module and new attribute "desc"
  into tag  "group" for add a description of group.
  SVN Rev[2294][2299]
- FIXED:    Email_admin - Remote SMTP: fixed bug #643 and #687
  in elastix.org.
       #643: config file main.cf with respect add a relay host
       #687: error in validation username as email
  SVN Rev[2255]
- FIXED:    Problem if any account was deleted due to if there
  is an error while to delete an email account and its user on
  system cannot be removed the account is deleted but the user not,
  it occur when a new account is created with the same user that
  was deleted because this user in system exist.. [#489]
  SVN Rev[2246][2247][2249]
- FIXED:    Email - Email Account:  password of email account
  cannot be replaced using module email account, the error was
  in function edit_email_account where use
  "crear_usuario_correo_sistema" where send var $email="".
  SVN Rev[2240]


* Thu Dec 30 2010 Eduardo Cueva <ecueva@palsoanto.com> 2.0.4-2
- CHANGED: In Spec file put process to move cyradm.php to
  /var/www/html/libs

* Thu Dec 23 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- CHANGED: Additionals libs, move libs from additional folder
  to each specify module. By example paloSantoEmail.class.php
  SVN Rev[2150]

* Mon Dec 20 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-25
- CHANGED: In spec file add instructions post and install about
  email from elastix.spec.
- NEW:     Files about configuration email was moved from
  additionals to setup forlder of email_admin module, these
  change is for better organization in elastix.spec. SVN Rev[2111]
* Mon Dec 06 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-24
- ADD:     Add new prereq to roundcube in spec file
- CHANGED: massive search and replace of HTML encodings with the
  actual characters. SVN Rev[2002]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-23
- CHANGED: Updated the Bulgarian language elastix. SVN Rev[1857]

* Mon Oct 18 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-22
- FIXED:  postfix configuration support in migration from 1.6 to 2.0.
  Some changes appear in email account
  See in http://bugs.elastix.org/view.php?id=490 [#490] SVN Rev[1840]
- CHANGED: Updated fr.lang. SVN Rev[1825]

* Tue Oct 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-21
- ADDED:      New fa.lang file (Persian). SVN Rev[1793]

* Tue Sep 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-20
- CHANGED: Apply all changes in before realease.

* Mon Sep 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-19
- NEW:     Added the certificate option to allow the autentication for remote smtp. SVN Rev[1748]

* Wed Aug 18 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-18
- CHANGED: Prereq elastix-2.0.0-34

* Tue Aug 17 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-17
- FIXED: Work around PHP bug (forget to close httpd file descriptors on PHP fork()) for the case of mailman restart. Requires SVN commit #1696. Rev[1703].

* Sat Aug 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-16
- CHANGED:   Change the help files of email_list and remote SMTP modules.

* Wed Jul 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-15
- CHANGED:   Maintenance coding(Menu WebMail from menu.xml). Rev[1638]

* Fri Jul 23 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-14
- FIXED: Name actions button was changed to "Enable" and "Disable".

* Wed Jul 21 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-13
- CHANGED: Module remote SMTP was improved.
-          Fixed bug the radio button enable or disable remote smtp [#237].

* Thu Jul 01 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-12
- CHANGED: Change the style in Remote SMTP module.

* Mon Jun  7 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-11
- Fixed bug where a domain cannot have a character "_"

* Tue Mar 16 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-10
- Defined number order menu.

* Mon Mar 01 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-9
- Update release module.

* Tue Jan 19 2010 Bruno Macias <bmacias@palosanto.com> 2.0.0-8
- Function getParameter removed in each module.

* Tue Dec 29 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-7
- Fixed bug, validation email format in module remote_smtp.

* Fri Dec 04 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-6
- Module Remote SMTP, more testing.
- Fixed minor bugs in emails.

* Tue Oct 20 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-5
- Fixed bug in module Remote SMTP, improved definition in the hostname and domain.

* Tue Oct 20 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-4
- Fixed bug name of id module, remote_smpt to remote_smtp.

* Mon Oct 19 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-3
- Add accion uninstall rpm.
- Fixed minor bugs in definition words languages and messages.

* Mon Sep 07 2009 Bruno Macias <bmacias@palosanto.com> 2.0.0-2
- Fixed Bug in email configuration, delete @example.com and validation in email box when not exits.
- New module smart host.
- New structure menu.xml, add attributes link and order.

* Wed Aug 26 2009 Bruno Macias <bmacias@palosanto.com> 1.0.0-1
- Initial version.
