<?php

/**
 * 25 city guide entries. Beaches, sights and nature areas are real places with approximate
 * coordinates; restaurants and cafes are fictional demo businesses.
 *
 * hours: null = always open, [open, close] = daily, 'season' = daily 10:00-22:00 from May to October.
 *
 * @return list<array<string, mixed>>
 */
return [
    [
        'type' => 'beach', 'location' => 'kobuleti-centre', 'lat' => 41.8180, 'lng' => 41.7722, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ქობულეთის ცენტრალური პლაჟი', 'en' => 'Kobuleti Central Beach', 'ru' => 'Центральный пляж Кобулети'],
        'description' => ['ka' => 'წვრილკენჭიანი პლაჟი ბულვარის გასწვრივ, შხაპებით, გასახდელებით და მაშველების პოსტებით.', 'en' => 'A fine-pebble beach along the boulevard with showers, changing cabins and lifeguard posts.', 'ru' => 'Мелкогалечный пляж вдоль бульвара с душами, кабинками и постами спасателей.'],
        'content' => ['ka' => '<p>ქალაქის მთავარი პლაჟი რამდენიმე კილომეტრზეა გადაჭიმული. ზღვაში შესვლა თანდათანობითია, რაც ოჯახებისთვის მოსახერხებელია. სეზონზე მუშაობს შეზლონგების გაქირავება და კაფეები.</p>', 'en' => '<p>The main town beach stretches for several kilometres. The water gets deeper gradually, which suits families. In season you can rent sun loungers and grab a bite at the beach cafes.</p>', 'ru' => '<p>Главный городской пляж тянется на несколько километров. Вход в море пологий, что удобно для семей с детьми. В сезон работают прокат шезлонгов и пляжные кафе.</p>'],
    ],
    [
        'type' => 'beach', 'location' => 'north-kobuleti', 'lat' => 41.8470, 'lng' => 41.7768, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ჩრდილოეთ ქობულეთის პლაჟი', 'en' => 'North Kobuleti Beach', 'ru' => 'Пляж Северного Кобулети'],
        'description' => ['ka' => 'უფრო მშვიდი პლაჟი ახალი სასტუმროების მიმდებარედ, ფართო სანაპირო ზოლით.', 'en' => 'A calmer beach next to the new hotels, with a wide strip of shore.', 'ru' => 'Более спокойный пляж рядом с новыми отелями, с широкой полосой берега.'],
        'content' => ['ka' => '<p>ჩრდილოეთ ნაწილში ნაკლები ხალხია, ხოლო ველობილიკი ცენტრამდე მიდის. საღამოს აქ კარგად ჩანს მზის ჩასვლა.</p>', 'en' => '<p>The northern part is less crowded and the cycle path runs all the way to the centre. It is a good spot to watch the sunset.</p>', 'ru' => '<p>В северной части меньше людей, а велодорожка ведёт до самого центра. Отличное место, чтобы встретить закат.</p>'],
    ],
    [
        'type' => 'beach', 'location' => 'tsikhisdziri', 'lat' => 41.7662, 'lng' => 41.7503, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ციხისძირის პლაჟი', 'en' => 'Tsikhisdziri Beach', 'ru' => 'Пляж Цихисдзири'],
        'description' => ['ka' => 'კენჭიანი პლაჟი მწვანე ფერდობების ძირში, გამჭვირვალე წყლით.', 'en' => 'A pebble beach at the foot of green hills with very clear water.', 'ru' => 'Галечный пляж у подножия зелёных холмов с очень прозрачной водой.'],
        'content' => ['ka' => '<p>პლაჟამდე ციცაბო კიბე ჩადის, ამიტომ ჩანთა მსუბუქი იყოს. იდეალურია სნორკელინგისთვის და პეტრას ციხის მონახულებასთან ერთად.</p>', 'en' => '<p>A steep staircase leads down to the beach, so pack light. Great for snorkelling and easy to combine with a visit to Petra fortress.</p>', 'ru' => '<p>К пляжу ведёт крутая лестница, так что берите поменьше вещей. Отлично подходит для снорклинга, а рядом крепость Петра.</p>'],
    ],
    [
        'type' => 'beach', 'location' => 'chakvi', 'lat' => 41.7238, 'lng' => 41.7288, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ჩაქვის პლაჟი', 'en' => 'Chakvi Beach', 'ru' => 'Пляж Чакви'],
        'description' => ['ka' => 'ოჯახური პლაჟი ჩაქვში, ბათუმიდან და ქობულეთიდან თანაბარ მანძილზე.', 'en' => 'A family beach in Chakvi, halfway between Batumi and Kobuleti.', 'ru' => 'Семейный пляж в Чакви, на полпути между Батуми и Кобулети.'],
        'content' => ['ka' => '<p>ჩაქვის სანაპიროზე მშვიდი ატმოსფეროა და ბევრი საოჯახო სასტუმრო. ახლოსაა ჩაის პლანტაციები და ბოტანიკური ბაღი.</p>', 'en' => '<p>The Chakvi seafront is quiet and full of family guesthouses. Tea plantations and the Batumi Botanical Garden are close by.</p>', 'ru' => '<p>На набережной Чакви спокойно и много семейных гостевых домов. Рядом чайные плантации и Батумский ботанический сад.</p>'],
    ],
    [
        'type' => 'beach', 'location' => 'pichvnari', 'lat' => 41.8868, 'lng' => 41.7742, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ფიჭვნარის პლაჟი', 'en' => 'Pichvnari Beach', 'ru' => 'Пляж Пичвнари'],
        'description' => ['ka' => 'ქვიშიანი პლაჟი ფიჭვის ტყით, აჭარის ერთ-ერთი ყველაზე სუფთა სანაპირო.', 'en' => 'A sandy beach backed by pine forest, one of the cleanest stretches of the Adjara coast.', 'ru' => 'Песчаный пляж с сосновым лесом, один из самых чистых участков побережья Аджарии.'],
        'content' => ['ka' => '<p>ფიჭვნარში სანაპირო ქვიშიანია, რაც იშვიათობაა აჭარაში. ბავშვებთან ერთად დასვენებისთვის ერთ-ერთი საუკეთესო ადგილია.</p>', 'en' => '<p>Pichvnari has a sandy shore, which is rare in Adjara. It is one of the best spots for a holiday with children.</p>', 'ru' => '<p>В Пичвнари песчаный берег, что для Аджарии редкость. Одно из лучших мест для отдыха с детьми.</p>'],
    ],
    [
        'type' => 'sight', 'location' => 'kobuleti-centre', 'lat' => 41.8200, 'lng' => 41.7738, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ზღვისპირა ბულვარი', 'en' => 'Seaside Boulevard', 'ru' => 'Приморский бульвар'],
        'description' => ['ka' => 'რამდენიმეკილომეტრიანი ბულვარი ფიჭვებით, ველობილიკით და კაფეებით.', 'en' => 'A boulevard several kilometres long with pines, a cycle path and cafes.', 'ru' => 'Бульвар длиной в несколько километров с соснами, велодорожкой и кафе.'],
        'content' => ['ka' => '<p>ბულვარი ქალაქის გულია: დილით აქ დარბიან, საღამოს კი სეირნობენ. ველოსიპედის ქირაობა რამდენიმე წერტილშია შესაძლებელი.</p>', 'en' => '<p>The boulevard is the heart of the town: joggers in the morning, strollers in the evening. Bikes can be rented at several points along it.</p>', 'ru' => '<p>Бульвар — сердце города: утром здесь бегают, вечером гуляют. Велосипеды можно взять напрокат в нескольких точках.</p>'],
    ],
    [
        'type' => 'sight', 'location' => 'tsikhisdziri', 'lat' => 41.7690, 'lng' => 41.7522, 'hours' => ['10:00', '19:00'], 'price' => 1,
        'title' => ['ka' => 'პეტრას ციხე', 'en' => 'Petra Fortress', 'ru' => 'Крепость Петра'],
        'description' => ['ka' => 'ბიზანტიური ციხე-ქალაქის ნანგრევები ზღვის თავზე, VI საუკუნე.', 'en' => 'Ruins of a 6th-century Byzantine fortress town above the sea.', 'ru' => 'Руины византийской крепости-города VI века над морем.'],
        'content' => ['ka' => '<p>პეტრა იუსტინიანე I-ის დროს აშენდა და ლაზიკის ომების მნიშვნელოვანი ციხე იყო. ბორცვიდან ზღვის შესანიშნავი ხედი იშლება.</p>', 'en' => '<p>Petra was built under Emperor Justinian I and played a key role in the Lazic War. The hilltop offers superb views of the sea.</p>', 'ru' => '<p>Петра построена при императоре Юстиниане I и сыграла важную роль в Лазской войне. С холма открывается великолепный вид на море.</p>'],
    ],
    [
        'type' => 'sight', 'location' => 'pichvnari', 'lat' => 41.8795, 'lng' => 41.7775, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ფიჭვნარის ნაქალაქარი', 'en' => 'Pichvnari Ancient Settlement', 'ru' => 'Древнее городище Пичвнари'],
        'description' => ['ka' => 'კოლხური და ბერძნული ნამოსახლარი, საქართველოს ერთ-ერთი მნიშვნელოვანი არქეოლოგიური ძეგლი.', 'en' => 'A Colchian and Greek settlement, one of the key archaeological sites in Georgia.', 'ru' => 'Колхское и греческое поселение, один из важнейших археологических памятников Грузии.'],
        'content' => ['ka' => '<p>აქ აღმოჩენილია ძვ. წ. V საუკუნის სამაროვნები და ბერძნული კერამიკა. გათხრები დღემდე გრძელდება.</p>', 'en' => '<p>Burials from the 5th century BC and Greek pottery have been found here, and excavations continue to this day.</p>', 'ru' => '<p>Здесь найдены захоронения V века до н. э. и греческая керамика, раскопки продолжаются по сей день.</p>'],
    ],
    [
        'type' => 'sight', 'location' => 'chakvi', 'lat' => 41.7290, 'lng' => 41.7480, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ჩაქვის ჩაის პლანტაციები', 'en' => 'Chakvi Tea Plantations', 'ru' => 'Чайные плантации Чакви'],
        'description' => ['ka' => 'საქართველოს პირველი სამრეწველო ჩაის პლანტაციები, XIX საუკუნის ბოლო.', 'en' => 'The first commercial tea plantations in Georgia, dating from the late 19th century.', 'ru' => 'Первые промышленные чайные плантации Грузии, конец XIX века.'],
        'content' => ['ka' => '<p>ჩაქვში ჩაის მოყვანა ჩინელმა სპეციალისტმა ლაო ჯინ ჯაომ განავითარა. დღეს რამდენიმე ოჯახური მეურნეობა სტუმრებს დეგუსტაციას სთავაზობს.</p>', 'en' => '<p>Tea growing in Chakvi was developed by the Chinese tea master Lau Jin Jau. Today several family farms offer tastings to visitors.</p>', 'ru' => '<p>Чаеводство в Чакви развил китайский специалист Лау Джин Джау. Сегодня несколько семейных хозяйств предлагают гостям дегустации.</p>'],
    ],
    [
        'type' => 'sight', 'location' => 'kobuleti-centre', 'lat' => 41.8195, 'lng' => 41.7772, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'ცენტრალური პარკი', 'en' => 'Central Park', 'ru' => 'Центральный парк'],
        'description' => ['ka' => 'ჩრდილიანი პარკი ამფითეატრით, საბავშვო მოედნებით და შადრევნებით.', 'en' => 'A shady park with an amphitheatre, playgrounds and fountains.', 'ru' => 'Тенистый парк с амфитеатром, детскими площадками и фонтанами.'],
        'content' => ['ka' => '<p>ზაფხულში ამფითეატრში კონცერტები და ფესტივალები იმართება. პარკი ბულვარიდან რამდენიმე წუთის სავალზეა.</p>', 'en' => '<p>In summer the amphitheatre hosts concerts and festivals. The park is a few minutes on foot from the boulevard.</p>', 'ru' => '<p>Летом в амфитеатре проходят концерты и фестивали. Парк в нескольких минутах ходьбы от бульвара.</p>'],
    ],
    [
        'type' => 'nature', 'location' => 'kobuleti-protected-areas', 'lat' => 41.8650, 'lng' => 41.7860, 'hours' => ['09:00', '18:00'], 'price' => 1,
        'title' => ['ka' => 'ისპანის ჭაობი', 'en' => 'Ispani Mire', 'ru' => 'Болото Испани'],
        'description' => ['ka' => 'უნიკალური სფაგნუმის ჭაობი, რამსარის კონვენციით დაცული ტერიტორია.', 'en' => 'A unique sphagnum mire protected under the Ramsar Convention.', 'ru' => 'Уникальное сфагновое болото под защитой Рамсарской конвенции.'],
        'content' => ['ka' => '<p>ხის ბილიკი ჭაობის შუაგულში შეგიყვანთ. ჭაობი ათასობით წლის წინ ჩამოყალიბდა და იშვიათი მცენარეების სახლია.</p>', 'en' => '<p>A wooden boardwalk takes you into the middle of the mire, which formed thousands of years ago and is home to rare plants.</p>', 'ru' => '<p>Деревянная тропа ведёт в самое сердце болота, сформировавшегося тысячи лет назад. Здесь растут редкие растения.</p>'],
    ],
    [
        'type' => 'nature', 'location' => 'kintrishi-protected-areas', 'lat' => 41.7500, 'lng' => 42.0300, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'კინტრიშის დაცული ტერიტორიები', 'en' => 'Kintrishi Protected Areas', 'ru' => 'Кинтришские охраняемые территории'],
        'description' => ['ka' => 'კოლხური ტყეები, მთის ტბები და სალაშქრო ბილიკები ზღვიდან ერთ საათში.', 'en' => 'Colchic forests, mountain lakes and hiking trails an hour from the sea.', 'ru' => 'Колхидские леса, горные озёра и треккинговые тропы в часе езды от моря.'],
        'content' => ['ka' => '<p>კინტრიშის ნაკრძალი 1959 წელს დაარსდა. ბილიკები სხვადასხვა სირთულისაა, ვიზიტორთა ცენტრში კი გიდის დაქირავება შეიძლება.</p>', 'en' => '<p>Kintrishi reserve was founded in 1959. Trails vary in difficulty and guides can be hired at the visitor centre.</p>', 'ru' => '<p>Кинтришский заповедник основан в 1959 году. Тропы разной сложности, гида можно нанять в визит-центре.</p>'],
    ],
    [
        'type' => 'nature', 'location' => 'mtirala-national-park', 'lat' => 41.6750, 'lng' => 41.8650, 'hours' => ['09:00', '18:00'], 'price' => 1,
        'title' => ['ka' => 'მტირალას ეროვნული პარკი', 'en' => 'Mtirala National Park', 'ru' => 'Национальный парк Мтирала'],
        'description' => ['ka' => 'საქართველოს ერთ-ერთი ყველაზე წვიმიანი ადგილი, ჩანჩქერებით და ტბით.', 'en' => 'One of the rainiest places in Georgia, with waterfalls and a lake.', 'ru' => 'Одно из самых дождливых мест Грузии, с водопадами и озером.'],
        'content' => ['ka' => '<p>ჩაქვისთავის ვიზიტორთა ცენტრიდან იწყება ბილიკები ჩანჩქერამდე და ტბამდე. შესაძლებელია კატამარანით გასეირნება.</p>', 'en' => '<p>Trails to the waterfall and the lake start at the Chakvistavi visitor centre. You can also go out on the lake by catamaran.</p>', 'ru' => '<p>От визит-центра в Чаквистави начинаются тропы к водопаду и озеру. Можно прокатиться по озеру на катамаране.</p>'],
    ],
    [
        'type' => 'nature', 'location' => 'kintrishi-protected-areas', 'lat' => 41.7720, 'lng' => 41.9400, 'hours' => null, 'price' => null,
        'title' => ['ka' => 'კინტრიშის ხეობა', 'en' => 'Kintrishi Gorge', 'ru' => 'Кинтришское ущелье'],
        'description' => ['ka' => 'მდინარე კინტრიშის ხეობა თაღოვანი ქვის ხიდებით და სოფლის მარნებით.', 'en' => 'The Kintrishi river valley with arched stone bridges and village wine cellars.', 'ru' => 'Долина реки Кинтриши с арочными каменными мостами и деревенскими марани.'],
        'content' => ['ka' => '<p>ხეობაში შუა საუკუნეების თაღოვანი ხიდებია შემორჩენილი. გზად შეგიძლიათ შეიძინოთ ადგილობრივი თაფლი და ყველი.</p>', 'en' => '<p>Medieval arched bridges survive along the gorge. On the way you can buy local honey and cheese.</p>', 'ru' => '<p>В ущелье сохранились средневековые арочные мосты. По дороге можно купить местный мёд и сыр.</p>'],
    ],
    [
        'type' => 'museum', 'location' => 'kobuleti-centre', 'lat' => 41.8212, 'lng' => 41.7795, 'hours' => ['10:00', '18:00'], 'price' => 1,
        'title' => ['ka' => 'ქობულეთის მხარეთმცოდნეობის მუზეუმი', 'en' => 'Kobuleti Museum of Local History', 'ru' => 'Кобулетский краеведческий музей'],
        'description' => ['ka' => 'ფიჭვნარის არქეოლოგიური აღმოჩენები და კობულეთის ისტორია.', 'en' => 'Archaeological finds from Pichvnari and the story of Kobuleti.', 'ru' => 'Археологические находки Пичвнари и история Кобулети.'],
        'content' => ['ka' => '<p>ექსპოზიციაში წარმოდგენილია კოლხური ოქრო, ბერძნული ამფორები და XIX საუკუნის კურორტის ფოტოები.</p>', 'en' => '<p>The collection includes Colchian gold, Greek amphorae and photos of the 19th-century resort.</p>', 'ru' => '<p>В экспозиции колхское золото, греческие амфоры и фотографии курорта XIX века.</p>'],
    ],
    [
        'type' => 'restaurant', 'location' => 'kobuleti-centre', 'lat' => 41.8188, 'lng' => 41.7745, 'hours' => ['11:00', '00:00'], 'price' => 3, 'owner' => 'restaurant',
        'title' => ['ka' => 'რესტორანი „ბულვარი“', 'en' => 'Bulvari Restaurant', 'ru' => 'Ресторан «Бульвари»'],
        'description' => ['ka' => 'აჭარული სამზარეულო და ზღვის პროდუქტები ტერასით ბულვარზე.', 'en' => 'Adjarian cuisine and seafood on a terrace by the boulevard.', 'ru' => 'Аджарская кухня и морепродукты на террасе у бульвара.'],
        'content' => ['ka' => '<p>მენიუში აჭარული ხაჭაპური, ბორანო და დღის დაჭერილი თევზია. საღამოობით ცოცხალი მუსიკა უკრავს.</p>', 'en' => '<p>The menu features Adjarian khachapuri, borano and the catch of the day. Live music plays in the evenings.</p>', 'ru' => '<p>В меню аджарский хачапури, борано и свежий улов дня. По вечерам живая музыка.</p>'],
    ],
    [
        'type' => 'restaurant', 'location' => 'kobuleti-centre', 'lat' => 41.8172, 'lng' => 41.7781, 'hours' => ['10:00', '23:00'], 'price' => 2, 'owner' => 'restaurant',
        'title' => ['ka' => 'აჭარული სახლი', 'en' => 'Adjarian House', 'ru' => 'Аджарский дом'],
        'description' => ['ka' => 'ხაჭაპური თონეში და ტრადიციული აჭარული კერძები ოჯახური რეცეპტებით.', 'en' => 'Khachapuri from a clay oven and traditional Adjarian dishes from family recipes.', 'ru' => 'Хачапури из тоне и традиционные аджарские блюда по семейным рецептам.'],
        'content' => ['ka' => '<p>აქ აუცილებლად გასინჯეთ სინორი და ჩირბული. ზაფხულში სასურველია მაგიდის წინასწარ დაჯავშნა.</p>', 'en' => '<p>Do try the sinori and chirbuli. In summer it is best to book a table in advance.</p>', 'ru' => '<p>Обязательно попробуйте синори и чирбули. Летом столик лучше бронировать заранее.</p>'],
    ],
    [
        'type' => 'restaurant', 'location' => 'north-kobuleti', 'lat' => 41.8455, 'lng' => 41.7772, 'hours' => ['12:00', '23:00'], 'price' => 3, 'owner' => 'restaurant',
        'title' => ['ka' => 'თევზის რესტორანი „ნავსადგური“', 'en' => 'Harbour Fish Restaurant', 'ru' => 'Рыбный ресторан «Гавань»'],
        'description' => ['ka' => 'შავი ზღვის ახალი თევზი გრილზე: სტავრიდა, კეფალი და კალკანი.', 'en' => 'Fresh Black Sea fish from the grill: horse mackerel, mullet and turbot.', 'ru' => 'Свежая черноморская рыба на гриле: ставрида, кефаль и калкан.'],
        'content' => ['ka' => '<p>თევზს თავად ირჩევთ ვიტრინიდან, მზარეული კი თქვენს თვალწინ ამზადებს.</p>', 'en' => '<p>You pick your fish from the display and the chef grills it in front of you.</p>', 'ru' => '<p>Рыбу выбираете сами на витрине, а повар готовит её у вас на глазах.</p>'],
    ],
    [
        'type' => 'restaurant', 'location' => 'south-kobuleti', 'lat' => 41.8020, 'lng' => 41.7765, 'hours' => ['12:00', '23:30'], 'price' => 2,
        'title' => ['ka' => 'სახინკლე „ძველი ქობულეთი“', 'en' => 'Old Kobuleti Tavern', 'ru' => 'Хинкальная «Старый Кобулети»'],
        'description' => ['ka' => 'ხინკალი, მწვადი და ქართული ღვინო ძველებურ ინტერიერში.', 'en' => 'Khinkali, mtsvadi and Georgian wine in an old-style interior.', 'ru' => 'Хинкали, мцвади и грузинское вино в старинном интерьере.'],
        'content' => ['ka' => '<p>ადგილობრივების საყვარელი ადგილი ხელმისაწვდომი ფასებით და დიდი პორციებით.</p>', 'en' => '<p>A local favourite with fair prices and generous portions.</p>', 'ru' => '<p>Любимое место местных жителей с доступными ценами и большими порциями.</p>'],
    ],
    [
        'type' => 'cafe', 'location' => 'chakvi', 'lat' => 41.7262, 'lng' => 41.7365, 'hours' => ['09:00', '21:00'], 'price' => 1,
        'title' => ['ka' => 'ჩაქვის ჩაიხანა', 'en' => 'Chakvi Tea House', 'ru' => 'Чайхана Чакви'],
        'description' => ['ka' => 'ადგილობრივი შავი და მწვანე ჩაი, ხილის მურაბები და ნამცხვრები.', 'en' => 'Local black and green tea, fruit preserves and cakes.', 'ru' => 'Местный чёрный и зелёный чай, варенье и выпечка.'],
        'content' => ['ka' => '<p>ჩაი ჩაქვის ფერდობებზეა მოკრეფილი. შეგიძლიათ შეიძინოთ ჩაი სახლში წასაღებად.</p>', 'en' => '<p>The tea is picked on the Chakvi slopes and you can buy some to take home.</p>', 'ru' => '<p>Чай собран на склонах Чакви, его можно купить с собой.</p>'],
    ],
    [
        'type' => 'cafe', 'location' => 'kobuleti-centre', 'lat' => 41.8205, 'lng' => 41.7742, 'hours' => ['08:00', '23:00'], 'price' => 2,
        'title' => ['ka' => 'კაფე „ფიჭვი“', 'en' => 'Pine Café', 'ru' => 'Кафе «Сосна»'],
        'description' => ['ka' => 'ყავა, საუზმე და დესერტები ფიჭვების ჩრდილში, ბულვარზე.', 'en' => 'Coffee, breakfast and desserts in the shade of the pines on the boulevard.', 'ru' => 'Кофе, завтраки и десерты в тени сосен на бульваре.'],
        'content' => ['ka' => '<p>დილით აქ ახლად გამომცხვარი ფუნთუშები და სპეშელთი ყავაა, საღამოს კი ლიმონათი.</p>', 'en' => '<p>Fresh pastries and specialty coffee in the morning, homemade lemonade in the evening.</p>', 'ru' => '<p>Утром свежая выпечка и спешелти-кофе, вечером домашний лимонад.</p>'],
    ],
    [
        'type' => 'cafe', 'location' => 'north-kobuleti', 'lat' => 41.8402, 'lng' => 41.7760, 'hours' => ['08:30', '22:00'], 'price' => 2,
        'title' => ['ka' => 'ზღვის ყავა', 'en' => 'Sea Coffee', 'ru' => 'Морской кофе'],
        'description' => ['ka' => 'პატარა ყავის ბარი ზღვის ხედით და სამუშაო ადგილებით.', 'en' => 'A small coffee bar with a sea view and spots to work from.', 'ru' => 'Небольшой кофе-бар с видом на море и местами для работы.'],
        'content' => ['ka' => '<p>სწრაფი Wi-Fi და როზეტები ყველა მაგიდასთან, რაც დისტანციურად მომუშავეებს მოსწონთ.</p>', 'en' => '<p>Fast Wi-Fi and sockets at every table make it popular with remote workers.</p>', 'ru' => '<p>Быстрый Wi-Fi и розетки у каждого столика — удаленщики это ценят.</p>'],
    ],
    [
        'type' => 'cafe', 'location' => 'kobuleti-centre', 'lat' => 41.8178, 'lng' => 41.7736, 'hours' => 'season', 'price' => 1,
        'title' => ['ka' => 'ბულვარის ნაყინი', 'en' => 'Boulevard Gelato', 'ru' => 'Джелато на бульваре'],
        'description' => ['ka' => 'ხელნაკეთი ნაყინი ადგილობრივი ციტრუსით და კენკრით.', 'en' => 'Handmade gelato with local citrus and berries.', 'ru' => 'Мороженое ручной работы с местными цитрусовыми и ягодами.'],
        'content' => ['ka' => '<p>სცადეთ მანდარინის და ფეიხოას ნაყინი, რომელიც მხოლოდ აქ შეგხვდებათ.</p>', 'en' => '<p>Try the mandarin and feijoa flavours you will not find anywhere else.</p>', 'ru' => '<p>Попробуйте мандариновое и фейхоа — такого больше нигде не найдёте.</p>'],
    ],
    [
        'type' => 'service', 'location' => 'kobuleti-centre', 'lat' => 41.8150, 'lng' => 41.7812, 'hours' => ['06:00', '23:00'], 'price' => null,
        'title' => ['ka' => 'ქობულეთის რკინიგზის სადგური', 'en' => 'Kobuleti Railway Station', 'ru' => 'Железнодорожный вокзал Кобулети'],
        'description' => ['ka' => 'მატარებლები თბილისისა და ბათუმის მიმართულებით.', 'en' => 'Trains to Tbilisi and Batumi.', 'ru' => 'Поезда в Тбилиси и Батуми.'],
        'content' => ['ka' => '<p>ზაფხულში ბილეთები სწრაფად იყიდება, ამიტომ შეიძინეთ წინასწარ ონლაინ.</p>', 'en' => '<p>Tickets sell out fast in summer, so buy them online in advance.</p>', 'ru' => '<p>Летом билеты быстро раскупают, лучше покупать заранее онлайн.</p>'],
    ],
    [
        'type' => 'service', 'location' => 'kobuleti-centre', 'lat' => 41.8192, 'lng' => 41.7758, 'hours' => ['10:00', '19:00'], 'price' => null,
        'title' => ['ka' => 'ტურისტული საინფორმაციო ცენტრი', 'en' => 'Tourist Information Centre', 'ru' => 'Туристический информационный центр'],
        'description' => ['ka' => 'რუკები, ექსკურსიები და რჩევები ქალაქისა და მიდამოების შესახებ.', 'en' => 'Maps, tours and tips about the town and its surroundings.', 'ru' => 'Карты, экскурсии и советы о городе и окрестностях.'],
        'content' => ['ka' => '<p>აქ დაგეხმარებიან ტრანსფერის, გიდის და ექსკურსიის მოძებნაში სამ ენაზე.</p>', 'en' => '<p>Staff help with transfers, guides and tours in three languages.</p>', 'ru' => '<p>Здесь помогут с трансфером, гидом и экскурсиями на трёх языках.</p>'],
    ],
];
