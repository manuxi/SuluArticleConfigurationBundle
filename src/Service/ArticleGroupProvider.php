<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Service;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;

class ArticleGroupProvider
{
    public function __construct(
        private readonly GroupProviderInterface $groupProvider,
    ) {
    }

    /**
     * Sulu >= 3.0.9 expects the template type; earlier 3.0 dev builds take no argument.
     *
     * @return array<string, FormGroup>
     */
    public function getGroups(): array
    {
        return (new \ReflectionMethod($this->groupProvider, 'getGroups'))->getNumberOfParameters() > 0
            ? $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            : $this->groupProvider->getGroups();
    }

    public function getGroupIdentifier(string $templateKey): ?string
    {
        foreach ($this->getGroups() as $group) {
            if (\in_array($templateKey, $group->templates, true)) {
                return $group->identifier;
            }
        }

        return null;
    }
}
