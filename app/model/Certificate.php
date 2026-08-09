<?php

class Certificate
{
    public ?int $certificateId;
    public string $verificationToken;
    public int $tournamentId;
    public string $recipientName;
    public ?int $playerId;
    public string $certificateType;
    public string $issueDate;
    public string $createdAt;

    public function __construct(
        ?int $certificateId,
        string $verificationToken,
        int $tournamentId,
        string $recipientName,
        ?int $playerId,
        string $certificateType,
        string $issueDate,
        ?string $createdAt = null
    ) {
        $this->certificateId = $certificateId;
        $this->verificationToken = $verificationToken;
        $this->tournamentId = $tournamentId;
        $this->recipientName = $recipientName;
        $this->playerId = $playerId;
        $this->certificateType = $certificateType;
        $this->issueDate = $issueDate;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): Certificate
    {
        return new self(
            isset($data['certificate_id']) ? (int) $data['certificate_id'] : null,
            $data['verification_token'] ?? $data['qr_code'] ?? '',
            (int) ($data['tournament_id'] ?? 0),
            $data['recipient_name'] ?? $data['recipient'] ?? 'Participant',
            isset($data['player_id']) ? (int) $data['player_id'] : (isset($data['recipient_user_id']) ? (int) $data['recipient_user_id'] : 0),
            $data['certificate_type'] ?? $data['cert_type'] ?? 'PARTICIPATION',
            $data['issue_date'] ?? date('Y-m-d'),
            $data['created_at'] ?? null
        );
    }
}
