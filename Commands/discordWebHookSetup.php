<?php

namespace MythicalDash\Addons\discorduserhook\Commands;

use MythicalDash\Addons\discorduserhook\DiscordUserHook;
use MythicalDash\Cli\App;
use MythicalDash\Cli\CommandBuilder;
use MythicalDash\Plugins\PluginSettings;

class discordWebHookSetup extends DiscordUserHook implements CommandBuilder
{
    
    private static array $webhookTypes = [
        'user_registration' => [
            'name' => 'User Registration',
            'setting' => 'discord_webhook_url_registration',
            'description' => 'Sends notifications when new users register'
        ],
        'user_login' => [
            'name' => 'User Login',
            'setting' => 'discord_webhook_url_login',
            'description' => 'Sends notifications when users log in'
        ],
        'server_create' => [
            'name' => 'Server Creation',
            'setting' => 'discord_webhook_url_server_create',
            'description' => 'Sends notifications when new servers are created'
        ],
        'server_update' => [
            'name' => 'Server Updates',
            'setting' => 'discord_webhook_url_server_update',
            'description' => 'Sends notifications when servers are modified'
        ],
        'server_delete' => [
            'name' => 'Server Deletion',
            'setting' => 'discord_webhook_url_server_delete',
            'description' => 'Sends notifications when servers are deleted'
        ],
        'ticket_create' => [
            'name' => 'Ticket Creation',
            'setting' => 'discord_webhook_url_ticket_create',
            'description' => 'Sends notifications when new support tickets are created'
        ]
    ];

    /**
     * Validates a Discord webhook URL
     */
    private static function isValidWebhookUrl(?string $url): bool
    {
        if (empty($url)) {
            return true; // Empty URL is valid (means skip/disable)
        }
        return (bool) preg_match('/^https:\/\/(discord\.com|discordapp\.com)\/api\/webhooks\/\d+\/[\w-]+$/', $url);
    }

    /**
     * Gets the webhook URL from settings
     */
    private static function getWebhookUrl(string $setting): ?string
    {
        $url = PluginSettings::getSetting('discorduserhook', $setting);
        return !empty($url) ? $url : null;
    }

    /**
     * Configures a single webhook type
     */
    private static function configureWebhook(App $app, string $type, array $info): void
    {
        $app->send("\n&b" . $info['name'] . " Webhook");
        $app->send("&7" . $info['description']);
        $app->send("&7Enter webhook URL or press Enter to skip:");
        
        while (true) {
            $url = trim(readline());
            
            if (empty($url)) {
                $app->send("&7Skipping " . $info['name'] . " webhook...");
                return;
            }
            
            if (self::isValidWebhookUrl($url)) {
                PluginSettings::setSetting('discorduserhook', $info['setting'], $url);
                $app->send("&a✓ " . $info['name'] . " webhook configured successfully!");
                return;
            }
            
            $app->send("&cInvalid Discord webhook URL. Please try again or press Enter to skip:");
        }
    }

    /**
     * Tests a configured webhook by sending a test message
     */
    private static function testWebhook(App $app, string $url, string $name): void
    {
        if (empty($url))
            return;

        $data = json_encode([
            'content' => '🔔 Test notification from MythicalDash Discord Webhook addon',
            'embeds' => [
                [
                    'title' => 'Test Notification',
                    'description' => "This is a test notification for the $name webhook.\nIf you see this, your webhook is configured correctly!",
                    'color' => 0x9B59B6,
                    'timestamp' => date('c')
                ]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 204) {
            $app->send("&a✓ Test message sent successfully to $name webhook!");
        } else {
            $app->send("&c✗ Failed to send test message to $name webhook (HTTP $httpCode)" . ($error ? ": $error" : ""));
        }
    }

    /**
     * @inheritDoc
     */
    public static function execute(array $args): void
    {
        $app = App::getInstance();
        
        $app->send("\n&b=== MythicalDash Discord Webhook Setup ===");
        $app->send("&7Configure Discord webhooks for different events.");
        $app->send("&7For each webhook, enter the URL or press Enter to skip.\n");
        
        foreach (self::$webhookTypes as $type => $info) {
            self::configureWebhook($app, $type, $info);
        }
        
        $app->send("\n&b=== Testing Configured Webhooks ===");
        $hasConfigured = false;
        foreach (self::$webhookTypes as $type => $info) {
            $url = self::getWebhookUrl($info['setting']);
            if (!empty($url)) {
                $hasConfigured = true;
                self::testWebhook($app, $url, $info['name']);
            }
        }
        
        if (!$hasConfigured) {
            $app->send("&7No webhooks were configured to test.");
        }
        
        $app->send("\n&a✓ Discord webhook setup complete!");
        $app->send("&7You can run this command again anytime to update your webhook configuration.");
        exit;
    }
    
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return "Setup Discord WebHook Addon";
    }
    
    /**
     * @inheritDoc
     */
    public static function getSubCommands(): array
    {
        return [];
    }
}