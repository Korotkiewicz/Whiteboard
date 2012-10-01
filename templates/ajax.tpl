{include file=$T_VIEW"}

<script type="text/javascript">//<![CDATA[
    {literal}
        jQuery(document).ready(function () { 
            var form_options = { 
                target: '#dialog-form',
                beforeSubmit: function() {
                    jQuery('#dialog-form').html('<div class="loader"></div>');
                }
            };
            
            jQuery('form.ajax').ajaxForm( form_options );
            jQuery(window).resize().resize();
        });
    {/literal}
    //]]></script>

{if $T_REDIRECT}
    <script type="text/javascript">
            window.open('{$T_REDIRECT}', 'mainframe');
    </script>
{/if}
