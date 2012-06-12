%define modname a2billing
Summary: Package that installs A2Billing.
Name: elastix-%{modname}
Version: 1.9.4
Release: 5
License: GPL
Group: Applications/System
Source0: %{modname}_%{version}.tar.gz
Source1: %{modname}_%{version}-%{release}.tgz
Patch0:  elastix-a2billing-1.8.1.patch
BuildRoot: %{_tmppath}/%{name}-%{version}
BuildArch: noarch
Prereq: asterisk, php, elastix-firstboot, python-setuptools, MySQL-python, python-sqlalchemy		
Prereq: elastix-framework >= 2.2.0-18
Prereq: freePBX >= 2.8.1

%description
A2billing is a full featured telecom platform and softswitch providing converged services, with self contained billing (pre or post-paid), reporting and statistics for IP and TDM based voice networks and can be configured to supply a wide range of services, rate calls, prepare and send out invoices, as well as accept payments via a number of payment service providers.
%prep
%setup -n %{modname}
%patch0 -p1


%install

rm -rf    $RPM_BUILD_ROOT
mkdir -p  $RPM_BUILD_ROOT
mkdir -p  $RPM_BUILD_ROOT/var/www/html
mkdir -p  $RPM_BUILD_ROOT/var/www/html/a2billing
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/br	
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/br/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/fr
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/fr/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/gr
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/gr/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/ru
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/ru/digits
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/global
mkdir -p  $RPM_BUILD_ROOT/var/lib/asterisk/sounds/g729
mkdir -p  $RPM_BUILD_ROOT/etc
mkdir -p  $RPM_BUILD_ROOT/etc/cron.d
mkdir -p  $RPM_BUILD_ROOT/etc/asterisk
mkdir -p  $RPM_BUILD_ROOT/usr/share/a2billing
mkdir -p  $RPM_BUILD_ROOT/var/log
mkdir -p  $RPM_BUILD_ROOT/var/log/asterisk

