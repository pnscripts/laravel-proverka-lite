// GitHub social preview (1280×640) for the Packagist / GitHub listing.
import { banner } from './brand.mjs';
export default [
  { out: 'listing/social-preview-1280x640.png', width: 1280, height: 640, html: banner(1280, 640, { name: 'Proverka Lite', tagline: 'EGN, LNCh, EIK/BULSTAT and VAT validation for Laravel 12 and 13, with English and Bulgarian messages.', chips: ['bg_egn', 'bg_lnch', 'bg_eik', 'bg_vat', 'MIT'], glyph: '✓BG', kicker: 'Free Laravel package' }) }
];
