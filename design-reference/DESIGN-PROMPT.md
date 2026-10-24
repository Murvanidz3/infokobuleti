# infokobuleti.com — Design Continuation Prompt

Copy everything below into a new workspace to keep designing pages for infokobuleti.com in the exact same style.

---

You are continuing the design of **infokobuleti.com**, an all-in-one local portal for Kobuleti, a seaside resort town on Georgia's Black Sea coast (Adjara). The portal covers hotels & guesthouses, real estate (sale/rent), classifieds, job vacancies, and a city guide (beaches, sights, events, news, useful info). The homepage is finished; every new page must look like it belongs to the same product.

## Audience & language
- Tourists planning a summer stay, local residents, property buyers/investors, job seekers, local businesses.
- **All UI text in Georgian (ქართული).** Header language switcher: KA / EN / RU (pill segmented control).
- Use realistic Georgian placeholder content: real-sounding hotel names, prices in ₾ (and $ for real estate), Kobuleti streets (შ. რუსთაველის ქ., დ. აღმაშენებლის გამზ., თამარ მეფის ქ., ი. ჭავჭავაძის ქ., ჩაქვის ქ.), nearby places (ციხისძირი, ჩაქვი, ქობულეთის დაცული ტერიტორიები, ისპანის ჭაობები, კინტრიში, პეტრას ციხე). Never lorem ipsum.

## Visual direction
Modern, clean, minimal, calm and airy. Lots of white space. Navy is the dominant brand color; coral is the single accent for actions.

### Colors
| Role | Hex |
|---|---|
| Brand navy (header, footer, headings, key text, rating badges, active filters, sale/rent badges) | `#0a1a2f` |
| Navy hover / lighter navy | `#1b304d` |
| Secondary text | `#3a4a5f` |
| Borders, subtle fills, icon chips | `#e8edf3` |
| Stronger borders, outlines | `#c9d3df` |
| Alternating section background | `#f5f7fa` |
| Base background | `#ffffff` |
| **Accent coral** (primary buttons, CTA, search button, hover fills, hearts, “ახალი” badge, link hover) | `#d4602a` |
| Coral hover (lighter) | `#e06d36` |
| Coral pressed / deeper | `#bf5323` |
| Coral text on white (prices) | `#b44f1e` |
| Coral tint fill | `#fcece2` |
| Muted sea-blue (sea temperature only, very sparing) | `#2f6f9f` |

Search-tab category hues (only inside the hero search tabs): hotels `#2f6f9f`, real estate `#2e7d6b`, classifieds `#b5622f`, jobs `#6a4f9e`.

No other bright colors. No gradients except the hero photo overlay.

### Typography
- UI/body: **Noto Sans Georgian** (400/500/600/700).
- Headings: **Noto Serif Georgian** (600). Hero h1 clamp(34px → 66px), line-height 1.18; section h2 clamp(28px → 40px); card titles 15.5–18px sans 600.
- Logo only: **Outfit** (300 + 700).
- Body 15–17px, line-height 1.6–1.7. Secondary text `#3a4a5f`.

### Logo (wordmark only, no icon)
`info` in Outfit 300, white at 70% opacity + `kobuleti` in Outfit 700 white, tight tracking (-0.025em). The dot of the final “i” is replaced with a **coral glowing circle** (`#e8703a`, soft glow) like a sun over the sea. 26px in header, 30px in footer.

### Layout & spacing
- Desktop-first at 1440px; content max-width 1408px; side padding clamp(20px → 64px).
- Section vertical padding clamp(56px → 104px). Sections alternate white / `#f5f7fa`.
- Section header: serif h2 + one-line subtitle on the left; “ყველა …” link with arrow on the right (arrow gap grows and text turns coral on hover).

### Components
- **Header:** navy, sticky; shrinks 78→62px on scroll and becomes `rgba(10,26,47,0.86)` with backdrop blur. Nav links white 78% → 100% on hover. KA/EN/RU pill switcher (active = white pill, navy text). CTA “+ განცხადების დამატება”: coral pill with a white circle holding a coral “+”, soft coral glow, lifts 2px on hover. Below 1240px → hamburger (44px square, thin white border) opening a full-width drawer.
- **Hero:** full-bleed photo with navy overlay gradient (≈70% bottom → 30% top). Small location line with pin icon, large serif white h1, short subtitle. Frosted glass search box (`rgba(255,255,255,0.14)`, 1px white 28% border, blur 18px, radius 20px) with icon tabs and a white field panel; fields are `#f5f7fa` tiles (label 12px + value 15px 600). Coral “ძიება” button. On mobile it collapses to one search input + coral arrow button.
- **Buttons:** Primary = coral bg, white text, radius 12px, hover lighter coral + 1–2px lift + coral shadow. Secondary = white bg, 1px coral (or navy) border. Tertiary = navy solid. Pills (filters/toggles) radius 999px; active = navy bg, white text.
- **Cards:** radius 16px, 1px `#e8edf3` border (or soft shadow variant), white bg. Hover: lift -4px + shadow `0 20px 40px -16px rgba(10,26,47,0.22)`; image inside zooms to 1.05 over 0.7s. Heart button: 38px white frosted circle, coral heart (filled when saved).
- **Hotel card:** 4:3 photo, name + navy rating chip (e.g. 9.4), address with pin, distance to sea with wave icon, divider, price in coral `₾220 / ღამე` + review count. Horizontal snap carousel with round outline arrow buttons (hover → coral fill).
- **Real-estate card:** 3:2 photo, navy “იყიდება” / “ქირავდება” pill badge top-left, price coral 22px bold + converted currency, title, location, feature row (m², rooms, floor) with icons.
- **List cards (classifieds/vacancies):** white panel with row dividers, 48px `#e8edf3` icon chip, title + meta, right-aligned price/salary + time; vacancy type pill (სრული განაკვეთი / სეზონური).
- **Category tiles:** equal height, centered; 52px outlined circle icon, name + count (single line each). Hover: coral fill, white text/icon, lift -4px.
- **Editorial guide cards:** full-bleed photo with navy bottom gradient, glass tag pill, serif white title; one large feature card + smaller cards.
- **Info widgets:** small white cards on `#f5f7fa`, 34px icon chip, big numeric value, 13px caption.
- **CTA banner:** full-width navy, serif white heading, muted white text, one coral button.
- **Footer:** navy; wordmark, short description, round outlined social icons (hover → coral fill), link columns in `#c9d3df` (hover white), contacts with icons, App Store / Google Play outline buttons, copyright row.

### Icons & imagery
- Line icons only, single 1.5px stroke, rounded caps/joins, navy (white on dark/coral). No emoji, no clip art, no filled icons (except saved heart).
- High-quality real photos; use placeholders until real photos are provided.

### Motion (subtle, smooth)
- Card lift + soft shadow on hover, image zoom inside cards, sections fade/slide up 24px on scroll into view (0.8s), tab content cross-fades (~0.2s), header compacts on scroll. Nothing flashy.

### Responsive
- Breakpoints by container width: ≥1240 full nav; <1100 tablet (2-col grids, search fields 2×2); <720 mobile (stacked cards, swipeable carousels at ~82% card width, hamburger, single search field, 2-col category tiles).
- Mobile hit targets ≥ 44px.

## Task
Using exactly this system, design: **[describe the next page here — e.g. hotel listing/search results page, hotel detail page, real-estate listing page, property detail, classifieds, vacancy list/detail, city-guide article, news article, add-listing form, user account]**. Reuse the same header and footer, keep the same card anatomy, spacing, type scale and hover behavior, and keep all copy in Georgian.
