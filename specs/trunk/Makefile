PACKS	= asterisk-addons asterisk-perl asterisk elastix-additionals \
	elastix freepbx hylafax iaxmodem libpri mISDN mISDNuser wildfire \
	zaptel

SPECS	= $(PACKS:%=%.spec)

SERVER_DIGIUM	= http://downloads.digium.com/pub
REMODE_ZAPTEL	= $(SERVER_DIGIUM)/zaptel/releases
REMODE_LIBPRI	= $(SERVER_DIGIUM)/libpri/releases
REMODE_ASTERISK	= $(SERVER_DIGIUM)/asterisk/releases
REMODE_AST_ADD	= $(SERVER_DIGIUM)/asterisk/releases

all:

zaptel: zaptel.spec SOURCES/zaptel-1.4.5.1.tar.gz
	./rpmer -ba $@.spec

libpri: libpri.spec SOURCES/libpri-1.4.1.tar.gz
	./rpmer -ba $@.spec


SOURCES/zaptel-%.tar.gz:
	cd SOURCES && wget -c $(REMODE_ZAPTEL)/$(@F)

SOURCES/libpri-%.tar.gz:
	cd SOURCES && wget -c $(REMODE_LIBPRI)/$(@F)

SOURCES/asterisk-%.tar.gz:
	cd SOURCES && wget -c $(REMODE_ASTERISK)/$(@F)

SOURCES/asterisk-addons-%.tar.gz:
	cd SOURCES && wget -c $(REMODE_AST_ADD)/$(@F)
