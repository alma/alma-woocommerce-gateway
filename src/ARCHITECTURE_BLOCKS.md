# Architecture des Blocks Alma pour WooCommerce

## Vue d'ensemble

L'intégration des blocks Alma pour WooCommerce permet d'utiliser les moyens de paiement Alma dans le checkout
WooCommerce Blocks.

## Architecture Globale

```
┌─────────────────────────────────────────────────────────────────────┐
│                        WooCommerce Checkout Page                    │
│                     (avec WooCommerce Blocks actifs)                │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         PHP Backend Layer                           │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  AbstractGatewayBlock.php                                     │  │
│  │  - is_active(): vérifie si le block est actif                 │  │
│  │  - get_payment_method_data(): retourne les settings           │  │
│  │  - get_payment_method_script_handles(): charge les assets     │  │ 
│  │  - format_eligibility_for_block(): formate les eligibilities  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                    │                                │
│                                    ▼                                │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  Concrete Blocks (extend AbstractGatewayBlock)                │  │
│  │  - PayNowGatewayBlock                                         │  │
│  │  - PnxGatewayBlock                                            │  │
│  │  - PayLaterGatewayBlock                                       │  │
│  │  - CreditGatewayBlock                                         │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ wp_localize_script
                                    │ - BlocksData (params globaux)
                                    │ - {gateway}_block_data (settings)
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      JavaScript Frontend Layer                      │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  alma-gateway-block.js (Point d'entrée principal)             │  │
│  │  ┌─────────────────────────────────────────────────────────┐  │  │
│  │  │  init_gateway_block()                                   │  │  │
│  │  │  - Monte CartObserver                                   │  │  │
│  │  │  - Enregistre les gateways initiales                    │  │  │
│  │  └─────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                    │                                │
│                                    ▼                                │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  CartObserver (React Component)                               │  │
│  │  - Observe les changements du panier (total, shipping)        │  │
│  │  - Déclenche fetchAlmaEligibility() sur changement            │  │
│  │  - Re-enregistre les gateways quand eligibility change        │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                    │                                │
│                                    ▼                                │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  register_payment_gateway_block()                             │  │
│  │  - Pour chaque gateway eligible                               │  │
│  │  - Génère le contenu du block                                 │  │
│  │  - Enregistre via wcBlocksRegistry.registerPaymentMethod()    │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                    │                                │
│                                    ▼                                │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  generateGatewayBlock()                                       │  │
│  │  Retourne un objet avec:                                      │  │
│  │  - name: ID de la gateway                                     │  │
│  │  - label: Composant React Label                               │  │
│  │  - content: AlmaBlockComponent                                │  │
│  │  - canMakePayment: fonction de validation                     │  │
│  │  - placeOrderButtonLabel: texte du bouton                     │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

## Composants React

```
┌─────────────────────────────────────────────────────────────────────┐
│  AlmaBlockComponent (Factory Component)                             │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  - Reçoit props de WooCommerce (eventRegistration, etc.)      │  │
│  │  - Enregistre onPaymentProcessing hook                        │  │
│  │  - Décide entre DisplayAlmaBlock ou DisplayAlmaInPageBlock    │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                               │                                     │
│                  ┌────────────┴────────────┐                        │
│                  ▼                         ▼                        │
│      ┌────────────────────────┐  ┌────────────────────────┐         │
│      │ DisplayAlmaBlock       │  │ DisplayAlmaInPageBlock │         │
│      │ (Mode Standard)        │  │ (Mode In-Page)         │         │
│      └────────────────────────┘  └────────────────────────┘         │
│                  │                         │                        │
│                  └────────────┬────────────┘                        │
│                               ▼                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  AlmaBlock (Composant de rendu principal)                     │  │
│  │  ┌─────────────────────────────────────────────────────────┐  │  │
│  │  │  IntlProvider (gestion des traductions)                 │  │  │
│  │  │  ├─ ToggleButtonsField (sélection du plan)              │  │  │
│  │  │  └─ Installments (affichage des échéances)              │  │  │
│  │  │     └─ InstallmentsContent                              │  │  │
│  │  │        ├─ Installment (une échéance)                    │  │  │
│  │  │        └─ InstallmentsTotal (total + frais)             │  │  │
│  │  └─────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

## Flux de Données (Redux Store)

