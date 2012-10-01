<!--ajax:groupDataTable-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="1" order="asc" id="groupDataTable" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=group&a=index&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="name">Nazwa przedmiotu</td>
        <td class="topTitle" name="gkey">Kod grupy</td>
        <td class="topTitle" name="next_lesson">Data następnych zajęć</td>
        <td class = "topTitle centerAlign" name="state">{$smarty.const._OPERATIONS}</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>{$item.course_name}</td>
            <td>{$item.gkey}</td>
            <td>{$item.next_lesson}</td>
            <td class="centerAlign">
                <a href = "{$T_MODULE_BASEURL}&c=group&b=uinfo&gkey={$item.gkey}" title="kontakt do członków grupy" alt="kontakt do członków grupy"><img src="images/16x16/users.png" alt="kontakt do członków grupy"/></a>&nbsp;
                <a id="{$item.gkey}_play" href = "{$T_WHITEBOARD_HTTP_URL}&c=lesson&b=room&gkey={$item.gkey}" target="_blank" class = "editLink" {*style="display: {if $item.state == 'open'}auto{else}none{/if};"*}><img border = "0" src = "images/16x16/start.png" title = "Przejdź do zajęć" alt = "Przejdź do zajęć" /></a>&nbsp;
                <a href = "{$T_MODULE_BASEURL}&c=group&a=modify&gkey={$item.gkey}" class = "editLink"><img border = "0" src = "images/16x16/edit.png" title = "{$smarty.const._EDIT}" alt = "{$smarty.const._EDIT}" /></a>&nbsp;
                <img class = "ajaxHandle" src = "images/16x16/trafficlight_{if $item.state == 'open'}green{else}red{/if}.png" alt = "Zielone - zajęcia w grupie w tym momencie są włączone, Czerwone - zajęcia są wyłączone" title = "Zielone - zajęcia w grupie w tym momencie są włączone, Czerwone - zajęcia są wyłączone" onclick = "activeDeactive(this, '{$T_MODULE_BASEURL}&c=group&x=openclose&gkey={$item.gkey}',['{$item.gkey}_play']);">
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:groupDataTable-->