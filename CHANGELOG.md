# Changelog — Presellia Resource Hub

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

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
