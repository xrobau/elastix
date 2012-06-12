Summary: The Open Source Linux PBX
Name: asterisk
Version: 1.4.13
Release: 2
Epoch: 1
License: GPL
Group: Applications/Internet
URL: http://www.asterisk.org
Source0: http://ftp.digium.com/pub/%{name}/releases/%{name}-%{version}.tar.gz
Source1: http://www.soft-switch.org/downloads/spandsp/spandsp-0.0.2pre26/asterisk-1.2.x/app_rxfax.c
Source2: http://www.soft-switch.org/downloads/spandsp/spandsp-0.0.2pre26/asterisk-1.2.x/app_txfax.c
#Source101: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-alaw-1.4.6.tar.gz
#Source102: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-g722-1.4.6.tar.gz
#Source103: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-g729-1.4.6.tar.gz
Source104: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-gsm-1.4.6.tar.gz
#Source105: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-ulaw-1.4.6.tar.gz
#Source106: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-en-wav-1.4.6.tar.gz
#Source107: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-alaw-1.4.6.tar.gz
#Source108: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-g722-1.4.6.tar.gz
#Source109: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-g729-1.4.6.tar.gz
Source110: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-gsm-1.4.6.tar.gz
#Source111: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-ulaw-1.4.6.tar.gz
#Source112: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-es-wav-1.4.6.tar.gz
#Source113: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-alaw-1.4.6.tar.gz
#Source114: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-g722-1.4.6.tar.gz
#Source115: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-g729-1.4.6.tar.gz
Source116: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-gsm-1.4.6.tar.gz
#Source117: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-ulaw-1.4.6.tar.gz
#Source118: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-core-sounds-fr-wav-1.4.6.tar.gz
#Source119: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-alaw-1.4.5.tar.gz
#Source120: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-g722-1.4.5.tar.gz
#Source121: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-g729-1.4.5.tar.gz
Source122: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-gsm-1.4.5.tar.gz
#Source123: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-ulaw-1.4.5.tar.gz
#Source124: http://ftp.digium.com/pub/telephony/sounds/releases/asterisk-extra-sounds-en-wav-1.4.5.tar.gz
#Patch0: asterisk-bri.patch
Patch1: asterisk-1.4.0-beta3-user.patch
Patch2: asterisk-1.0.7-mpg123.patch
Patch3: asterisk-1.4-spandsp.patch
Patch4: asterisk-rc-chown.patch
#Patch5: asterisk-1.4-palosanto1.patch
#Patch6: asterisk-hold.patch
Patch7: asterisk-unicall.patch
#Patch8: chan_unicall.msilva.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-buildroot
BuildRequires: gcc-c++
BuildRequires: bison, m4
BuildRequires: openssl-devel
BuildRequires: newt-devel
BuildRequires: libtermcap-devel, ncurses-devel
BuildRequires: postgresql-devel, postgresql-server, zlib-devel
BuildRequires: libpri-devel >= 1.4.0
BuildRequires: zaptel-devel >= 1.4.0, libtiff-devel >= 3.5.7
BuildRequires: libogg-devel, libvorbis-devel
BuildRequires: unixODBC-devel, libtool, sqlite-devel
# BuildRequires: freetds-devel
BuildRequires: pwlib-devel > 1.10.5-2, opal-devel
BuildRequires: qt-devel, kdelibs-devel, gtk+-devel
BuildRequires: alsa-lib-devel
BuildRequires: gsm-devel
BuildRequires:	net-snmp-devel
BuildRequires: spandsp-devel
BuildRequires: curl-devel, wget
BuildRequires: speex-devel >= 1.2

Obsoletes: asterisk-sounds

%description
Asterisk is a complete PBX in software. It runs on Linux and provides
all of the features you would expect from a PBX and more. Asterisk
does voice over IP in three protocols, and can interoperate with
almost all standards-based telephony equipment using relatively
inexpensive hardware.

%package devel
Summary: Development package for %{name}
Group: Development/Libraries
Requires: asterisk = %{epoch}:%{version}
Requires: spandsp-devel

#%package chan_misdn
#Summary: The mISDN channel driver for %{name}
#Group: System/Drivers
#BuildRequires: mISDN-devel = %{misdnver}
#BuildRequires: mISDNuser-devel = %{misdnuserver}
#Requires: mISDN = %{misdnver}
#Requires: mISDNuser = %{misdnuserver}
#Requires: asterisk >= %{version}
#
#%description chan_misdn
#The mISDN channel driver for Asterisk to be used with ISDN cards with
#the Cologne chipset (HFC-S based ISDN cards).

%description devel
Use this package for building/developing applications against %{name}.

%package sounds-es
Summary: Spanish language sound files for %{name}
Group: Applications/Internet
Requires: asterisk => %{epoch}:%{version}

%description sounds-es
Spanish language sound files for %{name}

