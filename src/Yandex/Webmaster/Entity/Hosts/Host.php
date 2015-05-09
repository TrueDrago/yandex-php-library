<?php
/**
 * @author idmultiship
 */

namespace Yandex\Webmaster\Entity\Hosts;

use JMS\Serializer\Annotation\XmlAttribute;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
/**
 * Class Host
 * @package Yandex\Webmaster\Entity
 */
class Host 
{
    /**
     * @XmlAttribute
     * @Type("string")
     * @var string
     */
    public $href;

    /**
     * @XmlElement(cdata=false)
     * @Type("string")
     * @var string
     */
    public $name;

    /**
     * @return int|null
     */
    public function getId()
    {
        preg_match('|https://webmaster.yandex.ru/api/v2/hosts/(\d+)|', $this->href, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }
}