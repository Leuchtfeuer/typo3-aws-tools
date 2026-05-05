# TYPO3 14 Upgrade TODO

Extension: `aws_tools` (leuchtfeuer/aws-tools)

Current version: 13.0.0 (TYPO3 ^13.4)

Target: TYPO3 ^14.3

---

## Pre-TYPO3 14: Fix in TYPO3 13 Branch First

These items are deprecated in TYPO3 13 and will break in TYPO3 14. They should be resolved on the current `master` branch before starting the TYPO3 14 upgrade.

---

### P1. `$GLOBALS['TSFE']->config` deprecated in TYPO3 13

**File**: `Classes/Middleware/ContentReplaceMiddleware.php:24`

**Issue**: `$GLOBALS['TSFE']->config['config']['tx_awstools.']` — in TYPO3 13, `TypoScriptFrontendController` properties are marked `@internal` and deprecated (Deprecation-105230, Breaking-102621). The PSR-15 middleware already receives `$request` as a parameter, making the migration straightforward.

**Reference**: TYPO3 13 — Deprecation-105230, Breaking-102621

- [x] Replace `$GLOBALS['TSFE']->config['config']['tx_awstools.']` with `$request->getAttribute('frontend.typoscript')->getConfigArray()['config.']['tx_awstools.'] ?? []`

---

### P2. Missing return type on event listener method
**File**: `Classes/EventListener/FileInvalidationEventListener.php:33`

**Issue**: `invalidateOnBackendUploadReplace(BeforeFileReplacedEvent $event)` has no return type, while `invalidateFile()` and `invalidateFileOnDeleting()` both declare `: void`. This is a code consistency issue — there is no specific TYPO3 13 breaking change for return types on event listener methods.

**Reference**: PHP 8.2 strict typing / code consistency

- [x] Add `: void` return type to `invalidateOnBackendUploadReplace()` — already done

---

## Breaking Changes

### 1. Backend module parent identifier renamed
**File**: `Configuration/Backend/Modules.php:7`

**Issue**: `'parent' => 'tools'` — the `tools` top-level module identifier was renamed to `system` in TYPO3 14. Using the old name causes errors during `typo3 cache:warmup`.

**Reference**: Feature-107628

- [x] Change `'parent' => 'tools'` to `'parent' => 'system'`

---

### 2. `$GLOBALS['TSFE']` removed from ContentReplaceMiddleware
**File**: `Classes/Middleware/ContentReplaceMiddleware.php:24`

**Issue**: `$GLOBALS['TSFE']->config['config']['tx_awstools.']` — `TypoScriptFrontendController` has been stripped of all properties in TYPO3 14. Accessing `$GLOBALS['TSFE']->config` will fail.

**Reference**: Breaking-107831

- [x] Replace `$GLOBALS['TSFE']->config['config']['tx_awstools.']` with access via `$request->getAttribute('frontend.typoscript')?->getConfigArray()['tx_awstools.']` — already done

---

### 3. `ProcessFileListActionsEvent` actions now use Buttons API
**File**: `Classes/EventListener/EditIconsEventListener.php`

**Issue**: `$event->getActionItems()` / `$event->setActionItems()` — the action system in the file list was reworked to use typed button components (Buttons API) instead of raw HTML strings. The current listener creates an HTML anchor string and injects it as `$actionItems['awstools_invalidate']`.

**Reference**: Breaking-107884

- [x] Refactor `manipulateEditIcons()` to use `ComponentFactory` for button creation instead of building raw HTML strings
- [x] Inject `ComponentFactory` via constructor DI
- [x] Replace `$event->getActionItems()` / `$event->setActionItems()` with new `$event->setAction()` API using `ActionGroup` enum

---

### 4. `typo3/cms-frontend/output-compression` middleware removed
**File**: `Configuration/RequestMiddlewares.php:14`

**Issue**: The middleware is registered with `'before' => ['typo3/cms-frontend/output-compression']`. TYPO3 14 removes the `output-compression` middleware entirely (application-level compression removed). Referencing a non-existent middleware in `before`/`after` causes registration errors.

