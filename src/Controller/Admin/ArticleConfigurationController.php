<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Manuxi\SuluArticleConfigurationBundle\Entity\ArticleConfiguration;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;
use Manuxi\SuluArticleConfigurationBundle\Schema\ConfigurationSchema;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Component\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api')]
class ArticleConfigurationController extends AbstractRestController
{
    public function __construct(
        private ArticleConfigurationRepository $repository,
        private EntityManagerInterface $entityManager,
        private ArticleRepositoryInterface $articleRepository,
        private ContentAggregatorInterface $contentAggregator,
        private ConfigurationSchema $schema,
        ViewHandlerInterface $viewHandler
    ) {
        parent::__construct($viewHandler);
    }

    #[Route(
        path: '/article-configurations/{id}',
        name: 'app.get_article_configurations',
        methods: ['GET'],
        defaults: ['_format' => 'json']
    )]
    public function getAction(string $id, Request $request): Response
    {
        $locale = $request->query->get('locale', 'en');
        $configuration = $this->repository->findByArticleId($id);
        $templateKey = $this->getTemplateKeyFromArticle($id, $locale) ?? $configuration?->getTemplateKey();

        if (!$configuration) {
            return $this->handleView($this->view($this->getDefaultData($id, $templateKey)));
        }

        return $this->handleView($this->view($this->serializeConfiguration($configuration, $templateKey)));
    }

    #[Route(
        path: '/article-configurations/{id}',
        name: 'app.put_article_configurations',
        methods: ['PUT'],
        defaults: ['_format' => 'json']
    )]
    public function putAction(string $id, Request $request): Response
    {
        $locale = $request->query->get('locale', 'en');
        $configuration = $this->repository->findByArticleId($id);

        if (!$configuration) {
            $configuration = new ArticleConfiguration();
            $configuration->setArticleId($id);
            $this->entityManager->persist($configuration);
        }

        $data = $request->toArray();

        $templateKey = $this->getTemplateKeyFromArticle($id, $locale);
        $configuration->setTemplateKey($templateKey);

        $default = (bool) ($data['default'] ?? false);
        if ($default && $templateKey) {
            $this->repository->clearDefaultsForTemplate($templateKey, $id);
        }
        $configuration->setDefault($default);

        $configuration->setData($this->schema->sanitize($templateKey, $data));

        $this->entityManager->flush();

        return $this->handleView($this->view($this->serializeConfiguration($configuration, $templateKey)));
    }

    private function getTemplateKeyFromArticle(string $articleId, string $locale): ?string
    {
        try {
            $article = $this->articleRepository->findOneBy(
                [
                    'uuid' => $articleId,
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                ],
                [
                    ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                        DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
                    ],
                ]
            );

            if (!$article) {
                return null;
            }

            $dimensionContent = $this->contentAggregator->aggregate($article, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_DRAFT,
            ]);

            return $dimensionContent->getTemplateKey();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultData(string $id, ?string $templateKey): array
    {
        return \array_merge(
            [
                'id' => $id,
                'articleId' => $id,
                'templateKey' => $templateKey,
                'default' => false,
            ],
            $this->schema->getDefaults($templateKey)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConfiguration(ArticleConfiguration $configuration, ?string $templateKey): array
    {
        return \array_merge(
            [
                'id' => $configuration->getArticleId(),
                'articleId' => $configuration->getArticleId(),
                'templateKey' => $templateKey ?? $configuration->getTemplateKey(),
                'default' => $configuration->isDefault(),
            ],
            $this->schema->sanitize($templateKey ?? $configuration->getTemplateKey(), $configuration->getData())
        );
    }
}
