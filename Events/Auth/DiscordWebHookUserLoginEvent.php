<?php
namespace MythicalDash\Addons\discorduserhook\Events\Auth;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Chat\User\User;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookUserLoginEvent {

	public function __construct($login) {
		$webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_user_login");
		if ($webhookUrl == null) {
			App::getInstance(true)->getLogger()->debug("No webhook url found for user login");
			return;
		}

		$settings = App::getInstance(true)->getConfig();
		$appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
		$userToken = User::getTokenFromEmail($login);
		if ($userToken == null) {
			App::getInstance(true)->getLogger()->debug("User token not found for user login");
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
			"👤 User Login",
			[
				"title" => "User Login",
				"description" => "A user has logged in to MythicalDash.\n[View in Admin Panel]($adminUrl)",
				"color" => "mythical",
				"author" => $userInfo[UserColumns::USERNAME],	
				"author_icon" => $userInfo[UserColumns::AVATAR],
				"fields" => [
					[
						"name" => "📋 User Details",
						"value" => "**Username:** " . $userInfo[UserColumns::USERNAME] . "\n**Email:** " . $userInfo[UserColumns::EMAIL],
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