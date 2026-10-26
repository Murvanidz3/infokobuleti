<?php

return [
    'days' => '{1} :count day|[2,*] :count days',
    'once' => 'One time',
    'until' => 'until :date',
    'paid_from_balance' => 'Paid from your balance. The service is already active.',

    'products' => [
        'super_vip' => 'Top of the list and on the homepage, with a dark highlighted card.',
        'vip' => 'Above regular listings, with a highlighted card and a VIP mark.',
        'featured_hotel' => 'Your hotel in the first slots of the homepage hotels block.',
        'bump' => 'Moves the listing to the top of the list right now (after VIPs).',
        'auto_bump' => 'An automatic bump every day for the chosen period.',
    ],

    'promote' => [
        'title' => 'Promote listing',
        'subtitle' => 'Choose a service and duration. Pay from your balance or by card.',
        'back' => 'My listings',
        'button' => 'Promote',
        'not_live' => 'Only live, published listings can be promoted.',
        'no_plans_title' => 'No paid services yet',
        'no_plans_text' => 'There are no price plans for this type of listing yet.',
    ],

    'checkout' => [
        'title' => 'Payment',
        'method' => 'Payment method',
        'card' => 'By card',
        'balance' => 'From balance',
        'bank' => 'Bank',
        'pay' => 'Pay',
        'secure' => 'You enter your card details on the bank\'s secure page. We never see them.',
    ],

    'errors' => [
        'insufficient_balance' => 'There is not enough money on your balance.',
        'not_allowed' => 'This purchase is not possible right now.',
        'gateway' => 'Could not reach the bank. Please try again shortly.',
        'choose_plan' => 'Choose a service.',
    ],

    'wallet' => [
        'title' => 'Balance',
        'text' => 'Top up once and pay for VIP, bumps and packages in one click.',
        'amount' => 'Amount',
        'custom' => 'Other amount, ₾',
        'limits' => 'From :min to :max ₾',
        'top_up' => 'Top up balance',
        'ledger' => 'Balance history',
        'notes' => [
            'duplicate_payment' => 'Order paid twice, the money went back to the balance',
        ],
    ],

    'package' => [
        'title' => 'Business package',
        'active' => 'Active until :date',
        'limit' => ':count active listings',
        'perks' => [
            'limit' => 'More active listings instead of the monthly limit',
            'badge' => 'Verified business badge',
            'logo' => 'Your logo on listing cards',
            'analytics' => 'Analytics of views, calls and saves',
        ],
        'buy' => 'Buy package',
        'extend' => 'Extend package',
        'none' => 'No packages are available yet.',
        'limit_reached' => 'Your package\'s limit of :count active listings is reached. Pause or delete an older listing.',
    ],

    'invoices' => [
        'title' => 'Invoices to pay',
    ],

    'promotions' => [
        'title' => 'Active services',
        'empty' => 'None of your listings is promoted right now.',
        'choose' => 'Choose a listing',
        'extend' => 'Extend',
    ],

    'history' => [
        'title' => 'Order history',
        'empty' => 'Your orders and invoices appear here once you buy a paid service.',
        'invoice' => 'Invoice',
        'refunded' => 'Refunded :amount',
    ],

    'analytics' => [
        'title' => 'Analytics',
        'subtitle' => 'How your listings perform.',
        'open' => 'Analytics',
        'period' => 'Period',
        'totals' => 'Totals for the period',
        'views_per_day' => 'Views per day',
        'top' => 'Best listings',
        'listing' => 'Listing',
        'empty' => 'No data for this period yet.',
        'locked_title' => 'Analytics come with the business package',
        'locked_text' => 'Get the business package to see how many people view, save and call about your listings.',
    ],

    'return' => [
        'success' => 'Payment received. The service is active.',
        'processing_done' => 'Payment received. The service will be active in a few seconds.',
        'failed' => 'The payment did not go through. You were not charged. Please try again.',
        'processing_title' => 'Checking the payment',
        'processing_text' => 'The bank has not confirmed the payment yet. This page refreshes by itself.',
        'waiting' => 'Waiting for the bank…',
        'check_again' => 'Check again',
    ],

    'fake_bank' => [
        'badge' => 'Test bank',
        'title' => 'Test payment',
        'text' => 'This is a test page. No real money is charged. Choose the result.',
        'purpose' => 'Purpose',
        'amount' => 'Amount',
        'approve' => 'Approve payment',
        'decline' => 'Decline',
        'done' => 'This payment is already finished.',
    ],

    'receipt' => [
        'subject' => 'Payment received · invoice :number',
        'line' => 'Thank you! ":title" is paid (:amount) and already active. The invoice is attached.',
        'action' => 'View payments',
    ],

    'refund' => [
        'subject' => 'Refund · :number',
        'line_card' => ':amount was returned to your card (order :number). It may take a few days to show in your bank.',
        'line_balance' => ':amount was returned to your balance (order :number).',
    ],

    'invoice_issued' => [
        'subject' => 'New invoice on infokobuleti.com',
        'line' => 'An invoice was issued to you: ":title", :amount.',
        'action' => 'Pay the invoice',
    ],

    'expiring' => [
        'subject' => ':kind ends tomorrow',
        'line' => '":title": the service ends on :date. Extend it to keep the listing on top.',
        'line_package' => 'Your business package ends on :date. Extend it to keep the badge, logo and limit.',
        'action' => 'Extend',
        'kinds' => [
            'vip' => 'VIP',
            'super_vip' => 'Super VIP',
            'featured_home' => 'Homepage placement',
            'auto_bump' => 'Auto-bump',
            'business_package' => 'Business package',
        ],
    ],

    'notifications' => [
        'receipt' => 'Payment received · :number',
        'refunded' => 'Refunded :amount',
        'refunded_card' => 'The money returns to your card within a few days.',
        'refunded_balance' => 'The money is back on your balance.',
        'invoice' => 'New invoice: :amount',
        'expiring' => ':kind ends tomorrow',
        'expiring_text' => '":title" · :date',
        'expiring_package' => 'Ends on :date',
    ],

    'invoice' => [
        'title' => 'Invoice',
        'heading' => 'Invoice / receipt',
        'status' => 'Status',
        'seller' => 'Seller',
        'seller_address' => 'Kobuleti, Georgia',
        'tax_id' => 'Tax ID',
        'iban' => 'Account',
        'buyer' => 'Buyer',
        'no_name' => 'Customer',
        'customer_id' => 'Customer ID: :id',
        'item' => 'Service',
        'qty' => 'Qty',
        'amount' => 'Amount',
        'refunded' => 'Refunded',
        'total' => 'Total paid',
        'footer' => 'This invoice was created automatically and needs no signature. Questions: contact@infokobuleti.com',
    ],
];