```
┌─────────────────────────────────────────────────────────────────────┐
│  alma-store.js (Redux Store)                            │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  State:                                                       │  │
│  │  - almaEligibility: { eligibility: {}, cart_total: number }   │  │
│  │  - selectedFeePlan: string | null                             │  │
│  │  - isLoading: boolean                                         │  │
│  │  ┌─────────────────────────────────────────────────────────┐  │  │
│  │  │  Actions:                                               │  │  │
│  │  │  - setAlmaEligibility(data)                             │  │  │
│  │  │  - setSelectedFeePlan(plan)                             │  │  │
│  │  │  - setLoading(isLoading)                                │  │  │
│  │  └─────────────────────────────────────────────────────────┘  │  │
│  │  ┌─────────────────────────────────────────────────────────┐  │  │
│  │  │  Selectors:                                             │  │  │
│  │  │  - getAlmaEligibility() → eligibility object            │  │  │
│  │  │  - getSelectedFeePlan() → plan key                      │  │  │
│  │  │  - isLoading() → boolean                                │  │  │
│  │  │  - getCartTotal() → number                              │  │  │
│  │  └─────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

## Flux de Paiement

### Mode Standard (Redirection)

```
User clicks "Place Order"
        │
        ▼
onPaymentProcessing (alma-gateway-block.js)
        │
        ▼
handleStandardPayment()
        │
        ├─ Récupère selectedPlan du store
        ├─ Construit paymentMethodData
        │  ├─ alma_fee_plan
        │  └─ payment_method
        │
        ▼
Retourne SUCCESS avec paymentMethodData
        │
        ▼
onPaymentSetup (DisplayAlmaBlock.js)
        │
        ├─ Ajoute le nonce
        ├─ Ajoute alma_plan_key
        │
        ▼
WooCommerce envoie POST à /wc/store/v1/checkout
        │
        ▼
Backend PHP traite le paiement
        │
        ├─ Crée la commande
        ├─ Appelle l'API Alma
        │
        ▼
Redirection vers la page de paiement Alma
```

### Mode In-Page

```
User clicks "Place Order"
        │
        ▼
onPaymentProcessing (alma-gateway-block.js)
        │
        ▼
handleInPagePayment()
        │
        ├─ Récupère selectedPlan du store
        ├─ Construit paymentMethodData
        │  ├─ alma_plan_key
        │  └─ payment_method
        │
        ▼
Retourne SUCCESS avec paymentMethodData
        │
        ▼
onPaymentSetup (DisplayAlmaInPageBlock.js)
        │
        ├─ Ajoute le nonce
        │
        ▼
WooCommerce envoie POST à /wc/store/v1/checkout
        │
        ▼
Backend PHP traite le paiement
        │
        ├─ Crée la commande
        ├─ Appelle l'API Alma
        ├─ Retourne payment_id
        │
        ▼
JavaScript: inPage.startPayment({ paymentId })
        │
        ▼
Modal Alma s'affiche avec iframe de paiement
        │
        ▼
User complete le paiement dans la modal
```

## Gestion de l'Eligibilité

```
┌─────────────────────────────────────────────────────────────────────┐
│  Flux d'Eligibilité                                                 │
│                                                                     │
│  Page Load                                                          │
│      │                                                              │
│      ▼                                                              │
│  init_gateway_block()                                               │
│      │                                                              │
│      ├─ Enregistre les gateways avec BlocksData.init_eligibility    │
│      └─ Monte CartObserver                                          │
│              │                                                      │
│              ▼                                                      │
│  CartObserver observe:                                              │
│      - cartTotal (WooCommerce CART_STORE)                           │
│      - shippingRates (WooCommerce CART_STORE)                       │
│              │                                                      │
│              ▼                                                      │
│  Changement détecté?                                                │
│      │ (via useRef pour éviter boucles)                             │
│      ▼                                                              │
│  fetchAlmaEligibility(storeKey, url)                                │
│      │                                                              │
│      ├─ dispatch.setLoading(true)                                   │
│      ├─ fetch(BlocksData.url) → GET                                 │
│      │   └─ PHP: AbstractGatewayBlock::get_block_data()             │
│      │       └─ format_eligibility_for_block()                      │
│      │           └─ EligibilityProvider::getEligibilityList()       │
│      │               └─ API Alma: POST /payments/eligibility        │
│      │                                                              │
│      ├─ dispatch.setAlmaEligibility(data)                           │
│      └─ dispatch.setLoading(false)                                  │
│              │                                                      │
│              ▼                                                      │
│  eligibility change détecté                                         │
│      │                                                              │
│      ▼                                                              │
│  register_payment_gateway_block(eligibility)                        │
│      │                                                              │
│      └─ Pour chaque gateway:                                        │
│          ├─ Si eligible → registerPaymentMethod()                   │
│          └─ Si non eligible → unregisterPaymentMethod()             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Structure des Données

### BlocksData (Global, localisé depuis PHP)

