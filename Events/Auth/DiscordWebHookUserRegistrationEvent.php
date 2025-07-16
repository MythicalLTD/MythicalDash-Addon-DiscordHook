<?php
namespace MythicalDash\Addons\discorduserhook\Events\Auth;

use MythicalDash\Addons\discorduserhook\Database\DiscordWebHookDB;
use MythicalDash\Addons\discorduserhook\Utils\MessageBuilder;
use MythicalDash\App;
use MythicalDash\Config\ConfigInterface;
use MythicalDash\Chat\User\User;
use MythicalDash\Chat\columns\UserColumns;
use MythicalDash\Plugins\PluginSettings;

class DiscordWebHookUserRegistrationEvent {

	public function __construct($username, $email) {
		$webhookUrl = PluginSettings::getSetting("discorduserhook", "discord_webhook_url_user_registration");
		if ($webhookUrl == null) {
			App::getInstance(true)->getLogger()->debug("No webhook url found for user registration");
			return;
		}

		$settings = App::getInstance(true)->getConfig();
		$appUrl = $settings->getSetting(ConfigInterface::APP_URL, "null");
		$userToken = User::getTokenFromEmail($email);
		if ($userToken == null) {
			App::getInstance(true)->getLogger()->debug("User token not found for user registration");
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
			"👤 New User Registration",
			[
				"title" => "New User Registration",
				"description" => "A new user has registered on MythicalDash.\n[View in Admin Panel]($adminUrl)",
				"color" => "mythical",
				"author" => $username,	
				"author_icon" => $userInfo[UserColumns::AVATAR],
				"fields" => [
					[
						"name" => "📋 User Details",
						"value" => "**Username:** $username\n**Email:** $email",
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