<?php

declare(strict_types=1);

namespace Manuxi\SuluArticleConfigurationBundle\Schema;

final class DefaultFields
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'layoutStyle' => [
                'type' => FieldDefinition::TYPE_SINGLE_SELECT,
                'values' => ['default', 'wide', 'fullwidth', 'narrow'],
                'default' => 'fullwidth',
                'section' => 'display_options',
                'colspan' => 6,
            ],
            'showToc' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'display_options', 'colspan' => 3],
            'showReadingTime' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'display_options', 'colspan' => 3],
            'showAuthorBox' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'display_options', 'colspan' => 3],
            'showRelated' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'display_options', 'colspan' => 3],
            'enableSidebar' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'sidebar_options', 'colspan' => 6],
            'sidebarPosition' => [
                'type' => FieldDefinition::TYPE_SINGLE_SELECT,
                'values' => ['left', 'right'],
                'default' => 'right',
                'section' => 'sidebar_options',
                'colspan' => 6,
                'visible_condition' => 'enableSidebar == true',
            ],
            'enableShareButtons' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'features', 'colspan' => 4],
            'enablePrint' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => true, 'section' => 'features', 'colspan' => 4],
            'hidePublishDate' => ['type' => FieldDefinition::TYPE_TOGGLE, 'default' => false, 'section' => 'publication_settings', 'colspan' => 6],
            'customCssClass' => ['type' => FieldDefinition::TYPE_TEXT, 'section' => 'styling', 'colspan' => 6],
        ];
    }
}
