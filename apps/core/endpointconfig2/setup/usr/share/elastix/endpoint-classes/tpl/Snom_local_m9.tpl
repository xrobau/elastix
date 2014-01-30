<?xml version="1.0" encoding="utf-8"?>
<settings>
 <phone-settings>
  <action_setup_url perm="RW" />
  <allow_check_sync perm="RW">true</allow_check_sync>
  <asset_id perm="RW" />
  <base_name perm="RW">snom-at-{{current_ip}}-reg-{{server_ip}}</base_name>
  <setting_server perm="RW">tftp://{{server_ip}}</setting_server>
  <dhcp perm="RW">{{if enable_dhcp}}{{else}}false{{endif}}</dhcp>
  <ip_adr perm="RW">{{static_ip}}</ip_adr>
  <netmask perm="RW">{{static_mask}}</netmask>
  <dns_server1 perm="RW">{{static_dns1}}</dns_server1>
  <dns_server2 perm="">{{static_dns2}}</dns_server2>
  <gateway perm="RW">{{static_gateway}}</gateway>
  <dst_offset perm="RW">0</dst_offset>
  <dst_start_day perm="RW">0</dst_start_day>
  <dst_start_day_of_week perm="RW">0</dst_start_day_of_week>
  <dst_start_month perm="RW">0</dst_start_month>
  <dst_start_time perm="RW">0</dst_start_time>
  <dst_start_week_of_month perm="RW">0</dst_start_week_of_month>
  <dst_stop_day perm="RW">0</dst_stop_day>
  <dst_stop_day_of_week perm="RW">0</dst_stop_day_of_week>
  <dst_stop_month perm="RW">0</dst_stop_month>
  <dst_stop_time perm="RW">0</dst_stop_time>
  <dst_stop_week_of_month perm="RW">0</dst_stop_week_of_month>
  <emergency_numbers perm="RW" />
  <emergency_proxy perm="RW" />
  <ethernet_replug perm="RW">reregister</ethernet_replug>
  <gmt_offset perm="RW">0</gmt_offset>
  <settings_refresh_timer perm="RW">0</settings_refresh_timer>
  <language perm="RW" />
  <log perm="RW" idx="1">5</log>
  <ntp_server perm="RW">ntp.snom.com</ntp_server>
  <outbound_method perm="RW" />
  <outbound_tcp perm="RW">100</outbound_tcp>
  <outbound_udp perm="RW">20</outbound_udp>
  <packet_length perm="RW" idx="1">20</packet_length>
  <pcap_on_bootup perm="RW">false</pcap_on_bootup>
  <pin_change_prompt perm="RW">true</pin_change_prompt>
  <propose_length perm="RW" idx="1">false</propose_length>
  <qos_publish_uri perm="RW" />
  <read_status perm="RW">false</read_status>
  <reject_msg perm="RW">486 Busy Here</reject_msg>
  <repeater perm="RW">false</repeater>
  <retry_t1 perm="RW">500</retry_t1>
  <ring_duration perm="RW">60</ring_duration>
  <rtcp_dup_rle perm="RW">true</rtcp_dup_rle>
  <rtcp_loss_rle perm="RW">true</rtcp_loss_rle>
  <rtcp_rcpt_times perm="RW">true</rtcp_rcpt_times>
  <rtcp_rcvr_rtt perm="RW">true</rtcp_rcvr_rtt>
  <rtcp_stat_summary perm="RW">true</rtcp_stat_summary>
  <rtcp_voip_metrics perm="RW">true</rtcp_voip_metrics>
  <rtp_port_end perm="RW">65534</rtp_port_end>
  <rtp_port_start perm="RW">49152</rtp_port_start>
  <session_timeout perm="RW">360</session_timeout>
  <settings_refresh_timer perm="RW">86400</settings_refresh_timer>
  <short_form perm="RW">false</short_form>
  <sip_port perm="RW">0</sip_port>
  <stun_interval perm="RW">5</stun_interval>
  <stun_server perm="RW" />
  <telnet_enabled perm="RW">false</telnet_enabled>
  <tones perm="RW">1</tones>
  <tos_rtp perm="RW">160</tos_rtp>
  <tos_sip perm="RW">160</tos_sip>
  {{py:n = 1}}{{for extension in sip}}
  <user_active idx="{{n}}" perm="">on</user_active>
  <user_realname idx="{{n}}" perm="">{{extension.description}}</user_realname>
  <user_name idx="{{n}}" perm="">{{extension.account}}</user_name>
  <user_host idx="{{n}}" perm="">{{server_ip}}</user_host>
  <user_pname idx="{{n}}" perm="">{{extension.account}}</user_pname>
  <user_pass idx="{{n}}" perm="">{{extension.secret}}</user_pass>
  {{if 'ipui' in dir(extension) }}<user_ipui perm="RW" idx="{{n}}">{{extension.ipui}}</user_ipui>
  {{endif}}<user_allow_call_waiting perm="RW" idx="{{n}}">true</user_allow_call_waiting>
  <user_allow_line_switching perm="RW" idx="{{n}}">false</user_allow_line_switching>
  <user_ear_protection perm="RW" idx="{{n}}">false</user_ear_protection>
  <user_enable_e164_substitution perm="RW" idx="{{n}}">false</user_enable_e164_substitution>
  <user_expiry perm="RW" idx="{{n}}">3600</user_expiry>
  <user_forward_mode perm="RW" idx="{{n}}">0</user_forward_mode>
  <user_forward_number perm="RW" idx="{{n}}" />
  <user_forward_timeout perm="RW" idx="{{n}}">10</user_forward_timeout>
  <user_add_incoming1_ipuis perm="RW" idx="{{n}}">true</user_add_incoming1_ipuis>
  {{py:n += 1}}{{endfor}}{{for m in range(n,max_sip_accounts+1)}}
  <user_active idx="{{m}}" perm="">off</user_active>
  <user_realname idx="{{m}}" perm=""></user_realname>
  <user_name idx="{{m}}" perm=""></user_name>
  <user_host idx="{{m}}" perm="">{{server_ip}}</user_host>
  <user_pname idx="{{m}}" perm=""></user_pname>
  <user_pass idx="{{m}}" perm=""></user_pass>
  <user_allow_call_waiting perm="RW" idx="{{m}}">true</user_allow_call_waiting>
  <user_allow_line_switching perm="RW" idx="{{m}}">false</user_allow_line_switching>
  <user_ear_protection perm="RW" idx="{{m}}">false</user_ear_protection>
  <user_enable_e164_substitution perm="RW" idx="{{m}}">false</user_enable_e164_substitution>
  <user_expiry perm="RW" idx="{{m}}">3600</user_expiry>
  <user_forward_mode perm="RW" idx="{{m}}">0</user_forward_mode>
  <user_forward_number perm="RW" idx="{{m}}" />
  <user_forward_timeout perm="RW" idx="{{m}}">10</user_forward_timeout>
  {{endfor}}
  <vlan_id perm="RW">0</vlan_id>
  <vlan_prio perm="RW">0</vlan_prio>
  <zone_desc perm="RW">GMT {{time_zone}}</zone_desc>
  </phone-settings>
</settings>          
