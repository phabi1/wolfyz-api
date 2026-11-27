<?php

namespace App\Membership;

/**
 * Membership permissions of the auth server, used by the routes
 * (config/routes/membership/*.php).
 */
final class Permissions
{
    public const CAMPAIGN_VIEW = 'membership.campaign.view';
    public const CAMPAIGN_EDIT = 'membership.campaign.edit';
    public const REQUEST_VIEW = 'membership.request.view';
    public const REQUEST_EDIT = 'membership.request.edit';
    public const SUBSCRIPTION_VIEW = 'membership.subscription.view';
    public const SUBSCRIPTION_EDIT = 'membership.subscription.edit';

    /** Any membership view: the campaign structure is read by every membership page. */
    public const ANY_VIEW = [self::CAMPAIGN_VIEW, self::REQUEST_VIEW, self::SUBSCRIPTION_VIEW];
}
