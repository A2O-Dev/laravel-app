<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 8/17/2017
 * Time: 11:43 AM
 */

namespace App\Models;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * @ORM\Entity
 * @ORM\Table(name="matched_attributes")
 */
class MatchedAttribute extends BaseModel
{
    use Timestamps;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $rules;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $type;

    /**
     * @var MatchedSheet
     *
     * @ORM\ManyToOne(targetEntity="MatchedSheet")
     * @ORM\JoinColumn(name="matched_sheet_id", referencedColumnName="id")
     * @deprecated
     */
    protected $matchedSheet;

    /**
     * @var ProfileMatching
     *
     * @ORM\ManyToOne(targetEntity="ProfileMatching")
     * @ORM\JoinColumn(name="profile_matching_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $profileMatching;

    /**
     * AttributeRequirement constructor.
     * @param ProfileMatching $profileMatching
     * @param AttributeDefinition $attributeDefinition
     * @param AttributeRequirement $attributeRequirement
     * @param Attribute $attribute
     */
    public function __construct(ProfileMatching $profileMatching, AttributeDefinition $attributeDefinition,
                                AttributeRequirement $attributeRequirement,
                                Attribute $attribute)
    {
        $this->profileMatching = $profileMatching;
        $this->profileMatching->getMatchedAttributeList()->add($this);
        $this->name = $attributeDefinition->getName();
        $this->type = $attributeRequirement->getType();
        if (is_array($attribute->getValue())) {
            $this->value = json_encode($attribute->getValue());
        } else {
            $this->value = $attribute->getValue();
        }

        $this->setRules($attributeRequirement->getRules());
    }

    /**
     * @param array $rules
     */
    public function setRules(array $rules)
    {
        $this->rules = json_encode($rules);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        $value = json_decode($this->value, true);
        if (is_array($value)) {
            return $value;
        } else {
            return $this->value;
        }
    }

    /**
     * @param MatchedSheet $matchedSheet
     * @deprecated
     */
    public function setMatchedSheet($matchedSheet)
    {
        $this->matchedSheet = $matchedSheet;
    }

    /**
     * @return MatchedSheet
     * @deprecated
     */
    public function getMatchedSheet()
    {
        return $this->matchedSheet;
    }

    public function toArray($type = self::ARRAY_MIN)
    {
        $array = parent::toArray();
        $array['rules'] = $this->getRules();
        return $array;
    }
}