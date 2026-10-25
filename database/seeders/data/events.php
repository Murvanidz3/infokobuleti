<?php

use Illuminate\Support\Carbon;

/**
 * 8 demo events. Dates are relative to the seeding day so most stay upcoming; one is a past
 * festival to show the archive. "place" refers to a guide place by its English title; events
 * without one sit in "location" (default: the town centre).
 *
 * @return list<array<string, mixed>>
 */
return [
    [
        'category' => 'concerts', 'starts' => fn () => now()->addDays(6)->setTime(20, 0), 'hours' => 3, 'free' => false, 'price' => 30, 'place' => null,
        'title' => ['ka' => 'შემოდგომის ჯაზის საღამო', 'en' => 'Autumn Jazz Evening', 'ru' => 'Осенний джазовый вечер'],
        'description' => ['ka' => 'ბათუმელი ჯაზ-კვარტეტის კონცერტი და ღვინის დეგუსტაცია.', 'en' => 'A Batumi jazz quartet in concert, with a wine tasting.', 'ru' => 'Концерт батумского джаз-квартета и дегустация вина.'],
        'venue' => ['ka' => 'ქობულეთი პალასის საკონფერენციო დარბაზი', 'en' => 'Kobuleti Palace conference hall', 'ru' => 'Конференц-зал «Кобулети Палас»'],
        'organizer' => 'Kobuleti Palace',
    ],
    [
        'category' => 'festivals', 'starts' => fn () => now()->addDays(20)->setTime(11, 0), 'hours' => 8, 'free' => true, 'price' => null, 'place' => 'Central Park',
        'title' => ['ka' => 'მანდარინობა', 'en' => 'Mandarin Festival', 'ru' => 'Праздник мандаринов'],
        'description' => ['ka' => 'ციტრუსის მოსავლის დღესასწაული: ბაზრობა, ფოლკლორი და საბავშვო გართობა.', 'en' => 'A citrus harvest festival with a market, folk music and fun for kids.', 'ru' => 'Праздник урожая цитрусовых: ярмарка, фольклор и развлечения для детей.'],
        'venue' => ['ka' => 'ცენტრალური პარკი', 'en' => 'Central Park', 'ru' => 'Центральный парк'],
        'organizer' => 'Kobuleti Municipality',
    ],
    [
        'category' => 'sport', 'starts' => fn () => now()->addDays(14)->setTime(9, 0), 'hours' => 4, 'free' => true, 'price' => null, 'place' => 'Seaside Boulevard',
        'title' => ['ka' => 'ქობულეთის შემოდგომის ნახევარმარათონი', 'en' => 'Kobuleti Autumn Half Marathon', 'ru' => 'Осенний полумарафон Кобулети'],
        'description' => ['ka' => '21 კმ ბულვარის გასწვრივ, ასევე 5 კმ-იანი სარბენი მოყვარულებისთვის.', 'en' => '21 km along the boulevard, plus a 5 km fun run for amateurs.', 'ru' => '21 км вдоль бульвара и забег на 5 км для любителей.'],
        'venue' => ['ka' => 'ზღვისპირა ბულვარი, სტარტი ცენტრში', 'en' => 'Seaside Boulevard, start in the centre', 'ru' => 'Приморский бульвар, старт в центре'],
        'organizer' => 'Adjara Running Club',
    ],
    [
        'category' => 'exhibitions', 'starts' => fn () => now()->subDays(2)->setTime(10, 0), 'days' => 40, 'free' => false, 'price' => 5, 'place' => 'Kobuleti Museum of Local History',
        'title' => ['ka' => 'ფოტოგამოფენა „ძველი ქობულეთი“', 'en' => 'Photo exhibition “Old Kobuleti”', 'ru' => 'Фотовыставка «Старый Кобулети»'],
        'description' => ['ka' => 'XIX-XX საუკუნეების ფოტოები კურორტის ისტორიიდან.', 'en' => 'Photos from the history of the resort, 19th to 20th century.', 'ru' => 'Фотографии из истории курорта XIX–XX веков.'],
        'venue' => ['ka' => 'მხარეთმცოდნეობის მუზეუმი', 'en' => 'Museum of Local History', 'ru' => 'Краеведческий музей'],
        'organizer' => 'Kobuleti Museum',
    ],
    [
        'category' => 'kids', 'starts' => fn () => now()->addDays(9)->setTime(12, 0), 'hours' => 2, 'free' => false, 'price' => 10, 'place' => null,
        'title' => ['ka' => 'თოჯინების თეატრი: „ნაცარქექია“', 'en' => 'Puppet theatre: “Natsarkekia”', 'ru' => 'Кукольный театр: «Нацаркекия»'],
        'description' => ['ka' => 'ქართული ზღაპრის დადგმა 4-დან 10 წლამდე ბავშვებისთვის.', 'en' => 'A Georgian fairy tale on stage for children aged 4 to 10.', 'ru' => 'Постановка грузинской сказки для детей от 4 до 10 лет.'],
        'venue' => ['ka' => 'კულტურის ცენტრი', 'en' => 'Culture Centre', 'ru' => 'Культурный центр'],
        'organizer' => 'Batumi Puppet Theatre',
    ],
    [
        'category' => 'festivals', 'starts' => fn () => now()->endOfYear()->setTime(22, 0), 'hours' => 4, 'free' => true, 'price' => null, 'place' => 'Seaside Boulevard',
        'title' => ['ka' => 'საახალწლო ღამე ბულვარზე', 'en' => 'New Year’s Eve on the boulevard', 'ru' => 'Новогодняя ночь на бульваре'],
        'description' => ['ka' => 'კონცერტი, ფეიერვერკი შუაღამისას და საახალწლო ბაზრობა.', 'en' => 'A concert, fireworks at midnight and a holiday market.', 'ru' => 'Концерт, фейерверк в полночь и новогодняя ярмарка.'],
        'venue' => ['ka' => 'ცენტრალური მოედანი ბულვარზე', 'en' => 'Main square on the boulevard', 'ru' => 'Главная площадь на бульваре'],
        'organizer' => 'Kobuleti Municipality',
    ],
    [
        'category' => 'festivals', 'starts' => fn () => Carbon::create(now()->year + 1, 5, 16, 11), 'hours' => 7, 'free' => true, 'price' => null, 'place' => 'Chakvi Tea House',
        'title' => ['ka' => 'ჩაის ფესტივალი ჩაქვში', 'en' => 'Chakvi Tea Festival', 'ru' => 'Фестиваль чая в Чакви'],
        'description' => ['ka' => 'ადგილობრივი ჩაის დეგუსტაცია, მასტერკლასები და ექსკურსია პლანტაციებზე.', 'en' => 'Local tea tastings, workshops and a tour of the plantations.', 'ru' => 'Дегустация местного чая, мастер-классы и экскурсия по плантациям.'],
        'venue' => ['ka' => 'ჩაქვის ჩაის პლანტაციები', 'en' => 'Chakvi tea plantations', 'ru' => 'Чайные плантации Чакви'],
        'organizer' => 'Chakvi Tea Growers',
    ],
    [
        'category' => 'festivals', 'starts' => fn () => Carbon::create(now()->year, 9, 20, 10), 'hours' => 9, 'free' => true, 'price' => null, 'place' => null, 'location' => 'chakvistavi',
        'title' => ['ka' => 'ჩაქვისთავობა', 'en' => 'Chakvistavoba', 'ru' => 'Чаквиставоба'],
        'description' => ['ka' => 'ტრადიციული მთის დღესასწაული სიმღერით, ჭიდაობით და ცხენოსნობით.', 'en' => 'A traditional mountain festival with songs, wrestling and horse riding.', 'ru' => 'Традиционный горный праздник с песнями, борьбой и скачками.'],
        'venue' => ['ka' => 'სოფელი ჩაქვისთავი', 'en' => 'Chakvistavi village', 'ru' => 'Село Чаквистави'],
        'organizer' => 'Kobuleti Municipality',
    ],
];
