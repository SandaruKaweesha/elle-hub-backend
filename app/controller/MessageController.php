<?php

require_once __DIR__ . "/../service/MessageService.php";

class MessageController
{
    private const JSON_HEADER = "Content-Type: application/json";
    private MessageService $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    /**
     * GET /messages/contacts/{userId}
     */
    public function getContacts($userId)
    {
        header(self::JSON_HEADER);
        $result = $this->messageService->getContacts((int) $userId);
        if (!$result["success"] && str_contains($result["message"] ?? "", "restricted")) {
            http_response_code(403);
        }
        echo json_encode($result);
    }

    /**
     * GET /messages/conversation/{user1Id}/{user2Id}
     */
    public function getConversation($user1Id, $user2Id)
    {
        header(self::JSON_HEADER);
        $result = $this->messageService->getConversation((int) $user1Id, (int) $user2Id);
        if (!$result["success"] && str_contains($result["message"] ?? "", "restricted")) {
            http_response_code(403);
        }
        echo json_encode($result);
    }

    /**
     * POST /messages/send
     */
    public function sendMessage()
    {
        header(self::JSON_HEADER);
        $requestBody = file_get_contents("php://input");
        $requestObject = json_decode($requestBody);

        $senderId = $requestObject->senderId ?? $requestObject->sender_user_id ?? 0;
        $receiverId = $requestObject->receiverId ?? $requestObject->receiver_user_id ?? 0;
        $content = $requestObject->content ?? $requestObject->message ?? "";

        if (!$senderId || !$receiverId || empty(trim($content))) {
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "message" => "Sender ID, Receiver ID, and Message content are required."
            ]);
            return;
        }

        $result = $this->messageService->sendMessage((int) $senderId, (int) $receiverId, $content);
        if (!$result["success"]) {
            http_response_code(400);
        }
        echo json_encode($result);
    }
}
