<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 8/17/2017
 * Time: 11:43 AM
 */

namespace App\Models;

use App\Helpers\StorageFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * @ORM\Entity
 * @ORM\Table(name="talents")
 */
class Talent extends BaseModel
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
     * @ORM\Column(type="string")
     */
    protected $firstName;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var SpecSheet
     *
     * @ORM\ManyToOne(targetEntity="SpecSheet", fetch="EAGER", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="spec_sheet_id", referencedColumnName="id")
     */
    protected $profile;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locationName;

    /**
     * @ORM\OneToOne(targetEntity="Location", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birthday;

    /**
     * Many Talents have Many CastingTypes.
     *
     * @var CastingType[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="CastingType")
     * @ORM\JoinTable(name="talent_casting_types",
     *      joinColumns={@ORM\JoinColumn(name="talent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_call_type_id", referencedColumnName="id")}
     *      )
     */
    private $castingTypes;

    /**
     * Many Talents have Many CastingAttributeValues.
     *
     * @var CastingAttributeValue[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="CastingAttributeValue")
     * @ORM\JoinTable(name="talent_casting_attribute_values",
     *      joinColumns={@ORM\JoinColumn(name="talent_id", referencedColumnName="id")},
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
     * Many Talents have Many ProjectTypes.
     *
     * @var ProjectType[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="ProjectType")
     * @ORM\JoinTable(name="talent_project_types",
     *      joinColumns={@ORM\JoinColumn(name="talent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="project_type_id", referencedColumnName="id")}
     *      )
     */
    private $projectTypes;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $profileImage;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $images;

    /**
     * @var String
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $gender;

    /**
     * @var CastingTalent[]
     * @ORM\OneToMany(targetEntity="CastingTalent", mappedBy="talent", cascade={"remove"})
     */
    private $castingTalentList;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    protected $isPlus;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    protected $represented;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $agency;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $videoUrl;

    /**
     * Talent constructor.
     * @param Category $personCategory
     * @param string $firstName
     * @param string $lastName
     */
    public function __construct(Category $personCategory, $firstName, $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->profile = new SpecSheet($personCategory, 'Features');
        $this->castingTypes = new ArrayCollection();
        $this->projectTypes = new ArrayCollection();
        $this->castingTalentList = new ArrayCollection();
        $this->isPlus = false;
        $this->represented = false;
        $this->castingAttributeValues = new ArrayCollection();
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
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return SpecSheet
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
    }

    /**
     * @return Location
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @param string $locationName
     */
    public function setLocationName($locationName)
    {
        $this->locationName = $locationName;
    }

    /**
     * @return ArrayCollection|CastingType[]
     */
    public function getCastingTypes()
    {
        return $this->castingTypes;
    }

    /**
     * @return ArrayCollection|ProjectType[]
     */
    public function getProjectTypes()
    {
        return $this->projectTypes;
    }

    /**
     * @return ArrayCollection|CastingAttributeValue[]
     */
    public function getCastingAttributeValues()
    {
        return $this->castingAttributeValues;
    }

    /**
     * @param mixed $castingAttributeValues
     */
    public function setCastingAttributeValues($castingAttributeValues)
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
     * @return StorageFile[]
     */
    public function getImages()
    {
        $arrayList = json_decode($this->images, true);
        $storageFileList = [];
        if (is_array($arrayList)) {
            foreach($arrayList as $arrayImage) {
                $tmpImage = StorageFile::createFromArray($arrayImage);
                if (!is_null($tmpImage)) {
                    $storageFileList[] = $tmpImage;
                }
            }
        }
        return $storageFileList;
    }

    /**
     * @param StorageFile[] $images
     */
    public function setImages(array $images)
    {
        $this->images = json_encode($images);
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param \DateTime $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * @return String
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param String $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return bool
     */
    public function isPlus()
    {
        return $this->isPlus;
    }

    /**
     * @param bool $isPlus
     */
    public function setIsPlus($isPlus)
    {
        $this->isPlus = $isPlus;
    }
    /**
     * @return bool
     **/
    public function getRepresented()
    {
        return $this->represented;
    }

    /**
     * @param bool $represented
     */
    public function setRepresented($represented)
    {
        $this->represented=$represented;
    }

    /**
     * @return string
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * @param string $agency
     */
    public function setAgency($agency)
    {
        $this->agency = $agency;
    }

    /**
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * @param string $videoUrl
     */
    public function setVideoUrl(string $videoUrl = null)
    {
        $this->videoUrl = $videoUrl;
    }


    public function toArray($type = self::ARRAY_MIN)
    {
        $array = parent::toArray($type);
        $array['user'] = $this->getUser();
        $array['castingTypes'] = $this->getCastingTypes()->toArray();
        $array['projectTypes'] = $this->getProjectTypes()->toArray();
        $array['location'] = ['name' => $this->getLocationName()];
        $array['profile'] = $this->getProfile();
        $array['represented']=$this->getRepresented();
        $array['agency']=$this->getAgency();

        unset($array['images']);
        if ($type == self::ARRAY_FULL) {
            $array['imageList'] = $this->getImages();
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
}