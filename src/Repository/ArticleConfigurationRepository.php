<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;

/**
 * @extends ServiceEntityRepository<ArticleConfiguration>
 */
class ArticleConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleConfiguration::class);
    }

    public function findByArticleId(string $articleId): ?ArticleConfiguration
    {
        return $this->findOneBy(['articleId' => $articleId]);
    }

    public function findDefaultForTemplate(string $templateKey): ?ArticleConfiguration
    {
        return $this->findOneBy([
            'templateKey' => $templateKey,
            'default' => true,
        ]);
    }

    public function clearDefaultsForTemplate(string $templateKey, ?string $excludeArticleId = null): void
    {
        $qb = $this->createQueryBuilder('c')
            ->update()
            ->set('c.default', ':false')
            ->where('c.templateKey = :templateKey')
            ->andWhere('c.default = :true')
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->setParameter('templateKey', $templateKey);

        if ($excludeArticleId) {
            $qb->andWhere('c.articleId != :excludeId')
                ->setParameter('excludeId', $excludeArticleId);
        }

        $qb->getQuery()->execute();
    }
}