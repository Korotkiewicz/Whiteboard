{if $T_WHITEBOARD_FORM_ERROR}
    {eF_template_printMessageBlock content = 'Nastapił błąd' type = 'failure'}
{/if}
{if $T_WHITEBOARD_FORM_SUCCESS}
    {eF_template_printMessageBlock content = 'Zapisano' type = 'success'}
{/if}

<script type="text/javascript">
    {literal}
                var updateState = function () {
                    var input = document.getElementById('use_old_question');
                    if(input.value == 'true') {
                        document.getElementById('question_old').setAttribute('class', 'answerquestion_block active');
                        document.getElementById('question_new').setAttribute('class', 'answerquestion_block');
                    } else {
                        document.getElementById('question_old').setAttribute('class', 'answerquestion_block');
                        document.getElementById('question_new').setAttribute('class', 'answerquestion_block active');
                    }
                }
                    
                var setState = function (state) {
                    document.getElementById('use_old_question').value = state;
                        
                    updateState();
                }
                    
                window.onload = updateState;
    {/literal}
</script>

{capture name = "viewQuestion"}
    {if $T_WHITEBOARD_QUESTION.old_gkey || $T_WHITEBOARD_QUESTION.old_week || $T_WHITEBOARD_QUESTION.old_question}
        <fieldset class="answerquestion_block" id="question_old" onclick="setState('true')">
            <legend>Pytanie na które odpowiedziano</legend>
            <table>
                <tr>
                    <td style="width: 100px;">Data odpowiedzi:</td>
                    <td>{$T_WHITEBOARD_QUESTION.modified}</td>
                </tr>
                <tr>
                    <td>Autor pytania:</td>
                    <td>#filter:login-{$T_WHITEBOARD_QUESTION.login}#</td>
                </tr>
                <tr>
                    <td>Dotyczy grupy:</td>
                    <td>{if $T_WHITEBOARD_QUESTION.old_gkey}<span style="color: red;">{$T_WHITEBOARD_QUESTION.old_gkey}</span>{else}{$T_WHITEBOARD_QUESTION.gkey}{/if}, tydzień {if $T_WHITEBOARD_QUESTION.old_week}<span style="color: red;">{$T_WHITEBOARD_QUESTION.old_week}</span>{else}{$T_WHITEBOARD_QUESTION.week}{/if}</td>
                </tr>
                <tr>
                    <td>Treść pytania:</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2"><br/><i>{if $T_WHITEBOARD_QUESTION.old_question}<span style="color: red;">{$T_WHITEBOARD_QUESTION.old_question}</span>{else}{$T_WHITEBOARD_QUESTION.question}{/if}</i></td>
                </tr>
            </table>
        </fieldset>
    {/if}
    <fieldset class="answerquestion_block" id="question_new" onclick="setState('false')">
        <legend>Aktualne pytanie</legend>
        <table>
            <tr>
                <td style="width: 100px;">Data pytania:</td>
                <td>{$T_WHITEBOARD_QUESTION.modified}</td>
            </tr>
            <tr>
                <td>Autor pytania:</td>
                <td>#filter:login-{$T_WHITEBOARD_QUESTION.login}#</td>
            </tr>
            <tr>
                <td>Dotyczy grupy:</td>
                <td>{$T_WHITEBOARD_QUESTION.gkey}, tydzień {$T_WHITEBOARD_QUESTION.week}</td>
            </tr>
            <tr>
                <td>Treść pytania:</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2"><br/><i>{$T_WHITEBOARD_QUESTION.question}</i></td>
            </tr>
        </table>
    </fieldset>
{/capture}

{capture name = "viewContent"}
    <div id="content">

        {$T_WHITEBOARD_FORM.javascript}

        <form {$T_WHITEBOARD_FORM.attributes}>
            {$T_WHITEBOARD_FORM.hidden}
            <table class="formElements" style="width:100%;margin-left:0px;">
                <tr id="editorRow" >
                    <td class="elementCell" >
                        {$T_WHITEBOARD_FORM.answer.html}
                    </td>
                </tr>

                <tr>
                    <td style="vertical-align:middle">
                        <img style="vertical-align:middle" src="images/16x16/order.png" title="{$smarty.const._TOGGLEHTMLEDITORMODE}" alt="{$smarty.const._TOGGLEHTMLEDITORMODE}" />
                        <a href="javascript:toggleEditor('answer','mceEditor');" id="toggleeditor_link">{$smarty.const._TOGGLEHTMLEDITORMODE}</a>
                    </td>
                </tr>
                {if $T_WHITEBOARD_FORM.version}
                    <tr>
                        <td><br/>{$T_WHITEBOARD_FORM.version.label}: {$T_WHITEBOARD_FORM.version.html}</td>
                    </tr>
                {/if}
                {if $T_WHITEBOARD_FORM.answer.error}
                    <tr>
                        <td class = "formError">{$T_WHITEBOARD_FORM.answer.error}</td>
                    </tr>
                {/if}

                <tr>
                    <td colspan = "100%" class = "submitCell">
                        {$T_WHITEBOARD_FORM.submitBtn.html}
                    </td>
                </tr>
            </table>
        </form>

    </div>
    <div style="clear:both">&nbsp;</div>
{/capture}

{assign var='imgSrc' value="`$T_MODULE_BASELINK`img/question_32x32.png"}
{eF_template_printBlock title='Pytanie' data = $smarty.capture.viewQuestion  absoluteImagePath="1" image=$imgSrc}
{eF_template_printBlock title='Odpowiedź na pytanie' data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}