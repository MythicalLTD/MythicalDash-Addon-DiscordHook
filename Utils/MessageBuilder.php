<?php

namespace MythicalDash\Addons\discorduserhook\Utils;

class MessageBuilder {
    /**
     * Convert color name or hex to Discord color integer
     */
    private static function getColorValue(string $color): int 
    {
        $colors = [
            'default' => 0,
            'red' => 15158332,
            'green' => 3066993,
            'blue' => 3447003,
            'yellow' => 16776960,
            'purple' => 10181046,
            'orange' => 15105570,
            'grey' => 9807270,
            'black' => 2303786,
            'white' => 16777215,
            'mythical' => 7506394 // A nice purple color for brand
        ];

        // If it's a hex color
        if (preg_match('/^#?([a-fA-F0-9]{6})$/', $color, $matches)) {
            return hexdec($matches[1]);
        }

        // Return color from predefined list or default if not found
        return $colors[strtolower($color)] ?? $colors['default'];
    }

    /**
     * Create a Discord embed
     * @param string $title The embed title
     * @param array $options Additional embed options
     * @return array Discord embed structure
     */
    public static function getDiscordEmbed(string $title, array $options = []): array 
    {
        $embed = [
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $options['description'] ?? '',
                    'color' => self::getColorValue($options['color'] ?? 'mythical'),
                    'timestamp' => date('c')
                ]
            ]
        ];

        // Add fields if provided
        if (!empty($options['fields']) && is_array($options['fields'])) {
            $embed['embeds'][0]['fields'] = array_map(function($field) {
                return [
                    'name' => $field['name'] ?? '',
                    'value' => $field['value'] ?? '',
                    'inline' => $field['inline'] ?? false
                ];
            }, $options['fields']);
        }

        // Add author if provided
        if (!empty($options['author'])) {
            $embed['embeds'][0]['author'] = [
                'name' => $options['author'],
                'icon_url' => $options['author_icon'] ?? null
            ];
        }

        // Add thumbnail if provided
        if (!empty($options['thumbnail'])) {
            $embed['embeds'][0]['thumbnail'] = ['url' => $options['thumbnail']];
        }

        // Add image if provided
        if (!empty($options['image'])) {
            $embed['embeds'][0]['image'] = ['url' => $options['image']];
        }

        // Add footer if provided
        if (!empty($options['footer'])) {
            $embed['embeds'][0]['footer'] = [
                'text' => $options['footer'],
                'icon_url' => $options['footer_icon'] ?? null
            ];
        }

        // Add URL if provided
        if (!empty($options['url'])) {
            $embed['embeds'][0]['url'] = $options['url'];
        }

        return $embed;
    }

    /**
     * Send a Discord webhook message
     * @throws \RuntimeException If the webhook request fails
     */
    public static function sendDiscordMessage(array $data, string $webHookUrl): void 
    {
        $headers = ['Content-Type: application/json'];
        
        $ch = curl_init($webHookUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Failed to send Discord webhook: " . $error);
        }
        
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Discord webhook request failed with status code: " . $httpCode);
        }
    }
}