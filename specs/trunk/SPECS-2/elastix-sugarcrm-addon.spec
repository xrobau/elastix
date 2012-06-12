%define modname sugarcrm-addon

Summary: Package that install SugarCRM.
Name: elastix-%{modname}
Version: 5.2.0l
Release: 6 
License: GPL
Group: Applications/System
#Source: %{modname}_%{version}-%{release}.tgz
Source: %{modname}_%{version}-5.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: php, mysql-server, asterisk
Conflicts: elastix-sugarcrm <= 4.5.0d-3
Prereq: elastix >= 2.0.0-55

%description
Package that install SugarCRM.

%prep
%setup -n %{modname}

%install
rm -rf      $RPM_BUILD_ROOT
mkdir -p    $RPM_BUILD_ROOT
mkdir -p    $RPM_BUILD_ROOT/var/www/html
mv SugarCRM $RPM_BUILD_ROOT/var/www/html/

mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%post
# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

if [ $1 -eq 1 ]; then #install
  # The installer database
  elastix-dbprocess "install" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db"
elif [ $1 -eq 2 ]; then #update
  # The installer database
  elastix-dbprocess "update" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db"
fi

%preun
if [ $1 -eq 0 ] ; then # delete
  echo "Delete SugarCRM menu"
  elastix-menuremove "%{modname}"

  # The installer database
  elastix-dbprocess "delete" "/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/setup/db"
fi

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/html/*
/usr/share/elastix/module_installer/*
%config(noreplace) /var/www/html/SugarCRM/config.*

%changelog
* Tue Oct 26 2010 Eduardo Cueva <ecueva@palosanto.com> 5.2.0l-6
  - CHANGED: Move line elastix-menumerge at beginning the "%post" 
    in spec file. It is for the process to update.

* Thu Jul 29 2010 Bruno Macias <bmacias@palosanto.com> 5.2.0l-5
  - CHANGED: Format name files sqs in db/install.

* Tue Jul 27 2010 Bruno Macias <bmacias@palosanto.com> 5.2.0l-4
  - CHANGED: Name rpm elastix-sugarcrm to elastix-sugarcrm-addon

* Thu Jul 22 2010 Bruno Macias <bmacias@palosanto.com> 5.2.0l-3
  - FIXED: added Conflict elastix-sugarcrm-4.5.0d-3.
  - CHANGED: sql CREATE TABLE by CREATE TABLE IF NOT EXISTS.

* Thu Jul 22 2010 Bruno Macias <bmacias@palosanto.com> 5.2.0l-2
  - NEW: Spec of RPM support pre administration for database install.
  - NEW: The sugarCRM will be a addons.

* Wed Jul 21 2010 Bruno Macias <bmacias@palosanto.com> 5.2.0l-1
  - UPDATED: SugarCRM to version 5.2.0l

* Wed Mar 26 2008 Bruno Macias <bmacias@palosanto.com> 4.5.0d-3
  - Fixed bug at install iso elastix, user asterisk not exists. Add Prereq asterisk againt.

* Tue Mar 25 2008 Bruno Macias <bmacias@palosanto.com> 4.5.0d-2
  - Delete Prereq elastix-additionals, asterisk and elastix. 

* Thu Mar 20 2008 Bruno Macias <bmacias@palosanto.com> 4.5.0d-1
  - new rpm elastix-sugarcrm 
  - add files schema.sugarcrm and sugarcrmWrapper.php for finish instalation.
  - With this rpm will be deleted parts of rpm elastix-additionals (webContentAdditional - crm) and elastix-mysqldbdata (sugarcrm database) 
