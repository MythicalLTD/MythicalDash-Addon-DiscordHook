<?php

namespace MythicalDash\Addons\discorduserhook\Cron;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\DiscordUserHook;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\Cli\App;
use MythicalDash\Cron\Cron;
use MythicalDash\Cron\TimeTask;
use MythicalDash\App as MainApp;

class SendWebHooks extends DiscordUserHook implements TimeTask {
    
    /**
     * Run the cronjob
     */
    public function run(): void {
        $cron = new Cron("send-webhooks","1M");
        $cron->runIfDue(function() {
        });
		$this->processWebhooks();

    }

    /**
     * Process pending webhooks and send test message
     */
    private function processWebhooks(): void {
        App::sendOutputWithNewLine('&aRunning send webhooks cronjob...');

        $app = MainApp::getInstance(true);
        $logger = $app->getLogger();
        
        // Create a test message
        $discordWebHookDB = new DiscordWebHookDB();

        // Process all pending webhooks
        $rows = $discordWebHookDB->getPendingWebhooks(29); // Discord limits to 30 so we want to be safe
        $sentCount = 0;

        foreach ($rows as $row) {
            try {
                MessageBuilder::sendDiscordMessage($row['message_data'], $row['webhook_url']);
                $discordWebHookDB->updateWebhookStatus($row['id'], 'sent');
                $sentCount++;
            } catch (\RuntimeException $e) {
                $logger->error("Failed to send webhook ID {$row['id']}: " . $e->getMessage());
                $discordWebHookDB->updateWebhookStatus($row['id'], 'failed');
            }
        }

        // Cleanup old entries
        $cleanedCount = $discordWebHookDB->cleanupOldEntries(7); // Clean entries older than 7 days
        if ($cleanedCount > 0) {
            $logger->info("Cleaned up $cleanedCount old webhook entries");
        }

        App::sendOutputWithNewLine("&aSent $sentCount messages to Discord");
    }
}