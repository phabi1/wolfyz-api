<?php
$this->layout()->extend('mail')
    ?>
<?php
$this->layout()->startBlock('title');
?>
Nouvelle demande
<?php
$this->layout()->endBlock('title');
?>
<?php
$this->layout()->startBlock('content');
?>
<p style="margin: 0 0 18px; font-size: 18px; line-height: 1.6; color: #1f2937;">
    Une nouvelle demande a été soumise pour la campagne <strong>"<?php echo $campaignName; ?>"</strong>.
</p>

<p style="margin: 0 0 10px; font-size: 15px; line-height: 1.7; color: #475569;">
    <strong>Membre :</strong> <?php echo $memberName; ?> (<?php echo $memberEmail; ?>)<br>
    <strong>ID de la demande :</strong> <?php echo $requestId; ?>
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 28px auto;">
    <tr>
        <td align="center" bgcolor="#9e1c1c" style="border-radius: 10px;">
            <a href="<?php echo $adminUrl; ?>"
                style="display: inline-block; padding: 14px 28px; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 10px;">
                Voir la demande
            </a>
        </td>
    </tr>
</table>

<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
    Si le bouton ne fonctionne pas, utilisez ce lien :
</p>
<p style="margin: 12px 0 0; font-size: 14px; line-height: 1.6; word-break: break-all; color: #2563eb;">
    <a href="<?php echo $adminUrl; ?>" style="color: #9e1c1c; text-decoration: none;">
        <?php echo $adminUrl; ?>
    </a>
</p>
<?php
$this->layout()->endBlock('content');
?>