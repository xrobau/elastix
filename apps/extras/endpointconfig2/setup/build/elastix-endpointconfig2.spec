%define modname endpointconfig2

Summary: Elastix Module Distributed Dial Plan
Name:    elastix-%{modname}
Version: 0.0.2
Release: 0
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires: freePBX >= 2.8.1-12
Requires: elastix-framework >= 2.4.0-0
Requires: elastix-agenda >= 2.4.0-5
Requires: py-Asterisk
Requires: python-eventlet
Requires: python-tempita
Requires: pyOpenSSL
Requires: python-daemon
Requires: MySQL-python
Requires: python-json

%description
The Elastix Endpoint Configurator is a complete rewrite and reimplementation of
the elastix-pbx module known as the Endpoint Configurator. This rewrite 
addresses several known design flaws in the old Endpoint Configurator and should
eventually be integrated as the new standard configurator in elastix-pbx.

User-visible features:
- Supports assignment of multiple accounts to a single endpoint.
- Automatic model detection implemented for most supported manufacturers.
- Improved user interface written with Ember.js.
- Network parameters can be updated onscreen in addition to being uploaded.
- Endpoint network scan is cancellable.
- The configuration of every endpoint is executed in parallel, considerably
  shortening the potential wait until all endpoints are configured.
- A log of the actual endpoint configuration can be displayed for diagnostics.
- Supports two additional download formats in addition to the download format of
  the old endpoint configurator - required for multiple account support.
- Custom properties can be assigned to the endpoint and per account, until GUI
  support is properly added.
- Can be installed alongside the old endpoint configurator.
- For supported phones, the module provides an HTTP resource to serve remote
  services, such as a phonebook browser, for better integration with Elastix.

For developers:
- The architecture of the module is plugin-friendly. Each vendor implementation
  (written in Python) has been completely encapsulated and no vendor-specific
  logic remains in the module core itself. To add a new vendor, it is enough to
  write a new implementation class in Python, add new templates if necessary,
  and add database records for MACs. Patching of the core is no longer required.
- Foundation for replacing the standard configurator dialog with a 
  vendor-specific one (not yet used).

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p                        $RPM_BUILD_ROOT/var/www/html/
mv modules/                     $RPM_BUILD_ROOT/var/www/html/

# Additional (module-specific) files that can be handled by RPM
mv setup/etc/ $RPM_BUILD_ROOT/
mv setup/usr/ $RPM_BUILD_ROOT/

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

  # Restart apache to disable HTTPS redirect on phonesrv script
  /sbin/service httpd restart
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
fi
rm -f $pathModule/preversion_%{modname}.info


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
%{_localstatedir}/www/html/*
/usr/share/elastix/module_installer/*
/usr/share/elastix/endpoint-classes
%defattr(644, root, root)
/etc/httpd/conf.d/elastix-endpointconfig.conf
%defattr(755, root, root)
/usr/bin/*
/usr/share/elastix/privileged/*

%changelog
* Wed Sep  4 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: merge the functionality of the program
  elastix-endpointclear into elastix-endpointconfig, and remove the large code
  duplication.
- CHANGED: New Endpoint Configurator: implement clearing of endpoint 
  configuration for Digium phones. This required a change in the python API.
  SVN Rev[5832]
- FIXED: New Endpoint Configurator: request dhcp property in view init so change
  to defined value will be observed, as noted in Ember.js rc8 changelog.
  SVN Rev[5829]

* Sun Sep  1 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: use GET for read requests whenever 
  possible.
  SVN Rev[5825]
- CHANGED: New Endpoint Configurator: update Ember.js to 1.0.0
  SVN Rev[5824]

* Fri Aug 30 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.2-0
- Bump version for release
- CHANGED: New Endpoint Configurator: add basic Digium phones support.
  SVN Rev[5822] 
- CHANGED: New Endpoint Configurator: update Ember.js to 1.0.0-rc8
  SVN Rev[5821]

* Thu Aug 29 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add phonebook search capability to Cisco 
  XML services.
  SVN Rev[5820]

* Wed Aug 28 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add phonebook support for Yealink.
  SVN Rev[5818]
- CHANGED: New Endpoint Configurator: add static provisioning support for 
  Grandstream GXP1450.
  SVN Rev[5817]

* Tue Aug 20 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add support for Elastix LXP100
  SVN Rev[5777]
- CHANGED: New Endpoint Configurator: add proper phonebook support for GXV3140.
  SVN Rev[5776]
- CHANGED: New Endpoint Configurator: write authentication hash as soon as it
  is available, to allow reference from HTTP requests. Required for phonebook
  in Grandstream GXV3140.
  SVN Rev[5775]

* Mon Aug 19 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add static provisioning support for GXV
  series. Tested to work with GXV3140.
  SVN Rev[5774]
- CHANGED: New Endpoint Configurator: set Elastix LXP200 and Grandstream GXP280
  as capable of static provisioning. Add default HTTP password for LXP200.
  SVN Rev[5773] 
- CHANGED: New Endpoint Configurator: serve gs_phonebook.xml as an alias to
  phonebook.xml, as requested by GXP280 phones.
  SVN Rev[5772]
- ADDED: New Endpoint Configurator: add phonebook support for Elastix phones. As
  these are rebranded GXP140x phones, it is enough to subclass the Grandstream
  phonebook class and do nothing else.
  SVN Rev[5771]

* Sun Aug 18 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add flag to inform support for static
  provisioning. Modify GUI to display the information.
  SVN Rev[5770]
- CHANGED: New Endpoint Configurator: update Ember.js to 1.0.0-rc7 and 
  Handlebars to 1.0.0.
  SVN Rev[5769]

* Fri Aug 16 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add some static provisioning support for
  a few supported GrandStream models. This also attempts to point the phonebook
  on GrandStream to the HTTP resource.
  SVN Rev[5768]
- ADDED: New Endpoint Configurator: add phonebook.xml resource for GrandStream.
  Fix invalid SQL for fetching the endpoint data.
  SVN Rev[5767]
- ADDED: New Endpoint Configurator: Add data for the GrandStream GXP1400
  SVN Rev[5766]

* Thu Aug 15 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.1-0
- ADDED: New Endpoint Configurator: Initial release 
