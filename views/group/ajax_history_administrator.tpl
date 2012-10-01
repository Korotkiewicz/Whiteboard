<!--ajax:groupDataTable-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="1" order="desc" id="groupDataTable" useAjax="1" rowsPerPage="20" url="{$T_MODULE_BASEURL}&c=group&x=history&gkey={$gkey}&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle" name="gkey">Grupa</td>
        <td class="topTitle" name="date">Data</td>
        <td class="topTitle" name="login">Login</td>
        <td class = "topTitle" name="what">Co się stało/co zostało zmienione</td>
        <td class = "topTitle" name="value">Na co</td>
    </tr>

    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>{$item.gkey}</td>
            <td>{$item.date}</td>
            <td>{$item.login}</td>
            <td>
                <a title="{$item.what}" style="cursor: help;">
                    {if $item.what == 'state'}
                        Stan grupy
                    {elseif $item.what == 'data'}
                        Informacje o grupie
                    {elseif $item.what == 'user'}
                        Uczestnicy/Prowadzący grupę
                    {elseif $item.what == 'room'}
                        Użytkownk wszedł na zajęcia
                    {else}
                        {$item.what}
                    {/if}
                </a>
            </td>
            <td>
                {if $item.what == 'data'}
                    <a title="{$item.value|unserialize|print_r}" style="color: gray; cursor: help;">&gt;&gt;<i>dane grupy</i>&lt;&lt;</a>
                {elseif $item.what == 'room'}
                    <a title="{$item.value}" style="color: gray; cursor: help;">&gt;&gt;<i>dane przeglądarki</i>&lt;&lt;</a>
                {elseif $item.value == 'open'}
                    <a title="{$item.value}" style="cursor: help; color: green;">otwarta</a>
                {elseif $item.value == 'closed'}
                    <a title="{$item.value}" style="cursor: help; color: orange;">zamknięta</a>
                {elseif $item.value == 'deleted'}
                    <a title="{$item.value}" style="cursor: help; color: red;">usunięta</a>
                {else}
                    {$item.value}
                {/if}
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:groupDataTable-->