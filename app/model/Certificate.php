<?php

class Certificate
{
    public string $id;
    public string $tournament;
    public string $certType;
    public string $recipient;
    public int $organizerId;
    public string $createdAt;

    public function __construct(string $id, string $tournament, string $certType, string $recipient, int $organizerId, string $createdAt = null)
    {
        $this->id = $id;
        $this->tournament = $tournament;
        $this->certType = $certType;
        $this->recipient = $recipient;
        $this->organizerId = $organizerId;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): Certificate
    {
        return new self(
            $data['id'],
            $data['tournament'],
            $data['cert_type'],
            $data['recipient'],
            $data['organizer_id'],
            $data['created_at'] ?? null
        );
    }
}
