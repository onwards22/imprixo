<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Imprixo - Impression Grand Format Professionnelle'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Impression professionnelle grand format ✓ Forex, Dibond, Bâches PVC ✓ Prix dégressifs -40% ✓ Livraison 48h'; ?>">
    <link rel="canonical" href="<?php echo isset($canonical_url) ? $canonical_url : 'https://imprixo.fr'; ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');

        * {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e63946 0%, #d62839 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(230, 57, 70, 0.3);
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-green {
            background: #10b981;
            color: white;
        }

        .badge-red {
            background: #e63946;
            color: white;
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .8;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- HEADER -->
    <header class="bg-white border-b-2 border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Top Bar -->
            <div class="flex items-center justify-between py-2 text-sm border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">📧 contact@imprixo.fr</span>
                    <span class="text-gray-600">⏰ Lun-Ven 9h-18h</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="badge badge-green pulse">✓ Livraison 48h</span>
                    <span class="badge badge-red">-40% Volume</span>
                </div>
            </div>

            <!-- Main Header -->
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center">
                    <a href="/index.html" class="flex-shrink-0">
                        <span class="text-3xl font-black text-gray-900">Imprixo</span>
                    </a>
                    <nav class="hidden md:ml-12 md:flex md:space-x-8">
                        <a href="/catalogue.html" class="text-gray-700 hover:text-red-600 px-3 py-2 text-sm font-bold transition">
                            Catalogue
                        </a>
                        <a href="/index.html#categories" class="text-gray-700 hover:text-red-600 px-3 py-2 text-sm font-bold transition">
                            Catégories
                        </a>
                        <a href="/index.html#avantages" class="text-gray-700 hover:text-red-600 px-3 py-2 text-sm font-bold transition">
                            Nos Avantages
                        </a>
                        <a href="/mon-compte.php" class="text-gray-700 hover:text-red-600 px-3 py-2 text-sm font-bold transition">
                            Mon Compte
                        </a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:block text-right">
                        <div class="text-xs text-gray-500 font-semibold">☎️ Service client</div>
                        <div class="text-lg font-black text-red-600">01 23 45 67 89</div>
                    </div>
                    <a href="/panier.html" class="bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-lg text-sm font-bold shadow-lg transition">
                        🛒 Panier
                    </a>
                    <a href="/index.html#contact" class="btn-primary text-white px-8 py-3 rounded-lg text-sm font-bold shadow-lg">
                        📧 Contact
                    </a>
                </div>
            </div>
        </div>
    </header>
