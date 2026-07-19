<?php
require_once __DIR__ . '/../repository/CertificateRepository.php';

class CertificateService
{
    private CertificateRepository $repository;

    public function __construct()
    {
        $this->repository = new CertificateRepository();
    }

    public function generateCertificate(array $data, int $organizerId): ?Certificate
    {
        $id = $this->generateUuid();
        $certificate = new Certificate(
            $id,
            $data['tournament'],
            $data['cert_type'],
            $data['recipient'],
            $organizerId
        );

        if ($this->repository->save($certificate)) {
            return $certificate;
        }

        return null;
    }

    public function getCertificateHistory(int $organizerId): array
    {
        return $this->repository->findByOrganizerId($organizerId);
    }

    public function verifyCertificate(string $id): ?array
    {
        $cert = $this->repository->findById($id);
        if ($cert) {
            return [
                'id' => $cert->id,
                'tournament' => $cert->tournament,
                'cert_type' => $cert->certType,
                'recipient' => $cert->recipient,
                'created_at' => $cert->createdAt,
                'organizer_name' => $cert->organizerName
            ];
        }
        return null;
    }

    private function generateUuid(): string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}
