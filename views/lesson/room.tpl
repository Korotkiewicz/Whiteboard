{if $T_CORRECT_BROWSER}
    <link rel="stylesheet" href="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/union.css?version=1.25" type="text/css" />
    <link rel="stylesheet" href="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/whiteboard.css?version=1.26" type="text/css" />
    <link rel="stylesheet" href="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/chat.css?version=1.25" type="text/css" />
    <link rel="stylesheet" href="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/colorPicker/colorPicker.css?version=1.25" type="text/css" />
    <link rel="stylesheet" href="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/thicknessPicker/thicknessPicker.css?version=1.25" type="text/css" />

    <!--[if lt IE 9]>
    <script src="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/union/excanvas.js"></script>
    <![endif]-->
<!--    <script type="text/javascript" src="js/OrbiterMicro_2.0.0.767_Beta3/OrbiterMicro_2.0.0.767_Beta3.js"></script>-->
    <script type="text/javascript" src="js/Orbiter_2.0.0.768_Beta3/Orbiter_2.0.0.768_Beta3_fix.js?version=1.01"></script>
<!--    <script type="text/javascript" src="http://cdn.unioncloud.io/OrbiterMicro_latest.js"></script>-->
    <script src="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/union/extend.js?version=1.25"></script>
    <script src="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/union/UnionFunc.js?version=1.31"></script>

    <script language="javascript" type="text/javascript" src="js/jquery/jquery-1.7.1.js"></script>
    <script language="javascript" type="text/javascript" src="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/colorPicker/jquery.colorPicker.min.js?version=1.25"/></script>
<script language="javascript" type="text/javascript" src="{$T_WHITEBOARD_RELATIVELINK}js/whiteboard/thicknessPicker/jquery.thicknessPicker.js?version=1.25"/></script>

<script type="text/javascript">
    {literal}
      //Run the code when document ready
        jQuery(function() {    
    {/literal}
            init('{$T_WHITEBOARD_RELATIVELINK}js/whiteboard', 'whiteboard', '{$T_WHITEBOARD_LOGIN}', '{$T_WHITEBOARD_PASSWORD}', '{$T_WHITEBOARD_ROOM_ID}', '{$T_WHITEBOARD_CONFIG.server.ip}', '{$T_WHITEBOARD_CONFIG.server.port}', '{$T_WHITEBOARD_CONFIG.whiteboard.color.background}', '{$T_WHITEBOARD_CONFIG.whiteboard.color.line}');
    {literal}
        });
    {/literal}
</script>

{capture name = "viewContent"}
    <div id="whiteboard"></div>
    <div>
        {if $T_WHITEBOARD_OCCUPANTS}
            <h3>Członkowie grupy:</h3>
            {include file=$T_WHITEBOARD_OCCUPANTS_TPL}
        {/if}
    </div>
{/capture}

{else}
    {capture name = "viewContent"}
        <h2 style="color: darkred; font-weight: bold;">Aby móc uczestniczyć w zajęciach należy zainstalować i uruchomić jedną z następujących przeglądarek:</h2>
        <ul>
            <li><img src="{$T_WHITEBOARD_RELATIVELINK}img/firefoxlogo.png" alt="Pobierz Firefox za darmo" title="Pobierz Firefox za darmo" height="10" style="margin-left: -18px;"/> &nbsp;<b><a href="http://www.mozilla.com/pl/firefox/" target="_blank" style="color: darkblue">Firefox 10.0 lub nowsza (pobierz tutaj)</a></b></li>
            <li>Google Chrome 9.0 lub nowsza</li>
            <li>Opera 10 lub nowsza</li>
            <li>Internet Explorer 9.0 lub nowsza</li>
            <li>Safari 4.0 lub nowsza</li>
        </ul>
    {/capture}
{/if}

{if $T_WHITEBOARD_DOJO}
    {assign var='imgSrc' value="`$T_WHITEBOARD_RELATIVELINK`img/dojo_32x32.png"}
{else}
    {assign var='imgSrc' value="`$T_WHITEBOARD_RELATIVELINK`img/class_32x32.png"}
{/if}
{eF_template_printBlock title=$T_WHITEBOARD_TITLE data = $smarty.capture.viewContent options=$T_LESSON_OPTIONS help='helpRoom' absoluteImagePath="1" image=$imgSrc}