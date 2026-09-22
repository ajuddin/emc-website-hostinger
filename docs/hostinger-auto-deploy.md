# Automatic deployment: GitHub → Hostinger

Hostinger has this built in. You do **not** need GitHub Actions, SSH keys, FTP
credentials or a webhook — the Hostinger GitHub App manages the webhook for you and
auto-deployment is on by default once connected.

Path in hPanel: **Websites → your site → Dashboard → Advanced → Git**.

---

## Read this before you touch anything

This repository is the **theme root**. `functions.php`, `style.css` and
`template-*.php` sit at the top level, so the deploy directory must be:

```
public_html/wp-content/themes/emc-theme
```

Not `public_html`. Pointing it at `public_html` would drop theme files over your
WordPress install and break the site.

### The one risk that matters for you

Your current workflow is "commit to GitHub for tracking, then apply to Hostinger by
hand". That means the live theme folder and the repository have almost certainly
**drifted apart** — any fix made directly on the server that was never committed back
exists only on Hostinger.

The moment you enable auto-deploy, the repository becomes the source of truth. Every
deploy replaces files in the target directory with the repo's version. Uncommitted
server-side edits are gone.

**Do Step 1 properly. It is the whole job. Everything after it is clicking buttons.**

---

## Step 1 — Reconcile the live theme against the repo

1. **Download the live theme.** hPanel → File Manager → navigate to
   `public_html/wp-content/themes/` → right-click `emc-theme` → Compress → download
   and unzip it, say to `~/Desktop/emc-theme-live`.

2. **Diff it against `main`:**

   ```bash
   cd ~/Documents/all_repos/emc-website-hostinger
   git checkout main
   diff -ru --exclude=.git --exclude=.DS_Store \
        ~/Desktop/emc-theme-live . | tee ~/Desktop/theme-drift.txt
   ```

3. **Read `theme-drift.txt` carefully.** Three kinds of finding:

   | What you see | What it means | What to do |
   |---|---|---|
   | `Only in ~/Desktop/emc-theme-live: <file>` | Exists live, not in git | Copy it into the repo and commit it, or confirm it is disposable |
   | `Only in .: <file>` | In git, not live | Expected for anything not yet deployed |
   | `diff -ru ... <file>` with content | The file differs | Decide which version is correct, then make the repo match |

4. **Commit everything you want to keep.** When `diff` reports only files you
   deliberately have not deployed yet, you are ready.

> Do not skip to Step 2 with unexplained differences in that file. Once auto-deploy
> runs, the live versions are overwritten.

---

## Step 2 — Tidy what would be published

Everything in the repo lands in the live theme folder and is reachable over the web.
Before connecting:

```bash
rm -f finish-git-setup.sh          # a shell script should not be publicly downloadable
git rm --cached finish-git-setup.sh 2>/dev/null || true
```

`docs/*.md` will also be publicly readable at
`https://…/wp-content/themes/emc-theme/docs/`. Markdown is not executed so this is
low risk, but if you would rather it were not public, move the docs to a separate
repository or a `docs` branch that is never deployed.

Add a `.gitattributes` with `export-ignore` if you prefer — though note Hostinger
deploys the working tree, not a `git archive`, so the reliable way to keep something
off the server is simply not to commit it to the deployed branch.

---

## Step 3 — Take a backup you can actually restore from

hPanel → **Files → Backups** → create a manual backup, **and** keep the
`emc-theme` zip from Step 1. Two independent copies, because Step 4 is the
irreversible one.

---

## Step 4 — Connect the repository

1. hPanel → **Websites** → your site → **Dashboard**.
2. Sidebar → **Advanced** → **Git**.
3. Click **Connect with GitHub**. A popup installs the Hostinger GitHub App.
4. Grant access to `ajuddin/emc-website-hostinger` specifically — not "all
   repositories".
5. Select the repository, then click **Next**.
6. Set the deployment options:
   - **Branch** — see Step 5 before choosing
   - **Deploy directory** — click **Change** and set
     `public_html/wp-content/themes/emc-theme`
7. Click **Deploy**. Logs stream live; the **Deployments** tab keeps the history with
   branch, commit ID, timestamp and status.

From then on: push to that branch → GitHub notifies Hostinger → Hostinger pulls →
files are replaced in the deploy directory. The dashboard shows an
**Auto-deployment** chip while the connection is healthy.

---

## Step 5 — Choose the branch deliberately

You asked about deploying `main`. Both options work; they differ in how much rope you
have.

**Option A — deploy `main`.** Merging a PR puts it live within seconds. Simple, and
fine for a low-traffic site where a mistake is cheap.

**Option B — deploy a `production` branch (recommended here).** `main` stays your
integration branch; you merge into `production` when you actually want to ship.

```bash
git checkout main
git checkout -b production
git push -u origin production
```

Then connect `production` in Step 4. To release:

```bash
git checkout production && git merge main && git push
```

Hostinger's own guidance suggests exactly this split — deploy `main` for
always-live workflows, or a dedicated `production` branch to batch changes and control
what ships.

Option B is worth the extra command here because this site takes real payments. It
means an accidental merge to `main` does not immediately change a live donation page.

---

## Step 6 — Verify, then deliberately break something

1. Check the site loads and the Membership page renders.
2. Make a trivial visible change on the deploy branch — a word in a heading — push,
   and confirm it appears live within a minute or so.
3. Revert it and push again. Confirm the revert also lands.

If step 2 does nothing, check the **Deployments** tab first: a deploy that never
started is a connection problem, a deploy marked failed has a log to read.

---

## Things that will bite you later

**Deploys do not clear the cache.** If Hostinger caching or a caching plugin is on,
you may deploy successfully and still see the old page. Clear the cache after deploys
that change CSS or templates.

**No build step runs.** Hostinger serves what is committed. That is fine here —
there is no build in this theme — but it means you can never commit source that needs
compiling.

**Editing theme files in wp-admin is now pointless.** Appearance → Theme File Editor
changes will be silently reverted on the next deploy. Consider disabling it by adding
`define( 'DISALLOW_FILE_EDIT', true );` to `wp-config.php`.

**Uploads and the database are not covered.** Only the theme folder is deployed.
Media, posts, pages, Customizer settings and membership records live in the database
and in `wp-content/uploads` — they still need their own backups.

**Whether extra files are deleted is unconfirmed.** Hostinger documents that a deploy
"replaces existing files in that directory". It does not say whether a file that
exists on the server but not in the repo is removed or left alone. Assume it may be
removed — which is another reason Step 1 matters. If you want certainty, put a
throwaway file like `zz-test.txt` in the live theme folder, deploy, and see whether it
survives.

---

## Rolling back a bad deploy

Because the repo is the source of truth, roll back with git rather than the file
manager:

```bash
git checkout production
git revert <bad-commit-sha>
git push
```

That triggers a fresh deploy of the corrected state. Use **Redeploy** on the Git page
if the webhook did not fire.

For a true emergency — site down, no time to think — restore the hPanel backup from
Step 3, then sort the repository out afterwards.

---

## Sources

- [How to deploy a Git repository in Hostinger](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/)
- [Git — Hostinger Documentation](https://docs.hostinger.com/websites/git)