**Reference**: Breaking-107943

- [x] Remove `'before' => ['typo3/cms-frontend/output-compression']` from `RequestMiddlewares.php`

---

### 5. `$GLOBALS['TYPO3_REQUEST']` no longer synchronized
**File**: `Classes/EventListener/CdnEventListener.php:31`

**Issue**: The constructor accesses `$GLOBALS['TYPO3_REQUEST']`. In TYPO3 14 the BC layer synchronizing `$GLOBALS['TYPO3_REQUEST']` with the PSR-7 request was removed. Additionally, accessing the request in a singleton constructor is problematic since the container may be built before any request is available.

**Reference**: Breaking-108113

- [x] Refactor `CdnEventListener` to initialize lazily — moved initialization from constructor to event handler method with `$this->initialized` guard
- [x] Replace `$GLOBALS['TYPO3_REQUEST']` access with a `resolveRequest()` helper that reads `$GLOBALS['TYPO3_REQUEST'] ?? null` safely

---

### 6. `ResourceFactory::getFileObjectByStorageAndIdentifier()` removed
**File**: `Classes/Controller/BackendController.php:75`

**Issue**: `ResourceFactory::getFileObjectByStorageAndIdentifier($storage, $identifier)` is listed as removed in TYPO3 14. The replacement is to use `StorageRepository` / `ResourceStorage` directly.

**Reference**: Important-107735

- [x] Replace `$this->resourceFactory->getFileObjectByStorageAndIdentifier($storage, $identifier)` with `$this->storageRepository->findByUid($storage)?->getFile($identifier)`

---

### 7. Extbase backend module TypoScript must be registered globally
**File**: `Configuration/TCA/Overrides/sys_template.php`, `Configuration/TypoScript/setup.typoscript`, `ext_localconf.php`

**Issue**: Extbase backend modules no longer guess a page ID to load TypoScript from; they rely on global TypoScript only. The `module.tx_awstools` TypoScript is currently only available as a static template that the integrator must include. Backend modules need this TypoScript to be registered globally.

**Reference**: Breaking-105728

- [x] Add `ExtensionManagementUtility::addTypoScriptSetup(...)` in `ext_localconf.php` to register the `module.tx_awstools` configuration as global TypoScript

---

## Version / Metadata Updates

### 8. Update `composer.json` version constraints
**File**: `composer.json:29-31`

**Issue**: All `typo3/*` requirements are pinned to `^13.4`. They must be updated for TYPO3 14 compatibility.

- [x] Change `"typo3/cms-backend": "^13.4"` → `"^14.3"` — already done
- [x] Change `"typo3/cms-core": "^13.4"` → `"^14.3"` — already done
- [x] Change `"typo3/cms-extbase": "^13.4"` → `"^14.3"` — already done

---

### 9. Update `ext_emconf.php` version constraints
**File**: `ext_emconf.php:8`

**Issue**: `'typo3' => '13.4.0-13.4.99'` must be updated.

- [x] Change `'version'` to `14.0.0` — already done
- [x] Change `'typo3' => '13.4.0-13.4.99'` to `'typo3' => '14.3.0-14.3.99'` — already done

---

### 10. Update `composer.json` description for extension title
**File**: `composer.json:3`

**Issue**: In TYPO3 14 the extension title in Extension Manager is derived from `composer.json` `description` using a ` - ` delimiter. The current description does not use this delimiter so the full description string becomes the title.

**Reference**: Breaking-108304

- [x] Update `"description"` to `"Amazon Web Services (AWS) Toolbox - This extension connects your TYPO3 instance to Amazon CloudFront..."` (title before ` - `, description after)

---

## Review / Verify

### 11. Review `CdnEventListener` TypoScript access pattern

**File**: `Classes/EventListener/CdnEventListener.php:62-73`

**Issue**: The constructor calls `ConfigurationManagerInterface::getConfiguration(CONFIGURATION_TYPE_FULL_TYPOSCRIPT)` which requires a frontend Extbase context. This may fail or return empty data in contexts where no frontend request is active. Review whether this still works as expected in TYPO3 14 or needs adjustment.

