# Tests JavaScript - Alma WooCommerce Gateway

## 📋 Vue d'ensemble

Ce répertoire contient les tests unitaires JavaScript pour le plugin Alma WooCommerce Gateway, avec un focus particulier
sur le script `alma-in-page.js` qui gère l'intégration du paiement in-page Alma.

## 🚀 Installation

Les dépendances de test sont déjà incluses dans `@wordpress/scripts`. Pour installer toutes les dépendances :

```bash
npm install
```

## ▶️ Lancer les tests

### Lancer tous les tests

```bash
npm test
```

### Lancer les tests en mode watch (re-run automatique à chaque modification)

```bash
npm run test:watch
```

### Lancer les tests avec rapport de couverture

```bash
npm run test:coverage
```

Le rapport de couverture sera généré dans le dossier `coverage/` :

- `coverage/lcov-report/index.html` : Rapport HTML détaillé
- Terminal : Résumé de la couverture

## 📁 Structure des tests

```
tests/js/
├── setup-tests.js           # Configuration globale pour Jest
├── alma-in-page.test.js     # Tests unitaires pour alma-in-page.js
└── README.md               # Ce fichier
```

## 🧪 Tests couverts

### `alma-in-page.test.js`

Tests pour le script de paiement in-page Alma :

#### 1. **Initialization Protection**

- Protection contre les exécutions multiples du script
- Gestion du flag `window.almaInPageInitialized`

#### 2. **hideAlmaPaymentMethods()**

- Masquage silencieux des gateways Alma
- Sauvegarde du mode de paiement sélectionné
- Sélection automatique d'un autre mode de paiement
- Protection contre l'écrasement de la sauvegarde

#### 3. **showAlmaPaymentMethods()**

- Réaffichage des gateways Alma
- Re-sélection automatique du mode précédent
- Gestion du cas où aucun mode n'était sauvegardé

#### 4. **displayErrorMessage()**

- Affichage conditionnel (seulement si Alma sélectionné)
- Suppression des messages d'erreur précédents
- Non-affichage si Alma n'est pas sélectionné

#### 5. **getAmount()**

- Extraction du montant du total de commande
- Conversion en centimes
- Gestion de différents formats de devise (€, $)
- Gestion des valeurs invalides

#### 6. **Plan selection and iframe mounting**

- Génération du sélecteur unique par plan
- Affichage/masquage des divs de plan
- Appel au SDK Alma avec les bons paramètres

#### 7. **SDK Polling**

- Détection immédiate si SDK déjà chargé
- Protection contre les instances multiples de polling
- Gestion du flag `window.almaSDKPollingActive`

#### 8. **URL parameter handling**

- Détection de l'URL de paiement in-page
- Extraction du payment ID
- Réinitialisation des flags

#### 9. **Payment started flag**

- Protection contre les démarrages multiples
- Réinitialisation à la fermeture de la modale

#### 10. **Gateway methods object**

- Construction correcte de l'objet `almaMethods`
- Mappage gateway → type + sélecteurs

## 🎯 Objectifs de couverture

- **Couverture globale cible** : > 80%
- **Fonctions critiques** : 100% (hideAlmaPaymentMethods, showAlmaPaymentMethods, displayErrorMessage)
- **Branches** : > 75%

## 🔧 Configuration

### `jest.config.js`

Configuration Jest personnalisée :

- Environment : `jsdom` (simulation navigateur)
- Setup file : `tests/js/setup-tests.js`
- Coverage : Reports HTML + LCOV + Text
- Globals : Mocks pour WordPress, WooCommerce, Alma SDK

### `setup-tests.js`

Initialisation avant chaque test :

- Mock de jQuery
- Mock des globals WordPress (`wp`)
- Mock des globals WooCommerce (`wc`)
- Mock du SDK Alma (`Alma.InPage`)
- Mock de `alma_in_page_settings`
- Matchers personnalisés

## 📝 Écrire de nouveaux tests

### Template de base

```javascript
describe('Ma fonctionnalité', () => {
    beforeEach(() => {
        // Setup avant chaque test
        document.body.innerHTML = `...`;
        jest.clearAllMocks();
    });

    test('devrait faire quelque chose', () => {
        // Arrange
        const input = 'test';

        // Act
        const result = maFonction(input);

        // Assert
        expect(result).toBe('expected');
    });
});
```

### Bonnes pratiques

1. **AAA Pattern** : Arrange, Act, Assert
2. **Isolation** : Chaque test doit être indépendant
3. **Nomenclature** : `should + action + expected result`
4. **Coverage** : Tester les cas nominaux ET les cas d'erreur
5. **Mocking** : Mocker les dépendances externes (AJAX, SDK, etc.)

## 🐛 Debugging

### Voir les logs dans les tests

Par défaut, les `console.log` sont mockés. Pour les voir :

```javascript
// Dans votre test
console.log.mockRestore();
console.log('Debug info');
```

### Lancer un seul test

```bash
npm test -- alma-in-page.test.js
```

### Lancer un seul describe/test

```javascript
describe.only('Ma fonctionnalité', () => { ...
});
test.only('devrait faire ça', () => { ...
});
```

## 📊 Rapport de couverture

Après `npm run test:coverage`, ouvrez :

```bash
open coverage/lcov-report/index.html
```

Vous verrez :

- % de lignes couvertes par fichier
- Branches non testées (en rouge)
- Fonctions non testées

## ❓ FAQ

### Les tests échouent avec "$ is not a function"

→ Vérifiez que `setup-tests.js` est bien exécuté et que jQuery est bien mocké.

### Comment tester les appels AJAX ?

```javascript
// Dans le test
$.ajax = jest.fn().mockResolvedValue({data: 'test'});

// Vérifier l'appel
expect($.ajax).toHaveBeenCalledWith(expect.objectContaining({
    url: '/expected-url'
}));
```

### Comment tester les timeouts/intervals ?

```javascript
jest.useFakeTimers();

// Code avec setTimeout
setTimeout(() => callback(), 1000);

// Avancer le temps
jest.advanceTimersByTime(1000);

// Vérifier
expect(callback).toHaveBeenCalled();

jest.useRealTimers();
```

## 📚 Ressources

- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Testing Library](https://testing-library.com/docs/queries/about)
- [WordPress Scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)

## 🤝 Contribuer

Lors de l'ajout de nouvelles fonctionnalités dans `alma-in-page.js` :

1. ✅ Écrire les tests AVANT le code (TDD)
2. ✅ Assurer une couverture > 80%
3. ✅ Tester les cas limites et erreurs
4. ✅ Lancer tous les tests avant de commit
5. ✅ Vérifier que la CI passe

## 🔄 CI/CD

Les tests sont exécutés automatiquement dans la CI GitHub Actions sur chaque PR.

Pour ajouter les tests à votre CI, ajoutez dans `.github/workflows/tests.yml` :

```yaml
-   name: Run JavaScript tests
    run: npm test

-   name: Check test coverage
    run: npm run test:coverage
```

---

**Dernière mise à jour** : Février 2025  
**Mainteneur** : Équipe Alma

