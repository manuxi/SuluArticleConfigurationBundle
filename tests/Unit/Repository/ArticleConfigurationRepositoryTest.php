<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use PHPUnit\Framework\TestCase;

class ArticleConfigurationRepositoryTest extends TestCase
{
    public function testConstruct(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);

        $registry->method('getManagerForClass')
            ->with(ArticleConfiguration::class)
            ->willReturn($entityManager);

        $entityManager->method('getClassMetadata')
            ->with(ArticleConfiguration::class)
            ->willReturn($classMetadata);

        $repository = new ArticleConfigurationRepository($registry);

        $this->assertInstanceOf(ArticleConfigurationRepository::class, $repository);
    }
}
