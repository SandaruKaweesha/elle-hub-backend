<?php

require_once __DIR__ . "/../../config/Database.php";

class MessageService
{
    /**
     * Get messaging contacts for a user (Only Sponsors <-> Organizers allowed)
     */
    public function getContacts(int $userId): array
    {
        try {
            $conn = Database::getConnection();

            // Fetch target user's role
            $stmtUser = $conn->prepare("SELECT user_id, role, email FROM users WHERE user_id = ?");
            $stmtUser->execute([$userId]);
            $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$currentUser) {
                return ["success" => false, "message" => "User not found"];
            }

            $userRole = strtoupper(trim($currentUser['role'] ?? ''));

            if ($userRole !== 'SPONSOR' && $userRole !== 'ORGANIZER') {
                return [
                    "success" => false, 
                    "message" => "Direct messaging is restricted to Sponsors and Tournament Organizers only."
                ];
            }

            // Target contact role
            $targetRole = ($userRole === 'SPONSOR') ? 'ORGANIZER' : 'SPONSOR';

            // Query target contacts (Organizers for Sponsor, or Sponsors for Organizer)
            if ($targetRole === 'ORGANIZER') {
                $sql = "
                    SELECT u.user_id, 
                           COALESCE(o.organization_name, u.email, 'Tournament Organizer') AS display_name,
                           u.email,
                           COALESCE(o.contact_number, 'Available on Request') AS contact_number,
                           'ORGANIZER' AS role,
                           o.organization_name
                    FROM users u
                    JOIN organizers o ON u.user_id = o.user_id
                    WHERE UPPER(u.role) = 'ORGANIZER' AND u.user_id != ?
                    ORDER BY display_name ASC
                ";
            } else {
                $sql = "
                    SELECT u.user_id, 
                           COALESCE(s.company_name, u.email, 'Sponsor Company') AS display_name,
                           u.email,
                           COALESCE(s.contact_number, 'Available on Request') AS contact_number,
                           'SPONSOR' AS role,
                           s.contact_person
                    FROM users u
                    JOIN sponsors s ON u.user_id = s.user_id
                    WHERE UPPER(u.role) = 'SPONSOR' AND u.user_id != ?
                    ORDER BY display_name ASC
                ";
            }

            $stmtContacts = $conn->prepare($sql);
            $stmtContacts->execute([$userId]);
            $contacts = $stmtContacts->fetchAll(PDO::FETCH_ASSOC);

            // Fetch latest message and unread count for each contact
            foreach ($contacts as &$contact) {
                $otherId = (int)$contact['user_id'];

                // Latest message
                $stmtMsg = $conn->prepare("
                    SELECT message_id, sender_user_id, receiver_user_id, content, sent_at, is_read
                    FROM messages
                    WHERE (sender_user_id = ? AND receiver_user_id = ?)
                       OR (sender_user_id = ? AND receiver_user_id = ?)
                    ORDER BY sent_at DESC, message_id DESC
                    LIMIT 1
                ");
                $stmtMsg->execute([$userId, $otherId, $otherId, $userId]);
                $lastMsg = $stmtMsg->fetch(PDO::FETCH_ASSOC);

                // Unread count (received by $userId from $otherId)
                $stmtUnread = $conn->prepare("
                    SELECT COUNT(*) as unread_count
                    FROM messages
                    WHERE sender_user_id = ? AND receiver_user_id = ? AND (is_read = 0 OR is_read IS NULL)
                ");
                $stmtUnread->execute([$otherId, $userId]);
                $unreadRow = $stmtUnread->fetch(PDO::FETCH_ASSOC);

                $contact['last_message'] = $lastMsg ? $lastMsg['content'] : "No message history yet";
                $contact['last_message_time'] = $lastMsg ? $lastMsg['sent_at'] : null;
                $contact['unread_count'] = (int)($unreadRow['unread_count'] ?? 0);
                $contact['avatar'] = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($contact['display_name']) . "&backgroundColor=eaf1ec";
            }

            // Sort contacts so latest active chats with most recent messages come FIRST (Top of list)
            usort($contacts, function($a, $b) {
                $timeA = $a['last_message_time'] ? strtotime($a['last_message_time']) : 0;
                $timeB = $b['last_message_time'] ? strtotime($b['last_message_time']) : 0;
                if ($timeA === $timeB) {
                    return strcmp($a['display_name'], $b['display_name']);
                }
                return $timeB <=> $timeA;
            });

            return ["success" => true, "data" => $contacts];

        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Get conversation between two users and mark received messages as read
     */
    public function getConversation(int $userId, int $otherUserId): array
    {
        try {
            $conn = Database::getConnection();

            // Validate both users are Sponsor and Organizer
            $stmtCheck = $conn->prepare("SELECT user_id, role, email FROM users WHERE user_id IN (?, ?)");
            $stmtCheck->execute([$userId, $otherUserId]);
            $users = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

            if (count($users) < 2) {
                return ["success" => false, "message" => "One or both users not found"];
            }

            $roles = array_map(fn($u) => strtoupper(trim($u['role'] ?? '')), $users);
            if (!in_array('SPONSOR', $roles) || !in_array('ORGANIZER', $roles)) {
                return [
                    "success" => false,
                    "message" => "Messaging is restricted to Sponsors and Tournament Organizers only."
                ];
            }

            // Mark incoming messages from $otherUserId as read
            $stmtRead = $conn->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE sender_user_id = ? AND receiver_user_id = ? AND (is_read = 0 OR is_read IS NULL)
            ");
            $stmtRead->execute([$otherUserId, $userId]);

            // Fetch chronological messages
            $stmtMsgs = $conn->prepare("
                SELECT message_id, sender_user_id, receiver_user_id, content, sent_at, is_read
                FROM messages
                WHERE (sender_user_id = ? AND receiver_user_id = ?)
                   OR (sender_user_id = ? AND receiver_user_id = ?)
                ORDER BY sent_at ASC, message_id ASC
            ");
            $stmtMsgs->execute([$userId, $otherUserId, $otherUserId, $userId]);
            $messages = $stmtMsgs->fetchAll(PDO::FETCH_ASSOC);

            return ["success" => true, "data" => $messages];

        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * Send message between Sponsor and Organizer
     */
    public function sendMessage(int $senderId, int $receiverId, string $content): array
    {
        try {
            $conn = Database::getConnection();

            $content = trim($content);
            if (empty($content)) {
                return ["success" => false, "message" => "Message content cannot be empty"];
            }

            // Validate roles of sender and receiver
            $stmtSender = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmtSender->execute([$senderId]);
            $sender = $stmtSender->fetch(PDO::FETCH_ASSOC);

            $stmtReceiver = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmtReceiver->execute([$receiverId]);
            $receiver = $stmtReceiver->fetch(PDO::FETCH_ASSOC);

            if (!$sender || !$receiver) {
                return ["success" => false, "message" => "Sender or Receiver user not found"];
            }

            $senderRole = strtoupper(trim($sender['role'] ?? ''));
            $receiverRole = strtoupper(trim($receiver['role'] ?? ''));

            $isSponsorOrganizer = ($senderRole === 'SPONSOR' && $receiverRole === 'ORGANIZER') ||
                                  ($senderRole === 'ORGANIZER' && $receiverRole === 'SPONSOR');

            if (!$isSponsorOrganizer) {
                return [
                    "success" => false,
                    "message" => "Direct messaging is allowed strictly between Tournament Organizers and Sponsors."
                ];
            }

            $stmtInsert = $conn->prepare("
                INSERT INTO messages (sender_user_id, receiver_user_id, content, sent_at, is_read)
                VALUES (?, ?, ?, NOW(), 0)
            ");
            $stmtInsert->execute([$senderId, $receiverId, $content]);
            $messageId = (int)$conn->lastInsertId();

            return [
                "success" => true,
                "message" => "Message sent successfully",
                "data" => [
                    "message_id" => $messageId,
                    "sender_user_id" => $senderId,
                    "receiver_user_id" => $receiverId,
                    "content" => $content,
                    "sent_at" => date("Y-m-d H:i:s"),
                    "is_read" => 0
                ]
            ];

        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
