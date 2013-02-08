insert into model (name,description,id_vendor,iax_support)values("SIP-T38G","SIP-T38G",(select id from vendor where name="Yealink"),'0');
insert into model (name,description,id_vendor,iax_support)values("C60","C60",(select id from vendor where name="Fanvil"),'1');
insert into model (name,description,id_vendor,iax_support)values("C58/C58P","C58/C58P",(select id from vendor where name="Fanvil"),'1');
insert into model (name,description,id_vendor,iax_support)values("C56/C56P","C56/C56P",(select id from vendor where name="Fanvil"),'0');
insert into model (name,description,id_vendor,iax_support)values("821","snom821-SIP",(select id from vendor where name="Snom"),'0');
