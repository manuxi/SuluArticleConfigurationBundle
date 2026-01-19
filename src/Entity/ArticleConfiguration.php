<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Manuxi\SuluArticleConfigurationBundle\Repository\ArticleConfigurationRepository;

#[ORM\Entity(repositoryClass: ArticleConfigurationRepository::class)]
#[ORM\Table(name: 'article_configuration')]
class ArticleConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private ?string $articleId = null;

    // Display Options
    #[ORM\Column(length: 255, nullable: true, options: ['default' => 'default'])]
    private ?string $layoutStyle = 'default';

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $enableSidebar = true;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => 'right'])]
    private ?string $sidebarPosition = 'right';

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $showToc = true;

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $showReadingTime = true;

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $showAuthorBox = true;

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $showRelated = true;

    // Features
    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $enableComments = false;

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $enableShareButtons = true;

    #[ORM\Column(nullable: true, options: ['default' => true])]
    private ?bool $enablePrint = true;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $enableDownloadPdf = false;

    // Publication Settings
    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $isFeatured = false;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $isSticky = false;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $hideFromLists = false;

    #[ORM\Column(nullable: true, options: ['default' => false])]
    private ?bool $hidePublishDate = false;

    // Styling
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customCssClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $headerBgColor = null;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => 'auto'])]
    private ?string $headerTextColor = 'auto';

    // Advanced
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customTemplate = null;

    #[ORM\Column(nullable: true, options: ['default' => 86400])]
    private ?int $cacheLifetime = 86400;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customData = null; // JSON encoded data

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticleId(): ?string
    {
        return $this->articleId;
    }

    public function setArticleId(string $articleId): static
    {
        $this->articleId = $articleId;
        return $this;
    }

    public function getLayoutStyle(): ?string
    {
        return $this->layoutStyle;
    }

    public function setLayoutStyle(?string $layoutStyle): static
    {
        $this->layoutStyle = $layoutStyle;
        return $this;
    }

    public function isEnableSidebar(): ?bool
    {
        return $this->enableSidebar;
    }

    public function setEnableSidebar(bool $enableSidebar): static
    {
        $this->enableSidebar = $enableSidebar;
        return $this;
    }

    public function getSidebarPosition(): ?string
    {
        return $this->sidebarPosition;
    }

    public function setSidebarPosition(?string $sidebarPosition): static
    {
        $this->sidebarPosition = $sidebarPosition;
        return $this;
    }

    public function isShowToc(): ?bool
    {
        return $this->showToc;
    }

    public function setShowToc(bool $showToc): static
    {
        $this->showToc = $showToc;
        return $this;
    }

    public function isShowReadingTime(): ?bool
    {
        return $this->showReadingTime;
    }

    public function setShowReadingTime(bool $showReadingTime): static
    {
        $this->showReadingTime = $showReadingTime;
        return $this;
    }

    public function isShowAuthorBox(): ?bool
    {
        return $this->showAuthorBox;
    }

    public function setShowAuthorBox(bool $showAuthorBox): static
    {
        $this->showAuthorBox = $showAuthorBox;
        return $this;
    }

    public function isShowRelated(): ?bool
    {
        return $this->showRelated;
    }

    public function setShowRelated(bool $showRelated): static
    {
        $this->showRelated = $showRelated;
        return $this;
    }

    public function isEnableComments(): ?bool
    {
        return $this->enableComments;
    }

    public function setEnableComments(bool $enableComments): static
    {
        $this->enableComments = $enableComments;
        return $this;
    }

    public function isEnableShareButtons(): ?bool
    {
        return $this->enableShareButtons;
    }

    public function setEnableShareButtons(bool $enableShareButtons): static
    {
        $this->enableShareButtons = $enableShareButtons;
        return $this;
    }

    public function isEnablePrint(): ?bool
    {
        return $this->enablePrint;
    }

    public function setEnablePrint(bool $enablePrint): static
    {
        $this->enablePrint = $enablePrint;
        return $this;
    }

    public function isEnableDownloadPdf(): ?bool
    {
        return $this->enableDownloadPdf;
    }

    public function setEnableDownloadPdf(bool $enableDownloadPdf): static
    {
        $this->enableDownloadPdf = $enableDownloadPdf;
        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    public function isSticky(): ?bool
    {
        return $this->isSticky;
    }

    public function setIsSticky(bool $isSticky): static
    {
        $this->isSticky = $isSticky;
        return $this;
    }

    public function isHideFromLists(): ?bool
    {
        return $this->hideFromLists;
    }

    public function setHideFromLists(bool $hideFromLists): static
    {
        $this->hideFromLists = $hideFromLists;
        return $this;
    }

    public function isHidePublishDate(): ?bool
    {
        return $this->hidePublishDate;
    }

    public function setHidePublishDate(bool $hidePublishDate): static
    {
        $this->hidePublishDate = $hidePublishDate;
        return $this;
    }

    public function getCustomCssClass(): ?string
    {
        return $this->customCssClass;
    }

    public function setCustomCssClass(?string $customCssClass): static
    {
        $this->customCssClass = $customCssClass;
        return $this;
    }

    public function getHeaderBgColor(): ?string
    {
        return $this->headerBgColor;
    }

    public function setHeaderBgColor(?string $headerBgColor): static
    {
        $this->headerBgColor = $headerBgColor;
        return $this;
    }

    public function getHeaderTextColor(): ?string
    {
        return $this->headerTextColor;
    }

    public function setHeaderTextColor(?string $headerTextColor): static
    {
        $this->headerTextColor = $headerTextColor;
        return $this;
    }

    public function getCustomTemplate(): ?string
    {
        return $this->customTemplate;
    }

    public function setCustomTemplate(?string $customTemplate): static
    {
        $this->customTemplate = $customTemplate;
        return $this;
    }

    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(?int $cacheLifetime): static
    {
        $this->cacheLifetime = $cacheLifetime;
        return $this;
    }

    public function getCustomData(): ?string
    {
        return $this->customData;
    }

    public function setCustomData(?string $customData): static
    {
        $this->customData = $customData;
        return $this;
    }
}
