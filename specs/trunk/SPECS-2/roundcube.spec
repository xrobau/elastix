%define	name RoundCubeMail
%define	release	10
%define	version	0.3.1

Summary: RoundCube Webmail is a browser-based multilingual IMAP client
Name: %{name}
Version: %{version}
Release: %{release}
Group: Applications/Internet
License: GNU General Public License (GPL)
URL: http://www.roundcube.net
Source0: roundcubemail-%{version}.tar.gz
Source1: roundcubemail-db-%{version}.tgz
Distribution: Elastix
BuildRoot: /var/tmp/%{name}-%{version}-root
BuildArch: noarch
Patch0: roundcube-config-v0.3.1.patch
Patch1: roundcube-changepass-integracion-elastix-v0.3.1.patch
Patch2: roundcube-elastix-integration2-v0.3.1.patch
Prereq: php, mysql-server
Prereq: elastix >= 2.0.4-19
Prereq: elastix-firstboot >= 2.0.0-4

%description
RoundCube Webmail is a browser-based multilingual IMAP client with an application-like user interface. It provides full functionality you expect from an e-mail client, including MIME support, address book, folder manipulation, message searching and spell checking. RoundCube Webmail is written in PHP and requires the MySQL database. The user interface is fully skinnable using XHTML and CSS 2.

%prep
%setup -n roundcubemail-%{version}
%patch0 -p1
%patch1 -p1
%patch2 -p1

%install
rm -rf   $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/var/www/html/mail/
mv  $RPM_BUILD_DIR/roundcubemail-%{version}/*  $RPM_BUILD_ROOT/var/www/html/mail/


mkdir -p  $RPM_BUILD_ROOT/usr/share/elastix/module_installer/build_tmp/
mkdir -p  $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
tar -xvzf %{SOURCE1} -C $RPM_BUILD_ROOT/usr/share/elastix/module_installer/build_tmp/

mv $RPM_BUILD_ROOT/usr/share/elastix/module_installer/build_tmp/roundcubemail-db/setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv $RPM_BUILD_ROOT/usr/share/elastix/module_installer/build_tmp/roundcubemail-db/menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%pre
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{name}.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_%{name}.info
fi


%post
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"

 # Run installer script to fix up ACLs and add module to Elastix menus.
  elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml
pathSQLiteDB="/var/www/db"
mkdir -p $pathSQLiteDB
preversion=`cat $pathModule/preversion_%{name}.info`

if [ $1 -eq 1 ]; then #install
  # The installer database
  elastix-dbprocess "install" "$pathModule/setup/db"
elif [ $1 -eq 2 ]; then #update
  # The installer database
  elastix-dbprocess "update" "$pathModule/setup/db" "$preversion"
fi


%preun
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # delete
  echo "Delete RoundCubeMail menu"
  elastix-menuremove "webmail"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%clean
rm -rf $RPM_BUILD_ROOT/

%files
%defattr(-, asterisk, asterisk)
/var/www/html/mail/*
/usr/share/elastix/module_installer/*
%config(noreplace) /var/www/html/mail/config/*.inc.php


%changelog
* Wed May 04 2011 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-10
- CHANGED:   In Source rouncubemail-db changed file db.info where parameter
  installation_force is replaced by ignore_backup

* Thu Mar 31 2011 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-9
- CHANGED:   Remove olds script to install and put new script sql.

* Wed Mar 30 2011 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-8
- CHANGED:   patch file "roundcube-changepass-integracion-elastix-v0.3.1.patch"
  was changed the label of error "loginemberror" which show a message when the
  process to login is incorrect. 
  For more details see: "http://bugs.elastix.org/view.php?id=793".

* Tue Dec 11 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-7
- CHANGED:   Change config files to see the folder INBOX, Sent, Trash and
  others folders by default. 
             Set Paramaters in main.inc.php to allow delete messages and 
  it can be sent to folder trash before to delete.

* Mon Dec 06 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-6
- CHANGED:   Add prereq elastix-firstboot in spec file

* Tue Oct 26 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-5
- CHANGED:   Move line elastix-menumerge at beginning the "%post" in spec file. It is for the process to update

* Thu Jul 29 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-4
- CHANGED:   Remove temporal files in roundcube-db.

* Thu Jul 29 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-3
- CHANGED:   Change the name of files to install a new roundcube database.

* Wed Jul 28 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1-2
- UPDATED:   Password to install roundCube User has been encrypted.
-            Validation in the process of installation. It should have as prerequisites: PHP, Mysql-server, Elastix >= 2.0.0-28

* Tue Jul 27 2010 Eduardo Cueva D. <ecueva@palosanto.com> version 0.3.1
- UPDATED:   RoundCube WebMail has been updated to the last stable version 0.3.1.

* Thu Jun 24 2010 Alex Villacis Lasso <a_villacis@palosanto.com> - 0.2.10
- Replace vulnerable html2text.php files with copies from 0.4beta.

* Wed Dec 30 2009 Bruno Macias V <bmacias@palosanto.com> - 0.2.9alpha.2
- Fixed bug in automatic login in webmail. Changed piriod (.) by dot (@). This change is in roundcube-elastix-integration2.patch.

* Mon Jun 01 2009 Bruno Macias V <bmacias@palosanto.com> - 0.2.8alpha.2
- Comment line chown asterisk.asterisk in the post. The permission isn't correct.

* Sat Jan 10 2009 Alex Villacis Lasso <a_villacis@palosanto.com> - 0.2.7alpha.2
- Mark package as noarch

* Wed Sep 10 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.2.6alpha.2
- Fixed bug, show content folder and delete email, pacth6 (roundcube-elastix-integration-javascript.patch)  

* Mon Sep 08 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.2.5alpha.1
- Fixed bug, show content folder and delete email 

* Wed Aug 27 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.2.4alpha
- Fixed bug with attachment patch 01-roundcubemail-localization-es_ES-remove-extra-newline.patch 02-roundcubemail-mail-mimepart-remove-stale-boundary.patch 03-roundcubemail-detect-binary-attachment.patch

* Fri Aug 22 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.2.3alpha
- In patch roundcube-config add dependence /etc/postfix/virtual, and better integration with elastix, changepass 

* Tue Aug 13 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.2.2alpha
- Add patch cofiguration for roundcube y patch changepass create by cchiriboga, update version of roundcube and update pacth integration.

* Tue Jun 03 2008 Bruno Macias   <bmacias@palosanto.com>       - 0.1.1-2
- Add patch for elastix integration web mail.

* Tue May 20 2008 Edgar Landivar <elandivar@palosanto.com>     - 0.1.1-1
- Updating to the 0.1.1 version
