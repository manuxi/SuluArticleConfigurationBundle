<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;

#[ORM\Entity(repositoryClass: ArticleConfigurationRepository::class)]
#[ORM\Table(name: 'ar_article_configuration')]
#[ORM\Index(columns: ['template_key', 'is_default'], name: 'idx_template_default')]
class ArticleConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $articleId = '';

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    private ?string $templateKey = null;

    #[ORM\Column(name: 'is_default', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $default = false;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => 'default'])]
    private string $layoutStyle = 'default';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enableSidebar = true;

    #[ORM\Column(type: Types::STRING, length: 16, options: ['default' => 'right'])]
    private string $sidebarPosition = 'right';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $showToc = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $showReadingTime = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $showAuthorBox = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $showRelated = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enableShareButtons = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enablePrint = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $hidePublishDate = false;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    private ?string $customCssClass = null;

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

    public function getLayoutStyle(): string
    {
        return $this->layoutStyle;
    }

    public function setLayoutStyle(string $layoutStyle): self
    {
        $this->layoutStyle = $layoutStyle;
        return $this;
    }

    public function isEnableSidebar(): bool
    {
        return $this->enableSidebar;
    }

    public function setEnableSidebar(bool $enableSidebar): self
    {
        $this->enableSidebar = $enableSidebar;
        return $this;
    }

    public function getSidebarPosition(): string
    {
        return $this->sidebarPosition;
    }

    public function setSidebarPosition(string $sidebarPosition): self
    {
        $this->sidebarPosition = $sidebarPosition;
        return $this;
    }

    public function isShowToc(): bool
    {
        return $this->showToc;
    }

    public function setShowToc(bool $showToc): self
    {
        $this->showToc = $showToc;
        return $this;
    }

    public function isShowReadingTime(): bool
    {
        return $this->showReadingTime;
    }

    public function setShowReadingTime(bool $showReadingTime): self
    {
        $this->showReadingTime = $showReadingTime;
        return $this;
    }

    public function isShowAuthorBox(): bool
    {
        return $this->showAuthorBox;
    }

    public function setShowAuthorBox(bool $showAuthorBox): self
    {
        $this->showAuthorBox = $showAuthorBox;
        return $this;
    }

    public function isShowRelated(): bool
    {
        return $this->showRelated;
    }

    public function setShowRelated(bool $showRelated): self
    {
        $this->showRelated = $showRelated;
        return $this;
    }

    public function isEnableShareButtons(): bool
    {
        return $this->enableShareButtons;
    }

    public function setEnableShareButtons(bool $enableShareButtons): self
    {
        $this->enableShareButtons = $enableShareButtons;
        return $this;
    }

    public function isEnablePrint(): bool
    {
        return $this->enablePrint;
    }

    public function setEnablePrint(bool $enablePrint): self
    {
        $this->enablePrint = $enablePrint;
        return $this;
    }

    public function isHidePublishDate(): bool
    {
        return $this->hidePublishDate;
    }

    public function setHidePublishDate(bool $hidePublishDate): self
    {
        $this->hidePublishDate = $hidePublishDate;
        return $this;
    }

    public function getCustomCssClass(): ?string
    {
        return $this->customCssClass;
    }

    public function setCustomCssClass(?string $customCssClass): self
    {
        $this->customCssClass = $customCssClass;
        return $this;
    }

}