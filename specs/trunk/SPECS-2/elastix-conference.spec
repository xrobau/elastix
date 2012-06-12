%define modname conference

Summary: Elastix Conference
Name:    elastix-conference
Version: 2.0.0
Release: 5
License: GPL
Group:   Applications/System
Source0: %{modname}_%{version}-%{release}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix >= 2.0.0-20
Requires: asterisk
Requires: freePBX
Requires: php-mbstring

%description
Elastix Conference
 Modulo modificado de Conferencia para Hubox

%prep
%setup -n %{modname}

%install
rm -rf $RPM_BUILD_ROOT

# Files provided by all Elastix modules
mkdir -p    $RPM_BUILD_ROOT/var/www/html/
mkdir -p    $RPM_BUILD_ROOT/var/lib/asterisk/
mv modules/ $RPM_BUILD_ROOT/var/www/html/
mv agi-bin/ $RPM_BUILD_ROOT/var/lib/asterisk/
mv sounds/en/	$RPM_BUILD_ROOT/var/lib/asterisk/sounds/
mv sounds/es/	$RPM_BUILD_ROOT/var/lib/asterisk/sounds/


# The following folder should contain all the data that is required by the installer,
# that cannot be handled by RPM.
mkdir -p    $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv setup/   $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv menu.xml $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv CHANGELOG $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

%post

# Run installer script to fix up ACLs and add module to Elastix menus.
elastix-menumerge /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/menu.xml

# The installer script expects to be in /tmp/new_module
mkdir -p /tmp/new_module/%{modname}
cp -r /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/* /tmp/new_module/%{modname}/
chown -R asterisk.asterisk /tmp/new_module/%{modname}

php /tmp/new_module/%{modname}/setup/installer.php
#reload asterisk
asterisk -rx 'reload'
rm -rf /tmp/new_module

# Fix possible conflicts with Smarty Templates
rm -rf /var/www/html/var/templates_c/*.*

%clean
rm -rf $RPM_BUILD_ROOT

%preun
if [ $1 -eq 0 ] ; then # Check to tell apart update and uninstall
  # Workaround for missing elastix-menuremove in old Elastix versions (before 2.0.0-20)
  if [ -e /usr/bin/elastix-menuremove ] ; then
    echo "Removing Conference menus..."
    elastix-menuremove "conference"
  else
    echo "No elastix-menuremove found, might have stale menu in web interface."
  fi
 
fi

%files
%defattr(-, asterisk, asterisk)
%{_localstatedir}/www/html/*
%{_localstatedir}/lib/asterisk/*
/usr/share/elastix/module_installer/*


%changelog
* Fri Jul 08 2011 Andres Pera <apera@palosanto.com> 2.0.0-5
- Se añadio el campo de fecha de inicio en la creación de cuentas VIP.
- Se modificó el contexto para que se reproduzca un audio (conference-reservations) si ingresa una conferencia que todavía no empieza.

* Tue Jun 21 2011 Andres Pera <apera@palosanto.com> 2.0.0-4
- Se agregaron los audios para la notificacion de entrada al helpdesk. conf-hdesk

* Mon Jun 20 2011 Andres Pera <apera@palosanto.com> 2.0.0-3
- Se arreglo el problema que al ingresar el usuario 0 se ingresaba automaticamente a la conferencia, a pesar que el usuario tiene defino otro valor.
- Se agrego el problema que al tercer intento de ingresar la sala se cuelga, ahora va al helpdesk.
- Se modifico la entrada del numero de la sala, para que espere 6 digitos de modo que se incluya el #

* Wed Jun 08 2011 Andres Pera <apera@palosanto.com> 2.0.0-2
- Se agrego la opcion para que los participantes escuchen musica en espera si son los unicos.
- Se quito el audio de auth-incorrect
- Se corrigio un bug al regresar del helpdesk. Antes se colgaba, ahora pide el usuario de nuevo.

* Fri May 20 2011 Andres Pera <apera@palosanto.com> 2.0.0-1
- Initial version.
