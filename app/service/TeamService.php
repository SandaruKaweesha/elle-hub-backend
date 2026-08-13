<?php

require_once __DIR__ . "/../repository/TeamRepository.php";

class TeamService
{
    private TeamRepository $teamRepository;

    public function __construct()
    {
        $this->teamRepository = new TeamRepository();
    }

    public function getTeamRankings(): array
    {
        try {
            $rawTeams = $this->teamRepository->getTeamRankings();
            
            $formattedTeams = [];
            $trends = ['up', 'same', 'down'];

            foreach ($rawTeams as $index => $row) {
                $rank = $index + 1;
                $played = (int) ($row['played'] ?? 0);
                $won = (int) ($row['won'] ?? 0);
                $baseRating = (int) ($row['rating'] ?? 100);

                // Compute total dynamic points (0 if team hasn't played or won any tournament yet)
                $points = ($played > 0 || $won > 0) ? (($won * 100) + ($played * 25) + $baseRating) : 0;

                // Pick trend indicator
                $trend = ($played > 0 || $won > 0) ? $trends[($row['user_id'] ?? $index) % 3] : 'same';

                $formattedTeams[] = [
                    'id' => (int) $row['user_id'],
                    'rank' => $rank,
                    'name' => $row['team_name'] ?: 'Team #' . $row['user_id'],
                    'district' => $row['district'] ?: 'General',
                    'contactNumber' => $row['contact_number'] ?? '',
                    'played' => $played,
                    'won' => $won,
                    'points' => $points,
                    'rating' => $baseRating,
                    'trend' => $trend,
                    'profilePicture' => $row['profile_picture'] ?? null
                ];
            }

            return [
                "success" => true,
                "data" => $formattedTeams
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Failed to retrieve rankings: " . $e->getMessage()
            ];
        }
    }
}
