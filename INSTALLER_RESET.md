# Réinitialisation de l'installateur CheckTime

## Comment afficher à nouveau la page d'installation

L'installateur vérifie l'existence d'un fichier de verrouillage pour savoir si l'application est déjà installée. Pour réinitialiser et revoir la page d'installation, suivez les étapes ci-dessous.

### Étape 1 : Supprimer le fichier de verrouillage

Le fichier `storage/app/.installed` empêche l'accès à l'installateur. Supprimez-le :

**Windows (PowerShell) :**
```powershell
Remove-Item "storage\app\.installed" -Force
```

**Linux / macOS :**
```bash
rm -f storage/app/.installed
```

### Étape 2 : Nettoyer les caches

Exécutez les commandes suivantes à la racine du projet :

**Windows (PowerShell) :**
```powershell
php artisan config:clear; php artisan cache:clear; php artisan view:clear
```

**Linux / macOS :**
```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

### Étape 3 : Accéder à l'installateur

Ouvrez votre navigateur et accédez à :

```
https://votre-domaine/install
```

---

## Phase 3 — API : Authentification par General Token

La 3ème phase de l'installateur utilise désormais un **General Token** pour l'authentification API, et non plus une logique Nom d'utilisateur / Mot de passe.

### Fichiers concernés

| Fichier | Rôle |
|---------|------|
| `app/Http/Controllers/InstallerController.php` | Backend : validation de `api_url` + `api_token`, test de connexion via header `Authorization: Token <token>` |
| `resources/views/installer/index.blade.php` | Frontend : champ unique "Token API (General Token)" dans l'étape 3 |

### Détails techniques

- **Controller** (`saveEndpoint`) : valide `api_url` (URL) et `api_token` (string, max 500 caractères)
- **Test de connexion** (`testApiConnection`) : requête GET vers `iclock/api/terminals/` avec le header `Authorization: Token <token>`
- **UI** (Step 3) : un seul champ de type password avec icône clé et bouton de visibilité, sans champs username/password
- **Environnement** : les valeurs sont sauvegardées dans `.env` sous `CHECKTIME_BASE_URL` et `CHECKTIME_TOKEN`
- **Base de données** : les valeurs sont aussi stockées dans la table `settings` (clés `api_url` et `api_token`)

### Résumé des 5 étapes de l'installateur

| Étape | Description |
|-------|-------------|
| 1 — Application | Nom, logo, fuseau horaire, langue |
| 2 — Admin | Compte administrateur (nom, email, mot de passe) |
| 3 — **API** | URL de l'API + **General Token** |
| 4 — SMTP | Configuration email (optionnel) |
| 5 — Installer | Résumé + lancement de l'installation |

---

> **Note** : Après réinstallation, vous devrez reconfigurer toutes les étapes car la session est réinitialisée.