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
}
