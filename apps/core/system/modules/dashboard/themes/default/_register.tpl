<input type="hidden" name="idCard" id="idCard" value="" />
<div style="text-align:center; width:100%; margin-top:5px;">
	<div class="message" style="color:red; font-size: 11px">Card has not been Registered</div></div>
	<div class="loading" style="float: right; position: absolute; top: 87px; left: 115px;"></div>
<table style="margin-top:5px">
	<tr>
		<td><label style="font-size: 12px; font-weight:bold;">Vendor (ex. digium):</label></td>
		<td id="lman"><input type="text" value='' name="manufacturer" id="manufacturer" /></td>
	</tr>
	<tr>
		<td><label style="font-size: 12px; font-weight:bold;">Serial Number:</label></td>
		<td id="lser"><input type="text" value="" name="noSerie" id="noSerie" /></td>
	</tr>
</table>
<div class="viewButton" style="margin-top:5px;">
     <input type="button" value="{$SAVE}" class="boton" onclick="saveRegister();" style="cursor:pointer" />
     <input class="boton" type="button" id="cancel" name="cancel" value="{$CANCEL}" style="cursor:pointer" onclick="hideModalPopUP();"/>
     
     
</div>

