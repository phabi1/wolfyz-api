<?php $this->layout()->extend('mail'); ?>
<?php $this->layout()->startBlock('title'); ?>Demande rejetée<?php $this->layout()->endBlock('title'); ?>
<?php $this->layout()->startBlock('content'); ?>
<p style="margin: 0 0 18px; font-size: 18px; line-height: 1.6; color: #1f2937;">Votre demande pour la campagne <strong>"<?php echo $campaignName; ?>"</strong> a bien été rejetée.</p>
<p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #475569;">Malheureusement, votre demande pour cette campagne a été rejetée.</p>
<p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #475569;">Raison du rejet : <strong><?php echo $reason; ?></strong></p>
<p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #475569;">Vous pouvez modifier votre demande en cliquant sur le bouton ci-dessous.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 28px auto;"><tr><td align="center" bgcolor="#9e1c1c" style="border-radius: 10px;"><a href="<?php echo $editUrl; ?>" style="display: inline-block; padding: 14px 28px; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 10px;">Modifier la demande</a></td></tr></table>
<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">Si le bouton ne fonctionne pas, vous pouvez utiliser ce lien :</p>
<p style="margin: 12px 0 0; font-size: 14px; line-height: 1.6; word-break: break-all;"><a href="<?php echo $editUrl; ?>" style="color: #9e1c1c;"><?php echo $editUrl; ?></a></p>
<?php $this->layout()->endBlock('content'); ?>
