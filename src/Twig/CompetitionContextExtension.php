<?php

namespace App\Twig;

use App\Service\CompetitionContextProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CompetitionContextExtension extends AbstractExtension
{
    public function __construct(private readonly CompetitionContextProvider $competitionContextProvider)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('competition_context', $this->competitionContext(...)),
        ];
    }

    /**
     * @return array{selectedCompetitionId: int|null, competitions: array<int, array{id: int, name: string}>}
     */
    public function competitionContext(): array
    {
        return $this->competitionContextProvider->getContext();
    }
}
