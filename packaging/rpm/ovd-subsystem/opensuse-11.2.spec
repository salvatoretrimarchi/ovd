Name: ovd-subsystem
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Subsystem
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.2

BuildArch: noarch

%description
This package provides the subsystem for the Ulteo Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-subsystem
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Requires: curl

%description -n ulteo-ovd-subsystem
This package provides the subsystem for the Ulteo Open Virtual Desktop.

%prep -n ulteo-ovd-subsystem
svn co https://svn.ulteo.com/ovd/trunk/packaging/rpm/ovd-subsystem/
cd ovd-subsystem
cp ulteo-ovd-subsystem ovd-subsystem-config %_builddir

%install -n ulteo-ovd-subsystem
SBINDIR=%buildroot/usr/sbin
INITDIR=%buildroot/etc/init.d
mkdir -p $SBINDIR $INITDIR
cp ovd-subsystem-config $SBINDIR
cp ulteo-ovd-subsystem $INITDIR

%postun -n ulteo-ovd-subsystem
rm -f /etc/ulteo/subsystem.conf
rm -rf /opt/ulteo

%clean -n ulteo-ovd-subsystem
rm -rf %buildroot

%files -n ulteo-ovd-subsystem
%defattr(744,root,root)
/usr/*
/etc/*

%changelog -n ulteo-ovd-subsystem
* Tue Aug 17 2010 Samuel Bovée <gauvain@ulteo.com> svn4180
- Initial release
