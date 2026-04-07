<?php
namespace PageBlocks\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class TopicsListSidebarForm extends Fieldset
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][topics][__attachmentIndex__][label]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Topic label', // @translate
            ],
            'attributes' => [
                'data-sidebar-id' => 'topic-data-label'
            ]
        ]);
        
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][topics][__attachmentIndex__][icon]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Icon name', // @translate
                'info' => 'Font Awesome 5 icon class name', // @translate
                'documentation' => 'https://fontawesome.com/v5/search?m=free&s=solid'
            ],
            'attributes' => [
                'data-sidebar-id' => 'topic-data-icon'
            ]
        ]);
        
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][topics][__attachmentIndex__][link]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Topic button link' // @translate
            ],
            'attributes' => [
                'data-sidebar-id' => 'topic-data-link'
            ]
        ]);
    }
}
?>