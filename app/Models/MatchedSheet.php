<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 8/17/2017
 * Time: 11:43 AM
 */

namespace App\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * @ORM\Entity
 * @ORM\Table(name="matched_sheets")
 */
class MatchedSheet extends BaseModel
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
     * @var MatchedSheet
     *
     * @ORM\ManyToOne(targetEntity="MatchedSheet")
     * @ORM\JoinColumn(name="parent_matched_sheet_id", referencedColumnName="id")
     */
    protected $parentMatchedSheet;

    /**
     * @var ArrayCollection|MatchedAttribute[]
     * @ORM\OneToMany(targetEntity="MatchedAttribute", mappedBy="matchedSheet", cascade={"persist", "remove"})
     */
    protected $matchedAttributeList;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $matchingPercentage;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $requirementSheetAttrCount;

    /**
     * Talent constructor.
     * @param RequirementSheet $requirementSheet
     * @param SpecSheet $specSheet
     * @param MatchedSheet
     */
    public function __construct(RequirementSheet $requirementSheet, SpecSheet $specSheet, MatchedSheet $matchedSheet = null)
    {
        $this->parentMatchedSheet = $matchedSheet;
        $this->matchingPercentage = 0;
        $this->category = $specSheet->getCategory();
        $this->matchedAttributeList = new ArrayCollection();
        $this->requirementSheetAttrCount = $requirementSheet->getAttrRequirementList()->count();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getMatchingPercentage()
    {
        return $this->matchingPercentage;
    }

    /**
     * @return MatchedSheet
     */
    public function getParentMatchedSheet()
    {
        return $this->parentMatchedSheet;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return int
     */
    public function getRequirementSheetAttrCount()
    {
        return $this->requirementSheetAttrCount;
    }

    public function updateMatchingPercentage() {
        $this->matchingPercentage = $this->getMatchedAttributeList()->count() * 100
            / $this->getRequirementSheetAttrCount();
        $this->matchingPercentage = round($this->matchingPercentage, 2);
    }

    /**
     * @return ArrayCollection|MatchedAttribute[]
     */
    public function getMatchedAttributeList()
    {
        return $this->matchedAttributeList;
    }

    public function toArray($type = self::ARRAY_MIN)
    {
        $array = parent::toArray($type);
        $array['parentMatchedSheet'] = null;
        if (!is_null($this->getParentMatchedSheet())) {
            $array['parentMatchedSheet'] = ['id' => $this->getParentMatchedSheet()->getId()];
        }
        return $array;
    }

    public static function matchSheet(RequirementSheet $requirementSheet, SpecSheet $specSheet) {
        $matchedSheet = null;
        foreach($requirementSheet->getAttrRequirementList() as $attrReq) {
            foreach($specSheet->getAttributeList() as $attribute) {
                $matchedAttribute = MatchedAttribute::matchAttribute($attrReq, $attribute);
                if (!is_null($matchedAttribute)) {
                    if (is_null($matchedSheet)) {
                        $matchedSheet = new MatchedSheet($requirementSheet, $specSheet);
                    }
                    $matchedSheet->getMatchedAttributeList()->add($matchedAttribute);
                    $matchedAttribute->setMatchedSheet($matchedSheet);
                    break;
                }
            }
        }
        if (!is_null($matchedSheet)) {
            $matchedSheet->updateMatchingPercentage();
        }

        return $matchedSheet;
    }
}