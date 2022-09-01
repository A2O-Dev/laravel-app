<?php
/**
 * Created by PhpStorm.
 * User: Ariel
 * Date: 11/28/2017
 * Time: 11:46 AM
 */

namespace App\Repositories;


use App\Models\MatchedSheet;

class MatchedSheetRepository extends BaseRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        $this->className = MatchedSheet::class;
    }
}