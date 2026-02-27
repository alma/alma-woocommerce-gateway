# Guide de démarrage - Tests JavaScript

## 🎯 Objectif

Ce guide vous aide à lancer rapidement les tests JavaScript pour le plugin Alma WooCommerce Gateway.

## ⚡ Quick Start

### 1. Installer les dépendances

```bash
npm install
```

### 2. Lancer les tests

```bash
npm test
```

Vous devriez voir :

```
PASS  tests/js/alma-in-page.test.js
  Alma In-Page Script
    ✓ Initialization Protection (5ms)
    ✓ hideAlmaPaymentMethods() (12ms)
    ✓ showAlmaPaymentMethods() (8ms)
    ...

Test Suites: 2 passed, 2 total
Tests:       45 passed, 45 total
Snapshots:   0 total
Time:        2.456s
```

### 3. Vérifier la couverture

```bash
npm run test:coverage
```

Vous verrez un tableau de couverture :

```
--------------------|---------|----------|---------|---------|-------------------
File                | % Stmts | % Branch | % Funcs | % Lines | Uncovered Line #s
--------------------|---------|----------|---------|---------|-------------------
All files           |   85.23 |    78.45 |   92.11 |   85.67 |
 alma-in-page.js    |   85.23 |    78.45 |   92.11 |   85.67 | 45-48,156-160
--------------------|---------|----------|---------|---------|-------------------
```

## 📊 Interpréter les résultats

### ✅ Tests qui passent

```
✓ should hide Alma payment methods without error message (12ms)
```

Tous les tests doivent passer (symbole ✓ vert).

### ❌ Tests qui échouent

```
✕ should display error message (8ms)

  Expected: "Test error"
  Received: undefined
```

Si un test échoue :

1. Lire le message d'erreur
2. Identifier le fichier et la ligne
3. Corriger le code
4. Re-lancer les tests

### 📈 Couverture de code

- **% Stmts** (Statements) : % de lignes exécutées
- **% Branch** : % de branches if/else testées
- **% Funcs** : % de fonctions appelées
- **% Lines** : % de lignes de code couvertes

**Objectifs** :

- ✅ > 80% = Excellent
- ⚠️ 60-80% = Acceptable
- ❌ < 60% = Insuffisant

## 🔍 Voir le détail de la couverture

Après `npm run test:coverage` :

```bash
# macOS/Linux
open coverage/lcov-report/index.html

# Windows
start coverage/lcov-report/index.html
```

Vous verrez un rapport HTML interactif avec :

- Fichiers couverts/non couverts (code surlignage)
- Lignes en vert (testées) / rouge (non testées)
- Branches manquantes

## 🐛 Débugger un test

### Voir les logs console

Par défaut, les `console.log` sont masqués. Pour les afficher :

```javascript
// Dans votre test
test('mon test', () => {
    console.log.mockRestore(); // Restaure console.log
    console.log('Debug info');

    // ... votre test
});
```

### Lancer un seul fichier de tests

```bash
npm test -- tests/js/alma-in-page.test.js
```

### Lancer un seul test

Ajoutez `.only` :

```javascript
test.only('should do something', () => {
    // Ce test uniquement sera exécuté
});
```

### Mode watch (auto-reload)

```bash
npm run test:watch
```

Les tests se relancent automatiquement à chaque modification de fichier.

## 📝 Structure des tests

### alma-in-page.test.js

Tests de base pour `alma-in-page.js` :

- 45+ tests
- Couvre les fonctions principales
- Tests synchrones

### alma-in-page-advanced.test.js

Tests avancés :

- Timers et async
- Scénarios complexes
- Edge cases
- ~30 tests

## 🚀 Prochaines étapes

### Ajouter de nouveaux tests

1. Créer un fichier `mon-composant.test.js` dans `tests/js/`
2. Copier la structure d'un test existant
3. Écrire vos tests
4. Lancer `npm test`

### Intégrer dans la CI

Dans `.github/workflows/ci.yml` :

```yaml
-   name: Install dependencies
    run: npm install

-   name: Run tests
    run: npm test

-   name: Check coverage
    run: npm run test:coverage -- --coverageThreshold='{"global":{"statements":80,"branches":75,"functions":85,"lines":80}}'
```

### TDD (Test Driven Development)

1. **Écrire le test** qui échoue
2. **Écrire le code** minimal pour passer
3. **Refactorer** le code
4. **Répéter**

## ❓ FAQ

### Les tests sont lents

→ Utilisez `test:watch` qui ne relance que les tests modifiés.

### Erreur "Cannot find module 'jquery'"

→ Lancez `npm install` pour installer toutes les dépendances.

### Les tests passent localement mais pas en CI

→ Vérifiez les versions de Node.js et npm entre local et CI.

### Comment mocker un module externe ?

```javascript
jest.mock('mon-module', () => ({
    maFonction: jest.fn()
}));
```

## 📚 Ressources

- [Jest Docs](https://jestjs.io/)
- [WordPress Scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- [README complet](./README.md)

## 🎓 Formation

Pour approfondir :

1. Lire la [documentation Jest](https://jestjs.io/docs/getting-started)
2. Pratiquer avec [Jest Crash Course](https://www.youtube.com/watch?v=7r4xVDI2vho)
3. Explorer le code des tests existants

---

**Besoin d'aide ?** Contactez l'équipe Alma ou ouvrez une issue sur GitHub.

