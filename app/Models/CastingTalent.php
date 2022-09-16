<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 8/17/2017
 * Time: 11:43 AM
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * @ORM\Entity
 * @ORM\Table(name="casting_talents")
 */
class CastingTalent extends BaseModel
{

    const STATUS_CHECKED = 'checked';
    const STATUS_MATCHED = 'matched';
    const STATUS_INVITED = 'invited';
    const STATUS_SELECTED = 'selected';
    const STATUS_PASSED_BY_DIRECTOR = 'passed_by_director';
    const STATUS_PASSED_BY_TALENT = 'passed_by_talent';
    const STATUS_BOOKED = 'booked';
    const STATUS_CALLBACK = 'callback';

    const STATUS_RESTORE = 'status_restore';

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
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $previousStatus;

    /**
     * @var Casting
     *
     * @ORM\ManyToOne(targetEntity="Casting", inversedBy="castingTalentList", fetch="EAGER")
     * @ORM\JoinColumn(name="casting_id", referencedColumnName="id")
     */
    protected $casting;

    /**
     * @var Talent
     *
     * @ORM\ManyToOne(targetEntity="Talent", inversedBy="castingTalentList", fetch="EAGER")
     * @ORM\JoinColumn(name="talent_id", referencedColumnName="id")
     */
    protected $talent;

    /**
     * @var MatchedSheet
     *
     * @ORM\ManyToOne(targetEntity="MatchedSheet", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="matched_sheet_id", referencedColumnName="id")
     */
    protected $matchedSheet;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    protected $matchingPercentage;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    protected $reminderCount;

    const ARRAY_TALENT = 'talent';
    const ARRAY_CASTING = 'casting';

    /**
     * Casting constructor.
     * @param Casting $casting
     * @param Talent $talent
     */
    public function __construct(Casting $casting, Talent $talent)
    {
        $this->status = self::STATUS_CHECKED;
        $this->casting = $casting;
        $this->talent = $talent;
        $this->reminderCount = 0;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getPreviousStatus()
    {
        return $this->previousStatus;
    }

    /**
     * @param string $previousStatus
     */
    public function setPreviousStatus($previousStatus)
    {
        $this->previousStatus = $previousStatus;
    }

    /**
     * @return Casting
     */
    public function getCasting()
    {
        return $this->casting;
    }

    /**
     * @return Talent
     */
    public function getTalent()
    {
        return $this->talent;
    }

    /**
     * @return MatchedSheet
     */
    public function getMatchedSheet()
    {
        return $this->matchedSheet;
    }

    /**
     * @param MatchedSheet $matchedSheet
     */
    public function updateMatchedSheet(MatchedSheet $matchedSheet)
    {
        $this->matchedSheet = $matchedSheet;
        $this->status = self::STATUS_MATCHED;
        $this->updateMatchingPercentage();
    }

    /**
     * @return float
     */
    public function getMatchingPercentage()
    {
        return $this->matchingPercentage;
    }

    public function updateMatchingPercentage() {
        $this->matchingPercentage = round($this->getMatchedSheet()->getMatchingPercentage(), 2);
    }

    public function toArray($type = self::ARRAY_MIN)
    {
        $array = parent::toArray();
        $array['casting'] = [
            "id" => $this->getCasting()->getId(),
            "status"=>$this->getCasting()->getStatus(),
            'castingDirector' => $this->getCasting()->getCastingDirector()
        ];
        $array['talent'] = ["id" => $this->getTalent()->getId()];
        $array['createdAt'] = $this->getCreatedAt();
        $array['updatedAt'] = $this->getUpdatedAt();
        $array['status']=$this->getStatus();

        if ($type == self::ARRAY_TALENT) {
            $array['talent'] = $this->getTalent();
        }
        if ($type == self::ARRAY_CASTING) {
            $array['casting'] = $this->getCasting()->toArray(Casting::ARRAY_FULL);
        }

        if ($type == self::ARRAY_FULL) {
            $array['talent'] = $this->getTalent();
            $array['casting'] = $this->getCasting();
        }

        return $array;
    }

    /**
     * Check if the given talent matches the casting requirements
     * Returns a CastingTalent with the matched status
     *
     * @param Casting $casting
     * @param Talent $talent
     * @return CastingTalent
     */
    public static function matchTalent(Casting $casting, Talent $talent) {
        $castingTalent = new CastingTalent($casting, $talent);

        if(count($casting->getRequirementSheet()->getAttrRequirementList()) > 0){
            $matchedSheet = MatchedSheet::matchSheet($casting->getRequirementSheet(), $talent->getProfile());
            if (!is_null($matchedSheet)) {
                $castingTalent->updateMatchedSheet($matchedSheet);
            }
        }
        else{
            $castingTalent->forceMatching();
        }
        return $castingTalent;
    }

    public function invite(){
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_INVITED;
    }

    public function select() {
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_SELECTED;
    }

    public function callback(){
        $this->reminderCount = 0;
        $this->previousStatus=$this->getStatus();
        $this->status = self::STATUS_CALLBACK;
    }

    public function book() {
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_BOOKED;
    }

    public function pass_by_director() {
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_PASSED_BY_DIRECTOR;
    }

    public function pass_by_talent(){
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_PASSED_BY_TALENT;
    }

    public function forceMatching(){
        $this->reminderCount = 0;
        $this->previousStatus = $this->getStatus();
        $this->status = self::STATUS_MATCHED;
        $this->matchingPercentage = 100;
    }

    public function statusRestore(){
        $this->reminderCount = 0;
        $status = $this->getStatus();
        $this->status = $this->getPreviousStatus();
        $this->previousStatus = $status;
    }

    public function addReminder() {
        $this->reminderCount += 1;
    }

    /**
     * @return int
     */
    public function getReminderCount()
    {
        return $this->reminderCount;
    }

}