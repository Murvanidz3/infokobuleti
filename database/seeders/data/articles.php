<?php

/**
 * 12 demo news articles. "days" is how long ago the article was published.
 *
 * @return list<array{category: string, days: int, featured: bool, title: array<string, string>, excerpt: array<string, string>, body: array<string, string>}>
 */
return [
    [
        'category' => 'city', 'days' => 1, 'featured' => true,
        'title' => ['ka' => 'ბულვარზე ახალი ველობილიკი გაიხსნა', 'en' => 'New cycle path opens on the boulevard', 'ru' => 'На бульваре открылась новая велодорожка'],
        'excerpt' => ['ka' => 'ველობილიკი ქალაქის ცენტრს ჩრდილოეთ ქობულეთთან აკავშირებს და მთელ სიგრძეზე განათებულია.', 'en' => 'The path links the town centre with North Kobuleti and is lit along its entire length.', 'ru' => 'Дорожка соединяет центр города с Северным Кобулети и освещена по всей длине.'],
        'body' => ['ka' => '<p>ახალი ველობილიკი ფეხით მოსიარულეთა ზონისგან გამოყოფილია და მას ველოსიპედების გაქირავების რამდენიმე წერტილი ემსახურება.</p><p>მუნიციპალიტეტის ცნობით, მომავალ წელს ბილიკი ციხისძირამდე გაგრძელდება.</p>', 'en' => '<p>The new path is separated from the pedestrian area and served by several bike rental points.</p><p>According to the municipality, it will be extended to Tsikhisdziri next year.</p>', 'ru' => '<p>Новая велодорожка отделена от пешеходной зоны, вдоль неё работают несколько пунктов проката.</p><p>По данным муниципалитета, в следующем году дорожку продлят до Цихисдзири.</p>'],
    ],
    [
        'category' => 'tourism', 'days' => 3, 'featured' => true,
        'title' => ['ka' => 'ხავერდოვანი სეზონი: ოქტომბერში ზღვა ჯერ კიდევ თბილია', 'en' => 'Velvet season: the sea is still warm in October', 'ru' => 'Бархатный сезон: в октябре море ещё тёплое'],
        'excerpt' => ['ka' => 'სასტუმროები შემოდგომაზე ფასდაკლებებს სთავაზობენ, ზღვის ტემპერატურა კი 20 გრადუსს აღწევს.', 'en' => 'Hotels offer autumn discounts while the sea stays around 20 degrees.', 'ru' => 'Отели предлагают осенние скидки, а море держится около 20 градусов.'],
        'body' => ['ka' => '<p>შემოდგომაზე ქობულეთში ნაკლები ხალხია, ფასები კი ზაფხულთან შედარებით თითქმის ორჯერ დაბალია.</p><p>ბევრი საოჯახო სასტუმრო ნოემბრის ბოლომდე მუშაობს.</p>', 'en' => '<p>Kobuleti is quieter in autumn and prices are almost half of the summer rates.</p><p>Many family guesthouses stay open until the end of November.</p>', 'ru' => '<p>Осенью в Кобулети меньше людей, а цены почти вдвое ниже летних.</p><p>Многие гостевые дома работают до конца ноября.</p>'],
    ],
    [
        'category' => 'infrastructure', 'days' => 5, 'featured' => false,
        'title' => ['ka' => 'ჩაქვის გზაზე სარეაბილიტაციო სამუშაოები დაიწყო', 'en' => 'Road works begin on the Chakvi road', 'ru' => 'На дороге в Чакви начался ремонт'],
        'excerpt' => ['ka' => 'სამუშაოები ღამით მიმდინარეობს, რომ მოძრაობა ნაკლებად შეფერხდეს.', 'en' => 'Works run at night to keep traffic delays to a minimum.', 'ru' => 'Работы ведутся ночью, чтобы меньше мешать движению.'],
        'body' => ['ka' => '<p>რეაბილიტაცია მოიცავს ასფალტის საფარის შეცვლას და ახალ ტროტუარებს.</p><p>მძღოლებს ურჩევენ, გაითვალისწინონ დროებითი შემოვლითი გზები.</p>', 'en' => '<p>The project covers new asphalt and new pavements.</p><p>Drivers are advised to follow the temporary diversions.</p>', 'ru' => '<p>Ремонт включает замену асфальта и новые тротуары.</p><p>Водителям советуют учитывать временные объезды.</p>'],
    ],
    [
        'category' => 'real-estate', 'days' => 6, 'featured' => false,
        'title' => ['ka' => 'ზღვისპირა ბინების ფასები შემოდგომაზე სტაბილურია', 'en' => 'Seaside apartment prices hold steady this autumn', 'ru' => 'Цены на квартиры у моря осенью стабильны'],
        'excerpt' => ['ka' => 'ახალ კორპუსებში კვადრატული მეტრის ფასი წინა სეზონის დონეზე რჩება.', 'en' => 'Prices per square metre in new buildings remain at last season’s level.', 'ru' => 'Цена квадратного метра в новостройках остаётся на уровне прошлого сезона.'],
        'body' => ['ka' => '<p>აგენტების თქმით, მყიდველების უმეტესობა ბინას ზაფხულში გასაქირავებლად ყიდულობს.</p><p>ყველაზე მოთხოვნადია ერთ და ოროთახიანი ბინები ბულვართან ახლოს.</p>', 'en' => '<p>Agents say most buyers purchase apartments to rent out in summer.</p><p>One- and two-room flats near the boulevard are in highest demand.</p>', 'ru' => '<p>По словам агентов, большинство покупателей берут квартиры под летнюю аренду.</p><p>Самый высокий спрос на одно- и двухкомнатные квартиры у бульвара.</p>'],
    ],
    [
        'category' => 'culture', 'days' => 8, 'featured' => false,
        'title' => ['ka' => 'მუზეუმში ფიჭვნარის ახალი აღმოჩენები გამოიფინა', 'en' => 'New Pichvnari finds go on display at the museum', 'ru' => 'В музее выставили новые находки из Пичвнари'],
        'excerpt' => ['ka' => 'ექსპოზიციაში ამ ზაფხულის გათხრებზე ნაპოვნი კერამიკა და მონეტებია.', 'en' => 'The display includes pottery and coins found during this summer’s dig.', 'ru' => 'В экспозиции керамика и монеты, найденные при раскопках этим летом.'],
        'body' => ['ka' => '<p>არქეოლოგების თქმით, აღმოჩენები ადასტურებს ფიჭვნარის აქტიურ ვაჭრობას ბერძნულ ქალაქებთან.</p><p>გამოფენა წლის ბოლომდე გაგრძელდება.</p>', 'en' => '<p>Archaeologists say the finds confirm active trade between Pichvnari and Greek cities.</p><p>The exhibition runs until the end of the year.</p>', 'ru' => '<p>По словам археологов, находки подтверждают активную торговлю Пичвнари с греческими городами.</p><p>Выставка продлится до конца года.</p>'],
    ],
    [
        'category' => 'tourism', 'days' => 10, 'featured' => false,
        'title' => ['ka' => 'ისპანის ჭაობზე ახალი ხის ბილიკი მოეწყო', 'en' => 'A new boardwalk opens at Ispani Mire', 'ru' => 'На болоте Испани проложили новую деревянную тропу'],
        'excerpt' => ['ka' => 'ბილიკი ვიზიტორებს ჭაობის შუაგულამდე მიიყვანს ბუნების დაზიანების გარეშე.', 'en' => 'The boardwalk takes visitors to the heart of the mire without harming it.', 'ru' => 'Тропа ведёт посетителей в самое сердце болота, не нанося вреда природе.'],
        'body' => ['ka' => '<p>ბილიკზე საინფორმაციო დაფებია სამ ენაზე, მათ შორის იშვიათი მცენარეების აღწერით.</p><p>ექსკურსია დაახლოებით ერთ საათს გრძელდება.</p>', 'en' => '<p>Information boards in three languages describe the rare plants along the way.</p><p>The tour takes about an hour.</p>', 'ru' => '<p>Вдоль тропы стоят информационные стенды на трёх языках с описанием редких растений.</p><p>Экскурсия длится около часа.</p>'],
    ],
    [
        'category' => 'city', 'days' => 12, 'featured' => false,
        'title' => ['ka' => 'ცენტრალურ პარკში საბავშვო მოედნები განახლდა', 'en' => 'Playgrounds renovated in Central Park', 'ru' => 'В Центральном парке обновили детские площадки'],
        'excerpt' => ['ka' => 'მოედნებზე უსაფრთხო საფარი და ახალი ატრაქციონები მოეწყო.', 'en' => 'The playgrounds now have safe surfacing and new equipment.', 'ru' => 'На площадках уложили безопасное покрытие и поставили новые аттракционы.'],
        'body' => ['ka' => '<p>განახლებულ მოედნებზე ადაპტირებული ატრაქციონებიც დამონტაჟდა.</p><p>პარკში დამატებითი განათება და სკამებიც გამოჩნდა.</p>', 'en' => '<p>Accessible play equipment has been installed as well.</p><p>The park also got extra lighting and benches.</p>', 'ru' => '<p>Установлены и адаптированные аттракционы.</p><p>В парке также появились дополнительное освещение и скамейки.</p>'],
    ],
    [
        'category' => 'infrastructure', 'days' => 15, 'featured' => false,
        'title' => ['ka' => 'მატარებლების შემოდგომის განრიგი ძალაშია', 'en' => 'Autumn train timetable now in effect', 'ru' => 'Вступило в силу осеннее расписание поездов'],
        'excerpt' => ['ka' => 'თბილისის მიმართულებით ზაფხულის დამატებითი რეისები გაუქმდა.', 'en' => 'Extra summer services to Tbilisi have been withdrawn.', 'ru' => 'Дополнительные летние рейсы в Тбилиси отменены.'],
        'body' => ['ka' => '<p>ქობულეთის სადგურიდან დღეში ორი მატარებელი გადის თბილისისკენ.</p><p>ბილეთების შეძენა სადგურის სალაროში და ონლაინ შეიძლება.</p>', 'en' => '<p>Two trains a day now leave Kobuleti station for Tbilisi.</p><p>Tickets are sold at the station and online.</p>', 'ru' => '<p>Из Кобулети в Тбилиси теперь отправляются два поезда в день.</p><p>Билеты продаются в кассе вокзала и онлайн.</p>'],
    ],
    [
        'category' => 'tourism', 'days' => 18, 'featured' => false,
        'title' => ['ka' => 'მტირალას პარკში ახალი სალაშქრო მარშრუტი გაიხსნა', 'en' => 'New hiking route opens in Mtirala National Park', 'ru' => 'В парке Мтирала открыли новый треккинговый маршрут'],
        'excerpt' => ['ka' => 'მარშრუტი ჩანჩქერს მთის ხედის წერტილთან აკავშირებს.', 'en' => 'The route links the waterfall with a mountain viewpoint.', 'ru' => 'Маршрут соединяет водопад со смотровой площадкой.'],
        'body' => ['ka' => '<p>ბილიკი საშუალო სირთულისაა და დაახლოებით ოთხ საათს მოითხოვს.</p><p>ვიზიტორთა ცენტრში შეგიძლიათ აიღოთ რუკა და დაიქირაოთ გიდი.</p>', 'en' => '<p>The trail is moderate and takes about four hours.</p><p>Maps and guides are available at the visitor centre.</p>', 'ru' => '<p>Тропа средней сложности, на неё уходит около четырёх часов.</p><p>Карту и гида можно взять в визит-центре.</p>'],
    ],
    [
        'category' => 'city', 'days' => 21, 'featured' => false,
        'title' => ['ka' => 'მანდარინის მოსავლის აღება იწყება', 'en' => 'Mandarin harvest is about to begin', 'ru' => 'Скоро начнётся сбор мандаринов'],
        'excerpt' => ['ka' => 'ფერმერები წელს კარგ მოსავალს ელიან.', 'en' => 'Farmers expect a good crop this year.', 'ru' => 'Фермеры ожидают в этом году хороший урожай.'],
        'body' => ['ka' => '<p>ქობულეთის სოფლებში მანდარინის კრეფა ნოემბრის დასაწყისში დაიწყება.</p><p>სეზონურ მუშებზე მოთხოვნა უკვე გაიზარდა.</p>', 'en' => '<p>Picking in the Kobuleti villages starts in early November.</p><p>Demand for seasonal workers is already up.</p>', 'ru' => '<p>Сбор мандаринов в сёлах Кобулети начнётся в начале ноября.</p><p>Спрос на сезонных работников уже вырос.</p>'],
    ],
    [
        'category' => 'culture', 'days' => 34, 'featured' => false,
        'title' => ['ka' => 'ჩაქვისთავობა: დღესასწაული მთის სოფელში', 'en' => 'Chakvistavoba: a festival in the mountain village', 'ru' => 'Чаквиставоба: праздник в горном селе'],
        'excerpt' => ['ka' => 'ტრადიციულ დღესასწაულზე ხალხური სიმღერები და აჭარული კერძები იყო.', 'en' => 'The traditional festival featured folk songs and Adjarian food.', 'ru' => 'На традиционном празднике звучали народные песни и угощали аджарскими блюдами.'],
        'body' => ['ka' => '<p>ჩაქვისთავობა ყოველ სექტემბერს იმართება და მთელი მუნიციპალიტეტიდან სტუმრებს იკრებს.</p><p>პროგრამაში ჭიდაობა და ცხენოსნობაც იყო.</p>', 'en' => '<p>Chakvistavoba takes place every September and draws guests from the whole municipality.</p><p>The programme included wrestling and horse riding.</p>', 'ru' => '<p>Чаквиставоба проходит каждый сентябрь и собирает гостей со всего муниципалитета.</p><p>В программе были борьба и скачки.</p>'],
    ],
    [
        'category' => 'real-estate', 'days' => 40, 'featured' => false,
        'title' => ['ka' => 'ჩრდილოეთ ქობულეთში ახალი საცხოვრებელი კომპლექსი შენდება', 'en' => 'A new residential complex is going up in North Kobuleti', 'ru' => 'В Северном Кобулети строят новый жилой комплекс'],
        'excerpt' => ['ka' => 'კომპლექსი ზღვიდან 100 მეტრშია და მშენებლობა 2028 წელს დასრულდება.', 'en' => 'The complex is 100 metres from the sea and due for completion in 2028.', 'ru' => 'Комплекс в 100 метрах от моря, сдача запланирована на 2028 год.'],
        'body' => ['ka' => '<p>პროექტი ითვალისწინებს მიწისქვეშა პარკინგს და გამწვანებულ ეზოს.</p><p>ბინების გაყიდვა უკვე დაიწყო მშენებლობის ეტაპზე.</p>', 'en' => '<p>The project includes underground parking and a landscaped courtyard.</p><p>Off-plan sales have already begun.</p>', 'ru' => '<p>Проект предусматривает подземный паркинг и озеленённый двор.</p><p>Продажи квартир на этапе строительства уже начались.</p>'],
    ],
];
