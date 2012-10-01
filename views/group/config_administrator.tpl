{if $T_WHITEBOARD_AJAX}
    {include file=$T_WHITEBOARD_TPL}
{else}
    {capture name = "viewContent"}
        <div class="toolbar">
            <span>
                <a href="{$T_MODULE_BASEURL}&c=group&a=modify">
                    <img src = "images/16x16/add.png"/>
                    dodaj nową grupę
                </a>
            </span>
            <span>
                <a href="{$T_MODULE_BASEURL}&c=group&a=schedule">
                    <img src = "images/16x16/calendar.png"/>
                    pokaż grafik
                </a>
            </span>
        </div>
        <div class="content">
            {include file=$T_WHITEBOARD_TPL}
        </div>
        
        <div style="clear:both">&nbsp;</div>
    {/capture}

    {eF_template_printBlock title='Zarządzanie grupami' data = $smarty.capture.viewContent image="32x32/tools.png"}
{/if}