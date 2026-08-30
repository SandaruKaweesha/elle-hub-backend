<?php
require_once __DIR__ . "/../../config/Database.php";

class NotificationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Send notification to a specific single user
     */
    public function sendToUser(int $userId, string $title, string $message, string $type = 'SYSTEM'): bool
    {
        try {
            $stmtNotif = $this->db->prepare("INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, ?, NOW())");
            $stmtNotif->execute([$title, $message, strtoupper($type)]);
            $notificationId = (int) $this->db->lastInsertId();

            $stmtUserNotif = $this->db->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read, received_at) VALUES (?, ?, 0, NOW())");
            return $stmtUserNotif->execute([$userId, $notificationId]);
        } catch (Exception $e) {
            error_log("Error in sendToUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all users belonging to a specific role (e.g. 'admin', 'organizer', 'team')
     */
    public function sendToRole(string $role, string $title, string $message, string $type = 'SYSTEM'): bool
    {
        try {
            $stmtNotif = $this->db->prepare("INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, ?, NOW())");
            $stmtNotif->execute([$title, $message, strtoupper($type)]);
            $notificationId = (int) $this->db->lastInsertId();

            $userStmt = $this->db->prepare("SELECT user_id FROM users WHERE LOWER(role) = LOWER(?)");
            $userStmt->execute([$role]);
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                return true;
            }

            $stmtUserNotif = $this->db->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read, received_at) VALUES (?, ?, 0, NOW())");
            foreach ($users as $u) {
                $stmtUserNotif->execute([(int)$u['user_id'], $notificationId]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in sendToRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Broadcast notification to ALL active users across the platform
     */
    public function sendToAll(string $title, string $message, string $type = 'TOURNAMENT'): bool
    {
        try {
            $stmtNotif = $this->db->prepare("INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, ?, NOW())");
            $stmtNotif->execute([$title, $message, strtoupper($type)]);
            $notificationId = (int) $this->db->lastInsertId();

            $userStmt = $this->db->query("SELECT user_id FROM users");
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                return true;
            }

            $stmtUserNotif = $this->db->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read, received_at) VALUES (?, ?, 0, NOW())");
            foreach ($users as $u) {
                $stmtUserNotif->execute([(int)$u['user_id'], $notificationId]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in sendToAll: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all users participating in a specific tournament 
     * (Organizer, Accepted Teams, Referees, Playgrounds, Sponsors)
     */
    public function sendToTournamentParticipants(int $tournamentId, string $title, string $message, string $type = 'TOURNAMENT'): bool
    {
        try {
            $userIds = [];

            // 1. Organizer
            $stmtOrg = $this->db->prepare("SELECT organizer_id FROM tournaments WHERE tournament_id = ?");
            $stmtOrg->execute([$tournamentId]);
            $orgId = (int)$stmtOrg->fetchColumn();
            if ($orgId > 0) $userIds[] = $orgId;

            // 2. Teams
            $stmtTeams = $this->db->prepare("SELECT team_user_id FROM tournament_team_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
            $stmtTeams->execute([$tournamentId]);
            foreach ($stmtTeams->fetchAll(PDO::FETCH_COLUMN) as $u) {
                if ($u > 0) $userIds[] = (int)$u;
            }

            // 3. Referees
            $stmtRef = $this->db->prepare("SELECT referee_user_id FROM tournament_referee_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
            $stmtRef->execute([$tournamentId]);
            foreach ($stmtRef->fetchAll(PDO::FETCH_COLUMN) as $u) {
                if ($u > 0) $userIds[] = (int)$u;
            }

            // 4. Playgrounds
            $stmtPg = $this->db->prepare("SELECT playground_user_id FROM tournament_playground_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
            $stmtPg->execute([$tournamentId]);
            foreach ($stmtPg->fetchAll(PDO::FETCH_COLUMN) as $u) {
                if ($u > 0) $userIds[] = (int)$u;
            }

            // 5. Sponsors
            $stmtSp = $this->db->prepare("SELECT sponsor_user_id FROM tournament_sponsor_requests WHERE tournament_id = ? AND status IN ('ACCEPTED', 'APPROVED')");
            $stmtSp->execute([$tournamentId]);
            foreach ($stmtSp->fetchAll(PDO::FETCH_COLUMN) as $u) {
                if ($u > 0) $userIds[] = (int)$u;
            }

            // 6. Admins
            $stmtAd = $this->db->query("SELECT user_id FROM users WHERE LOWER(role) = 'admin'");
            foreach ($stmtAd->fetchAll(PDO::FETCH_COLUMN) as $u) {
                if ($u > 0) $userIds[] = (int)$u;
            }

            $uniqueUserIds = array_unique($userIds);
            if (empty($uniqueUserIds)) {
                // Fallback: send to all active users if no participants linked yet
                return $this->sendToAll($title, $message, $type);
            }

            $stmtNotif = $this->db->prepare("INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, ?, NOW())");
            $stmtNotif->execute([$title, $message, strtoupper($type)]);
            $notificationId = (int) $this->db->lastInsertId();

            $stmtUserNotif = $this->db->prepare("INSERT INTO user_notifications (user_id, notification_id, is_read, received_at) VALUES (?, ?, 0, NOW())");
            foreach ($uniqueUserIds as $uId) {
                $stmtUserNotif->execute([$uId, $notificationId]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in sendToTournamentParticipants: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all notifications for a given user with unread count
     */
    public function getUserNotifications(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT n.notification_id, n.title, n.message, n.type, n.created_at, 
                       un.is_read, un.received_at
                FROM user_notifications un
                JOIN notifications n ON un.notification_id = n.notification_id
                WHERE un.user_id = ?
                ORDER BY un.received_at DESC
                LIMIT 100
            ");
            $stmt->execute([$userId]);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $unreadStmt = $this->db->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
            $unreadStmt->execute([$userId]);
            $unreadCount = (int) $unreadStmt->fetchColumn();

            return [
                "success" => true,
                "unread_count" => $unreadCount,
                "data" => $list
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(int $userId, int $notificationId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND notification_id = ?");
            return $stmt->execute([$userId, $notificationId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            return false;
        }
    }
}
