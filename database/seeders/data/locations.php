<?php

/**
 * Kobuleti municipality: the town with its districts, boroughs, villages and protected areas.
 * Coordinates are approximate centres, good enough for maps and "nearby" sorting.
 *
 * @return list<array{slug: string, type: string, name: array{ka: string, en: string, ru: string}, lat: float, lng: float, children?: list<array<string, mixed>>}>
 */
return [
    [
        'slug' => 'kobuleti-municipality',
        'type' => 'municipality',
        'name' => ['ka' => 'ქობულეთის მუნიციპალიტეტი', 'en' => 'Kobuleti Municipality', 'ru' => 'Кобулетский муниципалитет'],
        'lat' => 41.8200, 'lng' => 41.8000,
        'children' => [
            [
                'slug' => 'kobuleti',
                'type' => 'town',
                'name' => ['ka' => 'ქობულეთი', 'en' => 'Kobuleti', 'ru' => 'Кобулети'],
                'lat' => 41.8200, 'lng' => 41.7753,
                'children' => [
                    ['slug' => 'kobuleti-centre', 'type' => 'district', 'name' => ['ka' => 'ცენტრი', 'en' => 'Centre', 'ru' => 'Центр'], 'lat' => 41.8180, 'lng' => 41.7765],
                    ['slug' => 'north-kobuleti', 'type' => 'district', 'name' => ['ka' => 'ჩრდილოეთ ქობულეთი', 'en' => 'North Kobuleti', 'ru' => 'Северный Кобулети'], 'lat' => 41.8450, 'lng' => 41.7810],
                    ['slug' => 'south-kobuleti', 'type' => 'district', 'name' => ['ka' => 'სამხრეთ ქობულეთი', 'en' => 'South Kobuleti', 'ru' => 'Южный Кобулети'], 'lat' => 41.8000, 'lng' => 41.7740],
                ],
            ],
            ['slug' => 'chakvi', 'type' => 'borough', 'name' => ['ka' => 'ჩაქვი', 'en' => 'Chakvi', 'ru' => 'Чакви'], 'lat' => 41.7230, 'lng' => 41.7330],
            ['slug' => 'ochkhamuri', 'type' => 'borough', 'name' => ['ka' => 'ოჩხამური', 'en' => 'Ochkhamuri', 'ru' => 'Очхамури'], 'lat' => 41.8560, 'lng' => 41.8510],
            ['slug' => 'tsikhisdziri', 'type' => 'village', 'name' => ['ka' => 'ციხისძირი', 'en' => 'Tsikhisdziri', 'ru' => 'Цихисдзири'], 'lat' => 41.7680, 'lng' => 41.7550],
            ['slug' => 'pichvnari', 'type' => 'village', 'name' => ['ka' => 'ფიჭვნარი', 'en' => 'Pichvnari', 'ru' => 'Пичвнари'], 'lat' => 41.8850, 'lng' => 41.7790],
            ['slug' => 'bobokvati', 'type' => 'village', 'name' => ['ka' => 'ბობოყვათი', 'en' => 'Bobokvati', 'ru' => 'Бобоквати'], 'lat' => 41.8650, 'lng' => 41.7930],
            ['slug' => 'buknari', 'type' => 'village', 'name' => ['ka' => 'ბუკნარი', 'en' => 'Buknari', 'ru' => 'Букнари'], 'lat' => 41.7450, 'lng' => 41.7430],
            ['slug' => 'sakhalvasho', 'type' => 'village', 'name' => ['ka' => 'სახალვაშო', 'en' => 'Sakhalvasho', 'ru' => 'Сахалвашо'], 'lat' => 41.7380, 'lng' => 41.7700],
            ['slug' => 'chaisubani', 'type' => 'village', 'name' => ['ka' => 'ჩაისუბანი', 'en' => 'Chaisubani', 'ru' => 'Чаисубани'], 'lat' => 41.7900, 'lng' => 41.7920],
            ['slug' => 'mukhaestate', 'type' => 'village', 'name' => ['ka' => 'მუხაესტატე', 'en' => 'Mukhaestate', 'ru' => 'Мухаэстате'], 'lat' => 41.8070, 'lng' => 41.8010],
            ['slug' => 'khutsubani', 'type' => 'village', 'name' => ['ka' => 'ხუცუბანი', 'en' => 'Khutsubani', 'ru' => 'Хуцубани'], 'lat' => 41.8170, 'lng' => 41.8500],
            ['slug' => 'dagva', 'type' => 'village', 'name' => ['ka' => 'დაგვა', 'en' => 'Dagva', 'ru' => 'Дагва'], 'lat' => 41.8350, 'lng' => 41.8220],
            ['slug' => 'kvirike', 'type' => 'village', 'name' => ['ka' => 'ქვირიკე', 'en' => 'Kvirike', 'ru' => 'Квирике'], 'lat' => 41.8750, 'lng' => 41.8650],
            ['slug' => 'leghva', 'type' => 'village', 'name' => ['ka' => 'ლეღვა', 'en' => 'Leghva', 'ru' => 'Легва'], 'lat' => 41.8450, 'lng' => 41.8900],
            ['slug' => 'alambari', 'type' => 'village', 'name' => ['ka' => 'ალამბარი', 'en' => 'Alambari', 'ru' => 'Аламбари'], 'lat' => 41.8800, 'lng' => 41.8300],
            ['slug' => 'chakvistavi', 'type' => 'village', 'name' => ['ka' => 'ჩაქვისთავი', 'en' => 'Chakvistavi', 'ru' => 'Чаквистави'], 'lat' => 41.6800, 'lng' => 41.8700],
            ['slug' => 'kobuleti-protected-areas', 'type' => 'protected_area', 'name' => ['ka' => 'ქობულეთის დაცული ტერიტორიები', 'en' => 'Kobuleti Protected Areas', 'ru' => 'Кобулетские охраняемые территории'], 'lat' => 41.8650, 'lng' => 41.7850],
            ['slug' => 'kintrishi-protected-areas', 'type' => 'protected_area', 'name' => ['ka' => 'კინტრიშის დაცული ტერიტორიები', 'en' => 'Kintrishi Protected Areas', 'ru' => 'Кинтришские охраняемые территории'], 'lat' => 41.7500, 'lng' => 42.0300],
            ['slug' => 'mtirala-national-park', 'type' => 'protected_area', 'name' => ['ka' => 'მტირალას ეროვნული პარკი', 'en' => 'Mtirala National Park', 'ru' => 'Национальный парк Мтирала'], 'lat' => 41.6750, 'lng' => 41.8650],
        ],
    ],
];