#mv addons                                                   $RPM_BUILD_ROOT/usr/share/a2billing/
mv common                                                    $RPM_BUILD_ROOT/usr/share/a2billing/
mv admin                                                     $RPM_BUILD_ROOT/usr/share/a2billing/
mv agent                                                     $RPM_BUILD_ROOT/usr/share/a2billing/
mv customer                                                  $RPM_BUILD_ROOT/usr/share/a2billing/
mv AGI                                                       $RPM_BUILD_ROOT/usr/share/a2billing/
mv a2billing.conf                                            $RPM_BUILD_ROOT/etc/
mv CallBack                                                  $RPM_BUILD_ROOT/usr/share/a2billing/
mv Cronjobs                                                  $RPM_BUILD_ROOT/usr/share/a2billing/
mv DataBase                                                  $RPM_BUILD_ROOT/usr/share/a2billing/
mv webservice                                                $RPM_BUILD_ROOT/usr/share/a2billing/
tar -xvzf %{SOURCE1}
mv a2billing/setup/a2billing.cron                            $RPM_BUILD_ROOT/etc/cron.d/
mv a2billing/setup/extension_a2billing_additionals.conf      $RPM_BUILD_ROOT/etc/asterisk/
mv a2billing/*                                               $RPM_BUILD_ROOT/usr/share/a2billing/

mv addons/sounds/en/*                                        $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/
cp -r addons/sounds/global/*                                 $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/
mv addons/sounds/es/*                                        $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/
cp -r addons/sounds/global/*                                 $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/
mv addons/sounds/fr/*                                        $RPM_BUILD_ROOT/var/lib/asterisk/sounds/fr/
cp -r addons/sounds/global/*                                 $RPM_BUILD_ROOT/var/lib/asterisk/sounds/fr/
mv addons/sounds/br/*                                        $RPM_BUILD_ROOT/var/lib/asterisk/sounds/br/
cp -r addons/sounds/global/*                                 $RPM_BUILD_ROOT/var/lib/asterisk/sounds/br/
mv addons/sounds/ru/digits/*                                 $RPM_BUILD_ROOT/var/lib/asterisk/sounds/ru/digits/
mv addons/sounds/ru/*.gsm                                    $RPM_BUILD_ROOT/var/lib/asterisk/sounds/ru/

#revisar porque da conflicto el archivo a2billing.php en la instalacion 
cd $RPM_BUILD_ROOT/var/lib/asterisk/agi-bin/
ln -s  ../../../../usr/share/a2billing/AGI/a2billing.php  a2billing.php
ln -s ../../../../usr/share/a2billing/AGI/a2billing_monitoring.php  a2billing_monitoring.php

cd $RPM_BUILD_ROOT/var/www/html/a2billing/
ln -s ../../../../usr/share/a2billing/common    common
ln -s ../../../../usr/share/a2billing/admin     admin
ln -s ../../../../usr/share/a2billing/agent     agent
ln -s ../../../../usr/share/a2billing/customer  customer

chmod +x $RPM_BUILD_ROOT/var/log

# Conflicto con asterisk-sounds-es y asterisk
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/euro.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/euros.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/vm-and.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/en/vm-goodbye.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/dollars.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/euro.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/euros.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/peso.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/pesos.gsm
rm -f $RPM_BUILD_ROOT/var/lib/asterisk/sounds/es/vm-and.gsm

#Create Log Files
#Log files 
touch $RPM_BUILD_ROOT/var/log/asterisk/a2billing-daemon-callback.log
touch $RPM_BUILD_ROOT/var/log/a2billing-daemon-callback.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_alarm.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_autorefill.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_batch_process.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_bill_diduse.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_subscription_fee.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_currency_update.log
touch $RPM_BUILD_ROOT/var/log/cront_a2b_invoice.log
touch $RPM_BUILD_ROOT/var/log/a2billing_paypal.log
touch $RPM_BUILD_ROOT/var/log/a2billing_epayment.log
touch $RPM_BUILD_ROOT/var/log/api_ecommerce_request.log
touch $RPM_BUILD_ROOT/var/log/api_callback_request.log
touch $RPM_BUILD_ROOT/var/log/a2billing_agi.log
touch $RPM_BUILD_ROOT/etc/asterisk/additional_a2billing_iax.conf
touch $RPM_BUILD_ROOT/etc/asterisk/additional_a2billing_sip.conf


%pre
mkdir -p /usr/share/a2billing/
touch /usr/share/a2billing/version_a2billing.info
if [ $1 -eq 2 ]; then   
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' elastix-a2billing > /usr/share/a2billing/version_a2billing.info
fi

%post
#Add directory for monitoring Script
mkdir -p /var/lib/a2billing/script
mkdir -p /var/run/a2billing

LOAD_LOC=/usr/share/a2billing

#Callback
#Here is a little script to install the call-back Daemon. Change the LOAD_LOC variable to reflect where you have downloaded A2Billing.
#Callback Daemon installation Script

#mkdir -p /usr/bin/a2b_callback_daemon

#easy_install sqlalchemy
#cp $LOAD_LOC/CallBack/callback-daemon-py/callback_daemon/a2b-callback-daemon.rc /etc/init.d/a2b-callback-daemon
#chmod +x /etc/init.d/a2b-callback-daemon

#tar -xvzf $LOAD_LOC/CallBack/callback-daemon-py/dist/callback_daemon-1.0.prod-r1528.tar.gz -C /tmp/
#cd /tmp/callback_daemon-1.0.prod-r1528/dist/
#python setup.py build
#python setup.py bdist_egg
#easy_install dist/callback_daemon-1.0.prod_r1527-py2.5.egg
#chkconfig --add a2b-callback-daemon
#chkconfig a2b-callback-daemon on
#service a2b-callback-daemon start


# verificando inclusiones en sip.conf e iax.conf
echo "verifying the inclusion of additional_a2billing_sip.conf in /etc/asterisk/sip.conf"
grep "#include additional_a2billing_sip.conf" /etc/asterisk/sip.conf &> /dev/null
if [ $? -eq 1 ]; then
   echo "including additional_a2billing_sip.conf in /etc/asterisk/sip.conf..."
   echo "" >> /etc/asterisk/sip.conf
   echo \#include additional_a2billing_sip.conf >> /etc/asterisk/sip.conf
   echo "" >> /etc/asterisk/sip.conf
else
   echo "additional_a2billing_sip.conf in /etc/asterisk/sip.conf is already included"
fi

echo "verifying the inclusion of additional_a2billing_iax.conf in /etc/asterisk/iax.conf"
grep "#include additional_a2billing_iax.conf" /etc/asterisk/iax.conf &> /dev/null
if [ $? -eq 1 ]; then
   echo "including additional_a2billing_iax.conf in /etc/asterisk/iax.conf..."
   echo "" >> /etc/asterisk/iax.conf
   echo \#include additional_a2billing_iax.conf >> /etc/asterisk/iax.conf
   echo "" >> /etc/asterisk/iax.conf
else
   echo "additional_a2billing_iax.conf in /etc/asterisk/iax.conf is already included"
fi

# verificando inclusiones en extensions.conf de extension_a2billing_additionals.conf
echo "verifying the inclusion of extension_a2billing_additionals.conf in /etc/asterisk/extensions.conf"
grep "#include extension_a2billing_additionals.conf" /etc/asterisk/extensions.conf &> /dev/null
if [ $? -eq 1 ]; then
   echo "including extension_a2billing_additionals.conf in /etc/asterisk/extensions.conf..."
   echo "" >> /etc/asterisk/extensions.conf
   echo \#include extension_a2billing_additionals.conf >> /etc/asterisk/extensions.conf
   echo "" >> /etc/asterisk/extensions.conf
else
   echo "extension_a2billing_additionals.conf in /etc/asterisk/extensions.conf is already included"
fi


# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge "$LOAD_LOC/menu.xml"

#process databases
versiona2billing=`cat $LOAD_LOC/version_a2billing.info`

/sbin/service mysqld status &>/dev/null
is_mysqld_run=$?

if [ $1 -eq 1 ]; then #install
# The installer database
  elastix-dbprocess "install" "$LOAD_LOC/setup/db"
  if [ $is_mysqld_run -eq 0 ]; then # la base de datos esta corriendo
      php /usr/share/a2billing/setup/changeencodepass.php #se cambia las contraseñas de los usuarios a la codificacion definida 
  else
      echo "Service MySQL is stop. A2billing database wasn't installed,"
      echo "please execute php \"/usr/share/a2billing/setup/changeencodepass.php\""
      echo "after mya2billing database have been installed."
  fi
elif [ $1 -eq 2 ]; then #update
# The installer database
  elastix-dbprocess "update" "$LOAD_LOC/setup/db" "$versiona2billing"
#se coloca changeencodepass.php mas abajo debido a que es mejor ejecutar el script de sql cuando este corriendo el mysql
#Si mysql esta apagado hay problemas de que no se pueda realizar de forma correcta la transaccion sql
  if [ $is_mysqld_run -eq 0 ]; then # la base de datos esta corriendo                 
      php /usr/share/a2billing/setup/changeencodepass.php #se cambia las contraseñas de los usuarios a la codificacion definida
  else
      echo "Service MySQL is stop. A2billing database wasn't installed," 
      echo "please execute php \"/usr/share/a2billing/setup/changeencodepass.php\"" 
      echo "after mya2billing database have been installed."
  fi
fi

%preun
LOAD_LOC=/usr/share/a2billing
if [ $1 -eq 0 ] ; then # delete
  echo "Delete A2Billing menu"
  elastix-menuremove "a2b"

  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$LOAD_LOC/setup/db"
fi

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, asterisk, asterisk)
/var/www/html/*
/etc/cron.d/*
/var/lib/asterisk/sounds/*
/var/log/*
/var/log/asterisk/*
/var/lib/asterisk/agi-bin/*
%defattr(755, asterisk, asterisk)
/usr/share/a2billing/*
%config(noreplace) /etc/asterisk/additional_a2billing_sip.conf
%config(noreplace) /etc/asterisk/additional_a2billing_iax.conf
%config(noreplace) /etc/asterisk/extension_a2billing_additionals.conf
%config(noreplace) /etc/a2billing.conf

%changelog
* Thu May 31 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 1.9.4-5
- FIXED: Fix missing semicolon in a comment in file 
  extension_a2billing_additionals.conf. Fixes Elastix bug #1283.

* Thu Apr 26 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 1.9.4-4
- FIXED: Use commas instead of pipes in order to comply with Asterisk 1.6+ 
  context syntax. Fixes Elastix bug #1248.

* Mon Apr 02 2012 Bruno Macias <bmacias@palosanto.com> 1.9.4-3
- UPDATED: script setup/changeencodepass.php was updated for support
  change manager asterisk config username and password for a2billing
  configure.

* Mon Apr 02 2012 Bruno Macias <bmacias@palosanto.com> 1.9.4-2
- FIXED: Bug config files additional_a2billing_xxx.conf were changed.
  In this spec add noreplace option.
- ADDED: Prereq freePBX, It is used by manager user "admin" for edit
  config file additional_a2billing_xxx.conf.
- ADDED: Pacth0 added, change user mya2billing to admin in config file.
  variable fakeuser.

* Fri Dec 02 2011 Eduardo Cueva <ecueva@palosanto.com> 1.9.4-1
- CHANGED: In spec files changes to support updating of a2billing
- UPDATED: In elastix-a2billing-1.8.1.patch add the port by default
- UPDATED: Update a2billing to 1.9.4.
- CHANGED: Modules - Extra: Changes in a2billing to fix the bug with 
  user "root" and password without encode. SVN Rev[3418]

* Fri Nov 25 2011 Eduardo Cueva <ecueva@palosanto.com> 1.8.1-17
- CHANGED: In spec file changed Prereq elastix to 
  elastix-framework >= 2.2.0-18

* Wed May 04 2011 Eduardo Cueva <ecueva@palosanto.com> 1.8.1-16
- CHANGED: Changed file db.info the action installation_force by
  ignore_backup

* Sat Apr 02 2011 Eduardo Cueva <ecueva@palosanto.com> 1.8.1-15
- FIXED: a2billing menus.xml, bad definition type menu a2b, it
  most be module. SVN Rev[2484]

* Sat Apr 02 2011 Bruno Macias <bmacias@palosanto.com> 1.8.1-14
- FIXED: a2billing - database, It isn't installed because logic part 
  when mysql service not run not defined.

* Thu Mar 31 2011 Eduardo Cueva <ecueva@palosanto.com> 1.8.1-13beta
- CHANGED: a2billing - database, Script sql of installation was 
  improved, the change was to define the correct user by
  mya2billing db. SVN Rev[2476]

* Mon Feb 14 2011 Eduardo Cueva <ecueva@palosanto.com> 1.8.1-12beta
- CHANGED: In spec file using new format to elastix-dbprocess.

* Fri Oct 29 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-11beta
- FIXED : anteriormente a funcion executeFiles_SQL_update recibio los argumentos (stringsql, password, action, sql_export), pero ahora recibe los parametros
          (fileupdate, password, action, sql_export), donde cambio en la funcionalidad de que se recibe un archivo en lugar de una cadena.

* Thu Oct 28 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-10beta
- FIXED :  now exist a new file source caller a2billing-db.tar.gz, this file, create,update,the database, 
    also this file have the xml file, thay before this was writting in this spec, all information is read
    of this file.
* Mon Oct 18 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-9beta
- FIXED :  the new is that, change the way of update database now is calling to the dbprocess using a script of php to update databases.

* Sat Oct 16 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-8beta
- ADDED :  The new is that i put symlinks for that the file a2billing.php,a2billing_monitoring.php, can be seen in the directory: /         /var/lib/asterisk/agi-bin/

* Thu Oct 14 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-7beta
- ADDED : The new is that i put symlinks for that the folder admin,customer,agent, common, can be seen in the directory: /var/www/html/

* Thu Oct 14 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-6beta
- FIXED : The change was that the file log now this are created in the install part with their Permissions respectly. 

* Thu Oct 14 2010 Bruno Macias <bmacias@palosanto.com> 1.8.1-5beta
- ADDED : Prered elastix 2.0.0-42

* Thu Oct 14 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-4beta
- FIXED: the change was that before a file was done with "echo" and put it on the spool direction, but now it creates a file called filecron.cron and copies it to the install

* Mon Oct 11 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-3beta
- FIXED: The update that I did since I did the 1.4.0 version, and as the elastix this since version 1.3.0, so I had to add the updates since version 1.3.0
   to version 1.4.0.

* Fri Oct 08 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-2beta
- FIXED: the route was added in the function: generarDSNSistema ('root', 'mya2billing'), the function now is: generarDSNSistema ('root', 'mya2billing', "$ libsPath /"), which this return the user's password.

* Fri Oct 02 2010 Kleber Loayza <andresloa@palosanto.com> 1.8.1-1beta
- Update a2billing version to 1.8.1.

* Thu Sep 03 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.3.0-4
- Replace dependency on elastix-mysqldbdata with elastix-firstboot.

* Mon May 25 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.3.0-3
- Remove obsolete dependency on elastix-additionals
- Fix installation of sound files, which should be done at %%install instead of %%post time.
- Fix creation of various directories, which should be done at %%install instead of %%post time.

* Wed Mar 18 2009 Bruno Macias <bmacias@palosanto.com> 1.3.0-2
  - Validation exists directory /var/lib/asterisk/sounds/es and /var/lib/asterisk/sounds/fr

* Thu Apr 3 2008 Bruno Macias <bmacias@palosanto.com> 1.3.0-1
  - Validation exists directory /var/lib/asterisk/sounds/en/.
  - Version rpm fixed, now related wiht version a2billing.

* Thu Apr 3 2008 Bruno Macias <bmacias@palosanto.com> 0.8-5
  -Back version  






