```javascript
{
    url: string,              // URL pour fetchAlmaEligibility
            init_eligibility
:
    {       // Eligibilité initiale
        alma_paynow_gateway: {
        }
    ,
        alma_pnx_gateway: {
        }
    ,
        alma_paylater_gateway: {
        }
    ,
        alma_credit_gateway: {
        }
    }
,
    cart_total: number,
            nonce_value
:
    string,      // Nonce global (optionnel)
            is_in_page
:
    boolean,
            merchant_id
:
    string,      // Si in_page
            environment
:
    string,      // Si in_page (TEST/LIVE)
            language
:
    string,         // Si in_page
            ajax_url
:
    string          // Si in_page
}
```

### Settings (Par Gateway, via wcSettings)

```javascript
{
    name: string,             // 'alma_paynow_gateway'
            title
:
    string,            // 'Pay with Alma'
            description
:
    string,      // Description affichée
            gateway_name
:
    string,     // 'alma_paynow_gateway'
            label_button
:
    string,     // 'Pay With Alma'
            nonce_value
:
    string,      // Nonce spécifique à la gateway
            is_in_page
:
    boolean       // Mode In-Page activé
}
```

### Eligibility Structure

```javascript
{
    alma_paynow_gateway: {
        'general_1_0_0'
    :
        {      // Plan key
            planKey: 'general_1_0_0',
                    paymentPlan
        :
            [
                {
                    customer_fee: 0,
                    due_date: 1234567890,
                    purchase_amount: 10000,
                    total_amount: 10000,
                    localized_due_date: "Aujourd'hui"
                }
            ],
                    customerTotalCostAmount
        :
            0,
                    installmentsCount
        :
            1,
                    deferredDays
        :
            0,
                    deferredMonths
        :
            0,
                    annualInterestRate
        :
            0
        }
    }
,
    alma_pnx_gateway: {
        'general_3_0_0'
    :
        { ...
        }
    ,
        'general_4_0_0'
    :
        { ...
        }
    }
,
    alma_paylater_gateway: { ...
    }
,
    alma_credit_gateway: { ...
    }
}
```

### Payment Data (Envoyé à WooCommerce)

```javascript
{
    payment_method: 'alma_pnx_gateway',
            payment_data
:
    [
        {
            key: 'alma_checkout_noncealma_pnx_gateway',
            value: 'abc123...'  // Le nonce
        },
        {
            key: 'alma_plan_key',
            value: 'general_3_0_0'
        },
        {
            key: 'payment_method',
            value: 'alma_pnx_gateway'
        },
        {
            key: 'wc-alma_pnx_gateway-new-payment-method',
            value: false  // Ajouté automatiquement par WooCommerce
        }
    ]
}
```

## Hooks WooCommerce Blocks

### onPaymentSetup (DisplayAlmaBlock.js)

- **Timing**: Appelé avant l'envoi de la requête checkout
- **Usage**: Préparer les données de paiement (nonce, plan sélectionné)
- **Retour**: `{ type, meta: { paymentMethodData } }`

### onPaymentProcessing (alma-gateway-block.js)

- **Timing**: Appelé pendant le traitement du paiement
- **Usage**: Valider et compléter les données de paiement
- **Retour**: `{ type, meta: { paymentMethodData } }` ou `{ type: ERROR, message }`

## Points Clés d'Architecture

### 1. Séparation des Responsabilités

- **PHP Backend**: Configuration, eligibility, enregistrement des blocks
- **JavaScript**: UI, interaction utilisateur, gestion d'état
- **Redux Store**: État global partagé entre composants

### 2. Éviter les Boucles Infinies

- Utilisation de `useRef` pour tracker l'état précédent
- Flag `isFetching` pour éviter les appels parallèles
- Dépendances bien définies dans `useEffect`

### 3. Gestion d'État

- State local (`useState`) pour l'UI immédiate
- Redux store pour les données partagées
- Synchronisation via `useEffect` + `dispatch`

### 4. Validation des Données

- WooCommerce n'accepte que `string` ou `boolean` dans `payment_data`
- Conversion explicite avec `String()` pour éviter les erreurs
- Éviter les objets complexes dans `paymentMethodData`

### 5. Modes de Paiement

- **Standard**: Redirection vers page Alma
- **In-Page**: Modal Alma dans le checkout (iframe)
- Gestion séparée mais architecture similaire

## Améliorations Possibles

1. **TypeScript**: Typage des props et des données
2. **Tests**: Tests unitaires des composants React
3. **Performance**: Memoization avec `useMemo` et `useCallback`
4. **Erreurs**: Meilleure gestion des erreurs utilisateur
5. **Logging**: Logs structurés pour le debugging

---

*Document généré le: 20 octobre 2025*

