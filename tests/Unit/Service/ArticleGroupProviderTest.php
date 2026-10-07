<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Tests\Unit\Service;

use Manuxi\SuluArticleConfigurationBundle\Service\ArticleGroupProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;

class ArticleGroupProviderTest extends TestCase
{
    private function createProvider(): ArticleGroupProvider
    {
        $groupProvider = $this->createMock(GroupProviderInterface::class);
        $groupProvider->method('getGroups')->willReturn([
            'default' => new FormGroup('default', 'Default', ['page_simple']),
            'blog' => new FormGroup('blog', 'Blog', ['blog_post', 'blog_news']),
        ]);

        return new ArticleGroupProvider($groupProvider);
    }

    public function testGetGroups(): void
    {
        $this->assertSame(['default', 'blog'], \array_keys($this->createProvider()->getGroups()));
    }

    public function testGetGroupIdentifier(): void
    {
        $provider = $this->createProvider();

        $this->assertSame('blog', $provider->getGroupIdentifier('blog_news'));
        $this->assertSame('default', $provider->getGroupIdentifier('page_simple'));
        $this->assertNull($provider->getGroupIdentifier('unknown'));
    }
}
