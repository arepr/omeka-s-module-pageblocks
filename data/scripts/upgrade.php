<?php declare(strict_types=1);

namespace PageBlocks;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePageBlock;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 */

$migrators = [
    'team-members' => function (SitePageBlock $oldBlock) {
        $oldData = $oldBlock->getData();

        $newCards = array_map(function ($member) {
            return [
                'header' => $member['name'],
                'body' => $member['description'],
                'icon' => $member['avatar'],
                'button_text' => '',
                'button_link' => ''
            ];
        }, $oldData['members']);

        $newBlock = new SitePageBlock();
        $newBlock->setLayout('card-grid');
        $newBlock->setData([
            'header' => $oldData['header'],
            'compact' => true,
            'cards' => $newCards
        ]);

        return [
            'blocks' => [ $newBlock ]
        ];
    },
    'two-column' => function (SitePageBlock $oldBlock) {
        $oldData = $oldBlock->getData();
        
        $newBlockOne = new SitePageBlock();
        $newBlockOne->setLayout('html');
        $newBlockOne->setData([
            'html' => $oldData['html1']
        ]);
        
        $newBlockTwo = clone $newBlockOne;
        $newBlockTwo->setData([
            'html' => $oldData['html2']
        ]);
        
        return [
            'blocks' => [ $newBlockOne, $newBlockTwo ]
        ];
    },
    'three-column' => function (SitePageBlock $oldBlock) {
        $oldData = $oldBlock->getData();
        
        $newBlockOne = new SitePageBlock();
        $newBlockOne->setLayout('html');
        $newBlockOne->setData([
            'html' => $oldData['html1']
        ]);
        
        $newBlockTwo = clone $newBlockOne;
        $newBlockTwo->setData([
            'html' => $oldData['html2']
        ]);
        
        $newBlockThree = clone $newBlockTwo;
        $newBlockThree->setData([
            'html' => $oldData['html3']
        ]);
        
        return [
            'blocks' => [ $newBlockOne, $newBlockTwo, $newBlockThree ]
        ];
    },
    'media-single' => function (SitePageBlock $oldBlock) {
        $newBlockOne = new SitePageBlock();
        $newBlockOne->setLayout('media');
        $newBlockOne->setData([
            'layout' => '',
            'media_display' => '',
            'thumbnail_type' => 'large',
            'show_title_option' => 'no_title'
        ]);

        $newAttachments = [];
        foreach ($oldBlock->getAttachments() as $oldAttachment) {
            $newAttachment = clone $oldAttachment;
            $newAttachment->setBlock($newBlockOne);
            $newAttachments[] = $newAttachment;
        }
        
        $newBlockTwo = new SitePageBlock();
        $newBlockTwo->setLayout('html');
        $newBlockTwo->setData($oldBlock->getData());

        return [
            'blocks' => [ $newBlockOne, $newBlockTwo ],
            'attachments' => $newAttachments
        ];
    }
];

function migrateOldBlockTypes(array $migrators, EntityManager $manager) : void {
    $repository = $manager->getRepository("Omeka\Entity\SitePageBlock");

    $affectedPages = [];
    foreach (array_keys($migrators) as $layout) {
        $oldBlocks = $repository->findBy([ 'layout' => $layout ]);
        array_push($affectedPages, ...array_map(function ($oldBlock) {
            return $oldBlock->getPage();
        }, $oldBlocks));
    }

    $affectedPages = array_unique($affectedPages, SORT_REGULAR);
    foreach ($affectedPages as $affectedPage) {
        enableGridPageLayout($affectedPage);
        migrateAffectedPage($migrators, $manager, $affectedPage);
    }

    $manager->flush();
}

function enableGridPageLayout(SitePage $page) : void {
    $page->setLayout('grid');

    // Page has not been saved since before Omeka S 4.1.0
    if (!is_null($page->getLayoutData())) {
        return;
    }

    $layoutData = [
        "grid_columns" => "12",
        "grid_column_gap" => "10",
        "grid_row_gap" => "10",
        "template_name" => ""
    ];
    $page->setLayoutData($layoutData);

    $blocks = $page->getBlocks();
    foreach ($blocks as $block) {
        $block->setLayoutData([
            "grid_column_position" => "auto",
            "grid_column_span" => $layoutData['grid_columns']
        ]);
    }
}

function migrateAffectedPage(array $migrators, EntityManager $manager, SitePage $page) : void {
    $blocks = $page->getBlocks();
    $deltaPosition = 0;
    $numColumns = intval($page->getLayoutData()['grid_columns']);

    foreach ($blocks as $block) {
        // Cascade block positions to account for newly added ones
        $basePosition = $block->getPosition();
        if ($deltaPosition > 0) {
            $block->setPosition($basePosition + $deltaPosition);
        }

        // Only need to migrate affected blocks
        $layout = $block->getLayout();
        if (!in_array($layout, array_keys($migrators))) {
            continue;
        }

        $newObjects = $migrators[$layout]($block);
        $columnsPerBlock = floor($numColumns / count($newObjects['blocks']));

        foreach ($newObjects['blocks'] as $newBlock) {
            $newBlock->setPage($block->getPage());
            $newBlock->setPosition($basePosition + $deltaPosition++);
            $newBlock->setLayoutData([
                "grid_column_position" => "auto",
                "grid_column_span" => strval($columnsPerBlock)
            ]);
            $manager->persist($newBlock);
        }

        foreach ($newObjects['attachments'] ?? [] as $newAttachment) {
            $manager->persist($newAttachment);
        }

        $manager->remove($block);
        $deltaPosition--;
    }
}

if (version_compare($oldVersion, '2.0', '<')) {
    /** @var EntityManager $api */
    $manager = $services->get('Omeka\EntityManager');

    migrateOldBlockTypes($migrators, $manager);
}

?>