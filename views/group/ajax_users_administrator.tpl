<!--ajax:groupDataTable-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="3" order="asc" id="groupDataTable" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=group&x=users&type={$type}&gkey={$gkey}&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="login">Login</td>
        <td class="topTitle" name="student_name">Imię</td>
        <td class="topTitle" name="student_surname">Nazwisko</td>
        <td class = "topTitle centerAlign" name="is_added">{$smarty.const._OPERATIONS}</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>
                <div class="context_menu student"><a class = "editLink">{$item.login}</a></div>
            </td>
            <td>{$item.student_name}</td>
            <td>{$item.student_surname}</td>
            <td class="centerAlign">
                <img class = "ajaxHandle" src = "images/16x16/{if $item.is_added}forbidden{else}success{/if}.png" alt = "Zielone - przyłącz do grupy, Czerwone - odłącz od grupy" title = "Zielone - przyłącz do grupy, Czerwone - odłącz od grupy" onclick = "activeDeactive(this, '{$T_MODULE_BASEURL}&c=group&a=users&gkey={$gkey}&login={$item.login}');">
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:groupDataTable-->