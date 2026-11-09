# CLAUDE.md — Presellia Resource Hub

> Instructions permanentes pour toutes les sessions futures sur ce projet.

---

## Identité du projet

| Champ | Valeur |
|---|---|
| Nom | Presellia Resource Hub |
| Slug / repo | `presellia-resource-hub` |
| GitHub Repo | `tedisun/presellia-resource-hub` |
| Fichier principal | `presellia-resource-hub.php` |
| Préfixe constantes/classes | `PRH_` |
| Préfixe options WP | `prh_` |
| Préfixe metas propres | `_prh_*` |
| Custom Post Type | `presellia_ressource` (slug public: `/ressources/`) |
| Custom Taxonomy | `categorie_ressource` |
| Table de logs | `{prefix}prh_logs` |

---

## Langue et commits

- Langue des commits : **français**
- Format : `type: description` (ex: `feat: ajout filtre catégorie Ajax`)
- Types : `feat`, `fix`, `chore`, `docs`, `refactor`
- **Ne jamais committer sans demande explicite de Rodrigue**

---

## Versioning — séquence obligatoire

Avant chaque release, mettre à jour **deux endroits** dans `presellia-resource-hub.php` :
1. En-tête : `* Version: X.X.X`
2. Constante : `define( 'PRH_VERSION', 'X.X.X' );`

Puis :
1. Mettre à jour `CHANGELOG.md`
2. `git commit -m "chore: bump version X.X.X → Y.Y.Y"`
3. `git tag vY.Y.Y`
4. `git push origin main && git push origin vY.Y.Y`
-> GitHub Actions compile le ZIP (dossier racine `presellia-resource-hub/`) et publie la release automatiquement.
-> Depuis 1.2.0, le site détecte la release via `PRH_Updater` (dépôt privé : jeton `PRESELLIA_GITHUB_TOKEN` dans wp-config.php ou option `prh_github_token`). Plus besoin de téléverser le ZIP.

---

## Règles de base & contraintes techniques

- PHP 8.0+ requis
- Pas de Composer / vendor externe
- WooCommerce HPOS : compatibilité déclarée via `FeaturesUtil::declare_compatibility()`
- Sécurité :
  - Nonces sur tous les formulaires et endpoints Ajax
  - Vérification `current_user_can()` pour toutes les actions admin
  - `sanitize_*` strict sur les entrées et `esc_*` sur toutes les sorties
  - `hash_equals(hash('sha256', $stored), hash('sha256', $provided))` pour la clé API MCP
- Intégration FluentCRM :
  - Utilise l'API PHP native : `FluentCrmApi('contacts')->createOrUpdate(...)`
  - Attachement du tag FluentCRM et logging de l'événement dans `prh_logs`

---

## Metas de la ressource (`presellia_ressource`)

| Meta | Scope | Rôle |
|---|---|---|
| `_prh_format_spec` | Ressource | Ex: "PDF · 18 pages", "ISO · 4.6 Go", "Script PowerShell" |
| `_prh_compatible_os` | Ressource | Ex: "Windows 11 / 10", "Tous systèmes", "Office 2016-2024" |
| `_prh_media_type` | Ressource | `image`, `youtube`, `vimeo` ou `video_url` |
| `_prh_media_url` | Ressource | URL vidéo YouTube/Vimeo ou image de démo illustrative |
| `_prh_download_url` | Ressource | URL directe du téléchargement du fichier ou script |
| `_prh_access_type` | Ressource | `free` (téléchargement direct) ou `gated` (capture lead FluentCRM) |
| `_prh_crm_tag` | Ressource | Tag slug ou ID dans FluentCRM pour segmenter le lead |
| `_prh_linked_product_id`| Ressource | ID du produit WooCommerce lié pour l'offre "Grand Slam" |
| `_prh_offer_badge` | Ressource | Badge promo de l'offre (ex: "OFFRE COMPLÉMENTAIRE RECOMMANDÉE") |
| `_prh_offer_custom_title`| Ressource | Titre surchargé pour l'offre (ou nom du produit par défaut) |
| `_prh_value_stack` | Ressource | Points clés / livrables (séparés par saut de ligne) |
| `_prh_cta_label` | Ressource | Texte du bouton d'accès après le formulaire et dans l'e-mail (défaut : « Ouvrir la ressource ») |
| `_prh_email_subject` | Ressource | Objet de l'e-mail de livraison (variables `{prenom}`, `{titre}`, `{lien}`) |
| `_prh_email_body` | Ressource | Texte de l'e-mail de livraison, paragraphes séparés par une ligne vide ; bouton inséré après le 2e paragraphe |

