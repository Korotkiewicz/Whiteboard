{if $T_WHITEBOARD_FORM_ERROR}
    {eF_template_printMessageBlock content = 'Nastapił błąd' type = 'failure'}
{/if}
{if $T_WHITEBOARD_FORM_SUCCESS}
    {eF_template_printMessageBlock content = 'Zapisano' type = 'success'}
{/if}

<script type="text/javascript">
    var startTime = '{$T_WHITEBOARD_START_TIME}000';
    
    {literal}
    window.onload = function() {
        setNextGroupDate();
    }
    {/literal}
</script>

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
        {$T_WHITEBOARD_FORM.javascript}

        <form {$T_WHITEBOARD_FORM.attributes}>
            {$T_WHITEBOARD_FORM.hidden}

            <div class="l-form">
                <div class="l-form-header">
                    <h1>Wypełnij formularz</h1>
                </div>
                <div class="l-form-example">
                    <h2>{$smarty.const._EXAMPLES}</h2>
                </div>
                <div class="clear"></div>

                <div class="l-form-example">p1ew_g1</div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.gkey.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.gkey.html}</div>
                <div class="clear"></div>

                <div class="l-form-example"></div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.course_id.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.course_id.html}</div>
                <div class="clear"></div>

                <!--                <div class="l-form-example">Edukacja Wczesnoszkolna - Klasa 1 - Grupa 1</div>
                                <div class="l-form-label">{$T_WHITEBOARD_FORM.name.label}:</div>
                                <div class="l-form-element">{$T_WHITEBOARD_FORM.name.html}</div>
                                <div class="clear"></div>-->

                <div class="l-form-example">Sobota</div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.day_of_week.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.day_of_week.html}</div>
                <div class="clear"></div>

                <div class="l-form-example">08:30</div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.time.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.time.html}</div>
                <div class="clear"></div>

                <div class="l-form-example">1 - oznacza co tydzień<br/>2 - oznacza co drugi tydzień</div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.frequency.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.frequency.html}</div>
                <div class="clear"></div>

                <div class="l-form-example">Tzn. czy zajęcia zaczynają się w pierwszym tygodniu, czy dopiero po 'n' tygodniach</div>
                <div class="l-form-label">{$T_WHITEBOARD_FORM.shift_week.label}:</div>
                <div class="l-form-element">{$T_WHITEBOARD_FORM.shift_week.html} <div id="nextGroupDay"></div></div>
                <div class="clear"></div>
                

                {if $T_WHITEBOARD_IS_MODIFY}
                    <div class="line-dotted"></div>
                    <div class="l-form-example"></div>
                    <div class="l-form-label">Lista uczestników grupy:</div>
                    <div class="l-form-element">
                        {if $T_WHITEBOARD_PUPILS}
                            <ol style="margin: -4px 0px 0px 0px; padding: 0px 0px 0px 20px;">
                                {foreach item = "user" key = "key" from = $T_WHITEBOARD_PUPILS}
                                    <li>{$user.name} {$user.surname} ({$user.login}) 
                                        <a href="{$T_MODULE_BASEURL}&c=group&a=users&gkey={$gkey}&login={$user.login}" title="Usuń z grupy"><img src="images/16x16/error_delete.png"/></a>
                                    </li>
                                {/foreach}
                            </ol>
                        {/if}
                        &nbsp;- &nbsp;&nbsp;<a href="{$T_MODULE_BASEURL}&c=group&a=users&gkey={$gkey}" class="editLink">[ wybierz uczestników grupy ]</a>
                    </div>
                    <div class="clear"></div>

                    {if $T_WHITEBOARD_IS_ADMIN}
                        <div class="line-dotted"></div>
                        <div class="l-form-example"></div>
                        <div class="l-form-label">Lista prowadzących grupę:</div>
                        <div class="l-form-element">
                            {if $T_WHITEBOARD_PROFESSORS}
                                <ol style="margin: -4px 0px 0px 0px; padding: 0px 0px 0px 20px;">
                                    {foreach item = "user" key = "key" from = $T_WHITEBOARD_PROFESSORS}
                                        <li>{$user.name} {$user.surname} ({$user.login}) 
                                            <a href="{$T_MODULE_BASEURL}&c=group&a=users&gkey={$gkey}&login={$user.login}" title="Usuń z grupy"><img src="images/16x16/error_delete.png"/></a>
                                        </li>
                                    {/foreach}
                                </ol>
                            {/if}
                            &nbsp;- &nbsp;&nbsp;<a href="{$T_MODULE_BASEURL}&c=group&a=users&type=professor&gkey={$gkey}" class="editLink">[ wybierz profesorów grupy ]</a>
                        </div>
                        <div class="clear"></div>
                    {/if}
                    <br/>
                {/if}

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
{assign var='imgSrc' value="`$T_MODULE_BASELINK`img/class_32x32.png"}
{eF_template_printBlock title=$T_WHITEBOARD_TITLE data = $smarty.capture.viewContent absoluteImagePath="1" image=$imgSrc}