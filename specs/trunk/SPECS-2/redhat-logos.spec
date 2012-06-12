Name: redhat-logos
Summary: CentOS-related icons and pictures.
Version: 4.9.8
Release: 8%{?dist}
Group: System Environment/Base
Source0: redhat-logos-%{version}-centos.tar.bz2
Source1: redhat-logos-anaconda-centos.tar.bz2
Source2: redhat-logos-centos-bootloader.tar.bz2
Source3: redhat-logos-centos-firstboot.tar.bz2
Source4: redhat-logos-centos-gdm.tar.bz2
Source5: redhat-logos-centos-kdesplash.tar.bz2
Source6: redhat-logos-centos-redhat-pixmaps.tar.bz2
Source7: redhat-logos-centos-rhgb.tar.bz2
Source8: gnome-splash.png
Source9: redhat-credits.png
Patch0:  centos5-isolinux-colors.patch

License: Copyright © 2003-2007 the CentOS Project.  All rights reserved.
BuildRoot: %{_tmppath}/%{name}-root
BuildArchitectures: noarch
Conflicts: anaconda-images <= 10
Provides: system-logos

%description
The redhat-logos package (the "Package") contains files created by the
CentOS Project to replace the Red Hat "Shadow Man" logo and  RPM logo.
The Red Hat "Shadow Man" logo, RPM, and the RPM logo are trademarks or
registered trademarks of Red Hat, Inc.

The Package and CentOS logos (the "Marks") can only used as outlined in 
the included COPYING file. Please see that file for information on copying
and redistribution of the CentOS Marks.

%prep
%setup -n redhat-logos-%{version}-centos

(cd anaconda && tar xjf %{SOURCE1})
(cd bootloader && tar xjf %{SOURCE2})
(cd firstboot  && tar xjf %{SOURCE3})
(cd gdm && tar xjf %{SOURCE4})
(cd kde-splash/BlueCurve/ && tar xjf %{SOURCE5})
(cd redhat-pixmaps && tar xjf %{SOURCE6})
(cd rhgb  && tar xjf %{SOURCE7})
cp -f %{SOURCE8} gnome-splash/
cp -f %{SOURCE8} pixmaps/

