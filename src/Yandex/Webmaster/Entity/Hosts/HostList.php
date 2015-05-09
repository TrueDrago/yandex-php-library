<?php
/**
 * @author idmultiship
 */

namespace Yandex\Webmaster\Entity\Hosts;

use JMS\Serializer\Annotation\XmlRoot;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\XmlList;

/**
 * Class HostList
 * @package Yandex\Webmaster\Entity\Hosts
 * @XmlRoot("hostlist")
 */
class HostList
{
    /**
     * @XmlList(inline = true, entry = "host")
     * @Type("array<Yandex\Webmaster\Entity\Hosts\Host>")
     * @var Host[]
     */
    public $hosts;

    public function hasHost($id)
    {
        foreach ($this->hosts as $host) {
            if ($host->getId() == $id) {
                return true;
            }
        }
        return false;
    }
}