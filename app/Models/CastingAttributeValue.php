<?php
/**
 * Created by PhpStorm.
 * User: Dev2
 * Date: 4/5/2018
 * Time: 09:54
 */

namespace App\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * @ORM\Entity
 * @ORM\Table(name="casting_attribute_value")
 */
class CastingAttributeValue extends BaseModel
{
    use Timestamps;

    /**
     * @var $id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var $label
     *
     * @ORM\Column(type="string")
     */
    protected $label;

    /**
     * @var $value
     *
     * @ORM\Column(type="string")
     */
    protected $value;

    /**
     * @var $type
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * CastingAttributeValue constructor.
     * @param $label
     * @param $value
     * @param $type
     */
    public function __construct($label, $value, $type)
    {
        $this->label = $label;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}