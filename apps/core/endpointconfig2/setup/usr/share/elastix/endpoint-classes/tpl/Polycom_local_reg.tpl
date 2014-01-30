<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<reginfo>
    <reg{{py:n = 1}}{{for extension in sip}}
        reg.{{n}}.displayName="{{extension.description}}"
        reg.{{n}}.address="{{extension.extension}}"
        reg.{{n}}.label="{{extension.extension}}"
        reg.{{n}}.auth.userId="{{extension.account}}"
        reg.{{n}}.auth.password="{{extension.secret}}"
        reg.{{n}}.lineKeys="1"
        reg.{{n}}.server.1.address="{{server_ip}}"
        reg.{{n}}.server.1.expires=""
        reg.{{n}}.server.1.expires.lineSeize="30"
        reg.{{n}}.server.1.port="5060"
        reg.{{n}}.server.1.register="1"
        reg.{{n}}.server.1.retryMaxCount=""
        reg.{{n}}.server.1.retryTimeOut=""
        reg.{{n}}.server.1.transport="DNSnaptr"
        reg.{{n}}.server.2.transport="DNSnaptr"
        reg.{{n}}.thirdPartyName=""
        reg.{{n}}.type="private"{{py:n += 1}}{{endfor}}/>
    <msg msg.bypassInstantMessage="1">
        <mwi{{py:n = 1}}{{for extension in sip}}
            msg.mwi.{{n}}.callBack=""
            msg.mwi.{{n}}.callBackMode="disabled"
            msg.mwi.{{n}}.subscribe=""{{py:n += 1}}{{endfor}}></mwi>
    </msg>
</reginfo>