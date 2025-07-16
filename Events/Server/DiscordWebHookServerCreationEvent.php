<?php

namespace MythicalDash\Addons\discorduserhook\Events\Server;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Chat\User\User;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookServerCreationEvent
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
     * @param int $sv
     * @param string $name
     * @param string $description
     * @param int $ram
     * @param int $disk
     * @param int $cpu
     * @param int $ports	
     * @param int $databases	
     * @param int $backups
     * @param string $location
     * @param string $user
     * @param string $nest
     * @param string $egg
     */
    public function __construct($sv, $name, $description, $ram, $disk, $cpu, $ports, $databases, $backups, $location, $user, $nest, $egg)
    {
        $webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_server_creation");
        if ($webhookUrl == null) {
			App::getInstance(true)->getLogger()->debug("No webhook url found for server creation");
            return;
        }

        $userToken = User::getTokenFromUUID($user);
		$settings = App::getInstance(true)->getConfig();
        $userInfo = User::getInfoArray($userToken, [
            UserColumns::USERNAME,
            UserColumns::UUID,
            UserColumns::EMAIL,
			UserColumns::AVATAR
        ], []);

        // Format resources
        $formattedRam = $this->formatBytes($ram * 1024 * 1024); // Convert MB to bytes
        $formattedDisk = $this->formatBytes($disk * 1024 * 1024);

		$appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
        $adminUrl = $appUrl .'/mc-admin/server-queue';

        $discordWebHookDB = new DiscordWebHookDB();
        $embed = MessageBuilder::getDiscordEmbed(
            "🎮 New Server Created",
            [
                "url" => $adminUrl,
                "description" => "A new server has been queued for creation in MythicalDash.\n[View in Admin Panel]($adminUrl)",
                "color" => "mythical",
                "author" => $userInfo[UserColumns::USERNAME],
                "author_icon" => $userInfo[UserColumns::AVATAR],
                "fields" => [
                    [
                        "name" => "📋 Server Details",
                        "value" => "**Name:** $name\n**Description:** $description",
                        "inline" => false
                    ],
                    [
                        "name" => "💾 Resources",
                        "value" => "RAM: $formattedRam\nDisk: $formattedDisk\nCPU: $cpu%",
                        "inline" => true
                    ],
                    [
                        "name" => "🔧 Configuration",
                        "value" => "Ports: $ports\nDatabases: $databases\nBackups: $backups",
                        "inline" => true
                    ],
                    [
                        "name" => "📍 Location",
                        "value" => $location,
                        "inline" => true
                    ],
                    [
                        "name" => "🥚 Server Type",
                        "value" => "**Nest:** $nest\n**Egg:** $egg",
                        "inline" => true
                    ]
                ],
                "footer" => "MythicalDash • Server ID: $sv",
                "footer_icon" => "https://github.com/mythicalltd.png",
                "timestamp" => date('c')
            ]
        );

        $discordWebHookDB->createWebhookEntry($embed, $webhookUrl);
    }
}