%define version 0.1.14

Summary: Software modem for interfacing Asterisk and Hylafax via IAX2
Name: iaxmodem
Version: %{version}
Release: 1%{?dist}
License: GPL
Group: Applications/Communications
Url: https://sourceforge.net/projects/iaxmodem
Source0: http://prdownloads.sourceforge.net/iaxmodem/iaxmodem-%{version}.tar.gz
Vendor: Lee Howard <faxguy@howardsilvan.com>
Packager: Laimbock Consulting <asterisk@laimbock.com>
Buildroot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Requires: asterisk >= %{astver}

%description
IAXmodem is a software modem written in C that uses an IAX channel
(commonly provided by an Asterisk PBX system) instead of a traditional
phone line and uses a DSP library instead of DSP hardware chipsets.

To accomplish this, then, IAXmodem interfaces an IAX library known as
libiax2 with a DSP library known as spandsp, and then IAXmodem interfaces
the DSP library with a tty device node for interfacing with modem
applications.

%prep
%setup

%build
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

pushd lib/libiax2
%configure --disable-shared
%{__make} %{?_smp_mflags}
popd

pushd lib/spandsp
%configure --disable-shared
%{__make} %{?_smp_mflags}
popd

# set the variables
MODEMVER=iaxmodem-0.1.14
STEP1=`grep @VERSION@ lib/spandsp/config.status | sed 's/;.*//g'`
DSPVER=`echo "@VERSION@" | sed $STEP1`
if [ -n "$DSPVER" ]; then
    DSPVER="spandsp-$DSPVER-snapshot-20060707+"
fi
STEP1=`grep @VERSION@ lib/libiax2/config.status | sed 's/;.*//g'`
IAXVER=`echo "@VERSION@" | sed $STEP1`
if [ -n "$IAXVER" ]; then
    IAXVER="libiax2-$IAXVER-CVS-20060222+"
fi

# build a static version of iaxmodem
gcc $RPM_OPT_FLAGS -Wall -g -DMODEMVER=\"$MODEMVER\" -DDSPVER=\"$DSPVER\" -DIAXVER=\"$IAXVER\" -DSTATICLIBS -D_GNU_SOURCE -std=c99 -Ilib/libiax2/src -Ilib/spandsp/src -c -o iaxmodem.o iaxmodem.c
#" <- just here to keep the vi syntax highlighting sane
gcc -lm -lutil -ltiff -o iaxmodem iaxmodem.o lib/spandsp/src/.libs/libspandsp.a lib/libiax2/src/.libs/libiax.a

%install
# install the bunch manually
%{__install} -D -m 755 iaxmodem %{buildroot}%{_sbindir}/iaxmodem
%{__install} -D -m 644 iaxmodem.1 %{buildroot}%{_mandir}/man1/iaxmodem.1
%{__install} -D -m 660 config.ttyIAX %{buildroot}%{_localstatedir}/spool/hylafax/etc/config.ttyIAX
%{__install} -D -m 644 iaxmodem-cfg.ttyIAX %{buildroot}%{_sysconfdir}/iaxmodem/iaxmodem-cfg.ttyIAX
perl -pi -e 's,/usr/local/,/usr/,g' iaxmodem.init.fedora
%{__install} -D -m 755 iaxmodem.init.fedora %{buildroot}%{_initrddir}/iaxmodem

%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}

%files
%defattr(-,root,root)
%doc CHANGES FAQ README TODO
%attr(750,asterisk,asterisk)				%{_sbindir}/iaxmodem
%attr(0644,root,root)					%{_mandir}/man1/iaxmodem.1.gz
%attr(0660,root,asterisk)	%config(noreplace)	%{_localstatedir}/spool/hylafax/etc/config.ttyIAX
%attr(0660,root,asterisk)	%config(noreplace)	%{_sysconfdir}/iaxmodem/iaxmodem-cfg.ttyIAX
%attr(0755,root,root)					%{_initrddir}/iaxmodem

%changelog
* Thu Aug 17 2006 Laimbock Consulting <asterisk@laimbock.com> 0.1.14-1
- inital spec file based on 0.1.14
- static build so it does not interfere with spandsp

