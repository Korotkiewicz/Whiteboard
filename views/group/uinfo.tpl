{capture name = "viewContent"}
    <div class="toolbar">
        <span>
            <a href="javascript:javascript:history.back()">
                <img src = "images/16x16/go_back.png"/>
                powrót
            </a>
        </span>
    </div>
    <div class="content">
        <h3>Członkowie grupy:</h3>
        {if $T_WHITEBOARD_OCCUPANTS}
            {include file=$T_WHITEBOARD_OCCUPANTS_TPL}
        {else}
            Brak członków
        {/if}
    </div>
{/capture}

{eF_template_printBlock title=$T_WHITEBOARD_TITLE data = $smarty.capture.viewContent absoluteImagePath="1" image="images/32x32/users.png"}