%package sounds-fr
Summary: French language sound files for %{name}
Group: Applications/Internet
Requires: asterisk => %{epoch}:%{version}

%description sounds-fr
French language sound files for %{name}

%prep
%setup -q
for file in %{SOURCE104} %{SOURCE110} %{SOURCE116} %{SOURCE122}
do
  (cd sounds; cat $file | gzip -d | tar xf -) && \
  touch sounds/`basename $file | sed -e's,\(.*\).tar.gz,.\1,'`
done
#patch0 -p1 -b .bri
%patch1 -p0 -b .user
%patch2 -p1 -b .mpg123
%patch3 -p1 -b .spandsp
%patch4 -p0
#%patch5 -p1
#%patch6 -p1
%patch7 -p1
#%patch8 -p0
cp %{SOURCE1} %{SOURCE2} apps/
find . -type f | xargs grep -l /usr/lib/ | xargs perl -pi -e's,/usr/lib/,%{_libdir}/,'
perl -pi -e's,^OPTIMIZE.*,,' Makefile
perl -pi -e's,^.*-march.*,,g' Makefile
grep -rl '# include <linux/compiler.h>' . | xargs perl -pi -e's,# include <linux/compiler.h>,/* # include <linux/compiler.h> */,'

%build
for a in rc.debian.asterisk rc.gentoo.asterisk rc.mandrake.asterisk rc.mandrake.zaptel; do
    rm -f contrib/init.d/$a
done
%configure --with-misdn
OPTIMIZE="%{optflags}" make

%install
rm -rf %{buildroot}
make install DESTDIR=%{buildroot} ASTVARRUNDIR=%{_var}/run/asterisk
make samples DESTDIR=%{buildroot} ASTVARRUNDIR=%{_var}/run/asterisk
mkdir -p %{buildroot}%{_initrddir}
install -m 0644 include/asterisk.h %{buildroot}%{_includedir}/asterisk.h
install -p contrib/init.d/rc.redhat.asterisk %{buildroot}%{_initrddir}/asterisk

%{__mkdir_p} %{buildroot}%{_localstatedir}/lib/asterisk/sounds/{es,fr}

tar zxvf %{SOURCE110} -C %{buildroot}%{_localstatedir}/lib/asterisk/sounds/es
tar zxvf %{SOURCE116} -C %{buildroot}%{_localstatedir}/lib/asterisk/sounds/fr

# Asterisk looks for these ones in 
# %{_localstatedir}/lib/asterisk/sounds/digits using spanish sounds

for f in \
	1M.gsm 100-and.gsm 1F.gsm 200.gsm 20-and.gsm 21.gsm 22.gsm 23.gsm 24.gsm \
	25.gsm 26.gsm 27.gsm 28.gsm 29.gsm 300.gsm 400.gsm 500.gsm 600.gsm \
	700.gsm 800.gsm 900.gsm afternoon.gsm and.gsm at_s.gsm es-de.gsm \
	millions.gsm es-el.gsm
do
	ln -s  \
	../es/digits/${f} \
	%{buildroot}%{_localstatedir}/lib/asterisk/sounds/digits/
done

for f in \
	vm-INBOXs.gsm vm-Olds.gsm vm-youhaveno.gsm
do
	ln -s  \
	es/${f} \
	%{buildroot}%{_localstatedir}/lib/asterisk/sounds/
done

%clean
rm -rf %{buildroot}

%pre
# Add the "asterisk" user
/usr/sbin/useradd -r -c "Asterisk PBX" \
        -s /sbin/nologin -d %{_localstatedir}/lib/asterisk asterisk 2> /dev/null || :

%post
# Register the asterisk service
/sbin/chkconfig --add asterisk

%preun
if [ $1 = 0 ]; then
        /sbin/service asterisk stop > /dev/null 2>&1
        /sbin/chkconfig --del asterisk
fi

