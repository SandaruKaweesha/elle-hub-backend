<?php
require_once __DIR__ . "/../model/Tournament.php";
require_once __DIR__ . "/../repository/TournamentRepository.php";
require_once __DIR__ . "/../../config/Database.php";

class TournamentService{
    private $tournamentRepository;
    public function __construct(){
        $this->tournamentRepository = new TournamentRepository();
    }

    /**
     * Create a tournament. Sets default status to PENDING and saves.
     */
    public function createTournament(Tournament $tournament): array{
        // Default status: PENDING (requires admin approval)
        $tournament->setStatus("PENDING");

        try{
            Database::beginTransaction();

            $tournamentId = $this->tournamentRepository->save($tournament);
            $tournament->setTournamentId($tournamentId);

            Database::commit();

            return [
                "success" => true,
                "message" => "Tournament created successfully.",
                "data" => ["tournamentId" => $tournamentId]
            ];
        } catch (Exception $e) {
            Database::rollback();
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }
}

