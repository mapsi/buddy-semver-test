<?php

namespace App\Http\ViewComponents\ComponentLogic;

class OtherBrandsLogic extends BaseComponentLogic
{
    use FetchesData;

    /**
     * @return array
     */
    public function get()
    {
        return [
            'title' => lang('other_brands.title'),
            'brands' => $this->getOtherBrands(),
        ];
    }

    /**
     * @return array
     */
    private function getOtherBrands()
    {
        $otherBrands = [
            [
                'link' => 'https://globalarbitrationreview.com/',
                'class' =>  'gar'
            ],
            [
                'link' => 'https://globalcompetitionreview.com/',
                'class' =>  'gcr'
            ],
            [
                'link' => 'https://globalinvestigationsreview.com/',
                'class' =>  'gir'
            ],
            [
                'link' => 'https://globalrestructuringreview.com/',
                'class' =>  'grr'
            ],
            [
                'link' => 'https://globaldatareview.com/',
                'class' =>  'gdr'
            ],
            [
                'link' => 'https://latinlawyer.com/',
                'class' =>  'll'
            ],
            [
                'link' => 'http://whoswholegal.com/',
                'class' =>  'wwl'
            ],
            [
                'link' => 'https://thelawreviews.co.uk/',
                'class' =>  'tlr'
            ],
            [
                'link' => 'https://gettingthedealthrough.com/',
                'class' =>  'gtdt'
            ],
        ];

        $currentBrand = get_host_config()['machine_name'];

        $otherBrands = array_filter($otherBrands, function ($var) use ($currentBrand) {
            return ($var['class'] != $currentBrand);
        });

        // limit listing to 8 brands
        return array_slice($otherBrands, 0, 8);
    }
}
