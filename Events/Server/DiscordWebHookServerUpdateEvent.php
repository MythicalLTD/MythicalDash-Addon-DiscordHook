<?php

namespace MythicalDash\Addons\discorduserhook\Events\Server;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Chat\User\User;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookServerUpdateEvent
{
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Compare old and new values and return a formatted string showing the change
     */
    private function formatChange($oldValue, $newValue, string $format = '%s'): string 
    {
        if ($oldValue == $newValue) {
            return sprintf($format, $newValue);
        }
        return sprintf($format, $newValue) . " *(was: " . sprintf($format, $oldValue) . ")*";
    }

    /**
     * Format resource changes
     */
    private function getResourceChanges(array $server, array $updateData): array 
    {
        $oldMemory = $server['attributes']['limits']['memory'];
        $oldDisk = $server['attributes']['limits']['disk'];
        $oldCpu = $server['attributes']['limits']['cpu'];
        $oldDatabases = $server['attributes']['feature_limits']['databases'];
        $oldBackups = $server['attributes']['feature_limits']['backups'];
        $oldAllocations = $server['attributes']['feature_limits']['allocations'];

        // Convert memory values to bytes before formatting
        $oldMemoryBytes = $oldMemory * 1024 * 1024; // Convert MB to bytes
        $newMemoryBytes = $updateData['memory'] * 1024 * 1024;
        
        // Convert disk values to bytes before formatting
        $oldDiskBytes = $oldDisk * 1024 * 1024;
        $newDiskBytes = $updateData['disk'] * 1024 * 1024;

        return [
            'memory' => $this->formatChange(
                $this->formatBytes($oldMemoryBytes),
                $this->formatBytes($newMemoryBytes)
            ),
            'disk' => $this->formatChange(
                $this->formatBytes($oldDiskBytes),
                $this->formatBytes($newDiskBytes)
            ),
            'cpu' => $this->formatChange($oldCpu, $updateData['cpu'], '%d%%'),
            'databases' => $this->formatChange($oldDatabases, $updateData['feature_limits']['databases']),
            'backups' => $this->formatChange($oldBackups, $updateData['feature_limits']['backups']),
            'allocations' => $this->formatChange($oldAllocations, $updateData['feature_limits']['allocations'])
        ];
    }

    /**
     * Constructor for server update webhook
     */
    public function __construct(array $server, array $updateData, array $details)
    {
        $webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_server_update");
        if ($webhookUrl == null) {
            App::getInstance(true)->getLogger()->debug("No webhook url found for server updates");
            return;
        }

        // Get user info
        $userToken = User::getTokenFromUUID($details['user']);
        $settings = App::getInstance(true)->getConfig();
        $userInfo = User::getInfoArray($userToken, [
            UserColumns::USERNAME,
            UserColumns::UUID,
            UserColumns::EMAIL,
            UserColumns::AVATAR
        ], []);

        // Get resource changes
        $changes = $this->getResourceChanges($server, $updateData);

        // Build the admin URL
        $appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
        $adminUrl = $appUrl . '/mc-admin/server/' . $server['attributes']['id'];

        // Create embed
        $discordWebHookDB = new DiscordWebHookDB();
        $embed = MessageBuilder::getDiscordEmbed(
            "🔄 Server Updated",
            [
                "url" => $adminUrl,
                "description" => "Server configuration has been updated.\n[View Server in Admin Panel]($adminUrl)",
                "color" => "blue",
                "author" => $userInfo[UserColumns::USERNAME],
                "author_icon" => $userInfo[UserColumns::AVATAR],
                "fields" => [
                    [
                        "name" => "📋 Server Details",
                        "value" => sprintf(
                            "**Name:** %s\n**Description:** %s",
                            $this->formatChange($server['attributes']['name'], $details['name']),
                            $this->formatChange($server['attributes']['description'], $details['description'])
                        ),
                        "inline" => false
                    ],
                    [
                        "name" => "💾 Resources",
                        "value" => sprintf(
                            "RAM: %s\nDisk: %s\nCPU: %s",
                            $changes['memory'],
                            $changes['disk'],
                            $changes['cpu']
                        ),
                        "inline" => true
                    ],
                    [
                        "name" => "🔧 Limits",
                        "value" => sprintf(
                            "Databases: %s\nBackups: %s\nAllocations: %s",
                            $changes['databases'],
                            $changes['backups'],
                            $changes['allocations']
                        ),
                        "inline" => true
                    ],
                    [
                        "name" => "📍 Location",
                        "value" => $server['location']['name'],
                        "inline" => true
                    ],
                    [
                        "name" => "🥚 Server Type",
                        "value" => sprintf(
                            "**Nest:** %s\n**Egg:** %s",
                            $server['category']['name'],
                            $server['service']['name']
                        ),
                        "inline" => true
                    ]
                ],
                "footer" => "MythicalDash • Server ID: " . $server['attributes']['id'],
                "footer_icon" => "https://github.com/mythicalltd.png",
                "timestamp" => date('c')
            ]
        );

        $discordWebHookDB->createWebhookEntry($embed, $webhookUrl);
    }
}