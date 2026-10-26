<?php

/*
 * Labels for App\Enums\*, keyed by the snake_case enum name and the case value.
 */
return [
    'account_type' => [
        'individual' => 'ფიზიკური პირი',
        'business' => 'ბიზნესი',
    ],

    'amenity_scope' => [
        'hotel' => 'სასტუმრო',
        'room' => 'ოთახი',
        'property' => 'უძრავი ქონება',
    ],

    'application_status' => [
        'new' => 'ახალი',
        'viewed' => 'ნანახი',
        'shortlisted' => 'შერჩეული',
        'rejected' => 'უარყოფილი',
        'hired' => 'აყვანილი',
    ],

    'apply_method' => [
        'site' => 'საიტის მეშვეობით',
        'phone' => 'ტელეფონით',
        'email' => 'ელფოსტით',
    ],

    'bed_type' => [
        'single' => 'ერთადგილიანი საწოლი',
        'double' => 'ორადგილიანი საწოლი',
        'twin' => 'ორი ცალკე საწოლი',
        'sofa' => 'გასაშლელი დივანი',
        'bunk' => 'ორსართულიანი საწოლი',
    ],

    'booking_status' => [
        'pending' => 'მოლოდინში',
        'accepted' => 'დადასტურებული',
        'declined' => 'უარყოფილი',
        'cancelled' => 'გაუქმებული',
        'expired' => 'ვადაგასული',
    ],

    'building_status' => [
        'new' => 'ახალი აშენებული',
        'old' => 'ძველი აშენებული',
        'under_construction' => 'მშენებარე',
    ],

    'business_type' => [
        'hotel' => 'სასტუმრო',
        'agency' => 'უძრავი ქონების სააგენტო',
        'employer' => 'დამსაქმებელი',
        'service' => 'მომსახურება',
        'restaurant' => 'რესტორანი',
    ],

    'category_module' => [
        'classifieds' => 'განცხადებები',
        'vacancies' => 'ვაკანსიები',
        'news' => 'სიახლეები',
        'events' => 'ღონისძიებები',
    ],

    'currency' => [
        'GEL' => 'ლარი',
        'USD' => 'აშშ დოლარი',
        'EUR' => 'ევრო',
    ],

    'employment_type' => [
        'full_time' => 'სრული განაკვეთი',
        'part_time' => 'ნახევარი განაკვეთი',
        'seasonal' => 'სეზონური',
        'remote' => 'დისტანციური',
    ],

    'experience' => [
        'none' => 'გამოცდილების გარეშე',
        'under_1' => '1 წლამდე',
        '1_3' => '1–3 წელი',
        '3_5' => '3–5 წელი',
        'over_5' => '5 წელზე მეტი',
    ],

    'hotel_type' => [
        'hotel' => 'სასტუმრო',
        'guesthouse' => 'საოჯახო სასტუმრო',
        'apartment' => 'აპარტამენტი',
        'cottage' => 'კოტეჯი',
        'hostel' => 'ჰოსტელი',
    ],

    'item_condition' => [
        'new' => 'ახალი',
        'used' => 'მეორადი',
    ],

    'listed_by' => [
        'owner' => 'მესაკუთრე',
        'agency' => 'სააგენტო',
    ],

    'listing_group' => [
        'active' => 'აქტიური',
        'pending' => 'მოდერაციაზე',
        'attention' => 'საჭიროებს ყურადღებას',
        'paused' => 'დაპაუზებული',
        'expired' => 'ვადაგასული',
    ],

    'notification_topic' => [
        'messages' => 'ახალი მიწერები',
        'inquiries' => 'ჯავშნები და განაცხადები',
        'moderation' => 'მოდერაციის შედეგები',
        'reminders' => 'ვადის შეხსენებები',
        'saved_searches' => 'შენახული ძიებები',
    ],

    'listing_module' => [
        'hotels' => 'სასტუმროები',
        'properties' => 'უძრავი ქონება',
        'classifieds' => 'განცხადებები',
        'vacancies' => 'ვაკანსიები',
    ],

    'listing_status' => [
        'draft' => 'მონახაზი',
        'pending' => 'მოდერაციაზე',
        'published' => 'გამოქვეყნებული',
        'rejected' => 'უარყოფილი',
        'paused' => 'შეჩერებული',
        'expired' => 'ვადაგასული',
        'archived' => 'დაარქივებული',
    ],

    'location_type' => [
        'municipality' => 'მუნიციპალიტეტი',
        'town' => 'ქალაქი',
        'borough' => 'დაბა',
        'village' => 'სოფელი',
        'district' => 'უბანი',
        'protected_area' => 'დაცული ტერიტორია',
    ],

    'notification_channel' => [
        'email' => 'ელფოსტა',
        'push' => 'Push შეტყობინება',
        'telegram' => 'Telegram',
    ],

    'payment_gateway' => [
        'fake' => 'სატესტო',
        'bog' => 'საქართველოს ბანკი',
        'tbc' => 'თიბისი ბანკი',
    ],

    'payment_status' => [
        'pending' => 'მოლოდინში',
        'paid' => 'გადახდილი',
        'failed' => 'წარუმატებელი',
        'cancelled' => 'გაუქმებული',
        'refunded' => 'დაბრუნებული',
        'partially_refunded' => 'ნაწილობრივ დაბრუნებული',
    ],

    'place_type' => [
        'beach' => 'სანაპირო',
        'sight' => 'ღირსშესანიშნაობა',
        'nature' => 'ბუნება',
        'restaurant' => 'რესტორანი',
        'cafe' => 'კაფე',
        'museum' => 'მუზეუმი',
        'service' => 'მომსახურება',
    ],

    'promotion_type' => [
        'vip' => 'VIP',
        'super_vip' => 'სუპერ VIP',
        'bump' => 'ამოწევა',
        'featured_home' => 'მთავარ გვერდზე',
        'auto_bump' => 'ავტო-ამოწევა',
    ],

    'property_condition' => [
        'newly_renovated' => 'ახალი გარემონტებული',
        'old_renovation' => 'ძველი გარემონტებული',
        'needs_renovation' => 'სარემონტო',
        'white_frame' => 'თეთრი კარკასი',
        'black_frame' => 'შავი კარკასი',
        'green_frame' => 'მწვანე კარკასი',
    ],

    'property_deal' => [
        'sale' => 'იყიდება',
        'rent' => 'ქირავდება',
        'daily_rent' => 'ქირავდება დღიურად',
    ],

    'property_type' => [
        'apartment' => 'ბინა',
        'house' => 'სახლი',
        'land' => 'მიწის ნაკვეთი',
        'commercial' => 'კომერციული ფართი',
        'hotel_business' => 'სასტუმრო (ბიზნესი)',
    ],

    'report_reason' => [
        'spam' => 'სპამი',
        'fraud' => 'თაღლითობა',
        'duplicate' => 'დუბლიკატი',
        'wrong_category' => 'არასწორი კატეგორია',
        'offensive' => 'შეურაცხმყოფელი შინაარსი',
        'already_sold' => 'უკვე გაყიდულია',
        'other' => 'სხვა',
    ],

    'report_status' => [
        'open' => 'ღია',
        'resolved' => 'გადაწყვეტილი',
        'dismissed' => 'უარყოფილი',
    ],

    'review_status' => [
        'pending' => 'მოდერაციაზე',
        'published' => 'გამოქვეყნებული',
        'rejected' => 'უარყოფილი',
    ],

    'role' => [
        'super_admin' => 'სუპერ ადმინისტრატორი',
        'admin' => 'ადმინისტრატორი',
        'moderator' => 'მოდერატორი',
        'business' => 'ბიზნესი',
        'user' => 'მომხმარებელი',
    ],

    'salary_period' => [
        'hour' => 'საათში',
        'day' => 'დღეში',
        'month' => 'თვეში',
        'season' => 'სეზონში',
    ],

    'search_frequency' => [
        'instant' => 'მაშინვე',
        'daily' => 'დღეში ერთხელ',
        'weekly' => 'კვირაში ერთხელ',
    ],

    'vacancy_benefit' => [
        'accommodation' => 'საცხოვრებელი',
        'meals' => 'კვება',
        'transport' => 'ტრანსპორტი',
        'uniform' => 'ფორმა',
        'training' => 'სწავლება',
    ],
    'otp_purpose' => [
        'login' => 'შესვლა',
        'change_phone' => 'ნომრის შეცვლა',
    ],
    'sms_status' => [
        'queued' => 'რიგშია',
        'sent' => 'გაგზავნილია',
        'delivered' => 'მიწოდებულია',
        'failed' => 'ვერ გაიგზავნა',
    ],
    'permission' => [
        'access_admin' => 'ადმინ-პანელზე წვდომა',
        'moderate_content' => 'მოდერაცია',
        'manage_content' => 'კონტენტის მართვა',
        'manage_users' => 'მომხმარებლების მართვა',
        'manage_payments' => 'გადახდების მართვა',
        'manage_settings' => 'სისტემის პარამეტრები',
    ],
    'otp_error' => [
        'cooldown' => 'ახალი კოდის მოთხოვნა შეგიძლიათ :seconds წამში.',
        'phone_limit' => 'ამ ნომერზე ძალიან ბევრი კოდი გაიგზავნა. სცადეთ :minutes წუთში.',
        'ip_limit' => 'ძალიან ბევრი მცდელობა. სცადეთ :minutes წუთში.',
        'expired' => 'კოდს ვადა გაუვიდა ან ის შეიცვალა. მოითხოვეთ ახალი კოდი.',
        'invalid' => 'კოდი არასწორია. დარჩა :count მცდელობა.',
        'too_many_attempts' => 'მცდელობების ლიმიტი ამოიწურა. მოითხოვეთ ახალი კოდი.',
    ],
    'banner_slot' => [
        'home_top' => 'მთავარი, ზედა',
        'home_middle' => 'მთავარი, შუა',
        'listing_sidebar' => 'განცხადება, გვერდითი',
        'listing_results' => 'ძიების შედეგებში',
    ],
    'price_plan_product' => [
        'vip' => 'VIP',
        'super_vip' => 'სუპერ VIP',
        'bump' => 'ამოწევა',
        'auto_bump' => 'ავტო-ამოწევა',
        'business_package' => 'ბიზნეს პაკეტი',
        'featured_hotel' => 'რჩეული სასტუმრო',
        'banner' => 'ბანერი',
        'wallet_top_up' => 'ბალანსის შევსება',
        'custom' => 'ინდივიდუალური შეკვეთა',
    ],
    'contact_topic' => [
        'general' => 'ზოგადი კითხვა',
        'listing' => 'განცხადებასთან დაკავშირებით',
        'advertising' => 'რეკლამა',
        'partnership' => 'თანამშრომლობა',
        'bug' => 'ტექნიკური პრობლემა',
    ],
    'order_status' => [
        'pending' => 'გადახდის მოლოდინში',
        'paid' => 'გადახდილი',
        'fulfilled' => 'აქტიური',
        'failed' => 'წარუმატებელი',
        'cancelled' => 'გაუქმებული',
        'refunded' => 'დაბრუნებული',
        'partially_refunded' => 'ნაწილობრივ დაბრუნებული',
    ],
    'payment_method' => [
        'card' => 'ბარათით',
        'balance' => 'ბალანსიდან',
    ],
    'wallet_entry_type' => [
        'top_up' => 'შევსება',
        'purchase' => 'შეძენა',
        'refund' => 'დაბრუნება',
        'adjustment' => 'კორექტირება',
    ],
    'payment_event_type' => [
        'created' => 'შეკვეთა ბანკში',
        'callback' => 'ბანკის შეტყობინება',
        'status_check' => 'სტატუსის შემოწმება',
        'status_changed' => 'სტატუსის ცვლილება',
        'refund' => 'დაბრუნება',
        'error' => 'შეცდომა',
    ],
];
