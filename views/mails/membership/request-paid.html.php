<?php $this->layout()->extend('mail'); ?>
<?php $this->layout()->startBlock('title'); ?>Paiement reçu<?php $this->layout()->endBlock('title'); ?>
<?php $this->layout()->startBlock('content'); ?>
<p style="margin: 0 0 18px; font-size: 18px; line-height: 1.6; color: #1f2937;">Votre demande pour la campagne <strong>"<?php echo $campaignName; ?>"</strong> a bien été payée.</p>
<p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #475569;">Merci pour votre paiement. Vous pouvez maintenant profiter de votre adhésion.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 24px auto;"><tr><td align="center" bgcolor="#9e1c1c" style="border-radius: 10px;"><a href="<?php echo $invoiceUrl; ?>" style="display: inline-block; padding: 14px 28px; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 10px;">Télécharger ma facture</a></td></tr></table>
<p style="margin: 0 0 12px; font-size: 13px; line-height: 1.6; color: #64748b;">Si le bouton ne fonctionne pas, utilisez ce lien : <a href="<?php echo $invoiceUrl; ?>" style="color: #9e1c1c;"><?php echo $invoiceUrl; ?></a></p>
<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">Si vous avez des questions, n'hésitez pas à nous contacter.</p>
<p style="margin: 12px 0 0; font-size: 14px; line-height: 1.6; color: #475569;"><a href="<?php echo $contactUrl; ?>">Contactez-nous</a></p>
<?php $this->layout()->endBlock('content'); ?>
