<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/memory.png" border="0" align="absmiddle">&nbsp;&nbsp;{$SYSTEM_INFO_TITLE1}</td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      <tr>
	<td width="15%">{$CPU_INFO_TITLE}: </td>
	<td width="35%">{$cpu_info}</td>
	<td colspan="2" rowspan="5" width="50%" align="left"><img src="images/plot.php?id_graph=1" border="0"></td>
      </tr>
      <tr>
	<td>{$UPTIME_TITLE}:</td>
	<td>{$uptime}</td>
      </tr>
      <tr>
	<td>{$CPU_USAGE_TITLE}:</td>
	<td>{$cpu_usage}</td>
      </tr>
      <tr>
	<td>{$MEMORY_USAGE_TITLE}:</td>
	<td>{$mem_usage}</td>
      </tr>
      <tr>
	<td>{$SWAP_USAGE_TITLE}:</td>
	<td>{$swap_usage}</td>
      </tr>
    </table>
  </td>
</tr>
</table>
<br>
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr class="moduleTitle">
  <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="images/hd.png" border="0" align="absmiddle">&nbsp;&nbsp;{$SYSTEM_INFO_TITLE2}</td>
</tr>
<tr>
  <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
      {foreach from=$arrParticiones key=k item=particion name=particiones}
      <tr>
	<td width="15%"><img src="images/arrow-8.gif">&nbsp;<b>{$PARTICION_NAME_TITLE}:</b></td>
        <td width="35%"><b>{$particion.fichero}</b></td>
        <td width="50%" rowspan="5" align="left"><img src='images/pie2.php?du={$particion.uso}%' border='0'></td>
      </tr>
      <tr>
        <td width="15%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$CAPACITY_TITLE}:</td>
        <td width="35%">{$particion.total_bloques}GB</td>
      </tr>
      <tr>
        <td width="15%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$USAGE_TITLE}:</td>
        <td width="35%">{$particion.uso}%</td>
      </tr>
      <tr>
        <td width="15%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$MOUNT_POINT_TITLE}:</td>
        <td width="35%">{$particion.punto_montaje}</td>
      </tr>
      <tr>
        <td width="15%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        <td width="35%">&nbsp;</td>
      </tr>
      {/foreach}
    </table>
  </td>
</tr>
</table>
