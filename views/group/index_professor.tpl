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
        </div>
        <div class="content">
            {include file=$T_WHITEBOARD_TPL}
        </div>
        
        <div style="clear:both">&nbsp;</div>
    {/capture}

    {assign var='imgSrc' value="`$T_MODULE_BASELINK`img/class_32x32.png"}
    {eF_template_printBlock title='Moje grupy' data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}
{/if}