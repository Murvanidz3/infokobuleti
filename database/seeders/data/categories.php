<?php

/**
 * Category trees per module. Classifieds subcategories carry attribute schemas
 * (see App\Support\AttributeSchema for the field shape).
 */
$label = fn (string $ka, string $en, string $ru): array => ['ka' => $ka, 'en' => $en, 'ru' => $ru];
$option = fn (string $value, string $ka, string $en, string $ru): array => ['value' => $value, 'label' => $label($ka, $en, $ru)];
$brand = fn (string $value, string $name): array => ['value' => $value, 'label' => $label($name, $name, $name)];

$year = ['key' => 'year', 'type' => 'number', 'label' => $label('გამოშვების წელი', 'Year', 'Год выпуска'), 'min' => 1970, 'max' => 2027, 'required' => true, 'filterable' => true];
$mileage = ['key' => 'mileage', 'type' => 'number', 'label' => $label('გარბენი', 'Mileage', 'Пробег'), 'unit' => 'km', 'min' => 0, 'max' => 1000000, 'filterable' => true];

return [
    'classifieds' => [
        [
            'slug' => 'transport', 'icon' => 'car', 'name' => $label('ტრანსპორტი', 'Transport', 'Транспорт'),
            'children' => [
                [
                    'slug' => 'cars', 'name' => $label('მსუბუქი ავტომობილები', 'Cars', 'Легковые автомобили'),
                    'schema' => [
                        ['key' => 'make', 'type' => 'select', 'label' => $label('მწარმოებელი', 'Make', 'Марка'), 'required' => true, 'filterable' => true, 'options' => [
                            $brand('toyota', 'Toyota'), $brand('mercedes-benz', 'Mercedes-Benz'), $brand('bmw', 'BMW'),
                            $brand('hyundai', 'Hyundai'), $brand('ford', 'Ford'), $brand('nissan', 'Nissan'),
                            $brand('opel', 'Opel'), $brand('volkswagen', 'Volkswagen'), $brand('honda', 'Honda'), $brand('lexus', 'Lexus'),
                        ]],
                        ['key' => 'model', 'type' => 'text', 'label' => $label('მოდელი', 'Model', 'Модель'), 'required' => true],
                        $year,
                        $mileage,
                        ['key' => 'fuel', 'type' => 'select', 'label' => $label('საწვავი', 'Fuel', 'Топливо'), 'filterable' => true, 'options' => [
                            $option('petrol', 'ბენზინი', 'Petrol', 'Бензин'), $option('diesel', 'დიზელი', 'Diesel', 'Дизель'),
                            $option('hybrid', 'ჰიბრიდი', 'Hybrid', 'Гибрид'), $option('lpg', 'გაზი', 'LPG', 'Газ'),
                            $option('electric', 'ელექტრო', 'Electric', 'Электро'),
                        ]],
                        ['key' => 'transmission', 'type' => 'select', 'label' => $label('გადაცემათა კოლოფი', 'Transmission', 'Коробка передач'), 'filterable' => true, 'options' => [
                            $option('manual', 'მექანიკა', 'Manual', 'Механика'), $option('automatic', 'ავტომატიკა', 'Automatic', 'Автомат'),
                        ]],
                        ['key' => 'steering', 'type' => 'select', 'label' => $label('საჭე', 'Steering wheel', 'Руль'), 'options' => [
                            $option('left', 'მარცხენა', 'Left', 'Левый'), $option('right', 'მარჯვენა', 'Right', 'Правый'),
                        ]],
                        ['key' => 'customs_cleared', 'type' => 'boolean', 'label' => $label('განბაჟებული', 'Customs cleared', 'Растаможен'), 'filterable' => true],
                    ],
                ],
                [
                    'slug' => 'motorcycles', 'name' => $label('მოტოციკლები და სკუტერები', 'Motorcycles & scooters', 'Мотоциклы и скутеры'),
                    'schema' => [
                        $year,
                        ['key' => 'engine_cc', 'type' => 'number', 'label' => $label('ძრავის მოცულობა', 'Engine size', 'Объём двигателя'), 'unit' => 'cc', 'min' => 49, 'max' => 2500],
                        $mileage,
                    ],
                ],
                [
                    'slug' => 'bicycles', 'name' => $label('ველოსიპედები', 'Bicycles', 'Велосипеды'),
                    'schema' => [
                        ['key' => 'bike_type', 'type' => 'select', 'label' => $label('ტიპი', 'Type', 'Тип'), 'filterable' => true, 'options' => [
                            $option('city', 'საქალაქო', 'City', 'Городской'), $option('mountain', 'სამთო', 'Mountain', 'Горный'),
                            $option('road', 'გზის', 'Road', 'Шоссейный'), $option('kids', 'საბავშვო', 'Kids', 'Детский'),
                            $option('electric', 'ელექტრო', 'Electric', 'Электровелосипед'),
                        ]],
                    ],
                ],
                ['slug' => 'auto-parts', 'name' => $label('ავტონაწილები', 'Auto parts', 'Автозапчасти')],
            ],
        ],
        [
            'slug' => 'electronics', 'icon' => 'phone', 'name' => $label('ელექტრონიკა', 'Electronics', 'Электроника'),
            'children' => [
                [
                    'slug' => 'phones', 'name' => $label('მობილური ტელეფონები', 'Mobile phones', 'Мобильные телефоны'),
                    'schema' => [
                        ['key' => 'brand', 'type' => 'select', 'label' => $label('ბრენდი', 'Brand', 'Бренд'), 'required' => true, 'filterable' => true, 'options' => [
                            $brand('apple', 'Apple'), $brand('samsung', 'Samsung'), $brand('xiaomi', 'Xiaomi'), $brand('google', 'Google'),
                            $option('other', 'სხვა', 'Other', 'Другое'),
                        ]],
                        ['key' => 'storage_gb', 'type' => 'select', 'label' => $label('მეხსიერება', 'Storage', 'Память'), 'filterable' => true, 'options' => [
                            $brand('64', '64 GB'), $brand('128', '128 GB'), $brand('256', '256 GB'), $brand('512', '512 GB'),
                        ]],
                    ],
                ],
                ['slug' => 'computers', 'name' => $label('კომპიუტერები და ლეპტოპები', 'Computers & laptops', 'Компьютеры и ноутбуки')],
                ['slug' => 'tv-audio', 'name' => $label('ტელევიზორები და აუდიო', 'TV & audio', 'Телевизоры и аудио')],
            ],
        ],
        [
            'slug' => 'home-garden', 'icon' => 'sofa', 'name' => $label('სახლი და ბაღი', 'Home & garden', 'Дом и сад'),
            'children' => [
                ['slug' => 'furniture', 'name' => $label('ავეჯი', 'Furniture', 'Мебель')],
                ['slug' => 'appliances', 'name' => $label('საყოფაცხოვრებო ტექნიკა', 'Home appliances', 'Бытовая техника')],
                ['slug' => 'garden-harvest', 'name' => $label('ბაღი და მოსავალი', 'Garden & harvest', 'Сад и урожай')],
            ],
        ],
        ['slug' => 'clothing', 'icon' => 'tag', 'name' => $label('ტანსაცმელი და ფეხსაცმელი', 'Clothing & shoes', 'Одежда и обувь')],
        ['slug' => 'kids', 'icon' => 'users', 'name' => $label('საბავშვო ნივთები', "Kids' goods", 'Детские товары')],
        [
            'slug' => 'services', 'icon' => 'wrench', 'name' => $label('მომსახურება', 'Services', 'Услуги'),
            'children' => [
                ['slug' => 'repair-construction', 'name' => $label('რემონტი და მშენებლობა', 'Repair & construction', 'Ремонт и строительство')],
                ['slug' => 'transfers', 'name' => $label('ტრანსფერი და ტაქსი', 'Transfers & taxi', 'Трансфер и такси')],
                ['slug' => 'cleaning', 'name' => $label('დალაგება', 'Cleaning', 'Уборка')],
                ['slug' => 'tours', 'name' => $label('ტურები და გართობა', 'Tours & activities', 'Туры и развлечения')],
            ],
        ],
        ['slug' => 'hobby-sport', 'icon' => 'bike', 'name' => $label('ჰობი და სპორტი', 'Hobby & sport', 'Хобби и спорт')],
        ['slug' => 'animals', 'icon' => 'heart', 'name' => $label('ცხოველები', 'Animals', 'Животные')],
    ],

    'vacancies' => [
        ['slug' => 'hospitality', 'icon' => 'bed', 'name' => $label('სასტუმრო და ტურიზმი', 'Hotels & tourism', 'Гостиницы и туризм')],
        ['slug' => 'food-service', 'icon' => 'fork', 'name' => $label('რესტორანი და კაფე', 'Restaurants & cafes', 'Рестораны и кафе')],
        ['slug' => 'retail', 'icon' => 'tag', 'name' => $label('ვაჭრობა და გაყიდვები', 'Retail & sales', 'Торговля и продажи')],
        ['slug' => 'construction', 'icon' => 'wrench', 'name' => $label('მშენებლობა და რემონტი', 'Construction & repair', 'Строительство и ремонт')],
        ['slug' => 'transport-logistics', 'icon' => 'car', 'name' => $label('ტრანსპორტი და ლოგისტიკა', 'Transport & logistics', 'Транспорт и логистика')],
        ['slug' => 'education', 'icon' => 'users', 'name' => $label('განათლება', 'Education', 'Образование')],
        ['slug' => 'healthcare', 'icon' => 'plus', 'name' => $label('ჯანდაცვა', 'Healthcare', 'Здравоохранение')],
        ['slug' => 'office', 'icon' => 'briefcase', 'name' => $label('ოფისი და ადმინისტრირება', 'Office & administration', 'Офис и администрирование')],
        ['slug' => 'it-marketing', 'icon' => 'grid', 'name' => $label('IT და მარკეტინგი', 'IT & marketing', 'IT и маркетинг')],
        ['slug' => 'beauty', 'icon' => 'sun', 'name' => $label('სილამაზე და ჯანმრთელობა', 'Beauty & wellness', 'Красота и здоровье')],
        ['slug' => 'other', 'icon' => 'grid', 'name' => $label('სხვა', 'Other', 'Другое')],
    ],

    'news' => [
        ['slug' => 'city', 'icon' => 'building', 'name' => $label('ქალაქი', 'City', 'Город')],
        ['slug' => 'tourism', 'icon' => 'sun', 'name' => $label('ტურიზმი', 'Tourism', 'Туризм')],
        ['slug' => 'real-estate', 'icon' => 'house', 'name' => $label('უძრავი ქონება', 'Real estate', 'Недвижимость')],
        ['slug' => 'infrastructure', 'icon' => 'wrench', 'name' => $label('ინფრასტრუქტურა', 'Infrastructure', 'Инфраструктура')],
        ['slug' => 'culture', 'icon' => 'calendar', 'name' => $label('კულტურა', 'Culture', 'Культура')],
    ],

    'events' => [
        ['slug' => 'concerts', 'icon' => 'calendar', 'name' => $label('კონცერტები', 'Concerts', 'Концерты')],
        ['slug' => 'festivals', 'icon' => 'sun', 'name' => $label('ფესტივალები', 'Festivals', 'Фестивали')],
        ['slug' => 'sport', 'icon' => 'bike', 'name' => $label('სპორტი', 'Sport', 'Спорт')],
        ['slug' => 'kids', 'icon' => 'users', 'name' => $label('საბავშვო', 'For kids', 'Для детей')],
        ['slug' => 'exhibitions', 'icon' => 'image', 'name' => $label('გამოფენები', 'Exhibitions', 'Выставки')],
    ],
];
