{capture name = "viewContent"}
    <div id="content">
        {include file=$T_WHITEBOARD_TPL}
    </div>
    <div style="clear:both">&nbsp;</div>
{/capture}

{assign var='imgSrc' value="`$T_MODULE_BASELINK`img/question_32x32.png"}
{eF_template_printBlock title=$T_WHITEBOARD_TITLE data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}