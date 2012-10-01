<!--ajax:groupDataTable-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="1" order="asc" id="groupDataTable" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=group&a=config&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="course_name">Nazwa przedmiotu</td>
        <td class="topTitle" name="gkey">Kod grupy</td>
        <td class="topTitle" name="day_of_week">Dzień tygodnia</td>
        <td class="topTitle" name="frequency">Co ile tyg.</td>
        <td class="topTitle" name="next_lesson">Data następnych zajęć</td>
        <td class="topTitle" name="pupils_count"><a title="Liczba uczestników">L. ucz.</a></td>
        <td class="topTitle" name="professor_count"><a title="Liczba nauczycieli">L. naucz.</a></td>
        <td class="topTitle" name="last_history_date">Ostatnia aktualizacjia</td>
        <td class = "topTitle centerAlign" name="state">{$smarty.const._OPERATIONS}</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}" {if $item.state == 'deleted'}style="color: gray;"{/if}>
            <td>{$item.course_name}</td>
            <td>{$item.gkey}</td>
            <td>{if $item.day_of_week == 7}niedziela{elseif $item.day_of_week == 6}sobota{elseif $item.day_of_week == 5}piątek{elseif $item.day_of_week == 4}czwartek{elseif $item.day_of_week == 3}środa{elseif $item.day_of_week == 2}wtorek{elseif $item.day_of_week == 1}poniedziałek{else}{$item.day_of_week}{/if}</td>
            <td>{$item.frequency}</td>
            <td>
                <span class="{if $item.next_meeting_date_changed}changedDate{/if}" title="niebieski kolor oznacza że termin spotkania jest inny niż ten cykliczny">{$item.next_lesson}</span>{if $item.next_lesson && !$item.is_not_allowed}
                    <a href = "{$T_MODULE_BASEURL}&c=group&x=changedate&gkey={$item.gkey}" onclick="return changeDate(this);" class = "editLink"><img border = "0" src = "images/16x16/edit.png" title = "{$smarty.const._EDIT}" alt = "{$smarty.const._EDIT}" /></a>&nbsp;
                {/if}
            </td>
            <td>{$item.pupils_count}</td>
            <td>{$item.professor_count}</td>
            <td>{if $item.last_history_date}<a title="{$item.last_history_what}" style="cursor: help;">{$item.last_history_date}</a>{else}-{/if}</td>
            <td class="centerAlign">
                {if !$item.is_not_allowed}
                    <a href = "{$T_MODULE_BASEURL}&c=group&b=uinfo&gkey={$item.gkey}" title="kontakt do członków grupy" alt="kontakt do członków grupy"><img src="images/16x16/users.png" alt="kontakt do członków grupy"/></a>&nbsp;
                    <a href = "{$T_MODULE_BASEURL}&c=group&a=modify&gkey={$item.gkey}" class = "editLink"><img border = "0" src = "images/16x16/edit.png" title = "{$smarty.const._EDIT}" alt = "{$smarty.const._EDIT}" /></a>&nbsp;
                    {if $item.state != 'deleted'}
                        <img class = "ajaxHandle" src = "images/16x16/trafficlight_{if $item.state == 'open'}green{else}red{/if}.png" alt = "Zielone - zajęcia w grupie w tym momencie są włączone, Czerwone - zajęcia są wyłączone" title = "Zielone - zajęcia w grupie w tym momencie są włączone, Czerwone - zajęcia są wyłączone" onclick = "activeDeactive(this, '{$T_MODULE_BASEURL}&c=group&x=openclose&gkey={$item.gkey}');">
                        {if $T_WHITEBOARD_IS_ADMIN}
                            <a href = "{$T_MODULE_BASEURL}&c=group&a=history&gkey={$item.gkey}" class = "editLink"><img border = "0" src = "images/16x16/analysis.png" title = "Przeglądaj historię grupy" alt = "Przeglądaj historię grupy" /></a>&nbsp;
                            <a onclick="return confirm('Na pewno chcesz usunać grupę \'{$item.name}\'?')" href = "{$T_MODULE_BASEURL}&c=group&a=delete&gkey={$item.gkey}" class = "editLink"><img border = "0" src = "images/16x16/error_delete.png" title = "Usuń" alt = "Usuń" /></a>&nbsp;
                        {/if}
                    {elseif $T_WHITEBOARD_IS_ADMIN}
                        <a href = "{$T_MODULE_BASEURL}&c=group&a=undelete&gkey={$item.gkey}" class = "editLink"><img border = "0" src = "images/16x16/undo.png" title = "Przywróć grupę" alt = "Przywróć grupę" /></a>&nbsp;
                    {/if}
                {/if}
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:groupDataTable-->