- [x] Verified TypoScript configuration access — moved initialization to event handler method (item 5), resolves context issue

---

### 12. Verify `ResourceFactory::getFolderObjectFromCombinedIdentifier()` availability
**File**: `Classes/Controller/BackendController.php:69`

**Issue**: `ResourceFactory::getFolderObjectFromCombinedIdentifier()` — the Important-107735 changelog entry lists multiple `ResourceFactory` methods as removed. Verify whether this method is also removed.

**Reference**: Important-107735

- [x] Check whether `ResourceFactory::getFolderObjectFromCombinedIdentifier()` still exists in TYPO3 14 — confirmed still present, no migration needed

---

### 13. Missing `typo3/cms-filelist` composer dependency
**File**: `composer.json:27-32`

**Issue**: `Classes/EventListener/EditIconsEventListener.php` and `Configuration/Services.yaml` reference `TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent`, but the extension does not declare `typo3/cms-filelist` in its `composer.json` `require` section. The class only loads if another package pulls in `typo3/cms-filelist`. With the rework of the action API in TYPO3 14 (Breaking-107884) the dependency must be explicit.

- [x] Add `"typo3/cms-filelist": "^14.3"` to `composer.json` `require`

---

### 14. Add minimum PHP version requirement to `composer.json`
**File**: `composer.json:27-32`

**Issue**: `composer.json` does not declare a `php` constraint. TYPO3 14 requires PHP 8.3 or higher. While the platform indirectly enforces this, an explicit constraint avoids accidental installation on unsupported PHP versions.

- [x] Add `"php": "^8.3 || ^8.4"` to `composer.json` `require`

---

### 15. Verify Fluid templates against Fluid 5.0
**Files**: `Resources/Private/Layouts/Backend.html`, `Resources/Private/Templates/Invalidation/Index.html`, `Resources/Private/Partials/Invalidation/Table.html`

**Issue**: Fluid 5.0 (shipped with TYPO3 14) drops several deprecated patterns: CDATA blocks, `_`-prefixed variables (now reserved for Fluid internals), `renderStatic()`, and `LenientArgumentProcessor` fallback (Breaking-108148). A scan of the existing templates shows no immediate violations, but the templates should still be regression-tested in a TYPO3 14 environment.

**Reference**: Breaking-108148

- [ ] Run `vendor/bin/typo3 fluid:analyse` against the extension once running on TYPO3 14
- [ ] Verify the backend module still renders correctly (flash messages, form, distribution table)

---

### 16. Migrate legacy `.php_cs` configuration file
**File**: `.php_cs`

**Issue**: The PHP-CS-Fixer configuration uses the legacy file name `.php_cs`. Recent versions of `friendsofphp/php-cs-fixer` (3.x and later) expect `.php-cs-fixer.dist.php`. Not a TYPO3 14 breaking change, but housekeeping that fits the upgrade scope.

- [ ] Rename `.php_cs` to `.php-cs-fixer.dist.php` (and update any CI references)

---

### 17. Deprecation: `ext_tables.php` deprecated
**File**: `ext_tables.php`

**Issue**: `ext_tables.php` is deprecated in TYPO3 14.3 and will be removed in TYPO3 15. The file currently loads a JavaScript module via `PageRenderer::loadJavaScriptModule()`.

**Reference**: Deprecation-109438

- [ ] Move the `PageRenderer::loadJavaScriptModule()` call from `ext_tables.php` to an appropriate event listener (e.g. listening to `BeforePageRendererRenderingEvent` or using a different mechanism) or use `Configuration/JavaScriptModules.php` plus an event-based approach

---

### 18. Deprecation: `GeneralUtility::getIndpEnv()` deprecated
**File**: `Classes/EventListener/CdnEventListener.php:44`

**Issue**: `GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR')` is deprecated in TYPO3 14.3. The `NormalizedParams` from the PSR-7 request should be used instead.

**Reference**: Deprecation-109551

- [x] Replace `GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR')` with `$request->getAttribute('normalizedParams')?->getRequestDir()` in `CdnEventListener::resolveLanguage()`