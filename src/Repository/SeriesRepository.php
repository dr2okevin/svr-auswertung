<?php

namespace App\Repository;

use App\Entity\Competition;
use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Series>
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSeriesTotalsForCompetition(Competition $competition): array
    {
        $rows = $this->createQueryBuilder('series')
            ->select('team.id AS teamId')
            ->addSelect('team.Name AS teamName')
            ->addSelect('person.id AS personId')
            ->addSelect('person.FristName AS personFirstName')
            ->addSelect('person.LastName AS personLastName')
            ->addSelect('person.Professional AS isProfessional')
            ->addSelect('discipline.id AS disciplineId')
            ->addSelect('discipline.Name AS disciplineName')
            ->addSelect('COALESCE(SUM(shot.value), 0) AS totalScore')
            ->addSelect('COUNT(shot.id) AS shotCount')
            ->addSelect('(discipline.ShotsPerSeries * discipline.MaxSeriesCount) AS targetShotCount')
            ->innerJoin('series.Round', 'round')
            ->innerJoin('round.Competition', 'competition')
            ->innerJoin('series.Team', 'team')
            ->innerJoin('series.Person', 'person')
            ->innerJoin('series.Discipline', 'discipline')
            ->leftJoin('series.shots', 'shot')
            ->andWhere('competition = :competition')
            ->setParameter('competition', $competition)
            ->groupBy('series.id, team.id, person.id, discipline.id')
            ->orderBy('discipline.Name', 'ASC')
            ->addOrderBy('totalScore', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as &$row) {
            $targetShotCount = (int) ($row['targetShotCount'] ?? 0);
            if ($targetShotCount <= 0) {
                $targetShotCount = (int) ($row['shotCount'] ?? 0);
            }

            $row['targetShotCount'] = $targetShotCount;
        }
        unset($row);

        return $rows;
    }
}
