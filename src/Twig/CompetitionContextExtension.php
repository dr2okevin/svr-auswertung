<?php

namespace App\Twig;

use App\Enum\CompetitionType;
use Twig\Attribute\AsTwigFunction;
use App\Service\CompetitionContextProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CompetitionContextExtension
{
    public function __construct(private readonly CompetitionContextProvider $competitionContextProvider)
    {
    }

    /**
     * @return array{selectedCompetitionId: int|null, competitions: array<int, array{id: int, name: string, type: ?CompetitionType}>}
     */
    #[AsTwigFunction(name: 'competition_context')]
    public function competitionContext(): array
    {
        return $this->competitionContextProvider->getContext();
    }
}
