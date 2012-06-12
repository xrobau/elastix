<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="moduleTitle">
        <td class="moduleTitle" valign="middle">&nbsp;&nbsp;<img src="{$IMG}" border="0" align="absmiddle">&nbsp;&nbsp;{$title}</td>
        <td></td>
    </tr>
    <tr class="letra12">
     {if $FILENAME}
     <td align="left"><input class="button" type="submit" name="back" value="{$BACK}"></td>
     {else}
     <td align="left"><input class="button" type="submit" name="generate" value="{$GENERATE}"></td>
     
        <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>{/if}
    </tr>
</table>
<table>
    <tr>{if !$FILENAME}
        <table class="tabForm" style="font-size: 16px;" width="100%" >
            <tr class="letra12">
                <td align="left"><b>{$message.LABEL}: <span  class="required">*</span> </b></td>
                <td align="left">{$message.INPUT}</td>
                <td width="40%"></td>
            </tr>
            <tr class="letra12">
                <td align="left"><b>{$format.LABEL}:</b></td>
                <td align="left">{$format.INPUT}</td>
                <td align="left"></td>
            </tr>
        </table>
	{else}
	<table class="tabForm" style="font-size: 16px;" width="100%" >
            <tr class="letra12">
                <td align="left"><b> {if $EXECUTE}<a href="{$PATH}/{$FILENAME}{$EXTENSION}">{$DOWNLOAD} {$EXTENSION}</a>{/if}</b></td>
            </tr>
        </table>
	
	{/if}
   <tr>
</table>
