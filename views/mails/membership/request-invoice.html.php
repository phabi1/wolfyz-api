<?php $this->layout()->extend('mail'); ?>
<?php $this->layout()->startBlock('title'); ?>Votre facture<?php $this->layout()->endBlock('title'); ?>
<?php $this->layout()->startBlock('content'); ?>
<p style="margin: 0 0 18px; font-size: 18px; line-height: 1.6; color: #1f2937;">Bonjour <?php echo $firstname ?? ''; ?> <?php echo $lastname ?? ''; ?>,</p>
<p style="margin: 0 0 18px; font-size: 16px; line-height: 1.6; color: #475569;">Votre facture pour la campagne <strong>"<?php echo $campaignName; ?>"</strong> est disponible via le lien sécurisé ci-dessous.</p>
<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">Montant : <strong><?php echo number_format(((int) ($invoiceAmount ?? 0)) / 100, 2, ',', ' '); ?> <?php echo $invoiceCurrency ?? 'EUR'; ?></strong><br>Référence facture : <strong>#<?php echo $requestId ?? ''; ?></strong></p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 24px auto;"><tr><td align="center" bgcolor="#9e1c1c" style="border-radius: 10px;"><a href="<?php echo $invoiceDownloadUrl; ?>" style="display: inline-block; padding: 14px 28px; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 10px;">Télécharger ma facture</a></td></tr></table>
<p style="margin: 0; font-size: 13px; line-height: 1.6; color: #64748b;">Si le bouton ne fonctionne pas, utilisez ce lien : <a href="<?php echo $invoiceDownloadUrl; ?>" style="color: #9e1c1c;"><?php echo $invoiceDownloadUrl; ?></a></p>
<?php $this->layout()->endBlock('content'); ?>
