<?php
/**
 * @author idmultiship
 */

namespace Yandex\Webmaster\Entity;

use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlRoot;
/**
 * Class OriginalText
 * @package Yandex\Webmaster\Entity
 * @XmlRoot("original-text")
 */
class OriginalText 
{
    /**
     * @XmlElement(cdata=false)
     * @Type("string")
     * @var string
     */
    public $content;

    /**
     * @XmlElement(cdata=false)
     * @Type("string")
     * @var string
     */
    public $id;

    /**
     * @param string|null $content
     */
    public function __construct($content = null)
    {
        $this->content = $content;
    }
}