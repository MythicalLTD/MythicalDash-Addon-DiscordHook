<?php

namespace MythicalDash\Addons\discorduserhook\Events\Server;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Chat\User\User;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookServerDeleteEvent
{

    /**
     * Constructor for server update webhook
     */
    public function __construct($id)
    {
        $webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_server_delete");
        if ($webhookUrl == null) {
            App::getInstance(true)->getLogger()->debug("No webhook url found for server delete");
            return;
        }

		$settings = App::getInstance(true)->getConfig();
		$adminUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
		$adminUrl = $adminUrl . "/mc-admin/server/" . $id;
        // Create embed
        $discordWebHookDB = new DiscordWebHookDB();
        $embed = MessageBuilder::getDiscordEmbed(
            "Server Deleted",
            [
                "description" => "Server has been deleted. (ID: $id) [View Server in Admin Panel]($adminUrl)",
                "color" => "blue",
                "footer" => "MythicalDash • Server ID: " . $id,
                "footer_icon" => "https://github.com/mythicalltd.png",
                "timestamp" => date('c')
            ]
        );

        $discordWebHookDB->createWebhookEntry($embed, $webhookUrl);
    }
}