<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../model/Certificate.php';

class CertificateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function save(Certificate $certificate): bool
    {
        $query = "INSERT INTO certificates (verification_token, tournament_id, recipient_name, player_id, certificate_type, issue_date, created_at)
                  VALUES (:verification_token, :tournament_id, :recipient_name, :player_id, :certificate_type, :issue_date, :created_at)";

        $stmt = $this->db->prepare($query);

        return $stmt->execute([
            ':verification_token' => $certificate->verificationToken,
            ':tournament_id' => $certificate->tournamentId,
            ':recipient_name' => $certificate->recipientName,
            ':player_id' => $certificate->playerId ?? 0,
            ':certificate_type' => $certificate->certificateType,
            ':issue_date' => $certificate->issueDate,
            ':created_at' => $certificate->createdAt
        ]);
    }

    public function findByToken(string $token): ?array
    {
        $query = "SELECT c.certificate_id, c.verification_token, c.tournament_id, c.recipient_name, c.player_id, 
                         c.certificate_type, c.issue_date, c.created_at,
                         t.title AS tournament_title, t.location AS tournament_location, 
                         t.start_date, t.end_date, t.tournament_held_date,
                         COALESCE(org_user.email, 'Elle Hub Official Organizer') AS organizer_name,
                         COALESCE(sp.company_name, 'Official Tournament Sponsors') AS sponsor_name
                  FROM certificates c
                  JOIN tournaments t ON c.tournament_id = t.tournament_id
                  LEFT JOIN users org_user ON t.organizer_id = org_user.user_id
                  LEFT JOIN tournament_sponsor_requests tsr ON (t.tournament_id = tsr.tournament_id AND (UPPER(tsr.status) = 'ACCEPTED' OR UPPER(tsr.status) = 'APPROVED'))
                  LEFT JOIN sponsors sp ON tsr.sponsor_user_id = sp.user_id
                  WHERE c.verification_token = :token OR c.certificate_id = :token_id OR c.verification_token LIKE :like_token";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':token' => $token,
            ':token_id' => is_numeric($token) ? (int)$token : -1,
            ':like_token' => '%' . $token . '%'
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['certificateId'] = $row['certificate_id'];
            $row['verificationToken'] = $row['verification_token'];
            $row['tournamentId'] = $row['tournament_id'];
            $row['recipientName'] = $row['recipient_name'];
            $row['playerId'] = $row['player_id'];
            $row['certificateType'] = $row['certificate_type'];
            $row['issueDate'] = $row['issue_date'];
            $row['tournamentTitle'] = $row['tournament_title'];
            $row['tournamentLocation'] = $row['tournament_location'];
            $row['organizerName'] = $row['organizer_name'];
            $row['sponsorName'] = $row['sponsor_name'];
        }
        return $row ?: null;
    }


    public function findByTournamentId(int $tournamentId): array
    {
        $query = "SELECT c.*, t.title AS tournament_title 
                  FROM certificates c 
                  JOIN tournaments t ON c.tournament_id = t.tournament_id
                  WHERE c.tournament_id = :tournament_id 
                  ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':tournament_id' => $tournamentId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['certificateId'] = $row['certificate_id'];
            $row['verificationToken'] = $row['verification_token'];
            $row['tournamentId'] = $row['tournament_id'];
            $row['recipientName'] = $row['recipient_name'];
            $row['playerId'] = $row['player_id'];
            $row['certificateType'] = $row['certificate_type'];
            $row['issueDate'] = $row['issue_date'];
            $row['tournamentTitle'] = $row['tournament_title'];
        }
        return $rows;
    }

}
