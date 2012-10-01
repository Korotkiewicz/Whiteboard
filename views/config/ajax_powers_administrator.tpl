<!--ajax:userDataTableAdmin-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="2" order="asc" id="userDataTableAdmin" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=config&x=powers&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="login">{$smarty.const._LOGIN}</td>
        <td class="topTitle" name="name">Imię</td>
        <td class="topTitle" name="surname">Nazwisko</td>
        <td class="topTitle" name="countgroups">Przydzielone grupy</td>
        <td class = "topTitle centerAlign">{$smarty.const._OPERATIONS}</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>
                <div class="context_menu {$item.user_type}"><a href = "{$smarty.server.PHP_SELF}?ctg=personal&user={$item.login}" {if $item.active != 0}class = "editLink"{else}title="nie aktywne"{/if}>{$item.login}</a></div>
            </td>
            <td>{$item.name}</td>
            <td>{$item.surname}</td>
            <td>
                {if $item.groups}
                    {assign var='addComma' value=false}
                    {foreach name = 'apply_data' item = 'gname' key = 'gkey' from = $item.groups}{if $addComma}, {/if}<a title="{$gname}">{$gkey}</a>{assign var='addComma' value=true}{/foreach}
                {else}
                    -
                {/if}
            </td>
            <td class="centerAlign">
                <a class="editLink" href="{$T_MODULE_BASEURL}&c=config&a=selectgroups&login={$item.login}">wybierz grupy</a>
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:userDataTableAdmin-->
<div style="clear:both">&nbsp;</div>