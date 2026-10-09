<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Admin\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

/**
 * Mutable section used while composing the form levels; Sulu's SectionMetadata cannot remove items.
 *
 * @internal
 */
final class SectionNode
{
    private string $name;

    /**
     * @var array<string, string>
     */
    private array $labels;

    /**
     * @var array<string, string>
     */
    private array $descriptions;

    private int $colSpan;

    private ?string $visibleCondition;

    private ?string $disabledCondition;

    /**
     * @var array<string, SectionNode|FieldMetadata>
     */
    private array $children = [];

    public function __construct(SectionMetadata $section)
    {
        $this->name = $section->getName();
        $this->labels = $section->getLabels();
        $this->descriptions = $section->getDescriptions();
        $this->colSpan = $section->getColSpan();
        $this->visibleCondition = $section->getVisibleCondition();
        $this->disabledCondition = $section->getDisabledCondition();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Values set on the overriding section win, everything not set is kept.
     */
    public function applyOverrides(SectionMetadata $override): void
    {
        if ([] !== $override->getLabels()) {
            $this->labels = $override->getLabels();
        }

        if ([] !== $override->getDescriptions()) {
            $this->descriptions = $override->getDescriptions();
        }

        if (12 !== $override->getColSpan()) {
            $this->colSpan = $override->getColSpan();
        }

        if (null !== $override->getVisibleCondition()) {
            $this->visibleCondition = $override->getVisibleCondition();
        }

        if (null !== $override->getDisabledCondition()) {
            $this->disabledCondition = $override->getDisabledCondition();
        }
    }

    /**
     * @return array<string, SectionNode|FieldMetadata>
     */
    public function &getChildren(): array
    {
        return $this->children;
    }

    public function isEmpty(): bool
    {
        foreach ($this->children as $child) {
            if ($child instanceof FieldMetadata || !$child->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    public function toMetadata(): SectionMetadata
    {
        $section = new SectionMetadata($this->name);
        $section->setLabels($this->labels);
        $section->setDescriptions($this->descriptions);
        $section->setColSpan($this->colSpan);
        $section->setVisibleCondition($this->visibleCondition);
        $section->setDisabledCondition($this->disabledCondition);

        foreach ($this->children as $child) {
            if ($child instanceof FieldMetadata) {
                $section->addItem($child);
            } elseif (!$child->isEmpty()) {
                $section->addItem($child->toMetadata());
            }
        }

        return $section;
    }
}
