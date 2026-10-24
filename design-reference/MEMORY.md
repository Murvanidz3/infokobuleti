# Memory — design decisions log (infokobuleti.com)

## Files
- `Homepage.dc.html` — finished homepage (desktop 1440 + responsive; Tweaks: Preview Desktop/Mobile, card style Border/Shadow, hero overlay strength, scroll animations).
- `Accent Color Options.dc.html` — accent color comparison (1a coral chosen; 1b sand gold, 1c sea teal rejected).
- `DESIGN-PROMPT.md` — full English prompt/spec for continuing in another workspace.

## Decisions (in order)
1. Base brief: navy `#0a1a2f` dominant, light airy backgrounds, Noto Sans/Serif Georgian, 12–16px radius cards, line icons, subtle motion.
2. Category tiles: equal size, centered, single-line text, hover fill (now coral), 8 per row desktop / 4 tablet / 2 mobile. „სახლები და მიწა“ shortened to „სახლები“ to fit one line.
3. Hero search tabs got individual hues: hotels `#2f6f9f`, real estate `#2e7d6b`, classifieds `#b5622f`, jobs `#6a4f9e` (colored icon chip; active tab fills with its hue).
4. Header CTA made livelier: coral pill, white circle with “+”, glow, lift on hover.
5. **Second brand color = coral `#d4602a`** (user liked it; noted similarity to Bank of Georgia orange). Applied to all primary buttons, search button, CTA banner button, category hover, hearts, “ახალი” badge, carousel arrow hover, link hover, footer social hover; prices in `#b44f1e`. Navy stays for header/footer/headings/ratings/sale-rent badges/active filters.
6. Logo: icon mark (sun over waves) tried, then replaced by a pure wordmark in Outfit — “info” 300 / “kobuleti” 700, coral glowing sun-dot on the last “i”.

## Open items
- Real photography needed for all image slots.
- KA/EN/RU switcher is visual only; no EN/RU copy yet.
- Possibly unify search-tab hues with the navy/coral system later.
- Next pages not yet designed: listings/search results, hotel detail, property detail, classifieds, vacancies, guide article, news, add-listing form, account.
