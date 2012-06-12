%{!?kernel: %{expand: %%define kernel %(uname -r)}}
%define kxensufix %(if [ `uname -r | grep -E "xen$" | wc -l` == 1 ]; then echo "-xen"; fi)
Summary: Open Source Line Echo Canceller (OSLEC)
Name: oslec
Version: 0.1
Release: 12
License: GPL
Group: System Environment/Libraries
URL: http://www.rowetel.com/ucasterisk/oslec
Source0: oslec-0.1.tar.gz 
Source1: oslec.init
Prereq: kernel-module-zaptel, kernel-module-zaptel-xen
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildRequires: /bin/uname

%description
Oslec is an open source high performance line echo canceller. When used with Asterisk
it works well on lines where the built-in Zaptel echo canceller fails. No tweaks 
like rxgain/txgain or fxotrain are required. Oslec is supplied as GPL licensed 
C source code and is free as in speech.

%package -n kernel-module-oslec%{kxensufix}
Summary: Kernel modules required for OSLEC
Group: System Environment/Kernel
Requires: /sbin/depmod

%description -n kernel-module-oslec%{kxensufix}
This package contains the OSLEC kernel modules for the Linux kernel package.

%prep
%setup

%build
%{__make} 

%install
%{__rm} -rf %{buildroot}
%{__mkdir_p} %{buildroot}/lib/modules/%{kernel}/oslec
%{__mkdir_p} %{buildroot}/sbin
%{__mkdir_p} %{buildroot}/etc/init.d
%{__mv} kernel/oslec.ko %{buildroot}/lib/modules/%{kernel}/oslec/
%{__mv} kernel/oslec-ctrl-panel.sh %{buildroot}/sbin/
/bin/cp -a %{SOURCE1} %{buildroot}/etc/init.d/oslec

%clean
%{__rm} -rf %{buildroot}

%post 
/sbin/chkconfig --add oslec
/sbin/chkconfig --level 2345 oslec on

%post -n kernel-module-oslec%{kxensufix}
/sbin/depmod -a -F /boot/System.map-%{kernel} %{kernel} &>/dev/null || :

%postun -n kernel-module-oslec%{kxensufix} 
/sbin/depmod -a -F /boot/System.map-%{kernel} %{kernel} &>/dev/null || :

%files 
%defattr(-, root, root, 0755)
%config(noreplace) /etc/init.d/oslec
/sbin/*

%files -n kernel-module-oslec%{kxensufix}
%defattr(-, root, root, 0755)
/lib/modules/%{kernel}/oslec/oslec.ko

%changelog
* Thu Oct 20 2007 Edgar Landivar <elandivar@palosanto.com>0.1-12
- Added missing depmod command
- Split this package into two RPMs. This creates an additional kernel-modules
  package which is platform dependent

* Thu Oct 19 2007 Edgar Landivar <elandivar@palosanto.com>0.1-10
- Little fix in the init script header that makes it incompatible with chkconfig

* Thu Oct 18 2007 Edgar Landivar <elandivar@palosanto.com>0.1-7
- Init script added

* Tue Sep 11 2007 Edgar Landivar <elandivar@palosanto.com>0.1-6
- Initial RPM release.

