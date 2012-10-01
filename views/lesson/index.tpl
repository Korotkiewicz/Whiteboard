{capture name = "viewContent"}
    {if $T_CORRECT_BROWSER}

        <div id="rooms" style="text-align: center;">
        Wybierz zajęcia:
        <div style="clear:both">&nbsp;</div>
        {if $T_WHITEBOARD_GROUPS}
            {foreach name = 'groups' item = 'item' key = 'key' from = $T_WHITEBOARD_GROUPS}
                <a id="room_{$item.gkey}" class="classBox">
                   <img src="{$T_MODULE_BASELINK}img/class_128x128.png" height="128"/>
                    <br/>
                    {$item.course_name}
                    <br/>
                    {$item.next_lesson}
                </a>
            {/foreach}
        {else}Jeszcze nie przypisano do żadnej grupy zajęciowej.{/if}
        </div>

        <script type="text/javascript">
            {literal}
            window.onload = function () {
                listenToOpenGroups({/literal}'{$T_WHITEBOARD_BASEURL}&c=lesson'{literal});
            }
            {/literal}
        </script>

    {else}
        <h2 style="color: darkred; font-weight: bold;">Aby móc uczestniczyć w zajęciach należy zainstalować i uruchomić jedną z następujących przeglądarek:</h2>
        <ul>
            <li><img src="{$T_MODULE_BASELINK}img/firefoxlogo.png" alt="Pobierz Firefox za darmo" title="Pobierz Firefox za darmo" height="10" style="margin-left: -18px;"/> &nbsp;<b><a href="http://www.mozilla.com/pl/firefox/" target="_blank" style="color: darkblue">Firefox 10.0 lub nowsza (pobierz tutaj)</a></b></li>
            <li>Google Chrome 9.0 lub nowsza</li>
            <li>Opera 10 lub nowsza</li>
            <li>Internet Explorer 9.0 lub nowsza</li>
            <li>Safari 4.0 lub nowsza</li>
        </ul>
    {/if}

    <div style="clear:both">&nbsp;</div>
{/capture}

{assign var='imgSrc' value="`$T_MODULE_BASELINK`img/class_32x32.png"}
{eF_template_printBlock title='Moje zajęcia' data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}