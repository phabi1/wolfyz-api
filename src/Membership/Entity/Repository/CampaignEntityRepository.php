<?php
namespace App\Membership\Entity\Repository;

use App\Core\Db\Exception\DbException;

use App\Core\Entity\EntityRepository;

class CampaignEntityRepository extends EntityRepository implements CampaignEntityRepositoryInterface
{

    function updateSettings(int $campaignId, array $settings)
    {
        if (empty($settings)) {
            return;
        }

        $handler = $this->db->getHandler();

        $pathsAndValues = [];
        foreach (array_keys($settings) as $key) {
            $settingValue = $settings[$key];

            if (is_array($settingValue)) {
                $encodedValue = json_encode($settingValue, JSON_UNESCAPED_UNICODE);
                $sqlValue = "CAST('" . addslashes((string) $encodedValue) . "' AS JSON)";
            } elseif (is_string($settingValue)) {
                $sqlValue = "'" . addslashes($settingValue) . "'";
            } elseif (is_bool($settingValue)) {
                $sqlValue = $settingValue ? 'true' : 'false';
            } elseif ($settingValue === null) {
                $sqlValue = "CAST('null' AS JSON)";
            } else {
                $sqlValue = (string) $settingValue;
            }

            $pathsAndValues[] = "'$." . addslashes($key) . "', " . $sqlValue;
        }

        $value = 'settings = JSON_SET(settings, ' . implode(', ', $pathsAndValues) . ')';
        $sql = 'UPDATE `' . $handler->prefix . 'wolf_memberships_campaign` SET ' . $value . ' WHERE id = ' . (int) $campaignId;
        $handler->query($sql);
    }
}