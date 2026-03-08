<?php

namespace App\Service;

use App\Repository\CompetitionRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CompetitionContextProvider
{
    private const SESSION_KEY = 'active_competition_id';

    public function __construct(
        private readonly CompetitionRepository $competitionRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{selectedCompetitionId: int|null, competitions: array<int, array{id: int, name: string}>}
     */
    public function getContext(): array
    {
        $competitions = $this->competitionRepository->findBy([], ['Name' => 'ASC']);
        $competitionOptions = [];

        foreach ($competitions as $competition) {
            $id = $competition->getId();
            $name = $competition->getName();

            if ($id === null || $name === null) {
                continue;
            }

            $competitionOptions[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        return [
            'selectedCompetitionId' => $this->getSelectedCompetitionId(),
            'competitions' => $competitionOptions,
        ];
    }

    public function switchCompetition(?int $competitionId): void
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }

        if ($competitionId === null) {
            $session->remove(self::SESSION_KEY);

            return;
        }

        if ($this->competitionRepository->find($competitionId) === null) {
            return;
        }

        $session->set(self::SESSION_KEY, $competitionId);
    }

    public function getSelectedCompetitionId(): ?int
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return null;
        }

        $selectedCompetitionId = $session->get(self::SESSION_KEY);

        return is_int($selectedCompetitionId) ? $selectedCompetitionId : null;
    }
}
