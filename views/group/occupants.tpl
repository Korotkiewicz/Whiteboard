<table id="occupantList">
    <thead>
        <tr>
            <th>Imię i nazwisko</th>
            <th>Login</th>
            <th>e-mail</th>
            <th>Kraj (ustawiona strefa czasowa)</th>
            {if $T_WHITEBOARD_SHOW_PHONE}
                <th>Telefon</th>
            {/if}
            <th>Skype</th>
        </tr>
    </thead>
    <tbody>
        {assign var="skype" value=""}
        {assign var="email" value=""}
        {foreach item='user' key='key' from=$T_WHITEBOARD_OCCUPANTS}
            <tr id="login_{$user.login}">
                <td>{$user.name} {$user.surname}</td>
                <td>{$user.login}</td>
                <td>{assign var="newEMail" value=$user.email}{assign var="email" value="`$email`;`$newEMail`"}<a href="mailto:{$user.email}">{$user.email}</a></td>
                <td>{$user.country_name} (GMT {$user.timezoneOffset})</td>
                {if $T_WHITEBOARD_SHOW_PHONE}
                    <td>{if $user.Telefon && $user.Telefon|strlen > 4}<a href="callto://{$user.Telefon}">{$user.Telefon}</a>{/if}</td>
                {/if}
                <td>{if $user.Skype}{assign var="newSkype" value=$user.Skype}{assign var="skype" value="`$skype`;`$newSkype`"}<a href="skype:{$user.Skype}"><img src="{$T_WHITEBOARD_RELATIVELINK}/img/skype.png"/> {$user.Skype}</a>{/if}</td>
            </tr>    
        {/foreach}
        {if $T_WHITEBOARD_SHOW_PHONE}
            <tr>
                <td></td>
                <td></td>
                <td><a href="mailto:{$email|substr:1}">napisz do wszystkich <img src="images/16x16/mail.png"/></a></td>
                <td></td>
                <td></td>
                <td><a href="skype:{$skype|substr:1}?call">Połaczenie grupowe na Skype: <img src="{$T_WHITEBOARD_RELATIVELINK}/img/skype.png"/></a></td>
            </tr>
        {/if}
    </tbody>
</table>