{capture name ="table"}
    <table id="group_schedule" style="border-top: 1px solid #ccc; font-size: 10px;">
        <thead>
            <tr>
                <th></th>
                {foreach item="item" key="key" from=$T_WHITEBOARD_WEEKS}
                    <th>{$key}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody>
            {foreach item="row" key="rowId" from=$T_WHITEBOARD_DATA}
                <tr>
                    <td>
{if $rowId == 1}Pn{elseif $rowId == 2}Wt{elseif $rowId == 3}Śr{elseif $rowId == 4}Cz{elseif $rowId == 5}Pt{elseif $rowId == 6}Sb{elseif $rowId == 7}Nd{else}{$rowId}{/if} 
</td>
{foreach item="cell" key="colId" from=$row}
    <td {if $cell}style="background: #ddddff; {if $cell|@count > 3}font-size: 10px;{/if}"{/if}>
        {if $cell}
{foreach item="data" key="iter" from=$cell}{if $iter > 0}{if $cell.$oldIter.time != $data.time}<div class="line"> </div>{assign var="showTime" value=1}{else}{assign var="showTime" value=0}{/if}{else}{assign var="showTime" value=1}{/if}<span>{if $showTime}({$data.time|substr:0:5} {$data.date}) {else}, {/if}<a href = "{$T_MODULE_BASEURL}&c=group&a=modify&gkey={$data.gkey}&go_back_to_schedule=1" class = "editLink">{if $smarty.get.show == 'course_name'}{$data.course_name}{else}{$data.gkey}{/if}</a></span>{assign var="oldIter" value=$iter}{/foreach}
{/if}
</td>
{/foreach}
</tr>
{/foreach}
</tbody>
</table>
{/capture}

{if $smarty.get.export == 'pdf'}
    <script type="text/javascript">
        {literal}
        window.onload = function () {
            document.title = 'Grafik spotkan cotygodniowych';
            window.print();
            history.back();
        }
        {/literal}
    </script>
    
    {$smarty.capture.table}
{else}
    {capture name = "viewContent"}
        <div class="toolbar">
            <span>
                <a href="{$T_MODULE_BASEURL}&c=group&a=config">
                    <img src = "images/16x16/go_back.png"/>
                    powrót
                </a>
            </span>
            <span>
                <a onclick="window.location = window.location + '&export=pdf&popup=1'" title="Drukuj" style="cursor: pointer;"><img src="images/16x16/printer.png" alt="Drukuj"/></a>
            </span>
            <span>
                Pokaż:
                <select onchange="window.location = window.location + '&show=' + this.value">
                    <option value="gkey">Indentyfikatory grup</option>
                    <option value="course_name" {if $smarty.get.show == 'course_name'}selected{/if}>Nazwy przedmioty</option>
                </select>
            </span>
            <span>
                Przedmiot:
                <select onchange="window.location = window.location + '&course=' + this.value">
                    <option value="all">Wszystkie</option>
                    {foreach item="course_name" key="course_id" from=$T_WHITEBOARD_COURSES}
                        <option value="{$course_id}" {if $smarty.get.course == $course_id}selected{/if}>{$course_name}</option>
                    {/foreach}
                </select>
            </span>
            <span>
                Nauczyciel:
                <select onchange="window.location = window.location + '&teacher=' + this.value">
                    <option value="all">Wszystkie</option>
                    {foreach item="teacher_gkey" key="teacher" from=$T_WHITEBOARD_TEACHERS}
                        <option value="{$teacher}" {if $smarty.get.teacher == $teacher}selected{/if}>{$teacher}</option>
                    {/foreach}
                </select>
            </span>

        </div>
        <div class="content">
            {$smarty.capture.table}
        </div>

    {/capture}

    {eF_template_printBlock title='Grafik grup' data = $smarty.capture.viewContent image="32x32/calendar.png"}
{/if}