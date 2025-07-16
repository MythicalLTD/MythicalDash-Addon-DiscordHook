# MythicalDash Discord Webhook Addon

Send real-time notifications about important events in your MythicalDash instance directly to Discord channels.

## 🌟 Features

### User Events
- 👤 User Registration
  - Username and email
  - Direct link to admin panel
  - Avatar integration
- 🔐 User Login Tracking
  - Login timestamps
  - User details
  - Quick access to user profile
- 🗑️ User Deletion
  - Account removal confirmation
  - User identification details

### Server Events
- 🚀 Server Creation
  - Resource allocation details (RAM, CPU, Disk)
  - Server type and location
  - Configuration details (ports, databases, backups)
- 📝 Server Updates
  - Resource modifications
  - Feature limit changes
  - Before/after comparisons
- 🗑️ Server Deletion
  - Server identification
  - Deletion confirmation

### Support System
- 🎫 Ticket Creation
  - Priority-based color coding
  - Department assignment
  - Ticket details and initial message
  - Creator information

## 📥 Installation

### Method 1: Online Installation (Recommended)
```bash
php mythicaldash addon online-install
```
When prompted, enter the following URL:
```
https://github.com/MythicalLTD/MythicalDash-Addon-DiscordHook/releases/latest/download/discorduserhook.myd
```

### Method 2: Manual Installation
1. Download the addon package
2. Upload the file on your server in: `/var/www/mythicaldash-v3`
3. Run `php mythicaldash addon install` and enter the addon name!

## ⚙️ Configuration

### Discord Webhook Setup

#### Method 1: Using Setup Command (Recommended)
1. Run the setup command:
```bash
php mythicaldash discordWebHookSetup
```
2. Follow the interactive prompts:
   - Enter webhook URLs when prompted
   - Press Enter to skip any webhook you don't want to configure
   - Each webhook will be automatically tested after configuration

#### Method 2: Manual Configuration
1. In your Discord server:
   - Go to Server Settings → Integrations → Webhooks
   - Click "Create Webhook"
   - Name your webhook (e.g., "MythicalDash Notifications")
   - Choose the channel for notifications
   - Copy the webhook URL

2. Configure webhook URLs for each event type:
   - User Registration: `discord_webhook_url_registration`
   - User Login: `discord_webhook_url_login`
   - Server Creation: `discord_webhook_url_server_create`
   - Server Updates: `discord_webhook_url_server_update`
   - Server Deletion: `discord_webhook_url_server_delete`
   - Ticket Creation: `discord_webhook_url_ticket_create`

### Customization
- Each event type can be sent to different Discord channels
- Webhook URLs can be the same to consolidate notifications
- Leave a webhook URL empty to disable notifications for that event type

## 🎨 Notification Styling

### Priority Colors
- 🔴 Urgent/High: Red (#E74C3C)
- 🟡 Medium: Yellow (#F1C40F)
- 🟢 Low: Green (#2ECC71)
- 🟣 Default: Purple (#9B59B6)

### Message Format
- Rich embeds with consistent branding
- Emoji indicators for better visibility
- Direct links to admin panel
- User avatars when available
- Truncated messages for cleaner appearance

## 🔧 Troubleshooting

### Common Issues
1. **Notifications not sending**
   - Verify webhook URLs are correct
   - Check Discord channel permissions
   - Ensure the addon is properly installed
   - Run `php mythicaldash discordWebHookSetup` to test webhooks

2. **Missing Information**
   - Confirm user permissions
   - Verify database connections
   - Check log files for errors

3. **Database Issues**
   - If you experience webhook queue issues, run:
     ```bash
     php mythicaldash addon remove
	 # And install it again
     ```
   - This command will rebuild the webhook queue table

### Debug Mode
Enable debug logging in your MythicalDash configuration to track webhook delivery:
```php
'debug' => true
```

### Available Commands
- `discordWebHookSetup`: Configure and test webhook URLs

## 📚 Support

- 💬 [Discord Community](https://discord.mythical.systems)
- 🐛 [Issue Tracker](https://github.com/MythicalLTD/MythicalDash-Addon-DiscordHook/issues)

## 📄 License

This addon is part of MythicalDash and is covered under the MythicalSystems License v2.0.
Copyright (c) 2021–2025 MythicalSystems and Cassian Gherman