{if $T_WHITEBOARD_FORM_ERROR}
    {eF_template_printMessageBlock content = 'Nastapił błąd' type = 'failure'}
{/if}
{if $T_WHITEBOARD_FORM_SUCCESS}
    {eF_template_printMessageBlock content = 'Zapisano' type = 'success'}
{/if}

{capture name = "viewContent"}
    <div id="content">
    {$T_WHITEBOARD_FORM.javascript}

        <form {$T_WHITEBOARD_FORM.attributes}>
    {$T_WHITEBOARD_FORM.hidden}

            <div class="l-form">
                <div class="l-form-header">
                    <h1>Wypełnij formularz</h1>
                </div>
                <div class="l-form-example">
                    <h2></h2>
                </div>
                <div class="clear"></div>

                <div class="l-form-example"></div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.week.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.week.html}</div>
                <div class="clear"></div>

                <div class="l-form-example"></div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.question.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.question.html}</div>
                <div class="clear"></div>

                <div class="l-form-submit">
                    <div class="l-form-label"><span style="color:red">*</span> pola wymagane oznaczone są gwiazdką</div>
                    <div class="l-form-element" style="width: auto;">
                        {$T_WHITEBOARD_FORM.submit.html}
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </form>
    </div>
    <div style="clear:both">&nbsp;</div>
{/capture}

{assign var='imgSrc' value="`$T_MODULE_BASELINK`img/question_32x32.png"}
{eF_template_printBlock title='Zadaj pytanie' data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}