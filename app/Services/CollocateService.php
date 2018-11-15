<?php
namespace App\Services;

use App\Models\Variables;

class CollocateService
{
    protected $variableModel;

    public function __construct(Variables $variable)
    {
        $this->variableModel = $variable;
    }

    public function configuration()
    {
        return $this->variableModel->get();
    }
}