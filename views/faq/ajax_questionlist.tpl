<script type="text/javascript">
    {literal}
    window.onSortedTableComplete =  function () {
        disableRow = function disableRow () {  };
        enableRow = function enableRow () {  };
    }
    {/literal}
</script>

<!--ajax:questionlistDataTable-->
<table class = "sortedTable" size="{$T_WHITEBOARD_DATA_SIZE}" id="questionlistDataTable" useAjax="1" rowsPerPage="20" noFooter="true" url="{$T_MODULE_BASEURL}&c=faq&x=questionlist&" style="width: 100%">
    <tr class="topTitle">
        <td class="topTitle noSort">Nazwa przedmiotu</td>
        <td class = "topTitle noSort" style="width: 200px;">{$smarty.const._OPERATIONS}</td>
    </tr>
    {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
        <tr class="{cycle values="oddRowColor,evenRowColor "}">
            <td>
                <img id="toggleImgToClass_{$item.gkey}" src = "images/16x16/plus2.png" class = "ajaxHandle" alt = "Pytania do grupy" title = "Pytania do grupy" onclick = "toggleSubSection(this, '{$item.gkey}', 'questionlistDataTableSubSection');"/>
                <a onclick="toggleSubSection(document.getElementById('toggleImgToClass_{$item.gkey}'), '{$item.gkey}', 'questionlistDataTableSubSection');" style="cursor: pointer;" title="pokaż/ukryj pytania">{$item.course_name} <span style="color: gray;">({$item.gkey})</span></a>
            </td>
            <td>
                {if $T_WHITEBOARD_USER_TYPE != 'administrator'}
                <a href="{$T_MODULE_BASEURL}&c=faq&b=ask&gkey={$item.gkey}"><img src = "images/16x16/add.png" alt = "Dodaj pytanie" title = "Dodaj pytanie"/> Zadaj pytanie</a>
                {/if}
            </td>
        </tr>
    {/foreach}
</table>
<!--/ajax:questionlistDataTable-->

<div {if !$T_WHITEBOARD_QUESTION_VIEW}style="display: none;"{/if}>
<!--ajax:questionlistDataTableSubSection-->
<table class = "sortedTable subSection" size="{$T_WHITEBOARD_DATA_SIZE}" sortBy="1" order="asc" id="questionlistDataTableSubSection" useAjax="1" url="{$T_MODULE_BASEURL}&c=faq&x=questionlist&" style="width: 100%">
        <tr class="topTitle">
            <td class="topTitle" name="modified">Data</td>
            {if $T_WHITEBOARD_USER_TYPE != 'student'}
            <td class="topTitle" name="login">Autor</td>
            {/if}
            <td class="topTitle" name="week">Tydzień nauki</td>
            <td class="topTitle" name="question">Treść pytania</td>
            <td class="topTitle" name="state">Stan</td>
            <td class = "topTitle centerAlign noSort">{$smarty.const._OPERATIONS}</td>
        </tr>
        {foreach name = 'apply_data' item = 'item' key = 'key' from = $T_WHITEBOARD_DATA}
            <tr class="{cycle values="oddRowColor,evenRowColor "}">
                <td>
                    {$item.modified}
                </td>
                {if $T_WHITEBOARD_USER_TYPE != 'student'}
                <td>
                    #filter:login-{$item.login}#
                </td>
                {/if}
                <td>
                    {$item.week}
                </td>
                <td>
                    <a title="{$item.question}">{$item.question|substr:0:200}{if $item.question|strlen > 200}...{/if}</a>
                </td>
                <td>
                    {if $item.state == 'public'}<span style="color: blue;">odpowiedź opublikowana</span>{elseif $item.state == 'draft'}<span style="color: darkgreen;">odpowiedź robocza</span>{else}brak odpowiedzi{/if}
                </td>
                <td class="centerAlign">
                    {if $item.is_author}
                        <a href="{$T_MODULE_BASEURL}&c=faq&b=ask&gkey={$item.gkey}&qid={$item.id}" title="edytuj pytanie"><img src = "images/16x16/edit.png" alt = "edytuj pytanie" title = "edytuj pytanie"/></a>
                        <img class="ajaxHandle" src = "images/16x16/error_delete.png" alt = "usuń pytanie" title = "usuń pytanie" onclick="if(confirm('Czy na pewno chcesz usunąć pytanie?')) {literal}{{/literal} removeElement(this, '{$T_MODULE_BASEURL}&c=faq&b=delete_question&gkey={$item.gkey}&qid={$item.id}'); {literal}}{/literal}"/>
                    {/if}
                        {if $T_WHITEBOARD_USER_TYPE != 'student'}
                            <a href="{$T_MODULE_BASEURL}&c=faq&a=answer&gkey={$item.gkey}&qid={$item.id}" title="{if $item.has_answer}edytuj {/if}odpowiedź na pytanie"><img src = "images/16x16/{if !$item.has_answer}comment_add{else}job_descriptions{/if}.png"/></a>
                        {/if}
                </td>
            </tr>
        {/foreach}
</table>
<!--/ajax:questionlistDataTableSubSection-->
</div>