# Deploy — preview only

Site: **icomply-professional-services**

| Item | Value |
|---|---|
| Preview URL | https://icomply-professional-services.netlify.app |
| `NETLIFY_SITE_ID` | `dc86da59-3989-4b57-bac1-d214b9ca2072` |
| Repo | https://github.com/icomplypropertyservices/professional-services-web |
| Publish directory | `dist/` |
| Build | `php website/bin/static-export.php && php website/bin/check-static-export.php` |
| PHP | 8.3 |

## Secrets

Already configured on `icomplypropertyservices/professional-services-web`:

- `NETLIFY_AUTH_TOKEN`
- `NETLIFY_SITE_ID` = `dc86da59-3989-4b57-bac1-d214b9ca2072`

Do not commit the token. If a new environment is missing them, add both in GitHub → Settings → Secrets and variables → Actions. Create the Netlify site only with an existing token; this document does not contain one.

Suggested site name if the preview site ever has to be recreated: `icomply-professional-services`.

## What GitHub Actions does

- `.github/workflows/netlify-preview.yml` — pull requests. Draft deploy with alias `pr-<number>`. No `--prod`.
- `.github/workflows/netlify-deploy.yml` — push to `main`. Draft deploy with alias `preview`. No `--prod`.

Both workflows refuse to run unless `NETLIFY_SITE_ID` is exactly `dc86da59-3989-4b57-bac1-d214b9ca2072`.

Alias URLs look like:

- `https://preview--icomply-professional-services.netlify.app`
- `https://pr-N--icomply-professional-services.netlify.app`

## Not in scope

- Do not attach `icomplyprofessionalservices.co.uk` or `www`.
- Do not add a Netlify production custom domain.
- Do not run `netlify deploy --prod`.
- Do not change DNS. Production cutover waits until Jack says go.

`www` → apex 301 is a Jack-go step, after the domain is intentionally attached. It is not configured in this preview.

## Local check before relying on Actions

```bash
php website/bin/static-export.php
php website/bin/check-static-export.php
```
