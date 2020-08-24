<?php

namespace App\Classes;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LbrUserValidator
{
    /**
     * @var array
     */
    protected $inputBrands;

    /**
     * @var array
     */
    protected $inputBrandUsernames;

    /**
     * @var array
     */
    protected $inputBrandPasswords;

    /**
     * @var int / null
     */
    protected $userid;

    /**
     * @var array
     */
    protected $validations;

     /**
     * @var array
     */
    protected $errorMessages;

    /**
     * @param $inputBrands
     */
    public function __construct($userid, $inputBrands, $inputBrandUsernames, $inputBrandPasswords)
    {
        $this->userid = $userid;
        $this->inputBrands = $inputBrands;
        $this->inputBrandUsernames = $inputBrandUsernames;
        $this->inputBrandPasswords = $inputBrandPasswords;
        $this->validations = config('globemedia.validations');
        $this->errorMessages = [];
    }

    /**
     * @param $rules
     * @return array
     * @throws ValidationException
     */
    public function checkAndUpdateRules($rules)
    {
        return $this->updateValidations('rules', $rules);
    }

    /**
     * @param $messages
     * @return array
     * @throws ValidationException
     */
    public function checkAndUpdateMessages($messages)
    {
        return $this->updateValidations('messages', $messages);
    }

    /**
     * @param $type
     * @param $data
     * @return array
     * @throws ValidationException
     */
    protected function updateValidations($type, $data)
    {
        $this->checkGeneralValidations();
        $this->checkUsernameUniquePerBrand();
        $this->checkPasswordPerBrand();
        $this->outputValidationMessages();


        foreach ($this->inputBrands as $inputBrandID) {
            $inputBrand = Brand::find($inputBrandID)->machine_name;
            if (! isset($this->validations[$inputBrand][$type])) {
                continue;
            }
            $data = array_merge($data, $this->validations[$inputBrand][$type]);
        }

        return $data;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    protected function checkGeneralValidations()
    {
        if (! $this->inputBrands) {
            throw ValidationException::withMessages([
                'brands' => ['At least one of the listed brands must be selected'],
            ]);
        }
    }

    /**
     * @return void
     */
    protected function checkUsernameUniquePerBrand()
    {
        foreach ($this->inputBrands as $brandid) {
            $username = $this->inputBrandUsernames[$brandid];

            if ($username) {
                $query = DB::table('brand_user')
                    ->where('brand_id', $brandid)
                    ->where('username', $username);

                if ((! $query->get()->isEmpty()) && $query->first()->user_id != $this->userid) {
                    $this->errorMessages[] = $this->getBrandCode($brandid) . ' username already taken';
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function checkPasswordPerBrand()
    {
        foreach ($this->inputBrands as $brandid) {
            $input = $this->inputBrandPasswords[$brandid];

            if ($input && strlen($input) < 6) {
                $this->errorMessages[] = $this->getBrandCode($brandid) . ' password is too short (min:6 chars)';
            }

            if (! $input && ! $this->userid) {
                $this->errorMessages[] = $this->getBrandCode($brandid) . ' password required (min:6 chars)';
            }

            if (! $input && $this->userid) {
                $user = User::where('id', $this->userid)->first();
                $brand = Brand::where('id', $brandid)->first();

                $hasPassword = $user->hasPasswordForBrand($brand);

                if ($hasPassword == false) {
                    $this->errorMessages[] = $this->getBrandCode($brandid) . ' password required (min:6 chars)';
                }
            }
        }
    }

    /**
     * @return string
     */
    private function getBrandCode($brandid)
    {
        $query = DB::table('brands')
            ->where('id', $brandid);

        return strtoupper($query->first()->machine_name);
    }

    /**
     * @return void
     * @throws ValidationEception
     */
    private function outputValidationMessages()
    {
        if (! empty($this->errorMessages)) {
            throw ValidationException::withMessages([
                'brands' => $this->errorMessages,
            ]);
        }
    }
}
