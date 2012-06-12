Name: elastix-rear
Version: 1.7.23
Release: 2.1.0
Summary: Relax and Recover (ReaR) is a Linux Disaster Recovery framework. Modified for Elastix.

Group: Productivity/Archiving/Backup
License: GPL v2 or later
URL: http://rear.sourceforge.net
Source0: http://downloads.sourceforge.net/%{name}/rear-%{version}.tar.gz
Source1: elastix-rear-backup.sh
Source2: elastix-recover-assist
Source3: elastix-rear-local.conf-template
Source4: 83_install_extlinux.sh
Patch0: elastix-rear-default-boot-rear.patch
Patch1: elastix-rear-hostname-before-filename.patch
Patch2: elastix-rear-extlinux-conf.patch
Patch3: elastix-rear-visibly-create-restore-filesystem.patch
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

# all RPM based systems seem to have this and call it the same
Requires:       mingetty binutils iputils tar gzip ethtool syslinux
Requires:       dialog

Conflicts: rear

# if SuSE
%if 0%{?suse_version} != 0
Requires:       iproute2 lsb
# recent SuSE versions have an extra nfs-client package and switched to genisoimage/wodim
%if 0%{?suse_version} >= 1020
Requires:       genisoimage nfs-client
%else
Requires:       mkisofs
%endif
# openSUSE from 11.1 and SLES from 11 uses rpcbind instead of portmap
%if 0%{?suse_version} >= 1110
Requires:	rpcbind
%else
Requires:       portmap
%endif
# end SuSE
%endif

# if Mandriva
%if 0%{?mandriva_version} != 0
Requires:	iproute2 lsb
# Mandriva switched from 2008 away from mkisofs, and as a specialty call the package cdrkit-genisoimage!
%if 0%{?mandriva_version} >= 2008
Requires:	cdrkit-genisoimage rpcbind
%else
Requires:	mkisofs portmap
%endif
# end Mandriva
%endif

# all Red Hat compatible, Scientific Linux and other clones are not yet supported by openSUSE
# Build Server, add more RHEL clones as needed. To make the boolean expression simpler I copy
# this section for each Red Hat OS
%if 0%{?centos_version} != 0
Requires:	iproute redhat-lsb
# Red Hat moved from CentOS/RHEL/SL 6 and Fedora 9 away from mkisofs
%if 0%{?centos_version} >= 600
Requires:	genisoimage rpcbind
%else
Requires:	mkisofs portmap
%endif
# end CentOS
%endif

%if 0%{?rhel_version} != 0
Requires:	iproute redhat-lsb
# Red Hat moved from CentOS/RHEL/SL 6 and Fedora 9 away from mkisofs
%if 0%{?rhel_version} >= 600 
Requires:	genisoimage rpcbind
%else
Requires:	mkisofs portmap
%endif
# end Red Hat Enterprise Linux
%endif

%if 0%{?fedora_version} != 0
Requires:	iproute redhat-lsb
# Red Hat moved from CentOS/RHEL/SL 6 and Fedora 9 away from mkisofs
%if 0%{?fedora_version} >= 9
Requires:	genisoimage rpcbind
%else
Requires:	mkisofs portmap
%endif
# end Fedora
%endif

%description
Relax and Recover (abbreviated rear) is a highly modular disaster recovery
framework for GNU/Linux based systems, but can be easily extended to other
UNIX alike systems. The disaster recovery information (and maybe the backups)
can be stored via the network, local on hard disks or USB devices, DVD/CD-R,
tape, etc. The result is also a bootable image that is capable of booting via
PXE, DVD/CD and USB media.

Relax and Recover integrates with other backup software and provides integrated
bare metal disaster recovery abilities to the compatible backup software.

This version of the package has been modified to include default configurations
for backing up an entire CentOS/Elastix system to an USB stick.

%prep
%setup -q -n rear-%{version}
%patch0 -p1
%patch1 -p1
%patch2 -p1
%patch3 -p1
 
%build
# no code to compile - all bash scripts

%install

# create directories
mkdir -vp \
	$RPM_BUILD_ROOT%{_mandir}/man8 \
	$RPM_BUILD_ROOT%{_datadir} \
	$RPM_BUILD_ROOT%{_sysconfdir} \
	$RPM_BUILD_ROOT%{_sbindir} \
	$RPM_BUILD_ROOT%{_localstatedir}/lib/rear

# copy rear components into directories
cp -av usr/share/rear $RPM_BUILD_ROOT%{_datadir}/
cp -av usr/sbin/rear $RPM_BUILD_ROOT%{_sbindir}/
cp -av etc/rear $RPM_BUILD_ROOT%{_sysconfdir}/

# patch rear main script with correct locations for rear components
sed -i  -e 's#^CONFIG_DIR=.*#CONFIG_DIR="%{_sysconfdir}/rear"#' \
	-e 's#^SHARE_DIR=.*#SHARE_DIR="%{_datadir}/rear"#' \
	-e 's#^VAR_DIR=.*#VAR_DIR="%{_localstatedir}/lib/rear"#' \
	$RPM_BUILD_ROOT%{_sbindir}/rear

# update man page with correct locations
sed     -e 's#/etc#%{_sysconfdir}#' \
	-e 's#/usr/sbin#%{_sbindir}#' \
	-e 's#/usr/share#%{_datadir}#' \
	-e 's#/usr/share/doc/packages#%{_docdir}#' \
	doc/rear.8 >$RPM_BUILD_ROOT%{_mandir}/man8/rear.8

# Copy Elastix-specific files to /usr/sbin
cp %{SOURCE1} %{SOURCE2} $RPM_BUILD_ROOT%{_sbindir}/
cp %{SOURCE3} $RPM_BUILD_ROOT%{_sysconfdir}/rear/local.conf-template

