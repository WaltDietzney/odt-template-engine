<?php
namespace OdtTemplateEngine\Elements;

use DOMDocument;
use DOMNode;
use OdtTemplateEngine\Contracts\HasStyles;
use OdtTemplateEngine\Elements\OdtElement;
use OdtTemplateEngine\Elements\Paragraph;

class ListElement extends OdtElement implements HasStyles
{
    protected string $styleName;
    protected string $type; // 'numbered' oder 'bullet'
    protected array $items = [];

    protected int $level = 1;

    public function __construct(string $type = 'bullet', string $styleName = null)
    {
        $this->type = $type;
        $this->styleName = $styleName ?? $this->getDefaultStyleName();
    }

    protected function getDefaultStyleName(): string
    {
        return $this->type === 'numbered' ? 'Numbering_20_Symbol' : 'Bullet_20_Symbol';
    }

    public function addItem(Paragraph|self $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function setLevel(int $level): self
    {
        $this->level = max(1, min(10, $level));
        return $this;
    }

    public function addSubList(ListElement $list): self
    {
        $list->setLevel($this->level + 1);
        return $this->addItem($list);
    }


    public function toDomNode(DOMDocument $dom): DOMNode
    {
        $list = $dom->createElement('text:list');
        $list->setAttribute('text:style-name', $this->styleName);

        foreach ($this->items as $item) {
            $itemNode = $dom->createElement('text:list-item');

            if ($item instanceof Paragraph) {
                $itemNode->appendChild($item->toDomNode($dom));
            } elseif ($item instanceof self) {
                $itemNode->appendChild($item->toDomNode($dom)); // nested <text:list>
            }

            $list->appendChild($itemNode);
        }

        
        return $list;
    }

    public function registerStyles(): void{}

}
