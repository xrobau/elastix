Summary: Package that install VTigerCRM.
Name: elastix-vtigercrm
Version: 5.2.0
Release: 0
License: GPL
Group: Applications/System
Source: elastix-vtigercrm-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: asterisk, php, mysql-server
Prereq: elastix >= 2.0.0-55
Requires: elastix-firstboot >= 2.0.0-5

%description
Package that install VTigerCRM.

%prep

%setup -n elastix-vtigercrm

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/var/www/html
mv $RPM_BUILD_DIR/elastix-vtigercrm/vtigercrm              $RPM_BUILD_ROOT/var/www/html/
#mv $RPM_BUILD_DIR/elastix-vtigercrm/schema.vtiger         $RPM_BUILD_ROOT/var/www/html/
#mv $RPM_BUILD_DIR/elastix-vtigercrm/vtigercrmWrapper.php  $RPM_BUILD_ROOT/var/www/html/
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db/install/
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db/update/
mv $RPM_BUILD_DIR/elastix-vtigercrm/schema.vtiger $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db/install/08-schema-vtiger.sql

%pre
if [ $1 -eq 2 ]; then   
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' elastix-vtigercrm >  /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/version_anterior.info
fi
%post
elastix_version=`rpm -q --queryformat='%{VERSION}' elastix | sed - -e 's/\\([0-9].[0-9]*\\).*/\\1/'`
if [ "$elastix_version" = "1.0" ]; then
    echo "Elastix Version $elastix_version"
    elastix_release=`rpm -q --queryformat='%{RELEASE}' elastix | sed - -e 's/\\([0-9].[0-9]*\\).*/\\1/'`
    if [ "$elastix_release" \> "1" ]; then
        echo "Elastix Release $elastix_release"
        #Actualizacion
        if [ "`sqlite3 /var/www/db/menu.db "select count(id) from menu where id='vtigercrm';"`" = "0" ]; then
            `sqlite3 /var/www/db/menu.db "insert into menu values('vtigercrm','extras','vtigercrmWrapper.php?URL=/vtigercrm/','vTigerCRM','framed');"`
            echo "Menu vtigercrm agregado en menu.db"
        fi
        if [ "`sqlite3 /var/www/db/acl.db "select count(name) from acl_resource where name='vtigercrm';"`" = "0" ]; then
            `sqlite3 /var/www/db/acl.db "insert into acl_resource (name, description) values ('vtigercrm','vTigerCRM'); insert into acl_group_permission (id_action, id_group, id_resource) values (1,1,(select last_insert_rowid()));"`
            echo "Menu vtigercrm agregado en acl_resource, permisos otorgados en acl_group_permission"
        fi
        echo "Menu vtigerCRM Actualizado"
    else echo "Elastix Release no actualizable"
    fi
fi
versionaanterior=`cat /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/version_anterior.info`
if [ $1 -eq 1 ]; then #install
  # Run installer script to fix up ACLs and add module to Elastix menus.
  #elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml
  /sbin/service mysqld status &>/dev/null
  is_mysqld_run=$?  
  # The installer database
  if [ ! -d "/var/lib/mysql/vtigercrm520" ]; then #no existe la base
    if [ $is_mysqld_run -eq 0 ]; then # la base de datos esta corriendo
       echo "The service mysqld is running."
       elastix-dbprocess "install" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db"
    else
       echo "The service mysqld is not running."
       cp /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db/install/08-schema-vtiger.sql      /var/spool/elastix-mysqldbscripts/
    fi
  else
    echo "The vtigercrm520 database exists."
  fi
elif [ $1 -eq 2 ]; then #update
    # The installer database
  if [  -d "/var/lib/mysql/vtigercrm520" ]; then #existe la base        
    if [ $is_mysqld_run -eq 0 ]; then # la base de datos esta corriendo                 
        echo "The service mysqld is running."
        elastix-dbprocess "update" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db" "$versionaanterior"
    else
       echo "The service mysqld is not running."
       #elastix-dbprocess "update" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db" "$versiona2billing" "sql_export" > /var/spool/elastix-mysqldbscripts/112-schema-a2billingdb.sql
    fi    
  else
       echo "The mya2billing database not exists."
  fi
fi


%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/html/*
/usr/share/elastix/module_installer/*
%config(noreplace) /var/www/html/vtigercrm/config.*

%changelog
* Mon nov 8 2010 kleber loayza <andresloa@palosanto.com> 5.2.0
- Update Vtigercrm  to 5.2.0

* Fri Dec 4 2009 Bruno Macias <bmacias@palosanto.com> 5.1.0-6
- Update Vtigercrm  to 5.1.0
    
* Fri Jul 23 2010 Alex Villacis Lasso <a_villacis@palosanto.com> 5.1.0-8
- REMOVED: vtigercrmWrapper.php
- CHANGED: move schema.vtiger to spool directory for elastix-firstboot

* Mon Jan 04 2010 Bruno Macias <bmacias@palosanto.com> 5.1.0-7
- Fixed bug create dabatase in vtiger.schema.

* Fri Dec 4 2009 Bruno Macias <bmacias@palosanto.com> 5.1.0-6
- Update Vtigercrm  to 5.1.0

* Mon May 25 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 5.0.3-6
- Remove obsolete dependency on elastix-additionals

* Thu Mar 20 2008 Bruno Macias <bmacias@palosanto.com> 5.0.3-5
  - Source elastix-vtigercrm add files schema.vtiger and vtigercrmWrapper.php for finish instalation.
  - Comments removed in this spec and in section post replace /vtigercrm/ by vtigercrmWrapper.php?URL=/vtigercrm
* Wed Dec 05 2007 Adonis Figueroa <afigueroa@palosanto.com> 5.0.3-4
  - Comment pre and post installation because it's realized in the wrapper.
  - No replace file config.* in /var/www/html/vtigercrm/ , this funcionality is config
* Mon Dec 04 2007 Edgar Landivar <elandivar@palosanto.com> 5.0.3-3
  - Removing elastix-mysqldbdata dependency
* Mon Dec 03 2007 Edgar Landivar <elandivar@palosanto.com> 5.0.3-1
  - Changes in the version number to reflect the vtiger version number 
    instead of the elastix version number
  - Removed elastix dependency
* Mon Nov 19 2007 Bruno Macias <bmacias@palosanto.com> 0.8-5.7
  - Fixed validation if directory /var/lib/mysql/$nombredb existe. Change of -f to -d
  - Remove Prereq elastix
* Mon Nov 5 2007 Adonis Figueroa <afigueroa@palosanto.com> 0.8-5.5
  - Changes to add the vtiger menu in the database.
* Thu Nov 1 2007 Bruno Macias <bmacias@palosanto.com> 0.8-5.4
  - Changes in release this spec for maintenaince.
* Wed Oct 17 2007 Adonis Figueroa <afigueroa@palosanto.com> 0.8-5.2
  - Database change, it include DROP if exist the database