# Copy Elastix-specific step to /usr/share/rear/output/USB/default/
cp %{SOURCE4} $RPM_BUILD_ROOT%{_datadir}/rear/output/USB/default/

%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc COPYING CHANGES README doc/*
%{_sbindir}/rear
%{_sbindir}/elastix-*
%{_datadir}/rear
%{_localstatedir}/lib/rear
%{_mandir}/man8/rear*
%config(noreplace) %{_sysconfdir}/rear



%changelog
* Mon Dec 28 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.7.23-2.1.0
- Include patches for Elastix backup/restore and helper scripts.

* Fri Dec 11 2009 schlomo.schapiro@novell.com
- updated to rear 1.7.23 from upstream
  * some bugfixing
  * added validation info for openSUSE 11.2 i386 and x86_64
  * improved library collection for 64bit Linux (e.g. /lib*/libnss*)
  * symlink doc and contrib in the dist archive to reduce the size
  * clone required system users/groups to rescue system (daemon and rpc)
* Wed Dec  2 2009 schlomo.schapiro@novell.com
- updated to rear 1.7.22 from upsteam
  * added -L to stat call to read real device in 29_find_required_devices.sh
  * make svn2host report scp errors
  * updated rear.spec, rear.sourcespec of SUSE and FEDORA
  * updated contrib/mkvendorrp to pull version nr from rear, CHANGED
  * updated lib/spec/Fedora/rear.sourcespec Require field and doc/
  * updated skel/default/etc/scripts/system-setup to improve loading modules
  * updated finalize/default/88_check_for_mount_by_id.sh
  * added prep/GNU/Linux/28_include_vmware_tools.sh
  * moved some misplaced scripts from pack to build
  * added contrib/svn2hosts (continuous integration script)
  * fixed up mkdist-workflow.sh and contrib/svn2tar to share more code
  * removed the UTF-8 conversion (IMHO should be only done manually)
  * removed the overwriting of the generic spec file with vendor specific spec file
  * fixed the copying of /dev/shm/* by adding it to COPY_AS_IS_EXCLUDE
  * support open-vm-tools and loading of vmxnet
  * removed the usage of udev_volume_id (for RHEL4) in favour of internal vol_id
  * fixed internal vol_id to correctly strip leading spaces from the values
  * fixed 31_create_filesystems.sh to actually correctly create ext* and
    support FS labels with < or > in them
    (https://sourceforge.net/tracker/?func=detail&aid=2891970&group_id=171835&atid=859452)
  * added usleep and mktemp
  * Fixed all wrong occurences of [*] with [@].
    See https://sourceforge.net/tracker/?func=detail&atid=859452&aid=2877091&group_id=171835
  * backup/NETFS/default/40_create_include_exclude_files.sh
    fixed variable name for excluded mountpoints to actually read EXCLUDE_MOUNTPOINTS
  * recreate/GNU/Linux/22_create_lvm2_devices.sh
    move lvm vgchange -a y  in loop to avoid old VGs to activate
* Sun Sep 27 2009 schlomo.schapiro@novell.com
- updated to rear 1.7.21 from upstream
  * support openSUSE 11.2 (M6)
  * rsyslogd
  * ext4
* Sun Mar  8 2009 Schlomo.Schapiro@novell.com
- import into Factory
* Thu Jan 29 2009 Schlomo Schapiro <rear@schlomo.schapiro.org>
- 1.7.14-1
- added man page
- fixed TSM bug with result files
- patch rear binary to point to correct _datadir and _sysconfdir
- move distribution config files to /usr/share/rear/conf
- add hpacucli support
- TSM point-in-time restore
- fix bonding for multiple bonding devices
* Tue Jan 20 2009 Gratien D'haese <gdha@sourceforge.net>
- 1.7.13-1
- add COPYING license file
- linux-functions.sh: added rpmtopdir function;
- mkdist-workflow.sh: updated with rpmtopdir function; convert doc files to UTF-8
* Fri Jan  9 2009 Gratien D'haese <gdha@sourceforge.net> 
- 1.7.12-1
- NetBackup integration completed
- moved validation from /etc/rear to doc directory
* Tue Dec 30 2008 Gratien D'haese <gdha@sourceforge.net>
- 1.7.11-1
- added scriptfor Data Protector and NetBackup integration
* Wed Dec 17 2008 Gratien D'haese <gdha@sourceforge.net> 
- 1.7.10-1
- completed verify/NBU/default/40_verify_nbu.sh script for NBU
- remove contrib entry from %%%%doc line in spec file
* Mon Dec  1 2008 Gratien D'haese <gdha@sourceforge.net>
- 1.7.9-1
- remove from skel/default the symbolic links sh->bash, bin/init->init
  and the empty files etc/mtab, var/log/lastlog and var/lib/nfs/state
- add the link sh-bash into file pack/GNU/Linux/00_create_symlinks.sh
- add new file pack/GNU/Linux/10_touch_empty_files.sh to create the empty files
- add pack/GNU/Linux/20_create_dotfiles.sh and removed .bash_history from skel/default
- Added intial scripts for rear integration with NetBackup (of Symantec)
- copy rear.sourcespec according OS_VENDOR
- correct rear.spec file according comment 11 of bugzilla #468189
* Mon Oct 27 2008 Gratien D'haese <gdha@sourceforge.net>
- 1.7.8-1
- Fix rpmlint error/warnings for Fedora packaging
- updated the Summary line and %%%%install section
* Fri Oct 24 2008 Gratien D'haese <gdha@sourceforge.net> 
- 1.7.7-1
- rewrote rear.spec for Fedora Packaging request
* Mon Aug 28 2006 Schlomo Schapiro <rear@schlomo.schapiro.org>
- 1.0-1
- Initial RPM Release
