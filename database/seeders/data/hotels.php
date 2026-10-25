<?php

/**
 * 30 places to stay. "business" ones get an owner with a verified business profile; the rest are
 * family guesthouses and flats run by individuals.
 *
 * @return list<array{name: array{ka: string, en: string, ru: string}, type: string, stars: int|null, sea: int, location: string, rooms: int, business: bool}>
 */
return [
    ['name' => ['ka' => 'სასტუმრო „ოქროს ქვიშა“', 'en' => 'Golden Sand Hotel', 'ru' => 'Отель «Золотой песок»'], 'type' => 'hotel', 'stars' => 4, 'sea' => 50, 'location' => 'kobuleti-centre', 'rooms' => 42, 'business' => true],
    ['name' => ['ka' => 'ქობულეთი პალასი', 'en' => 'Kobuleti Palace', 'ru' => 'Кобулети Палас'], 'type' => 'hotel', 'stars' => 5, 'sea' => 30, 'location' => 'north-kobuleti', 'rooms' => 96, 'business' => true],
    ['name' => ['ka' => 'საოჯახო სასტუმრო „ნინო“', 'en' => 'Nino Guesthouse', 'ru' => 'Гостевой дом «Нино»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 150, 'location' => 'kobuleti-centre', 'rooms' => 8, 'business' => false],
    ['name' => ['ka' => 'ბლუ ბულვარი ბუთიკ ჰოტელი', 'en' => 'Blue Boulevard Boutique Hotel', 'ru' => 'Бутик-отель «Блу Бульвар»'], 'type' => 'hotel', 'stars' => 4, 'sea' => 80, 'location' => 'kobuleti-centre', 'rooms' => 18, 'business' => true],
    ['name' => ['ka' => 'გესტჰაუსი „ფიჭვნარი“', 'en' => 'Pichvnari Guesthouse', 'ru' => 'Гостевой дом «Пичвнари»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 200, 'location' => 'pichvnari', 'rooms' => 6, 'business' => false],
    ['name' => ['ka' => 'ციხისძირი ვილა რეზორტი', 'en' => 'Tsikhisdziri Villa Resort', 'ru' => 'Цихисдзири Вилла Резорт'], 'type' => 'hotel', 'stars' => 4, 'sea' => 150, 'location' => 'tsikhisdziri', 'rooms' => 24, 'business' => true],
    ['name' => ['ka' => 'სასტუმრო „ზღვის ბრიზი“', 'en' => 'Sea Breeze Hotel', 'ru' => 'Отель «Морской бриз»'], 'type' => 'hotel', 'stars' => 3, 'sea' => 120, 'location' => 'kobuleti-centre', 'rooms' => 30, 'business' => true],
    ['name' => ['ka' => 'სასტუმრო „კოლხეთი“', 'en' => 'Kolkheti Hotel', 'ru' => 'Отель «Колхети»'], 'type' => 'hotel', 'stars' => 3, 'sea' => 200, 'location' => 'south-kobuleti', 'rooms' => 36, 'business' => true],
    ['name' => ['ka' => 'ზურმუხტის სანაპირო', 'en' => 'Emerald Coast Hotel', 'ru' => 'Отель «Изумрудный берег»'], 'type' => 'hotel', 'stars' => 4, 'sea' => 40, 'location' => 'north-kobuleti', 'rooms' => 54, 'business' => true],
    ['name' => ['ka' => 'სასტუმრო „ლაზური“', 'en' => 'Lazuri Hotel', 'ru' => 'Отель «Лазури»'], 'type' => 'hotel', 'stars' => 3, 'sea' => 300, 'location' => 'kobuleti-centre', 'rooms' => 22, 'business' => true],
    ['name' => ['ka' => 'ფიჭვნარი რეზორტი', 'en' => 'Pichvnari Resort', 'ru' => 'Пичвнари Резорт'], 'type' => 'hotel', 'stars' => 4, 'sea' => 100, 'location' => 'pichvnari', 'rooms' => 60, 'business' => true],
    ['name' => ['ka' => 'სასტუმრო „თეთრი აფრა“', 'en' => 'White Sail Hotel', 'ru' => 'Отель «Белый парус»'], 'type' => 'hotel', 'stars' => 3, 'sea' => 90, 'location' => 'north-kobuleti', 'rooms' => 28, 'business' => true],
    ['name' => ['ka' => 'ჩაქვი ბიჩ ჰოტელი', 'en' => 'Chakvi Beach Hotel', 'ru' => 'Чакви Бич Отель'], 'type' => 'hotel', 'stars' => 3, 'sea' => 60, 'location' => 'chakvi', 'rooms' => 20, 'business' => true],
    ['name' => ['ka' => 'სასტუმრო „აჭარა“', 'en' => 'Adjara Hotel', 'ru' => 'Отель «Аджария»'], 'type' => 'hotel', 'stars' => 2, 'sea' => 450, 'location' => 'kobuleti-centre', 'rooms' => 40, 'business' => true],
    ['name' => ['ka' => 'საოჯახო სასტუმრო „მზიური“', 'en' => 'Mziuri Guesthouse', 'ru' => 'Гостевой дом «Мзиури»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 250, 'location' => 'south-kobuleti', 'rooms' => 7, 'business' => false],
    ['name' => ['ka' => 'გესტჰაუსი „ციტრუსი“', 'en' => 'Citrus Guesthouse', 'ru' => 'Гостевой дом «Цитрус»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 300, 'location' => 'kobuleti-centre', 'rooms' => 9, 'business' => false],
    ['name' => ['ka' => 'საოჯახო სასტუმრო „ბებოს სახლი“', 'en' => "Granny's House Guesthouse", 'ru' => 'Гостевой дом «Бабушкин дом»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 500, 'location' => 'south-kobuleti', 'rooms' => 5, 'business' => false],
    ['name' => ['ka' => 'გესტჰაუსი „ფიჭვის ჩრდილი“', 'en' => 'Pine Shade Guesthouse', 'ru' => 'Гостевой дом «Тень сосен»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 120, 'location' => 'north-kobuleti', 'rooms' => 10, 'business' => false],
    ['name' => ['ka' => 'საოჯახო სასტუმრო „თამარი“', 'en' => 'Tamari Guesthouse', 'ru' => 'Гостевой дом «Тамари»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 350, 'location' => 'kobuleti-centre', 'rooms' => 6, 'business' => false],
    ['name' => ['ka' => 'გესტჰაუსი „ჩაის ბაღი“', 'en' => 'Tea Garden Guesthouse', 'ru' => 'Гостевой дом «Чайный сад»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 1800, 'location' => 'chaisubani', 'rooms' => 5, 'business' => false],
    ['name' => ['ka' => 'საოჯახო სასტუმრო „ლიმონი“', 'en' => 'Lemon Guesthouse', 'ru' => 'Гостевой дом «Лимон»'], 'type' => 'guesthouse', 'stars' => null, 'sea' => 400, 'location' => 'tsikhisdziri', 'rooms' => 6, 'business' => false],
    ['name' => ['ka' => 'აპარტამენტები „სი ვიუ“', 'en' => 'Sea View Apartments', 'ru' => 'Апартаменты «Си Вью»'], 'type' => 'apartment', 'stars' => null, 'sea' => 60, 'location' => 'north-kobuleti', 'rooms' => 12, 'business' => false],
    ['name' => ['ka' => 'ბულვარის აპარტამენტები', 'en' => 'Boulevard Apartments', 'ru' => 'Апартаменты у бульвара'], 'type' => 'apartment', 'stars' => null, 'sea' => 100, 'location' => 'kobuleti-centre', 'rooms' => 8, 'business' => false],
    ['name' => ['ka' => 'აპარტამენტები „ოქროს ნავსადგური“', 'en' => 'Golden Harbour Apartments', 'ru' => 'Апартаменты «Золотая гавань»'], 'type' => 'apartment', 'stars' => null, 'sea' => 150, 'location' => 'north-kobuleti', 'rooms' => 10, 'business' => false],
    ['name' => ['ka' => 'კოტეჯები „ციხისძირის ბაღი“', 'en' => 'Tsikhisdziri Garden Cottages', 'ru' => 'Коттеджи «Сад Цихисдзири»'], 'type' => 'cottage', 'stars' => null, 'sea' => 350, 'location' => 'tsikhisdziri', 'rooms' => 4, 'business' => false],
    ['name' => ['ka' => 'კოტეჯები „ბობოყვათი“', 'en' => 'Bobokvati Cottages', 'ru' => 'Коттеджи «Бобоквати»'], 'type' => 'cottage', 'stars' => null, 'sea' => 600, 'location' => 'bobokvati', 'rooms' => 3, 'business' => false],
    ['name' => ['ka' => 'მთის კოტეჯი „კინტრიში“', 'en' => 'Kintrishi Mountain Cottage', 'ru' => 'Горный коттедж «Кинтриши»'], 'type' => 'cottage', 'stars' => null, 'sea' => 21000, 'location' => 'kintrishi-protected-areas', 'rooms' => 2, 'business' => false],
    ['name' => ['ka' => 'ჰოსტელი „ზღვის ფანჯარა“', 'en' => 'Sea Window Hostel', 'ru' => 'Хостел «Морское окно»'], 'type' => 'hostel', 'stars' => null, 'sea' => 200, 'location' => 'kobuleti-centre', 'rooms' => 6, 'business' => false],
    ['name' => ['ka' => 'ქობულეთი ბექფექერს ჰოსტელი', 'en' => 'Kobuleti Backpackers Hostel', 'ru' => 'Хостел «Кобулети Бэкпэкерс»'], 'type' => 'hostel', 'stars' => null, 'sea' => 350, 'location' => 'kobuleti-centre', 'rooms' => 5, 'business' => false],
    ['name' => ['ka' => 'ჰოსტელი „ტალღა“', 'en' => 'Wave Hostel', 'ru' => 'Хостел «Волна»'], 'type' => 'hostel', 'stars' => null, 'sea' => 150, 'location' => 'south-kobuleti', 'rooms' => 4, 'business' => false],
];