### Livraison d'une ressource `gated` (depuis 1.1.0)

1. Formulaire → `PRH_FluentCRM::handle_lead_capture()` (nonce, champ piège, 5 envois / IP / 10 min).
2. Consentement coché → contact FluentCRM créé ou mis à jour (jamais réinscrit s'il est désinscrit), tag attaché, note ajoutée.
3. Toujours → `PRH_Delivery::send()` envoie le lien par e-mail (transactionnel).
4. Réponse → la modale affiche un bouton vers `_prh_download_url` (nouvel onglet). Pas de redirection.
5. Les automatisations de relance se construisent dans FluentCRM sur le déclencheur « tag appliqué » ; le plugin ne gère que le premier e-mail.

---

## Architecture des fichiers

```
presellia-resource-hub.php           → bootstrap : constantes, autoload/requires, hooks activation, HPOS
includes/
  class-prh-activator.php            → création table prh_logs, options par défaut, upgrade idempotent
  class-prh-logger.php               → logger statique (info, warning, error) dans table MySQL prh_logs
  class-prh-cpt.php                  → CPT presellia_ressource & taxonomie categorie_ressource
  class-prh-metaboxes.php            → métadonnées admin simplifiées et soignées
  class-prh-fluentcrm.php            → pont Ajax formulaire <-> FluentCrmApi('contacts')
  class-prh-templates.php            → hook template_include pour charger les templates de l'archive et du single
  class-prh-api.php                  → API REST MCP (prh/v1) pour diagnostic et gestion par agent IA
  class-prh-admin.php                → écran d'administration (clé API, diagnostic, logs)
templates/
  archive-ressource.php              → catalogue des ressources (/ressources/) avec grille 16:9 & recherche
  single-ressource.php               → landing page Alex Hormozi (stack de valeur, lead gate, offre WooCommerce)
assets/
  css/resource-hub.css               → styles front (Dark cards, Hero, filtres, modal, offre WooCommerce)
  css/resource-admin.css             → styles back-office (metaboxes propres)
  js/resource-hub.js                 → recherche instantanée, filtrage tags, Ajax FluentCRM, modal
  js/resource-admin.js               → dynamique des champs en admin
```

---

## API REST MCP (`/wp-json/prh/v1/`)

Toutes les routes d'administration/diagnostic nécessitent le header :
`X-PRH-API-Key: {prh_api_key}`

| Méthode | Route | Description |
|---|---|---|
| `GET` | `/prh/v1/status` | Santé du plugin, version, statut FluentCRM & WooCommerce, compteurs |
| `GET` | `/prh/v1/logs` | Consultation des logs récents (params: limit, level, event) |
| `POST`| `/prh/v1/logs/clear` | Purge de la table des logs |
| `GET` | `/prh/v1/resources` | Liste complète des ressources avec toutes les métadonnées |
| `GET` | `/prh/v1/resources/(?P<id>\d+)` | Détails complets d'une ressource |
| `POST`| `/prh/v1/resources` | Création / modification d'une ressource par l'agent IA |
| `DELETE`| `/prh/v1/resources/(?P<id>\d+)`| Suppression d'une ressource |

---

## Pièges & Décisions arrêtées

- **Pas de mentions sensationnelles** : Ne JAMAIS inclure de badges du style "Sans virus" ou "Sans risque" (demande explicite de Rodrigue).
- **Template Shoptimizer** : Le thème Shoptimizer force un `#secondary` (sidebar) qui écrase les grilles. Toujours s'assurer que les templates injectent le CSS de containment pour `#secondary { display: none !important; }` et `#primary { width: 100% !important; max-width: 100% !important; }`.
- **Formulaire Lead** : Doit toujours contenir Prénom, Email et Téléphone/WhatsApp.
- **Offre Grand Slam** : Si aucun `_prh_linked_product_id` n'est renseigné, la box ne doit pas s'afficher.
