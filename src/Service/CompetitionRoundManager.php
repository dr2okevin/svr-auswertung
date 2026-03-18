<?php

namespace App\Service;

use App\Entity\Competition;
use App\Entity\Round;
use App\Enum\CompetitionType;
use Doctrine\ORM\EntityManagerInterface;

class CompetitionRoundManager
{
    public function syncRoundsForCompetition(Competition $competition, EntityManagerInterface $entityManager): void
    {
        if ($competition->getType() === CompetitionType::ROUNDS) {
            return;
        }

        $rounds = $competition->getRounds();
        $defaultRound = $rounds->first();

        if (!$defaultRound instanceof Round) {
            $defaultRound = new Round();
            $competition->addRound($defaultRound);
            $entityManager->persist($defaultRound);
        }

        $defaultRound
            ->setName($competition->getName() ?: 'Standardrunde')
            ->setStartDate($competition->getStartTime() ?? new \DateTime())
            ->setEndDate($competition->getEndTime() ?? new \DateTime());

        foreach ($rounds as $round) {
            if ($round === $defaultRound) {
                continue;
            }

            $competition->removeRound($round);
            $entityManager->remove($round);
        }
    }

    public function createRoundFromCompetitionWindow(Competition $competition): Round
    {
        $round = new Round();
        $round
            ->setCompetition($competition)
            ->setName(sprintf('Runde %d', $competition->getRounds()->count() + 1))
            ->setStartDate($competition->getStartTime() ?? new \DateTime())
            ->setEndDate($competition->getEndTime() ?? new \DateTime());

        return $round;
    }
}
