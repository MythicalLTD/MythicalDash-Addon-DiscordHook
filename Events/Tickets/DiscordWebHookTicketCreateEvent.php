<?php

namespace MythicalDash\Addons\discorduserhook\Events\Tickets;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\Chat\Tickets\Departments;
use MythicalDash\Plugins\PluginSettings;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\App;
use MythicalDash\Chat\User\User;
use MythicalDash\Chat\columns\UserColumns;

class DiscordWebHookTicketCreateEvent {

    public function __construct($ticket_id, $department_id, $subject, $message, $priority, $user_id) {
        $webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_ticket_create");
        if ($webhookUrl == null) {
            App::getInstance(true)->getLogger()->debug("No webhook url found for ticket create");
            return;
        }

        $settings = App::getInstance(true)->getConfig();
        $appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
        
        $userInfo = User::getInfoArray(User::getTokenFromUUID($user_id), [
            UserColumns::USERNAME,
            UserColumns::UUID,
            UserColumns::EMAIL,
            UserColumns::AVATAR
        ], []);

        if (empty($userInfo)) {
            App::getInstance(true)->getLogger()->debug("User info not found for ticket creation");
            return;
        }

        $department = Departments::getById((int)$department_id);
        $departmentName = $department['name'];

        $adminUrl = $appUrl . '/mc-admin/tickets/' . $ticket_id;
        
        // Get priority color
        $priorityColors = [
			'urgent' => 0xE74C3C,    // Red
            'high' => 0xE74C3C,    // Red
            'medium' => 0xF1C40F,   // Yellow
            'low' => 0x2ECC71      // Green
        ];
        $priorityColor = $priorityColors[strtolower($priority)] ?? 0x9B59B6; // Default purple if priority not found

        $embed = MessageBuilder::getDiscordEmbed(
            "🎫 New Support Ticket",
            [
                "title" => "New Support Ticket Created",
                "description" => "A new support ticket has been created.\n[View in Admin Panel]($adminUrl)",
                "color" => $priorityColor,
                "author" => $userInfo[UserColumns::USERNAME],
                "author_icon" => $userInfo[UserColumns::AVATAR],
                "fields" => [
                    [
                        "name" => "📋 Ticket Details",
                        "value" => "**ID:** #" . $ticket_id . 
                                 "\n**Department:** " . $departmentName . 
                                 "\n**Priority:** " . ucfirst($priority) . 
                                 "\n**Subject:** " . $subject,
                        "inline" => false
                    ],
                    [
                        "name" => "💬 Message",
                        "value" => strlen($message) > 200 ? substr($message, 0, 200) . "..." : $message,
                        "inline" => false
                    ],
                    [
                        "name" => "👤 Created By",
                        "value" => "**Username:** " . $userInfo[UserColumns::USERNAME] . "\n**Email:** " . $userInfo[UserColumns::EMAIL],
                        "inline" => false
                    ]
                ],
                "footer" => "MythicalDash",
                "footer_icon" => "https://github.com/mythicalltd.png",
                "url" => $adminUrl
            ]
        );

        $discordWebHookDB = new DiscordWebHookDB();
        $discordWebHookDB->createWebhookEntry($embed, $webhookUrl);
    }
}