Summary: Additional packages and and third party software for the Elastix PBX software appliance
Name: elastix-additionals
Version: 0.5
Release: 1
License: GPL
Group: Applications/System
Source: elastix-additionals-0.5.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Prereq: elastix, atftp-server, vsftp

%description
Additional packages and and third party software for the Elastix PBX software appliance

%prep
%setup -n elastix-additionals

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT/tftpboot
mkdir -p $RPM_BUILD_ROOT/etc
mkdir -p $RPM_BUILD_ROOT/etc/vsftpd
mkdir -p $RPM_BUILD_ROOT/etc/xinetd.d
mkdir -p $RPM_BUILD_ROOT/var/log
mkdir -p $RPM_BUILD_ROOT/var/ftp/config
mkdir -p $RPM_BUILD_ROOT/var/www/html

tar xvf $RPM_BUILD_DIR/elastix-additionals/tftpboot.tar -C $RPM_BUILD_ROOT/tftpboot
mv -f $RPM_BUILD_DIR/elastix-additionals/tftp $RPM_BUILD_ROOT/etc/xinetd.d
touch $RPM_BUILD_ROOT/var/log/atftpd.log

cp -f $RPM_BUILD_DIR/elastix-additionals/vsftpd.conf $RPM_BUILD_ROOT/etc/vsftpd
cp -f $RPM_BUILD_DIR/elastix-additionals/vsftpd.user_list $RPM_BUILD_ROOT/etc

mv $RPM_BUILD_DIR/elastix-additionals/webContentAdditional/* $RPM_BUILD_ROOT/var/www/html/

%post

# Tareas de TFTP
chmod 777 /tftpboot
chmod 666 /tftpboot/* -R
chown nobody:nobody /var/log/atftpd.log
chkconfig --level 2345 tftp on

# Tareas de VSFTPD
chkconfig --level 2345 vsftpd on
chmod 777 /var/ftp/config

%pre

useradd -d /var/ftp -M -s /sbin/nologin ftpuser
(echo asteriskftp; sleep 2; echo asteriskftp) | passwd ftpuser

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/html/*
%defattr(-, root, root)
/tftpboot/*
/etc/xinetd.d/*
/var/log/*
%dir
