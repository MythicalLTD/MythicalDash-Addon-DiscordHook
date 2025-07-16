<?php

namespace MythicalDash\Addons\discorduserhook;

use MythicalDash\Addons\discorduserhook\Events\Auth\DiscordWebHookUserRegistrationEvent;
use MythicalDash\Addons\discorduserhook\Events\Auth\DiscordWebHookUserLoginEvent;
use MythicalDash\Addons\discorduserhook\Events\Server\DiscordWebHookServerCreationEvent;
use MythicalDash\Addons\discorduserhook\Events\Server\DiscordWebHookServerUpdateEvent;
use MythicalDash\Addons\discorduserhook\Events\Server\DiscordWebHookServerDeleteEvent;
use MythicalDash\Addons\discorduserhook\Events\Tickets\DiscordWebHookTicketCreateEvent;
use MythicalDash\Addons\discorduserhook\Events\User\DiscordWebHookUserDeleteEvent;
use MythicalDash\Plugins\Events\Events\AuthEvent;
use MythicalDash\Plugins\Events\Events\ServerEvent;
use MythicalDash\Plugins\Events\Events\ServerQueueEvent;
use MythicalDash\Plugins\Events\Events\TicketEvent;
use MythicalDash\Plugins\Events\Events\UserEvent;
use MythicalDash\Plugins\MythicalDashPlugin;
use MythicalDash\App;
use MythicalDash\Cli\App as AppCli;
use MythicalDash\Chat\Database;

class DiscordUserHook implements MythicalDashPlugin
{

    /**
     * @inheritDoc
     */
    public static function processEvents(\MythicalDash\Plugins\PluginEvents $event): void
    {
        $event->on(ServerQueueEvent::onServerQueueCreated(), function ($sv, $name, $description, $ram, $disk, $cpu, $ports, $databases, $backups, $location, $user, $nest, $egg) {
            new DiscordWebHookServerCreationEvent($sv, $name, $description, $ram, $disk, $cpu, $ports, $databases, $backups, $location, $user, $nest, $egg);
        });

        $event->on(AuthEvent::onAuthRegisterSuccess(), function ($username, $email) {
            new DiscordWebHookUserRegistrationEvent($username, $email);
        });

        $event->on(AuthEvent::onAuthLoginSuccess(), function ($login) {
            new DiscordWebHookUserLoginEvent($login);
        });

        $event->on(UserEvent::onUserDelete(), function ($uuid) {
            new DiscordWebHookUserDeleteEvent($uuid);
        });

        $event->on(ServerEvent::onServerUpdated(), function ($server, $updateData, $details) {
            new DiscordWebHookServerUpdateEvent($server, $updateData, $details);
        });

        $event->on(ServerEvent::onServerDeleted(), function ($server) {
            new DiscordWebHookServerDeleteEvent($server);
        });

        $event->on(TicketEvent::onTicketCreate(), function ($ticket_id, $department_id, $subject, $message, $priority, $user_id) {
            new DiscordWebHookTicketCreateEvent($ticket_id, $department_id, $subject, $message, $priority, $user_id);
        });
    }

    /**
     * @inheritDoc
     */
    public static function pluginInstall(): void
    {
        $appCli = AppCli::getInstance();
        $app = App::getInstance(true, true);

        if (!file_exists(__DIR__ . '/../../../storage/.env')) {
            $app->getLogger()->warning('Executed a command without a .env file');
            $appCli->send('The .env file does not exist. Please create one before running this command');
            exit;
        }

        try {
            $app->loadEnv();
            $db = new Database($_ENV['DATABASE_HOST'], $_ENV['DATABASE_DATABASE'], $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], $_ENV['DATABASE_PORT']);
            $mysqli = $db->getMysqli();
        } catch (\Exception $e) {
            $appCli->send('&cFailed to connect to the database: &r' . $e->getMessage());
            exit;
        }

        $logger = $app->getLogger();

        $appCli->send("&aInstalling Discord user hook...");
        $appCli->send("&aCreating Discord user hook table...");

        $mysqli->query("
            CREATE TABLE IF NOT EXISTS `mythicaldash_addon_discorduserhook_queue` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `message_json` TEXT NOT NULL,
                `webhook_url` VARCHAR(255) NOT NULL,
                `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $logger->info("Discord user hook table created");
        $appCli->send("&aDiscord user hook table created");
    }

    /**
     * @inheritDoc
     */
    public static function pluginUninstall(): void
    {
        $appCli = AppCli::getInstance();
        $app = App::getInstance(true, true);

        if (!file_exists(__DIR__ . '/../../../storage/.env')) {
            $app->getLogger()->warning('Executed a command without a .env file');
            $appCli->send('The .env file does not exist. Please create one before running this command');
            exit;
        }

        try {
            $app->loadEnv();
            $db = new Database($_ENV['DATABASE_HOST'], $_ENV['DATABASE_DATABASE'], $_ENV['DATABASE_USER'], $_ENV['DATABASE_PASSWORD'], $_ENV['DATABASE_PORT']);
            $db = $db->getMysqli();
        } catch (\Exception $e) {
            $appCli->send('&cFailed to connect to the database: &r' . $e->getMessage());
            exit;
        }

        $logger = $app->getLogger();

        $appCli->send("&aUninstalling Discord user hook...");
        $appCli->send("&aDropping Discord user hook table...");

        $db->query("DROP TABLE IF EXISTS `mythicaldash_addon_discorduserhook_queue`");

        $logger->info("Discord user hook table dropped");
        $appCli->send("&aDiscord user hook table dropped");
    }
}
