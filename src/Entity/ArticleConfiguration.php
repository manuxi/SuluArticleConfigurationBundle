<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Entity;

class ArticleConfiguration
{
    private ?int $id = null;

    private string $articleId = '';

    private ?string $templateKey = null;

    private bool $default = false;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $data = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticleId(): string
    {
        return $this->articleId;
    }

    public function setArticleId(string $articleId): self
    {
        $this->articleId = $articleId;

        return $this;
    }

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(?string $templateKey): self
    {
        $this->templateKey = $templateKey;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
