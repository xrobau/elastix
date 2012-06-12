Summary: RPM that install Wildfire
Name: wildfire
Version: 3.2.0
Release: rc
License: GPL
Group: Applications/System
Source: wildfire_3_2_0_rc_withplugins.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: jre, elastix

%description
RPM that install Wildfire

%prep
%setup -n wildfire

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/usr/local/wildfire
mkdir -p $RPM_BUILD_ROOT/etc/init.d

mv -f $RPM_BUILD_DIR/wildfire/bin/extra/wildfired $RPM_BUILD_ROOT/etc/init.d/
mv -f $RPM_BUILD_DIR/wildfire/.install4j $RPM_BUILD_ROOT/usr/local/wildfire/
mv -f $RPM_BUILD_DIR/wildfire/* $RPM_BUILD_ROOT/usr/local/wildfire/

%post

chmod +x /etc/init.d/wildfired

/sbin/chkconfig --add wildfired
/sbin/chkconfig wildfired off

/bin/chown -R jive:jive /usr/local/wildfire

%pre

groupadd jive
useradd -d /usr/local/wildfire -g jive -M -s /bin/bash jive

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, root, root)
/etc/init.d/*
%dir
/usr/local/wildfire
