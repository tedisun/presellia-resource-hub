# Changelog — Presellia Resource Hub

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [1.2.1] — 2026-11-09
### Ajouté
- Texte du bouton du formulaire réglable par ressource (`_prh_form_label`, champ API `form_label`), par défaut « Recevoir la ressource ».
- « Vérifier à nouveau » dans Tableau de bord > Mises à jour relit GitHub immédiatement (cache de 6 h vidé).
### Modifié
- Fin du vocabulaire « débloquer » : la fenêtre affiche le titre de la ressource, le bouton dit ce qu'on reçoit, le message final devient « C'est prêt ».

## [1.2.0] — 2026-11-09
### Ajouté
- Mises à jour automatiques depuis les releases GitHub (dépôt privé) : la nouvelle version apparaît dans Extensions et peut s'installer seule si les mises à jour automatiques sont activées pour le plugin.
- Téléchargement authentifié en deux temps (lien signé demandé avec le jeton, fichier téléchargé sans jeton).
- Réglage « Jeton GitHub » et état de la connexion dans Ressources > Réglages. La constante `PRESELLIA_GITHUB_TOKEN` (wp-config.php) est utilisée en priorité.
### Modifié
- Le ZIP de release contient maintenant un dossier racine `presellia-resource-hub/`.

## [1.1.1] — 2026-11-09
### Ajouté
- Image du produit WooCommerce dans l'encart d'offre (image | texte | prix), cliquable vers la fiche produit.
### Modifié
- Couverture de ressource : format standard 16:9 (fichier conseillé 1600 × 900, WebP), affichée à 720 px de large maximum au lieu de toute la largeur.
### Corrigé
- Case de consentement du formulaire : elle prenait la taille des champs texte (pleine largeur, 48 px) et repoussait son texte hors de la fenêtre.

## [1.1.0] — 2026-11-08
### Ajouté
- Livraison par e-mail : après le formulaire, le visiteur reçoit le lien de la ressource par e-mail (`wp_mail`, donc le SMTP du site), même s'il a décoché le consentement marketing. Objet, texte et bouton modifiables par ressource (`_prh_email_subject`, `_prh_email_body`, `_prh_cta_label`), variables `{prenom}`, `{titre}`, `{lien}`.
- Réglage « Adresse de réponse des e-mails » (`prh_reply_to`).
- Panneau de succès dans la modale : bouton d'accès qui s'ouvre dans un nouvel onglet, la page et l'offre recommandée restent affichées. Plus de redirection automatique.
- Mémoire locale des ressources débloquées : un visiteur qui revient sur le même navigateur accède directement au lien.
- FluentCRM : note « Ressource téléchargée » sur la fiche du contact.
- API REST : champs `cta_label`, `email_subject`, `email_body`.
### Modifié
- FluentCRM : un contact désinscrit, en rebond ou en plainte n'est plus réinscrit ; un prénom ou téléphone vide n'efface plus la valeur connue ; le tag est attaché après l'enregistrement (déclenche les automatisations « tag appliqué »).
- Une ressource non publiée ne se débloque que pour un éditeur connecté (tests en brouillon).
- Textes du formulaire : « Votre adresse e-mail » au lieu de « Votre Email Professionnel », mention de l'envoi par e-mail.
- Inclut les changements préparés pour 1.0.6 : refonte de l'archive `/ressources/`, nouveaux titre et sous-titre par défaut du hub, titre d'onglet correct avec Yoast.

