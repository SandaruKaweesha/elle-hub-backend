<?php

require_once __DIR__ . "/../app/controller/MessageController.php";

$router->get(
    "/messages/contacts/{userId}",
    [MessageController::class, "getContacts"]
);

$router->get(
    "/messages/conversation/{user1Id}/{user2Id}",
    [MessageController::class, "getConversation"]
);

$router->post(
    "/messages/send",
    [MessageController::class, "sendMessage"]
);
