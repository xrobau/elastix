Summary: LCDproc client for Elastix status display
Name: lcdelastix
Version: 4.0.0
Release: 3
License: GPL
Group: Applications/System
Source0: lcdelastix-%{version}.tar.bz2
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot
BuildArch: noarch
Requires: php >= 5.0.0
Requires: elastix-framework
Requires: lcdproc >= 1:0.5.6-1
# for lsusb dependency
Requires: usbutils

%description

This is a daemon and a set of utilities that implement the Elastix status
output in the LCD display of Elastix appliances. This daemon should not be
running on a device without a LCD display, unless you know what you are doing.

%prep
%setup -n lcdelastix

%install
rm -rf %{buildroot}
mkdir -p  %{buildroot}/opt/lcdelastix/
rm -f *.spec
mkdir -p %{buildroot}%{_bindir}
install -m 0755 elastix-configure-lcd  %{buildroot}%{_bindir}
rm -f elastix-configure-lcd
cp -r * %{buildroot}/opt/lcdelastix/
mkdir -p %{buildroot}%{_sysconfdir}/rc.d/init.d
install -m 0755 %{buildroot}/opt/lcdelastix/lcdelastix  %{buildroot}%{_sysconfdir}/rc.d/init.d/lcdelastix

%clean
rm -rf %{buildroot}

%pre

%post
# Add LCDd service, but do not enable it. Prevents program from unnecessarily
# running on a system without a LCD display
/sbin/chkconfig --add lcdelastix
if [ $1 -eq 1 ] ; then # install
    /sbin/chkconfig lcdelastix off
fi

%preun
if [ $1 -eq 0 ] ; then # Check to tell apart update and uninstall
	/sbin/chkconfig --del lcdelastix
fi

%files
%defattr(-,root,root,-)
%{_sysconfdir}/rc.d/init.d/lcdelastix
%{_bindir}/elastix-configure-lcd
%defattr(-,asterisk,asterisk,-)
/opt/lcdelastix
/opt/lcdelastix/*

%changelog
* Thu Jun 23 2016 Alex Villacis Lasso <a_villacis@palosanto.com> 4.0.0-3
- Bump version for release
- FIXED: lcdelastix: add Requires: usbutils for lsusb.
  SVN Rev[7635]
- FIXED: lcdelastix: do not disable lcdelastix service on every update.
  SVN Rev[7634]
- FIXED: lcdelastix: do not install two copies of elastix-configure-lcd.
  SVN Rev[7633]
- FIXED: lcdelastix: in Elastix 4, lsusb is at /usr/bin, not /sbin. The script
  elastix-configure-lcd now tries several paths until one is found. Also the
  return value of lsusb is actually examined.
  SVN Rev[7632]

* Fri Apr 22 2016 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: lcdelastix: check whether /etc/localtime is a symlink and use it as
  an additional way to find out the current timezone.
  SVN Rev[7603]

* Tue Oct 27 2015 Alex Villacis Lasso <a_villacis@palosanto.com> 4.0.0-2
- Bump version for release.

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: lcdelastix: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7240]

* Tue Sep 29 2015 Alex Villacis Lasso <a_villacis@palosanto.com> 4.0.0-1
- Bump version for release.

* Wed Aug 28 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 1.4.1-0
- Bump version for release.
- CHANGED: Add and require support for setting logo in jbc1602 case.
  SVN Rev[5815]
- FIXED: Use correct units in memory applet in one line case.
  SVN Rev[5814]

* Fri Jul 26 2013 Alex Villacis Lasso <a_villacis@palosanto.com> 1.4.0-0
- CHANGED: Modify crystalfontz.pl to accept an optional argument to point the
  LCD character device.
  SVN Rev[5432]
- ADDED: Add new script elastix-configure-lcd to ease setting up of a LCD.
  SVN Rev[5430]
- CHANGED: Make the LCD applets Elastix 3-aware.
  SVN Rev[5425]
- FIXED: Correct owner of /opt/lcdelastix. Fixes Elastix bug #1639.
- CHANGED: Rewrite main application to make proper use of LCDProc menus. Rework
  applets to be capable of having a compact version which will be used with
  displays of less than 3 lines. Fix the CPU usage code to calculate the CPU
  usage correctly.
  SVN Rev[5422]

* Wed Mar 14 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 1.3.0-1
- FIXED: additionals - lcdelastix/lcdapplets/ch.php: Se muestra mensaje de error
  en el shell cuando se accede a PBX Activity>Concurr Channels con el LCD del
  appliance. Bug 0001098. SVN Rev[3528]

* Tue Mar 13 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 1.3.0-0
- Forked lcdelastix to separate package lcdelastix

