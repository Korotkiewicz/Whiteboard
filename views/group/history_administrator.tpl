{capture name = "viewContent"}
    <div class="toolbar">
        <span>
            <a href="{$T_MODULE_BASEURL}&c=group&a=config">
                <img src = "images/16x16/go_back.png"/>
                powrót
            </a>
        </span>
    </div>
    <div class="content">
        {include file=$T_WHITEBOARD_TPL}
    </div>

{/capture}

{eF_template_printBlock title=$T_WHITEBOARD_TITLE data = $smarty.capture.viewContent image='32x32/users.png'}