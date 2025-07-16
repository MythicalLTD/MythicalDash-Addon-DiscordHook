<?php
namespace MythicalDash\Addons\discorduserhook\Events\User;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Chat\User\User;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookUserDeleteEvent {

	public function __construct($uuid) {
		$webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_user_delete");
		if ($webhookUrl == null) {
			App::getInstance(true)->getLogger()->debug("No webhook url found for user delete");
			return;
		}

		$settings = App::getInstance(true)->getConfig();
		$appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
		$userToken = User::getTokenFromUUID($uuid);
		if ($userToken == null) {
			App::getInstance(true)->getLogger()->debug("User token not found for user delete");
			return;
		}

		$userInfo = User::getInfoArray($userToken, [
			UserColumns::USERNAME,
			UserColumns::UUID,
			UserColumns::EMAIL,
			UserColumns::AVATAR,
			UserColumns::UUID
		], []);
		$discordWebHookDB = new DiscordWebHookDB();
		$adminUrl = $appUrl .'/mc-admin/users/'. $userInfo[UserColumns::UUID].'/edit';
		$embed = MessageBuilder::getDiscordEmbed(
			"👤 User Deleted",	
			[
				"title" => "User Deleted",
				"description" => "A user has been deleted from MythicalDash.\n[View in Admin Panel]($adminUrl)",
				"color" => "mythical",
				"author" => $userInfo[UserColumns::USERNAME],	
				"author_icon" => $userInfo[UserColumns::AVATAR],
				"fields" => [
					[
						"name" => "📋 User Details",
						"value" => "**Username:** " . $userInfo[UserColumns::USERNAME] . "\n**Email:** " . $userInfo[UserColumns::EMAIL] . "\n**UUID:** " . $userInfo[UserColumns::UUID],
						"inline" => false
					],
				],
				"footer" => "MythicalDash",
				"footer_icon" => "https://github.com/mythicalltd.png",
				"url" => $adminUrl
			]
		);

		$discordWebHookDB->createWebhookEntry($embed, $webhookUrl);
	}
}