%define modname endpointconfig2

Summary: Elastix Module Distributed Dial Plan
Name:    elastix-%{modname}
Version: 0.0.6
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
Requires: python-cjson
Requires: pytz
Requires: php-magpierss

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
mkdir -p $RPM_BUILD_ROOT/usr/local/share/elastix/endpoint-classes/tpl

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
/usr/local/share/elastix/endpoint-classes
%defattr(644, root, root)
/etc/httpd/conf.d/elastix-endpointconfig.conf
%defattr(755, root, root)
/usr/bin/*
/usr/share/elastix/privileged/*

%changelog
* Wed Nov 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: New Endpoint Configurator: do not crash when configuring the RCA IP150 
  with static IP and just one DNS instead of two.
  SVN Rev[6085]
- FIXED: New Endpoint Configurator: tweak the Atlinks Temporis IP800 template to
  blank out unused accounts on the phone display.
  SVN Rev[6083] 
- FIXED: New Endpoint Configurator: enhance Atlinks Temporis IP800: add support
  for setting language (default Spanish), and link to remote phonebooks with the
  exact same format as Yealink. Reprogram line keys to display assigned accounts
  correctly. Bind to Elastix as primary NTP server.
  SVN Rev[6082]

* Tue Nov 12 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: New Endpoint Configurator: make DPMA optional when removing phone 
  configuration for Digium phones.
  SVN Rev[6081]
- CHANGED: New Endpoint Configurator: when removing configuration files, also
  unregister previously detected accounts.
  SVN Rev[6080]
- FIXED: New Endpoint Configurator: the AudioCodes 310HD/320HD require a dummy
  HTTP request from the same IP that will later POST the autoconfiguration data,
  in order for the phone to accept the changes. Also set default language to 
  Spanish, and inherit timezone from the Elastix server.
  SVN Rev[6079]

* Fri Nov 08 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: tweak detection method for Elastix LXP200
  with updated firmware.
  SVN Rev[6074]
- CHANGED: New Endpoint Configurator: increase default socket timeout to 10
  seconds to cope with several endpoints that take a long time to apply changes.
  SVN Rev[6073]

* Thu Nov 07 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add support for RCA IP150
  SVN Rev[6072]

* Mon Nov 04 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.6-0
- Bump version for release
- FIXED: New Endpoint Configurator: fix incorrect variable reference that caused
  an uncaught exception when configuring a static IP on a Grandstream/Elastix
  phone.
  SVN Rev[6053]
- FIXED: New Endpoint Configurator: do not silently lose uncaught exceptions when
  logging to a progress file. Required for more precise debugging.
  SVN Rev[6050]
- FIXED: New Endpoint Configurator: fix incorrect variable references in
  configuration status monitoring.
  SVN Rev[6049]
- CHANGED: New Endpoint Configurator: allow configuration of default language
  for Grandstream and Elastix endpoints by setting the custom endpoint property
  'language'. The default if unset is 'es' for Spanish.
  SVN Rev[6048]

* Sat Oct 26 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: updated internal copy of Ember.js to 
  version 1.1.2.
  SVN Rev[6041]

* Wed Oct 23 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: updated internal copy of Ember.js to
  version 1.1.0
  SVN Rev[6031]

* Tue Oct 22 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: enhance the Grandstream/Elastix 
  configurator to add the following useful parameters: NTP Server, set to the
  Elastix server; disable override of NTP server by DHCP option 42; enable 
  automatic attended transfer; set default display language to Spanish; enable
  auto-answer on Call-Info.
  SVN Rev[6026]

* Thu Oct 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.5-0
- Bump version for release

* Wed Oct 16 2013 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: New Endpoint Configurator: add help files.
  SVN Rev[6013]

* Mon Oct 07 2013 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add support for offset-based timezone
  configuration using the pytz library. The phones by the following manufacturers
  will now inherit the telephony server timezone: Atlinks, Yealink. Based on a 
  patch by Israel Santana Alemán.
  SVN Rev[5995]

* Sat Oct 05 2013 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: require and use system-installed magpierss
  instead of bundled magpierss.
  SVN Rev[5991]

* Sun Sep 29 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add status message at the end of the 
  configuration process indicating whether there were warnings or errors.
  SVN Rev[5953]

* Thu Sep 26 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.4-0
- Bump version for release

* Tue Sep 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: New Endpoint Configurator: do not allow any code path to exit the 
  configurator without printing the end banner. This is required for the GUI to
  know that the configurator stopped running.
  SVN Rev[5894]

* Fri Sep 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: rework the remote phonebook support to
  allow authenticated endpoints that do not have extensions associated with an
  Elastix user to still access the internal contact list and the public contacts
  of the external contact list.
  SVN Rev[5879]

* Thu Sep 12 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: New Endpoint Configurator: add the ability to store custom templates
  that will override the default ones if required, at the directory 
  /usr/local/share/elastix/endpoint-classes/tpl . Based on a patch by Israel 
  Santana Alemán.
  SVN Rev[5875]

* Fri Sep  6 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 
- CHANGED: New Endpoint Configurator: add sip notify call to Digium 
  implementation in order to reboot the phone when not (yet) configured for
  DPMA.
  SVN Rev[5841]

* Wed Sep  4 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 0.0.3-0
- Bump version for release.
- CHANGED: New Endpoint Configurator: fix attempt to assign to immutable tuple
  that only raises an error in python 2.7
  SVN Rev[5835]
- CHANGED: New Endpoint Configurator: switch from python-json to python-cjson
  since the latter module exists in both CentOS and Fedora 17.
  SVN Rev[5834]
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