## [1.0.5] — 2026-10-11
### Sécurité
- Formulaire de déblocage : champ piège anti-robots (soumission ignorée sans livraison ni inscription CRM) et limite de 5 soumissions par IP toutes les 10 minutes.
- API REST : `status` limité à `publish`, `draft`, `pending`, `private`.
### Ajouté
- Case de consentement email (cochée par défaut) dans le formulaire. Décochée : la ressource est livrée mais le contact n'est pas inscrit dans FluentCRM.
### Modifié
- Exemples des champs du formulaire neutres (`Votre prénom`, `vous@exemple.com`) à la place de données internes.
- Le téléphone n'est plus enregistré dans les logs (il reste dans FluentCRM).
- La purge hebdomadaire des logs (30 jours) est replanifiée automatiquement après une mise à jour.
### Supprimé
- Prix secondaire `soit env. … €/CFA` dans l'encart produit : calculé côté serveur, il restait figé dans le cache de page et ne suivait pas le sélecteur de devises.

## [1.0.4] — 2026-09-16
### Ajouté
- Sélecteur dynamique de formulaires Fluent Forms / FluentCRM dans l'écran d'édition des ressources (liste déroulante automatique avec ID et titre des formulaires sans aucun copier-coller de shortcode).
- Prise en charge du rendu automatique du formulaire Fluent Forms sélectionné dans la modale de déblocage de la ressource.

## [1.0.3] — 2026-09-15
### Modifié
- Suppression définitive du bloc `.archive-header` et des fils d'Ariane résiduels Shoptimizer sur l'archive `/ressources/` et les taxonomies, garantissant un raccordement sans espace blanc directement sous la barre de navigation.
- Mise à jour du cache-busting des feuilles de style vers la version 1.0.3.

## [1.0.2] — 2026-09-15
### Ajouté
- Affichage automatique et esthétique du prix secondaire en Euros (`soit env. 12,96 €`) sous le prix principal CFA dans la box Grand Slam Offer (synchronisé avec les taux dynamiques de WOOCS / FOX).
- Shortcode `[presellia_resource_hub]` permettant d'intégrer le catalogue de ressources sur n'importe quelle page WordPress standard ou Elementor.
### Modifié
- Suppression complète du padding supérieur et des breadcrumbs du thème Shoptimizer sur les modèles de ressource (adoption intégrale du gabarit Full width no heading).

## [1.0.1] — 2026-09-15
### Corrigé
- Correction de l'encodage UTF-8 (suppression du BOM) pour compatibilité stricte PHP/WordPress.
- Préservation des sauts de ligne lors de l'enregistrement de la Value Stack (`_prh_value_stack`) via l'API REST MCP.
- Intégration des illustrations vectorielles 16:9 par catégorie comme fallback automatique dans les templates d'archive et de single.

## [1.0.0] — 2026-09-15
### Ajouté
- Version initiale du plugin Presellia Resource Hub.
- Custom Post Type `presellia_ressource` avec slug d'archive `/ressources/`.
- Taxonomie personnalisée `categorie_ressource` avec filtres interactifs.
- Métadonnées de ressource simplifiées : spécification/format, type et URL de média (image / YouTube / vidéo), mode d'accès (gratuit direct ou lead généré FluentCRM), tag FluentCRM, produit WooCommerce lié pour Grand Slam Offer, promesse/bénéfice principal et value stack.
- Modèle d'archive `/ressources/` moderne, responsive, avec covers 16:9, barre de recherche temps réel et filtres par catégorie.
- Modèle de ressource unique `/ressources/{slug}/` style Alex Hormozi (headline axée résultat, média d'autorité, stack de valeur à puces, formulaire de capture FluentCRM avec WhatsApp/téléphone et box d'offre spéciale WooCommerce en temps réel).
- Passerelle native AJAX vers FluentCRM Pro via `FluentCrmApi('contacts')`.
- Table de log MySQL dédiée `{prefix}prh_logs` et classe statique `PRH_Logger`.
- API REST / MCP `/prh/v1/` (`/status`, `/logs`, `/resources`) sécurisée par en-tête `X-PRH-API-Key` pour diagnostic et gestion autonome par agents IA.
- Page de réglages administrateur pour la gestion de la clé API MCP, l'affichage des logs et les statistiques système.
- Compatibilité déclarée WooCommerce High-Performance Order Storage (HPOS).
