<!--ajax:userDataTableAdmin-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="2" order="desc" id="userDataTableAdmin" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=config&x=selectgroups&login={$T_WHITEBOARD_LOGIN}&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="name">Nazwa grupy</td>
        <td class = "topTitle centerAlign" name="selected">{$smarty.const._OPERATIONS}</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>{$item.name}</td>
            <td class="centerAlign">
                <img class = "ajaxHandle" src = "images/16x16/trafficlight_{if $item.selected}green{else}red{/if}.png" alt = "Zielone - użytkownik uczestniczy w grupie, Czerwone - nie uczestniczy" title = "Zielone - użytkownik uczestniczy w grupie, Czerwone - nie uczestniczy" onclick = "activeDeactive(this, '{$T_MODULE_BASEURL}&c=config&a=selectgroups&login={$T_WHITEBOARD_LOGIN}&gkey={$item.key}');">                    
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:userDataTableAdmin-->
<div style="clear:both">&nbsp;</div>