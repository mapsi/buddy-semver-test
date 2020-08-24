<?php

namespace App\Console\Commands;

use App\Models\Import;
use Illuminate\Console\Command;

class SetRegionParents extends Command
{
    protected $signature = 'regions:set-parents';
    protected $description = 'Sets the region heirarchy.';

    private $regions = [
        [
            'name' => 'Africa & Middle East',
            'tid' => 47,
            'uuid' => 'd76cf1d3-1b6a-4fb6-ac38-32d37710b150',
            'children' => [
                [
                    'tid' => 48,
                    'uuid' => 'dd85954d-14d8-4156-83a6-d22a24a614ff',
                    'name' => 'Benin',
                ], [
                    'tid' => 49,
                    'uuid' => '1c7eee0b-0a0f-4d4d-8663-af06657c64a2',
                    'name' => 'Burundi',
                ], [
                    'tid' => 50,
                    'uuid' => '24df20f6-9a86-4180-abfe-b9a0351b0cb6',
                    'name' => 'Cameroon',
                ], [
                    'tid' => 51,
                    'uuid' => '7e38c8a2-0cac-4415-a711-d8fe4df4557d',
                    'name' => 'Chad',
                ], [
                    'tid' => 52,
                    'uuid' => '3aeabd81-f940-4c82-9727-6d3f44c5b745',
                    'name' => 'Congo',
                ], [
                    'tid' => 53,
                    'uuid' => 'a3790aa7-d087-40d8-8c4b-2309bffd767c',
                    'name' => 'Côte d\'Ivoire',
                ], [
                    'tid' => 54,
                    'uuid' => 'd57b353f-da27-4ccf-9d9a-4349f7a0c6d0',
                    'name' => 'Egypt',
                ], [
                    'tid' => 55,
                    'uuid' => 'c511d95c-b54a-470f-9e41-2c352045db9f',
                    'name' => 'Gambia',
                ], [
                    'tid' => 56,
                    'uuid' => '78fc06e5-3df3-423e-8e9f-d6b53e48c396',
                    'name' => 'Guinea',
                ], [
                    'tid' => 57,
                    'uuid' => '55d854e7-d244-479b-b1d9-9b4755e009c3',
                    'name' => 'Iran, Islamic Republic of',
                ], [
                    'tid' => 58,
                    'uuid' => '4291b4fe-f893-4b35-8442-b3019f099808',
                    'name' => 'Iraq',
                ], [
                    'tid' => 59,
                    'uuid' => '1a2f674d-91cc-49c1-bf5f-d6badafb6ade',
                    'name' => 'Israel',
                ], [
                    'tid' => 60,
                    'uuid' => '7946b361-67ff-49cf-b5a7-802a1319fdbc',
                    'name' => 'Kenya',
                ], [
                    'tid' => 61,
                    'uuid' => 'eecb6d74-72d3-4b92-985b-c6f366e20ff4',
                    'name' => 'Lesotho',
                ], [
                    'tid' => 62,
                    'uuid' => '28eac46f-f6c2-4271-b885-c7d16f12ce93',
                    'name' => 'Malawi',
                ], [
                    'tid' => 63,
                    'uuid' => '84ae7046-a39a-4dce-aecf-56705ecfdc11',
                    'name' => 'Mali',
                ], [
                    'tid' => 64,
                    'uuid' => '0c1dc19d-879d-469e-8d5b-f9797a22338a',
                    'name' => 'Mozambique',
                ], [
                    'tid' => 65,
                    'uuid' => 'da1d9a64-d882-4a60-9130-09ebbdccb8a8',
                    'name' => 'Niger',
                ], [
                    'tid' => 66,
                    'uuid' => '9add9fc3-b6a2-4c2b-829e-bebc2290cbef',
                    'name' => 'Nigeria',
                ], [
                    'tid' => 67,
                    'uuid' => '63c1d237-d535-42b0-8333-d4e88fd3c570',
                    'name' => 'Oman',
                ], [
                    'tid' => 68,
                    'uuid' => '4f770552-3895-4d8e-a276-ee67445cacbf',
                    'name' => 'Qatar',
                ], [
                    'tid' => 69,
                    'uuid' => '9d5cb7fd-80be-4070-8cdb-ea6ff611b0a1',
                    'name' => 'Saudi Arabia',
                ], [
                    'tid' => 70,
                    'uuid' => 'a5441cd8-b506-4df6-b136-049476ab795a',
                    'name' => 'Senegal',
                ], [
                    'tid' => 71,
                    'uuid' => 'c13357bf-60b2-4a68-861d-c4a862f0aace',
                    'name' => 'South Africa',
                ], [
                    'tid' => 72,
                    'uuid' => '362d3123-c633-4eb3-bde2-866ab00cb9f4',
                    'name' => 'Swaziland',
                ], [
                    'tid' => 73,
                    'uuid' => '8fad46a1-1598-49d1-9c7f-be98659a62d7',
                    'name' => 'Syria',
                ], [
                    'tid' => 74,
                    'uuid' => '6350f078-05a9-431a-a9a0-d84d43142db1',
                    'name' => 'Togo',
                ], [
                    'tid' => 75,
                    'uuid' => '4f3c172e-89cb-4275-93db-43c086ce27da',
                    'name' => 'Uganda',
                ], [
                    'tid' => 76,
                    'uuid' => '6f45563b-f610-4270-b064-a8bc25922030',
                    'name' => 'United Arab Emirates',
                ], [
                    'tid' => 77,
                    'uuid' => 'a8e3d320-79d2-414e-a320-d7e5f4eb5494',
                    'name' => 'Zambia',
                ], [
                    'tid' => 78,
                    'uuid' => '2075c9ac-023b-427a-8a45-756423c9a807',
                    'name' => 'Zimbabwe',
                ],
            ],
        ], [
            'name' => 'Asia-Pacific',
            'tid' => 79,
            'uuid' => '33999c90-06e2-49bd-afbf-363d0d67fee5',
            'children' => [
                [
                    'tid' => 80,
                    'uuid' => '994d53a9-4860-494b-ba23-63c7e12a88d8',
                    'name' => 'Australia',
                ], [
                    'tid' => 81,
                    'uuid' => '37e11a2f-b5f1-47b8-b7aa-6face86f21a7',
                    'name' => 'Cambodia',
                ], [
                    'tid' => 82,
                    'uuid' => '3db18905-8c50-42dc-a539-cc8dae7c646f',
                    'name' => 'China',
                ], [
                    'tid' => 83,
                    'uuid' => '4f5e0dba-0919-4174-a6a2-12b3157d4918',
                    'name' => 'Hong Kong',
                ], [
                    'tid' => 84,
                    'uuid' => 'e1802922-5de7-441b-aab1-4ae80372ccd5',
                    'name' => 'India',
                ], [
                    'tid' => 85,
                    'uuid' => '2331454d-344a-4980-a552-e31c10cddce4',
                    'name' => 'Indonesia',
                ], [
                    'tid' => 86,
                    'uuid' => '2b5da688-8c3e-41af-8410-4480704dab78',
                    'name' => 'Japan',
                ], [
                    'tid' => 87,
                    'uuid' => 'c5d64d9e-48b2-435d-a784-fd53709f0864',
                    'name' => 'Kazakhstan',
                ], [
                    'tid' => 88,
                    'uuid' => 'e10a347b-bc6a-4272-b176-03bafde92eb7',
                    'name' => 'Laos',
                ], [
                    'tid' => 89,
                    'uuid' => 'f7a9489f-7ae4-4f60-8c5e-e14f5d0c2063',
                    'name' => 'Malaysia',
                ], [
                    'tid' => 90,
                    'uuid' => '47f42c21-eb62-410a-9719-44f8c34f8774',
                    'name' => 'Myanmar',
                ], [
                    'tid' => 91,
                    'uuid' => 'e80c8490-5830-4a24-a8dc-5b5e14f136d4',
                    'name' => 'New Zealand',
                ], [
                    'tid' => 92,
                    'uuid' => '77e6844c-5495-4f40-96eb-6d9a147b5392',
                    'name' => 'Pakistan',
                ], [
                    'tid' => 93,
                    'uuid' => '9593c58e-6af8-4b6b-a916-001d9cf75c2a',
                    'name' => 'Philippines',
                ], [
                    'tid' => 94,
                    'uuid' => 'bf182de7-b140-4092-a96a-c75cc32d19bf',
                    'name' => 'Russian Federation',
                ], [
                    'tid' => 95,
                    'uuid' => 'f5d75b1f-4ade-42a7-b80e-0f05644440a8',
                    'name' => 'Singapore',
                ], [
                    'tid' => 96,
                    'uuid' => '3223dcf3-2cc8-4b10-aaf4-760c4be1cb29',
                    'name' => 'South Korea',
                ], [
                    'tid' => 96,
                    'uuid' => '3223dcf3-2cc8-4b10-aaf4-760c4be1cb29',
                    'name' => 'South Korea',
                ], [
                    'tid' => 96,
                    'uuid' => '3223dcf3-2cc8-4b10-aaf4-760c4be1cb29',
                    'name' => 'South Korea',
                ], [
                    'tid' => 97,
                    'uuid' => '0cda7583-393a-43a3-8227-7229afe37e0d',
                    'name' => 'Sri Lanka',
                ], [
                    'tid' => 98,
                    'uuid' => '01b98a30-fed7-4b83-8bd2-819fd2d3f47f',
                    'name' => 'Taiwan',
                ], [
                    'tid' => 99,
                    'uuid' => '4f5d7bf2-34ab-4d5c-b426-da52862db280',
                    'name' => 'Tanzania, United Republic of',
                ], [
                    'tid' => 100,
                    'uuid' => '797f7d34-9762-46f2-afd7-ef4c4c127dc7',
                    'name' => 'Thailand',
                ], [
                    'tid' => 101,
                    'uuid' => 'a41f732a-e0f2-4a53-9b93-fe0a196014cd',
                    'name' => 'Vietnam',
                ],
            ]
        ], [
            'name' => 'Europe',
            'tid' => 102,
            'uuid' => '4ec98b18-0432-4775-b9f2-cbb0fdbb51a5',
            'children' => [
                [
                    'tid' => 103,
                    'uuid' => '5b3b2d91-dedb-4361-9ac6-691b93e9e226',
                    'name' => 'Albania',
                ], [
                    'tid' => 104,
                    'uuid' => 'da324d37-f7df-43c3-9e5d-33a3a3698fb1',
                    'name' => 'Austria',
                ], [
                    'tid' => 105,
                    'uuid' => 'f9afcc11-8bac-4077-9fb2-cde97fd8e650',
                    'name' => 'Belarus',
                ], [
                    'tid' => 106,
                    'uuid' => '21dcd710-54b9-4de0-a315-abc143477b6a',
                    'name' => 'Belgium',
                ], [
                    'tid' => 107,
                    'uuid' => '5dedd16a-b3ee-4b86-8170-25d15f79b5fb',
                    'name' => 'Bosnia and Herzegovina',
                ], [
                    'tid' => 108,
                    'uuid' => 'b2e08a39-8591-4083-9869-b0a0d2690b8a',
                    'name' => 'Bulgaria',
                ], [
                    'tid' => 109,
                    'uuid' => '1c8690f3-c902-44cf-bf9c-31c8daf375c6',
                    'name' => 'Croatia',
                ], [
                    'tid' => 110,
                    'uuid' => '099f0917-5bf9-4fa1-b113-40e2f7885dce',
                    'name' => 'Cyprus',
                ], [
                    'tid' => 111,
                    'uuid' => 'ebb36997-e8c4-4af1-b60f-055675167a70',
                    'name' => 'Czech Republic',
                ], [
                    'tid' => 112,
                    'uuid' => '3a07f0fb-e91c-4d3e-a030-e5a4403b70da',
                    'name' => 'Denmark',
                ], [
                    'tid' => 113,
                    'uuid' => '499b341b-13b8-4193-b00e-6b7187b0584e',
                    'name' => 'Estonia',
                ], [
                    'tid' => 114,
                    'uuid' => '425a9d7f-bdd8-4319-b468-2628c5543c10',
                    'name' => 'European Union',
                ], [
                    'tid' => 115,
                    'uuid' => '2b22de55-6c5f-44dd-966f-73add8322528',
                    'name' => 'Finland',
                ], [
                    'tid' => 116,
                    'uuid' => 'd845377a-5e18-4774-a23f-57cb3e3cdeca',
                    'name' => 'France',
                ], [
                    'tid' => 117,
                    'uuid' => 'e6af6d7a-34c8-418d-931a-e0e86241a1ad',
                    'name' => 'Germany',
                ], [
                    'tid' => 118,
                    'uuid' => 'db3af01f-125b-4e3a-a576-2d29a6458275',
                    'name' => 'Greece',
                ], [
                    'tid' => 119,
                    'uuid' => 'd027f57b-f2e9-42da-a04a-01259dfcf2ea',
                    'name' => 'Iceland',
                ], [
                    'tid' => 120,
                    'uuid' => '60b6088b-d32f-4baa-96a9-de910db91385',
                    'name' => 'Ireland',
                ], [
                    'tid' => 121,
                    'uuid' => 'b5fad71b-2a87-4de9-959f-5fef700b8968',
                    'name' => 'Italy',
                ], [
                    'tid' => 122,
                    'uuid' => 'a9c36e3f-45d5-407b-a73a-07af27bdf72b',
                    'name' => 'Kosovo',
                ], [
                    'tid' => 123,
                    'uuid' => '64f8e404-61d5-456f-9bc8-473f99f59655',
                    'name' => 'Latvia',
                ], [
                    'tid' => 124,
                    'uuid' => 'f77551af-32b2-40cb-b50a-86c9e580ad9b',
                    'name' => 'Macedonia, the former Yugoslav Republic of',
                ], [
                    'tid' => 125,
                    'uuid' => '93d8867b-d89e-4714-9116-8ffb6dcb6542',
                    'name' => 'Montenegro',
                ], [
                    'tid' => 126,
                    'uuid' => '52d8dfb2-40b6-4870-8084-5c85774db0ab',
                    'name' => 'Netherlands',
                ], [
                    'tid' => 127,
                    'uuid' => 'c0bfd73f-cac2-40b3-a598-7088f80f3cd5',
                    'name' => 'Norway',
                ], [
                    'tid' => 128,
                    'uuid' => 'b0a3bf6c-4203-473c-9756-c18d8a9557a0',
                    'name' => 'Poland',
                ], [
                    'tid' => 129,
                    'uuid' => 'af59d5d5-f89b-4a0a-8b80-020351517bb0',
                    'name' => 'Portugal',
                ], [
                    'tid' => 130,
                    'uuid' => 'ec5a8935-d758-4f0a-838d-a6c59d3344ce',
                    'name' => 'Romania',
                ], [
                    'tid' => 131,
                    'uuid' => '5f0ce326-bffa-4050-af50-345ee145f4e8',
                    'name' => 'Serbia',
                ], [
                    'tid' => 132,
                    'uuid' => 'a37b898e-cc8f-45bb-8a3c-10610570026c',
                    'name' => 'Serbia and Montenegro',
                ], [
                    'tid' => 133,
                    'uuid' => '7ac398ee-897a-4556-9610-010625a10118',
                    'name' => 'Slovenia',
                ], [
                    'tid' => 134,
                    'uuid' => 'ce966a43-ba0e-4742-a8d4-013de26595a8',
                    'name' => 'Spain',
                ], [
                    'tid' => 135,
                    'uuid' => 'cad52430-7791-4c66-a2de-741791dbf00e',
                    'name' => 'Sweden',
                ], [
                    'tid' => 136,
                    'uuid' => 'f47e5835-770e-4bc6-b198-231bed22224c',
                    'name' => 'Switzerland',
                ], [
                    'tid' => 137,
                    'uuid' => '684be255-00d4-4fff-a5cd-ba2b11e6d1db',
                    'name' => 'Turkey',
                ], [
                    'tid' => 138,
                    'uuid' => 'e79dcc33-61f8-4917-ad2f-c5a5a5e29be5',
                    'name' => 'Ukraine',
                ], [
                    'tid' => 139,
                    'uuid' => '0ebd89fa-87a1-4e13-91c1-ca8dfb4f054f',
                    'name' => 'United Kingdom',
                ],
            ],
        ], [
            'name' => 'Latin America & Caribbean',
            'tid' => 140,
            'uuid' => 'e32897a8-c391-44be-ad01-65d578612990',
            'children' => [
                [
                    'tid' => 141,
                    'uuid' => '8823f9a2-5bb4-445f-af8a-313712083ead',
                    'name' => 'Argentina',
                ], [
                    'tid' => 142,
                    'uuid' => 'd93c8767-498b-49ae-bd28-b7d769a74202',
                    'name' => 'Bolivia',
                ], [
                    'tid' => 143,
                    'uuid' => 'f2db4d5c-2005-4a29-9204-5f35be78eb89',
                    'name' => 'Brazil',
                ], [
                    'tid' => 144,
                    'uuid' => 'e1fecee1-71d2-4286-8a3b-3fb49c5d23c3',
                    'name' => 'Caribbean',
                ], [
                    'tid' => 145,
                    'uuid' => '14448416-fd25-4834-ac3e-377314188d4a',
                    'name' => 'Cayman Islands',
                ], [
                    'tid' => 146,
                    'uuid' => '754b1fa1-d119-41a8-90f8-53f196b0ca32',
                    'name' => 'Chile',
                ], [
                    'tid' => 147,
                    'uuid' => '9983cfb9-e639-4ebe-b6b8-f01feced8b17',
                    'name' => 'Colombia',
                ], [
                    'tid' => 148,
                    'uuid' => '93a12106-f479-458d-b0fa-53bac337f52f',
                    'name' => 'Costa Rica',
                ], [
                    'tid' => 149,
                    'uuid' => 'e665b156-642b-4c44-a949-134596b946a6',
                    'name' => 'Cuba',
                ], [
                    'tid' => 150,
                    'uuid' => '96f1e6d4-ecaf-4d4e-8ee6-4cc66ff4c077',
                    'name' => 'Ecuador',
                ], [
                    'tid' => 151,
                    'uuid' => '06a96cb3-77df-4612-977a-5df939997aa6',
                    'name' => 'El Salvador',
                ], [
                    'tid' => 152,
                    'uuid' => '0222d8b2-18b9-404f-9c64-76ce86418241',
                    'name' => 'Mexico',
                ], [
                    'tid' => 153,
                    'uuid' => 'fb5443e3-158b-47a4-9d17-ca73ffd94075',
                    'name' => 'Peru',
                ], [
                    'tid' => 154,
                    'uuid' => 'c7cf6bc1-6028-4303-b883-3fe91c28fba5',
                    'name' => 'Venezuela',
                ],
            ],
        ], [
            'name' => 'North America',
            'tid' => 155,
            'uuid' => '7b644b93-4b86-459b-9731-aaebfefe5c14',
            'children' => [
                [
                    'tid' => 156,
                    'uuid' => 'da7ab6da-3dfe-4a93-9bf1-a55535534505',
                    'name' => 'Canada',
                ], [
                    'tid' => 157,
                    'uuid' => 'ee03c6bf-b332-4f95-83b5-5a95063c97ea',
                    'name' => 'United States of America',
                ],
            ],
        ],
    ];

    public function handle()
    {
        foreach ($this->regions as $region) {
            $region_model = Import::firstByUuidOrFail($region['uuid'])->importable;

            foreach ($region['children'] as $child_region) {
                $subregion_model = Import::firstByUuidOrFail($child_region['uuid'])->importable;
                $subregion_model->parent_id = $region_model->id;
                $subregion_model->save();
            }
        }
    }
}
