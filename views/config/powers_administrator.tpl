{capture name = "viewContent"}
    
{include file=$T_WHITEBOARD_TPL}

{/capture}

{eF_template_printBlock title='Zarządzanie uprawnieniami' data = $smarty.capture.viewContent image="32x32/users.png"}