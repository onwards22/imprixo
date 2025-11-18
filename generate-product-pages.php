<?php
// Script pour générer toutes les pages produits avec configurateur Canva

$_SERVER['SERVER_NAME'] = 'localhost';
require_once __DIR__ . '/api/config.php';

try {
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->query("SELECT * FROM produits ORDER BY nom");
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $created = 0;

    foreach ($produits as $produit) {
        $code = $produit['code_produit'];
        $nom = htmlspecialchars($produit['nom']);
        $categorie = htmlspecialchars($produit['categorie']);
        $description = htmlspecialchars($produit['description_longue']);
        $epaisseur = htmlspecialchars($produit['epaisseur'] ?? 'N/A');
        $usage = htmlspecialchars($produit['usage'] ?? 'Voir description');

        // Prix par défaut
        $prix_base = $produit['prix_0_10'];
        $prix_11_50 = $produit['prix_11_50'];
        $prix_51_100 = $produit['prix_51_100'];
        $prix_101_300 = $produit['prix_101_300'];
        $prix_300_plus = $produit['prix_300_plus'];

        // Emoji par catégorie
        $emoji = '📄';
        if (strpos($categorie, 'Aluminium') !== false || strpos($categorie, 'Dibond') !== false) $emoji = '✨';
        elseif (strpos($categorie, 'Bâche') !== false || strpos($categorie, 'Mesh') !== false) $emoji = '🎪';
        elseif (strpos($categorie, 'Textile') !== false) $emoji = '🎨';
        elseif (strpos($categorie, 'Acryl') !== false) $emoji = '💎';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$nom - Imprixo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        * { font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif; }
        .btn-primary { background: linear-gradient(135deg, #e63946 0%, #d62839 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(230, 57, 70, 0.4); }
        .btn-secondary { background: #2b2d42; transition: all 0.2s ease; }
        .btn-secondary:hover { background: #1a1b2e; }
        .nav-link { position: relative; }
        .nav-link:after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: #e63946; transition: width 0.2s ease; }
        .nav-link:hover:after { width: 100%; }
        .cart-badge { position: absolute; top: -8px; right: -8px; background: #e63946; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .canvas-area { background: repeating-conic-gradient(#f0f0f0 0% 25%, transparent 0% 50%) 50% / 20px 20px; position: relative; overflow: hidden; }
        .dimension-input { border: 2px solid #e63946; border-radius: 8px; padding: 12px; font-size: 18px; font-weight: 700; text-align: center; }
        .dimension-input:focus { outline: none; box-shadow: 0 0 0 4px rgba(230, 57, 70, 0.2); }
        .preview-panel { background: linear-gradient(135deg, #1a1b2e 0%, #2b2d42 100%); }
        .option-card { transition: all 0.3s ease; border: 3px solid transparent; }
        .option-card:hover { border-color: #e63946; transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .option-card.selected { border-color: #e63946; background: #fff1f2; }
        .price-badge { background: linear-gradient(135deg, #e63946 0%, #d62839 100%); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(230, 57, 70, 0.4); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease-out; }
    </style>
</head>
<body class="bg-gray-50">
    <div id="root"></div>
    <script type="text/babel">
        const { useState, useEffect, useRef } = React;

        function getCart() {
            try { return JSON.parse(localStorage.getItem('visuprint_cart') || '[]'); }
            catch { return []; }
        }

        function Header({ cartCount }) {
            return (
                <header className="bg-white border-b-2 border-gray-200 sticky top-0 z-50 shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between h-16">
                            <div className="flex items-center">
                                <a href="/"><span className="text-2xl font-black text-gray-900">Imprixo</span></a>
                                <nav className="hidden md:ml-10 md:flex md:space-x-8">
                                    <a href="/" className="nav-link text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">Accueil</a>
                                    <a href="/catalogue.html" className="nav-link text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">Catalogue</a>
                                    <a href="/mon-compte.php" className="nav-link text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">Mon compte</a>
                                </nav>
                            </div>
                            <div className="flex items-center space-x-4">
                                <a href="/panier.html" className="relative btn-secondary text-white px-4 py-2 rounded text-sm font-semibold">
                                    🛒 Panier
                                    {cartCount > 0 && <span className="cart-badge">{cartCount}</span>}
                                </a>
                            </div>
                        </div>
                    </div>
                </header>
            );
        }

        function App() {
            const [config, setConfig] = useState({
                width: 100, height: 150, quantity: 1,
                printSide: 'simple', hasEyelets: false,
                hasCutting: false, hasLamination: false,
                uploadedFile: null, filePreview: null
            });
            const [cartCount, setCartCount] = useState(0);
            const fileInputRef = useRef(null);

            useEffect(() => {
                setCartCount(getCart().length);
            }, []);

            const surface = (config.width * config.height) / 10000;
            const totalSurface = surface * config.quantity;

            let pricePerM2 = $prix_base;
            if (totalSurface > 300) pricePerM2 = $prix_300_plus;
            else if (totalSurface > 100) pricePerM2 = $prix_101_300;
            else if (totalSurface > 50) pricePerM2 = $prix_51_100;
            else if (totalSurface > 10) pricePerM2 = $prix_11_50;

            let totalPrice = surface * pricePerM2 * config.quantity;
            if (config.printSide === 'double') totalPrice *= 1.3;
            if (config.hasEyelets) totalPrice += surface * 2 * config.quantity;
            if (config.hasCutting) totalPrice += surface * 1.5 * config.quantity;
            if (config.hasLamination) totalPrice += surface * 5 * config.quantity;

            const handleFileUpload = (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        setConfig({...config, uploadedFile: file, filePreview: reader.result});
                    };
                    reader.readAsDataURL(file);
                }
            };

            const addToCart = () => {
                const cart = getCart();
                cart.push({
                    name: '$nom',
                    code: '$code',
                    surface,
                    width: config.width,
                    height: config.height,
                    quantity: config.quantity,
                    printSide: config.printSide,
                    hasEyelets: config.hasEyelets,
                    hasCutting: config.hasCutting,
                    hasLamination: config.hasLamination,
                    price: totalPrice
                });
                localStorage.setItem('visuprint_cart', JSON.stringify(cart));
                window.location.href = '/panier.html';
            };

            return (
                <div className="min-h-screen bg-gray-50">
                    <Header cartCount={cartCount} />

                    {/* Breadcrumb */}
                    <div className="bg-white border-b border-gray-200 py-3">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center text-sm text-gray-600">
                                <a href="/" className="hover:text-red-600">Accueil</a>
                                <span className="mx-2">›</span>
                                <a href="/catalogue.html" className="hover:text-red-600">Catalogue</a>
                                <span className="mx-2">›</span>
                                <span className="text-gray-900 font-bold">$nom</span>
                            </div>
                        </div>
                    </div>

                    <section className="py-8">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="grid lg:grid-cols-3 gap-8">
                                {/* CANVAS PREVIEW */}
                                <div className="lg:col-span-2">
                                    <div className="bg-white rounded-2xl border-2 border-gray-200 overflow-hidden shadow-lg">
                                        {/* Toolbar */}
                                        <div className="bg-gray-900 text-white p-4 flex items-center justify-between">
                                            <div>
                                                <h2 className="text-xl font-black">$nom</h2>
                                                <p className="text-sm text-gray-400">$categorie</p>
                                            </div>
                                            <div className="flex gap-2">
                                                <button onClick={() => fileInputRef.current?.click()} className="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-bold text-sm">
                                                    📁 Upload Fichier
                                                </button>
                                                <input ref={fileInputRef} type="file" accept="image/*,.pdf" onChange={handleFileUpload} className="hidden" />
                                            </div>
                                        </div>

                                        {/* Canvas Area */}
                                        <div className="p-8 bg-gray-100">
                                            <div className="canvas-area bg-white rounded-lg shadow-inner" style={{ height: '500px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                                {config.filePreview ? (
                                                    <img src={config.filePreview} alt="Preview" className="max-w-full max-h-full object-contain" />
                                                ) : (
                                                    <div className="text-center">
                                                        <div className="text-8xl mb-4">$emoji</div>
                                                        <p className="text-xl font-bold text-gray-900 mb-2">{config.width} × {config.height} cm</p>
                                                        <p className="text-gray-500">Surface: {surface.toFixed(3)} m²</p>
                                                        <button onClick={() => fileInputRef.current?.click()} className="mt-6 bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-bold">
                                                            + Ajouter votre fichier
                                                        </button>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Dimensions */}
                                            <div className="mt-4 grid grid-cols-2 gap-3">
                                                <div className="bg-white rounded-lg p-4 border-2 border-gray-200">
                                                    <label className="block text-sm font-bold text-gray-700 mb-2">📏 Largeur (cm)</label>
                                                    <input type="number" value={config.width} onChange={(e) => setConfig({...config, width: parseFloat(e.target.value) || 0})} className="dimension-input w-full" min="1" max="500" />
                                                </div>
                                                <div className="bg-white rounded-lg p-4 border-2 border-gray-200">
                                                    <label className="block text-sm font-bold text-gray-700 mb-2">📐 Hauteur (cm)</label>
                                                    <input type="number" value={config.height} onChange={(e) => setConfig({...config, height: parseFloat(e.target.value) || 0})} className="dimension-input w-full" min="1" max="500" />
                                                </div>
                                            </div>
                                        </div>

                                        {/* Options */}
                                        <div className="p-6 bg-white border-t-2 border-gray-200">
                                            <h3 className="text-lg font-black text-gray-900 mb-4">⚙️ Options d'impression</h3>
                                            <div className="grid grid-cols-2 gap-4">
                                                <button onClick={() => setConfig({...config, printSide: config.printSide === 'simple' ? 'double' : 'simple'})} className={\`option-card p-4 rounded-lg bg-white cursor-pointer \${config.printSide === 'double' ? 'selected' : ''}\`}>
                                                    <div className="text-3xl mb-2">🔄</div>
                                                    <div className="font-bold text-gray-900">Double face</div>
                                                    <div className="text-sm text-gray-600">+30% du prix</div>
                                                </button>

                                                <button onClick={() => setConfig({...config, hasEyelets: !config.hasEyelets})} className={\`option-card p-4 rounded-lg bg-white cursor-pointer \${config.hasEyelets ? 'selected' : ''}\`}>
                                                    <div className="text-3xl mb-2">⭕</div>
                                                    <div className="font-bold text-gray-900">Œillets</div>
                                                    <div className="text-sm text-gray-600">+2€/m²</div>
                                                </button>

                                                <button onClick={() => setConfig({...config, hasCutting: !config.hasCutting})} className={\`option-card p-4 rounded-lg bg-white cursor-pointer \${config.hasCutting ? 'selected' : ''}\`}>
                                                    <div className="text-3xl mb-2">✂️</div>
                                                    <div className="font-bold text-gray-900">Découpe</div>
                                                    <div className="text-sm text-gray-600">+1.5€/m²</div>
                                                </button>

                                                <button onClick={() => setConfig({...config, hasLamination: !config.hasLamination})} className={\`option-card p-4 rounded-lg bg-white cursor-pointer \${config.hasLamination ? 'selected' : ''}\`}>
                                                    <div className="text-3xl mb-2">✨</div>
                                                    <div className="font-bold text-gray-900">Lamination</div>
                                                    <div className="text-sm text-gray-600">+5€/m²</div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* SIDEBAR */}
                                <div className="lg:col-span-1">
                                    <div className="bg-white rounded-2xl border-2 border-gray-200 overflow-hidden shadow-lg sticky top-24">
                                        <div className="preview-panel p-6 text-white">
                                            <h3 className="text-lg font-bold mb-4">💰 Récapitulatif</h3>
                                            <div className="space-y-3 mb-6">
                                                <div className="flex justify-between text-sm">
                                                    <span>Surface totale:</span>
                                                    <span className="font-bold">{totalSurface.toFixed(2)} m²</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span>Prix/m²:</span>
                                                    <span className="font-bold">{pricePerM2.toFixed(2)}€</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span>Quantité:</span>
                                                    <span className="font-bold">{config.quantity}</span>
                                                </div>
                                            </div>
                                            <div className="price-badge text-center fade-in">
                                                <div className="text-sm mb-1">TOTAL TTC</div>
                                                <div className="text-4xl font-black">{totalPrice.toFixed(2)}€</div>
                                            </div>
                                        </div>

                                        <div className="p-6 border-t-2 border-gray-200">
                                            <label className="block text-sm font-bold text-gray-700 mb-3">📦 Quantité</label>
                                            <div className="flex items-center gap-3">
                                                <button onClick={() => setConfig({...config, quantity: Math.max(1, config.quantity - 1)})} className="w-12 h-12 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-xl">-</button>
                                                <input type="number" value={config.quantity} onChange={(e) => setConfig({...config, quantity: parseInt(e.target.value) || 1})} className="flex-1 dimension-input" min="1" />
                                                <button onClick={() => setConfig({...config, quantity: config.quantity + 1})} className="w-12 h-12 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold text-xl">+</button>
                                            </div>
                                        </div>

                                        <div className="p-6 border-t-2 border-gray-200 space-y-3">
                                            <button onClick={addToCart} className="w-full btn-primary text-white py-4 rounded-lg font-black text-lg">
                                                🛒 Ajouter au Panier
                                            </button>
                                            <button className="w-full bg-gray-900 hover:bg-gray-800 text-white py-3 rounded-lg font-bold">
                                                📞 Demander un Devis
                                            </button>
                                        </div>

                                        <div className="p-6 bg-gray-50 text-sm text-gray-600 space-y-2">
                                            <div className="flex items-center gap-2"><span>✓</span><span>Livraison 48-72h</span></div>
                                            <div className="flex items-center gap-2"><span>✓</span><span>Qualité certifiée</span></div>
                                            <div className="flex items-center gap-2"><span>✓</span><span>Prix dégressifs</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Description */}
                            <div className="mt-12 bg-white rounded-2xl p-8 border-2 border-gray-200">
                                <h2 className="text-2xl font-black text-gray-900 mb-4">📝 Description</h2>
                                <p className="text-gray-700 leading-relaxed mb-6">$description</p>

                                <div className="grid md:grid-cols-3 gap-6 mt-8">
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="font-bold text-gray-900 mb-2">📏 Épaisseur</h4>
                                        <p className="text-gray-600">$epaisseur</p>
                                    </div>
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="font-bold text-gray-900 mb-2">🏠 Usage</h4>
                                        <p className="text-gray-600">$usage</p>
                                    </div>
                                    <div className="bg-gray-50 rounded-lg p-6">
                                        <h4 className="font-bold text-gray-900 mb-2">⏱️ Délai</h4>
                                        <p className="text-gray-600">3 jours ouvrés</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            );
        }

        ReactDOM.render(<App />, document.getElementById('root'));
    </script>
</body>
</html>
HTML;

        $filepath = __DIR__ . "/produit/$code.html";
        file_put_contents($filepath, $html);
        $created++;
        echo "✓ Créé: $filepath\n";
    }

    echo "\n✅ $created pages produits générées avec succès!\n";

} catch (Exception $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
}