%files
%defattr(-,root,root,-)
%doc README* *.txt ChangeLog BUGS CREDITS doc/* configs contrib
%{_initrddir}/asterisk
%{_sbindir}/aelparse
%{_sbindir}/asterisk
%{_sbindir}/astgenkey
%{_sbindir}/safe_asterisk
%{_sbindir}/astman
%{_sbindir}/autosupport
%{_sbindir}/muted
%{_sbindir}/rasterisk
%{_sbindir}/smsq
%{_sbindir}/stereorize
%{_sbindir}/streamplayer
%{_libdir}/asterisk
%{_mandir}/man8/asterisk.8*
%{_mandir}/man8/astgenkey.8*
%{_mandir}/man8/autosupport.8*
%{_mandir}/man8/safe_asterisk.8*

%defattr(-,asterisk,asterisk,-)
%dir %{_sysconfdir}/asterisk
%config(noreplace) %{_sysconfdir}/asterisk/*
%{_localstatedir}/lib/asterisk
%{_localstatedir}/run/asterisk
%{_localstatedir}/spool/asterisk
%{_localstatedir}/log/asterisk
# These simbolic links go in sounds-es
%exclude %{_localstatedir}/lib/asterisk/sounds/vm-INBOXs.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/vm-Olds.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/vm-youhaveno.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/es
%exclude %{_localstatedir}/lib/asterisk/sounds/fr
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/1M.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/100-and.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/1F.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/200.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/20-and.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/21.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/22.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/23.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/24.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/25.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/26.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/27.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/28.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/29.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/300.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/400.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/500.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/600.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/700.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/800.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/900.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/afternoon.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/and.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/at_s.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/es-de.gsm
%exclude %{_localstatedir}/lib/asterisk/sounds/digits/millions.gsm


%files devel
%defattr(-,root,root,-)
%{_includedir}/asterisk.h
%{_includedir}/asterisk/

%files sounds-es
%defattr(-,root,root,-)
%{_localstatedir}/lib/asterisk/sounds/es
%{_localstatedir}/lib/asterisk/sounds/vm-INBOXs.gsm
%{_localstatedir}/lib/asterisk/sounds/vm-Olds.gsm
%{_localstatedir}/lib/asterisk/sounds/vm-youhaveno.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/1M.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/100-and.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/1F.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/200.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/20-and.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/21.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/22.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/23.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/24.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/25.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/26.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/27.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/28.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/29.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/300.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/400.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/500.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/600.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/700.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/800.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/900.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/afternoon.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/and.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/at_s.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/es-de.gsm
%{_localstatedir}/lib/asterisk/sounds/digits/millions.gsm

%files sounds-fr
%defattr(-,root,root,-)
%{_localstatedir}/lib/asterisk/sounds/fr

%changelog
* Thu Oct 18 2007 Edgar Landivar <e_landivar@palosanto.com>
- Update to 1.4.13

* Mon Oct  8 2007 Edgar Landivar <e_landivar@palosanto.com>
- Update to 1.4.12.1

* Wed Oct  3 2007 Edgar Landivar <e_landivar@palosanto.com>
- Update to 1.4.12

* Mon Jun 11 2007 Edwin Boza <www.palosanto.com>
- Chage owner to devices

* Wed Mar 21 2007 Joel Barrios <http://joel-barrios.blogspot.com/>
- Update 1.4.2

* Sat Mar 17 2007 Joel Barrios <http://joel-barrios.blogspot.com/>
- Update patch for spandsp.
- Make txfax and rxfax to build.
- Spawn %{name}-sounds-es and %{name}-sounds-fr

* Sat Mar  3 2007 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.4.1-35
- Update to 1.4.1.

* Sun Dec 24 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.4.0-33
- Update to 1.4.0.

* Sat Dec 16 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.4.0-32_beta4
- Update to 1.4.0-beta4.

* Wed Dec  6 2006 Alexander Bergolth <leo@leo.wu-wien.ac.at> - 1:1.4.0-1_beta3
- First try with 1.4.0-beta3 (currently only --without spandsp)

* Wed Oct 25 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1:1.2.13-30
- Rebuild w/o Fedora Extras to avoid beta bits.
- Bump epoch to superseed beta bits.

* Thu Oct 19 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.13-29
- Update to 1.2.13.

* Sat Sep 16 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.12.1-28
- Update to 1.2.12.1.

* Sat Sep  9 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.12-27
- Update to 1.2.12.

* Fri Aug 25 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.11-26
- Update to 1.2.11.

* Sat Jul 15 2006 Axel Thimm <Axel.Thimm@ATrpms.net> - 1.2.10-26
- Update to 1.2.10.

* Sun Jun 18 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Fix conditional spandsp build.

* Wed Jun  7 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.9.1.

* Wed May 31 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.8.

* Thu Apr 13 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.7.
- Update to 1.2.7.1.

* Mon Mar 27 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.6.

* Sun Mar  5 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.5.

* Fri Feb  3 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.4.

* Thu Jan 26 2006 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.3.

* Mon Dec 12 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.1.

* Mon Nov 21 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.2.0.

* Tue Oct 11 2005 Mark Wormgoor <mark@wormgoor.com>
- Fix compiler options to work with non-i686 hardware

* Fri Jul 15 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.9.

* Mon Jun 27 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.8.

* Sun Apr 3 2005 Mark Wormgoor <mark@wormgoor.com>
- Separated into devel
- Add spandsp fax patch
- Include zaptel module

* Fri Apr  1 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.7.

* Wed Jan 26 2005 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.5.

* Mon Dec 13 2004 Axel Thimm <Axel.Thimm@ATrpms.net>
- Add lippri support.
- Add postgresql support.

* Wed Dec  8 2004 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0.3.

* Fri Aug 20 2004 Axel Thimm <Axel.Thimm@ATrpms.net>
- Update to 1.0RC2.

* Sun Mar 28 2004 Axel Thimm <Axel.Thimm@ATrpms.net> 
- Initial build.
