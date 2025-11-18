<?php
$page_title = "Samba 195g B1 - Impression Grand Format | Prix Dégressifs | Imprixo";
$page_description = "Samba 195g B1 pour impression grand format ✓ Prix dégressifs 22€→14.1€/m² ✓ Livraison 3j ✓ Qualité pro B1 ✓ Devis gratuit";
$canonical_url = "https://imprixo.fr/produit/SAMBA-195G-B1.php";
?>
<?php include(__DIR__ . '/../includes/header.php'); ?>

<!-- Schema.org Product -->
<script type="application/ld+json">
{
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "Samba 195g B1",
    "description": "Support textile Samba 195g B1",
    "brand": {
        "@type": "Brand",
        "name": "Imprixo"
    },
    "offers": {
        "@type": "AggregateOffer",
        "lowPrice": 14.1,
        "highPrice": 22,
        "priceCurrency": "EUR",
        "availability": "https://schema.org/InStock"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": 4.8,
        "reviewCount": 166
    }
}
</script>

<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

<style>
.trust-badge {
    background: white;
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.trust-badge:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.step-active {
    background: #e63946;
    color: white;
}

.step-completed {
    background: #10b981;
    color: white;
}

.step-inactive {
    background: #e5e7eb;
    color: #6b7280;
}

details summary {
    cursor: pointer;
    user-select: none;
}

details summary::-webkit-details-marker {
    display: none;
}

details[open] summary svg {
    transform: rotate(180deg);
}
</style>

<!-- Urgency Banner -->
<div class="bg-gradient-to-r from-red-600 to-red-500 text-white py-3 text-center">
    <p class="font-bold text-sm md:text-base">
        🔥 OFFRE LIMITÉE : Livraison GRATUITE dès 200€ + Devis sous 1h | Plus que <span class="pulse">34</span> en stock !
    </p>
</div>

<!-- Main Content -->
<div id="root"></div>

<script type="text/babel">
const { useState, useEffect, useRef } = React;

const PRODUCT_DATA = {
    code: 'SAMBA-195G-B1',
    nom: 'Samba 195g B1',
    sousTitre: 'Textile Samba',
    descCourte: `Support textile Samba 195g B1`,
    descLongue: `Support textile professionnel Samba 195g B1. Impression HD haute définition. Idéal pour événementiel, stands, kakémonos, roll-ups.`,
    categorie: 'Textiles imprimables standard',
    poids: 0.3,
    epaisseur: '-',
    formatMax: 'Illimité',
    usage: 'Intérieur/Extérieur selon produit',
    dureeVie: '2-5 ans',
    certification: 'B1',
    finition: 'Mat ou brillant',
    impressionFaces: 'Simple face',
    prix: {
        '0-10': 22,
        '11-50': 19.4,
        '51-100': 17.6,
        '101-300': 15.8,
        '300+': 14.1
    },
    delai: 3,
    stock: 34,
    rating: 4.8,
    reviewCount: 166
};

function StepIndicator({ currentStep }) {
    const steps = [
        { num: 1, name: 'Dimensions' },
        { num: 2, name: 'Fichier' },
        { num: 3, name: 'Options' },
        { num: 4, name: 'Quantité' },
        { num: 5, name: 'Récap' }
    ];

    return (
        <div className="mb-8">
            <div className="flex items-center justify-between mb-4">
                {steps.map((step, idx) => (
                    <React.Fragment key={step.num}>
                        <div className="flex flex-col items-center">
                            <div className={\`w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold text-sm md:text-base transition ${
                                currentStep === step.num ? 'step-active' :
                                currentStep > step.num ? 'step-completed' : 'step-inactive'
                            }\`}>
                                {currentStep > step.num ? '✓' : step.num}
                            </div>
                            <div className="text-xs md:text-sm mt-2 font-medium">{step.name}</div>
                        </div>
                        {idx < steps.length - 1 && (
                            <div className={\`flex-1 h-1 mx-2 ${currentStep > step.num ? 'bg-green-500' : 'bg-gray-200'}\`}></div>
                        )}
                    </React.Fragment>
                ))}
            </div>
        </div>
    );
}

function ProductConfigurator() {
    const [currentStep, setCurrentStep] = useState(1);
    const [config, setConfig] = useState({
        largeur: 100,
        hauteur: 100,
        fichier: null,
        finition: 'standard',
        quantite: 1,
        impressionFace: 'simple'
    });
    const [surface, setSurface] = useState(1);
    const [prixUnitaire, setPrixUnitaire] = useState(PRODUCT_DATA.prix['0-10']);
    const [prixTotal, setPrixTotal] = useState(PRODUCT_DATA.prix['0-10']);
    const [imagePreview, setImagePreview] = useState(null);
    const fileInputRef = useRef(null);

    useEffect(() => {
        const surf = (config.largeur * config.hauteur) / 10000;
        setSurface(surf);

        let prix = PRODUCT_DATA.prix['0-10'];
        const totalSurf = surf * config.quantite;

        if (totalSurf > 300) prix = PRODUCT_DATA.prix['300+'];
        else if (totalSurf > 100) prix = PRODUCT_DATA.prix['101-300'];
        else if (totalSurf > 50) prix = PRODUCT_DATA.prix['51-100'];
        else if (totalSurf > 10) prix = PRODUCT_DATA.prix['11-50'];

        setPrixUnitaire(prix);
        setPrixTotal(prix * surf * config.quantite);
    }, [config.largeur, config.hauteur, config.quantite]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setConfig({...config, fichier: file});
            const reader = new FileReader();
            reader.onloadend = () => setImagePreview(reader.result);
            reader.readAsDataURL(file);
        }
    };

    const addToCart = () => {
        const cartItem = {
            code: PRODUCT_DATA.code,
            nom: PRODUCT_DATA.nom,
            config,
            surface,
            prixUnitaire,
            prixTotal,
            image: imagePreview
        };
        let cart = JSON.parse(localStorage.getItem('visuprint_cart') || '[]');
        cart.push(cartItem);
        localStorage.setItem('visuprint_cart', JSON.stringify(cart));
        alert('✅ Produit ajouté au panier!');
        window.location.href = '/panier.html';
    };

    const economie = PRODUCT_DATA.prix['0-10'] - prixUnitaire;

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="max-w-6xl mx-auto">

                {/* Breadcrumb */}
                <nav className="text-sm mb-6 text-gray-600">
                    <a href="/index.html" className="hover:text-red-600">Accueil</a>
                    <span className="mx-2">/</span>
                    <a href="/catalogue.html" className="hover:text-red-600">Catalogue</a>
                    <span className="mx-2">/</span>
                    <span className="text-gray-900 font-medium">{PRODUCT_DATA.nom}</span>
                </nav>

                {/* Header Produit */}
                <div className="bg-white rounded-xl shadow-lg p-6 md:p-8 mb-8">
                    <h1 className="text-3xl md:text-4xl font-black text-gray-900 mb-2">{PRODUCT_DATA.nom}</h1>
                    <p className="text-lg text-gray-600 mb-2">{PRODUCT_DATA.sousTitre}</p>
                    <p className="text-gray-700 mb-4">{PRODUCT_DATA.descCourte}</p>

                    <div className="flex items-center mb-4">
                        <span className="text-yellow-500 text-xl mr-1">★★★★★</span>
                        <span className="font-bold text-gray-900">{PRODUCT_DATA.rating}</span>
                        <span className="text-gray-500 text-sm ml-1">({PRODUCT_DATA.reviewCount} avis)</span>
                        <span className="text-gray-400 text-sm ml-4">Réf: {PRODUCT_DATA.code}</span>
                    </div>

                    {/* Trust Badges */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="trust-badge">
                            <div className="text-3xl mb-2">⚡</div>
                            <div className="text-xs font-bold">Livraison Express</div>
                            <div className="text-xs text-gray-600">{PRODUCT_DATA.delai} jours</div>
                        </div>
                        <div className="trust-badge">
                            <div className="text-3xl mb-2">🔒</div>
                            <div className="text-xs font-bold">Paiement Sécurisé</div>
                            <div className="text-xs text-gray-600">SSL</div>
                        </div>
                        <div className="trust-badge">
                            <div className="text-3xl mb-2">↩️</div>
                            <div className="text-xs font-bold">Satisfait/Remboursé</div>
                            <div className="text-xs text-gray-600">30 jours</div>
                        </div>
                        <div className="trust-badge">
                            <div className="text-3xl mb-2">⭐</div>
                            <div className="text-xs font-bold">Note Clients</div>
                            <div className="text-xs text-gray-600">{PRODUCT_DATA.rating}/5</div>
                        </div>
                    </div>
                </div>

                {/* Configurateur */}
                <div className="bg-white rounded-xl shadow-lg p-6 md:p-8 mb-8">
                    <h2 className="text-2xl font-bold mb-6 text-gray-900">Configurez votre produit</h2>

                    <StepIndicator currentStep={currentStep} />

                    {/* Étape 1 */}
                    {currentStep === 1 && (
                        <div className="space-y-6">
                            <h3 className="text-xl font-bold">Dimensions</h3>
                            <div className="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium mb-2">Largeur (cm)</label>
                                    <input type="number" value={config.largeur}
                                           onChange={(e) => setConfig({...config, largeur: parseInt(e.target.value) || 0})}
                                           className="w-full px-4 py-3 border-2 rounded-lg focus:border-red-600 focus:outline-none"
                                           min="10" max="300" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-2">Hauteur (cm)</label>
                                    <input type="number" value={config.hauteur}
                                           onChange={(e) => setConfig({...config, hauteur: parseInt(e.target.value) || 0})}
                                           className="w-full px-4 py-3 border-2 rounded-lg focus:border-red-600 focus:outline-none"
                                           min="10" max="300" />
                                </div>
                            </div>
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div className="text-sm text-blue-900">
                                    <strong>Surface:</strong> {surface.toFixed(2)} m² (Max: {PRODUCT_DATA.formatMax} cm)
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Étape 2 */}
                    {currentStep === 2 && (
                        <div className="space-y-6">
                            <h3 className="text-xl font-bold">Importez votre fichier</h3>
                            <div onClick={() => fileInputRef.current?.click()}
                                 className="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer hover:border-red-600">
                                {imagePreview ? (
                                    <div>
                                        <img src={imagePreview} alt="Aperçu" className="max-h-64 mx-auto mb-4 rounded" />
                                        <p className="text-sm text-gray-600">Cliquez pour changer</p>
                                    </div>
                                ) : (
                                    <div>
                                        <div className="text-6xl mb-4">📁</div>
                                        <p className="text-lg font-medium mb-2">Cliquez pour importer</p>
                                        <p className="text-sm text-gray-600">PNG, JPG, PDF (50 MB max)</p>
                                    </div>
                                )}
                            </div>
                            <input ref={fileInputRef} type="file" onChange={handleFileChange}
                                   accept="image/*,.pdf" className="hidden" />
                        </div>
                    )}

                    {/* Étape 3 */}
                    {currentStep === 3 && (
                        <div className="space-y-6">
                            <h3 className="text-xl font-bold">Options</h3>
                            <div>
                                <label className="block text-sm font-medium mb-2">Face d'impression</label>
                                <select value={config.impressionFace}
                                        onChange={(e) => setConfig({...config, impressionFace: e.target.value})}
                                        className="w-full px-4 py-3 border-2 rounded-lg focus:border-red-600 focus:outline-none">
                                    <option value="simple">Simple face</option>
                                    {PRODUCT_DATA.impressionFaces.includes('double') && <option value="double">Double face</option>}
                                </select>
                            </div>
                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div className="text-sm text-green-900">
                                    ✓ <strong>Certification:</strong> {PRODUCT_DATA.certification}<br/>
                                    ✓ <strong>Durée de vie:</strong> {PRODUCT_DATA.dureeVie}<br/>
                                    ✓ <strong>Usage:</strong> {PRODUCT_DATA.usage}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Étape 4 */}
                    {currentStep === 4 && (
                        <div className="space-y-6">
                            <h3 className="text-xl font-bold">Quantité</h3>
                            <input type="number" value={config.quantite}
                                   onChange={(e) => setConfig({...config, quantite: parseInt(e.target.value) || 1})}
                                   className="w-full px-4 py-3 border-2 rounded-lg focus:border-red-600 focus:outline-none text-xl font-bold"
                                   min="1" />

                            <div className="bg-gray-50 rounded-lg p-6">
                                <h4 className="font-bold mb-4">Prix dégressifs (par m²)</h4>
                                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                                    <div className="bg-white p-3 rounded text-center border-2">
                                        <div className="text-xs text-gray-600 mb-1">0-10 m²</div>
                                        <div className="font-bold">{PRODUCT_DATA.prix['0-10']}€</div>
                                    </div>
                                    <div className="bg-white p-3 rounded text-center border-2">
                                        <div className="text-xs text-gray-600 mb-1">11-50 m²</div>
                                        <div className="font-bold text-green-600">{PRODUCT_DATA.prix['11-50']}€</div>
                                    </div>
                                    <div className="bg-white p-3 rounded text-center border-2">
                                        <div className="text-xs text-gray-600 mb-1">51-100 m²</div>
                                        <div className="font-bold text-green-600">{PRODUCT_DATA.prix['51-100']}€</div>
                                    </div>
                                    <div className="bg-white p-3 rounded text-center border-2">
                                        <div className="text-xs text-gray-600 mb-1">101-300 m²</div>
                                        <div className="font-bold text-green-600">{PRODUCT_DATA.prix['101-300']}€</div>
                                    </div>
                                    <div className="bg-white p-3 rounded text-center border-2 border-green-600">
                                        <div className="text-xs text-gray-600 mb-1">300+ m²</div>
                                        <div className="font-bold text-green-600">{PRODUCT_DATA.prix['300+']}€</div>
                                    </div>
                                </div>
                            </div>

                            {economie > 0 && (
                                <div className="bg-green-50 border-2 border-green-500 rounded-lg p-4 text-center">
                                    <div className="text-2xl font-black text-green-700">
                                        🎉 Vous économisez {economie.toFixed(2)}€/m² !
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Étape 5 */}
                    {currentStep === 5 && (
                        <div className="space-y-6">
                            <h3 className="text-xl font-bold">Récapitulatif</h3>
                            <div className="bg-gray-50 rounded-lg p-6 space-y-4">
                                <div className="flex justify-between pb-3 border-b">
                                    <span className="font-medium">Produit</span>
                                    <span className="font-bold">{PRODUCT_DATA.nom}</span>
                                </div>
                                <div className="flex justify-between pb-3 border-b">
                                    <span className="font-medium">Dimensions</span>
                                    <span className="font-bold">{config.largeur} × {config.hauteur} cm</span>
                                </div>
                                <div className="flex justify-between pb-3 border-b">
                                    <span className="font-medium">Quantité</span>
                                    <span className="font-bold">{config.quantite} ex.</span>
                                </div>
                                <div className="flex justify-between pb-3 border-b">
                                    <span className="font-medium">Prix/m²</span>
                                    <span className="font-bold">{prixUnitaire.toFixed(2)}€</span>
                                </div>
                                <div className="flex justify-between pt-3">
                                    <span className="text-xl font-black">TOTAL</span>
                                    <span className="text-3xl font-black text-red-600">{prixTotal.toFixed(2)}€</span>
                                </div>
                                {prixTotal >= 200 ? (
                                    <div className="bg-green-100 border border-green-400 rounded-lg p-3 text-center">
                                        <span className="text-green-800 font-bold">🎁 Livraison GRATUITE !</span>
                                    </div>
                                ) : (
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center text-sm">
                                        <span className="text-blue-900">Plus que {(200 - prixTotal).toFixed(2)}€ pour la livraison gratuite</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Navigation */}
                    <div className="flex justify-between mt-8 pt-6 border-t">
                        {currentStep > 1 && (
                            <button onClick={() => setCurrentStep(currentStep - 1)}
                                    className="px-6 py-3 bg-gray-200 hover:bg-gray-300 font-bold rounded-lg">
                                ← Retour
                            </button>
                        )}
                        {currentStep < 5 && (
                            <button onClick={() => setCurrentStep(currentStep + 1)}
                                    className="ml-auto px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-lg">
                                Continuer →
                            </button>
                        )}
                        {currentStep === 5 && (
                            <button onClick={addToCart}
                                    className="ml-auto px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-lg text-lg">
                                🛒 Ajouter au panier
                            </button>
                        )}
                    </div>
                </div>

                {/* Contenu SEO */}
                <div className="bg-white rounded-xl shadow-lg p-6 md:p-8">
                    <h2 className="text-3xl font-black mb-6">{PRODUCT_DATA.nom} : Le Guide Complet</h2>

                    <div className="prose max-w-none">
                        <h3 className="text-2xl font-bold mb-4">Description</h3>
                        <p className="text-gray-700 leading-relaxed mb-6">{PRODUCT_DATA.descLongue}</p>

                        <h3 className="text-2xl font-bold mb-4">Spécifications</h3>
                        <table className="w-full border-collapse mb-6">
                            <tbody>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Référence</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.code}</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Poids</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.poids} kg/m²</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Épaisseur</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.epaisseur}</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Format max</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.formatMax} cm</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Usage</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.usage}</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Durée de vie</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.dureeVie}</td>
                                </tr>
                                <tr className="border-b">
                                    <td className="py-3 px-4 font-bold bg-gray-50">Certification</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.certification}</td>
                                </tr>
                                <tr>
                                    <td className="py-3 px-4 font-bold bg-gray-50">Délai</td>
                                    <td className="py-3 px-4">{PRODUCT_DATA.delai} jours</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3 className="text-2xl font-bold mb-4">FAQ</h3>
                        <div className="space-y-4">
                            <details className="bg-gray-50 rounded-lg p-4">
                                <summary className="font-bold cursor-pointer">Quel est le délai de livraison ?</summary>
                                <p className="mt-3 text-gray-700">Le délai standard est de {PRODUCT_DATA.delai} jours ouvrés.</p>
                            </details>
                            <details className="bg-gray-50 rounded-lg p-4">
                                <summary className="font-bold cursor-pointer">Quelles dimensions maximales ?</summary>
                                <p className="mt-3 text-gray-700">Format maximum : {PRODUCT_DATA.formatMax} cm.</p>
                            </details>
                            <details className="bg-gray-50 rounded-lg p-4">
                                <summary className="font-bold cursor-pointer">Le prix comprend l'impression ?</summary>
                                <p className="mt-3 text-gray-700">Oui, tous nos prix incluent l'impression HD en quadrichromie.</p>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

ReactDOM.render(<ProductConfigurator />, document.getElementById('root'));
</script>

<?php include(__DIR__ . '/../includes/footer.php'); ?>