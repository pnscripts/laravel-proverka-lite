# Proverka Lite: Packagist submission

Nothing has been submitted. Packagist account `PNScripts` already exists (pn-invoice, pn-shop, pn-shop-core listed).

1. Owner: create the public repo `github.com/pnscripts/laravel-proverka-lite`, push `main` and the tag `v1.0.0` (already tagged locally).
2. Owner: https://packagist.org/packages/submit → repository URL → Check → Submit. Vendor `pnscripts` is already ours, so the name is accepted.
3. Auto-update: Packagist installs the GitHub hook automatically when the account is connected to GitHub; otherwise add the webhook `https://packagist.org/api/github?username=PNScripts` (content type JSON, secret = Packagist API token, push event).
4. GitHub repo settings: description = composer description, topics `laravel validation bulgaria egn eik`, social preview `listing/social-preview-1280x640.png`.

`composer validate --strict` passes. Versions come from tags (no `version` field). The dist ZIP excludes tests/listing/tools via .gitattributes.
