<?php

namespace Fanwall\Model;

use Core\Model\Base;
use Siberian\Image;
use Zend_Exception;

/**
 * Class Fanwall
 * @package Fanwall\Model
 *
 * @method string getAdminEmails()
 * @method string getIconTopics()
 * @method string getIconNearby()
 * @method string getIconMap()
 * @method string getIconGallery()
 * @method string getIconPost()
 */
class Fanwall extends Base
{
    /**
     * Radius constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct ($params = [])
    {
        parent::__construct($params);
        $this->_db_table = "Fanwall\Model\Db\Table\Fanwall";
        return $this;
    }

    /**
     * @return array
     */
    public function buildSettings ()
    {
        $settings = [
            "icons" => [],
        ];
        $icons = [
            "topics" => $this->getIconTopics(),
            "nearby" => $this->getIconNearby(),
            "map" => $this->getIconMap(),
            "gallery" => $this->getIconGallery(),
            "post" => $this->getIconPost(),
        ];
        foreach ($icons as $key => $path) {
            $iconPath = path("/images/application{$path}");
            if (is_file($iconPath)) {
                $settings["icons"][$key] = (new Image($iconPath))->resize(32, 32)->inline("png", 100);
            } else {
                $settings["icons"][$key] = null;
            }
        }

        $settings["cardDesign"] = (boolean) ($this->getDesign() === "card");

        return $settings;
    }

    /**
     * @param null $optionValue
     * @return array|bool
     * @throws Zend_Exception
     */
    public function getEmbedPayload($optionValue = null)
    {
        $fanWall = (new self())->find($optionValue->getId(), "value_id");

        return [
            "settings" => $fanWall->buildSettings()
        ];
    }

}