%patch0 -p1
%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps/redhat
for i in redhat-pixmaps/*; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps/redhat
done
(cd $RPM_BUILD_ROOT%{_datadir}/pixmaps/redhat; \
for i in *-mini.xpm; do \
  linkfile=`echo $i | sed -e "s/\(.*\)-mini/mini-\1/"` ; \
  ln -s $i $linkfile ; \
done)

# should be ifarch i386
mkdir -p $RPM_BUILD_ROOT/boot/grub
install -m 644 bootloader/rhlilo-en.pcx $RPM_BUILD_ROOT/boot/message
install -m 644 bootloader/splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/splash.xpm.gz
# end i386 bits

mkdir -p $RPM_BUILD_ROOT%{_datadir}/firstboot/pixmaps
for i in firstboot/* ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/firstboot/pixmaps
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/rhgb
for i in rhgb/* ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/rhgb
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps/splash
for i in gnome-splash/* ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps/splash
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/apps/ksplash/Themes/BlueCurve
for i in kde-splash/BlueCurve/* ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/apps/ksplash/Themes/BlueCurve
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
for i in pixmaps/* ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps
done

mkdir -p $RPM_BUILD_ROOT%{_datadir}/gdm/themes/CentOSCubes
for i in gdm/*.png ; do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/gdm/themes/CentOSCubes
done

(cd anaconda ; make DESTDIR=$RPM_BUILD_ROOT install)

mkdir -p $RPM_BUILD_ROOT%{_datadir}/anaconda/pixmaps/rnotes
#for i in anaconda/rnotes/*.png ; do
#  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/anaconda/pixmaps/rnotes
#done
cp -aRf anaconda/rnotes/* $RPM_BUILD_ROOT%{_datadir}/anaconda/pixmaps/rnotes

mkdir -p $RPM_BUILD_ROOT%{_datadir}/gnome-screensaver
for i in gnome-screensaver/*;  do
  install -m 644 $i $RPM_BUILD_ROOT%{_datadir}/gnome-screensaver
done

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
%doc COPYING
%{_datadir}/firstboot
%{_datadir}/apps
%{_datadir}/pixmaps
%{_datadir}/gdm
%{_datadir}/rhgb
%{_datadir}/gnome-screensaver/*
%{_datadir}/anaconda/pixmaps/*
/usr/lib/anaconda-runtime/boot/*png
/usr/lib/anaconda-runtime/*.sh
# should be ifarch i386
/boot/message*
/boot/grub/splash.xpm.gz
# end i386 bits

%changelog
* Fri Jun 15 2007 Edwin Boza <www.palosanto.com>
- Change syslinux-splash.png (Elastix)

* Sun Apr  1 2007 Johnny Hughes <johnny@centos.org> - 4.9.8-6.el5.centos
- Rolled in ja and cz install slides.
- Rolled in newer graphics for the install theme.

* Tue Mar 27 2007 Johnny Hughes <johnny@centos.org> - 4.9.8-5.el5.centos
- Modified to remove Red Hat branding in the SPEC file.
- Modofied to add "Other Than English" install slides

* Mon Feb 19 2007 Johnny Hughes <johnny@centos.org> - 4.9.8-2.el5.centos
- modified to change the file splashtolss.sh to better build the anaconda
  splash file (splash.lss)

* Tue Jan 24 2007 Johnny Hughes <johnny@centos.org> - 4.9.8-1.el5.centos
- Removed RedHat logos and artwork from Source0 and recompiled it.
- Added Sources 1 thru 9 to replace logos and artwork. 
- removed backgrounds/ directory (handling that in desktop-backgrounds-basic)
- This package no longer tracks the upstream redhat-logos package.
- Added Theme.rc into the kde-splash tarball (see this RH bugzilla entry)
  https://bugzilla.redhat.com/bugzilla/show_bug.cgi?id=224363

* Wed Oct 25 2006 David Zeuthen <davidz@redhat.com> - 4.9.8-1
- Add new shadowman logos (#211837)

* Mon Oct 23 2006 Matthias Clasen <mclasen@redhat.com> - 4.9.7-1 
- Include the xml file in the tarball

* Mon Oct 23 2006 Matthias Clasen <mclasen@redhat.com> - 4.9.6-1
- Add names for the default background (#211556)

* Tue Oct 17 2006 Matthias Clasen <mclasen@redhat.com> - 4.9.5-1
- Update the url pointing to the trademark policy (#187124)

* Thu Oct  5 2006 Matthias Clasen <mclasen@redhat.com> - 4.9.4-1
- Fix some colormap issues in the syslinux-splash (#209201)

* Wed Sep 20 2006 Ray Strode <rstrode@redhat.com> - 4.9.2-1
- ship new artwork from Diana Fong for login screen

* Tue Sep 19 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.8-1
- Fix packager to dist the xml background file

* Tue Sep 19 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.7-1
- Add background xml file for the new backgrounds
- Add po directory for translating the background xml

* Tue Sep 19 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.6-1
- Add new RHEL graphics

* Fri Aug 25 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.5-1
- Modify the anaconda/splash.png file to say Beta instead of Alpha

* Tue Aug 01 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.4-1
- Add firstboot-left to the firstboot images

* Fri Jul 28 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.3-1
- Add attributions to the background graphics metadata
- Add a 4:3 asspect ratio version of the default background graphic

* Thu Jul 27 2006 John (J5) Palmieri <johnp@redhat.com> - 1.2.2-1
- Add default backgrounds

* Wed Jul 12 2006 Matthias Clasen <mclasen@redhat.com> - 1.2.1-1
- Add system lock dialog

* Thu Jun 15 2006 Jeremy Katz <katzj@redhat.com> - 1.2.0-1
- alpha graphics

* Wed Aug  3 2005 David Zeuthen <davidz@redhat.com> - 1.1.26-1
- Add russian localisation for rnotes (#160738)

* Thu Dec  2 2004 Jeremy Katz <katzj@redhat.com> - 1.1.25-1
- add rnotes

* Fri Nov 19 2004 Alexander Larsson <alexl@redhat.com> - 1.1.24-1
- Add rhgb logo (#139788)

* Mon Nov  1 2004 Alexander Larsson <alexl@redhat.com> - 1.1.22-1
- Move rh logo from redhat-artwork here (#137593)

* Fri Oct 29 2004 Alexander Larsson <alexl@redhat.com> - 1.1.21-1
- Fix alignment of gnome splash screen (#137360)

* Fri Oct  1 2004 Alexander Larsson <alexl@redhat.com> - 1.1.20-1
- New gnome splash

* Tue Aug 24 2004 Jeremy Katz <katzj@redhat.com> - 1.1.19-1
- update firstboot splash

* Sat Jun  5 2004 Jeremy Katz <katzj@redhat.com> - 1.1.18-1
- provides: system-logos

* Thu Jun  3 2004 Jeremy Katz <katzj@redhat.com> - 1.1.17-1
- add anaconda bits

* Tue Mar 23 2004 Alexander Larsson <alexl@redhat.com> 1.1.16-1
- fix the logos in the gdm theme

* Fri Jul 18 2003 Havoc Pennington <hp@redhat.com> 1.1.15-1
- build new stuff from garrett

* Wed Feb 26 2003 Havoc Pennington <hp@redhat.com> 1.1.14-1
- build new stuff in cvs

* Mon Feb 24 2003 Jeremy Katz <katzj@redhat.com> 1.1.12-1
- updated again
- actually update the grub splash

* Fri Feb 21 2003 Jeremy Katz <katzj@redhat.com> 1.1.11-1
- updated splash screens from Garrett

* Tue Feb 18 2003 Havoc Pennington <hp@redhat.com> 1.1.10-1
- move in a logo from gdm theme #84543

* Mon Feb  3 2003 Havoc Pennington <hp@redhat.com> 1.1.9-1
- rebuild

* Wed Jan 15 2003 Brent Fox <bfox@redhat.com> 1.1.8-1
- rebuild for completeness

* Mon Dec 16 2002 Havoc Pennington <hp@redhat.com>
- rebuild

* Thu Sep  5 2002 Havoc Pennington <hp@redhat.com>
- add firstboot images to makefile/specfile
- add /usr/share/pixmaps stuff
- add splash screen images
- add COPYING

* Thu Sep  5 2002 Jeremy Katz <katzj@redhat.com>
- add boot loader images

* Thu Sep  5 2002 Havoc Pennington <hp@redhat.com>
- move package to CVS

* Tue Jun 25 2002 Owen Taylor <otaylor@redhat.com>
- Add a shadowman-only derived from redhat-transparent.png

* Thu May 23 2002 Tim Powers <timp@redhat.com>
- automated rebuild

* Wed Jan 09 2002 Tim Powers <timp@redhat.com>
- automated rebuild

* Thu May 31 2001 Owen Taylor <otaylor@redhat.com>
- Fix alpha channel in redhat-transparent.png

* Wed Jul 12 2000 Prospector <bugzilla@redhat.com>
- automatic rebuild

* Mon Jun 19 2000 Owen Taylor <otaylor@redhat.com>
- Add %defattr

* Mon Jun 19 2000 Owen Taylor <otaylor@redhat.com>
- Add version of logo for embossing on the desktop

* Tue May 16 2000 Preston Brown <pbrown@redhat.com>
- add black and white version of our logo (for screensaver).

* Mon Feb 07 2000 Preston Brown <pbrown@redhat.com>
- rebuild for new description.

* Fri Sep 25 1999 Bill Nottingham <notting@redhat.com>
- different.

* Mon Sep 13 1999 Preston Brown <pbrown@redhat.com>
- added transparent mini and 32x32 round icons

* Sat Apr 10 1999 Michael Fulbright <drmike@redhat.com>
- added rhad logos

* Thu Apr 08 1999 Bill Nottingham <notting@redhat.com>
- added smaller redhat logo for use on web page

* Wed Apr 07 1999 Preston Brown <pbrown@redhat.com>
- added transparent large redhat logo

* Tue Apr 06 1999 Bill Nottingham <notting@redhat.com>
- added mini-* links to make AnotherLevel happy

* Mon Apr 05 1999 Preston Brown <pbrown@redhat.com>
- added copyright

* Tue Mar 30 1999 Michael Fulbright <drmike@redhat.com>
- added 48 pixel rounded logo image for gmc use

* Mon Mar 29 1999 Preston Brown <pbrown@redhat.com>
- package created
