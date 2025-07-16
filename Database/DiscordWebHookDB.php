<?php

namespace MythicalDash\Addons\discorduserhook\Database;

use MythicalDash\App;

class DiscordWebHookDB {
    private const TABLE_NAME = 'mythicaldash_addon_discorduserhook_queue';

    /**
     * Create a new webhook queue entry
     * @param array $messageData The message data to be sent
     * @param string $webhookUrl The Discord webhook URL
     * @return int|false The ID of the created entry or false on failure
     */
    public function createWebhookEntry(array $messageData, string $webhookUrl): int|false {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $messageJson = json_encode($messageData);
        $stmt = $mysqli->prepare("
            INSERT INTO " . self::TABLE_NAME . " 
            (message_json, webhook_url) 
            VALUES (?, ?)
        ");

        $stmt->bind_param("ss", $messageJson, $webhookUrl);
        
        if ($stmt->execute()) {
            return $mysqli->insert_id;
        }
        
        return false;
    }

    /**
     * Get a webhook queue entry by ID
     * @param int $id The entry ID
     * @return array|null The entry data or null if not found
     */
    public function getWebhookEntry(int $id): ?array {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $stmt = $mysqli->prepare("
            SELECT * FROM " . self::TABLE_NAME . "
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $entry = $result->fetch_assoc();
        
        if ($entry) {
            $entry['message_data'] = json_decode($entry['message_json'], true);
        }
        
        return $entry ?: null;
    }

    /**
     * Get pending webhook entries
     * @param int $limit Maximum number of entries to return
     * @return array Array of pending entries
     */
    public function getPendingWebhooks(int $limit = 10): array {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $stmt = $mysqli->prepare("
            SELECT * FROM " . self::TABLE_NAME . "
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT ?
        ");

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $entries = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['message_data'] = json_decode($row['message_json'], true);
            $entries[] = $row;
        }
        
        return $entries;
    }

    /**
     * Update webhook entry status
     * @param int $id The entry ID
     * @param string $status New status value
     * @return bool Success status
     */
    public function updateWebhookStatus(int $id, string $status): bool {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $stmt = $mysqli->prepare("
            UPDATE " . self::TABLE_NAME . "
            SET status = ?
            WHERE id = ?
        ");

        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    /**
     * Delete a webhook entry
     * @param int $id The entry ID
     * @return bool Success status
     */
    public function deleteWebhookEntry(int $id): bool {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $stmt = $mysqli->prepare("
            DELETE FROM " . self::TABLE_NAME . "
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Clean up old webhook entries
     * @param int $daysOld Number of days old to clean up
     * @return int Number of entries deleted
     */
    public function cleanupOldEntries(int $daysOld = 30): int {
        $mysqli = App::getInstance(true)->getDatabase()->getMysqli();
        
        $stmt = $mysqli->prepare("
            DELETE FROM " . self::TABLE_NAME . "
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND status != 'pending'
        ");

        $stmt->bind_param("i", $daysOld);
        $stmt->execute();
        
        return $mysqli->affected_rows;
    }
}