<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 8/17/2017
 * Time: 11:43 AM
 */
namespace App\Models;

use App\Helpers\StorageFile;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * @ORM\Entity
 * @ORM\Table(name="role_calls")
 * @deprecated
 */
class Casting extends BaseModel
{

    const STATUS_ACTIVE = 'active';         // status active
    const STATUS_INACTIVE = 'inactive';     // status inactive
    const STATUS_ARCHIVED = 'archived';     // status archived

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
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $image;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDate;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locationName;

    /**
     * @ORM\OneToOne(targetEntity="Location", cascade={"remove"})
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var CastingType
     *
     * @ORM\ManyToOne(targetEntity="CastingType", fetch="EAGER")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    protected $type;

    /**
     * Many Talents have Many CastingAttributeValues.
     *
     * @var CastingAttributeValue[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="CastingAttributeValue")
     * @ORM\JoinTable(name="casting_casting_attribute_values",
     *      joinColumns={@ORM\JoinColumn(name="casting_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="casting_attribute_value_id", referencedColumnName="id")}
     *      )
     */
    private $castingAttributeValues;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $paymentType;

    /**
     * @var int
     *
     * @ORM\Column(type="string")
     */
    protected $requiredTalentsCount;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @var CastingDirector
     *
     * @ORM\ManyToOne(targetEntity="CastingDirector", inversedBy="castingList")
     * @ORM\JoinColumn(name="castingDirector_id", referencedColumnName="id")
     */
    protected $castingDirector;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", cascade={"remove"}, fetch="EAGER")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    protected $project;

    /**
     * @var RequirementSheet
     *
     * @ORM\ManyToOne(targetEntity="RequirementSheet", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="req_sheet_id", referencedColumnName="id")
     */
    protected $requirementSheet;

    /**
     * @var
     */
    protected $processedTalentsCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $searchRadius;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $roleAge;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $roleGender;

    /**
     * @var CastingTalent[]
     * @ORM\OneToMany(targetEntity="CastingTalent", mappedBy="casting", cascade={"remove"})
     */
    private $castingTalentList;

    /**
     * @var Chat[]
     * @ORM\OneToMany(targetEntity="Chat", mappedBy="casting", cascade={"remove"})
     */
    private $chatList;


    /**
     * Casting constructor.
     * @param Category $personCategory
     * @param CastingDirector $castingDirector
     * @param Project $project
     * @param CastingType $castingType
     * @param ArrayCollection $castingAttributeValues
     * @param string $name
     */
    public function __construct(Category $personCategory, CastingDirector $castingDirector, Project $project, CastingType $castingType, $castingAttributeValues, $name)
    {
        $this->type = $castingType;
        $this->name = $name;
        $this->status = self::STATUS_ACTIVE;
        $this->castingDirector = $castingDirector;
        $this->project = $project;
        $this->requirementSheet = new RequirementSheet($personCategory, 'Role Requirements');
        $this->requiredTalentsCount = 1;
        $this->searchRadius = null;
        $this->roleAge = null;
        $this->roleGender = null;
        $this->castingAttributeValues = $castingAttributeValues;
        $this->castingTalentList = new ArrayCollection();
        $this->chatList = new ArrayCollection();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param StorageFile $image
     */
    public function setImage(StorageFile $image)
    {
        $this->image = json_encode($image);
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param string $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @param string $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @param Location $location
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return ArrayCollection|CastingAttributeValue[]
     */
    public function getCastingAttributeValues()
    {
        return $this->castingAttributeValues;
    }

    /**
     * @param ArrayCollection|CastingAttributeValue[] $castingAttributeValues
     */
    public function setCastingAttributeValue($castingAttributeValues)
    {
        $this->castingAttributeValues = $castingAttributeValues;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param string $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
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
     * @return StorageFile
     */
    public function getImage()
    {
        return StorageFile::createFromJsonString($this->image);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return CastingDirector
     */
    public function getCastingDirector()
    {
        return $this->castingDirector;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return RequirementSheet
     */
    public function getRequirementSheet()
    {
        return $this->requirementSheet;
    }

    /**
     * @return CastingType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param CastingType $type
     */
    public function setType(CastingType $type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getRequiredTalentsCount()
    {
        return $this->requiredTalentsCount;
    }

    /**
     * @param int $requiredTalentsCount
     */
    public function setRequiredTalentsCount($requiredTalentsCount)
    {
        $this->requiredTalentsCount = $requiredTalentsCount;
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @return int
     */
    public function getSearchRadius()
    {
        return $this->searchRadius;
    }

    /**
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @param int $searchRadius
     */
    public function setSearchRadius($searchRadius)
    {
        $this->searchRadius = $searchRadius;
    }

    /**
     * @param string $locationName
     */
    public function setLocationName($locationName)
    {
        $this->locationName = $locationName;
    }

    /**
     * @return string
     */
    public function getRoleAge()
    {
        return json_decode($this->roleAge, true);
    }

    /**
     * @param string $roleAge
     */
    public function setRoleAge($roleAge)
    {
        if (is_null($roleAge)) {
            $this->roleAge = $roleAge;
        } else {
            $this->roleAge = json_encode($roleAge);
        }
    }

    /**
     * @return string
     */
    public function getRoleGender()
    {
        return $this->roleGender;
    }

    /**
     * @param string $roleGender
     */
    public function setRoleGender($roleGender)
    {
        $this->roleGender = $roleGender;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getFullName() {
        return $this->getProject()->getName() . " - " . $this->getName();
    }

    public function toArray($type = self::ARRAY_MIN)
    {
        $array = parent::toArray();
        $array['castingDirector'] = $this->getCastingDirector();
        $array['project'] = $this->getProject();
        $array['requirementSheet'] = ['id' => $this->getRequirementSheet()->getId()];
        $array['type'] = $this->getType();
        $array['createdAt'] = $this->getCreatedAt();
        $array['updatedAt'] = $this->getUpdatedAt();
        $array['image'] = $this->getImage();
        $array['status'] = $this->getStatus();
        $array['requirementSheet'] = $this->getRequirementSheet();
        $array['roleAge'] = $this->getRoleAge();
        $array['location'] = ['name' => $this->getLocationName()];

        if ($type == self::ARRAY_FULL) {
            $array['location'] = $this->getLocation();

            $array['unionTypes'] = [];
            foreach($this->getCastingAttributeValues() as $castingAttributeValue) {
                if ($castingAttributeValue->getType() == 'union') {
                    $array['unionTypes'][] = $castingAttributeValue;
                }
            }
        }

        return $array;
    }

    /**
     * @param string $types Name of the index of the array
     * @param string $value Type of attribute to filter
     * @return array
     */
    public function getFilteredAttributeIds($types, $value){
        $attributes[$types] = [];

        foreach ($this->getCastingAttributeValues() as $castingAttributeValue){
            if($castingAttributeValue->getType() == $value){
                $attributes[$types][] = $castingAttributeValue->getId();
            }
        }
        return $attributes;
    }
}